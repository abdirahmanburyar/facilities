<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\MohDispense;
use App\Models\MohDispenseItem;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\MohDispenseImport;

class MohDispenseController extends Controller
{
    public function index(Request $request)
    {
        $per_page = $request->input('per_page') ?? 10;
        $query = MohDispense::query()
            ->where('facility_id', auth()->user()->facility_id)
            ->when($request->search, function ($query) use ($request) {
                $query->where('moh_dispense_number', 'like', '%' . $request->search . '%');
            })
            ->withCount('items')
            ->with('createdBy:id,name')
            ->latest();

        $mohDispenses = $query->paginate($per_page, ['*'], 'page', $request->input('page', 1))
            ->withQueryString();
        $mohDispenses->setPath(url()->current());

        return inertia('MohDispense/Index', [
            'mohDispenses' => $mohDispenses,
            'filters' => $request->only('search', 'per_page', 'page')
        ]);
    }

    public function create()
    {
        $user = auth()->user();
        $facility = $user->facility;
        
        $eligibleItemsCount = 0;
        if ($facility) {
            $eligibleItemsCount = $facility->eligibleProducts()->count();
        }

        return inertia('MohDispense/Create', [
            'eligibleItemsCount' => $eligibleItemsCount,
        ]);
    }

    public function store(Request $request)
    {
        try {
            // Debug file information
            if ($request->hasFile('excel_file')) {
                $file = $request->file('excel_file');
                logger()->info('File details:', [
                    'original_name' => $file->getClientOriginalName(),
                    'mime_type' => $file->getMimeType(),
                    'extension' => $file->getClientOriginalExtension(),
                    'size' => $file->getSize(),
                ]);
            }

            $request->validate([
                'excel_file' => 'required|file', // No size limit
            ], [
                'excel_file.required' => 'Please select a file to upload.',
                'excel_file.file' => 'The uploaded file is not valid.',
            ]);

            // Additional file type validation
            $file = $request->file('excel_file');
            $allowedExtensions = ['xlsx', 'xls', 'csv'];
            $extension = strtolower($file->getClientOriginalExtension());
            
            // Also check MIME type as fallback
            $allowedMimeTypes = [
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', // .xlsx
                'application/vnd.ms-excel', // .xls
                'text/csv', // .csv
                'application/csv', // .csv alternative
                'text/plain', // .csv sometimes reported as this
                'application/octet-stream' // fallback for some systems
            ];
            
            $mimeType = $file->getMimeType();
            
            if (!in_array($extension, $allowedExtensions) && !in_array($mimeType, $allowedMimeTypes)) {
                return response()->json([
                    'message' => 'Invalid file type. Please upload an Excel file (.xlsx, .xls) or CSV file (.csv). Detected: ' . $extension . ' (' . $mimeType . ')'
                ], 422);
            }

            // Create the MohDispense record first
            $mohDispense = MohDispense::create([
                'facility_id' => auth()->user()->facility_id,
                'created_by' => auth()->user()->id,
                'status' => 'draft',
            ]);

            // Store the Excel file temporarily
            $file = $request->file('excel_file');
            $fileName = 'moh_dispense_' . $mohDispense->id . '_' . time() . '.' . $file->getClientOriginalExtension();
            $filePath = $file->storeAs('temp/moh_dispenses', $fileName);
            
            // Update the MohDispense record with file info
            $mohDispense->update([
                'excel_file_name' => $file->getClientOriginalName(),
                'excel_file_path' => $filePath,
                'status' => 'draft'
            ]);
            
            logger()->info('MOH Dispense file uploaded successfully', [
                'moh_dispense_id' => $mohDispense->id,
                'file_name' => $file->getClientOriginalName(),
                'file_size' => $file->getSize(),
                'stored_path' => $filePath
            ]);
            
            return response()->json([
                'message' => 'Excel file uploaded successfully. You can now process it.',
                'moh_dispense_id' => $mohDispense->id,
                'moh_dispense_number' => $mohDispense->moh_dispense_number,
                'status' => 'draft'
            ], 200);

        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'Error creating MOH Dispense: ' . $th->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $mohDispense = MohDispense::with([
                'items.product:id,name',
                'createdBy:id,name',
                'facility:id,name'
            ])->findOrFail($id);

            return inertia('MohDispense/Show', [
                'mohDispense' => $mohDispense,
            ]);
        } catch (\Throwable $th) {
            return response()->json($th->getMessage(), 500);
        }
    }

    public function process($id)
    {
        try {
            $mohDispense = MohDispense::findOrFail($id);
            
            // Check if it's in draft status and has a file
            if ($mohDispense->status !== 'draft') {
                return response()->json(['message' => 'Only draft MOH dispenses can be processed.'], 400);
            }
            
            if (!$mohDispense->excel_file_path) {
                return response()->json(['message' => 'No Excel file found for processing.'], 400);
            }

            // Check if file exists
            if (!\Storage::exists($mohDispense->excel_file_path)) {
                return response()->json(['message' => 'Excel file not found on server.'], 400);
            }

            // Set longer execution time for import
            set_time_limit(0); // No time limit
            ini_set('memory_limit', '1024M'); // 1GB memory limit

            // Process the Excel file
            $filePath = \Storage::path($mohDispense->excel_file_path);
            
            logger()->info('Starting MOH Dispense processing', [
                'moh_dispense_id' => $mohDispense->id,
                'file_path' => $filePath
            ]);

            Excel::import(new MohDispenseImport($mohDispense->id), $filePath);
            
            // Update status to processed
            $mohDispense->update(['status' => 'processed']);
            
            logger()->info('MOH Dispense processing completed successfully', [
                'moh_dispense_id' => $mohDispense->id,
                'moh_dispense_number' => $mohDispense->moh_dispense_number
            ]);
            
            return response()->json([
                'message' => 'MOH Dispense processed successfully.',
                'moh_dispense_id' => $mohDispense->id,
                'moh_dispense_number' => $mohDispense->moh_dispense_number,
                'status' => 'processed'
            ], 200);

        } catch (\Throwable $th) {
            logger()->error('MOH Dispense processing failed', [
                'moh_dispense_id' => $id,
                'error' => $th->getMessage(),
                'file' => $th->getFile(),
                'line' => $th->getLine(),
                'trace' => $th->getTraceAsString()
            ]);
            
            return response()->json([
                'message' => 'Error processing MOH Dispense: ' . $th->getMessage()
            ], 500);
        }
    }

    public function submit($id)
    {
        try {
            $mohDispense = MohDispense::findOrFail($id);
            
            // Check if it's in draft status
            if ($mohDispense->status !== 'draft') {
                return back()->with('error', 'Only draft MOH dispenses can be submitted.');
            }

            // Update status to processed
            $mohDispense->update(['status' => 'processed']);

            return back()->with('success', 'MOH dispense submitted successfully.');

        } catch (\Throwable $th) {
            return back()->with('error', 'Error submitting MOH dispense: ' . $th->getMessage());
        }
    }

    public function downloadTemplate()
    {
        try {
            $user = auth()->user();
            $facility = $user->facility;
            
            if (!$facility) {
                return response()->json(['message' => 'User facility not found'], 404);
            }

            // Generate CSV content with only headers
            $csvContent = "item,batch_no,expiry_date,quantity,dispense_date,dispensed_by\n";

            // Generate filename with facility name
            $fileName = 'moh_dispense_template_' . str_replace(' ', '_', $facility->name) . '.csv';
            
            // Create temporary file
            $tempPath = tempnam(sys_get_temp_dir(), 'moh_template_');
            file_put_contents($tempPath, $csvContent);

            return response()->download($tempPath, $fileName)->deleteFileAfterSend(true);

        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'Error generating template: ' . $th->getMessage()
            ], 500);
        }
    }

}

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
            $request->validate([
                'excel_file' => 'required|file|mimes:xlsx,xls,csv|max:10240', // 10MB max
            ]);

            // Create the MohDispense record first
            $mohDispense = MohDispense::create([
                'facility_id' => auth()->user()->facility_id,
                'created_by' => auth()->user()->id,
                'status' => 'processing',
            ]);

            // Process the Excel file with chunks
            $file = $request->file('excel_file');
            
            try {
                // Import the Excel file using chunks
                Excel::import(new MohDispenseImport($mohDispense->id), $file);
                
                // Update status to processed
                $mohDispense->update(['status' => 'processed']);
                
                return response()->json([
                    'message' => 'MOH Dispense processed successfully.',
                    'moh_dispense_id' => $mohDispense->id,
                    'status' => 'processed'
                ], 200);
                
            } catch (\Exception $e) {
                // Update status to failed
                $mohDispense->update(['status' => 'failed']);
                
                return response()->json([
                    'message' => 'Error processing Excel file: ' . $e->getMessage()
                ], 500);
            }

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

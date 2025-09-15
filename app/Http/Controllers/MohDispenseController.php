<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\MohDispense;
use App\Models\MohDispenseItem;
use App\Models\Product;
use App\Jobs\ProcessMohDispenseImport;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
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
                'status' => 'draft',
            ]);

            // Store the uploaded file temporarily
            $file = $request->file('excel_file');
            $fileName = 'moh-dispenses/' . time() . '_' . $file->getClientOriginalName();
            $filePath = $file->storeAs('moh-dispenses', time() . '_' . $file->getClientOriginalName(), 'public');

            // Dispatch the job to process the Excel file
            ProcessMohDispenseImport::dispatch($mohDispense->id, $filePath);

            return response()->json([
                'message' => 'MOH Dispense upload queued successfully. Processing will begin shortly.',
                'moh_dispense_id' => $mohDispense->id,
                'status' => 'queued'
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

    public function downloadTemplate()
    {
        try {
            $user = auth()->user();
            $facility = $user->facility;
            
            if (!$facility) {
                return response()->json(['message' => 'User facility not found'], 404);
            }

            // Get eligible products for the facility
            $eligibleProducts = $facility->eligibleProducts()
                ->select('products.id', 'products.name', 'products.productID')
                ->get();

            // Generate CSV content
            $csvContent = "item,source,batch_no,expiry_date,quantity,dispense_date,dispensed_by\n";
            
            foreach ($eligibleProducts as $product) {
                $csvContent .= sprintf(
                    "%s,,,,\n",
                    $product->name // Include product name in item column, other columns empty
                );
            }

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

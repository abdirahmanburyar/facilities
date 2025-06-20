<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\FacilityInventory;
use App\Models\Product;
use App\Models\Dispence;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Http\Resources\DispenceResource;
use App\Services\FacilityInventoryMovementService;


class DispenceController extends Controller
{
    public function index(Request $request)
    {
        $per_page = $request->input('per_page') ?? 10;
        $query = Dispence::query()
            ->where('facility_id', auth()->user()->facility_id)
            ->when($request->search, function ($query) use ($request) {
                $query->where('patient_name', 'like', '%' . $request->search . '%')
                    ->orWhere('patient_phone', 'like', '%' . $request->search . '%')
                    ->orWhere('diagnosis', 'like', '%' . $request->search . '%')
                    ->orWhere('dispence_number', 'like', '%' . $request->search . '%');
            })
            ->withCount('items')
            ->with('dispenced_by:id,name')
            ->latest();

        $dispences = $query->paginate($per_page, ['*'], 'page', $request->input('page', 1))
            ->withQueryString();
        $dispences->setPath(url()->current());

        return inertia('Dispence/Index', [
            'dispences' => DispenceResource::collection($dispences),
            'filters' => $request->only('search', 'per_page', 'page')
        ]);
    }

    public function create()
    {
        $user =  auth()->user();
        $items = Product::whereHas('inventory', function($q) use($user){
            $q->where('facility_id', $user->facility_id);
        })
        ->select('id','name')
        ->get();
        return inertia('Dispence/Create', [
            'inventories' => $items,
        ]);
    }

    public function checkInventory(Request $request){
        try {
            $request->validate([
                'product_id' => 'required',
                'quantity' => 'required',
            ]);
            $user = auth()->user();
            $inventory = FacilityInventory::where('facility_id', $user->facility_id)
                ->where('product_id', $request->product_id)
                ->withSum('items', 'quantity')
                ->first();
            logger()->info((int) $inventory->items_sum_quantity);
            return response()->json((int) $inventory->items_sum_quantity, 200);

        } catch (\Throwable $th) {
            return response()->json($th->getMessage(), 200);
        }
    }

    public function store(Request $request)
    {
        try {
            DB::beginTransaction();
            $validated = $request->validate([
                'patient_name' => 'required|string|max:255',
                'patient_age' => 'required|integer|min:1|max:120',
                'patient_gender' => 'required|in:male,female',
                'phone_number' => 'required|string|max:255',
                'diagnosis' => 'required|string|max:255',
                'items' => 'required|array',
                'items.*.product_id' => 'required|exists:products,id',
                'items.*.dose' => 'required|numeric',
                'items.*.frequency' => 'required|numeric',
                'items.*.duration' => 'required|numeric',
                'items.*.quantity' => 'required|numeric|min:1',
            ]);

            $dispence = Dispence::create([
                'patient_name' => $validated['patient_name'],
                'patient_age' => $validated['patient_age'],
                'patient_gender' => $validated['patient_gender'],
                'patient_phone' => $validated['phone_number'],
                'diagnosis' => $validated['diagnosis'],
                'dispence_date' => Carbon::now()->toDateString(),
                'dispenced_by' => auth()->user()->id,
                'facility_id' => auth()->user()->facility_id,
            ]);

            $facilityInventoryMovementService = new FacilityInventoryMovementService();

            foreach($validated['items'] as $item){
                $remainingQuantity = $item['quantity'];
                $inventories = FacilityInventory::where('facility_id', auth()->user()->facility_id)
                    ->where('product_id', $item['product_id'])
                    ->where('quantity', '>', 0)
                    ->orderBy('expiry_date', 'asc')
                    ->get();
                    
                foreach($inventories as $inventory){
                    if($remainingQuantity <= 0) break;
                    
                    $quantityToDeduct = min($remainingQuantity, $inventory->quantity);
                    
                    $dispenceItem = $dispence->items()->create([
                        'product_id' => $item['product_id'],
                        'dose' => $item['dose'],
                        'batch_number' => $inventory->batch_number,
                        'expiry_date' => $inventory->expiry_date,
                        'barcode' => $inventory->barcode,
                        'uom' => $inventory->uom ?? 'N/A',
                        'frequency' => $item['frequency'],
                        'duration' => $item['duration'],
                        'quantity' => $quantityToDeduct,
                    ]);
                    
                    $inventory->decrement('quantity', $quantityToDeduct);
                    $remainingQuantity -= $quantityToDeduct;

                    $dispence['quantity'] = $quantityToDeduct;
                    
                    // Record facility inventory movement for this dispense item
                    $facilityInventoryMovementService->recordDispenseIssued(
                        $dispence,
                        $dispenceItem,
                        auth()->user()->facility_id
                    );
                }
                
                // Check if we couldn't dispense the full quantity
                if($remainingQuantity > 0){
                    throw new \Exception("Insufficient inventory for product ID {$item['product_id']}. Remaining quantity: {$remainingQuantity}");
                }
            }
            
            DB::commit();
            return response()->json("Dispence created successfully", 200);

        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json($th->getMessage(), 500);
        }
    }

    public function show($id)
    {
        try {
            $dispence = Dispence::with('items.product:id,name','dispenced_by:id,name','facility')->findOrFail($id);
            return inertia('Dispence/Show', [
                'dispence' => $dispence,
            ]);
        } catch (\Throwable $th) {
            return response()->json($th->getMessage(), 500);
        }
    }
}

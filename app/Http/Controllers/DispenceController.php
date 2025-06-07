<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\FacilityInventory;
use App\Models\Dispence;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Http\Resources\DispenceResource;

class DispenceController extends Controller
{
    public function index(Request $request)
    {
        $per_page = $request->input('per_page') ?? 10;
        $query = Dispence::query()
            ->where('facility_id', auth()->user()->facility_id)
            ->when($request->search, function ($query) use ($request) {
                $query->where('patient_name', 'like', '%' . $request->search . '%')
                    ->orWhere('patient_phone', 'like', '%' . $request->search . '%');
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
        $inventories = FacilityInventory::where('facility_id', auth()->user()->facility_id)
            ->select(
                'facility_inventories.id',
                'facility_inventories.product_id',
                'facility_inventories.quantity',
                'facility_inventories.expiry_date',
                'products.name as product_name'
            )
            ->join('products', 'products.id', '=', 'facility_inventories.product_id')
            ->where('facility_inventories.quantity', '>', 0)
            ->where('facility_inventories.expiry_date', '>', Carbon::now())
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->product_id,
                    'name' => $item->product_name,
                    'quantity' => $item->quantity,
                    'expiry_date' => $item->expiry_date
                ];
            });

        return inertia('Dispence/Create', [
            'inventories' => $inventories,
        ]);
    }

    public function store(Request $request)
    {
        try {
            DB::beginTransaction();
            $validated = $request->validate([
                'patient_name' => 'required|string|max:255',
                'phone_number' => 'required|string|max:255',
                'diagnosis' => 'required|string|max:255',
                'items' => 'required|array',
                'items.*.product_id' => 'required|exists:products,id',
                'items.*.dose' => 'required|numeric',
                'items.*.frequency' => 'required|numeric',
                'items.*.start_date' => 'required|date',
                'items.*.duration' => 'required|numeric',
                'items.*.quantity' => 'required|numeric|min:1',
            ]);

            $dispence = Dispence::create([
                'patient_name' => $validated['patient_name'],
                'patient_phone' => $validated['phone_number'],
                'diagnosis' => $validated['diagnosis'],
                'dispence_date' => Carbon::now()->toDateString(),
                'dispenced_by' => auth()->user()->id,
                'facility_id' => auth()->user()->facility_id,
            ]);
            foreach($validated['items'] as $item){
                $totalQuantity = 0;
                foreach($validated['items'] as $item){
                    $remainingQuantity = $item['quantity'] - $totalQuantity;
                    $inventories = FacilityInventory::where('facility_id', auth()->user()->facility_id)
                        ->where('product_id', $item['product_id'])
                        ->where('quantity', '>', 0)
                        ->orderBy('expiry_date', 'asc')
                        ->get();
                    foreach($inventories as $inventory){
                        $quantityToDeduct = min($remainingQuantity, $inventory->quantity);
                        $dispence->items()->create([
                            'product_id' => $item['product_id'],
                            'dose' => $item['dose'],
                            'batch_number' => $inventory->batch_number,
                            'expiry_date' => $inventory->expiry_date,
                            'barcode' => $inventory->barcode,
                            'uom' => $inventory->uom ?? 'N/A',
                            'frequency' => $item['frequency'],
                            'start_date' => $item['start_date'],
                            'duration' => $item['duration'],
                            'quantity' => $quantityToDeduct,
                        ]);
                        $inventory->decrement('quantity', $quantityToDeduct);
                        $totalQuantity += $quantityToDeduct;
                        $remainingQuantity -= $quantityToDeduct;
                        if($remainingQuantity <= 0){
                            break 2;
                        }
                    }
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

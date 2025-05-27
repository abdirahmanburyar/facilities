<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\FacilityInventory;
use App\Models\Dispence;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DispenceController extends Controller
{
    public function index(Request $request)
    {
        $dispences = Dispence::query()
            ->where('facility_id', auth()->user()->facility_id)
            ->get();

        return inertia('Dispence/Index', [
            'dispences' => $dispences,
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
            $validated = $request->validate([
                'patient_name' => 'required|string|max:255',
                'phone_number' => 'required|string|max:255',
                'items' => 'required|array',
                'items.*.product_id' => 'required|exists:facility_inventories,id',
                'items.*.dose' => 'required|numeric',
                'items.*.frequency' => 'required|numeric',
                'items.*.start_date' => 'required|date',
                'items.*.duration' => 'required|numeric',
                'items.*.quantity' => 'required|numeric|min:1',
            ]);

            // First validate if we have enough stock for all items
            foreach ($validated['items'] as $item) {
                $inventory = FacilityInventory::where('id', $item['product_id'])
                    ->where('facility_id', auth()->user()->facility_id)
                    ->first();

                if (!$inventory) {
                    return response()->json(['error' => 'Product not found in your facility'], 404);
                }

                if ($inventory->quantity < $item['quantity']) {
                    return response()->json([
                        'error' => 'Insufficient stock for product: ' . $inventory->product->name,
                        'available' => $inventory->quantity,
                        'requested' => $item['quantity']
                    ], 422);
                }
            }

            // If all validations pass, wrap in transaction
            return DB::transaction(function () use ($validated) {
                // Generate dispence number (DIS-YYYYMMDD-XXXX)
                $today = Carbon::now();
                $latestDispence = Dispence::whereDate('created_at', $today)
                    ->latest()
                    ->first();

                $sequence = $latestDispence ? (int)substr($latestDispence->dispence_number, -4) + 1 : 1;
                $dispence_number = sprintf('DIS-%s-%04d', $today->format('Ymd'), $sequence);

                $dispence = Dispence::create([
                    'dispence_number' => $dispence_number,
                    'dispence_date' => $today,
                    'patient_name' => $validated['patient_name'],
                    'patient_phone' => $validated['phone_number'], // Changed from phone_number to patient_phone
                    'facility_id' => auth()->user()->facility_id,
                    'dispenced_by' => auth()->id(),
                ]);

                foreach ($validated['items'] as $item) {
                    $facilityInventory = FacilityInventory::lockForUpdate()
                        ->where('id', $item['product_id'])
                        ->where('facility_id', auth()->user()->facility_id)
                        ->first();

                    // Double-check quantity one last time
                    if ($facilityInventory->quantity < $item['quantity']) {
                        throw new \Exception('Stock changed while processing. Please try again.');
                    }

                    $facilityInventory->decrement('quantity', $item['quantity']);

                    $dispence->items()->create([
                        'product_id' => $item['product_id'],
                        'dose' => $item['dose'],
                        'frequency' => $item['frequency'],
                        'start_date' => $item['start_date'],
                        'duration' => $item['duration'],
                        'quantity' => $item['quantity'],
                        'created_by' => auth()->id(),
                        'updated_by' => auth()->id(),
                    ]);
                }

                return response()->json('Dispence created successfully', 200);
            });

        } catch (\Throwable $th) {
            return response()->json($th->getMessage(), 500);
        }
    }
}

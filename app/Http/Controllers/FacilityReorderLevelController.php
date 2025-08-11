<?php

namespace App\Http\Controllers;

use App\Models\FacilityReorderLevel;
use App\Models\Product;
use App\Models\Facility;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Log;

class FacilityReorderLevelController extends Controller
{
    public function index(Request $request)
    {
        $facilityId = auth()->user()->facility_id;

        $query = FacilityReorderLevel::with(['product:id,name,productID'])
            ->where('facility_id', $facilityId)
            ->latest();

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->whereHas('product', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('productID', 'like', "%{$search}%");
            });
        }

        $reorderLevels = $query->paginate($request->input('per_page', 25))->withQueryString();

        return Inertia::render('Inventory/ReorderLevel', [
            'reorderLevels' => $reorderLevels,
            'filters' => $request->only(['search', 'per_page'])
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.amc' => 'required|numeric|min:0',
            'items.*.lead_time' => 'required|integer|min:1',
        ]);

        $facilityId = auth()->user()->facility_id;
        if (!$facilityId) {
            abort(403, 'No facility assigned to the current user');
        }

        $created = 0; $updated = 0;
        foreach ($request->items as $item) {
            $record = FacilityReorderLevel::updateOrCreate(
                [
                    'facility_id' => $facilityId,
                    'product_id' => $item['product_id'],
                ],
                [
                    'amc' => $item['amc'],
                    'lead_time' => $item['lead_time'],
                ]
            );
            $record->wasRecentlyCreated ? $created++ : $updated++;
        }

        return back()->with('success', "Reorder levels saved. Created: {$created}, Updated: {$updated}");
    }

    public function destroy(FacilityReorderLevel $reorderLevel)
    {
        try {
            if ($reorderLevel->facility_id !== auth()->user()->facility_id) {
                abort(403, 'Unauthorized');
            }
            $reorderLevel->delete();
            return back()->with('success', 'Reorder level deleted.');
        } catch (\Throwable $e) {
            \Log::error('Failed to delete facility reorder level', [
                'id' => $reorderLevel->id ?? null,
                'error' => $e->getMessage(),
            ]);
            return back()->with('error', 'Failed to delete reorder level.');
        }
    }

    public function update(Request $request, FacilityReorderLevel $reorderLevel)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'amc' => 'required|numeric|min:0',
            'lead_time' => 'required|integer|min:1',
        ]);

        // Ensure it belongs to current user's facility
        if ($reorderLevel->facility_id !== auth()->user()->facility_id) {
            abort(403);
        }

        $reorderLevel->update([
            'product_id' => $request->product_id,
            'amc' => $request->amc,
            'lead_time' => $request->lead_time,
        ]);

        return back()->with('success', 'Reorder level updated.');
    }
}



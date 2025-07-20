<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Facility;
use Illuminate\Support\Facades\DB;
use App\Models\Product;
use App\Models\Transfer;

use App\Models\Order;
use App\Models\InventoryReport;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        // Get distinct facility types and their counts (only main types)
        $facilityTypes = Facility::select('facility_type', DB::raw('count(*) as count'))
            ->whereIn('facility_type', ['Health Centre', 'Primary Health Unit', 'Regional Hospital', 'District Hospital'])
            ->groupBy('facility_type')
            ->orderBy('count', 'desc')
            ->get()
            ->map(function ($type) {
                return [
                    'label' => $this->getAbbreviatedName($type->facility_type),
                    'fullName' => $type->facility_type,
                    'value' => $type->count,
                    'color' => $this->getFacilityTypeColor($type->facility_type),
                ];
            });
        


        // Product category counts - filtered by current user's facility
        $productCategoryCounts = [
            'Drugs' => Product::whereHas('category', function($q) { $q->where('name', 'Durgs'); })
                ->whereHas('facilityInventories', function($q) use ($facilityId) { 
                    $q->where('facility_id', $facilityId); 
                })->count(),
            'Consumable' => Product::whereHas('category', function($q) { $q->where('name', 'Consumables'); })
                ->whereHas('facilityInventories', function($q) use ($facilityId) { 
                    $q->where('facility_id', $facilityId); 
                })->count(),
            'Lab' => Product::whereHas('category', function($q) { $q->where('name', 'Lab'); })
                ->whereHas('facilityInventories', function($q) use ($facilityId) { 
                    $q->where('facility_id', $facilityId); 
                })->count(),
        ];

        // Transfer received count - filtered by current user's facility
        $transferReceivedCount = Transfer::where(function($query) use ($facilityId) {
                $query->where('from_facility_id', $facilityId)
                    ->orWhere('to_facility_id', $facilityId);
            })
            ->where('status', 'received')
            ->count();



        // Get current user's facility_id
        $user = auth()->user();
        $facilityId = $user->facility_id;

        // Order status statistics - filtered by current user's facility
        $orderStats = [
            'pending' => Order::where('facility_id', $facilityId)->where('status', 'pending')->count(),
            'reviewed' => Order::where('facility_id', $facilityId)->where('status', 'reviewed')->count(),
            'approved' => Order::where('facility_id', $facilityId)->where('status', 'approved')->count(),
            'in_process' => Order::where('facility_id', $facilityId)->where('status', 'in_process')->count(),
            'dispatched' => Order::where('facility_id', $facilityId)->where('status', 'dispatched')->count(),
            'received' => Order::where('facility_id', $facilityId)->where('status', 'received')->count(),
            'rejected' => Order::where('facility_id', $facilityId)->where('status', 'rejected')->count(),
        ];



        // Delayed orders count - filtered by current user's facility
        $ordersDelayedCount = Order::where('facility_id', $facilityId)
            ->whereNotNull('order_date')
            ->whereNotNull('expected_date')
            ->whereRaw('order_date < expected_date')
            ->count();

        // Inventory statistics
        $statusCounts = [
            'in_stock' => 0,
            'low_stock' => 0,
            'out_of_stock' => 0,
        ];
        
        // For facilities, we'll use facility inventory filtered by current user's facility
        $facilityInventories = \App\Models\FacilityInventory::where('facility_id', $facilityId)->with('items')->get();
        foreach ($facilityInventories as $inventory) {
            $reorderLevel = $inventory->reorder_level ?? 0;
            foreach ($inventory->items ?? [] as $item) {
                $qty = $item->quantity;
                if ($qty == 0) {
                    $statusCounts['out_of_stock']++;
                } elseif ($qty <= $reorderLevel) {
                    $statusCounts['low_stock']++;
                } else {
                    $statusCounts['in_stock']++;
                }
            }
        }

        // Expired statistics
        $now = Carbon::now();
        $sixMonthsFromNow = $now->copy()->addMonths(6);
        $oneYearFromNow = $now->copy()->addYear();

        $expiredCount = \App\Models\FacilityInventoryItem::join('facility_inventories', 'facility_inventory_items.facility_inventory_id', '=', 'facility_inventories.id')
            ->where('facility_inventories.facility_id', $facilityId)
            ->where('facility_inventory_items.quantity', '>', 0)
            ->where('facility_inventory_items.expiry_date', '<', $now)
            ->count();
        $expiring6MonthsCount = \App\Models\FacilityInventoryItem::join('facility_inventories', 'facility_inventory_items.facility_inventory_id', '=', 'facility_inventories.id')
            ->where('facility_inventories.facility_id', $facilityId)
            ->where('facility_inventory_items.quantity', '>', 0)
            ->where('facility_inventory_items.expiry_date', '>=', $now)
            ->where('facility_inventory_items.expiry_date', '<=', $sixMonthsFromNow)
            ->count();
        $expiring1YearCount = \App\Models\FacilityInventoryItem::join('facility_inventories', 'facility_inventory_items.facility_inventory_id', '=', 'facility_inventories.id')
            ->where('facility_inventories.facility_id', $facilityId)
            ->where('facility_inventory_items.quantity', '>', 0)
            ->where('facility_inventory_items.expiry_date', '>=', $now)
            ->where('facility_inventory_items.expiry_date', '<=', $oneYearFromNow)
            ->count();

        $expiredStats = [
            'expired' => $expiredCount,
            'expiring_within_6_months' => $expiring6MonthsCount,
            'expiring_within_1_year' => $expiring1YearCount,
        ];

        $responseData = [
            'dashboardData' => [
                'summary' => $facilityTypes,
                'order_stats' => [],
                'tasks' => [],
                'recommended_actions' => [],
                'product_status' => []
            ],
            'productCategoryCard' => $productCategoryCounts,
            'transferReceivedCard' => $transferReceivedCount,
            'orderStats' => $orderStats,
            'ordersDelayedCount' => $ordersDelayedCount,
            'inventoryStatusCounts' => collect($statusCounts)->map(fn($count, $status) => ['status' => $status, 'count' => $count])->values(),
            'expiredStats' => $expiredStats,
        ];

        return Inertia::render('Dashboard', $responseData);
    }

    /**
     * Get human-readable type label
     */
    private function getTypeLabel($type)
    {
        $labels = [
            'beginning_balance' => 'Beginning Balance',
            'received_quantity' => 'Quantity Received',
            'issued_quantity' => 'Quantity Issued',
            'closing_balance' => 'Closing Balance'
        ];

        return $labels[$type] ?? 'Unknown';
    }

    private function getFacilityTypeColor($facilityType)
    {
        $colors = [
            'Regional Hospital' => 'red',
            'District Hospital' => 'orange',
            'Health Centre' => 'blue',
            'Primary Health Unit' => 'green'
        ];

        return $colors[$facilityType] ?? 'gray';
    }

    private function getAbbreviatedName($facilityType)
    {
        $abbreviations = [
            'Regional Hospital' => 'RH',
            'District Hospital' => 'DH',
            'Health Centre' => 'HC',
            'Primary Health Unit' => 'PHU'
        ];

        return $abbreviations[$facilityType] ?? $facilityType;
    }
} 
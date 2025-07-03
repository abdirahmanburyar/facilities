<?php

namespace App\Http\Controllers;

use App\Models\FacilityInventory;
use App\Models\FacilityInventoryItem;
use App\Models\FacilityInventoryMovement;
use App\Models\Order;
use App\Models\Transfer;
use App\Models\Dispence;
use App\Models\BackOrderHistory;
use App\Models\Product;
use App\Models\FacilityMonthlyReport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $facilityId = $user->facility_id;
        
        // Get dashboard data
        $inventoryStatus = $this->getInventoryStatus($facilityId);
        $recentActivity = $this->getRecentActivity($facilityId);
        $statistics = $this->getStatistics($facilityId);
        $lowStockAlerts = $this->getLowStockAlerts($facilityId);
        $monthlyReportStatus = $this->getMonthlyReportStatus($facilityId);
        $inventoryMovements = $this->getInventoryMovementsSummary($facilityId);
        
        return Inertia::render('Dashboard', [
            'inventoryStatus' => $inventoryStatus,
            'recentActivity' => $recentActivity,
            'statistics' => $statistics,
            'lowStockAlerts' => $lowStockAlerts,
            'monthlyReportStatus' => $monthlyReportStatus,
            'inventoryMovements' => $inventoryMovements,
        ]);
    }

    private function getInventoryStatus($facilityId)
    {
        $now = Carbon::now();
        
        // Get AMC data for last 3 months
        $startDate = Carbon::now()->subMonths(3)->startOfMonth()->format('Y-m-d');
        $endDate = Carbon::now()->subMonths(1)->endOfMonth()->format('Y-m-d');
        
        $amcSubquery = FacilityInventoryMovement::facilityIssued()
            ->select('product_id', DB::raw('SUM(facility_issued_quantity) / 3 as amc'))
            ->whereBetween('movement_date', [$startDate, $endDate])
            ->groupBy('product_id');

        $inventories = FacilityInventory::where('facility_id', $facilityId)
            ->leftJoinSub($amcSubquery, 'amc_data', function ($join) {
                $join->on('facility_inventories.product_id', '=', 'amc_data.product_id');
            })
            ->addSelect('facility_inventories.*')
            ->addSelect(DB::raw('COALESCE(amc_data.amc, 0) as amc'))
            ->addSelect(DB::raw('ROUND(COALESCE(amc_data.amc, 0) * 6) as reorder_level'))
            ->with('items')
            ->get();

        $statusCounts = [
            'in_stock' => 0,
            'low_stock' => 0,
            'out_of_stock' => 0,
            'expired' => 0,
            'soon_expiring' => 0
        ];

        foreach ($inventories as $inventory) {
            $amc = $inventory->amc ?: 0;
            $reorderLevel = $inventory->reorder_level ?: ($amc * 6);

            foreach ($inventory->items ?? [] as $item) {
                $qty = $item->quantity;

                if ($qty == 0) {
                    $statusCounts['out_of_stock']++;
                } elseif ($qty <= $reorderLevel) {
                    $statusCounts['low_stock']++;
                } else {
                    $statusCounts['in_stock']++;
                }

                if ($item->expiry_date) {
                    if ($item->expiry_date < $now) {
                        $statusCounts['expired']++;
                    } elseif ($item->expiry_date <= $now->copy()->addMonths(6)) {
                        $statusCounts['soon_expiring']++;
                    }
                }
            }
        }

        return $statusCounts;
    }

    private function getRecentActivity($facilityId)
    {
        // Recent Orders
        $recentOrders = Order::where('facility_id', $facilityId)
            ->with(['items.product:id,name'])
            ->latest()
            ->take(5)
            ->get()
            ->map(function($order) {
                return [
                    'id' => $order->id,
                    'order_number' => $order->order_number,
                    'status' => $order->status,
                    'created_at' => $order->created_at,
                    'items_count' => $order->items->count(),
                ];
            });

        // Recent Transfers
        $recentTransfers = Transfer::where(function($query) use ($facilityId) {
                $query->where('from_facility_id', $facilityId)
                    ->orWhere('to_facility_id', $facilityId);
            })
            ->with(['items.product:id,name'])
            ->latest()
            ->take(5)
            ->get()
            ->map(function($transfer) use ($facilityId) {
                return [
                    'id' => $transfer->id,
                    'transferID' => $transfer->transferID,
                    'status' => $transfer->status,
                    'type' => $transfer->from_facility_id == $facilityId ? 'outgoing' : 'incoming',
                    'created_at' => $transfer->created_at,
                    'items_count' => $transfer->items->count(),
                ];
            });

        // Recent Dispenses
        $recentDispenses = Dispence::where('facility_id', $facilityId)
            ->with(['items.product:id,name'])
            ->latest()
            ->take(5)
            ->get()
            ->map(function($dispence) {
                return [
                    'id' => $dispence->id,
                    'dispence_number' => $dispence->dispence_number,
                    'patient_name' => $dispence->patient_name,
                    'created_at' => $dispence->created_at,
                    'items_count' => $dispence->items->count(),
                ];
            });

        return [
            'orders' => $recentOrders,
            'transfers' => $recentTransfers,
            'dispenses' => $recentDispenses,
        ];
    }

    private function getStatistics($facilityId)
    {
        $currentMonth = Carbon::now()->format('Y-m');
        
        // Total products in facility
        $totalProducts = FacilityInventory::where('facility_id', $facilityId)->count();
        
        // This month's movements
        $thisMonthMovements = FacilityInventoryMovement::where('facility_id', $facilityId)
            ->whereMonth('movement_date', Carbon::now()->month)
            ->whereYear('movement_date', Carbon::now()->year)
            ->get();
            
        $totalReceived = $thisMonthMovements->where('movement_type', 'facility_received')->sum('facility_received_quantity');
        $totalIssued = $thisMonthMovements->where('movement_type', 'facility_issued')->sum('facility_issued_quantity');
        
        // Active orders count
        $activeOrders = Order::where('facility_id', $facilityId)
            ->whereNotIn('status', ['received', 'cancelled'])
            ->count();
            
        // Active transfers count
        $activeTransfers = Transfer::where(function($query) use ($facilityId) {
                $query->where('from_facility_id', $facilityId)
                    ->orWhere('to_facility_id', $facilityId);
            })
            ->whereNotIn('status', ['received', 'cancelled'])
            ->count();

        return [
            'total_products' => $totalProducts,
            'total_received_this_month' => $totalReceived,
            'total_issued_this_month' => $totalIssued,
            'active_orders' => $activeOrders,
            'active_transfers' => $activeTransfers,
        ];
    }

    private function getLowStockAlerts($facilityId)
    {
        $startDate = Carbon::now()->subMonths(3)->startOfMonth()->format('Y-m-d');
        $endDate = Carbon::now()->subMonths(1)->endOfMonth()->format('Y-m-d');
        
        $amcSubquery = FacilityInventoryMovement::facilityIssued()
            ->select('product_id', DB::raw('SUM(facility_issued_quantity) / 3 as amc'))
            ->whereBetween('movement_date', [$startDate, $endDate])
            ->groupBy('product_id');

        $lowStockItems = FacilityInventory::where('facility_id', $facilityId)
            ->leftJoinSub($amcSubquery, 'amc_data', function ($join) {
                $join->on('facility_inventories.product_id', '=', 'amc_data.product_id');
            })
            ->addSelect('facility_inventories.*')
            ->addSelect(DB::raw('COALESCE(amc_data.amc, 0) as amc'))
            ->addSelect(DB::raw('ROUND(COALESCE(amc_data.amc, 0) * 6) as reorder_level'))
            ->with(['product:id,name', 'items'])
            ->get()
            ->filter(function($inventory) {
                $totalQuantity = $inventory->items->sum('quantity');
                $reorderLevel = $inventory->reorder_level ?: ($inventory->amc * 6);
                return $totalQuantity > 0 && $totalQuantity <= $reorderLevel;
            })
            ->take(10)
            ->map(function($inventory) {
                $totalQuantity = $inventory->items->sum('quantity');
                return [
                    'product_name' => $inventory->product->name,
                    'current_stock' => $totalQuantity,
                    'reorder_level' => $inventory->reorder_level,
                    'amc' => round($inventory->amc, 2),
                    'alert_level' => $totalQuantity <= ($inventory->reorder_level * 0.5) ? 'critical' : 'warning'
                ];
            });

        return $lowStockItems;
    }

    private function getMonthlyReportStatus($facilityId)
    {
        $currentYear = Carbon::now()->year;
        $currentMonth = Carbon::now()->month;
        
        $currentReport = FacilityMonthlyReport::where('facility_id', $facilityId)
            ->where('report_period', Carbon::now()->format('Y-m'))
            ->first();
            
        $lastMonthReport = FacilityMonthlyReport::where('facility_id', $facilityId)
            ->where('report_period', Carbon::now()->subMonth()->format('Y-m'))
            ->first();

        return [
            'current_month' => [
                'period' => Carbon::now()->format('F Y'),
                'status' => $currentReport ? $currentReport->status : 'not_generated',
                'submitted_at' => $currentReport ? $currentReport->submitted_at : null,
            ],
            'last_month' => [
                'period' => Carbon::now()->subMonth()->format('F Y'),
                'status' => $lastMonthReport ? $lastMonthReport->status : 'not_generated',
                'submitted_at' => $lastMonthReport ? $lastMonthReport->submitted_at : null,
            ],
        ];
    }

    private function getInventoryMovementsSummary($facilityId)
    {
        $last30Days = Carbon::now()->subDays(30);
        
        $movements = FacilityInventoryMovement::where('facility_id', $facilityId)
            ->where('movement_date', '>=', $last30Days)
            ->get()
            ->groupBy(function($movement) {
                return Carbon::parse($movement->movement_date)->format('Y-m-d');
            })
            ->map(function($dayMovements) {
                return [
                    'received' => $dayMovements->where('movement_type', 'facility_received')->sum('facility_received_quantity'),
                    'issued' => $dayMovements->where('movement_type', 'facility_issued')->sum('facility_issued_quantity'),
                ];
            })
            ->take(7); // Last 7 days for chart

        return $movements;
    }
} 
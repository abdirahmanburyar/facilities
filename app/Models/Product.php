<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Category;
use App\Models\Dosage;
use App\Models\Inventory;
use App\Models\Supply;
use App\Models\SupplyItem;
use App\Models\SubCategory;
use App\Models\FacilityInventoryItem;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Product extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'productID',
        'name',
        'category_id',
        'dosage_id',
        'movement',
        'reorder_level',
        'is_active',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($product) {
            // Find the highest productID in the database
            $maxProductId = self::max('productID');
            
            // If there are existing products, increment the highest productID
            if ($maxProductId) {
                $nextId = (int)$maxProductId + 1;
            } else {
                // Start from 1 if no products exist
                $nextId = 1;
            }
            
            // Format as 6-digit number with leading zeros
            $product->productID = str_pad($nextId, 6, '0', STR_PAD_LEFT);
        });
    }



    public function category()
    {
        return $this->belongsTo(Category::class);
    }
    
    public function dosage()
    {
        return $this->belongsTo(Dosage::class);
    }

    public function subCategory()
    {
        return $this->belongsTo(SubCategory::class, 'sub_category_id');
    }

    /**
     * Get the supply items that contain this product.
     */
    public function supplyItems()
    {
        return $this->hasMany(SupplyItem::class);
    }

    /**
     * Get the supplies that contain this product.
     */
    public function supplies()
    {
        return $this->belongsToMany(Supply::class, 'supply_items')
            ->withPivot(['quantity', 'status'])
            ->withTimestamps();
    }

    public function eligible(){
        return $this->hasMany(EligibleItem::class);
    }

    public function eligibleForFacilityType($facilityType) {
        return $this->eligible()->where('facility_type', $facilityType)->exists();
    }

    public function inventory(){
        return $this->hasMany(FacilityInventory::class);
    }

    public function facilityInventoryItems(){
        return $this->hasManyThrough(
            FacilityInventoryItem::class,
            FacilityInventory::class,
            'product_id', // Foreign key on FacilityInventory table
            'facility_inventory_id', // Foreign key on FacilityInventory table
            'id', // Local key on Product table
            'id' // Local key on FacilityInventory table
        );
    }

    public function facilityInventoryItemsForFacility($facilityId){
        return $this->hasManyThrough(
            FacilityInventoryItem::class,
            FacilityInventory::class,
            'product_id', // Foreign key on FacilityInventory table
            'facility_inventory_id', // Foreign key on FacilityInventory table
            'id', // Local key on Product table
            'id' // Local key on FacilityInventory table
        )->whereHas('inventory', function($q) use ($facilityId) {
            $q->where('facility_id', $facilityId);
        });
    }

    /**
     * Get the facility inventory structure for this product
     */
    public function getFacilityInventoryStructureAttribute()
    {
        $facilityInventoryItems = $this->facilityInventoryItems;
        
        if ($facilityInventoryItems->isEmpty()) {
            return [
                'id' => $this->id,
                'name' => $this->name,
                'product' => $this,
                'items' => [],
                'status' => 'out_of_stock',
                'amc' => 0,
                'reorder_level' => 0
            ];
        }

        // Calculate total quantity
        $totalQuantity = $facilityInventoryItems->sum('quantity');
        
        // Calculate AMC from monthly consumption
        $amc = $this->calculateAMC();
        
        // Calculate reorder level
        $reorderLevel = $this->calculateReorderLevel($amc);
        
        // Determine status
        $status = $this->determineInventoryStatus($totalQuantity, $reorderLevel, $amc);

        return [
            'id' => $this->id,
            'name' => $this->name,
            'product' => $this,
            'items' => $facilityInventoryItems,
            'status' => $status,
            'amc' => $amc,
            'reorder_level' => $reorderLevel
        ];
    }

    /**
     * Get the facility inventory structure for a specific facility
     */
    public function getFacilityInventoryStructureForFacility($facilityId)
    {
        $facilityInventoryItems = $this->facilityInventoryItemsForFacility($facilityId)->get();
        
        if ($facilityInventoryItems->isEmpty()) {
            return [
                'id' => $this->id,
                'name' => $this->name,
                'product' => $this,
                'items' => [],
                'status' => 'out_of_stock',
                'amc' => 0,
                'reorder_level' => 0
            ];
        }

        // Calculate total quantity
        $totalQuantity = $facilityInventoryItems->sum('quantity');
        
        // Calculate AMC from monthly consumption
        $amc = $this->calculateAMC();
        
        // Calculate reorder level
        $reorderLevel = $this->calculateReorderLevel($amc);
        
        // Determine status
        $status = $this->determineInventoryStatus($totalQuantity, $reorderLevel, $amc);

        return [
            'id' => $this->id,
            'name' => $this->name,
            'product' => $this,
            'items' => $facilityInventoryItems,
            'status' => $status,
            'amc' => $amc,
            'reorder_level' => $reorderLevel
        ];
    }

    /**
     * Calculate Average Monthly Consumption
     */
    private function calculateAMC()
    {
        // Get monthly consumption data for the last 6 months
        $monthlyConsumptions = \DB::table('monthly_consumption_items as mci')
            ->join('monthly_consumption_reports as mcr', 'mci.parent_id', '=', 'mcr.id')
            ->select('mci.quantity')
            ->where('mci.product_id', $this->id)
            ->where('mci.quantity', '>', 0)
            ->where('mcr.month_year', '>=', now()->subMonths(6)->format('Y-m'))
            ->get();

        if ($monthlyConsumptions->count() < 3) {
            return 0;
        }

        // Apply 70% screening logic
        $quantities = $monthlyConsumptions->pluck('quantity')->toArray();
        $selectedMonths = $this->applyAMCScreening($quantities);
        
        return $selectedMonths->count() > 0 ? $selectedMonths->avg() : 0;
    }

    /**
     * Apply AMC screening with 70% threshold
     */
    private function applyAMCScreening($quantities)
    {
        if (count($quantities) < 3) {
            return collect($quantities);
        }

        // Start with first 3 months
        $firstThree = array_slice($quantities, 0, 3);
        $average = array_sum($firstThree) / 3;
        
        // Check each month against 70% threshold
        $passedMonths = [];
        foreach ($firstThree as $quantity) {
            $deviation = abs($quantity - $average);
            $percentage = $average > 0 ? ($deviation / $average) * 100 : 0;
            
            if ($percentage <= 70) {
                $passedMonths[] = $quantity;
            }
        }

        // If all 3 passed, use them
        if (count($passedMonths) == 3) {
            return collect($passedMonths);
        }

        // Try to find alternative combinations
        $remaining = array_slice($quantities, 3);
        $candidates = array_merge($passedMonths, $remaining);
        
        for ($i = 0; $i <= count($candidates) - 3; $i++) {
            $testGroup = array_slice($candidates, $i, 3);
            $testAverage = array_sum($testGroup) / 3;
            
            $allPass = true;
            foreach ($testGroup as $quantity) {
                $deviation = abs($quantity - $testAverage);
                $percentage = $testAverage > 0 ? ($deviation / $testAverage) * 100 : 0;
                
                if ($percentage > 70) {
                    $allPass = false;
                    break;
                }
            }
            
            if ($allPass) {
                return collect($testGroup);
            }
        }

        // Fallback to passed months
        return collect($passedMonths);
    }

    /**
     * Calculate reorder level based on AMC
     */
    private function calculateReorderLevel($amc)
    {
        if ($amc <= 0) {
            return 0;
        }
        
        // Reorder level = (AMC * 3) + buffer stock
        $bufferStock = $amc * 0.5; // 50% buffer
        return ($amc * 3) + $bufferStock;
    }

    /**
     * Determine inventory status
     */
    private function determineInventoryStatus($totalQuantity, $reorderLevel, $amc)
    {
        if ($totalQuantity <= 0) {
            return 'out_of_stock';
        }
        
        if ($reorderLevel > 0 && $totalQuantity <= $reorderLevel && $totalQuantity < ($reorderLevel / 0.3)) {
            return 'low_stock_reorder_level';
        }
        
        if ($reorderLevel > 0 && $totalQuantity <= $reorderLevel) {
            return 'reorder_level';
        }
        
        if ($reorderLevel > 0 && $totalQuantity < ($reorderLevel / 0.3)) {
            return 'low_stock';
        }
        
        return 'in_stock';
    }
}

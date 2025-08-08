<?php

namespace App\Models;

use App\Models\Traits\ClearsDashboardCache;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class ItemsCatalog extends Model
{
    use ClearsDashboardCache, HasFactory, SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'items_catalog';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'unit',
        'secondary_category_id',
        'code',
    ];

    /**
     * Get the secondary category that categorizes this item.
     */
    public function secondaryCategory(): BelongsTo
    {
        return $this->belongsTo(SecondaryCategory::class);
    }

    /**
     * Get the item specifications for this catalog item.
     */
    public function specifications(): HasMany
    {
        return $this->hasMany(ItemSpecification::class, 'item_catalog_id');
    }

    /**
     * Get comprehensive deletion impact statistics.
     */
    public function getDeletionImpact(): array
    {
        $specifications = $this->specifications()->with([
            'contractItems.icsNumbers',
            'contractItems.parNumbers', 
            'contractItems.idrNumbers',
            'consumableItems'
        ])->get();

        $impact = [
            'specifications' => $specifications->count(),
            'contract_items' => 0,
            'consumable_items' => 0,
            'ics_numbers' => 0,
            'par_numbers' => 0,
            'idr_numbers' => 0,
            'risk_level' => 'low',
            'risk_message' => '',
            'has_associated_data' => false,
        ];

        foreach ($specifications as $spec) {
            $impact['contract_items'] += $spec->contractItems->count();
            $impact['consumable_items'] += $spec->consumableItems->count();
            
            foreach ($spec->contractItems as $contractItem) {
                $impact['ics_numbers'] += $contractItem->icsNumbers->count();
                $impact['par_numbers'] += $contractItem->parNumbers->count();
                $impact['idr_numbers'] += $contractItem->idrNumbers->count();
            }
        }

        // Determine risk level and message
        $impact = $this->assessDeletionRisk($impact);
        $impact['has_associated_data'] = $impact['specifications'] > 0;

        return $impact;
    }

    /**
     * Assess the deletion risk level based on impact data.
     */
    protected function assessDeletionRisk(array $impact): array
    {
        $totalInventoryRecords = $impact['ics_numbers'] + $impact['par_numbers'] + $impact['idr_numbers'];
        
        if ($totalInventoryRecords > 0) {
            // HIGH RISK: Has active inventory records
            $impact['risk_level'] = 'high';
            $impact['risk_message'] = 'This item has active inventory records and should not be deleted.';
        } elseif ($impact['contract_items'] > 0) {
            // MEDIUM RISK: Has contract items but no inventory
            $impact['risk_level'] = 'medium';
            $impact['risk_message'] = 'This item has procurement history. Deletion will remove contract and pricing data.';
        } elseif ($impact['specifications'] > 0) {
            // LOW RISK: Only has specifications
            $impact['risk_level'] = 'low';
            $impact['risk_message'] = 'This item has specification variants that will be deleted.';
        } else {
            // SAFE: No associated data
            $impact['risk_level'] = 'safe';
            $impact['risk_message'] = 'This item has no associated data and is safe to delete.';
        }

        return $impact;
    }

    /**
     * Force delete this catalog item and all associated data.
     * WARNING: This will cascade through the entire system!
     */
    public function forceDeleteWithAssociations(): bool
    {
        return DB::transaction(function () {
            // Get all specifications with their relationships
            $specifications = $this->specifications()->with([
                'contractItems.icsNumbers.icsItemBatches.itemComponents',
                'contractItems.icsNumbers.icsTransfers',
                'contractItems.parNumbers.parItemBatches',
                'contractItems.parNumbers.parTransfers',
                'contractItems.idrNumbers.idrItemBatches.acknowledgementReceipts',
                'consumableItems'
            ])->get();

            foreach ($specifications as $specification) {
                // Delete contract items and their cascading relationships
                foreach ($specification->contractItems as $contractItem) {
                    // Delete ICS related data
                    foreach ($contractItem->icsNumbers as $icsNumber) {
                        // Delete item components
                        foreach ($icsNumber->icsItemBatches as $batch) {
                            $batch->itemComponents()->delete();
                        }
                        // Delete transfers and batches
                        $icsNumber->icsTransfers()->delete();
                        $icsNumber->icsItemBatches()->delete();
                    }
                    $contractItem->icsNumbers()->delete();

                    // Delete PAR related data
                    foreach ($contractItem->parNumbers as $parNumber) {
                        $parNumber->parTransfers()->delete();
                        $parNumber->parItemBatches()->delete();
                    }
                    $contractItem->parNumbers()->delete();

                    // Delete IDR related data
                    foreach ($contractItem->idrNumbers as $idrNumber) {
                        foreach ($idrNumber->idrItemBatches as $batch) {
                            $batch->acknowledgementReceipts()->delete();
                        }
                        $idrNumber->idrItemBatches()->delete();
                    }
                    $contractItem->idrNumbers()->delete();
                }

                // Delete consumable items
                $specification->consumableItems()->delete();
                
                // Delete contract items
                $specification->contractItems()->delete();
            }

            // Delete all specifications
            $this->specifications()->delete();

            // Finally delete the catalog item
            return $this->delete();
        });
    }

    /**
     * Check if this item can be safely deleted (no associations).
     */
    public function canBeDeletedSafely(): bool
    {
        return !$this->specifications()->exists();
    }

    /**
     * Check if deletion should be blocked due to high risk.
     */
    public function isDeletionBlocked(): bool
    {
        $impact = $this->getDeletionImpact();
        return $impact['risk_level'] === 'high';
    }

    /**
     * Get a summary of what would be deleted.
     */
    public function getDeletionSummary(): string
    {
        $impact = $this->getDeletionImpact();
        
        $summary = [];
        
        if ($impact['specifications'] > 0) {
            $summary[] = $impact['specifications'] . ' specification' . ($impact['specifications'] !== 1 ? 's' : '');
        }
        
        if ($impact['contract_items'] > 0) {
            $summary[] = $impact['contract_items'] . ' contract item' . ($impact['contract_items'] !== 1 ? 's' : '');
        }
        
        if ($impact['consumable_items'] > 0) {
            $summary[] = $impact['consumable_items'] . ' consumable item' . ($impact['consumable_items'] !== 1 ? 's' : '');
        }

        $inventoryItems = [];
        if ($impact['ics_numbers'] > 0) {
            $inventoryItems[] = $impact['ics_numbers'] . ' ICS record' . ($impact['ics_numbers'] !== 1 ? 's' : '');
        }
        if ($impact['par_numbers'] > 0) {
            $inventoryItems[] = $impact['par_numbers'] . ' PAR record' . ($impact['par_numbers'] !== 1 ? 's' : '');
        }
        if ($impact['idr_numbers'] > 0) {
            $inventoryItems[] = $impact['idr_numbers'] . ' IDR record' . ($impact['idr_numbers'] !== 1 ? 's' : '');
        }
        
        if (!empty($inventoryItems)) {
            $summary[] = implode(', ', $inventoryItems);
        }

        return implode(', ', $summary);
    }
}

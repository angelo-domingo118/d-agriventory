<?php

namespace App\Models;

use App\Models\Traits\ClearsDashboardCache;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class ItemSpecification extends Model
{
    use ClearsDashboardCache, HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'item_catalog_id',
        'brand',
        'model',
        'detailed_specifications',
    ];

    /**
     * Get the catalog item that this specification belongs to.
     */
    public function itemCatalog(): BelongsTo
    {
        return $this->belongsTo(ItemsCatalog::class, 'item_catalog_id');
    }

    /**
     * Get the contract items for this specification.
     */
    public function contractItems(): HasMany
    {
        return $this->hasMany(ContractItem::class);
    }

    /**
     * Get the consumable items for this specification.
     */
    public function consumableItems(): HasMany
    {
        return $this->hasMany(ConsumableItem::class);
    }

    /**
     * Get comprehensive deletion impact statistics.
     */
    public function getDeletionImpact(): array
    {
        $contractItems = $this->contractItems()->with([
            'icsNumbers',
            'parNumbers', 
            'idrNumbers'
        ])->get();

        $impact = [
            'contract_items' => $contractItems->count(),
            'consumable_items' => $this->consumableItems()->count(),
            'ics_numbers' => 0,
            'par_numbers' => 0,
            'idr_numbers' => 0,
            'risk_level' => 'low',
            'risk_message' => '',
            'has_associated_data' => false,
        ];

        foreach ($contractItems as $contractItem) {
            $impact['ics_numbers'] += $contractItem->icsNumbers->count();
            $impact['par_numbers'] += $contractItem->parNumbers->count();
            $impact['idr_numbers'] += $contractItem->idrNumbers->count();
        }

        // Determine risk level and message
        $impact = $this->assessDeletionRisk($impact);
        $impact['has_associated_data'] = $impact['contract_items'] > 0 || $impact['consumable_items'] > 0;

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
            $impact['risk_message'] = 'This specification has active inventory records and should not be deleted.';
        } elseif ($impact['contract_items'] > 0) {
            // MEDIUM RISK: Has contract items but no inventory
            $impact['risk_level'] = 'medium';
            $impact['risk_message'] = 'This specification has procurement history. Deletion will remove contract and pricing data.';
        } elseif ($impact['consumable_items'] > 0) {
            // LOW RISK: Has consumable items
            $impact['risk_level'] = 'low';
            $impact['risk_message'] = 'This specification has consumable inventory records that will be deleted.';
        } else {
            // SAFE: No associated data
            $impact['risk_level'] = 'safe';
            $impact['risk_message'] = 'This specification has no associated data and is safe to delete.';
        }

        return $impact;
    }

    /**
     * Force delete this specification and all associated data.
     * WARNING: This will cascade through the entire system!
     */
    public function forceDeleteWithAssociations(): bool
    {
        return DB::transaction(function () {
            // Get all contract items with their relationships
            $contractItems = $this->contractItems()->with([
                'icsNumbers.icsItemBatches.itemComponents',
                'icsNumbers.icsTransfers',
                'parNumbers.parItemBatches',
                'parNumbers.parTransfers',
                'idrNumbers.idrItemBatches.acknowledgementReceipts'
            ])->get();

            foreach ($contractItems as $contractItem) {
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
            $this->consumableItems()->delete();
            
            // Delete contract items
            $this->contractItems()->delete();

            // Finally delete the specification
            return $this->delete();
        });
    }

    /**
     * Check if this specification can be safely deleted (no associations).
     */
    public function canBeDeletedSafely(): bool
    {
        return !$this->contractItems()->exists() && !$this->consumableItems()->exists();
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
        
        $parts = [];
        if ($impact['contract_items'] > 0) {
            $parts[] = $impact['contract_items'] . ' contract ' . 
                      ($impact['contract_items'] === 1 ? 'item' : 'items');
        }
        if ($impact['consumable_items'] > 0) {
            $parts[] = $impact['consumable_items'] . ' consumable ' . 
                      ($impact['consumable_items'] === 1 ? 'item' : 'items');
        }
        if ($impact['ics_numbers'] > 0) {
            $parts[] = $impact['ics_numbers'] . ' ICS ' . 
                      ($impact['ics_numbers'] === 1 ? 'record' : 'records');
        }
        if ($impact['par_numbers'] > 0) {
            $parts[] = $impact['par_numbers'] . ' PAR ' . 
                      ($impact['par_numbers'] === 1 ? 'record' : 'records');
        }
        if ($impact['idr_numbers'] > 0) {
            $parts[] = $impact['idr_numbers'] . ' IDR ' . 
                      ($impact['idr_numbers'] === 1 ? 'record' : 'records');
        }

        return implode(', ', $parts);
    }
}

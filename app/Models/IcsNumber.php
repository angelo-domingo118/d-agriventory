<?php

namespace App\Models;

use App\Models\Traits\ClearsDashboardCache;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\DB;

class IcsNumber extends Model
{
    use ClearsDashboardCache, HasFactory;

    /**
     * The table associated with the model.
     *
     * Note: This table uses the singular naming convention 'ics_number' instead of the Laravel
     * plural convention 'ics_numbers'. This is intentional to maintain consistency with the
     * business terminology where "ICS Number" refers to a specific inventory document type.
     * This naming convention is used consistently throughout the application for similar inventory
     * document types (par_number, idr_number, etc.).
     *
     * @var string
     */
    protected $table = 'ics_number';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'ics_number',
        'assigned_employee_id',
        'contract_item_id',
        'ics_type',
        'quantity',
        'estimated_useful_life',
        'date_prepared',
        'date_accepted',
        'remarks',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'date_prepared' => 'date',
        'date_accepted' => 'date',
    ];

    /**
     * Get the employee this ICS number is assigned to.
     */
    public function assignedEmployee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'assigned_employee_id');
    }

    /**
     * Get the contract item for this ICS number.
     */
    public function contractItem(): BelongsTo
    {
        return $this->belongsTo(ContractItem::class);
    }

    /**
     * Get the item batches for this ICS number.
     */
    public function itemBatches(): HasMany
    {
        return $this->hasMany(IcsItemBatch::class);
    }

    /**
     * Get the transfers for this ICS number.
     */
    public function transfers(): HasMany
    {
        return $this->hasMany(IcsTransfer::class);
    }

    /**
     * Get the latest transfer for this ICS number.
     */
    public function latestTransfer(): HasOne
    {
        return $this->hasOne(IcsTransfer::class)->latest('transfer_date');
    }

    /**
     * Calculate the total value of all ICS items.
     */
    public static function calculateTotalValue(): float
    {
        return (float) static::join('contract_items', 'ics_number.contract_item_id', '=', 'contract_items.id')
            ->sum(DB::raw('ics_number.quantity * contract_items.unit_price'));
    }

    /**
     * Check if this ICS number can be safely deleted (no associated data).
     */
    public function canBeDeletedSafely(): bool
    {
        $impact = $this->getDeletionImpact();
        return !$impact['has_associated_data'];
    }

    /**
     * Get the deletion impact analysis for this ICS number.
     */
    public function getDeletionImpact(): array
    {
        $itemBatchesCount = $this->itemBatches()->count();
        $transfersCount = $this->transfers()->count();

        $hasAssociatedData = $itemBatchesCount > 0 || $transfersCount > 0;

        return [
            'has_associated_data' => $hasAssociatedData,
            'item_batches' => $itemBatchesCount,
            'transfers' => $transfersCount,
            'risk_level' => $this->calculateRiskLevel($itemBatchesCount, $transfersCount),
            'risk_message' => $this->getRiskMessage($itemBatchesCount, $transfersCount),
        ];
    }

    /**
     * Calculate the risk level for deletion based on associated data.
     */
    private function calculateRiskLevel(int $itemBatchesCount, int $transfersCount): string
    {
        // High risk if there are any transfers (inventory movement history)
        if ($transfersCount > 0) {
            return 'high';
        }

        // Medium risk if there are item batches but no transfers
        if ($itemBatchesCount > 0) {
            return 'medium';
        }

        // Safe to delete if no associated data
        return 'safe';
    }

    /**
     * Get a descriptive risk message for deletion.
     */
    private function getRiskMessage(int $itemBatchesCount, int $transfersCount): string
    {
        if ($transfersCount > 0) {
            return 'This ICS record has transfer history. Deleting it will remove valuable inventory tracking data and audit trails.';
        }

        if ($itemBatchesCount > 0) {
            return 'This ICS record has item batches with serial numbers and other identification data.';
        }

        return 'This ICS record has no associated data and can be safely deleted.';
    }

    /**
     * Force delete this ICS number along with all its associations.
     */
    public function forceDeleteWithAssociations(): bool
    {
        return DB::transaction(function () {
            // Delete all associated transfers first
            $this->transfers()->delete();
            
            // Delete all associated item batches
            $this->itemBatches()->delete();

            // Finally delete the ICS number itself
            return $this->delete();
        });
    }
}

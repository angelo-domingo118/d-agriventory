<?php

namespace App\Models;

use App\Models\Traits\ClearsDashboardCache;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Division extends Model
{
    use ClearsDashboardCache, HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'code',
    ];

    /**
     * Get the inventory managers for this division.
     */
    public function inventoryManagers(): HasMany
    {
        return $this->hasMany(DivisionInventoryManager::class);
    }

    /**
     * Get the employees that belong to this division.
     */
    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }

    /**
     * Get the consumable records for this division.
     */
    public function consumableRecords(): HasMany
    {
        return $this->hasMany(ConsumableRecord::class);
    }

    /**
     * Get deletion impact statistics.
     */
    public function getDeletionImpact(): array
    {
        $employeesCount = $this->employees()->count();
        $inventoryManagersCount = $this->inventoryManagers()->count();
        $consumableRecordsCount = $this->consumableRecords()->count();

        return [
            'employees' => $employeesCount,
            'inventory_managers' => $inventoryManagersCount,
            'consumable_records' => $consumableRecordsCount,
            'has_associated_data' => $employeesCount > 0 || $inventoryManagersCount > 0 || $consumableRecordsCount > 0,
            'risk_level' => $employeesCount > 0 ? 'high' : (($inventoryManagersCount > 0 || $consumableRecordsCount > 0) ? 'medium' : 'safe'),
            'risk_message' => $employeesCount > 0 
                ? 'This division has employees assigned and should not be deleted.'
                : (($inventoryManagersCount > 0 || $consumableRecordsCount > 0) 
                    ? 'This division has inventory managers or consumable records. Deletion will affect operational data.'
                    : 'This division has no associated data and is safe to delete.'),
        ];
    }

    /**
     * Check if this division can be safely deleted (has no associations).
     */
    public function canBeDeletedSafely(): bool
    {
        $impact = $this->getDeletionImpact();
        return !$impact['has_associated_data'];
    }

    /**
     * Check if deletion should be blocked (has employees).
     */
    public function isDeletionBlocked(): bool
    {
        return $this->getDeletionImpact()['risk_level'] === 'high';
    }
}

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
}

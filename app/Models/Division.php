<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Division extends Model
{
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
     * Get the inventory manager for this division.
     */
    public function inventoryManager(): HasOne
    {
        return $this->hasOne(DivisionInventoryManager::class);
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

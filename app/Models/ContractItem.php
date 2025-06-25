<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ContractItem extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'contract_id',
        'item_specification_id',
        'unit_price',
        'item_type',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'unit_price' => 'decimal:2',
    ];

    /**
     * Get the contract that this item belongs to.
     */
    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    /**
     * Get the item specification for this contract item.
     */
    public function itemSpecification(): BelongsTo
    {
        return $this->belongsTo(ItemSpecification::class);
    }

    /**
     * Get the ICS numbers for this contract item.
     */
    public function icsNumbers(): HasMany
    {
        return $this->hasMany(IcsNumber::class);
    }

    /**
     * Get the PAR numbers for this contract item.
     */
    public function parNumbers(): HasMany
    {
        return $this->hasMany(ParNumber::class);
    }

    /**
     * Get the IDR numbers for this contract item.
     */
    public function idrNumbers(): HasMany
    {
        return $this->hasMany(IdrNumber::class);
    }
}

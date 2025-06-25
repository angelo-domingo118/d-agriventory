<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ItemSpecification extends Model
{
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
    public function catalogItem(): BelongsTo
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
}

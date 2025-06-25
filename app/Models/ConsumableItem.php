<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConsumableItem extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'consumable_items';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'consumable_record_id',
        'item_specification_id',
        'initial_quantity',
        'current_quantity',
    ];

    /**
     * Get the consumable record this item belongs to.
     */
    public function record(): BelongsTo
    {
        return $this->belongsTo(ConsumableRecord::class, 'consumable_record_id');
    }

    /**
     * Get the specification for this consumable item.
     */
    public function specification(): BelongsTo
    {
        return $this->belongsTo(ItemSpecification::class, 'item_specification_id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ItemComponent extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'item_components';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'ics_item_batch_id',
        'component_type',
        'brand',
        'model',
        'serial_number',
    ];

    /**
     * Get the ics item batch that owns the component.
     */
    public function icsItemBatch(): BelongsTo
    {
        return $this->belongsTo(IcsItemBatch::class, 'ics_item_batch_id');
    }
}

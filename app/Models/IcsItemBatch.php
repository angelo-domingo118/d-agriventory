<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IcsItemBatch extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'ics_item_batches';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'ics_number_id',
        'quantity',
        'identification_data',
    ];

    /**
     * Get the ICS number this batch belongs to.
     */
    public function icsNumber(): BelongsTo
    {
        return $this->belongsTo(IcsNumber::class);
    }
}

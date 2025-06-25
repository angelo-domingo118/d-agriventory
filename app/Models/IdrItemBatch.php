<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class IdrItemBatch extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'idr_item_batches';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'idr_number_id',
        'quantity',
        'identification_data',
    ];

    /**
     * Get the IDR number this batch belongs to.
     */
    public function idrNumber(): BelongsTo
    {
        return $this->belongsTo(IdrNumber::class);
    }

    /**
     * Get the acknowledgement receipts for this batch.
     */
    public function acknowledgementReceipts(): HasMany
    {
        return $this->hasMany(AcknowledgementReceipt::class);
    }
}

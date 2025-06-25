<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AcknowledgementReceipt extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'acknowledgement_receipts';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'idr_item_batch_id',
        'quantity_reduced',
    ];

    /**
     * Get the IDR item batch this receipt is for.
     */
    public function idrItemBatch(): BelongsTo
    {
        return $this->belongsTo(IdrItemBatch::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;

class ParItemBatch extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'par_item_batches';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'par_number_id',
        'contract_item_id',
        'quantity',
    ];

    /**
     * Get the PAR number this batch belongs to.
     */
    public function parNumber(): BelongsTo
    {
        return $this->belongsTo(ParNumber::class, 'par_number_id');
    }

    /**
     * Get the contract item for this batch.
     */
    public function contractItem(): BelongsTo
    {
        return $this->belongsTo(ContractItem::class);
    }

    /**
     * Get the catalog item through the contract item and item specification.
     */
    public function catalogItem(): HasOneThrough
    {
        return $this->hasOneThrough(
            ItemsCatalog::class,
            ItemSpecification::class,
            'id', // Foreign key on ItemSpecification table
            'id', // Foreign key on ItemsCatalog table
            'contract_item_id', // Local key on ParItemBatch table
            'catalog_item_id' // Local key on ItemSpecification table
        );
    }
}

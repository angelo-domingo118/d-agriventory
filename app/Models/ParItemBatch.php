<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
        'identification_data',
    ];

    /**
     * Get the PAR number this batch belongs to.
     */
    public function parNumber(): BelongsTo
    {
        return $this->belongsTo(ParNumber::class, 'par_number_id');
    }
}

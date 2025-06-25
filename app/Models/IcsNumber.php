<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class IcsNumber extends Model
{
    /**
     * The table associated with the model.
     * 
     * Note: This table uses the singular naming convention 'ics_number' instead of the Laravel
     * plural convention 'ics_numbers'. This is intentional to maintain consistency with the
     * business terminology where "ICS Number" refers to a specific inventory document type.
     * This naming convention is used consistently throughout the application for similar inventory
     * document types (par_number, idr_number, etc.).
     *
     * @var string
     */
    protected $table = 'ics_number';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'assigned_employee_id',
        'contract_item_id',
        'ics_type',
        'estimated_useful_life',
        'date_accepted',
        'remarks',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'date_accepted' => 'date',
    ];

    /**
     * Get the employee this ICS number is assigned to.
     */
    public function assignedEmployee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'assigned_employee_id');
    }

    /**
     * Get the contract item for this ICS number.
     */
    public function contractItem(): BelongsTo
    {
        return $this->belongsTo(ContractItem::class);
    }

    /**
     * Get the item batches for this ICS number.
     */
    public function itemBatches(): HasMany
    {
        return $this->hasMany(IcsItemBatch::class);
    }

    /**
     * Get the transfers for this ICS number.
     */
    public function transfers(): HasMany
    {
        return $this->hasMany(IcsTransfer::class);
    }
}

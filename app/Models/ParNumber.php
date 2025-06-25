<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ParNumber extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'par_number';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'assigned_employee_id',
        'contract_item_id',
        'area_code',
        'building_code',
        'account_code',
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
     * Get the employee this PAR number is assigned to.
     */
    public function assignedEmployee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'assigned_employee_id');
    }

    /**
     * Get the contract item for this PAR number.
     */
    public function contractItem(): BelongsTo
    {
        return $this->belongsTo(ContractItem::class);
    }

    /**
     * Get the item batches for this PAR number.
     */
    public function itemBatches(): HasMany
    {
        return $this->hasMany(ParItemBatch::class);
    }

    /**
     * Get the transfers for this PAR number.
     */
    public function transfers(): HasMany
    {
        return $this->hasMany(ParTransfer::class);
    }
}

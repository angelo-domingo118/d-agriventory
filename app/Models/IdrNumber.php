<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class IdrNumber extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'idr_number';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'number',
        'assigned_employee_id',
        'approving_employee_id',
        'contract_item_id',
        'inventory_code',
        'ors',
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
     * Get the employee this IDR number is assigned to.
     */
    public function assignedEmployee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'assigned_employee_id');
    }

    /**
     * Get the employee who approved this IDR.
     */
    public function approvingEmployee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'approving_employee_id');
    }

    /**
     * Get the contract item for this IDR number.
     */
    public function contractItem(): BelongsTo
    {
        return $this->belongsTo(ContractItem::class);
    }

    /**
     * Get the item batches for this IDR number.
     */
    public function itemBatches(): HasMany
    {
        return $this->hasMany(IdrItemBatch::class);
    }
}

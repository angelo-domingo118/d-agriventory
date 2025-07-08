<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class IdrNumber extends Model
{
    use HasFactory;

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
        'quantity',
        'inventory_code',
        'ors',
        'date_prepared',
        'date_accepted',
        'remarks',
        'received_by_id',
        'received_from_id',
        'date',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'date_prepared' => 'date',
        'date_accepted' => 'date',
        'date' => 'date',
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
     * Get the employee who received the items.
     */
    public function receivedBy(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'received_by_id');
    }

    /**
     * Get the employee who issued the items.
     */
    public function receivedFrom(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'received_from_id');
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

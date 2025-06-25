<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IcsTransfer extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'ics_transfers';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'ics_number_id',
        'from_employee_id',
        'to_employee_id',
        'transfer_date',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'transfer_date' => 'date',
    ];

    /**
     * Get the ICS number for this transfer.
     */
    public function icsNumber(): BelongsTo
    {
        return $this->belongsTo(IcsNumber::class);
    }

    /**
     * Get the employee the transfer is from.
     */
    public function fromEmployee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'from_employee_id');
    }

    /**
     * Get the employee the transfer is to.
     */
    public function toEmployee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'to_employee_id');
    }
}

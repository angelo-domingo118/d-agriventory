<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Employee extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'division_id',
        'position_id',
    ];

    /**
     * Get the division that employs this employee.
     */
    public function division(): BelongsTo
    {
        return $this->belongsTo(Division::class);
    }

    /**
     * Get the position of this employee.
     */
    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }

    /**
     * Get the ICS numbers assigned to this employee.
     */
    public function icsNumbers(): HasMany
    {
        return $this->hasMany(IcsNumber::class, 'assigned_employee_id');
    }

    /**
     * Get the PAR numbers assigned to this employee.
     */
    public function parNumbers(): HasMany
    {
        return $this->hasMany(ParNumber::class, 'assigned_employee_id');
    }

    /**
     * Get the IDR numbers assigned to this employee.
     */
    public function assignedIdrNumbers(): HasMany
    {
        return $this->hasMany(IdrNumber::class, 'assigned_employee_id');
    }

    /**
     * Get the IDR numbers approved by this employee.
     */
    public function approvedIdrNumbers(): HasMany
    {
        return $this->hasMany(IdrNumber::class, 'approving_employee_id');
    }

    /**
     * Get the ICS transfers originating from this employee.
     */
    public function icsTransfersFrom(): HasMany
    {
        return $this->hasMany(IcsTransfer::class, 'from_employee_id');
    }

    /**
     * Get the ICS transfers destined to this employee.
     */
    public function icsTransfersTo(): HasMany
    {
        return $this->hasMany(IcsTransfer::class, 'to_employee_id');
    }

    /**
     * Get the PAR transfers originating from this employee.
     */
    public function parTransfersFrom(): HasMany
    {
        return $this->hasMany(ParTransfer::class, 'from_employee_id');
    }

    /**
     * Get the PAR transfers destined to this employee.
     */
    public function parTransfersTo(): HasMany
    {
        return $this->hasMany(ParTransfer::class, 'to_employee_id');
    }
}

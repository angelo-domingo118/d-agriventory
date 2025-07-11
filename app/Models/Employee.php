<?php

namespace App\Models;

use App\Models\Traits\ClearsDashboardCache;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Employee extends Model
{
    use ClearsDashboardCache, HasFactory, SoftDeletes;

    /**
     * The Employee model represents a person in the organization.
     *
     * Note: This table is not for authentication. It represents a list of all
     * personnel, regardless of whether they have an account to access the system.
     */

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'employee_number',
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

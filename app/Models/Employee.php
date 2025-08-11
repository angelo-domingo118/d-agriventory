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
        'division_id',
        'position_title',
        'position_code',
        'position_type',
        'position_description',
    ];

    /**
     * Get the division that employs this employee.
     */
    public function division(): BelongsTo
    {
        return $this->belongsTo(Division::class);
    }

    /**
     * Get the formatted position type for display.
     */
    public function getFormattedPositionTypeAttribute(): ?string
    {
        if (!$this->position_type) {
            return null;
        }
        
        return str_replace('_', ' ', ucwords(strtolower($this->position_type), '_'));
    }

    /**
     * Check if the employee has position information.
     */
    public function hasPosition(): bool
    {
        return !empty($this->position_title);
    }

    /**
     * Get a full position descriptor for display.
     */
    public function getFullPositionAttribute(): ?string
    {
        if (!$this->hasPosition()) {
            return null;
        }

        $parts = [$this->position_title];
        
        if ($this->position_code) {
            $parts[] = "({$this->position_code})";
        }
        
        if ($this->position_type) {
            $parts[] = "[{$this->getFormattedPositionTypeAttribute()}]";
        }

        return implode(' ', $parts);
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

    /**
     * Get deletion impact statistics.
     */
    public function getDeletionImpact(): array
    {
        $icsCount = $this->icsNumbers()->count();
        $parCount = $this->parNumbers()->count();
        $assignedIdrCount = $this->assignedIdrNumbers()->count();
        $approvedIdrCount = $this->approvedIdrNumbers()->count();
        $icsTransfersFromCount = $this->icsTransfersFrom()->count();
        $icsTransfersToCount = $this->icsTransfersTo()->count();
        $parTransfersFromCount = $this->parTransfersFrom()->count();
        $parTransfersToCount = $this->parTransfersTo()->count();

        $totalAssignments = $icsCount + $parCount + $assignedIdrCount + $approvedIdrCount;
        $totalTransfers = $icsTransfersFromCount + $icsTransfersToCount + $parTransfersFromCount + $parTransfersToCount;

        return [
            'ics_numbers' => $icsCount,
            'par_numbers' => $parCount,
            'assigned_idr_numbers' => $assignedIdrCount,
            'approved_idr_numbers' => $approvedIdrCount,
            'ics_transfers_from' => $icsTransfersFromCount,
            'ics_transfers_to' => $icsTransfersToCount,
            'par_transfers_from' => $parTransfersFromCount,
            'par_transfers_to' => $parTransfersToCount,
            'total_assignments' => $totalAssignments,
            'total_transfers' => $totalTransfers,
            'has_associated_data' => $totalAssignments > 0 || $totalTransfers > 0,
            'risk_level' => $totalAssignments > 0 ? 'high' : ($totalTransfers > 0 ? 'medium' : 'safe'),
            'risk_message' => $totalAssignments > 0 
                ? 'This employee has active inventory assignments and should not be deleted.'
                : ($totalTransfers > 0 
                    ? 'This employee has transfer history. Deletion will affect historical records.'
                    : 'This employee has no associated data and is safe to delete.'),
        ];
    }

    /**
     * Check if this employee can be safely deleted (has no associations).
     */
    public function canBeDeletedSafely(): bool
    {
        $impact = $this->getDeletionImpact();
        return !$impact['has_associated_data'];
    }

    /**
     * Check if deletion should be blocked (has active inventory).
     */
    public function isDeletionBlocked(): bool
    {
        return $this->getDeletionImpact()['risk_level'] === 'high';
    }
}

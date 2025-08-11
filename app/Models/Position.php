<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Position extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'title',
        'code',
        'position_type',
        'description',
    ];

    /**
     * Get the employees that have this position.
     */
    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }

    /**
     * Get the employees for the position, including those related to soft-deleted positions.
     */
    public function employeesWithTrashedPositions(): HasMany
    {
        return $this->hasMany(Employee::class)->withTrashed();
    }

    /**
     * Get deletion impact statistics.
     */
    public function getDeletionImpact(): array
    {
        $employeesCount = $this->employees()->count();

        return [
            'employees' => $employeesCount,
            'has_associated_data' => $employeesCount > 0,
            'risk_level' => $employeesCount > 0 ? 'medium' : 'safe',
            'risk_message' => $employeesCount > 0 
                ? 'This position has employees assigned. Deletion will remove their position assignment.'
                : 'This position has no associated data and is safe to delete.',
        ];
    }

    /**
     * Check if this position can be safely deleted (has no associations).
     */
    public function canBeDeletedSafely(): bool
    {
        $impact = $this->getDeletionImpact();
        return !$impact['has_associated_data'];
    }

    /**
     * Check if deletion should be blocked.
     */
    public function isDeletionBlocked(): bool
    {
        // Positions don't block deletion, they just unassign employees
        return false;
    }
}

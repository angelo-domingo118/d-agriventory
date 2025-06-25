<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Position extends Model
{
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
}

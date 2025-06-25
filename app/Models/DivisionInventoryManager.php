<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DivisionInventoryManager extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'division_id',
    ];

    /**
     * Get the user that is an inventory manager.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the division that this manager manages.
     */
    public function division(): BelongsTo
    {
        return $this->belongsTo(Division::class);
    }
}

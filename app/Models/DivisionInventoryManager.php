<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DivisionInventoryManager extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'division_id',
    ];

    /**
     * Note: 'user_id' is intentionally excluded from $fillable to prevent mass-assignment
     * of this sensitive field. Instead, 'user_id' should be assigned explicitly in the
     * controller or service layer from the authenticated user's ID or through validated input,
     * avoiding direct mass assignment from request data to mitigate privilege escalation risks.
     */

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

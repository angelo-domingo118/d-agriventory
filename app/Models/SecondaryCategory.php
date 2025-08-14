<?php

namespace App\Models;

use App\Models\Traits\ClearsDashboardCache;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class SecondaryCategory extends Model
{
    use ClearsDashboardCache, HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'primary_category_id',
        'name',
        'code',
        'description',
    ];

    /**
     * Get the primary category that contains this secondary category.
     */
    public function primaryCategory(): BelongsTo
    {
        return $this->belongsTo(PrimaryCategory::class);
    }

    /**
     * Get the items catalog entries for this secondary category.
     */
    public function items(): HasMany
    {
        return $this->hasMany(ItemsCatalog::class);
    }

    /**
     * Get deletion impact statistics.
     */
    public function getDeletionImpact(): array
    {
        $itemsCount = $this->items()->count();

        return [
            'items' => $itemsCount,
            'has_associated_data' => $itemsCount > 0,
        ];
    }

    /**
     * Force delete this secondary category and all associated data.
     */
    public function forceDeleteWithAssociations(): bool
    {
        return DB::transaction(function () {
            // Delete all catalog items in this secondary category
            $this->items()->delete();

            // Finally delete the secondary category
            return $this->delete();
        });
    }

    /**
     * Check if this category can be safely deleted (has no associations).
     */
    public function canBeDeletedSafely(): bool
    {
        return ! $this->items()->exists();
    }
}

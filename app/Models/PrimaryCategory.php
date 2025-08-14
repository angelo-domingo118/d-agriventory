<?php

namespace App\Models;

use App\Models\Traits\ClearsDashboardCache;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class PrimaryCategory extends Model
{
    use ClearsDashboardCache, HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'code',
        'description',
    ];

    /**
     * Get the secondary categories for this primary category.
     */
    public function secondaryCategories(): HasMany
    {
        return $this->hasMany(SecondaryCategory::class);
    }

    /**
     * Get deletion impact statistics.
     */
    public function getDeletionImpact(): array
    {
        $secondaryCategories = $this->secondaryCategories()->withCount('items')->get();
        $secondaryCount = $secondaryCategories->count();
        $itemsCount = $secondaryCategories->sum('items_count');

        return [
            'secondary_categories' => $secondaryCount,
            'items' => $itemsCount,
            'has_associated_data' => $secondaryCount > 0 || $itemsCount > 0,
        ];
    }

    /**
     * Force delete this primary category and all associated data.
     */
    public function forceDeleteWithAssociations(): bool
    {
        return DB::transaction(function () {
            // Delete all items in secondary categories
            foreach ($this->secondaryCategories as $secondaryCategory) {
                $secondaryCategory->items()->delete();
            }

            // Delete all secondary categories
            $this->secondaryCategories()->delete();

            // Finally delete the primary category
            return $this->delete();
        });
    }

    /**
     * Check if this category can be safely deleted (has no associations).
     */
    public function canBeDeletedSafely(): bool
    {
        return ! $this->secondaryCategories()->exists();
    }
}

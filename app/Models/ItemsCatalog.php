<?php

namespace App\Models;

use App\Models\Traits\ClearsDashboardCache;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ItemsCatalog extends Model
{
    use ClearsDashboardCache, HasFactory, SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'items_catalog';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'unit',
        'secondary_category_id',
        'code',
    ];

    /**
     * Get the secondary category that categorizes this item.
     */
    public function secondaryCategory(): BelongsTo
    {
        return $this->belongsTo(SecondaryCategory::class);
    }

    /**
     * Get the item specifications for this catalog item.
     */
    public function specifications(): HasMany
    {
        return $this->hasMany(ItemSpecification::class, 'item_catalog_id');
    }
}

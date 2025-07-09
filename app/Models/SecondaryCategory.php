<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Traits\ClearsDashboardCache;

class SecondaryCategory extends Model
{
    use HasFactory, SoftDeletes, ClearsDashboardCache;

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
}

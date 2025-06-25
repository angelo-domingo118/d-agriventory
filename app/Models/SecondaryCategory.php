<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SecondaryCategory extends Model
{
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
    public function itemsCatalog(): HasMany
    {
        return $this->hasMany(ItemsCatalog::class);
    }
}

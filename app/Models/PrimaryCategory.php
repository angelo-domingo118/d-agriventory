<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PrimaryCategory extends Model
{
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
}

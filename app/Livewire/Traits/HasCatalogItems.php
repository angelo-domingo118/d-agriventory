<?php

namespace App\Livewire\Traits;

use App\Models\ItemsCatalog;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\Computed;

trait HasCatalogItems
{
    #[Computed]
    public function catalogItems()
    {
        return Cache::remember('catalog-items-for-contracts', now()->addHour(), function () {
            return ItemsCatalog::with('secondaryCategory.primaryCategory')
                ->orderBy('name')
                ->get()
                ->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'name' => $item->name,
                        'code' => $item->code,
                        'unit' => $item->unit,
                        'category' => $item->secondaryCategory?->name,
                        'primary_category' => $item->secondaryCategory?->primaryCategory?->name,
                        'label' => "{$item->name} ({$item->code}) - {$item->unit}",
                    ];
                });
        });
    }
} 
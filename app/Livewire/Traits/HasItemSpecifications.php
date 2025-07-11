<?php

namespace App\Livewire\Traits;

use App\Models\ItemSpecification;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\Computed;

trait HasItemSpecifications
{
    #[Computed]
    public function itemSpecifications()
    {
        return Cache::remember('item-specifications-for-contracts', now()->addHour(), function () {
            return ItemSpecification::with('itemCatalog')
                ->get()
                ->map(function ($spec) {
                    $itemName = $spec->itemCatalog?->name ?? 'N/A';
                    $itemCode = $spec->itemCatalog?->code ?? 'N/A';

                    return [
                        'id' => $spec->id,
                        'label' => "{$itemName} ({$itemCode}) - {$spec->specification}",
                    ];
                });
        });
    }
} 
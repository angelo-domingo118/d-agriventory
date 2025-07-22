<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Computed;
use App\Models\ConsumableItem;
use App\Models\ConsumableRecord;
use App\Models\IcsTransfer;
use App\Models\ParTransfer;
use Illuminate\Support\Facades\DB;

new #[Layout('components.layouts.app')] class extends Component
{
    public $division;

    public function mount()
    {
        $user = auth()->user()->load('divisionInventoryManager.division');
        $this->division = $user->divisionInventoryManager->division;
    }

    #[Computed]
    public function itemsInStock(): int
    {
        return ConsumableItem::whereHas('record', function ($query) {
            $query->where('division_id', $this->division->id);
        })->where('current_quantity', '>', 0)->count();
    }

    #[Computed]
    public function lowStockItems(): int
    {
        return ConsumableItem::whereHas('record', function ($query) {
            $query->where('division_id', $this->division->id);
        })->whereRaw('current_quantity <= initial_quantity * 0.2')
          ->where('current_quantity', '>', 0)
          ->count();
    }

    #[Computed]
    public function outOfStockItems(): int
    {
        return ConsumableItem::whereHas('record', function ($query) {
            $query->where('division_id', $this->division->id);
        })->where('current_quantity', 0)->count();
    }

    #[Computed]
    public function totalConsumableValue(): float
    {
        return ConsumableItem::whereHas('record', function ($query) {
            $query->where('division_id', $this->division->id);
        })
        ->join('item_specifications', 'consumable_items.item_specification_id', '=', 'item_specifications.id')
        ->join('contract_items', 'item_specifications.id', '=', 'contract_items.item_specification_id')
        ->sum(DB::raw('consumable_items.current_quantity * contract_items.unit_price'));
    }

    #[Computed]
    public function recentConsumableRecords()
    {
        return ConsumableRecord::where('division_id', $this->division->id)
            ->with(['items.specification.itemCatalog'])
            ->latest()
            ->take(5)
            ->get();
    }

    #[Computed]
    public function lowStockItemsList()
    {
        return ConsumableItem::whereHas('record', function ($query) {
            $query->where('division_id', $this->division->id);
        })
        ->with(['specification.itemCatalog', 'record'])
        ->whereRaw('current_quantity <= initial_quantity * 0.2')
        ->where('current_quantity', '>', 0)
        ->orderBy('current_quantity', 'asc')
        ->take(10)
        ->get();
    }
}

?>

<div>
    <x-inventory-manager.layout :heading="__('Inventory Manager Dashboard')" :subheading="'Division: ' . $this->division->name">
        
        <!-- Header Actions -->
        <div class="border-b border-stone-200 pb-4 dark:border-stone-700 mb-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-semibold text-stone-900 dark:text-stone-100">
                        Division Overview
                    </h1>
                    <p class="mt-1 text-sm text-stone-600 dark:text-stone-400">
                        Manage and track your division's consumable inventory
                    </p>
                </div>
                <div class="flex items-center gap-x-4">
                    <flux:button 
                        variant="primary" 
                        :href="route('inventory-manager.consumables.create')" 
                        wire:navigate>
                        <flux:icon.plus class="w-4 h-4 mr-2" />
                        Add Consumable Record
                    </flux:button>
                </div>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <!-- Items In Stock Card -->
            <div class="overflow-hidden rounded-lg border border-stone-200 bg-white shadow-sm dark:border-stone-700 dark:bg-stone-800">
                <div class="p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-blue-100 dark:bg-blue-900/20">
                                <flux:icon.boxes class="h-6 w-6 text-blue-600 dark:text-blue-400" />
                            </div>
                        </div>
                        <div class="ml-4 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-stone-500 dark:text-stone-400 truncate">
                                    Items In Stock
                                </dt>
                                <dd class="text-2xl font-semibold text-stone-900 dark:text-stone-100">
                                    {{ number_format($this->itemsInStock) }}
                                </dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Low Stock Items Card -->
            <div class="overflow-hidden rounded-lg border border-stone-200 bg-white shadow-sm dark:border-stone-700 dark:bg-stone-800">
                <div class="p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-amber-100 dark:bg-amber-900/20">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6 text-amber-600 dark:text-amber-400">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                                </svg>
                            </div>
                        </div>
                        <div class="ml-4 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-stone-500 dark:text-stone-400 truncate">
                                    Low Stock Items
                                </dt>
                                <dd class="text-2xl font-semibold text-amber-600 dark:text-amber-400">
                                    {{ number_format($this->lowStockItems) }}
                                </dd>
                            </dl>
                        </div>
                    </div>
                </div>
                @if($this->lowStockItems > 0)
                    <div class="border-t border-amber-200 bg-amber-50 px-6 py-2 dark:border-amber-800 dark:bg-amber-900/10">
                        <div class="text-sm">
                            <span class="text-amber-700 dark:text-amber-300 font-medium">Requires attention</span>
                        </div>
                    </div>
                @endif
            </div>

            <!-- Out of Stock Card -->
            <div class="overflow-hidden rounded-lg border border-stone-200 bg-white shadow-sm dark:border-stone-700 dark:bg-stone-800">
                <div class="p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-red-100 dark:bg-red-900/20">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6 text-red-600 dark:text-red-400">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </div>
                        </div>
                        <div class="ml-4 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-stone-500 dark:text-stone-400 truncate">
                                    Out of Stock
                                </dt>
                                <dd class="text-2xl font-semibold text-red-600 dark:text-red-400">
                                    {{ number_format($this->outOfStockItems) }}
                                </dd>
                            </dl>
                        </div>
                    </div>
                </div>
                @if($this->outOfStockItems > 0)
                    <div class="border-t border-red-200 bg-red-50 px-6 py-2 dark:border-red-800 dark:bg-red-900/10">
                        <div class="text-sm">
                            <span class="text-red-700 dark:text-red-300 font-medium">Immediate attention required</span>
                        </div>
                    </div>
                @endif
            </div>

            <!-- Total Value Card -->
            <div class="overflow-hidden rounded-lg border border-stone-200 bg-white shadow-sm dark:border-stone-700 dark:bg-stone-800">
                <div class="p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-green-100 dark:bg-green-900/20">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6 text-green-600 dark:text-green-400">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                </svg>
                            </div>
                        </div>
                        <div class="ml-4 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-stone-500 dark:text-stone-400 truncate">
                                    Total Value
                                </dt>
                                <dd class="text-2xl font-semibold text-green-600 dark:text-green-400">
                                    ₱{{ number_format($this->totalConsumableValue, 2) }}
                                </dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Recent Consumable Records -->
            <div class="overflow-hidden rounded-lg border border-stone-200 bg-white shadow-sm dark:border-stone-700 dark:bg-stone-800">
                <div class="border-b border-stone-200 px-6 py-4 dark:border-stone-700">
                    <h3 class="text-lg font-medium text-stone-800 dark:text-stone-200">Recent Consumable Records</h3>
                    <p class="mt-1 text-sm text-stone-500 dark:text-stone-400">Latest consumable records added to your division</p>
                </div>
                <div class="p-6">
                    @if($this->recentConsumableRecords->count() > 0)
                        <div class="space-y-4">
                            @foreach($this->recentConsumableRecords as $record)
                            <div class="flex items-center justify-between p-4 rounded-lg border border-stone-200 dark:border-stone-700 hover:bg-stone-50 dark:hover:bg-stone-700/50 transition-colors duration-150">
                                <div class="flex-1">
                                    <div class="flex items-center space-x-3">
                                        <div class="flex-shrink-0">
                                            <div class="flex h-8 w-8 items-center justify-center rounded-full bg-blue-100 dark:bg-blue-900/20">
                                                <flux:icon.document-text class="h-4 w-4 text-blue-600 dark:text-blue-400" />
                                            </div>
                                        </div>
                                        <div class="flex-1">
                                            <p class="font-medium text-stone-900 dark:text-stone-100">{{ $record->record_number }}</p>
                                            <div class="flex items-center space-x-4 mt-1">
                                                <p class="text-sm text-stone-600 dark:text-stone-400">{{ $record->date_received->format('M d, Y') }}</p>
                                                <span class="text-stone-300 dark:text-stone-600">•</span>
                                                <p class="text-sm text-stone-500 dark:text-stone-500">{{ $record->items->count() }} items</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex-shrink-0">
                                    <flux:button 
                                        variant="ghost" 
                                        size="sm" 
                                        href="{{ route('inventory-manager.consumables.show', $record) }}" 
                                        wire:navigate>
                                        <flux:icon.eye class="h-4 w-4 mr-1" />
                                        View
                                    </flux:button>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-8">
                            <flux:icon.document-text class="mx-auto h-12 w-12 text-stone-300 dark:text-stone-600" />
                            <h4 class="mt-2 text-sm font-medium text-stone-900 dark:text-stone-100">No consumable records</h4>
                            <p class="mt-1 text-sm text-stone-500 dark:text-stone-400">Get started by creating your first consumable record.</p>
                        </div>
                    @endif
                    <div class="mt-6 pt-4 border-t border-stone-200 dark:border-stone-700">
                        <flux:button 
                            variant="ghost" 
                            href="{{ route('inventory-manager.consumables.index') }}" 
                            wire:navigate
                            class="w-full justify-center">
                            View All Records
                            <flux:icon.arrow-right class="ml-2 h-4 w-4" />
                        </flux:button>
                    </div>
                </div>
            </div>

            <!-- Low Stock Alert -->
            <div class="overflow-hidden rounded-lg border border-stone-200 bg-white shadow-sm dark:border-stone-700 dark:bg-stone-800">
                <div class="border-b border-stone-200 px-6 py-4 dark:border-stone-700">
                    <h3 class="text-lg font-medium text-stone-800 dark:text-stone-200">Low Stock Alert</h3>
                    <p class="mt-1 text-sm text-stone-500 dark:text-stone-400">Items that need restocking soon</p>
                </div>
                <div class="p-6">
                    @if($this->lowStockItemsList->count() > 0)
                        <div class="space-y-4">
                            @foreach($this->lowStockItemsList as $item)
                            <div class="flex items-center justify-between p-4 border-l-4 border-amber-500 bg-amber-50 dark:bg-amber-900/20 rounded-r-lg">
                                <div class="flex-1">
                                    <div class="flex items-center space-x-3">
                                        <div class="flex-shrink-0">
                                            <div class="flex h-8 w-8 items-center justify-center rounded-full bg-amber-100 dark:bg-amber-900/30">
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 text-amber-600 dark:text-amber-400">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                                                </svg>
                                            </div>
                                        </div>
                                        <div class="flex-1">
                                            <p class="font-medium text-stone-900 dark:text-stone-100">{{ $item->specification->itemCatalog->name }}</p>
                                            <p class="text-sm text-stone-600 dark:text-stone-400">{{ $item->specification->brand ?? 'Generic' }}</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex-shrink-0 text-right">
                                    <p class="font-semibold text-amber-700 dark:text-amber-300">{{ $item->current_quantity }} left</p>
                                    <p class="text-xs text-stone-500 dark:text-stone-500">of {{ $item->initial_quantity }}</p>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-8">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-12 h-12 mx-auto text-green-500 mb-4">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <h4 class="text-sm font-medium text-stone-900 dark:text-stone-100">All items well stocked!</h4>
                            <p class="mt-1 text-sm text-stone-500 dark:text-stone-400">No items currently running low on inventory.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </x-inventory-manager.layout>
</div> 
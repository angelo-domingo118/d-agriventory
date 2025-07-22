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
        <!-- Statistics Cards -->
        <div class="bg-white dark:bg-stone-800 shadow-sm rounded-lg p-6 mb-6">
            <h2 class="text-xl font-medium text-stone-800 dark:text-stone-200 mb-4">Division Statistics</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <x-dashboard.stat-card title="Items In Stock" :value="$this->itemsInStock">
                    <x-slot name="icon">
                        <flux:icon.boxes class="w-6 h-6" />
                    </x-slot>
                </x-dashboard.stat-card>

                <x-dashboard.stat-card title="Low Stock Items" :value="$this->lowStockItems">
                    <x-slot name="icon">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6 text-amber-600">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                        </svg>
                    </x-slot>
                </x-dashboard.stat-card>

                <x-dashboard.stat-card title="Out of Stock" :value="$this->outOfStockItems">
                    <x-slot name="icon">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6 text-red-600">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </x-slot>
                </x-dashboard.stat-card>

                <x-dashboard.stat-card title="Total Value" :value="'₱' . number_format($this->totalConsumableValue, 2)">
                    <x-slot name="icon">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6 text-green-600">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                        </svg>
                    </x-slot>
                </x-dashboard.stat-card>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Recent Consumable Records -->
            <div class="bg-white dark:bg-stone-800 shadow-sm rounded-lg p-6">
                <h3 class="text-lg font-medium text-stone-800 dark:text-stone-200 mb-4">Recent Consumable Records</h3>
                @if($this->recentConsumableRecords->count() > 0)
                    <div class="space-y-3">
                        @foreach($this->recentConsumableRecords as $record)
                        <div class="flex items-center justify-between p-3 bg-stone-50 dark:bg-stone-700 rounded-lg">
                            <div>
                                <p class="font-medium text-stone-900 dark:text-stone-100">{{ $record->record_number }}</p>
                                <p class="text-sm text-stone-600 dark:text-stone-400">{{ $record->date_received->format('M d, Y') }}</p>
                                <p class="text-sm text-stone-500 dark:text-stone-500">{{ $record->items->count() }} items</p>
                            </div>
                            <div class="text-right">
                                <flux:button 
                                    variant="ghost" 
                                    size="sm" 
                                    href="{{ route('inventory-manager.consumables.show', $record) }}" 
                                    wire:navigate>
                                    View
                                </flux:button>
                            </div>
                        </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-stone-600 dark:text-stone-400">No consumable records found.</p>
                @endif
                <div class="mt-4">
                    <flux:button 
                        variant="ghost" 
                        href="{{ route('inventory-manager.consumables.index') }}" 
                        wire:navigate>
                        View All Records
                    </flux:button>
                </div>
            </div>

            <!-- Low Stock Alert -->
            <div class="bg-white dark:bg-stone-800 shadow-sm rounded-lg p-6">
                <h3 class="text-lg font-medium text-stone-800 dark:text-stone-200 mb-4">Low Stock Alert</h3>
                @if($this->lowStockItemsList->count() > 0)
                    <div class="space-y-3">
                        @foreach($this->lowStockItemsList as $item)
                        <div class="flex items-center justify-between p-3 border-l-4 border-amber-500 bg-amber-50 dark:bg-amber-900/20 rounded-r-lg">
                            <div>
                                <p class="font-medium text-stone-900 dark:text-stone-100">{{ $item->specification->itemCatalog->name }}</p>
                                <p class="text-sm text-stone-600 dark:text-stone-400">{{ $item->specification->brand ?? 'Generic' }}</p>
                            </div>
                            <div class="text-right">
                                <p class="font-semibold text-amber-700 dark:text-amber-300">{{ $item->current_quantity }} left</p>
                                <p class="text-xs text-stone-500 dark:text-stone-500">of {{ $item->initial_quantity }}</p>
                            </div>
                        </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-4">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-12 h-12 mx-auto text-green-500 mb-2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <p class="text-stone-600 dark:text-stone-400">All items are well stocked!</p>
                    </div>
                @endif
            </div>
        </div>
    </x-inventory-manager.layout>
</div> 
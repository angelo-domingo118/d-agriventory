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
    public function stats(): array
    {
        return [
            'items_in_stock' => $this->itemsInStock(),
            'low_stock_items' => $this->lowStockItems(),
            'out_of_stock_items' => $this->outOfStockItems(),
            'total_value' => $this->totalConsumableValue(),
        ];
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
    public function quickActions(): array
    {
        return [
            [
                'title' => 'Add Consumable Record',
                'subtitle' => 'Create new inventory record',
                'icon' => 'plus',
                'route' => 'inventory-manager.consumables.create',
                'color' => 'primary',
            ],
            [
                'title' => 'View All Items',
                'subtitle' => 'Browse inventory items',
                'icon' => 'boxes',
                'route' => 'inventory-manager.items.index',
                'color' => 'ghost',
            ],
            [
                'title' => 'Generate Reports',
                'subtitle' => 'View inventory reports',
                'icon' => 'chart-bar',
                'route' => 'inventory-manager.reports.index',
                'color' => 'ghost',
            ],
        ];
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
    <div class="flex items-center justify-between">
        <!-- Breadcrumbs as Title -->
        <div>
            <flux:breadcrumbs class="text-2xl font-semibold">
                <flux:breadcrumbs.item :href="route('inventory-manager.dashboard')" wire:navigate icon="home" class="text-xl sm:text-2xl font-semibold text-stone-700 dark:text-stone-300" />
                <flux:breadcrumbs.item class="text-xl sm:text-2xl font-semibold text-stone-900 dark:text-stone-100">Dashboard</flux:breadcrumbs.item>
            </flux:breadcrumbs>
        </div>
        <div class="flex items-center gap-x-2">
            <flux:button
                variant="outline"
                wire:click="$refresh"
                class="!p-2"
            >
                <x-flux::icon.rotate-cw class="h-5 w-5" wire:loading.class="animate-spin" />
                <span class="sr-only">Refresh</span>
            </flux:button>
            <flux:button 
                variant="primary" 
                :href="route('inventory-manager.consumables.create')" 
                wire:navigate>
                Add Consumable Record
            </flux:button>
        </div>
    </div>

    <div class="mt-6 grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
        <x-dashboard.stat-card 
            title="Items In Stock" 
            :value="number_format($this->stats['items_in_stock'])" 
            subtitle="Available items"
            class="bg-blue-50 dark:bg-blue-900/20"
        />
        
        <x-dashboard.stat-card 
            title="Low Stock Items" 
            :value="number_format($this->stats['low_stock_items'])" 
            :subtitle="$this->stats['low_stock_items'] > 0 ? 'Requires attention' : 'All items well stocked'"
            class="{{ $this->stats['low_stock_items'] > 0 ? 'bg-amber-50 dark:bg-amber-900/20' : 'bg-blue-50 dark:bg-blue-900/20' }}"
        />
        
        <x-dashboard.stat-card 
            title="Out of Stock" 
            :value="number_format($this->stats['out_of_stock_items'])" 
            :subtitle="$this->stats['out_of_stock_items'] > 0 ? 'Immediate attention needed' : 'No items out of stock'"
            class="{{ $this->stats['out_of_stock_items'] > 0 ? 'bg-red-50 dark:bg-red-900/20' : 'bg-blue-50 dark:bg-blue-900/20' }}"
        />
        
        <x-dashboard.stat-card 
            title="Total Value" 
            :value="'₱' . number_format($this->stats['total_value'], 2)" 
            subtitle="{{ $this->division->name }}"
            class="bg-green-50 dark:bg-green-900/20"
        />
    </div>

    <!-- Quick Actions -->
    <div class="mt-8 grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
        @foreach($this->quickActions as $action)
            <x-dashboard.action-card
                :title="$action['title']"
                :subtitle="$action['subtitle']"
                :href="route($action['route'])"
                :icon="$action['icon']"
                wire:navigate
            />
        @endforeach
    </div>

    <div class="mt-8 grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Recent Consumable Records -->
        <div class="overflow-hidden rounded-lg bg-white shadow ring-1 ring-black ring-opacity-5 dark:bg-stone-800 dark:ring-stone-700">
            <div class="border-b border-stone-200 px-6 py-4 dark:border-stone-700">
                <h3 class="text-lg font-semibold text-stone-900 dark:text-stone-100">Recent Consumable Records</h3>
                <p class="mt-1 text-sm text-stone-500 dark:text-stone-400">Latest consumable records added to your division</p>
            </div>
            <div class="p-6">
                @if($this->recentConsumableRecords->count() > 0)
                    <div class="flow-root">
                        <ul role="list" class="-mb-8">
                            @foreach($this->recentConsumableRecords as $record)
                                <li class="{{ !$loop->last ? 'pb-4' : '' }}">
                                    @if(!$loop->last)
                                        <div class="relative pb-4">
                                            <span class="absolute left-4 top-4 -ml-px h-full w-0.5 bg-stone-200 dark:bg-stone-700" aria-hidden="true"></span>
                                    @endif
                                    <div class="relative flex space-x-3">
                                        <div>
                                            <span class="h-8 w-8 rounded-full bg-blue-500 flex items-center justify-center ring-8 ring-white dark:ring-stone-800">
                                                <x-flux::icon.document-text class="h-4 w-4 text-white" />
                                            </span>
                                        </div>
                                        <div class="flex min-w-0 flex-1 justify-between space-x-4 pt-1.5">
                                            <div>
                                                <p class="text-sm font-medium text-stone-900 dark:text-stone-100">{{ $record->record_number }}</p>
                                                <p class="text-sm text-stone-500 dark:text-stone-400">{{ $record->items->count() }} items</p>
                                            </div>
                                            <div class="whitespace-nowrap text-right text-sm text-stone-500 dark:text-stone-400">
                                                <time datetime="{{ $record->date_received->format('Y-m-d') }}">{{ $record->date_received->format('M d, Y') }}</time>
                                            </div>
                                        </div>
                                    </div>
                                    @if(!$loop->last)
                                        </div>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @else
                    <div class="text-center py-8">
                        <x-flux::icon.document-text class="mx-auto h-12 w-12 text-stone-300 dark:text-stone-600" />
                        <h4 class="mt-2 text-sm font-medium text-stone-900 dark:text-stone-100">No consumable records</h4>
                        <p class="mt-1 text-sm text-stone-500 dark:text-stone-400">Get started by creating your first consumable record.</p>
                    </div>
                @endif
                <div class="mt-6 pt-4 border-t border-stone-200 dark:border-stone-700">
                    <flux:button 
                        variant="ghost" 
                        :href="route('inventory-manager.consumables.index')" 
                        wire:navigate
                        class="w-full justify-center">
                        View All Records
                        <x-flux::icon.arrow-right class="ml-2 h-4 w-4" />
                    </flux:button>
                </div>
            </div>
        </div>

        <!-- Low Stock Alert -->
        <div class="overflow-hidden rounded-lg bg-white shadow ring-1 ring-black ring-opacity-5 dark:bg-stone-800 dark:ring-stone-700">
            <div class="border-b border-stone-200 px-6 py-4 dark:border-stone-700">
                <h3 class="text-lg font-semibold text-stone-900 dark:text-stone-100">Low Stock Alert</h3>
                <p class="mt-1 text-sm text-stone-500 dark:text-stone-400">Items that need restocking soon</p>
            </div>
            <div class="p-6">
                @if($this->lowStockItemsList->count() > 0)
                    <div class="flow-root">
                        <ul role="list" class="-my-5 divide-y divide-stone-200 dark:divide-stone-700">
                            @foreach($this->lowStockItemsList as $item)
                                <li class="py-4">
                                    <div class="flex items-center space-x-4">
                                        <div class="flex-shrink-0">
                                            <div class="h-8 w-8 rounded-full bg-amber-100 flex items-center justify-center dark:bg-amber-900/20">
                                                <x-flux::icon.exclamation-triangle class="h-4 w-4 text-amber-600 dark:text-amber-400" />
                                            </div>
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <p class="text-sm font-medium text-stone-900 dark:text-stone-100 truncate">
                                                {{ $item->specification->itemCatalog->name }}
                                            </p>
                                            <p class="text-sm text-stone-500 dark:text-stone-400 truncate">
                                                {{ $item->specification->brand ?? 'Generic' }}
                                            </p>
                                        </div>
                                        <div class="text-right">
                                            <p class="text-sm font-semibold text-amber-700 dark:text-amber-300">{{ $item->current_quantity }} left</p>
                                            <p class="text-xs text-stone-500 dark:text-stone-400">of {{ $item->initial_quantity }}</p>
                                        </div>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @else
                    <div class="text-center py-8">
                        <x-flux::icon.check-circle class="mx-auto h-12 w-12 text-green-500 dark:text-green-400" />
                        <h4 class="mt-2 text-sm font-medium text-stone-900 dark:text-stone-100">All items well stocked!</h4>
                        <p class="mt-1 text-sm text-stone-500 dark:text-stone-400">No items currently running low on inventory.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div> 
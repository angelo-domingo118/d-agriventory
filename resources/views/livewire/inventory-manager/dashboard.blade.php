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

<div class="w-full mx-auto space-y-4 sm:space-y-6" wire:poll.15s>
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between space-y-4 sm:space-y-0 mb-2">
        <div class="relative">
            <h1 class="text-3xl sm:text-4xl font-bold bg-gradient-to-r from-stone-900 via-stone-700 to-green-700 dark:from-stone-100 dark:via-stone-300 dark:to-green-400 bg-clip-text text-transparent">D'Agriventory</h1>
            <p class="text-sm sm:text-base text-stone-600 dark:text-stone-400 mt-1">
                {{ $this->division->name }} Division - Inventory Management
            </p>
        </div>
        <div class="flex items-center space-x-4">
            <div class="hidden sm:flex items-center px-4 py-2 bg-green-50 dark:bg-green-900/20 rounded-full border border-green-200 dark:border-green-800">
                <x-flux::icon.check-circle class="h-4 w-4 text-green-600 dark:text-green-400 mr-2" />
                <span class="text-sm font-medium text-green-700 dark:text-green-300">System Online</span>
                </div>
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

    <!-- Enhanced Stats -->
    <div class="grid grid-cols-1 gap-4 sm:gap-5 md:gap-6 sm:grid-cols-2 lg:grid-cols-4">
        <!-- Items In Stock -->
        <div class="group bg-gradient-to-br from-white to-stone-50 dark:from-stone-800 dark:to-stone-900 rounded-xl shadow-lg border border-stone-200/50 dark:border-stone-700/50 p-4 sm:p-5 hover:shadow-xl hover:-translate-y-1 transition-all duration-300 backdrop-blur-sm">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs sm:text-sm font-semibold text-stone-500 dark:text-stone-400 uppercase tracking-wider">Items In Stock</p>
                    <p class="text-xl sm:text-2xl font-bold text-stone-900 dark:text-stone-100 mt-1">{{ number_format($this->stats['items_in_stock']) }}</p>
                    <div class="flex items-center mt-1">
                        <span class="text-xs text-stone-500 dark:text-stone-400">Available items</span>
                    </div>
                </div>
                <div class="flex-shrink-0 p-3 bg-blue-100 dark:bg-blue-900/30 rounded-xl group-hover:scale-110 transition-transform duration-300">
                    <x-flux::icon.box class="h-6 w-6 text-blue-600 dark:text-blue-400" />
                </div>
            </div>
        </div>

        <!-- Low Stock Items -->
        <div class="group bg-gradient-to-br from-white to-stone-50 dark:from-stone-800 dark:to-stone-900 rounded-xl shadow-lg border border-stone-200/50 dark:border-stone-700/50 p-4 sm:p-5 hover:shadow-xl hover:-translate-y-1 transition-all duration-300 backdrop-blur-sm">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs sm:text-sm font-semibold text-stone-500 dark:text-stone-400 uppercase tracking-wider">Low Stock Items</p>
                    <p class="text-xl sm:text-2xl font-bold text-stone-900 dark:text-stone-100 mt-1">{{ number_format($this->stats['low_stock_items']) }}</p>
                    <div class="flex items-center mt-1">
                        <span class="text-xs {{ $this->stats['low_stock_items'] > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-stone-500 dark:text-stone-400' }}">
                            {{ $this->stats['low_stock_items'] > 0 ? 'Requires attention' : 'All items well stocked' }}
                        </span>
                    </div>
                </div>
                <div class="flex-shrink-0 p-3 {{ $this->stats['low_stock_items'] > 0 ? 'bg-amber-100 dark:bg-amber-900/30' : 'bg-green-100 dark:bg-green-900/30' }} rounded-xl group-hover:scale-110 transition-transform duration-300">
                    @if($this->stats['low_stock_items'] > 0)
                        <x-flux::icon.exclamation-triangle class="h-6 w-6 text-amber-600 dark:text-amber-400" />
                    @else
                        <x-flux::icon.check-circle class="h-6 w-6 text-green-600 dark:text-green-400" />
                    @endif
                </div>
            </div>
        </div>

        <!-- Out of Stock -->
        <div class="group bg-gradient-to-br from-white to-stone-50 dark:from-stone-800 dark:to-stone-900 rounded-xl shadow-lg border border-stone-200/50 dark:border-stone-700/50 p-4 sm:p-5 hover:shadow-xl hover:-translate-y-1 transition-all duration-300 backdrop-blur-sm">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs sm:text-sm font-semibold text-stone-500 dark:text-stone-400 uppercase tracking-wider">Out of Stock</p>
                    <p class="text-xl sm:text-2xl font-bold text-stone-900 dark:text-stone-100 mt-1">{{ number_format($this->stats['out_of_stock_items']) }}</p>
                    <div class="flex items-center mt-1">
                        <span class="text-xs {{ $this->stats['out_of_stock_items'] > 0 ? 'text-red-600 dark:text-red-400' : 'text-stone-500 dark:text-stone-400' }}">
                            {{ $this->stats['out_of_stock_items'] > 0 ? 'Immediate attention needed' : 'No items out of stock' }}
                        </span>
                    </div>
                </div>
                <div class="flex-shrink-0 p-3 {{ $this->stats['out_of_stock_items'] > 0 ? 'bg-red-100 dark:bg-red-900/30' : 'bg-green-100 dark:bg-green-900/30' }} rounded-xl group-hover:scale-110 transition-transform duration-300">
                    @if($this->stats['out_of_stock_items'] > 0)
                        <x-flux::icon.x-circle class="h-6 w-6 text-red-600 dark:text-red-400" />
                    @else
                        <x-flux::icon.check-circle class="h-6 w-6 text-green-600 dark:text-green-400" />
                    @endif
                </div>
            </div>
        </div>

        <!-- Total Value -->
        <div class="group bg-gradient-to-br from-white to-stone-50 dark:from-stone-800 dark:to-stone-900 rounded-xl shadow-lg border border-stone-200/50 dark:border-stone-700/50 p-4 sm:p-5 hover:shadow-xl hover:-translate-y-1 transition-all duration-300 backdrop-blur-sm">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs sm:text-sm font-semibold text-stone-500 dark:text-stone-400 uppercase tracking-wider">Total Value</p>
                    <p class="text-xl sm:text-2xl font-bold text-stone-900 dark:text-stone-100 mt-1">₱{{ number_format($this->stats['total_value'], 2) }}</p>
                    <div class="flex items-center mt-1">
                        <span class="text-xs text-stone-500 dark:text-stone-400">{{ $this->division->name }}</span>
                    </div>
                </div>
                <div class="flex-shrink-0 p-3 bg-green-100 dark:bg-green-900/30 rounded-xl group-hover:scale-110 transition-transform duration-300">
                    <x-flux::icon.chart-bar class="h-6 w-6 text-green-600 dark:text-green-400" />
                </div>
            </div>
        </div>
    </div>

    <!-- Inventory Alerts -->
    <div class="bg-white dark:bg-stone-800/50 rounded-lg shadow-sm border border-stone-200 dark:border-stone-700/60 overflow-hidden transition-all duration-300">
        @php
            $totalActiveAlerts = ($this->stats['low_stock_items'] > 0 ? 1 : 0) + ($this->stats['out_of_stock_items'] > 0 ? 1 : 0);
        @endphp

        <!-- Alert Header -->
        <div class="p-3 sm:p-4 border-b border-stone-200 dark:border-stone-700">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <div class="flex-shrink-0">
                        @if($totalActiveAlerts > 0)
                            <div class="relative">
                                <x-flux::icon.exclamation-triangle class="h-5 w-5 text-amber-500" />
                                <div class="absolute -top-2 -right-2 h-4 w-4 bg-red-500 text-white text-xs rounded-full flex items-center justify-center font-bold">{{ $totalActiveAlerts }}</div>
                            </div>
                        @else
                            <x-flux::icon.check-circle class="h-5 w-5 text-green-500" />
                        @endif
                    </div>
                    <div>
                        <h2 class="text-base sm:text-lg font-semibold text-stone-900 dark:text-stone-100">
                            Inventory Alerts
                        </h2>
                        <p class="text-xs sm:text-sm text-stone-500 dark:text-stone-400">
                            @if($totalActiveAlerts > 0)
                                {{ $totalActiveAlerts }} {{ Str::plural('issue', $totalActiveAlerts) }} requiring attention
                            @else
                                All items properly stocked
                            @endif
                        </p>
                    </div>
                </div>
            </div>
        </div>

        @if($totalActiveAlerts == 0)
            <!-- No Alerts State -->
            <div class="p-6 sm:p-8 text-center">
                <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-green-100 dark:bg-green-900/20 mb-4">
                    <x-flux::icon.check-circle class="h-6 w-6 text-green-600 dark:text-green-400" />
                </div>
                <h3 class="text-sm font-medium text-stone-900 dark:text-stone-100 mb-1">All Clear!</h3>
                <p class="text-sm text-stone-500 dark:text-stone-400">Your inventory is well-stocked with no urgent issues.</p>
            </div>
        @else
            <!-- Alert Items -->
            <div class="p-4 space-y-3">
                @if($this->stats['out_of_stock_items'] > 0)
                    <div class="flex items-start space-x-3 p-3 rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800/50">
                        <div class="flex-shrink-0 mt-0.5">
                            <x-flux::icon.x-circle class="h-5 w-5 text-red-600 dark:text-red-400" />
                        </div>
                        <div class="flex-1 min-w-0">
                            <h4 class="text-sm font-semibold text-red-900 dark:text-red-100">Out of Stock Alert</h4>
                            <p class="text-sm text-red-700 dark:text-red-300">{{ $this->stats['out_of_stock_items'] }} items are completely out of stock</p>
                        </div>
                        <div class="flex-shrink-0">
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900/50 dark:text-red-200">
                                Critical
                            </span>
                        </div>
                    </div>
                @endif

                @if($this->stats['low_stock_items'] > 0)
                    <div class="flex items-start space-x-3 p-3 rounded-lg bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800/50">
                        <div class="flex-shrink-0 mt-0.5">
                            <x-flux::icon.exclamation-triangle class="h-5 w-5 text-amber-600 dark:text-amber-400" />
                        </div>
                        <div class="flex-1 min-w-0">
                            <h4 class="text-sm font-semibold text-amber-900 dark:text-amber-100">Low Stock Alert</h4>
                            <p class="text-sm text-amber-700 dark:text-amber-300">{{ $this->stats['low_stock_items'] }} items are running low and need restocking</p>
                        </div>
                        <div class="flex-shrink-0">
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-amber-100 text-amber-800 dark:bg-amber-900/50 dark:text-amber-200">
                                Warning
                            </span>
                        </div>
                    </div>
                @endif
            </div>
        @endif
            </div>

    <!-- Enhanced Quick Actions -->
    <div class="mt-8 grid grid-cols-1 gap-4 sm:gap-5 md:gap-6 sm:grid-cols-2 lg:grid-cols-3">
        @foreach($this->quickActions as $action)
            <a 
                href="{{ route($action['route']) }}" 
                wire:navigate
                class="group relative bg-gradient-to-br from-white to-stone-50 dark:from-stone-800 dark:to-stone-900 rounded-xl shadow-lg border border-stone-200/50 dark:border-stone-700/50 p-6 hover:shadow-xl hover:-translate-y-2 transition-all duration-300 backdrop-blur-sm overflow-hidden"
            >
                <div class="flex items-start justify-between">
                    <div class="flex-1">
                        <h3 class="text-lg font-semibold text-stone-900 dark:text-stone-100 group-hover:text-accent transition-colors">
                            {{ $action['title'] }}
                        </h3>
                        <p class="mt-2 text-sm text-stone-600 dark:text-stone-400">
                            {{ $action['subtitle'] }}
                        </p>
                    </div>
                    <div class="flex-shrink-0 ml-4">
                        <div class="flex items-center justify-center w-12 h-12 rounded-xl bg-accent/10 text-accent group-hover:bg-accent group-hover:text-white transition-all duration-300 group-hover:scale-110">
                            @if($action['icon'] === 'plus')
                                <x-flux::icon.plus-circle class="w-6 h-6" />
                            @elseif($action['icon'] === 'boxes')
                                <x-flux::icon.boxes class="w-6 h-6" />
                            @elseif($action['icon'] === 'chart-bar')
                                <x-flux::icon.chart-bar class="w-6 h-6" />
                            @else
                                <x-flux::icon.box class="w-6 h-6" />
                            @endif
                        </div>
                    </div>
                </div>
                
                <!-- Hover effect overlay -->
                <div class="absolute inset-0 rounded-xl bg-gradient-to-r from-transparent via-white/20 to-transparent opacity-0 group-hover:opacity-100 group-hover:animate-pulse transition-opacity duration-500 pointer-events-none"></div>
            </a>
        @endforeach
        </div>

    <div class="mt-8 grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Recent Consumable Records -->
        <div class="bg-gradient-to-br from-white to-stone-50 dark:from-stone-800 dark:to-stone-900 rounded-xl shadow-lg border border-stone-200/50 dark:border-stone-700/50 backdrop-blur-sm">
            <div class="p-4 sm:p-6 lg:p-8">
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h3 class="text-lg sm:text-xl font-bold text-stone-900 dark:text-stone-100 flex items-center">
                            <div class="w-2 h-6 bg-gradient-to-b from-blue-500 to-blue-600 rounded-full mr-3"></div>
                            Recent Consumable Records
                        </h3>
                        <p class="text-sm text-stone-500 dark:text-stone-400 mt-1 ml-5">Latest consumable records added to your division</p>
                    </div>
                    <div class="flex items-center px-3 py-1.5 bg-blue-100 dark:bg-blue-900/30 rounded-full text-xs text-blue-700 dark:text-blue-300">
                        <x-flux::icon.document-text class="h-4 w-4 mr-1" />
                        <span class="hidden sm:inline font-medium">{{ $this->recentConsumableRecords->count() }} Records</span>
                    </div>
                </div>
                
                <div class="overflow-hidden rounded-lg border border-stone-200/50 dark:border-stone-700/50 shadow-sm">
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
                </div>
            </div>

            <!-- Low Stock Alert -->
        <div class="bg-gradient-to-br from-white to-stone-50 dark:from-stone-800 dark:to-stone-900 rounded-xl shadow-lg border border-stone-200/50 dark:border-stone-700/50 backdrop-blur-sm">
            <div class="p-4 sm:p-6 lg:p-8">
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h3 class="text-lg sm:text-xl font-bold text-stone-900 dark:text-stone-100 flex items-center">
                            <div class="w-2 h-6 bg-gradient-to-b from-amber-500 to-amber-600 rounded-full mr-3"></div>
                            Low Stock Alert
                        </h3>
                        <p class="text-sm text-stone-500 dark:text-stone-400 mt-1 ml-5">Items that need restocking soon</p>
                    </div>
                    <div class="flex items-center px-3 py-1.5 bg-amber-100 dark:bg-amber-900/30 rounded-full text-xs text-amber-700 dark:text-amber-300">
                        <x-flux::icon.exclamation-triangle class="h-4 w-4 mr-1" />
                        <span class="hidden sm:inline font-medium">{{ $this->lowStockItemsList->count() }} Items</span>
                    </div>
                </div>
                
                <div class="overflow-hidden rounded-lg border border-stone-200/50 dark:border-stone-700/50 shadow-sm">
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
        </div>
</div> 
<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use App\Models\ConsumableRecord;

new #[Layout('components.layouts.app')] class extends Component
{
    public ConsumableRecord $record;

    public function mount(ConsumableRecord $record)
    {
        // Check admin permissions
        if (!auth()->user()->hasAdminPermission('view_inventory')) {
            abort(403, 'Unauthorized access.');
        }
        
        $this->record = $record->load(['items.specification.itemCatalog', 'division']);
    }
    
    public function getTotalValue()
    {
        return $this->record->items->sum(function ($item) {
            $contractItem = $item->specification->contractItems()->first();
            return $contractItem ? $item->current_quantity * $contractItem->unit_price : 0;
        });
    }
    
    public function getStockStatus()
    {
        $lowStock = $this->record->items->filter(function ($item) {
            return $item->current_quantity <= ($item->initial_quantity * 0.2) && $item->current_quantity > 0;
        })->count();
        $outOfStock = $this->record->items->where('current_quantity', 0)->count();

        if ($outOfStock > 0) {
            return ['status' => 'Out of Stock', 'color' => 'red'];
        } elseif ($lowStock > 0) {
            return ['status' => 'Low Stock', 'color' => 'amber'];
        } else {
            return ['status' => 'Good Stock', 'color' => 'green'];
        }
    }
}

?>

<div class="py-6">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 md:px-8">
        <!-- Breadcrumbs -->
        <div class="flex items-center justify-between mb-4">
            <div>
                <flux:breadcrumbs class="text-2xl font-semibold">
                    <flux:breadcrumbs.item :href="route('admin.dashboard')" wire:navigate icon="home" class="text-xl sm:text-2xl font-semibold text-stone-700 dark:text-stone-300" />
                    <flux:breadcrumbs.item class="text-xl sm:text-2xl font-semibold text-stone-500 dark:text-stone-400">Inventory</flux:breadcrumbs.item>
                    <flux:breadcrumbs.item :href="route('admin.inventory.consumables.index')" wire:navigate class="text-xl sm:text-2xl font-semibold text-stone-500 dark:text-stone-400">Consumables</flux:breadcrumbs.item>
                    <flux:breadcrumbs.item class="text-xl sm:text-2xl font-semibold text-stone-900 dark:text-stone-100">{{ $record->record_number }}</flux:breadcrumbs.item>
                </flux:breadcrumbs>
            </div>
        </div>

        <!-- Header -->
        <div class="mb-8">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold leading-7 text-stone-900 dark:text-stone-100 sm:truncate sm:text-3xl sm:tracking-tight">
                        {{ $record->record_number }}
                    </h1>
                    <div class="mt-1 flex flex-col sm:mt-0 sm:flex-row sm:flex-wrap sm:space-x-6">
                        <div class="mt-2 flex items-center text-sm text-stone-500">
                            <x-flux::icon.building-office class="mr-1.5 h-5 w-5 flex-shrink-0 text-stone-400" />
                            {{ $record->division->name }}
                        </div>
                        <div class="mt-2 flex items-center text-sm text-stone-500">
                            <x-flux::icon.calendar class="mr-1.5 h-5 w-5 flex-shrink-0 text-stone-400" />
                            {{ $record->date_received->format('M d, Y') }}
                        </div>
                        @php
                            $stockStatus = $this->getStockStatus();
                        @endphp
                        <div class="mt-2 flex items-center">
                            <flux:badge color="{{ $stockStatus['color'] }}" size="sm">
                                {{ $stockStatus['status'] }}
                            </flux:badge>
                        </div>
                    </div>
                </div>
                <div class="flex space-x-2">
                    <flux:button 
                        variant="filled" 
                        :href="route('admin.inventory.consumables.edit', $record)" 
                        wire:navigate>
                        <x-flux::icon.edit class="mr-1.5 h-4 w-4" />
                        Edit Record
                    </flux:button>
                    <flux:button 
                        variant="ghost" 
                        :href="route('admin.inventory.consumables.details')" 
                        wire:navigate>
                        <x-flux::icon.arrow-left class="mr-1.5 h-4 w-4" />
                        Back to List
                    </flux:button>
                </div>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4 mb-8">
            <div class="overflow-hidden rounded-lg bg-white px-4 py-5 shadow sm:p-6 dark:bg-stone-800">
                <dt class="truncate text-sm font-medium text-stone-500 dark:text-stone-400">Total Items</dt>
                <dd class="mt-1 text-3xl font-semibold tracking-tight text-stone-900 dark:text-stone-100">{{ $record->items->count() }}</dd>
            </div>
            
            <div class="overflow-hidden rounded-lg bg-white px-4 py-5 shadow sm:p-6 dark:bg-stone-800">
                <dt class="truncate text-sm font-medium text-stone-500 dark:text-stone-400">Total Current Quantity</dt>
                <dd class="mt-1 text-3xl font-semibold tracking-tight text-stone-900 dark:text-stone-100">{{ $record->items->sum('current_quantity') }}</dd>
            </div>
            
            <div class="overflow-hidden rounded-lg bg-white px-4 py-5 shadow sm:p-6 dark:bg-stone-800">
                <dt class="truncate text-sm font-medium text-stone-500 dark:text-stone-400">Total Initial Quantity</dt>
                <dd class="mt-1 text-3xl font-semibold tracking-tight text-stone-900 dark:text-stone-100">{{ $record->items->sum('initial_quantity') }}</dd>
            </div>
            
            <div class="overflow-hidden rounded-lg bg-white px-4 py-5 shadow sm:p-6 dark:bg-stone-800">
                <dt class="truncate text-sm font-medium text-stone-500 dark:text-stone-400">Estimated Value</dt>
                <dd class="mt-1 text-3xl font-semibold tracking-tight text-stone-900 dark:text-stone-100">₱{{ number_format($this->getTotalValue(), 2) }}</dd>
            </div>
        </div>

        <!-- Record Details -->
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <!-- Record Information -->
            <div class="lg:col-span-1">
                <div class="overflow-hidden rounded-lg bg-white shadow dark:bg-stone-800">
                    <div class="px-4 py-5 sm:p-6">
                        <h3 class="text-base font-semibold leading-6 text-stone-900 dark:text-stone-100">Record Information</h3>
                        <div class="mt-5 border-t border-stone-200 dark:border-stone-700">
                            <dl class="divide-y divide-stone-200 dark:divide-stone-700">
                                <div class="py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:py-5">
                                    <dt class="text-sm font-medium text-stone-500 dark:text-stone-400">Record Number</dt>
                                    <dd class="mt-1 text-sm text-stone-900 sm:col-span-2 sm:mt-0 dark:text-stone-100">{{ $record->record_number }}</dd>
                                </div>
                                <div class="py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:py-5">
                                    <dt class="text-sm font-medium text-stone-500 dark:text-stone-400">Division</dt>
                                    <dd class="mt-1 text-sm text-stone-900 sm:col-span-2 sm:mt-0 dark:text-stone-100">{{ $record->division->name }}</dd>
                                </div>
                                <div class="py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:py-5">
                                    <dt class="text-sm font-medium text-stone-500 dark:text-stone-400">Date Received</dt>
                                    <dd class="mt-1 text-sm text-stone-900 sm:col-span-2 sm:mt-0 dark:text-stone-100">{{ $record->date_received->format('F d, Y') }}</dd>
                                </div>
                                @if($record->remarks)
                                <div class="py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:py-5">
                                    <dt class="text-sm font-medium text-stone-500 dark:text-stone-400">Remarks</dt>
                                    <dd class="mt-1 text-sm text-stone-900 sm:col-span-2 sm:mt-0 dark:text-stone-100">{{ $record->remarks }}</dd>
                                </div>
                                @endif
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Items List -->
            <div class="lg:col-span-2">
                <div class="overflow-hidden rounded-lg bg-white shadow dark:bg-stone-800">
                    <div class="px-4 py-5 sm:p-6">
                        <h3 class="text-base font-semibold leading-6 text-stone-900 dark:text-stone-100">Items in this Record</h3>
                        <div class="mt-5">
                            <div class="flow-root">
                                <div class="-mx-4 -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
                                    <div class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">
                                        <table class="min-w-full divide-y divide-stone-300 dark:divide-stone-700">
                                            <thead>
                                                <tr>
                                                    <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-stone-900 sm:pl-0 dark:text-stone-100">Item</th>
                                                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-stone-900 dark:text-stone-100">Brand/Model</th>
                                                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-stone-900 dark:text-stone-100">Initial Qty</th>
                                                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-stone-900 dark:text-stone-100">Current Qty</th>
                                                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-stone-900 dark:text-stone-100">Unit</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-stone-200 dark:divide-stone-800">
                                                @foreach($record->items as $item)
                                                <tr>
                                                    <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm sm:pl-0">
                                                        <div class="font-medium text-stone-900 dark:text-stone-100">{{ $item->specification->itemCatalog->name }}</div>
                                                        @if($item->specification->detailed_specifications)
                                                        <div class="text-stone-500 dark:text-stone-400">{{ Str::limit($item->specification->detailed_specifications, 50) }}</div>
                                                        @endif
                                                    </td>
                                                    <td class="whitespace-nowrap px-3 py-4 text-sm text-stone-500 dark:text-stone-400">
                                                        {{ collect([$item->specification->brand, $item->specification->model])->filter()->join(' / ') ?: 'N/A' }}
                                                    </td>
                                                    <td class="whitespace-nowrap px-3 py-4 text-sm text-stone-900 dark:text-stone-100">{{ $item->initial_quantity }}</td>
                                                    <td class="whitespace-nowrap px-3 py-4 text-sm">
                                                        @if($item->current_quantity == 0)
                                                        <span class="inline-flex items-center rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-medium text-red-800 dark:bg-red-900/30 dark:text-red-300">
                                                            {{ $item->current_quantity }}
                                                        </span>
                                                        @elseif($item->current_quantity <= ($item->initial_quantity * 0.2))
                                                        <span class="inline-flex items-center rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-medium text-amber-800 dark:bg-amber-900/30 dark:text-amber-300">
                                                            {{ $item->current_quantity }}
                                                        </span>
                                                        @else
                                                        <span class="text-stone-900 dark:text-stone-100">{{ $item->current_quantity }}</span>
                                                        @endif
                                                    </td>
                                                    <td class="whitespace-nowrap px-3 py-4 text-sm text-stone-500 dark:text-stone-400">{{ $item->specification->itemCatalog->unit }}</td>
                                                </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div> 
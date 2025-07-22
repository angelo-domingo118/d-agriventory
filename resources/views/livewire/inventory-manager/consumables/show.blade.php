<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use App\Models\ConsumableRecord;

new #[Layout('components.layouts.app')] class extends Component
{
    public ConsumableRecord $record;
    public $division;

    public function mount(ConsumableRecord $record)
    {
        $user = auth()->user()->load('divisionInventoryManager.division');
        $this->division = $user->divisionInventoryManager->division;
        
        // Ensure the record belongs to the user's division
        if ($record->division_id !== $this->division->id) {
            abort(403, 'Unauthorized access to this consumable record.');
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

<div>
    <x-inventory-manager.layout 
        :heading="'Consumable Record: ' . $record->record_number"
        :subheading="'Division: ' . $record->division->name">
        
        <x-slot name="header">
            <div class="flex space-x-3">
                <flux:button 
                    variant="ghost"
                    :href="route('inventory-manager.consumables.index')" 
                    wire:navigate>
                    ← Back to List
                </flux:button>
                
                <flux:button 
                    variant="primary"
                    :href="route('inventory-manager.consumables.edit', $record)" 
                    wire:navigate>
                    Edit Record
                </flux:button>
            </div>
        </x-slot>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Record Details -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Record Information -->
                <div class="bg-white dark:bg-stone-800 shadow-sm rounded-lg p-6">
                    <h3 class="text-lg font-medium text-stone-800 dark:text-stone-200 mb-4">Record Information</h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-stone-600 dark:text-stone-400">Record Number</label>
                            <p class="mt-1 text-lg font-semibold text-stone-900 dark:text-stone-100">{{ $record->record_number }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-stone-600 dark:text-stone-400">Date Received</label>
                            <p class="mt-1 text-lg text-stone-900 dark:text-stone-100">{{ $record->date_received->format('M d, Y') }}</p>
                        </div>
                        <div class="sm:col-span-2">
                            <label class="block text-sm font-medium text-stone-600 dark:text-stone-400">Remarks</label>
                            <p class="mt-1 text-stone-900 dark:text-stone-100">{{ $record->remarks ?: 'No remarks' }}</p>
                        </div>
                    </div>
                </div>
                
                <!-- Items List -->
                <div class="bg-white dark:bg-stone-800 shadow-sm rounded-lg">
                    <div class="px-6 py-4 border-b border-stone-200 dark:border-stone-700">
                        <h3 class="text-lg font-medium text-stone-800 dark:text-stone-200">Items ({{ $record->items->count() }})</h3>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-stone-200 dark:divide-stone-700">
                            <thead class="bg-stone-50 dark:bg-stone-700">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-stone-500 dark:text-stone-300 uppercase tracking-wider">
                                        Item
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-stone-500 dark:text-stone-300 uppercase tracking-wider">
                                        Brand/Model
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-stone-500 dark:text-stone-300 uppercase tracking-wider">
                                        Initial Qty
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-stone-500 dark:text-stone-300 uppercase tracking-wider">
                                        Current Qty
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-stone-500 dark:text-stone-300 uppercase tracking-wider">
                                        Status
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-stone-800 divide-y divide-stone-200 dark:divide-stone-700">
                                @foreach($record->items as $item)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-stone-900 dark:text-stone-100">
                                            {{ $item->specification->itemCatalog->name }}
                                        </div>
                                        <div class="text-sm text-stone-500 dark:text-stone-400">
                                            {{ $item->specification->itemCatalog->unit }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-stone-900 dark:text-stone-100">
                                            {{ $item->specification->brand ?? 'Generic' }}
                                        </div>
                                        @if($item->specification->model)
                                        <div class="text-sm text-stone-500 dark:text-stone-400">
                                            {{ $item->specification->model }}
                                        </div>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-stone-900 dark:text-stone-100">
                                        {{ number_format($item->initial_quantity) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-stone-900 dark:text-stone-100">
                                        {{ number_format($item->current_quantity) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @if($item->current_quantity == 0)
                                            <span class="px-2 py-1 text-xs font-medium rounded-full bg-red-100 text-red-800 dark:bg-red-800 dark:text-red-100">
                                                Out of Stock
                                            </span>
                                        @elseif($item->current_quantity <= ($item->initial_quantity * 0.2))
                                            <span class="px-2 py-1 text-xs font-medium rounded-full bg-amber-100 text-amber-800 dark:bg-amber-800 dark:text-amber-100">
                                                Low Stock
                                            </span>
                                        @else
                                            <span class="px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100">
                                                Good Stock
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <!-- Summary Sidebar -->
            <div class="space-y-6">
                <!-- Status Card -->
                <div class="bg-white dark:bg-stone-800 shadow-sm rounded-lg p-6">
                    <h3 class="text-lg font-medium text-stone-800 dark:text-stone-200 mb-4">Status</h3>
                    @php $status = $this->getStockStatus(); @endphp
                    <div class="text-center">
                        <span class="px-3 py-2 text-sm font-medium rounded-full
                            @if($status['color'] === 'green') bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100
                            @elseif($status['color'] === 'amber') bg-amber-100 text-amber-800 dark:bg-amber-800 dark:text-amber-100  
                            @else bg-red-100 text-red-800 dark:bg-red-800 dark:text-red-100 @endif">
                            {{ $status['status'] }}
                        </span>
                    </div>
                </div>
                
                <!-- Stats -->
                <div class="bg-white dark:bg-stone-800 shadow-sm rounded-lg p-6">
                    <h3 class="text-lg font-medium text-stone-800 dark:text-stone-200 mb-4">Statistics</h3>
                    <div class="space-y-4">
                        <div class="flex justify-between">
                            <span class="text-sm text-stone-600 dark:text-stone-400">Total Items:</span>
                            <span class="font-medium text-stone-900 dark:text-stone-100">{{ $record->items->count() }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-sm text-stone-600 dark:text-stone-400">Items in Stock:</span>
                            <span class="font-medium text-stone-900 dark:text-stone-100">{{ $record->items->where('current_quantity', '>', 0)->count() }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-sm text-stone-600 dark:text-stone-400">Out of Stock:</span>
                            <span class="font-medium text-red-600">{{ $record->items->where('current_quantity', 0)->count() }}</span>
                        </div>
                        <div class="border-t border-stone-200 dark:border-stone-700 pt-4">
                            <div class="flex justify-between">
                                <span class="text-sm text-stone-600 dark:text-stone-400">Est. Total Value:</span>
                                <span class="font-semibold text-green-600">₱{{ number_format($this->getTotalValue(), 2) }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </x-inventory-manager.layout>
</div>

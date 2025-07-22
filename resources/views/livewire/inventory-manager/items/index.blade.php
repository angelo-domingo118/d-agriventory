<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use App\Models\ConsumableItem;
use Livewire\WithPagination;

new #[Layout('components.layouts.app')] class extends Component
{
    use WithPagination;
    
    public $division;

    public function mount()
    {
        $user = auth()->user()->load('divisionInventoryManager.division');
        $this->division = $user->divisionInventoryManager->division;
    }
    
    public function getItems()
    {
        return ConsumableItem::whereHas('record', function ($query) {
            $query->where('division_id', $this->division->id);
        })
        ->with(['specification.itemCatalog', 'record'])
        ->paginate(10);
    }
    
    public function getTableHeaders(): array
    {
        return ['Name', 'Brand/Model', 'Record', 'Initial Stock', 'Current Stock', 'Status'];
    }
    
    public function getItemStatus($item): array
    {
        if ($item->current_quantity == 0) {
            return ['status' => 'Out of Stock', 'color' => 'red'];
        } elseif ($item->current_quantity <= ($item->initial_quantity * 0.2)) {
            return ['status' => 'Low Stock', 'color' => 'amber'];
        } else {
            return ['status' => 'In Stock', 'color' => 'green'];
        }
    }
}

?>

<div>
    <x-inventory-manager.layout heading="Items Management" :subheading="'Manage inventory items for ' . $this->division->name">
        
        <!-- Header Actions -->
        <div class="border-b border-stone-200 pb-4 dark:border-stone-700 mb-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-semibold text-stone-900 dark:text-stone-100">
                        Inventory Items
                    </h1>
                    <p class="mt-1 text-sm text-stone-600 dark:text-stone-400">
                        Overview of all consumable items in your division
                    </p>
                </div>
                <div class="flex items-center gap-x-4">
                    <flux:button 
                        variant="primary" 
                        :href="route('inventory-manager.consumables.create')" 
                        wire:navigate>
                        <flux:icon.plus class="w-4 h-4 mr-2" />
                        Add New Item
                    </flux:button>
                </div>
            </div>
        </div>
        
        <div class="overflow-hidden rounded-lg border border-stone-200 bg-white shadow-sm dark:border-stone-700 dark:bg-stone-800">
            <div class="border-b border-stone-200 px-6 py-4 dark:border-stone-700">
                <h3 class="text-lg font-medium text-stone-800 dark:text-stone-200">All Items</h3>
                <p class="mt-1 text-sm text-stone-500 dark:text-stone-400">Complete inventory of consumable items</p>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-stone-200 dark:divide-stone-700">
                    <thead class="bg-stone-50 dark:bg-stone-700">
                        <tr>
                            @foreach($this->getTableHeaders() as $header)
                            <th class="px-6 py-3 text-left text-xs font-medium text-stone-500 dark:text-stone-300 uppercase tracking-wider">
                                {{ $header }}
                            </th>
                            @endforeach
                            <th class="px-6 py-3 text-right text-xs font-medium text-stone-500 dark:text-stone-300 uppercase tracking-wider">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-stone-800 divide-y divide-stone-200 dark:divide-stone-700">
                        @forelse($this->getItems() as $item)
                            @php $status = $this->getItemStatus($item); @endphp
                            <tr class="hover:bg-stone-50 dark:hover:bg-stone-700">
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
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-stone-900 dark:text-stone-100">
                                        {{ $item->record->record_number }}
                                    </div>
                                    <div class="text-sm text-stone-500 dark:text-stone-400">
                                        {{ $item->record->date_received->format('M d, Y') }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-stone-900 dark:text-stone-100">
                                    {{ number_format($item->initial_quantity) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-stone-900 dark:text-stone-100">
                                    {{ number_format($item->current_quantity) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 py-1 text-xs font-medium rounded-full
                                        @if($status['color'] === 'green') bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100
                                        @elseif($status['color'] === 'amber') bg-amber-100 text-amber-800 dark:bg-amber-800 dark:text-amber-100  
                                        @else bg-red-100 text-red-800 dark:bg-red-800 dark:text-red-100 @endif">
                                        {{ $status['status'] }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <div class="flex justify-end space-x-2">
                                        <flux:button 
                                            variant="ghost" 
                                            size="sm"
                                            :href="route('inventory-manager.consumables.show', $item->record)" 
                                            wire:navigate>
                                            View Record
                                        </flux:button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-12 text-center text-stone-500 dark:text-stone-400">
                                    <div class="flex flex-col items-center">
                                        <svg class="w-12 h-12 mb-4 text-stone-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                        </svg>
                                        <p class="text-lg font-medium">No items found</p>
                                        <p class="text-sm">Start by adding consumable records to your division.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            @if($this->getItems()->hasPages())
                <div class="px-6 py-4 border-t border-stone-200 dark:border-stone-700">
                    {{ $this->getItems()->links() }}
                </div>
            @endif
        </div>
    </x-inventory-manager.layout>
</div>
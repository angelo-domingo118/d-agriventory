<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\WithPagination;
use App\Models\ConsumableRecord;
use App\Models\ConsumableItem;
use Livewire\Attributes\Url;

new #[Layout('components.layouts.app')] class extends Component
{
    use WithPagination;

    public $division;
    
    #[Url]
    public $search = '';
    
    #[Url] 
    public $sortField = 'date_received';
    
    #[Url]
    public $sortDirection = 'desc';

    public function mount()
    {
        $user = auth()->user()->load('divisionInventoryManager.division');
        $this->division = $user->divisionInventoryManager->division;
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
        $this->resetPage();
    }

    public function getRecords()
    {
        return ConsumableRecord::where('division_id', $this->division->id)
            ->with(['items.specification.itemCatalog'])
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('record_number', 'like', '%' . $this->search . '%')
                      ->orWhere('remarks', 'like', '%' . $this->search . '%')
                      ->orWhereHas('items.specification.itemCatalog', function ($sq) {
                          $sq->where('name', 'like', '%' . $this->search . '%');
                      });
                });
            })
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate(10);
    }

    public function getStockStatus($record)
    {
        $totalItems = $record->items->count();
        $lowStock = $record->items->filter(function ($item) {
            return $item->current_quantity <= ($item->initial_quantity * 0.2) && $item->current_quantity > 0;
        })->count();
        $outOfStock = $record->items->where('current_quantity', 0)->count();

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
    <x-inventory-manager.layout heading="Consumables Management" :subheading="'Manage consumable inventory for ' . $this->division->name">
        
        <!-- Header Actions -->
        <div class="border-b border-stone-200 pb-4 dark:border-stone-700 mb-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-semibold text-stone-900 dark:text-stone-100">
                        Consumables Inventory
                    </h1>
                    <p class="mt-1 text-sm text-stone-600 dark:text-stone-400">
                        Track and manage consumable items for your division
                    </p>
                </div>
                <div class="flex items-center gap-x-4">
                    <flux:button 
                        variant="primary" 
                        :href="route('inventory-manager.consumables.create')" 
                        wire:navigate>
                        <flux:icon.plus class="w-4 h-4 mr-2" />
                        Add New Record
                    </flux:button>
                </div>
            </div>
        </div>

        <!-- Search and Filters -->
        <div class="overflow-hidden rounded-lg border border-stone-200 bg-white shadow-sm dark:border-stone-700 dark:bg-stone-800 mb-6">
            <div class="border-b border-stone-200 px-6 py-4 dark:border-stone-700">
                <h3 class="text-lg font-medium text-stone-800 dark:text-stone-200">Search & Filters</h3>
            </div>
            <div class="p-6">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between space-y-4 md:space-y-0 md:space-x-6">
                    <div class="flex-1 max-w-md">
                        <flux:input 
                            wire:model.live.debounce.300ms="search" 
                            placeholder="Search records, items, or remarks..."
                            icon="magnifying-glass"
                        />
                    </div>
                    <div class="flex items-center space-x-4">
                        <div class="text-sm text-stone-600 dark:text-stone-400">
                            <span class="font-medium">{{ $this->getRecords()->total() }}</span> records found
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Records Table -->
        <div class="overflow-hidden rounded-lg border border-stone-200 bg-white shadow-sm dark:border-stone-700 dark:bg-stone-800">
            <div class="border-b border-stone-200 px-6 py-4 dark:border-stone-700">
                <h3 class="text-lg font-medium text-stone-800 dark:text-stone-200">Consumable Records</h3>
                <p class="mt-1 text-sm text-stone-500 dark:text-stone-400">All consumable records for {{ $this->division->name }}</p>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-stone-200 dark:divide-stone-700">
                    <thead class="bg-stone-50 dark:bg-stone-700">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-stone-500 dark:text-stone-300 uppercase tracking-wider cursor-pointer"
                                wire:click="sortBy('record_number')">
                                <div class="flex items-center space-x-1">
                                    <span>Record Number</span>
                                    @if($sortField === 'record_number')
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                            @if($sortDirection === 'asc')
                                                <path fill-rule="evenodd" d="M14.707 12.707a1 1 0 01-1.414 0L10 9.414l-3.293 3.293a1 1 0 01-1.414-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 010 1.414z" clip-rule="evenodd"/>
                                            @else
                                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/>
                                            @endif
                                        </svg>
                                    @endif
                                </div>
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-stone-500 dark:text-stone-300 uppercase tracking-wider cursor-pointer"
                                wire:click="sortBy('date_received')">
                                <div class="flex items-center space-x-1">
                                    <span>Date Received</span>
                                    @if($sortField === 'date_received')
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                            @if($sortDirection === 'asc')
                                                <path fill-rule="evenodd" d="M14.707 12.707a1 1 0 01-1.414 0L10 9.414l-3.293 3.293a1 1 0 01-1.414-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 010 1.414z" clip-rule="evenodd"/>
                                            @else
                                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/>
                                            @endif
                                        </svg>
                                    @endif
                                </div>
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-stone-500 dark:text-stone-300 uppercase tracking-wider">
                                Items
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-stone-500 dark:text-stone-300 uppercase tracking-wider">
                                Status
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-stone-500 dark:text-stone-300 uppercase tracking-wider">
                                Remarks
                            </th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-stone-500 dark:text-stone-300 uppercase tracking-wider">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-stone-800 divide-y divide-stone-200 dark:divide-stone-700">
                        @forelse($this->getRecords() as $record)
                            @php $status = $this->getStockStatus($record); @endphp
                            <tr class="hover:bg-stone-50 dark:hover:bg-stone-700">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-stone-900 dark:text-stone-100">
                                        {{ $record->record_number }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-stone-900 dark:text-stone-100">
                                        {{ $record->date_received->format('M d, Y') }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-stone-900 dark:text-stone-100">
                                        {{ $record->items->count() }} items
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 py-1 text-xs font-medium rounded-full
                                        @if($status['color'] === 'green') bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100
                                        @elseif($status['color'] === 'amber') bg-amber-100 text-amber-800 dark:bg-amber-800 dark:text-amber-100  
                                        @else bg-red-100 text-red-800 dark:bg-red-800 dark:text-red-100 @endif">
                                        {{ $status['status'] }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-stone-600 dark:text-stone-300 max-w-xs truncate">
                                        {{ $record->remarks ?? 'No remarks' }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <div class="flex justify-end space-x-2">
                                        <flux:button 
                                            variant="ghost" 
                                            size="sm"
                                            :href="route('inventory-manager.consumables.show', $record)" 
                                            wire:navigate>
                                            View
                                        </flux:button>
                                        <flux:button 
                                            variant="ghost" 
                                            size="sm"
                                            :href="route('inventory-manager.consumables.edit', $record)" 
                                            wire:navigate>
                                            Edit
                                        </flux:button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center text-stone-500 dark:text-stone-400">
                                    <div class="flex flex-col items-center">
                                        <svg class="w-12 h-12 mb-4 text-stone-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                        </svg>
                                        <p class="text-lg font-medium">No consumable records found</p>
                                        <p class="text-sm">Start by adding a new consumable record to your division.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            @if($this->getRecords()->hasPages())
                <div class="px-6 py-4 border-t border-stone-200 dark:border-stone-700">
                    {{ $this->getRecords()->links() }}
                </div>
            @endif
        </div>
    </x-inventory-manager.layout>
</div>

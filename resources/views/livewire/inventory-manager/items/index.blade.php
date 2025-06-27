<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;

new #[Layout('components.layouts.app')] class extends Component
{
    public string $section = 'Items';

    // Sample data - In a real implementation, this would come from a database
    public array $items = [
        ['name' => 'Sample Item 1', 'category' => 'Office Supplies', 'stock' => 42, 'status' => 'In Stock'],
        ['name' => 'Sample Item 2', 'category' => 'Equipment', 'stock' => 8, 'status' => 'Low Stock'],
        ['name' => 'Sample Item 3', 'category' => 'Consumables', 'stock' => 0, 'status' => 'Out of Stock'],
    ];
    
    public function getTableHeaders(): array
    {
        return ['Name', 'Category', 'Stock', 'Status', 'Actions'];
    }
}

?>

<div>
    <x-inventory-manager.layout heading="Items Management" subheading="Manage your division's inventory items">
        <x-slot name="header">
            <flux:button variant="primary" aria-label="Add new inventory item">
                {{ __('Add New Item') }}
            </flux:button>
        </x-slot>
        
        <x-data-table 
            :headers="$this->getTableHeaders()"
            :data="$items"
            ariaLabel="Inventory Items List"
            caption="List of inventory items with their details and status"
        >
            @foreach($items as $item)
            <tr>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-stone-900 dark:text-stone-200">{{ $item['name'] }}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-stone-900 dark:text-stone-200">{{ $item['category'] }}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-stone-900 dark:text-stone-200">{{ $item['stock'] }}</td>
                <td class="px-6 py-4 whitespace-nowrap">
                    @if($item['status'] === 'In Stock')
                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800 dark:bg-green-700 dark:text-green-100">{{ $item['status'] }}</span>
                    @elseif($item['status'] === 'Low Stock')
                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-amber-100 text-amber-800 dark:bg-amber-700 dark:text-amber-100">{{ $item['status'] }}</span>
                    @else
                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-stone-100 text-stone-800 dark:bg-stone-700 dark:text-stone-100">{{ $item['status'] }}</span>
                    @endif
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                    <div class="flex space-x-2">
                        <flux:button variant="ghost" aria-label="Edit {{ $item['name'] }}">
                            {{ __('Edit') }}
                        </flux:button>
                        <flux:button variant="ghost" aria-label="View {{ $item['name'] }} details">
                            {{ __('View') }}
                        </flux:button>
                    </div>
                </td>
            </tr>
            @endforeach
        </x-data-table>
    </x-inventory-manager.layout>
</div>
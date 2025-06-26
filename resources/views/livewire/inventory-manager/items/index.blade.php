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

@include('livewire.inventory-manager._layout', ['section' => $section, 'slot' => function() { ?>
    <div class="border-b border-stone-200 dark:border-stone-700 pb-4 mb-4">
        <h2 class="text-xl font-medium text-stone-800 dark:text-stone-200">Items Management</h2>
        <p class="mt-2 text-sm text-stone-600 dark:text-stone-400">Manage your division's inventory items.</p>
    </div>
    
    <div class="flex justify-end mb-6">
        <button type="button" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-md shadow-sm" aria-label="Add new inventory item">
            Add New Item
        </button>
    </div>
    
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
                <button type="button" class="text-green-600 hover:text-green-900 dark:text-green-400 dark:hover:text-green-200 mr-2 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2" aria-label="Edit {{ $item['name'] }}">Edit</button>
                <button type="button" class="text-stone-600 hover:text-stone-900 dark:text-stone-400 dark:hover:text-stone-200 focus:outline-none focus:ring-2 focus:ring-stone-500 focus:ring-offset-2" aria-label="View {{ $item['name'] }} details">View</button>
            </td>
        </tr>
        @endforeach
    </x-data-table>
<?php })
<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;

new #[Layout('components.layouts.app')] class extends Component
{
    public string $section = 'Transfers';

    // Sample data - In a real implementation, this would come from a database
    public array $transfers = [
        ['id' => 'TR-001', 'item' => 'Office Chair', 'source' => 'Main Office', 'destination' => 'Field Office', 'status' => 'Pending', 'date' => '2023-11-15'],
        ['id' => 'TR-002', 'item' => 'Desktop Computer', 'source' => 'IT Department', 'destination' => 'Accounting', 'status' => 'In Transit', 'date' => '2023-11-10'],
        ['id' => 'TR-003', 'item' => 'Filing Cabinet', 'source' => 'Admin', 'destination' => 'Records', 'status' => 'Completed', 'date' => '2023-11-05'],
    ];
    
    public function getTableHeaders(): array
    {
        return ['ID', 'Item', 'Source', 'Destination', 'Status', 'Date', 'Actions'];
    }
}

?>

@include('livewire.inventory-manager._layout', ['section' => $section, 'slot' => function() { ?>
    <div class="border-b border-stone-200 dark:border-stone-700 pb-4 mb-4">
        <h2 class="text-xl font-medium text-stone-800 dark:text-stone-200">Transfer Management</h2>
        <p class="mt-2 text-sm text-stone-600 dark:text-stone-400">Manage item transfers between divisions and departments.</p>
    </div>
    
    <div class="flex justify-end mb-6">
        <button type="button" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-md shadow-sm" aria-label="Create new transfer">
            Create Transfer
        </button>
    </div>
    
    <x-data-table 
        :headers="$this->getTableHeaders()"
        :data="$transfers"
        ariaLabel="Item Transfers List"
        caption="List of item transfers between divisions and departments"
    >
        @foreach($transfers as $transfer)
        <tr>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-stone-900 dark:text-stone-200">{{ $transfer['id'] }}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-stone-900 dark:text-stone-200">{{ $transfer['item'] }}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-stone-900 dark:text-stone-200">{{ $transfer['source'] }}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-stone-900 dark:text-stone-200">{{ $transfer['destination'] }}</td>
            <td class="px-6 py-4 whitespace-nowrap">
                @if($transfer['status'] === 'Pending')
                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-amber-100 text-amber-800 dark:bg-amber-700 dark:text-amber-100">{{ $transfer['status'] }}</span>
                @elseif($transfer['status'] === 'In Transit')
                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-stone-100 text-stone-800 dark:bg-stone-700 dark:text-stone-100">{{ $transfer['status'] }}</span>
                @elseif($transfer['status'] === 'Completed')
                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800 dark:bg-green-700 dark:text-green-100">{{ $transfer['status'] }}</span>
                @endif
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-stone-900 dark:text-stone-200">{{ $transfer['date'] }}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                <button type="button" class="text-green-600 hover:text-green-900 dark:text-green-400 dark:hover:text-green-200 mr-2 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2" aria-label="Edit transfer {{ $transfer['id'] }}">Edit</button>
                <button type="button" class="text-stone-600 hover:text-stone-900 dark:text-stone-400 dark:hover:text-stone-200 focus:outline-none focus:ring-2 focus:ring-stone-500 focus:ring-offset-2" aria-label="View transfer {{ $transfer['id'] }} details">View</button>
            </td>
        </tr>
        @endforeach
    </x-data-table>
<?php })
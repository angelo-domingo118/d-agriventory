<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use App\Models\ConsumableItem;
use App\Models\ConsumableRecord;
use Illuminate\Support\Facades\DB;

new #[Layout('components.layouts.app')] class extends Component
{
    public $division;

    public function mount()
    {
        $user = auth()->user()->load('divisionInventoryManager.division');
        $this->division = $user->divisionInventoryManager->division;
    }

    public function getInventoryReport()
    {
        return ConsumableItem::whereHas('record', function ($query) {
            $query->where('division_id', $this->division->id);
        })
        ->with(['specification.itemCatalog'])
        ->get()
        ->groupBy('specification.itemCatalog.name')
        ->map(function ($items, $itemName) {
            $totalInitial = $items->sum('initial_quantity');
            $totalCurrent = $items->sum('current_quantity');
            $totalUsed = $totalInitial - $totalCurrent;
            $usagePercentage = $totalInitial > 0 ? ($totalUsed / $totalInitial) * 100 : 0;
            
            return [
                'item_name' => $itemName,
                'total_initial' => $totalInitial,
                'total_current' => $totalCurrent,
                'total_used' => $totalUsed,
                'usage_percentage' => round($usagePercentage, 1),
                'records_count' => $items->unique('consumable_record_id')->count(),
            ];
        })
        ->sortByDesc('usage_percentage');
    }

    public function getStockLevelsReport()
    {
        return ConsumableItem::whereHas('record', function ($query) {
            $query->where('division_id', $this->division->id);
        })
        ->with(['specification.itemCatalog'])
        ->get()
        ->map(function ($item) {
            $usageRate = $item->initial_quantity > 0 ? 
                (($item->initial_quantity - $item->current_quantity) / $item->initial_quantity) * 100 : 0;
            
            return [
                'item' => $item,
                'usage_rate' => $usageRate,
                'stock_status' => $item->current_quantity == 0 ? 'out_of_stock' : 
                                ($item->current_quantity <= ($item->initial_quantity * 0.2) ? 'low_stock' : 'good_stock')
            ];
        })
        ->sortBy(function ($item) {
            return $item['stock_status'] == 'out_of_stock' ? 0 : 
                   ($item['stock_status'] == 'low_stock' ? 1 : 2);
        });
    }
}

?>

<div>
    <x-inventory-manager.layout heading="Reports" :subheading="'Inventory reports for ' . $this->division->name">
        
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Inventory Usage Report -->
            <div class="bg-white dark:bg-stone-800 shadow-sm rounded-lg">
                <div class="px-6 py-4 border-b border-stone-200 dark:border-stone-700">
                    <h3 class="text-lg font-medium text-stone-800 dark:text-stone-200">Inventory Usage Report</h3>
                </div>
                <div class="p-6">
                    @if($this->getInventoryReport()->count() > 0)
                        <div class="space-y-4">
                            @foreach($this->getInventoryReport()->take(10) as $report)
                            <div class="flex items-center justify-between p-4 border border-stone-200 dark:border-stone-700 rounded-lg">
                                <div>
                                    <p class="font-medium text-stone-900 dark:text-stone-100">{{ $report['item_name'] }}</p>
                                    <p class="text-sm text-stone-600 dark:text-stone-400">
                                        {{ $report['records_count'] }} record(s)
                                    </p>
                                </div>
                                <div class="text-right">
                                    <p class="text-lg font-semibold text-stone-900 dark:text-stone-100">
                                        {{ number_format($report['usage_percentage'], 1) }}%
                                    </p>
                                    <p class="text-sm text-stone-600 dark:text-stone-400">
                                        {{ number_format($report['total_used']) }} / {{ number_format($report['total_initial']) }} used
                                    </p>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-center text-stone-600 dark:text-stone-400 py-8">No usage data available.</p>
                    @endif
                </div>
            </div>

            <!-- Stock Levels Report -->
            <div class="bg-white dark:bg-stone-800 shadow-sm rounded-lg">
                <div class="px-6 py-4 border-b border-stone-200 dark:border-stone-700">
                    <h3 class="text-lg font-medium text-stone-800 dark:text-stone-200">Stock Levels Alert</h3>
                </div>
                <div class="p-6">
                    @if($this->getStockLevelsReport()->count() > 0)
                        <div class="space-y-4 max-h-96 overflow-y-auto">
                            @foreach($this->getStockLevelsReport()->take(15) as $report)
                            <div class="flex items-center justify-between p-3 
                                @if($report['stock_status'] == 'out_of_stock') border-l-4 border-red-500 bg-red-50 dark:bg-red-900/20
                                @elseif($report['stock_status'] == 'low_stock') border-l-4 border-amber-500 bg-amber-50 dark:bg-amber-900/20
                                @else border-l-4 border-green-500 bg-green-50 dark:bg-green-900/20 @endif
                                rounded-r-lg">
                                <div>
                                    <p class="font-medium text-stone-900 dark:text-stone-100">
                                        {{ $report['item']->specification->itemCatalog->name }}
                                    </p>
                                    <p class="text-sm text-stone-600 dark:text-stone-400">
                                        {{ $report['item']->specification->brand ?? 'Generic' }}
                                    </p>
                                </div>
                                <div class="text-right">
                                    <p class="font-semibold 
                                        @if($report['stock_status'] == 'out_of_stock') text-red-700 dark:text-red-300
                                        @elseif($report['stock_status'] == 'low_stock') text-amber-700 dark:text-amber-300
                                        @else text-green-700 dark:text-green-300 @endif">
                                        {{ number_format($report['item']->current_quantity) }}
                                    </p>
                                    <p class="text-sm text-stone-500 dark:text-stone-500">
                                        of {{ number_format($report['item']->initial_quantity) }}
                                    </p>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-center text-stone-600 dark:text-stone-400 py-8">No stock data available.</p>
                    @endif
                </div>
            </div>
        </div>

        <!-- Summary Statistics -->
        <div class="mt-6 bg-white dark:bg-stone-800 shadow-sm rounded-lg p-6">
            <h3 class="text-lg font-medium text-stone-800 dark:text-stone-200 mb-4">Summary Statistics</h3>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div class="text-center">
                    <p class="text-2xl font-bold text-stone-900 dark:text-stone-100">
                        {{ $this->getInventoryReport()->count() }}
                    </p>
                    <p class="text-sm text-stone-600 dark:text-stone-400">Unique Items</p>
                </div>
                <div class="text-center">
                    <p class="text-2xl font-bold text-green-600">
                        {{ $this->getStockLevelsReport()->where('stock_status', 'good_stock')->count() }}
                    </p>
                    <p class="text-sm text-stone-600 dark:text-stone-400">Good Stock</p>
                </div>
                <div class="text-center">
                    <p class="text-2xl font-bold text-amber-600">
                        {{ $this->getStockLevelsReport()->where('stock_status', 'low_stock')->count() }}
                    </p>
                    <p class="text-sm text-stone-600 dark:text-stone-400">Low Stock</p>
                </div>
                <div class="text-center">
                    <p class="text-2xl font-bold text-red-600">
                        {{ $this->getStockLevelsReport()->where('stock_status', 'out_of_stock')->count() }}
                    </p>
                    <p class="text-sm text-stone-600 dark:text-stone-400">Out of Stock</p>
                </div>
            </div>
        </div>
    </x-inventory-manager.layout>
</div> 
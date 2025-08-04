<?php

use App\Models\ParNumber;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.app')] class extends Component {
    public ParNumber $par;

    public function mount(ParNumber $par): void
    {
        if (!auth()->user()->hasAdminPermission('view_inventory')) {
            abort(403);
        }
        $this->par = $par->load('assignedEmployee', 'itemBatches.contractItem.itemSpecification.itemCatalog');
    }

    #[Computed]
    public function totalValue()
    {
        return $this->par->itemBatches->reduce(function ($carry, $batch) {
            return $carry + ($batch->quantity * $batch->contractItem->unit_price);
        }, 0);
    }
}; ?>

<div>
    <div class="border-b border-stone-200 pb-5 dark:border-stone-700">
        <div class="flex flex-wrap items-center justify-between sm:flex-nowrap">
            <div>
                <flux:breadcrumbs class="text-2xl font-semibold">
                    <flux:breadcrumbs.item :href="route('admin.dashboard')" wire:navigate icon="home" class="text-xl sm:text-2xl font-semibold text-stone-700 dark:text-stone-300" />
                    <flux:breadcrumbs.item href="#" class="text-xl sm:text-2xl font-semibold text-stone-500 dark:text-stone-400">Inventory</flux:breadcrumbs.item>
                    <flux:breadcrumbs.item :href="route('admin.inventory.par.index')" wire:navigate class="text-xl sm:text-2xl font-semibold text-stone-500 dark:text-stone-400">PAR Management</flux:breadcrumbs.item>
                    <flux:breadcrumbs.item class="text-xl sm:text-2xl font-semibold text-stone-900 dark:text-stone-100">PAR Details #{{ $par->par_number }}</flux:breadcrumbs.item>
                </flux:breadcrumbs>
                <p class="mt-1 text-sm text-stone-600 dark:text-stone-400">
                    Viewing Property Acknowledgement Receipt #<span class="font-bold">{{ $par->par_number }}</span>
                </p>
            </div>
            <div class="mt-3 flex flex-shrink-0 gap-x-3 sm:mt-0">
                 <a href="{{ route('admin.inventory.par.index') }}" wire:navigate class="flux-button-ghost">
                    <x-flux::icon.arrow-left class="h-5 w-5" />
                    <span>Back to list</span>
                </a>
                <a href="{{ route('admin.inventory.par.edit', $par) }}" wire:navigate class="flux-button-primary">
                    <x-flux::icon.edit class="h-5 w-5" />
                    <span>Edit</span>
                </a>
                <button type="button" @click="window.print()" class="flux-button-secondary">
                    <x-flux::icon.printer class="h-5 w-5" />
                    <span>Print</span>
                </button>
            </div>
        </div>
    </div>

    <div class="mt-6">
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            {{-- Main Details --}}
            <div class="space-y-6 lg:col-span-2">
                <div class="overflow-hidden rounded-lg border border-stone-200 bg-white dark:border-stone-700 dark:bg-stone-800">
                    <div class="px-4 py-5 sm:px-6">
                        <h3 class="text-base font-semibold leading-7 text-stone-900 dark:text-white">
                            PAR Information
                        </h3>
                        <p class="mt-1 max-w-2xl text-sm leading-6 text-stone-500 dark:text-stone-400">
                            Details about the receipt and assigned custodian.
                        </p>
                    </div>
                    <div class="border-t border-stone-200 px-4 py-5 dark:border-stone-700 sm:p-0">
                        <dl class="sm:divide-y sm:divide-stone-200 dark:sm:divide-stone-700">
                             <div class="py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6 sm:py-5">
                                <dt class="text-sm font-medium text-stone-500 dark:text-stone-400">PAR Number</dt>
                                <dd class="mt-1 text-sm leading-6 text-stone-700 dark:text-stone-200 sm:col-span-2 sm:mt-0">{{ $par->par_number }}</dd>
                            </div>
                            <div class="py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6 sm:py-5">
                                <dt class="text-sm font-medium text-stone-500 dark:text-stone-400">Assigned Employee</dt>
<dd class="mt-1 text-sm leading-6 text-stone-700 dark:text-stone-200 sm:col-span-2 sm:mt-0">
    {{ $par->assignedEmployee?->name ?? 'Employee not found' }}
</dd>                            </div>
                            <div class="py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6 sm:py-5">
                                <dt class="text-sm font-medium text-stone-500 dark:text-stone-400">Date Acquired</dt>
                                <dd class="mt-1 text-sm leading-6 text-stone-700 dark:text-stone-200 sm:col-span-2 sm:mt-0">{{ $par->date_acquired->format('F j, Y') }}</dd>
                            </div>
                             <div class="py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6 sm:py-5">
                                <dt class="text-sm font-medium text-stone-500 dark:text-stone-400">Area / Building / Room</dt>
                                <dd class="mt-1 text-sm leading-6 text-stone-700 dark:text-stone-200 sm:col-span-2 sm:mt-0">
                                    {{ $par->area_code ?? 'N/A' }} / {{ $par->building_code ?? 'N/A' }}
                                </dd>
                            </div>
                            <div class="py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6 sm:py-5">
                                <dt class="text-sm font-medium text-stone-500 dark:text-stone-400">Account Code</dt>
                                <dd class="mt-1 text-sm leading-6 text-stone-700 dark:text-stone-200 sm:col-span-2 sm:mt-0">{{ $par->account_code ?? 'N/A' }}</dd>
                            </div>
                             <div class="py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6 sm:py-5">
                                <dt class="text-sm font-medium text-stone-500 dark:text-stone-400">Remarks</dt>
                                <dd class="mt-1 text-sm leading-6 text-stone-700 dark:text-stone-200 sm:col-span-2 sm:mt-0">{{ $par->remarks ?: 'No remarks.' }}</dd>
                            </div>
                        </dl>
                    </div>
                </div>
            </div>

            {{-- Totals --}}
            <div class="lg:col-span-1">
                <div class="rounded-lg bg-white p-6 shadow dark:bg-stone-800">
                    <h3 class="text-lg font-medium text-stone-900 dark:text-white">Summary</h3>
                    <div class="mt-6 space-y-4">
                        <div class="flex justify-between">
                            <p class="text-stone-600 dark:text-stone-300">Total Items</p>
                            <p class="font-semibold text-stone-900 dark:text-white">{{ $par->itemBatches->sum('quantity') }}</p>
                        </div>
                        <div class="flex justify-between">
                            <p class="text-stone-600 dark:text-stone-300">Total Value</p>
                            <p class="font-semibold text-stone-900 dark:text-white">₱{{ number_format($this->totalValue, 2) }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Item Batches Table --}}
        <div class="mt-6 overflow-hidden rounded-lg border border-stone-200 bg-white dark:border-stone-700 dark:bg-stone-800">
             <div class="px-4 py-5 sm:px-6">
                <h3 class="text-base font-semibold leading-7 text-stone-900 dark:text-white">
                    Items Included
                </h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-stone-200 dark:divide-stone-700">
                    <thead class="bg-stone-50 dark:bg-stone-700/50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-stone-500 dark:text-stone-300">Item Name</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-stone-500 dark:text-stone-300">Serial Number</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-stone-500 dark:text-stone-300">Quantity</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-stone-500 dark:text-stone-300">Unit Cost</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-stone-500 dark:text-stone-300">Total Cost</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-stone-200 bg-white dark:divide-stone-800 dark:bg-stone-900">
                        @forelse($par->itemBatches as $batch)
                            <tr>
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-stone-900 dark:text-stone-100">{{ $batch->contractItem?->itemSpecification?->itemCatalog?->name ?? 'Item not found' }}</td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-stone-900 dark:text-stone-100">{{ $batch->serial_number }}</td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-stone-900 dark:text-stone-100">{{ $batch->quantity }}</td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-stone-500 dark:text-stone-300">₱{{ number_format($batch->contractItem?->unit_price ?? 0, 2) }}</td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-stone-500 dark:text-stone-300">₱{{ number_format($batch->quantity * ($batch->contractItem?->unit_price ?? 0), 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-4 text-center text-sm text-stone-500 dark:text-stone-400">No items found for this PAR.</td>
                            </tr>
                        @endforelse
                    </tbody>
                     <tfoot class="bg-stone-50 dark:bg-stone-700/50">
                        <tr>
                            <td colspan="2" class="px-6 py-3 text-left text-sm font-semibold text-stone-900 dark:text-white">Total</td>
                            <td class="px-6 py-3 text-left text-sm font-semibold text-stone-900 dark:text-white">{{ $par->itemBatches->sum('quantity') }}</td>
                            <td class="px-6 py-3"></td>
                            <td class="px-6 py-3 text-left text-sm font-semibold text-stone-900 dark:text-white">₱{{ number_format($this->totalValue, 2) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div> 
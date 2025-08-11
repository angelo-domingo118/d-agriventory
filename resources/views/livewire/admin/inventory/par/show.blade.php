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
        $this->par = $par->load([
            'assignedEmployee.division',
            'contractItem.itemSpecification.itemCatalog',
            'contractItem.contract.supplier',
            'itemBatches'
        ]);
    }

    #[Computed]
    public function totalValue()
    {
        return $this->par->quantity * $this->par->contractItem->unit_price;
    }
}; ?>

<div>
    <div class="border-b border-stone-200 pb-5 dark:border-stone-700">
        <div class="flex flex-wrap items-center justify-between sm:flex-nowrap">
            <div>
                <flux:breadcrumbs class="text-2xl font-semibold">
                    <flux:breadcrumbs.item :href="route('admin.dashboard')" wire:navigate icon="home" class="text-xl sm:text-2xl font-semibold text-stone-700 dark:text-stone-300" />
                    <flux:breadcrumbs.item class="text-xl sm:text-2xl font-semibold text-stone-500 dark:text-stone-400">Inventory</flux:breadcrumbs.item>
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
                                    @if($par->assignedEmployee?->division)
                                        <br><span class="text-stone-500">{{ $par->assignedEmployee->division->name }}</span>
                                    @endif
                                </dd>
                            </div>
                            <div class="py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6 sm:py-5">
                                <dt class="text-sm font-medium text-stone-500 dark:text-stone-400">Item</dt>
                                <dd class="mt-1 text-sm leading-6 text-stone-700 dark:text-stone-200 sm:col-span-2 sm:mt-0">
                                    {{ $par->contractItem?->itemSpecification?->itemCatalog?->name ?? 'Item not found' }}
                                    @if($par->contractItem?->itemSpecification)
                                        @php $spec = $par->contractItem->itemSpecification; @endphp
                                        @if($spec->brand || $spec->model)
                                            <br><span class="text-stone-500">{{ collect([$spec->brand, $spec->model])->filter()->join(' / ') }}</span>
                                        @endif
                                    @endif
                                </dd>
                            </div>
                            <div class="py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6 sm:py-5">
                                <dt class="text-sm font-medium text-stone-500 dark:text-stone-400">Supplier</dt>
                                <dd class="mt-1 text-sm leading-6 text-stone-700 dark:text-stone-200 sm:col-span-2 sm:mt-0">
                                    {{ $par->contractItem?->contract?->supplier?->name ?? 'Supplier not found' }}
                                    @if($par->contractItem?->contract?->contract_po_ib_number)
                                        <br><span class="text-stone-500">Contract: {{ $par->contractItem->contract->contract_po_ib_number }}</span>
                                    @endif
                                </dd>
                            </div>
                            <div class="py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6 sm:py-5">
                                <dt class="text-sm font-medium text-stone-500 dark:text-stone-400">Quantity & Cost</dt>
                                <dd class="mt-1 text-sm leading-6 text-stone-700 dark:text-stone-200 sm:col-span-2 sm:mt-0">
                                    {{ $par->quantity }} {{ $par->contractItem?->itemSpecification?->itemCatalog?->unit ?? 'unit' }}(s)
                                    <br><span class="text-stone-500">₱{{ number_format($par->contractItem?->unit_price ?? 0, 2) }} per unit</span>
                                </dd>
                            </div>
                            @if($par->date_prepared || $par->date_accepted || $par->date_acquired)
                                <div class="py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6 sm:py-5">
                                    <dt class="text-sm font-medium text-stone-500 dark:text-stone-400">Dates</dt>
                                    <dd class="mt-1 text-sm leading-6 text-stone-700 dark:text-stone-200 sm:col-span-2 sm:mt-0">
                                        @if($par->date_prepared)
                                            <div>Prepared: {{ $par->date_prepared->format('F j, Y') }}</div>
                                        @endif
                                        @if($par->date_accepted)
                                            <div>Accepted: {{ $par->date_accepted->format('F j, Y') }}</div>
                                        @endif
                                        @if($par->date_acquired)
                                            <div>Acquired: {{ $par->date_acquired->format('F j, Y') }}</div>
                                        @endif
                                    </dd>
                                </div>
                            @endif
                            @if($par->inventory_code)
                                <div class="py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6 sm:py-5">
                                    <dt class="text-sm font-medium text-stone-500 dark:text-stone-400">Inventory Code</dt>
                                    <dd class="mt-1 text-sm leading-6 text-stone-700 dark:text-stone-200 sm:col-span-2 sm:mt-0">{{ $par->inventory_code }}</dd>
                                </div>
                            @endif
                            <div class="py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6 sm:py-5">
                                <dt class="text-sm font-medium text-stone-500 dark:text-stone-400">Location Codes</dt>
                                <dd class="mt-1 text-sm leading-6 text-stone-700 dark:text-stone-200 sm:col-span-2 sm:mt-0">
                                    <div>Area: {{ $par->area_code ?? 'N/A' }}</div>
                                    <div>Building: {{ $par->building_code ?? 'N/A' }}</div>
                                    <div>Account: {{ $par->account_code ?? 'N/A' }}</div>
                                </dd>
                            </div>
                            @if($par->remarks)
                                <div class="py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6 sm:py-5">
                                    <dt class="text-sm font-medium text-stone-500 dark:text-stone-400">Remarks</dt>
                                    <dd class="mt-1 text-sm leading-6 text-stone-700 dark:text-stone-200 sm:col-span-2 sm:mt-0">{{ $par->remarks }}</dd>
                                </div>
                            @endif
                        </dl>
                    </div>
                </div>
            </div>

            {{-- Summary --}}
            <div class="lg:col-span-1">
                <div class="rounded-lg bg-white p-6 shadow dark:bg-stone-800">
                    <h3 class="text-lg font-medium text-stone-900 dark:text-white">Summary</h3>
                    <div class="mt-6 space-y-4">
                        <div class="flex justify-between">
                            <p class="text-stone-600 dark:text-stone-300">Quantity</p>
                            <p class="font-semibold text-stone-900 dark:text-white">{{ $par->quantity }} {{ $par->contractItem?->itemSpecification?->itemCatalog?->unit ?? 'unit' }}(s)</p>
                        </div>
                        <div class="flex justify-between">
                            <p class="text-stone-600 dark:text-stone-300">Unit Price</p>
                            <p class="font-semibold text-stone-900 dark:text-white">₱{{ number_format($par->contractItem?->unit_price ?? 0, 2) }}</p>
                        </div>
                        <div class="flex justify-between border-t pt-4">
                            <p class="text-stone-600 dark:text-stone-300">Total Value</p>
                            <p class="font-semibold text-stone-900 dark:text-white">₱{{ number_format($this->totalValue, 2) }}</p>
                        </div>
                        <div class="flex justify-between">
                            <p class="text-stone-600 dark:text-stone-300">Identification Batches</p>
                            <p class="font-semibold text-stone-900 dark:text-white">{{ $par->itemBatches->count() }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Identification Data Batches --}}
        @if($par->itemBatches->isNotEmpty())
            <div class="mt-6 overflow-hidden rounded-lg border border-stone-200 bg-white dark:border-stone-700 dark:bg-stone-800">
                <div class="px-4 py-5 sm:px-6">
                    <h3 class="text-base font-semibold leading-7 text-stone-900 dark:text-white">
                        Identification Data
                    </h3>
                    <p class="mt-1 text-sm text-stone-500 dark:text-stone-400">
                        Serial numbers, asset tags, and other identification data for individual items.
                    </p>
                </div>
                <div class="border-t border-stone-200 dark:border-stone-700">
                    <div class="divide-y divide-stone-200 dark:divide-stone-700">
                        @foreach($par->itemBatches as $batch)
                            <div wire:key="batch-{{ $batch->id }}" class="px-6 py-4">
                                <div class="flex items-start justify-between">
                                    <div class="flex-1">
                                        <h4 class="text-sm font-medium text-stone-900 dark:text-stone-100">
                                            Batch #{{ $loop->iteration }}
                                        </h4>
                                        @if($batch->identification_data)
                                            <div class="mt-1 text-sm text-stone-600 dark:text-stone-300 whitespace-pre-wrap">{{ $batch->identification_data }}</div>
                                        @else
                                            <div class="mt-1 text-sm italic text-stone-500 dark:text-stone-400">No identification data recorded</div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif
    </div>
</div> 
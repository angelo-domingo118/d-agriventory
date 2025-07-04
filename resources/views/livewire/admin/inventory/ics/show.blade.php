<?php

use App\Models\Employee;
use App\Models\IcsNumber;
use App\Models\IcsTransfer;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.app')] class extends Component {
    public IcsNumber $icsNumber;

    public function mount(IcsNumber $icsNumber): void
    {
        if (!auth()->user()->hasAdminPermission('view_inventory')) {
            abort(403);
        }

        $this->icsNumber = $icsNumber->load([
            'contractItem.itemSpecification.catalogItem.secondaryCategory.primaryCategory',
            'contractItem.contract.supplier',
            'assignedEmployee.position',
            'assignedEmployee.division',
            'itemBatches.components',
            'transfers.fromEmployee',
            'transfers.toEmployee',
        ]);
    }
}; ?>

<div class="space-y-8">
     <div class="border-b border-stone-200 pb-5 dark:border-stone-700">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-semibold text-stone-900 dark:text-stone-100">
                    ICS Details: {{ $this->icsNumber->ics_number }}
                </h1>
                <p class="mt-1 text-sm text-stone-600 dark:text-stone-400">
                    Viewing full details for Inventory Custodian Slip #{{ $this->icsNumber->ics_number }}.
                </p>
            </div>
            <div class="flex items-center gap-x-4">
                <flux:button variant="ghost" :href="route('admin.inventory.ics.index')" wire:navigate>
                    &larr; Back to List
                </flux:button>
                <flux:button variant="primary" :href="route('admin.inventory.ics.edit', $icsNumber)" wire:navigate>
                    Edit ICS
                </flux:button>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-8 lg:grid-cols-4">
        <!-- Main Content -->
        <div class="lg:col-span-3">
            <div class="space-y-8">
                <!-- Item & Contract Details -->
                <div class="overflow-hidden rounded-lg border border-stone-200 bg-white shadow-sm dark:border-stone-700 dark:bg-stone-800">
                    <div class="border-b border-stone-200 px-4 py-3 dark:border-stone-700">
                        <h3 class="font-semibold text-stone-800 dark:text-stone-200">Item Information</h3>
                    </div>
                    <div class="grid grid-cols-1 gap-y-4 p-6 md:grid-cols-2">
                        <div>
                            <span class="text-sm font-medium text-stone-500 dark:text-stone-400">Item Name</span>
                            <p class="mt-1 text-stone-900 dark:text-stone-100">{{ $this->icsNumber->contractItem?->itemSpecification?->catalogItem?->name ?? '—' }}</p>
                        </div>
                        <div>
                            <span class="text-sm font-medium text-stone-500 dark:text-stone-400">Item Code</span>
                             <p class="mt-1 text-stone-900 dark:text-stone-100">{{ $this->icsNumber->contractItem?->itemSpecification?->catalogItem?->code ?? '—' }}</p>
                        </div>
                        <div>
                            <span class="text-sm font-medium text-stone-500 dark:text-stone-400">Category</span>
                            <p class="mt-1 text-stone-900 dark:text-stone-100">
                                @php
                                    $secondary = $this->icsNumber->contractItem?->itemSpecification?->catalogItem?->secondaryCategory;
                                    $primary = $secondary?->primaryCategory;
                                @endphp
                                {{ $primary?->name ?? 'N/A' }} / {{ $secondary?->name ?? 'N/A' }}
                            </p>
                        </div>
                        <div>
                            <span class="text-sm font-medium text-stone-500 dark:text-stone-400">Unit</span>
                            <p class="mt-1 text-stone-900 dark:text-stone-100">{{ $this->icsNumber->contractItem?->itemSpecification?->catalogItem?->unit ?? 'unit' }}</p>
                        </div>
                        <div class="md:col-span-2">
                            <span class="text-sm font-medium text-stone-500 dark:text-stone-400">Detailed Specifications</span>
                            <p class="mt-1 text-stone-900 dark:text-stone-100">{{ $this->icsNumber->contractItem?->itemSpecification?->detailed_specifications ?: 'No specifications provided.' }}</p>
                        </div>
                         <div class="md:col-span-2 border-t border-stone-200 dark:border-stone-700 pt-4">
                            <span class="text-sm font-medium text-stone-500 dark:text-stone-400">Contract</span>
                            <p class="mt-1 text-stone-900 dark:text-stone-100">
                                {{ $this->icsNumber->contractItem?->contract?->contract_po_ib_number ?? '—' }}
                                <span class="text-stone-500 dark:text-stone-400"> (Supplier: {{ $this->icsNumber->contractItem?->contract?->supplier?->name ?? '—' }})</span>
                            </p>
                        </div>
                    </div>
                </div>

                 <!-- Serial Numbers / Components -->
                <div class="overflow-hidden rounded-lg border border-stone-200 bg-white shadow-sm dark:border-stone-700 dark:bg-stone-800">
                    <div class="border-b border-stone-200 px-4 py-3 dark:border-stone-700">
                        <h3 class="font-semibold text-stone-800 dark:text-stone-200">Serial Number(s) / Components</h3>
                    </div>
                    <div class="p-6">
                        @php
                            $hasAnyIdentification = $this->icsNumber->itemBatches->some(fn($batch) => $batch->identification_data || $batch->components->isNotEmpty());
                        @endphp
                        @if($this->icsNumber->itemBatches->isNotEmpty())
                            @if($hasAnyIdentification)
                                <ul class="space-y-4">
                                    @foreach($this->icsNumber->itemBatches as $batch)
                                        <li wire:key="show-batch-{{ $batch->id }}">
                                            @if($this->icsNumber->itemBatches->count() > 1)
                                                <p class="font-medium text-stone-700 dark:text-stone-300">Batch #{{ $loop->iteration }}:</p>
                                            @endif
                                            <div @if($this->icsNumber->itemBatches->count() > 1) class="pl-4 mt-2" @endif>
                                                @if($batch->components->isNotEmpty())
                                                    <ul class="list-disc pl-5 space-y-1 text-stone-600 dark:text-stone-400">
                                                        @foreach($batch->components as $component)
                                                            <li>
                                                                <strong>{{ $component->component_type }}:</strong>
                                                                @if($component->serial_number)
                                                                    <span>{{ $component->serial_number }}</span>
                                                                @else
                                                                    <span class="italic text-stone-500">Not provided</span>
                                                                @endif
                                                            </li>
                                                        @endforeach
                                                    </ul>
                                                @elseif($batch->identification_data)
                                                    <p class="text-stone-600 dark:text-stone-300">{{ $batch->identification_data }}</p>
                                                @else
                                                    <p class="italic text-stone-500">No serial number recorded for this batch.</p>
                                                @endif
                                            </div>
                                        </li>
                                    @endforeach
                                </ul>
                            @else
                                <p class="italic text-stone-500">No serial numbers recorded for any batches.</p>
                            @endif
                        @else
                            <p class="italic text-stone-500">No item serials recorded.</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Sidebar Info -->
        <div class="lg:col-span-1">
            <div class="space-y-8">
                <!-- Custodian Details -->
                <div class="overflow-hidden rounded-lg border border-stone-200 bg-white shadow-sm dark:border-stone-700 dark:bg-stone-800">
                    <div class="border-b border-stone-200 px-4 py-3 dark:border-stone-700">
                        <h3 class="font-semibold text-stone-800 dark:text-stone-200">Custodian Information</h3>
                    </div>
                    <div class="space-y-4 p-6">
                        <div>
                            <span class="text-sm font-medium text-stone-500 dark:text-stone-400">Current Custodian</span>
                            <p class="mt-1 text-stone-900 dark:text-stone-100">{{ $this->icsNumber->assignedEmployee?->name ?? 'Unassigned' }}</p>
                        </div>
                        <div>
                            <span class="text-sm font-medium text-stone-500 dark:text-stone-400">Position</span>
                            <p class="mt-1 text-stone-900 dark:text-stone-100">{{ $this->icsNumber->assignedEmployee?->position?->name ?? '—' }}</p>
                        </div>
                        <div>
                            <span class="text-sm font-medium text-stone-500 dark:text-stone-400">Division/Office</span>
                            <p class="mt-1 text-stone-900 dark:text-stone-100">{{ $this->icsNumber->assignedEmployee?->division?->name ?? '—' }}</p>
                        </div>
                    </div>
                </div>

                <!-- Document Details -->
                <div class="overflow-hidden rounded-lg border border-stone-200 bg-white shadow-sm dark:border-stone-700 dark:bg-stone-800">
                    <div class="border-b border-stone-200 px-4 py-3 dark:border-stone-700">
                        <h3 class="font-semibold text-stone-800 dark:text-stone-200">Document Details</h3>
                    </div>
                    <div class="space-y-4 p-6">
                        <div>
                            <span class="text-sm font-medium text-stone-500 dark:text-stone-400">Qty per Batch</span>
                            <p class="mt-1 text-stone-900 dark:text-stone-100">{{ $this->icsNumber->quantity }} {{ $this->icsNumber->contractItem?->itemSpecification?->catalogItem?->unit ?? 'unit' }}(s)</p>
                        </div>
                        <div>
                            <span class="text-sm font-medium text-stone-500 dark:text-stone-400">Batches</span>
                            <p class="mt-1 text-stone-900 dark:text-stone-100">{{ $this->icsNumber->itemBatches->count() }}</p>
                        </div>
                        <div>
                            <span class="text-sm font-medium text-stone-500 dark:text-stone-400">Unit Cost</span>
                            <p class="mt-1 text-stone-900 dark:text-stone-100">₱{{ number_format($this->icsNumber->contractItem?->unit_price ?? 0, 2) }}</p>
                        </div>
                        <div>
                            <span class="text-sm font-medium text-stone-500 dark:text-stone-400">Est. Useful Life</span>
                            <p class="mt-1 text-stone-900 dark:text-stone-100">{{ $this->icsNumber->estimated_useful_life }} years</p>
                        </div>
                        <div>
                            <span class="text-sm font-medium text-stone-500 dark:text-stone-400">Date Prepared</span>
                            <p class="mt-1 text-stone-900 dark:text-stone-100">{{ $this->icsNumber->date_prepared->format('F d, Y') }}</p>
                        </div>
                        <div>
                            <span class="text-sm font-medium text-stone-500 dark:text-stone-400">Date Accepted</span>
                            <p class="mt-1 text-stone-900 dark:text-stone-100">{{ $this->icsNumber->date_accepted->format('F d, Y') }}</p>
                        </div>
                        <div class="pt-2">
                            <span class="text-sm font-medium text-stone-500 dark:text-stone-400">Remarks</span>
                            <p class="mt-1 text-stone-900 dark:text-stone-100">{{ $this->icsNumber->remarks ?: 'No remarks provided.' }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div> 
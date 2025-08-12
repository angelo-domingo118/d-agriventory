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
            'contractItem.itemSpecification.itemCatalog.secondaryCategory.primaryCategory',
            'contractItem.contract.supplier',
            'assignedEmployee.division',
            // Removed '.item.itemSpecification' as ItemComponent currently has no `item` relation. Eager load only components for now.
            'itemBatches.components',
            'transfers.fromEmployee.division',
            'transfers.toEmployee.division',
        ]);
    }
}; ?>

<div>
<div class="space-y-6">
    <div class="border-b border-stone-200 pb-5 dark:border-stone-700">
        <div class="flex items-center justify-between">
            <!-- Breadcrumbs as Title -->
            <div>
                <flux:breadcrumbs class="text-2xl font-semibold">
                    <flux:breadcrumbs.item :href="route('admin.dashboard')" wire:navigate icon="home" class="text-xl sm:text-2xl font-semibold text-stone-700 dark:text-stone-300" />
                    <flux:breadcrumbs.item class="text-xl sm:text-2xl font-semibold text-stone-500 dark:text-stone-400">Inventory</flux:breadcrumbs.item>
                    <flux:breadcrumbs.item :href="route('admin.inventory.ics.index')" wire:navigate class="text-xl sm:text-2xl font-semibold text-stone-700 dark:text-stone-300">ICS Management</flux:breadcrumbs.item>
                    <flux:breadcrumbs.item class="text-xl sm:text-2xl font-semibold text-stone-900 dark:text-stone-100">ICS {{ $icsNumber->ics_number }}</flux:breadcrumbs.item>
                </flux:breadcrumbs>
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

    <!-- Top Row: Supplier & Contract + Employee Assignment -->
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <!-- Supplier & Contract Section -->
        <div class="overflow-hidden rounded-lg border border-stone-200 bg-white shadow-sm dark:border-stone-700 dark:bg-stone-800">
            <div class="border-b border-stone-200 px-6 py-4 dark:border-stone-700">
                <h3 class="flex items-center font-semibold text-stone-800 dark:text-stone-200">
                    <x-flux::icon.building-office class="mr-2 h-5 w-5 text-stone-500 dark:text-stone-400" />
                    Supplier & Contract
                </h3>
            </div>
            <div class="px-6 py-5">
                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                    <div>
                        <span class="text-sm font-medium text-stone-500 dark:text-stone-400">Supplier</span>
                        <p class="mt-2 text-base font-medium text-stone-900 dark:text-stone-100">{{ $this->icsNumber->contractItem?->contract?->supplier?->name ?? '—' }}</p>
                    </div>
                    <div>
                        <span class="text-sm font-medium text-stone-500 dark:text-stone-400">Contract/PO/IB No.</span>
                        <p class="mt-2 text-base font-medium text-stone-900 dark:text-stone-100">{{ $this->icsNumber->contractItem?->contract?->contract_po_ib_number ?? '—' }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Employee Assignment Section -->
        <div class="overflow-hidden rounded-lg border border-stone-200 bg-white shadow-sm dark:border-stone-700 dark:bg-stone-800">
            <div class="border-b border-stone-200 px-6 py-4 dark:border-stone-700">
                <h3 class="flex items-center font-semibold text-stone-800 dark:text-stone-200">
                    <x-flux::icon.user class="mr-2 h-5 w-5 text-stone-500 dark:text-stone-400" />
                    Employee Assignment
                </h3>
            </div>
            <div class="px-6 py-5">
                <div class="grid grid-cols-1 gap-6 sm:grid-cols-3">
                    <div>
                        <span class="text-sm font-medium text-stone-500 dark:text-stone-400">Current Custodian</span>
                        <p class="mt-2 text-base font-medium text-stone-900 dark:text-stone-100">{{ $this->icsNumber->assignedEmployee?->name ?? 'Unassigned' }}</p>
                    </div>
                    <div>
                        <span class="text-sm font-medium text-stone-500 dark:text-stone-400">Position</span>
                        <p class="mt-2 text-base font-medium text-stone-900 dark:text-stone-100">{{ $this->icsNumber->assignedEmployee?->position ?? '—' }}</p>
                    </div>
                    <div>
                        <span class="text-sm font-medium text-stone-500 dark:text-stone-400">Division/Office</span>
                        <p class="mt-2 text-base font-medium text-stone-900 dark:text-stone-100">{{ $this->icsNumber->assignedEmployee?->division?->name ?? '—' }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content Row -->
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <!-- Column 1: Item Information -->
        <div class="space-y-6">
            <!-- Item Catalog & Specifications -->
            <div class="overflow-hidden rounded-lg border border-stone-200 bg-white shadow-sm dark:border-stone-700 dark:bg-stone-800">
                <div class="border-b border-stone-200 px-6 py-4 dark:border-stone-700">
                    <h3 class="flex items-center font-semibold text-stone-800 dark:text-stone-200">
                        <x-flux::icon.cube class="mr-2 h-5 w-5 text-stone-500 dark:text-stone-400" />
                        Item Information
                    </h3>
                </div>
                <div class="px-6 py-5">
                    <div class="space-y-6">
                        <!-- Item Catalog Details -->
                        <div>
                            <h4 class="mb-4 flex items-center text-sm font-semibold text-stone-700 dark:text-stone-300 uppercase tracking-wide">
                                <x-flux::icon.tag class="mr-2 h-4 w-4" />
                                Item Catalog
                            </h4>
                            <div class="space-y-4">
                                <div>
                                    <span class="text-sm font-medium text-stone-500 dark:text-stone-400">Item Name</span>
                                    <p class="mt-2 text-base font-medium text-stone-900 dark:text-stone-100">{{ $this->icsNumber->contractItem?->itemSpecification?->itemCatalog?->name ?? '—' }}</p>
                                </div>
                                <div>
                                    <span class="text-sm font-medium text-stone-500 dark:text-stone-400">Item Code</span>
                                    <p class="mt-2 font-mono text-sm text-stone-700 dark:text-stone-300">{{ $this->icsNumber->contractItem?->itemSpecification?->itemCatalog?->code ?? '—' }}</p>
                                </div>
                                <div>
                                    <span class="text-sm font-medium text-stone-500 dark:text-stone-400">Category</span>
                                    <p class="mt-2 text-base text-stone-900 dark:text-stone-100">
                                        @php
                                            $secondary = $this->icsNumber->contractItem?->itemSpecification?->itemCatalog?->secondaryCategory;
                                            $primary = $secondary?->primaryCategory;
                                        @endphp
                                        {{ $primary?->name ?? 'N/A' }} / {{ $secondary?->name ?? 'N/A' }}
                                    </p>
                                </div>
                                <div>
                                    <span class="text-sm font-medium text-stone-500 dark:text-stone-400">Unit of Measure</span>
                                    <p class="mt-2 text-base text-stone-900 dark:text-stone-100">{{ $this->icsNumber->contractItem?->itemSpecification?->itemCatalog?->unit ?? 'unit' }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Item Specifications -->
                        <div class="border-t border-stone-200 pt-6 dark:border-stone-700">
                            <h4 class="mb-4 flex items-center text-sm font-semibold text-stone-700 dark:text-stone-300 uppercase tracking-wide">
                                <x-flux::icon.cog-6-tooth class="mr-2 h-4 w-4" />
                                Item Specifications
                            </h4>
                            <div class="space-y-4">
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <span class="text-sm font-medium text-stone-500 dark:text-stone-400">Brand</span>
                                        <p class="mt-2 text-base font-medium text-stone-900 dark:text-stone-100">{{ $this->icsNumber->contractItem?->itemSpecification?->brand ?? '—' }}</p>
                                    </div>
                                    <div>
                                        <span class="text-sm font-medium text-stone-500 dark:text-stone-400">Model</span>
                                        <p class="mt-2 text-base font-medium text-stone-900 dark:text-stone-100">{{ $this->icsNumber->contractItem?->itemSpecification?->model ?? '—' }}</p>
                                    </div>
                                </div>
                                <div>
                                    <span class="text-sm font-medium text-stone-500 dark:text-stone-400">Detailed Specifications</span>
                                    <div class="mt-2 rounded-md bg-stone-50 p-3 dark:bg-stone-800/50">
                                        <p class="text-sm text-stone-700 dark:text-stone-300">{{ $this->icsNumber->contractItem?->itemSpecification?->detailed_specifications ?: 'No specifications provided.' }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Pricing Information -->
            <div class="overflow-hidden rounded-lg border border-stone-200 bg-white shadow-sm dark:border-stone-700 dark:bg-stone-800">
                <div class="border-b border-stone-200 px-6 py-4 dark:border-stone-700">
                    <h3 class="flex items-center font-semibold text-stone-800 dark:text-stone-200">
                        <x-flux::icon.currency-dollar class="mr-2 h-5 w-5 text-stone-500 dark:text-stone-400" />
                        Pricing Information
                    </h3>
                </div>
                <div class="px-6 py-5">
                    <div class="grid grid-cols-2 gap-6">
                        <div class="rounded-lg bg-green-50 p-4 dark:bg-green-900/20">
                            <span class="text-sm font-medium text-green-600 dark:text-green-400">Unit Cost</span>
                            <p class="mt-2 text-2xl font-bold text-green-700 dark:text-green-300">₱{{ number_format($this->icsNumber->contractItem?->unit_price ?? 0, 2) }}</p>
                        </div>
                        <div class="rounded-lg bg-blue-50 p-4 dark:bg-blue-900/20">
                            <span class="text-sm font-medium text-blue-600 dark:text-blue-400">Total Value</span>
                            <p class="mt-2 text-2xl font-bold text-blue-700 dark:text-blue-300">₱{{ number_format(($this->icsNumber->contractItem?->unit_price ?? 0) * $this->icsNumber->quantity, 2) }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Column 2: Document Details -->
        <div class="overflow-hidden rounded-lg border border-stone-200 bg-white shadow-sm dark:border-stone-700 dark:bg-stone-800">
            <div class="border-b border-stone-200 px-6 py-4 dark:border-stone-700">
                <h3 class="flex items-center font-semibold text-stone-800 dark:text-stone-200">
                    <x-flux::icon.document-text class="mr-2 h-5 w-5 text-stone-500 dark:text-stone-400" />
                    Document Details
                </h3>
            </div>
            <div class="px-6 py-5">
                <div class="space-y-6">
                    <!-- ICS Information -->
                    <div>
                        <h4 class="mb-4 flex items-center text-sm font-semibold text-stone-700 dark:text-stone-300 uppercase tracking-wide">
                            <x-flux::icon.hashtag class="mr-2 h-4 w-4" />
                            ICS Information
                        </h4>
                        <div class="space-y-4">
                            <div class="rounded-lg bg-stone-50 p-4 dark:bg-stone-800/50">
                                <span class="text-sm font-medium text-stone-500 dark:text-stone-400">ICS Number</span>
                                <p class="mt-1 text-2xl font-bold text-stone-900 dark:text-stone-100">{{ $this->icsNumber->ics_number }}</p>
                            </div>
                            <div>
                                <span class="text-sm font-medium text-stone-500 dark:text-stone-400">ICS Type</span>
                                <span class="mt-2 inline-flex items-center rounded-full bg-purple-100 px-3 py-1 text-sm font-medium text-purple-800 dark:bg-purple-900/20 dark:text-purple-300">
                                    {{ $this->icsNumber->ics_type }}
                                </span>
                            </div>
                            <div>
                                <span class="text-sm font-medium text-stone-500 dark:text-stone-400">Est. Useful Life</span>
                                <p class="mt-2 text-base font-medium text-stone-900 dark:text-stone-100">{{ $this->icsNumber->estimated_useful_life ? $this->icsNumber->estimated_useful_life . ' years' : '—' }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Document Dates -->
                    <div class="border-t border-stone-200 pt-6 dark:border-stone-700">
                        <h4 class="mb-4 flex items-center text-sm font-semibold text-stone-700 dark:text-stone-300 uppercase tracking-wide">
                            <x-flux::icon.calendar-days class="mr-2 h-4 w-4" />
                            Document Dates
                        </h4>
                        <div class="space-y-4">
                            <div>
                                <span class="text-sm font-medium text-stone-500 dark:text-stone-400">Date Prepared</span>
                                <p class="mt-2 text-base font-medium text-stone-900 dark:text-stone-100">{{ $this->icsNumber->date_prepared?->format('F d, Y') ?? '—' }}</p>
                            </div>
                            <div>
                                <span class="text-sm font-medium text-stone-500 dark:text-stone-400">Date Accepted</span>
                                <p class="mt-2 text-base font-medium text-stone-900 dark:text-stone-100">{{ $this->icsNumber->date_accepted?->format('F d, Y') ?? '—' }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Quantity Information -->
                    <div class="border-t border-stone-200 pt-6 dark:border-stone-700">
                        <h4 class="mb-4 flex items-center text-sm font-semibold text-stone-700 dark:text-stone-300 uppercase tracking-wide">
                            <x-flux::icon.squares-2x2 class="mr-2 h-4 w-4" />
                            Quantity & Batches
                        </h4>
                        <div class="grid grid-cols-2 gap-4">
                            <div class="rounded-lg bg-orange-50 p-4 dark:bg-orange-900/20">
                                <span class="text-sm font-medium text-orange-600 dark:text-orange-400">Total Quantity</span>
                                <p class="mt-1 text-xl font-bold text-orange-700 dark:text-orange-300">{{ $this->icsNumber->quantity }}</p>
                                <p class="text-xs text-orange-600 dark:text-orange-400">{{ $this->icsNumber->contractItem?->itemSpecification?->itemCatalog?->unit ?? 'unit' }}(s)</p>
                            </div>
                            <div class="rounded-lg bg-indigo-50 p-4 dark:bg-indigo-900/20">
                                <span class="text-sm font-medium text-indigo-600 dark:text-indigo-400">Batches</span>
                                <p class="mt-1 text-xl font-bold text-indigo-700 dark:text-indigo-300">{{ $this->icsNumber->itemBatches->count() }}</p>
                                <p class="text-xs text-indigo-600 dark:text-indigo-400">{{ Str::plural('batch', $this->icsNumber->itemBatches->count()) }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Remarks -->
                    <div class="border-t border-stone-200 pt-6 dark:border-stone-700">
                        <h4 class="mb-4 flex items-center text-sm font-semibold text-stone-700 dark:text-stone-300 uppercase tracking-wide">
                            <x-flux::icon.chat-bubble-left-ellipsis class="mr-2 h-4 w-4" />
                            Remarks
                        </h4>
                        <div class="rounded-md bg-stone-50 p-4 dark:bg-stone-800/50">
                            <p class="text-sm text-stone-700 dark:text-stone-300">{{ $this->icsNumber->remarks ?: 'No remarks provided.' }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bottom Row: Full-width Batches & Components -->
    <div class="overflow-hidden rounded-lg border border-stone-200 bg-white shadow-sm dark:border-stone-700 dark:bg-stone-800">
        <div class="border-b border-stone-200 px-6 py-4 dark:border-stone-700">
            <h3 class="flex items-center font-semibold text-stone-800 dark:text-stone-200">
                <x-flux::icon.queue-list class="mr-2 h-5 w-5 text-stone-500 dark:text-stone-400" />
                Batches & Identification Data
                <span class="ml-3 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-stone-100 text-stone-800 dark:bg-stone-700 dark:text-stone-300">
                    {{ $this->icsNumber->itemBatches->count() }} {{ Str::plural('batch', $this->icsNumber->itemBatches->count()) }}
                </span>
            </h3>
        </div>
        <div class="px-6 py-5">
            @php
                $hasAnyIdentification = $this->icsNumber->itemBatches->some(fn($batch) => $batch->identification_data || $batch->components->isNotEmpty());
                $isDesktopComputer = str_contains(strtoupper($this->icsNumber->contractItem?->itemSpecification?->itemCatalog?->name ?? ''), 'DESKTOP COMPUTER');
            @endphp
            
            @if($this->icsNumber->itemBatches->isNotEmpty())
                @if($hasAnyIdentification)
                    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2 xl:grid-cols-3">
                        @foreach($this->icsNumber->itemBatches as $batch)
                            <div wire:key="show-batch-{{ $batch->id }}" class="rounded-lg border border-stone-300 bg-gradient-to-br from-white to-stone-50 dark:border-stone-600 dark:from-stone-800 dark:to-stone-800/50">
                                <div class="flex items-center justify-between p-4 bg-gradient-to-r from-stone-100 to-stone-50 dark:from-stone-700 dark:to-stone-700/50 rounded-t-lg border-b border-stone-200 dark:border-stone-600">
                                    <h4 class="font-semibold text-stone-800 dark:text-stone-200 flex items-center space-x-2">
                                        <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-white shadow-sm dark:bg-stone-600 text-sm font-bold text-stone-700 dark:text-stone-200">
                                            {{ $loop->iteration }}
                                        </span>
                                        <span>Batch #{{ $loop->iteration }}</span>
                                        @if ($isDesktopComputer)
                                            <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800 dark:bg-purple-800/20 dark:text-purple-300">
                                                <x-flux::icon.computer-desktop class="mr-1 h-3 w-3" />
                                                Desktop Computer
                                            </span>
                                        @endif
                                    </h4>
                                </div>
                                
                                <div class="p-4">
                                    <!-- Identification Data (Serial number, Asset tag, etc.) -->
                                    <div>
                                        <span class="text-sm font-medium text-stone-500 dark:text-stone-400">Identification Data</span>
                                        @if($batch->identification_data)
                                            <div class="mt-2 rounded-md bg-white p-3 border border-stone-200 dark:bg-stone-700 dark:border-stone-600">
                                                <p class="text-sm font-mono text-stone-900 dark:text-stone-100 break-all">{{ $batch->identification_data }}</p>
                                            </div>
                                        @else
                                            <p class="mt-2 italic text-stone-500">No identification data recorded for this batch.</p>
                                        @endif
                                    </div>

                                    @if ($isDesktopComputer && $batch->components->isNotEmpty())
                                        <div class="mt-4 border-t border-stone-200 pt-4 dark:border-stone-600">
                                            <h5 class="mb-3 flex items-center font-medium text-stone-800 dark:text-stone-200">
                                                <x-flux::icon.cpu-chip class="mr-2 h-4 w-4" />
                                                Components ({{ $batch->components->count() }})
                                            </h5>
                                            
                                            <div class="space-y-3">
                                                @foreach($batch->components as $component)
                                                    <div class="relative rounded-lg border border-stone-200 bg-white p-3 shadow-sm dark:border-stone-600 dark:bg-stone-700/50">
                                                        <div class="flex items-center justify-between mb-2">
                                                            <h6 class="font-medium text-stone-800 dark:text-stone-200 text-sm flex items-center">
                                                                <x-flux::icon.wrench-screwdriver class="mr-1 h-3 w-3 text-stone-500" />
                                                                {{ $component->component_type ?: 'Component #' . $loop->iteration }}
                                                            </h6>
                                                        </div>
                                                        <div class="grid grid-cols-1 gap-2 text-sm">
                                                            @if($component->serial_number)
                                                                <div class="flex items-center">
                                                                    <span class="text-stone-500 dark:text-stone-400 min-w-0 flex-shrink-0">S/N:</span>
                                                                    <span class="ml-2 font-mono text-stone-900 dark:text-stone-100">{{ $component->serial_number }}</span>
                                                                </div>
                                                            @endif
                                                            @if($component->brand || $component->model)
                                                                <div class="flex items-center">
                                                                    <span class="text-stone-500 dark:text-stone-400 min-w-0 flex-shrink-0">Brand/Model:</span>
                                                                    <span class="ml-2 text-stone-900 dark:text-stone-100">
                                                                        {{ collect([$component->brand, $component->model])->filter()->implode(' ') ?: '—' }}
                                                                    </span>
                                                                </div>
                                                            @endif
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-12">
                        <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-stone-100 dark:bg-stone-800">
                            <x-flux::icon.document-text class="h-8 w-8 text-stone-400" />
                        </div>
                        <h3 class="mt-4 text-base font-medium text-stone-900 dark:text-stone-100">No identification data recorded</h3>
                        <p class="mt-2 text-sm text-stone-500 dark:text-stone-400">No identification data has been recorded for any batches.</p>
                    </div>
                @endif
            @else
                <div class="text-center py-12">
                    <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-stone-100 dark:bg-stone-800">
                        <x-flux::icon.cube class="h-8 w-8 text-stone-400" />
                    </div>
                    <h3 class="mt-4 text-base font-medium text-stone-900 dark:text-stone-100">No batches recorded</h3>
                    <p class="mt-2 text-sm text-stone-500 dark:text-stone-400">No item batches have been recorded for this ICS.</p>
                </div>
            @endif
        </div>
    </div>

    <!-- Transfer History Section -->
    <div class="overflow-hidden rounded-lg border border-stone-200 bg-white shadow-sm dark:border-stone-700 dark:bg-stone-800">
        <div class="border-b border-stone-200 px-6 py-4 dark:border-stone-700">
            <h3 class="flex items-center font-semibold text-stone-800 dark:text-stone-200">
                <x-flux::icon.arrow-path class="mr-2 h-5 w-5 text-stone-500 dark:text-stone-400" />
                Transfer History
                @if ($this->icsNumber->transfers->isNotEmpty())
                    <span class="ml-3 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-stone-100 text-stone-800 dark:bg-stone-700 dark:text-stone-300">
                        {{ $this->icsNumber->transfers->count() }} {{ Str::plural('transfer', $this->icsNumber->transfers->count()) }}
                    </span>
                @endif
            </h3>
        </div>
        <div class="px-6 py-5">
            @if ($this->icsNumber->transfers->isNotEmpty())
                <div class="space-y-4">
                    @foreach ($this->icsNumber->transfers->sortByDesc('transfer_date') as $transfer)
                        <div wire:key="transfer-{{ $transfer->id }}" class="flex items-start space-x-4 p-4 bg-stone-50 dark:bg-stone-800/50 rounded-lg border border-stone-200 dark:border-stone-600">
                            <div class="flex-shrink-0 mt-1">
                                <div class="flex items-center justify-center w-8 h-8 rounded-full bg-blue-100 dark:bg-blue-900/20">
                                    <x-flux::icon.arrow-right class="h-4 w-4 text-blue-600 dark:text-blue-400" />
                                </div>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center justify-between mb-2">
                                    <div class="flex items-center space-x-2">
                                        <span class="text-sm font-medium text-stone-600 dark:text-stone-300">
                                            From: {{ $transfer->fromEmployee?->name ?? 'N/A' }}
                                        </span>
                                        <x-flux::icon.arrow-right class="h-4 w-4 text-stone-400" />
                                        <span class="text-sm font-medium text-stone-900 dark:text-stone-100">
                                            To: {{ $transfer->toEmployee?->name ?? 'N/A' }}
                                        </span>
                                    </div>
                                    <span class="text-sm font-medium text-stone-700 dark:text-stone-300 bg-white dark:bg-stone-700 px-3 py-1 rounded-full border border-stone-200 dark:border-stone-600">
                                        {{ $transfer->transfer_date->format('F d, Y') }}
                                    </span>
                                </div>
                                
                                @if ($transfer->fromEmployee?->division || $transfer->toEmployee?->division)
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                                        @if ($transfer->fromEmployee?->division)
                                            <div>
                                                <span class="text-stone-500 dark:text-stone-400">From Division:</span>
                                                <span class="ml-2 text-stone-700 dark:text-stone-300">{{ $transfer->fromEmployee->division->name }}</span>
                                            </div>
                                        @endif
                                        @if ($transfer->toEmployee?->division)
                                            <div>
                                                <span class="text-stone-500 dark:text-stone-400">To Division:</span>
                                                <span class="ml-2 text-stone-700 dark:text-stone-300">{{ $transfer->toEmployee->division->name }}</span>
                                            </div>
                                        @endif
                                    </div>
                                @endif
                                
                                @if ($transfer->remarks)
                                    <div class="mt-3 p-3 bg-white dark:bg-stone-700 rounded-md border border-stone-200 dark:border-stone-600">
                                        <span class="text-xs font-medium text-stone-500 dark:text-stone-400 uppercase tracking-wide">Remarks:</span>
                                        <p class="mt-1 text-sm text-stone-700 dark:text-stone-300">{{ $transfer->remarks }}</p>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-12">
                    <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-stone-100 dark:bg-stone-800">
                        <x-flux::icon.clock class="h-8 w-8 text-stone-400" />
                    </div>
                    <h3 class="mt-4 text-base font-medium text-stone-900 dark:text-stone-100">No transfers recorded</h3>
                    <p class="mt-2 text-sm text-stone-500 dark:text-stone-400">This item has remained with its original assignee since creation.</p>
                </div>
            @endif
        </div>
    </div>
</div>
</div> 
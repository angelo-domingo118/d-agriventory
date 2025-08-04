<?php

use App\Models\IdrNumber;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Computed;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new #[Layout('components.layouts.app')] class extends Component {
    use WithPagination;

    public IdrNumber $idrNumber;

    public int $perPage = 10;

    public function mount(IdrNumber $idrNumber): void
    {
        if (!auth()->user()->hasAdminPermission('view_inventory')) {
            abort(403);
        }

        $this->idrNumber = $idrNumber->load([
            'receivedBy',
            'receivedFrom',
            'contractItem.itemSpecification.itemCatalog.secondaryCategory.primaryCategory',
            'itemBatches',
        ])->loadCount('itemBatches');
    }

    #[Computed]
    public function batches()
    {
        return $this->idrNumber->itemBatches()->paginate($this->perPage, ['*'], 'batchesPage');
    }
}; ?>

<div class="space-y-8">
     <div class="border-b border-stone-200 pb-5 dark:border-stone-700">
        <div class="flex items-center justify-between">
            <div>
                <flux:breadcrumbs class="text-2xl font-semibold">
                    <flux:breadcrumbs.item :href="route('admin.dashboard')" wire:navigate icon="home" class="text-xl sm:text-2xl font-semibold text-stone-700 dark:text-stone-300" />
                    <flux:breadcrumbs.item href="#" class="text-xl sm:text-2xl font-semibold text-stone-500 dark:text-stone-400">Inventory</flux:breadcrumbs.item>
                    <flux:breadcrumbs.item :href="route('admin.inventory.idr.index')" wire:navigate class="text-xl sm:text-2xl font-semibold text-stone-500 dark:text-stone-400">IDR Management</flux:breadcrumbs.item>
                    <flux:breadcrumbs.item class="text-xl sm:text-2xl font-semibold text-stone-900 dark:text-stone-100">IDR Details: {{ $this->idrNumber->number }}</flux:breadcrumbs.item>
                </flux:breadcrumbs>
                <p class="mt-1 text-sm text-stone-600 dark:text-stone-400">
                    Viewing full details for Inspection and Delivery Report #{{ $this->idrNumber->number }}.
                </p>
            </div>
            <div class="flex items-center gap-x-4">
                <flux:button variant="ghost" :href="route('admin.inventory.idr.index')" wire:navigate>
                    &larr; Back to List
                </flux:button>
                <flux:button variant="primary" :href="route('admin.inventory.idr.edit', $idrNumber)" wire:navigate>
                    Edit IDR
                </flux:button>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-8 lg:grid-cols-4">
        <div class="lg:col-span-3">
            <div class="space-y-8">
                <div class="overflow-hidden rounded-lg border border-stone-200 bg-white shadow-sm dark:border-stone-700 dark:bg-stone-800">
                    <div class="border-b border-stone-200 px-4 py-3 dark:border-stone-700">
                        <h3 class="font-semibold text-stone-800 dark:text-stone-200">Item Information</h3>
                    </div>
                    <div class="grid grid-cols-1 gap-y-4 p-6 md:grid-cols-2">
                        <div class="sm:col-span-1">
                            <dt class="text-sm font-medium text-stone-500 dark:text-stone-400">Item Name</dt>
                            <p class="mt-1 text-stone-900 dark:text-stone-100">{{ $this->idrNumber->contractItem?->itemSpecification?->itemCatalog?->name ?? '—' }}</p>
                        </div>
                        <div class="sm:col-span-1">
                            <dt class="text-sm font-medium text-stone-500 dark:text-stone-400">Item Code</dt>
                            <p class="mt-1 text-stone-900 dark:text-stone-100">{{ $this->idrNumber->contractItem?->itemSpecification?->itemCatalog?->code ?? '—' }}</p>
                        </div>
                        <div class="sm:col-span-1">
                            @php
                                $secondary = $this->idrNumber->contractItem?->itemSpecification?->itemCatalog?->secondaryCategory;
                            @endphp
                            <dt class="text-sm font-medium text-stone-500 dark:text-stone-400">Category</dt>
                            <p class="mt-1 text-stone-900 dark:text-stone-100">
                                {{ $secondary?->primaryCategory?->name ?? 'N/A' }} / {{ $secondary?->name ?? 'N/A' }}
                            </p>
                        </div>
                        <div class="sm:col-span-1">
                            <dt class="text-sm font-medium text-stone-500 dark:text-stone-400">Unit</dt>
                            <p class="mt-1 text-stone-900 dark:text-stone-100">{{ $this->idrNumber->contractItem?->itemSpecification?->itemCatalog?->unit ?? 'unit' }}</p>
                        </div>
                        <div class="sm:col-span-1">
                            <dt class="text-sm font-medium text-stone-500 dark:text-stone-400">Unit Price</dt>
                            <p class="mt-1 text-stone-900 dark:text-stone-100">₱{{ number_format($this->idrNumber->contractItem?->unit_price ?? 0, 2) }}</p>
                        </div>
                        <div class="sm:col-span-1">
                            <dt class="text-sm font-medium text-stone-500 dark:text-stone-400">Inventory Code</dt>
                            <p class="mt-1 text-stone-900 dark:text-stone-100">{{ $this->idrNumber->inventory_code }}</p>
                        </div>
                        <div class="sm:col-span-1">
                            <dt class="text-sm font-medium text-stone-500 dark:text-stone-400">ORS Number</dt>
                            <p class="mt-1 text-stone-900 dark:text-stone-100">{{ $this->idrNumber->ors }}</p>
                        </div>
                        <div class="sm:col-span-1">
                            <dt class="text-sm font-medium text-stone-500 dark:text-stone-400">Date Prepared</dt>
                            <p class="mt-1 text-stone-900 dark:text-stone-100">{{ $this->idrNumber->date_prepared->format('F d, Y') }}</p>
                        </div>
                        <div class="sm:col-span-1">
                            <dt class="text-sm font-medium text-stone-500 dark:text-stone-400">Date Accepted</dt>
                            <p class="mt-1 text-stone-900 dark:text-stone-100">{{ $this->idrNumber->date_accepted->format('F d, Y') }}</p>
                        </div>
                        <div class="sm:col-span-1">
                            <dt class="text-sm font-medium text-stone-500 dark:text-stone-400">IDR Date</dt>
                            <p class="mt-1 text-stone-900 dark:text-stone-100">{{ $this->idrNumber->date->format('F d, Y') }}</p>
                        </div>
                        <div class="sm:col-span-1">
                            <dt class="text-sm font-medium text-stone-500 dark:text-stone-400">Remarks</dt>
                            <p class="mt-1 text-stone-900 dark:text-stone-100">{{ $this->idrNumber->remarks ?: 'No remarks provided.' }}</p>
                        </div>
                    </div>
                </div>

                <div class="overflow-hidden rounded-lg border border-stone-200 bg-white shadow-sm dark:border-stone-700 dark:bg-stone-800">
                    <div class="border-b border-stone-200 px-4 py-3 dark:border-stone-700"><h3 class="font-semibold text-stone-800 dark:text-stone-200">Batches / Serial Numbers</h3></div>
                    <div class="p-6">
                        @if($this->batches->isNotEmpty())
                            <ul class="space-y-2">
                                @foreach($this->batches as $batch)
                                    <li wire:key="show-batch-{{ $batch->id }}" class="text-stone-600 dark:text-stone-300">
                                        <strong>Batch #{{ ($this->batches->currentPage() - 1) * $this->batches->perPage() + $loop->iteration }}:</strong> {{ $batch->identification_data ?: 'No data' }}
                                    </li>
                                @endforeach
                            </ul>
                            <div class="mt-4">
                                {{ $this->batches->links(data: ['scrollTo' => false]) }}
                            </div>
                        @else
                            <p class="italic text-stone-500">No item batches recorded.</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="lg:col-span-1">
            <div class="space-y-8">
                <div class="overflow-hidden rounded-lg border border-stone-200 bg-white shadow-sm dark:border-stone-700 dark:bg-stone-800">
                    <div class="border-b border-stone-200 px-4 py-3 dark:border-stone-700"><h3 class="font-semibold text-stone-800 dark:text-stone-200">Personnel</h3></div>
                    <div class="space-y-4 p-6">
                        <div><span class="text-sm font-medium text-stone-500 dark:text-stone-400">Assigned To (Stock Officer)</span><p class="mt-1 text-stone-900 dark:text-stone-100">{{ $this->idrNumber->assignedEmployee?->name ?? 'Unassigned' }}</p></div>
                        <div><span class="text-sm font-medium text-stone-500 dark:text-stone-400">Approving Official</span><p class="mt-1 text-stone-900 dark:text-stone-100">{{ $this->idrNumber->approvingEmployee?->name ?? '—' }}</p></div>
                        <div><span class="text-sm font-medium text-stone-500 dark:text-stone-400">Received By</span><p class="mt-1 text-stone-900 dark:text-stone-100">{{ $this->idrNumber->receivedBy?->name ?? '—' }}</p></div>
                        <div><span class="text-sm font-medium text-stone-500 dark:text-stone-400">Issued By</span><p class="mt-1 text-stone-900 dark:text-stone-100">{{ $this->idrNumber->receivedFrom?->name ?? '—' }}</p></div>
                    </div>
                </div>

                <div class="overflow-hidden rounded-lg border border-stone-200 bg-white shadow-sm dark:border-stone-700 dark:bg-stone-800">
                    <div class="border-b border-stone-200 px-4 py-3 dark:border-stone-700"><h3 class="font-semibold text-stone-800 dark:text-stone-200">Document Details</h3></div>
                    <div class="space-y-4 p-6">
                        <div><span class="text-sm font-medium text-stone-500 dark:text-stone-400">Total Quantity</span><p class="mt-1 text-stone-900 dark:text-stone-100">{{ $this->idrNumber->quantity }} {{ Str::plural($this->idrNumber->contractItem?->itemSpecification?->itemCatalog?->unit ?? 'unit', $this->idrNumber->quantity) }}</p></div>
                        <div><span class="text-sm font-medium text-stone-500 dark:text-stone-400">Batches</span><p class="mt-1 text-stone-900 dark:text-stone-100">{{ $this->idrNumber->item_batches_count }}</p></div>
                        <div><span class="text-sm font-medium text-stone-500 dark:text-stone-400">Unit Cost</span><p class="mt-1 text-stone-900 dark:text-stone-100">₱{{ number_format($this->idrNumber->contractItem?->unit_price ?? 0, 2) }}</p></div>
                        <div><span class="text-sm font-medium text-stone-500 dark:text-stone-400">Inventory Code</span><p class="mt-1 text-stone-900 dark:text-stone-100">{{ $this->idrNumber->inventory_code }}</p></div>
                        <div><span class="text-sm font-medium text-stone-500 dark:text-stone-400">ORS Number</span><p class="mt-1 text-stone-900 dark:text-stone-100">{{ $this->idrNumber->ors }}</p></div>
                        <div><span class="text-sm font-medium text-stone-500 dark:text-stone-400">Date Prepared</span><p class="mt-1 text-stone-900 dark:text-stone-100">{{ $this->idrNumber->date_prepared->format('F d, Y') }}</p></div>
                        <div><span class="text-sm font-medium text-stone-500 dark:text-stone-400">Date Accepted</span><p class="mt-1 text-stone-900 dark:text-stone-100">{{ $this->idrNumber->date_accepted->format('F d, Y') }}</p></div>
                        <div><span class="text-sm font-medium text-stone-500 dark:text-stone-400">IDR Date</span><p class="mt-1 text-stone-900 dark:text-stone-100">{{ $this->idrNumber->date->format('F d, Y') }}</p></div>
                        <div class="pt-2"><span class="text-sm font-medium text-stone-500 dark:text-stone-400">Remarks</span><p class="mt-1 text-stone-900 dark:text-stone-100">{{ $this->idrNumber->remarks ?: 'No remarks provided.' }}</p></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div> 
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

    public ?int $to_employee_id = null;
    public string $transfer_date = '';

    public function mount(IcsNumber $icsNumber): void
    {
        if (!auth()->user()->hasAdminPermission('view_inventory')) {
            abort(403);
        }

        $this->loadIcsData();
        $this->transfer_date = now()->format('Y-m-d');
    }

    #[Computed]
    public function employees()
    {
        // Exclude the current custodian from the list of potential transferees
        return Employee::where('id', '!=', $this->icsNumber->assigned_employee_id)
            ->orderBy('name')
            ->get();
    }

    public function transfer(): void
    {
        if (!auth()->user()->hasAdminPermission('update_inventory')) {
            abort(403);
        }

        $validated = $this->validate([
            'to_employee_id' => ['required', 'integer', 'exists:employees,id'],
            'transfer_date' => ['required', 'date'],
        ]);

        DB::transaction(function () use ($validated) {
            // 1. Create the transfer record
            IcsTransfer::create([
                'ics_number_id' => $this->icsNumber->id,
                'from_employee_id' => $this->icsNumber->assigned_employee_id,
                'to_employee_id' => $validated['to_employee_id'],
                'transfer_date' => $validated['transfer_date'],
            ]);

            // 2. Update the assigned employee on the ICS record
            $this->icsNumber->update([
                'assigned_employee_id' => $validated['to_employee_id'],
            ]);
        });

        $this->dispatch('close-modal', 'transfer-item-modal');
        $this->loadIcsData(); // Reload data to show the new transfer
        $this->dispatch('item-transferred');
        $this->reset('to_employee_id', 'transfer_date');
    }

    protected function loadIcsData(): void
    {
        $this->icsNumber->load([
            'assignedEmployee.division',
            'assignedEmployee.position',
            'contractItem.contract.supplier',
            'contractItem.itemSpecification.catalogItem.secondaryCategory.primaryCategory',
            'itemBatches.components',
            'transfers.fromEmployee',
            'transfers.toEmployee',
        ]);
    }
}; ?>

<div>
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-stone-900 dark:text-stone-100">
                ICS Details: {{ $this->icsNumber->ics_number }}
            </h1>
            <p class="mt-1 text-sm text-stone-600 dark:text-stone-400">
                Viewing full details for Inventory Custodian Slip #{{ $this->icsNumber->ics_number }}.
            </p>
        </div>
        <div class="flex items-center gap-x-4">
            <flux:button variant="ghost" :href="route('admin.inventory.ics.edit', $icsNumber)" wire:navigate>
                Edit Item
            </flux:button>
            <flux:button variant="primary" x-data x-on:click.prevent="$dispatch('open-modal', 'transfer-item-modal')">
                Transfer Item
            </flux:button>
            <flux:button variant="ghost" :href="route('admin.inventory.ics.index')" wire:navigate>
                &larr; Back to ICS List
            </flux:button>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-8 lg:grid-cols-3">
        <!-- Main Content -->
        <div class="lg:col-span-2">
            <div class="space-y-8">
                <!-- Item & Contract Details -->
                <div class="rounded-lg border border-stone-200 bg-white dark:border-stone-700 dark:bg-stone-800">
                    <div class="border-b border-stone-200 px-4 py-3 dark:border-stone-700">
                        <h3 class="font-semibold text-stone-800 dark:text-stone-200">Item Information</h3>
                    </div>
                    <div class="grid grid-cols-1 gap-y-4 p-6 md:grid-cols-2">
                        <div>
                            <span class="text-sm font-medium text-stone-500 dark:text-stone-400">Item Name</span>
                            <p class="mt-1 text-stone-900 dark:text-stone-100">{{ $this->icsNumber->contractItem?->itemSpecification?->catalogItem?->name ?? 'N/A' }}</p>
                        </div>
                        <div>
                            <span class="text-sm font-medium text-stone-500 dark:text-stone-400">Primary Category</span>
                            <p class="mt-1 text-stone-900 dark:text-stone-100">{{ $this->icsNumber->contractItem?->itemSpecification?->catalogItem?->secondaryCategory?->primaryCategory?->name ?? 'N/A' }}</p>
                        </div>
                        <div>
                            <span class="text-sm font-medium text-stone-500 dark:text-stone-400">Secondary Category</span>
                            <p class="mt-1 text-stone-900 dark:text-stone-100">{{ $this->icsNumber->contractItem?->itemSpecification?->catalogItem?->secondaryCategory?->name ?? 'N/A' }}</p>
                        </div>
                        <div>
                            <span class="text-sm font-medium text-stone-500 dark:text-stone-400">Unit</span>
                            <p class="mt-1 text-stone-900 dark:text-stone-100">{{ $this->icsNumber->contractItem?->itemSpecification?->catalogItem?->unit ?? 'N/A' }}</p>
                        </div>
                        <div class="md:col-span-2">
                            <span class="text-sm font-medium text-stone-500 dark:text-stone-400">Detailed Specifications</span>
                            <p class="mt-1 text-stone-900 dark:text-stone-100">{{ $this->icsNumber->contractItem?->itemSpecification?->detailed_specifications ?: 'N/A' }}</p>
                        </div>
                         <div class="md:col-span-2 border-t border-stone-200 dark:border-stone-700 pt-4">
                            <span class="text-sm font-medium text-stone-500 dark:text-stone-400">Contract</span>
                            <p class="mt-1 text-stone-900 dark:text-stone-100">
                                {{ $this->icsNumber->contractItem?->contract?->contract_po_ib_number ?? 'N/A' }}
                                <span class="text-stone-500 dark:text-stone-400"> (Supplier: {{ $this->icsNumber->contractItem?->contract?->supplier?->name ?? 'N/A' }})</span>
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Item Components -->
                @if($this->icsNumber->itemBatches->flatMap->components->isNotEmpty())
                    <div class="rounded-lg border border-stone-200 bg-white dark:border-stone-700 dark:bg-stone-800">
                        <div class="border-b border-stone-200 px-4 py-3 dark:border-stone-700">
                            <h3 class="font-semibold text-stone-800 dark:text-stone-200">Item Components</h3>
                        </div>
                        <div class="p-4">
                            <ul class="divide-y divide-stone-200 dark:divide-stone-700">
                                @foreach($this->icsNumber->itemBatches->flatMap->components as $component)
                                    <li class="py-3">
                                        <p class="font-medium text-stone-900 dark:text-stone-100">{{ $component->name }}</p>
                                        <p class="text-sm text-stone-600 dark:text-stone-400">Serial: {{ $component->serial_number ?: 'N/A' }}</p>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        <!-- Sidebar Info -->
        <div class="lg:col-span-1">
            <div class="space-y-8">
                <!-- Custodian Details -->
                <div class="rounded-lg border border-stone-200 bg-white dark:border-stone-700 dark:bg-stone-800">
                    <div class="border-b border-stone-200 px-4 py-3 dark:border-stone-700">
                        <h3 class="font-semibold text-stone-800 dark:text-stone-200">Custodian Information</h3>
                    </div>
                    <div class="space-y-4 p-6">
                        <div>
                            <span class="text-sm font-medium text-stone-500 dark:text-stone-400">Current Custodian</span>
                            <p class="mt-1 text-stone-900 dark:text-stone-100">{{ $this->icsNumber->assignedEmployee?->name ?? 'N/A' }}</p>
                        </div>
                        <div>
                            <span class="text-sm font-medium text-stone-500 dark:text-stone-400">Position</span>
                            <p class="mt-1 text-stone-900 dark:text-stone-100">{{ $this->icsNumber->assignedEmployee?->position?->name ?? 'N/A' }}</p>
                        </div>
                        <div>
                            <span class="text-sm font-medium text-stone-500 dark:text-stone-400">Division/Office</span>
                            <p class="mt-1 text-stone-900 dark:text-stone-100">{{ $this->icsNumber->assignedEmployee?->division?->name ?? 'N/A' }}</p>
                        </div>
                    </div>
                </div>

                <!-- Document Details -->
                <div class="rounded-lg border border-stone-200 bg-white dark:border-stone-700 dark:bg-stone-800">
                    <div class="border-b border-stone-200 px-4 py-3 dark:border-stone-700">
                        <h3 class="font-semibold text-stone-800 dark:text-stone-200">Document Details</h3>
                    </div>
                    <div class="space-y-4 p-6">
                        <div>
                            <span class="text-sm font-medium text-stone-500 dark:text-stone-400">Quantity</span>
                            <p class="mt-1 text-stone-900 dark:text-stone-100">{{ $this->icsNumber->quantity }}</p>
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
                            <p class="mt-1 text-stone-900 dark:text-stone-100">{{ $this->icsNumber->remarks ?: 'N/A' }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Transfer History -->
    @if ($this->icsNumber->transfers->isNotEmpty())
        <div class="mt-8 rounded-lg border border-stone-200 bg-white dark:border-stone-700 dark:bg-stone-800">
            <div class="border-b border-stone-200 px-4 py-3 dark:border-stone-700">
                <h3 class="font-semibold text-stone-800 dark:text-stone-200">Transfer History</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-stone-200 dark:divide-stone-700">
                    <thead>
                        <tr>
                            <th scope="col" class="px-6 py-3 bg-stone-100 dark:bg-stone-700 text-left text-xs font-medium text-stone-500 dark:text-stone-300 uppercase tracking-wider">Date</th>
                            <th scope="col" class="px-6 py-3 bg-stone-100 dark:bg-stone-700 text-left text-xs font-medium text-stone-500 dark:text-stone-300 uppercase tracking-wider">From</th>
                            <th scope="col" class="px-6 py-3 bg-stone-100 dark:bg-stone-700 text-left text-xs font-medium text-stone-500 dark:text-stone-300 uppercase tracking-wider">To</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-stone-800 divide-y divide-stone-200 dark:divide-stone-700">
                        @foreach($this->icsNumber->transfers as $transfer)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-stone-600 dark:text-stone-300">{{ $transfer->transfer_date?->format('F d, Y') ?? 'N/A' }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-stone-600 dark:text-stone-300">{{ $transfer->fromEmployee?->name ?? 'N/A' }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-stone-600 dark:text-stone-300">{{ $transfer->toEmployee?->name ?? 'N/A' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <!-- Transfer Modal -->
    <flux:modal name="transfer-item-modal" focusable class="max-w-lg">
        <form wire:submit.prevent="transfer" class="p-6">
            <h2 class="text-lg font-medium text-stone-900 dark:text-stone-100">
                Transfer Item
            </h2>
            <p class="mt-1 text-sm text-stone-600 dark:text-stone-400">
                Select a new employee to transfer this item to. This action will be logged.
            </p>

            <div class="mt-6 space-y-4">
                <flux:select wire:model="to_employee_id" label="Transfer To" required>
                    <option value="">Select an employee</option>
                    @foreach($this->employees as $employee)
                        <option value="{{ $employee->id }}">{{ $employee->name }}</option>
                    @endforeach
                </flux:select>
                <flux:input wire:model="transfer_date" type="date" label="Transfer Date" required />
            </div>

            <div class="mt-6 flex justify-end gap-x-4">
                <flux:modal.close>
                    <flux:button variant="ghost" type="button">
                        Cancel
                    </flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary">
                    Confirm Transfer
                </flux:button>
            </div>
        </form>
    </flux:modal>
    <x-action-message class="me-3" on="item-transferred">
        {{ __('Item transferred successfully.') }}
    </x-action-message>
</div> 
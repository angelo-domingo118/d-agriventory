<?php

use App\Models\Contract;
use App\Models\ContractItem;
use App\Models\Employee;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use App\Models\IcsNumber;
use Illuminate\Support\Facades\DB;

new #[Layout('components.layouts.app')] class extends Component {
    // Form state
    public string $ics_number = '';
    public ?int $contract_id = null;
    public ?int $contract_item_id = null;
    public ?int $assigned_employee_id = null;
    public string $ics_type = 'SPLV';
    public ?int $quantity = null;
    public ?int $estimated_useful_life = null;
    public ?string $date_prepared = null;
    public string $remarks = '';

    public function mount(): void
    {
        if (!auth()->user()->hasAdminPermission('create_inventory')) {
            abort(403);
        }
    }

    #[Computed]
    public function contracts()
    {
        return Contract::with('supplier:id,name')
            ->orderBy('contract_po_ib_number')
            ->get(['id', 'contract_po_ib_number', 'supplier_id']);
    }

    #[Computed]
    public function contractItems()
    {
        if (!$this->contract_id) {
            return collect();
        }

        return ContractItem::where('contract_id', $this->contract_id)
            ->with('itemSpecification.catalogItem:id,name')
            ->get();
    }

    #[Computed]
    public function employees()
    {
        return Employee::orderBy('name')->get(['id', 'name']);
    }

    public function store(): void
    {
        $validated = $this->validate([
            'ics_number' => ['required', 'string', 'max:255', Rule::unique('ics_number', 'ics_number')],
            'contract_id' => ['required', 'integer', Rule::exists('contracts', 'id')],
            'contract_item_id' => ['required', 'integer', Rule::exists('contract_items', 'id')],
            'assigned_employee_id' => ['required', 'integer', Rule::exists('employees', 'id')],
            'ics_type' => ['required', 'string', Rule::in(['SPLV', 'SPHV'])],
            'quantity' => ['required', 'integer', 'min:1'],
            'estimated_useful_life' => ['required', 'integer', 'min:1'],
            'date_prepared' => ['required', 'date'],
            'remarks' => ['nullable', 'string'],
        ]);

        DB::transaction(function () use ($validated) {
            IcsNumber::create([
                'ics_number' => $validated['ics_number'],
                'assigned_employee_id' => $validated['assigned_employee_id'],
                'contract_item_id' => $validated['contract_item_id'],
                'ics_type' => $validated['ics_type'],
                'quantity' => $validated['quantity'],
                'estimated_useful_life' => $validated['estimated_useful_life'],
                'date_prepared' => $validated['date_prepared'],
                'date_accepted' => $validated['date_prepared'], // Set accepted date same as prepared
                'remarks' => $validated['remarks'],
            ]);
        });

        session()->flash('success', "ICS record created successfully.");

        $this->redirect(route('admin.inventory.ics.index'), navigate: true);
    }
}; ?>

<div class="overflow-hidden rounded-lg border border-stone-200 bg-white shadow-sm dark:border-stone-700 dark:bg-stone-800">
    <form wire:submit="store">
        <div class="border-b border-stone-200 p-6 dark:border-stone-700">
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-semibold text-stone-900 dark:text-stone-100">
                    Create New ICS Record
                </h1>
                <div class="flex items-center gap-x-4">
                    <x-action-message class="me-3" on="ics-created">
                        {{ __('Record saved successfully.') }}
                    </x-action-message>
                    <flux:button variant="ghost" :href="route('admin.inventory.ics.index')" wire:navigate>
                        Cancel
                    </flux:button>
                    <flux:button type="submit" variant="primary">
                        Save Record
                    </flux:button>
                </div>
            </div>
        </div>

        <div class="p-6">
            <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
                <div class="md:col-span-2">
                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                        <flux:input wire:model="ics_number" label="ICS Number" required />

                        <flux:select wire:model.live="contract_id" label="Contract" required>
                            <option value="">Select a contract</option>
                            @foreach($this->contracts as $contract)
                                <option value="{{ $contract->id }}">
                                    {{ $contract->contract_po_ib_number }} - {{ $contract->supplier->name }}
                                </option>
                            @endforeach
                        </flux:select>

                        <flux:select wire:model="contract_item_id" label="Item from Contract" required :disabled="!$this->contract_id">
                            <option value="">Select an item</option>
                            @foreach($this->contractItems as $item)
                                <option value="{{ $item->id }}">
                                    {{ $item->itemSpecification->catalogItem->name }}
                                </option>
                            @endforeach
                        </flux:select>

                        <flux:select wire:model="assigned_employee_id" label="Assign to Employee" required>
                            <option value="">Select an employee</option>
                            @foreach($this->employees as $employee)
                                <option value="{{ $employee->id }}">{{ $employee->name }}</option>
                            @endforeach
                        </flux:select>

                        <flux:input wire:model="quantity" type="number" label="Quantity" min="1" required />

                        <flux:input wire:model="estimated_useful_life" type="number" label="Estimated Useful Life (Years)" min="1" required />
                    </div>
                </div>
                <div class="md:col-span-1">
                    <div class="space-y-6">
                        <flux:select wire:model="ics_type" label="ICS Type" required>
                            <option value="SPLV">Small Value (SPLV)</option>
                            <option value="SPHV">High Value (SPHV)</option>
                        </flux:select>

                        <flux:input wire:model="date_prepared" type="date" label="Date Prepared" required />

                        <flux:textarea wire:model="remarks" label="Remarks" rows="5" />
                    </div>
                </div>
            </div>
        </div>
    </form>
</div> 
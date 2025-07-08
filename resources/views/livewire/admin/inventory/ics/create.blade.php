<?php

use App\Models\Contract;
use App\Models\ContractItem;
use App\Models\Employee;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use App\Models\IcsNumber;
use Illuminate\Support\Facades\DB;
use App\Models\IcsItemBatch;

new #[Layout('components.layouts.app')] class extends Component {
    // Form state
    public string $ics_number = '';
    public ?int $contract_id = null;
    public ?int $contract_item_id = null;
    public ?int $assigned_employee_id = null;
    public string $ics_type = 'SPLV';
    public int $quantity = 1;
    public ?int $estimated_useful_life = null;
    public ?string $date_prepared = null;
    public ?string $date_accepted = null;
    public string $remarks = '';
    public bool $use_current_date = true;
    public bool $use_current_date_accepted = true;

    // Display only property
    public ?float $unit_price = 0.0;
    public bool $isParItem = false;

    public array $batches = [];

    public Collection $allContracts;
    public Collection $allEmployees;


    public function mount(): void
    {
        if (!auth()->user()->hasAdminPermission('create_inventory')) {
            abort(403);
        }
        
        $this->generateIcsNumber();
        $this->date_prepared = now()->format('m/d/Y');
        $this->date_accepted = now()->format('m/d/Y');
        
        // Pre-load data for select dropdowns
        $this->allContracts = Contract::with('supplier:id,name')
            ->orderBy('contract_po_ib_number')
            ->get(['id', 'contract_po_ib_number', 'supplier_id']);

        $this->allEmployees = Employee::orderBy('name')
            ->get(['id', 'name']);

        // Start with one batch
        $this->updatedQuantity($this->quantity);
    }

    public function generateIcsNumber(): void
    {
        $prefix = 'ICS-' . now()->format('Y-m-');
        $lastIcs = IcsNumber::where('ics_number', 'like', $prefix . '%')
            ->orderBy('ics_number', 'desc')
            ->first();

        if ($lastIcs) {
            $lastNumber = (int) substr($lastIcs->ics_number, -4);
            $newNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $newNumber = '0001';
        }

        $this->ics_number = $prefix . $newNumber;
    }

    #[Computed]
    public function contractItems()
    {
        if (!$this->contract_id) {
            return collect();
        }

        return ContractItem::where('contract_id', $this->contract_id)
            ->with('itemSpecification.itemCatalog:id,name')
            ->get();
    }

    #[Computed]
    public function selectedContractItem()
    {
        if ($this->contract_item_id) {
            return ContractItem::find($this->contract_item_id);
        }
        return null;
    }

    public function updatedContractItemId($value): void
    {
        $item = $this->selectedContractItem;
        $this->unit_price = $item?->unit_price ?? 0.0;

        if ($this->unit_price >= 50000) {
            $this->isParItem = true;
            $this->ics_type = '';
        } elseif ($this->unit_price > 5000) {
            $this->isParItem = false;
            $this->ics_type = 'SPHV';
        } else {
            $this->isParItem = false;
            $this->ics_type = 'SPLV';
        }
    }

    public function updatedQuantity($value): void
    {
        $value = (int) $value;
        if ($value < 1) {
            $this->quantity = 1;
            $value = 1;
        }

        $currentCount = count($this->batches);

        if ($value > $currentCount) {
            for ($i = 0; $i < $value - $currentCount; $i++) {
                $this->addBatch();
            }
        } elseif ($value < $currentCount) {
            array_splice($this->batches, $value);
        }
    }

    public function addBatch(): void
    {
        $this->batches[] = [
            'id' => null, // Not needed for creation
            'identification_data' => null,
            'components' => [['id' => null, 'component_type' => '', 'brand' => '', 'model' => '', 'serial_number' => '']],
        ];
    }

    public function removeBatch(int $index): void
    {
        if (isset($this->batches[$index])) {
            array_splice($this->batches, $index, 1);
        }
        $this->quantity = count($this->batches);
    }

    public function addComponent(int $batchIndex): void
    {
        $this->batches[$batchIndex]['components'][] = ['id' => null, 'component_type' => '', 'brand' => '', 'model' => '', 'serial_number' => ''];
    }

    public function removeComponent(int $batchIndex, int $componentIndex): void
    {
        if (isset($this->batches[$batchIndex]['components'][$componentIndex])) {
            array_splice($this->batches[$batchIndex]['components'], $componentIndex, 1);
        }
    }

    public function updatedUseCurrentDate(bool $value): void
    {
        if ($value) {
            $this->date_prepared = now()->format('m/d/Y');
        } else {
            $this->date_prepared = null;
        }
    }

    public function updatedUseCurrentDateAccepted(bool $value): void
    {
        if ($value) {
            $this->date_accepted = now()->format('m/d/Y');
        } else {
            $this->date_accepted = null;
        }
    }

    public function store(): void
    {
        $validated = $this->validate([
            'ics_number' => ['required', 'string', 'max:255', Rule::unique('ics_number', 'ics_number')],
            'contract_id' => ['required', 'integer', Rule::exists('contracts', 'id')],
            'contract_item_id' => ['required', 'integer', Rule::exists('contract_items', 'id')],
            'assigned_employee_id' => ['required', 'integer', Rule::exists('employees', 'id')],
            'ics_type' => ['required', 'string', Rule::in(['SPLV', 'SPHV'])],
            'quantity' => ['required', 'integer', 'min:1', function ($attribute, $value, $fail) {
                if ($value != count($this->batches)) {
                    $fail("The quantity ($value) must match the number of batches (" . count($this->batches) . ").");
                }
            }],
            'estimated_useful_life' => ['required', 'integer', 'min:1'],
            'date_prepared' => ['required', 'date_format:m/d/Y'],
            'date_accepted' => ['required', 'date_format:m/d/Y'],
            'remarks' => ['nullable', 'string'],
            'batches.*.components.*.component_type' => ['required_with:batches.*.components.*.serial_number', 'nullable', 'string', 'max:255'],
            'batches.*.components.*.brand' => ['nullable', 'string', 'max:255'],
            'batches.*.components.*.model' => ['nullable', 'string', 'max:255'],
            'batches.*.components.*.serial_number' => ['nullable', 'string', 'max:255'],
        ]);

        DB::transaction(function () use ($validated) {
            $prepared_date = Carbon::createFromFormat('m/d/Y', $validated['date_prepared'])->format('Y-m-d');
            $accepted_date = Carbon::createFromFormat('m/d/Y', $validated['date_accepted'])->format('Y-m-d');

            $ics = IcsNumber::create([
                'ics_number' => $validated['ics_number'],
                'assigned_employee_id' => $validated['assigned_employee_id'],
                'contract_item_id' => $validated['contract_item_id'],
                'ics_type' => $validated['ics_type'],
                'quantity' => $validated['quantity'],
                'estimated_useful_life' => $validated['estimated_useful_life'],
                'date_prepared' => $prepared_date,
                'date_accepted' => $accepted_date,
                'remarks' => $validated['remarks'],
            ]);

            foreach ($this->batches as $batchData) {
                $batch = $ics->itemBatches()->create([
                    'identification_data' => $batchData['identification_data'] ?? null
                ]);

                foreach ($batchData['components'] as $componentData) {
                    if ($componentData['component_type'] || $componentData['serial_number'] || $componentData['brand'] || $componentData['model']) {
                        $batch->components()->create($componentData);
                    }
                }
            }
        });

        session()->flash('success', "ICS record created successfully.");

        $this->redirect(route('admin.inventory.ics.index'), navigate: true);
    }
}; ?>

<form wire:submit="store">
    <div class="border-b border-stone-200 pb-5 dark:border-stone-700">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-semibold text-stone-900 dark:text-stone-100">
                    Create New ICS Record
                </h1>
                <p class="mt-1 text-sm text-stone-600 dark:text-stone-400">
                    Fill in the details for the new Inventory Custodian Slip.
                </p>
            </div>
            <div class="flex items-center gap-x-4">
                <x-action-message class="me-3" on="ics-created">
                    {{ __('Record saved successfully.') }}
                </x-action-message>
                <flux:button variant="ghost" :href="route('admin.inventory.ics.index')" wire:navigate>
                    Cancel
                </flux:button>
                <flux:button type="submit" variant="primary" :disabled="$isParItem">
                    Save Record
                </flux:button>
            </div>
        </div>
    </div>

    <div class="mt-8 grid grid-cols-1 gap-8 lg:grid-cols-4">
        <!-- Main Content -->
        <div class="lg:col-span-3">
            @if ($isParItem)
                <div class="mb-6 rounded-md border-l-4 border-yellow-400 bg-yellow-50 p-4 dark:bg-yellow-800/20">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-yellow-400 dark:text-yellow-300" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-yellow-700 dark:text-yellow-200">
                                This item's value is ₱{{ number_format($this->unit_price, 2) }}.<br>
                                Items valued at ₱50,000 or more should be registered as Property, Plant, and Equipment (PPE) using a <strong>Property Acknowledgement Receipt (PAR)</strong>, not an ICS.
                            </p>
                        </div>
                    </div>
                </div>
            @endif
            <div class="space-y-8">
                <!-- Item & Contract Details -->
                <div class="overflow-hidden rounded-lg border border-stone-200 bg-white shadow-sm dark:border-stone-700 dark:bg-stone-800">
                    <div class="border-b border-stone-200 px-4 py-3 dark:border-stone-700">
                        <h3 class="font-semibold text-stone-800 dark:text-stone-200">Item & Contract Information</h3>
                    </div>
                    <div class="p-6">
                        <div class="space-y-6">
                            <div class="grid grid-cols-1 gap-x-6 gap-y-8 sm:grid-cols-3">
                                <div class="sm:col-span-1">
                                    <flux:select wire:model.live="contract_id" label="Contract" id="contract_id" required tabindex="1">
                                        <option value="">Select a contract</option>
                                        @foreach($this->allContracts as $contract)
                                            <option value="{{ $contract->id }}">{{ $contract->contract_po_ib_number }} ({{ $contract->supplier->name }})</option>
                                        @endforeach
                                    </flux:select>
                                    <x-input-error for="contract_id" class="mt-2" />
                                </div>
                                <div class="sm:col-span-1">
                                    <flux:select wire:model.live="contract_item_id" label="Item" id="contract_item_id" :disabled="!$this->contract_id" required tabindex="2">
                                        <option value="">Select an item</option>
                                        @foreach($this->contractItems as $item)
                                            <option value="{{ $item->id }}">{{ $item->itemSpecification->itemCatalog->name }}</option>
                                        @endforeach
                                    </flux:select>
                                    <x-input-error for="contract_item_id" class="mt-2" />
                                </div>

                                <div class="sm:col-span-1">
                                    <flux:input
                                        wire:model="unit_price"
                                        label="Unit Cost"
                                        id="unit_cost"
                                        type="text"
                                        :disabled="true"
                                    >
                                        <x-slot:leading>
                                            <span class="text-stone-500">₱</span>
                                        </x-slot:leading>
                                    </flux:input>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Batches & Components -->
                <div class="overflow-hidden rounded-lg border border-stone-200 bg-white shadow-sm dark:border-stone-700 dark:bg-stone-800">
                    <div class="border-b border-stone-200 px-4 py-3 dark:border-stone-700">
                        <h3 class="font-semibold text-stone-800 dark:text-stone-200">Batches & Components</h3>
                    </div>
                    <div class="p-6">
                        <div class="space-y-6">
                            <flux:input type="number" wire:model.live="quantity" label="Total Quantity / Number of Batches" min="1" required tabindex="3" />

                            <div class="space-y-8">
                                @foreach ($batches as $batchIndex => $batch)
                                    <div wire:key="batch-{{ $batchIndex }}" class="rounded-md border border-stone-300 bg-stone-50 p-4 dark:border-stone-600 dark:bg-stone-800/50">
                                        <div class="flex items-center justify-between border-b border-stone-200 pb-3 dark:border-stone-700">
                                            <h4 class="font-semibold text-stone-800 dark:text-stone-200">
                                                Batch #{{ $loop->iteration }}
                                            </h4>
                                            @if ($quantity > 1)
                                                <div class="p-2">
                                                    <button type="button" wire:click.prevent="removeBatch({{ $batchIndex }})" class="rounded-full bg-red-500 p-1.5 text-white shadow-md hover:bg-red-600 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2">
                                                        <x-flux::icon.x-mark class="h-5 w-5" />
                                                    </button>
                                                </div>
                                            @endif
                                        </div>

                                        <div class="mt-4 space-y-4">
                                            @foreach ($batch['components'] as $componentIndex => $component)
                                                <div wire:key="component-{{ $batchIndex }}-{{ $componentIndex }}" class="relative rounded-md border border-stone-200 bg-white p-4 dark:border-stone-600 dark:bg-stone-700">
                                                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                                        <flux:input wire:model="batches.{{ $batchIndex }}.components.{{ $componentIndex }}.component_type" label="Component Type" placeholder="e.g., Monitor, Casing" tabindex="{{ 4 + ($batchIndex * 100) + ($componentIndex * 4) + 1 }}" />
                                                        <flux:input wire:model="batches.{{ $batchIndex }}.components.{{ $componentIndex }}.serial_number" label="Serial Number" tabindex="{{ 4 + ($batchIndex * 100) + ($componentIndex * 4) + 2 }}" />
                                                        <flux:input wire:model="batches.{{ $batchIndex }}.components.{{ $componentIndex }}.brand" label="Brand" tabindex="{{ 4 + ($batchIndex * 100) + ($componentIndex * 4) + 3 }}" />
                                                        <flux:input wire:model="batches.{{ $batchIndex }}.components.{{ $componentIndex }}.model" label="Model" tabindex="{{ 4 + ($batchIndex * 100) + ($componentIndex * 4) + 4 }}" />
                                                    </div>
                                                    @if (count($batch['components']) > 1)
                                                        <div class="absolute -right-2 -top-2">
                                                            <button type="button" wire:click.prevent="removeComponent({{ $batchIndex }}, {{ $componentIndex }})" class="rounded-full bg-red-500 p-1 text-white shadow-md hover:bg-red-600 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2">
                                                                <x-flux::icon.x-mark class="h-4 w-4" />
                                                            </button>
                                                        </div>
                                                    @endif
                                                </div>
                                            @endforeach
                                        </div>

                                        <div class="mt-4 border-t border-stone-200 pt-3 dark:border-stone-700">
                                            <flux:button type="button" variant="ghost" wire:click.prevent="addComponent({{ $batchIndex }})">
                                                Add Component
                                            </flux:button>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
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
                    <div class="p-6">
                        <flux:select wire:model.live="assigned_employee_id" label="Assign To Employee" id="assigned_employee_id" required tabindex="500">
                            <option value="">Select an employee</option>
                            @foreach($this->allEmployees as $employee)
                                <option value="{{ $employee->id }}">{{ $employee->name }}</option>
                            @endforeach
                        </flux:select>
                    </div>
                </div>

                <!-- Document Details -->
                <div class="overflow-hidden rounded-lg border border-stone-200 bg-white shadow-sm dark:border-stone-700 dark:bg-stone-800">
                    <div class="border-b border-stone-200 px-4 py-3 dark:border-stone-700">
                        <h3 class="font-semibold text-stone-800 dark:text-stone-200">Document Details</h3>
                    </div>
                    <div class="space-y-6 p-6">
                        <flux:input wire:model="ics_number" label="ICS Number" required readonly tabindex="501" />
                        <flux:select wire:model="ics_type" label="ICS Type" required :disabled="$isParItem || !$this->contract_item_id" tabindex="502">
                            <option value="SPLV">SPLV - P5,000.00 or less</option>
                            <option value="SPHV">SPHV - P5,001.00 to P49,999.99</option>
                        </flux:select>
                        <flux:input wire:model="estimated_useful_life" type="number" label="Estimated Useful Life (Years)" min="1" required :disabled="$isParItem" tabindex="503" />
                        <div x-data="{ isPar: $wire.get('isParItem') }" x-init="$watch('$wire.isParItem', value => isPar = value)">
                             <flux:input
                                x-mask="isPar ? '' : '99/99/9999'"
                                wire:model.blur="date_prepared"
                                type="text"
                                label="Date Prepared"
                                placeholder="MM/DD/YYYY"
                                required
                                :disabled="$isParItem"
                                :readonly="$use_current_date"
                                tabindex="504"
                            />
                            <x-input-error for="date_prepared" class="mt-2" />
                            <div class="mt-2">
                                <flux:checkbox
                                    wire:model.live="use_current_date"
                                    label="Use current date"
                                    id="use_current_date"
                                />
                            </div>
                        </div>
                        <div x-data="{ isPar: $wire.get('isParItem') }" x-init="$watch('$wire.isParItem', value => isPar = value)">
                            <flux:input
                                x-mask="isPar ? '' : '99/99/9999'"
                                wire:model.blur="date_accepted"
                                type="text"
                                label="Date Accepted"
                                placeholder="MM/DD/YYYY"
                                required
                                :disabled="$isParItem"
                                :readonly="$use_current_date_accepted"
                                tabindex="505"
                            />
                            <x-input-error for="date_accepted" class="mt-2" />
                            <div class="mt-2">
                                <flux:checkbox
                                    wire:model.live="use_current_date_accepted"
                                    label="Use current date"
                                    id="use_current_date_accepted"
                                />
                            </div>
                        </div>
                        <flux:textarea wire:model="remarks" label="Remarks" placeholder="Add any notes or remarks here..." :disabled="$isParItem" tabindex="506" />
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>
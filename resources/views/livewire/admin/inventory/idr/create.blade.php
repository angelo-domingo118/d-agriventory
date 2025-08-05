<?php

use App\Models\Contract;
use App\Models\ContractItem;
use App\Models\Employee;
use App\Models\IdrNumber;
use App\Models\IdrItemBatch;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Illuminate\Support\Facades\DB;

new #[Layout('components.layouts.app')] class extends Component {
    // Form state
    public string $number = '';
    public ?int $contract_id = null;
    public ?int $contract_item_id = null;
    public ?int $assigned_employee_id = null;
    public ?int $approving_employee_id = null;
    public ?int $received_by_id = null;
    public ?int $received_from_id = null;
    public int $quantity = 1;
    public string $inventory_code = '';
    public string $ors = '';
    public ?string $date_prepared = null;
    public ?string $date_accepted = null;
    public ?string $date = null;
    public string $remarks = '';

    // Display only property
    public ?float $unit_price = 0.0;
    
    public array $batches = [];

    public Collection $allContracts;
    public Collection $allEmployees;


    public function mount(): void
    {
        if (!auth()->user()->hasAdminPermission('create_inventory')) {
            abort(403);
        }
        
        $this->date_prepared = now()->format('Y-m-d');
        $this->date_accepted = now()->format('Y-m-d');
        $this->date = now()->format('Y-m-d');
        
        $this->allContracts = Contract::with('supplier:id,name')->orderBy('contract_po_ib_number')->get(['id', 'contract_po_ib_number', 'supplier_id']);
        $this->allEmployees = Employee::orderBy('name')->get(['id', 'name']);

        $this->updatedQuantity($this->quantity);
    }

    #[Computed]
    public function contractItems()
    {
        if (!$this->contract_id) {
            return collect();
        }
        return ContractItem::where('contract_id', $this->contract_id)->with('itemSpecification.itemCatalog:id,name')->get();
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
        $this->batches[] = [ 'id' => null, 'identification_data' => null ];
    }

    public function removeBatch(int $index): void
    {
        if (isset($this->batches[$index])) {
            array_splice($this->batches, $index, 1);
        }
        $this->quantity = count($this->batches);
    }
    
    public function store(): void
    {
        $validated = $this->validate([
            'contract_id' => ['required', 'integer', Rule::exists('contracts', 'id')],
            'contract_item_id' => ['required', 'integer', Rule::exists('contract_items', 'id')],
            'assigned_employee_id' => ['required', 'integer', Rule::exists('employees', 'id')],
            'approving_employee_id' => ['required', 'integer', Rule::exists('employees', 'id')],
            'received_by_id' => ['required', 'integer', Rule::exists('employees', 'id')],
            'received_from_id' => ['required', 'integer', Rule::exists('employees', 'id')],
            'quantity' => ['required', 'integer', 'min:1', fn ($attribute, $value, $fail) => $value != count($this->batches) && $fail("The quantity ($value) must match the number of batches (" . count($this->batches) . ").")],
            'inventory_code' => ['required', 'string', 'max:255'],
            'ors' => ['required', 'string', 'max:255'],
            'date_prepared' => ['required', 'date'],
            'date_accepted' => ['required', 'date'],
            'date' => ['required', 'date'],
            'remarks' => ['nullable', 'string'],
            'batches.*.identification_data' => ['nullable', 'string', 'max:255'],
        ]);

        DB::transaction(function () use ($validated) {
            // Lock the table to prevent race conditions when generating a new number.
            $lastIdr = IdrNumber::latest('id')->lockForUpdate()->first();
            $validated['number'] = $lastIdr ? $lastIdr->number + 1 : 1;

            $idr = IdrNumber::create($validated);
            foreach ($this->batches as $batchData) {
                $idr->itemBatches()->create(['identification_data' => $batchData['identification_data'] ?? null]);
            }
        });

        session()->flash('success', "IDR record created successfully.");
        $this->redirect(route('admin.inventory.idr.index'), navigate: true);
    }
}; ?>

<form wire:submit="store">
    <div class="border-b border-stone-200 pb-5 dark:border-stone-700">
        <div class="flex items-center justify-between">
            <div>
                <flux:breadcrumbs class="text-2xl font-semibold">
                    <flux:breadcrumbs.item :href="route('admin.dashboard')" wire:navigate icon="home" class="text-xl sm:text-2xl font-semibold text-stone-700 dark:text-stone-300" />
                    <flux:breadcrumbs.item class="text-xl sm:text-2xl font-semibold text-stone-500 dark:text-stone-400">Inventory</flux:breadcrumbs.item>
                    <flux:breadcrumbs.item :href="route('admin.inventory.idr.index')" wire:navigate class="text-xl sm:text-2xl font-semibold text-stone-500 dark:text-stone-400">IDR Management</flux:breadcrumbs.item>
                    <flux:breadcrumbs.item class="text-xl sm:text-2xl font-semibold text-stone-900 dark:text-stone-100">Create New IDR</flux:breadcrumbs.item>
                </flux:breadcrumbs>
                <p class="mt-1 text-sm text-stone-600 dark:text-stone-400">Fill in the details for the new Inspection and Delivery Report.</p>
            </div>
             <div class="flex items-center gap-x-4">
                <flux:button variant="ghost" :href="route('admin.inventory.idr.index')" wire:navigate>Cancel</flux:button>
                <flux:button type="submit" variant="primary">Save IDR</flux:button>
            </div>
        </div>
    </div>

    <div class="mt-8 grid grid-cols-1 gap-8 lg:grid-cols-3">
        <div class="lg:col-span-2">
            <div class="space-y-8">
                <div class="overflow-hidden rounded-lg border border-stone-200 bg-white shadow-sm dark:border-stone-700 dark:bg-stone-800">
                    <div class="border-b border-stone-200 px-4 py-3 dark:border-stone-700"><h3 class="font-semibold text-stone-800 dark:text-stone-200">Item & Contract Information</h3></div>
                    <div class="p-6">
                        <div class="grid grid-cols-1 gap-6 sm:grid-cols-3">
                            <div class="sm:col-span-1">
                                <flux:select wire:model.live="contract_id" label="Contract" id="contract_id" required>
                                    <option value="">Select a contract</option>
                                    @foreach($this->allContracts as $contract)
                                        <option value="{{ $contract->id }}">{{ $contract->contract_po_ib_number }} ({{ $contract->supplier->name }})</option>
                                    @endforeach
                                </flux:select>
                            </div>
                            <div class="sm:col-span-1">
                                <flux:select wire:model.live="contract_item_id" label="Item" id="contract_item_id" :disabled="!$this->contract_id" required>
                                    <option value="">Select an item</option>
                                    @if ($this->contractItems)
                                        @foreach ($this->contractItems as $item)
                                            <option value="{{ $item->id }}">{{ $item->itemSpecification->itemCatalog->name }}</option>
                                        @endforeach
                                    @endif
                                </flux:select>
                            </div>
                            <div class="sm:col-span-1">
                                <flux:input wire:model="unit_price" label="Unit Cost" id="unit_cost" type="text" :disabled="true">
                                    <x-slot:leading><span class="text-stone-500">₱</span></x-slot:leading>
                                </flux:input>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="overflow-hidden rounded-lg border border-stone-200 bg-white shadow-sm dark:border-stone-700 dark:bg-stone-800">
                    <div class="border-b border-stone-200 px-4 py-3 dark:border-stone-700"><h3 class="font-semibold text-stone-800 dark:text-stone-200">Batches / Serial Numbers</h3></div>
                    <div class="p-6">
                        <div class="space-y-6">
                            <flux:input type="number" wire:model.live="quantity" label="Total Quantity / Number of Batches" min="1" required />
                            <div class="space-y-4">
                                @foreach ($batches as $batchIndex => $batch)
                                    <div wire:key="batch-{{ $batchIndex }}" class="relative rounded-md border border-stone-300 bg-stone-50 p-4 dark:border-stone-600 dark:bg-stone-800/50">
                                        <div class="flex items-center justify-between">
                                            <label for="batch-{{ $batchIndex }}-data" class="text-sm font-medium text-stone-700 dark:text-stone-300">Batch #{{ $loop->iteration }} Serial/Identification</label>
                                            @if ($quantity > 1)
                                                <button type="button" wire:click.prevent="removeBatch({{ $batchIndex }})" class="text-red-500 hover:text-red-700">&times; Remove</button>
                                            @endif
                                        </div>
                                        <flux:input wire:model="batches.{{ $batchIndex }}.identification_data" id="batch-{{ $batchIndex }}-data" placeholder="e.g. SN: 12345, Asset Tag: 67890" />
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="lg:col-span-1">
            <div class="space-y-8">
                 <div class="overflow-hidden rounded-lg border border-stone-200 bg-white shadow-sm dark:border-stone-700 dark:bg-stone-800">
                    <div class="border-b border-stone-200 px-4 py-3 dark:border-stone-700"><h3 class="font-semibold text-stone-800 dark:text-stone-200">Personnel</h3></div>
                    <div class="space-y-6 p-6">
                        <flux:select wire:model="assigned_employee_id" label="Assigned To (Stock Officer)" required>
                            <option value="">Select Employee</option>
                            @foreach($this->allEmployees as $employee) <option value="{{ $employee->id }}">{{ $employee->name }}</option> @endforeach
                        </flux:select>
                        <flux:select wire:model="approving_employee_id" label="Approving Official" required>
                             <option value="">Select Employee</option>
                            @foreach($this->allEmployees as $employee) <option value="{{ $employee->id }}">{{ $employee->name }}</option> @endforeach
                        </flux:select>
                         <flux:select wire:model="received_by_id" label="Received By" required>
                             <option value="">Select Employee</option>
                            @foreach($this->allEmployees as $employee) <option value="{{ $employee->id }}">{{ $employee->name }}</option> @endforeach
                        </flux:select>
                         <flux:select wire:model="received_from_id" label="Received From (Issued By)" required>
                            <option value="">Select Employee</option>
                            @foreach($this->allEmployees as $employee) <option value="{{ $employee->id }}">{{ $employee->name }}</option> @endforeach
                        </flux:select>
                    </div>
                </div>
                <div class="overflow-hidden rounded-lg border border-stone-200 bg-white shadow-sm dark:border-stone-700 dark:bg-stone-800">
                    <div class="border-b border-stone-200 px-4 py-3 dark:border-stone-700"><h3 class="font-semibold text-stone-800 dark:text-stone-200">Document Details</h3></div>
                    <div class="space-y-6 p-6">
                         <flux:input wire:model="number" label="IDR Number" required />
                         <flux:input wire:model="inventory_code" label="Inventory Code" required />
                         <flux:input wire:model="ors" label="ORS Number" required />
                         <flux:input wire:model="date_prepared" type="date" label="Date Prepared" required />
                         <flux:input wire:model="date_accepted" type="date" label="Date Accepted" required />
                         <flux:input wire:model="date" type="date" label="Date (IDR)" required />
                         <flux:textarea wire:model="remarks" label="Remarks" placeholder="Add any notes or remarks here..." />
                    </div>
                </div>
            </div>
        </div>
    </div>
</form> 
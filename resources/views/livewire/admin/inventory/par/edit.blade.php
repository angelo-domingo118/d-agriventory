<?php

use App\Models\ContractItem;
use App\Models\Employee;
use App\Models\ParNumber;
use App\Models\ParItemBatch;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.app')] class extends Component {
    public ParNumber $par;

    // Form state
    public string $par_number = '';
    public ?int $employee_id = null;
    public ?string $date_acquired = null;
    public string $area_code = '';
    public string $building_code = '';
    public string $account_code = '';
    public string $remarks = '';

    // Batch management
    public array $batches = [];
    public ?int $selected_contract_item_id = null;
    public int $quantity = 1;
    public ?float $unit_cost = null;

    // Display/logic properties
    public bool $isIcsItem = false;
    public ?string $itemWarningMessage = '';

    public Collection $allEmployees;
    public Collection $allContractItems;

    public function mount(ParNumber $par): void
    {
        if (!auth()->user()->hasAdminPermission('edit_inventory')) {
            abort(403);
        }

        $this->par = $par;
        $this->fill($par->toArray());
        $this->date_acquired = Carbon::parse($par->date_acquired)->format('m/d/Y');
        $this->employee_id = $par->assigned_employee_id;

        foreach ($par->itemBatches as $batch) {
            $this->batches[] = [
                'id' => $batch->id,
                'contract_item_id' => $batch->contract_item_id,
                'item_name' => $batch->contractItem->itemSpecification->itemCatalog->name,
                'quantity' => $batch->quantity,
                'unit_cost' => $batch->contractItem->unit_price,
                'total_cost' => $batch->quantity * $batch->contractItem->unit_price,
            ];
        }

        // Pre-load data for select dropdowns
        $this->allEmployees = Employee::orderBy('name')->get(['id', 'name']);
        $this->allContractItems = ContractItem::with('itemSpecification.itemCatalog', 'contract.supplier')
            ->where('unit_price', '>=', 50000)
            ->get();
    }
    
    public function updatedSelectedContractItemId($value): void
    {
        $this->isIcsItem = false;
        $this->itemWarningMessage = '';
        $this->unit_cost = null;

        if ($value) {
            $item = ContractItem::find($value);
            if ($item) {
                $this->unit_cost = $item->unit_price;
                if ($item->unit_price < 50000) {
                    $this->isIcsItem = true;
                    $this->itemWarningMessage = 'This item is valued under ₱50,000. It should be recorded using an ICS form.';
                }
            }
        }
    }
    
    #[Computed]
    public function selectedContractItem()
    {
        if ($this->selected_contract_item_id) {
            return ContractItem::with('itemSpecification.itemCatalog', 'contract.supplier')->find($this->selected_contract_item_id);
        }
        return null;
    }

    public function addBatch(): void
    {
        $this->validate([
            'selected_contract_item_id' => ['required', 'integer', Rule::exists('contract_items', 'id')],
            'quantity' => ['required', 'integer', 'min:1'],
            'unit_cost' => ['required', 'numeric', 'min:0'],
        ]);

        if($this->isIcsItem) {
             $this->addError('batch', 'Cannot add items valued under ₱50,000 to a PAR.');
             return;
        }

        $selectedItem = $this->selectedContractItem();

        $this->batches[] = [
            'id' => null, // new item
            'contract_item_id' => $this->selected_contract_item_id,
            'item_name' => $selectedItem->itemSpecification->itemCatalog->name,
            'quantity' => $this->quantity,
            'unit_cost' => $this->unit_cost,
            'total_cost' => $this->quantity * $this->unit_cost,
        ];

        // Reset fields
        $this->reset('selected_contract_item_id', 'quantity', 'unit_cost', 'isIcsItem', 'itemWarningMessage');
        $this->quantity = 1;
    }

    public function removeBatch(int $index): void
    {
        if (isset($this->batches[$index])) {
            array_splice($this->batches, $index, 1);
        }
    }

    public function update(): void
    {
        $validated = $this->validate([
            'par_number' => ['required', 'string', 'max:255', Rule::unique('par_number', 'par_number')->ignore($this->par->id)],
            'employee_id' => ['required', 'integer', Rule::exists('employees', 'id')],
            'date_acquired' => ['required', 'date_format:m/d/Y'],
            'area_code' => ['nullable', 'string', 'max:255'],
            'building_code' => ['nullable', 'string', 'max:255'],
            'account_code' => ['nullable', 'string', 'max:255'],
            'remarks' => ['nullable', 'string'],
            'batches' => ['required', 'array', 'min:1'],
            'batches.*.id' => ['nullable', 'integer'],
            'batches.*.contract_item_id' => ['required', 'integer', Rule::exists('contract_items', 'id')],
            'batches.*.quantity' => ['required', 'integer', 'min:1'],
            'batches.*.unit_cost' => ['required', 'numeric', 'min:50000'],
        ]);
        
        DB::transaction(function () use ($validated) {
            $acquiredDate = Carbon::createFromFormat('m/d/Y', $validated['date_acquired'])->format('Y-m-d');

            $this->par->update([
                'par_number' => $validated['par_number'],
                'assigned_employee_id' => $validated['employee_id'],
                'date_acquired' => $acquiredDate,
                'area_code' => $validated['area_code'],
                'building_code' => $validated['building_code'],
                'account_code' => $validated['account_code'],
                'remarks' => $validated['remarks'],
            ]);
            
            $existingBatchIds = collect($validated['batches'])->pluck('id')->filter();
            $this->par->itemBatches()->whereNotIn('id', $existingBatchIds)->delete();

            foreach ($validated['batches'] as $batchData) {
                $this->par->itemBatches()->updateOrCreate(
                    ['id' => $batchData['id']],
                    [
                        'contract_item_id' => $batchData['contract_item_id'],
                        'quantity' => $batchData['quantity'],
                    ]
                );
            }
        });

        session()->flash('success', "PAR record updated successfully.");
        $this->redirect(route('admin.inventory.par.index'), navigate: true);
    }
}; ?>

<form wire:submit="update">
    <div class="border-b border-stone-200 pb-5 dark:border-stone-700">
        <div class="flex items-center justify-between">
            <div>
                <flux:breadcrumbs class="text-2xl font-semibold">
                    <flux:breadcrumbs.item :href="route('admin.dashboard')" wire:navigate icon="home" class="text-xl sm:text-2xl font-semibold text-stone-700 dark:text-stone-300" />
                    <flux:breadcrumbs.item class="text-xl sm:text-2xl font-semibold text-stone-500 dark:text-stone-400">Inventory</flux:breadcrumbs.item>
                    <flux:breadcrumbs.item :href="route('admin.inventory.par.index')" wire:navigate class="text-xl sm:text-2xl font-semibold text-stone-500 dark:text-stone-400">PAR Management</flux:breadcrumbs.item>
                    <flux:breadcrumbs.item class="text-xl sm:text-2xl font-semibold text-stone-900 dark:text-stone-100">Edit PAR #{{ $par->par_number }}</flux:breadcrumbs.item>
                </flux:breadcrumbs>
                <p class="mt-1 text-sm text-stone-600 dark:text-stone-400">
                    Update the details for PAR #{{ $par->par_number }}.
                </p>
            </div>
            <div class="flex items-center gap-x-3">
                <a href="{{ route('admin.inventory.par.index') }}" wire:navigate
                    class="flux-button-ghost">
                    Cancel
                </a>
                <button type="submit" class="flux-button-primary">
                    Save Changes
                </button>
            </div>
        </div>
    </div>

    <div class="mt-6 grid grid-cols-1 gap-x-6 gap-y-8 lg:grid-cols-12">
        {{-- Main PAR Details --}}
        <div class="lg:col-span-8">
            <div class="rounded-lg border border-stone-200 bg-white dark:border-stone-700 dark:bg-stone-800">
                <div class="border-b border-stone-200 px-4 py-5 dark:border-stone-700 sm:px-6">
                    <h3 class="text-base font-semibold leading-6 text-stone-900 dark:text-stone-50">
                        PAR Information
                    </h3>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 gap-x-6 gap-y-5 sm:grid-cols-6">
                        <div class="sm:col-span-3">
                            <label for="par_number" class="flux-label">PAR Number</label>
                            <div class="mt-2">
                                <input id="par_number" type="text" wire:model="par_number"
                                    class="flux-input-text" />
                                 <x-input-error for="par_number" class="mt-2" />
                            </div>
                        </div>

                        <div class="sm:col-span-3">
                            <label for="employee_id" class="flux-label">Assigned Employee</label>
                             <div class="mt-2">
                                <select id="employee_id" wire:model.live="employee_id" class="flux-input-select">
                                    <option value="">Select employee...</option>
                                    @foreach($allEmployees as $employee)
                                        <option value="{{ $employee->id }}">{{ $employee->name }}</option>
                                    @endforeach
                                </select>
                                <x-input-error for="employee_id" class="mt-2" />
                            </div>
                        </div>

                        <div class="sm:col-span-3">
                            <label for="date_acquired" class="flux-label">Date Acquired</label>
                            <div class="mt-2">
                                <input x-data x-init="new Pikaday({ field: $el, format: 'MM/DD/YYYY' })" type="text" id="date_acquired"
                                    wire:model="date_acquired"
                                    class="flux-input-text" />
                                <x-input-error for="date_acquired" class="mt-2" />
                            </div>
                        </div>

                         <div class="sm:col-span-3">
                            <label for="account_code" class="flux-label">Account Code</label>
                            <div class="mt-2">
                                <input type="text" id="account_code" wire:model.blur="account_code" class="flux-input-text" />
                                <x-input-error for="account_code" class="mt-2" />
                            </div>
                        </div>
                        
                        <div class="sm:col-span-3">
                            <label for="area_code" class="flux-label">Area Code</label>
                            <div class="mt-2">
                                <input type="text" id="area_code" wire:model.blur="area_code" class="flux-input-text" />
                                <x-input-error for="area_code" class="mt-2" />
                            </div>
                        </div>

                        <div class="sm:col-span-3">
                            <label for="building_code" class="flux-label">Building/Room Code</label>
                            <div class="mt-2">
                                <input type="text" id="building_code" wire:model.blur="building_code" class="flux-input-text" />
                                <x-input-error for="building_code" class="mt-2" />
                            </div>
                        </div>

                        <div class="col-span-full">
                            <label for="remarks" class="flux-label">Remarks</label>
                            <div class="mt-2">
                                <textarea id="remarks" wire:model.blur="remarks" rows="3" class="flux-input-textarea"></textarea>
                                <x-input-error for="remarks" class="mt-2" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Item Batches --}}
        <div class="lg:col-span-4">
            <div class="space-y-6">
                {{-- Add Item Form --}}
                <div class="rounded-lg border border-stone-200 bg-white dark:border-stone-700 dark:bg-stone-800">
                    <div class="border-b border-stone-200 px-4 py-5 dark:border-stone-700 sm:px-6">
                        <h3 class="text-base font-semibold leading-6 text-stone-900 dark:text-stone-50">
                            Add Items
                        </h3>
                    </div>
                    <div class="p-6 space-y-4">
                         <div>
                            <label for="selected_contract_item_id" class="flux-label">Item</label>
                            <div class="mt-2">
                                <select id="selected_contract_item_id" wire:model.live="selected_contract_item_id" class="flux-input-select">
                                    <option value="">Select an item</option>
                                    @foreach ($allContractItems as $item)
                                        <option value="{{ $item->id }}">
                                            {{ $item->itemSpecification->itemCatalog->name }} ({{ $item->contract->contract_po_ib_number }})
                                        </option>
                                    @endforeach
                                </select>
                                <x-input-error for="selected_contract_item_id" class="mt-2" />
                            </div>
                        </div>
                        
                         @if($isIcsItem)
                            <div class="rounded-md bg-yellow-50 p-4 dark:bg-yellow-800/20">
                                <div class="flex">
                                    <div class="flex-shrink-0">
                                        <svg class="h-5 w-5 text-yellow-400 dark:text-yellow-300" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                            <path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" />
                                        </svg>
                                    </div>
                                    <div class="ml-3">
                                        <h3 class="text-sm font-medium text-yellow-800 dark:text-yellow-300">Attention needed</h3>
                                        <div class="mt-2 text-sm text-yellow-700 dark:text-yellow-200">
                                            <p>{{ $itemWarningMessage }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label for="quantity" class="flux-label">Quantity</label>
                                <div class="mt-2">
                                    <input type="number" id="quantity" wire:model="quantity" min="1" class="flux-input-text" />
                                </div>
                            </div>
                            <div>
                                <label for="unit_cost" class="flux-label">Unit Cost</label>
                                <div class="mt-2">
                                    <input type="text" id="unit_cost" wire:model="unit_cost" readonly class="flux-input-text-disabled" />
                                </div>
                            </div>
                        </div>
                        <x-input-error for="batch" class="mt-2" />

                        <div class="flex justify-end">
                             <button type="button" wire:click="addBatch" class="flux-button-primary" @if($isIcsItem) disabled @endif>
                                Add Item
                            </button>
                        </div>
                    </div>
                </div>

                {{-- Items Table --}}
                 @if(!empty($batches))
                    <div class="rounded-lg border border-stone-200 bg-white dark:border-stone-700 dark:bg-stone-800">
                        <div class="px-4 py-5 sm:px-6">
                            <h3 class="text-base font-semibold leading-6 text-stone-900 dark:text-stone-50">
                                Item List
                            </h3>
                        </div>
                        <div class="flow-root">
                             <ul role="list" class="divide-y divide-stone-200 dark:divide-stone-700">
                                @foreach($batches as $index => $batch)
                                    <li wire:key="batch-{{ $batch['id'] ?? 'new-'.$index }}" class="p-4 sm:p-6">
                                        <div class="flex items-center justify-between">
                                            <div>
                                                <p class="font-medium text-stone-900 dark:text-white">{{ $batch['item_name'] }}</p>
                                                <p class="text-sm text-stone-500 dark:text-stone-400">
                                                    {{ $batch['quantity'] }} x ₱{{ number_format($batch['unit_cost'], 2) }}
                                                </p>
                                            </div>
                                            <div class="text-right">
                                                 <p class="font-semibold text-stone-900 dark:text-white">
                                                    ₱{{ number_format($batch['total_cost'], 2) }}
                                                </p>
                                                <button type="button" wire:click="removeBatch({{ $index }})" class="text-sm font-medium text-rose-600 hover:text-rose-500 dark:text-rose-400 dark:hover:text-rose-300">
                                                    Remove
                                                </button>
                                            </div>
                                        </div>
                                    </li>
                                @endforeach
                             </ul>
                        </div>
                        <div class="border-t border-stone-200 bg-stone-50 px-4 py-4 dark:border-stone-700 dark:bg-stone-800/50 sm:px-6">
                            <div class="flex justify-between text-base font-medium text-stone-900 dark:text-white">
                                <p>Total</p>
                                <p>₱{{ number_format(array_sum(array_column($batches, 'total_cost')), 2) }}</p>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</form> 
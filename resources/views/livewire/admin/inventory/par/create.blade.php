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
    // Form state
    public string $par_number = '';
    public ?int $employee_id = null;
    public ?string $date_acquired = null;
    public string $area_code = '';
    public string $building_code = '';
    public string $account_code = '';
    public string $remarks = '';
    public bool $use_current_date = true;

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

    public function mount(): void
    {
        if (!auth()->user()->hasAdminPermission('create_inventory')) {
            abort(403);
        }

        $this->generateParNumber();
        $this->date_acquired = now()->format('m/d/Y');

        // Pre-load data for select dropdowns
        $this->allEmployees = Employee::orderBy('name')->get(['id', 'name']);
        $this->allContractItems = ContractItem::with('itemSpecification.catalogItem', 'contract.supplier')
            ->where('unit_price', '>=', 50000)
            ->get();
    }

    public function generateParNumber(): void
    {
        $prefix = 'PAR-' . now()->format('Y-m-');
        $lastPar = ParNumber::where('par_number', 'like', $prefix . '%')
            ->orderBy('par_number', 'desc')
            ->first();

        if ($lastPar) {
            $lastNumber = (int) substr($lastPar->par_number, -4);
            $newNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $newNumber = '0001';
        }

        $this->par_number = $prefix . $newNumber;
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
    public function getSelectedContractItem()
    {
        if($this->selected_contract_item_id) {
            return ContractItem::with('itemSpecification.catalogItem', 'contract.supplier')->find($this->selected_contract_item_id);
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

        $selectedItem = $this->getSelectedContractItem();

        $this->batches[] = [
            'contract_item_id' => $this->selected_contract_item_id,
            'item_name' => $selectedItem->itemSpecification->catalogItem->name,
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

    public function updatedUseCurrentDate(bool $value): void
    {
        if ($value) {
            $this->date_acquired = now()->format('m/d/Y');
        } else {
            $this->date_acquired = null;
        }
    }

    public function store(): void
    {
        $validated = $this->validate([
            'par_number' => ['required', 'string', 'max:255', Rule::unique('par_number', 'par_number')],
            'employee_id' => ['required', 'integer', Rule::exists('employees', 'id')],
            'date_acquired' => ['required', 'date_format:m/d/Y'],
            'area_code' => ['nullable', 'string', 'max:255'],
            'building_code' => ['nullable', 'string', 'max:255'],
            'account_code' => ['nullable', 'string', 'max:255'],
            'remarks' => ['nullable', 'string'],
            'batches' => ['required', 'array', 'min:1'],
            'batches.*.contract_item_id' => ['required', 'integer', Rule::exists('contract_items', 'id')],
            'batches.*.quantity' => ['required', 'integer', 'min:1'],
            'batches.*.unit_cost' => ['required', 'numeric', 'min:50000'],
        ]);
        
        DB::transaction(function () use ($validated) {
            $acquiredDate = Carbon::createFromFormat('m/d/Y', $validated['date_acquired'])->format('Y-m-d');

            $par = ParNumber::create([
                'par_number' => $validated['par_number'],
                'assigned_employee_id' => $validated['employee_id'],
                'date_acquired' => $acquiredDate,
                'area_code' => $validated['area_code'],
                'building_code' => $validated['building_code'],
                'account_code' => $validated['account_code'],
                'remarks' => $validated['remarks'],
            ]);

            foreach ($validated['batches'] as $batchData) {
                $par->itemBatches()->create([
                    'contract_item_id' => $batchData['contract_item_id'],
                    'quantity' => $batchData['quantity'],
                ]);
            }
        });

        session()->flash('success', "PAR record created successfully.");
        $this->redirect(route('admin.inventory.par.index'), navigate: true);
    }
}; ?>

<form wire:submit="store">
    <div class="border-b border-stone-200 pb-5 dark:border-stone-700">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-semibold text-stone-900 dark:text-stone-100">
                    Create New Property Acknowledgement Receipt (PAR)
                </h1>
                <p class="mt-1 text-sm text-stone-600 dark:text-stone-400">
                    Fill in the details for the new PAR. Items must be valued at ₱50,000 or more.
                </p>
            </div>
            <div class="flex items-center gap-x-4">
                <flux:button variant="ghost" :href="route('admin.inventory.par.index')" wire:navigate>
                    Cancel
                </flux:button>
                <flux:button type="submit" variant="primary">
                    Save PAR
                </flux:button>
            </div>
        </div>
    </div>

    <div class="mt-8 grid grid-cols-1 gap-8 lg:grid-cols-4">
        <!-- Main Content -->
        <div class="lg:col-span-3">
            <div class="space-y-8">
                <div class="overflow-hidden rounded-lg border border-stone-200 bg-white shadow-sm dark:border-stone-700 dark:bg-stone-800">
                    <div class="border-b border-stone-200 px-4 py-3 dark:border-stone-700">
                        <h3 class="font-semibold text-stone-800 dark:text-stone-200">Item Batches</h3>
                    </div>
                    <div class="p-6">
                        <div class="space-y-6">
                            <!-- Add Item Form -->
                            <div class="rounded-md border border-stone-300 bg-stone-50 p-4 dark:border-stone-600 dark:bg-stone-800/50">
                                <h4 class="font-semibold text-stone-800 dark:text-stone-200">Add New Item</h4>
                                <div class="mt-4 space-y-4">
                                    <flux:select wire:model.live="selected_contract_item_id" label="Item" id="selected_contract_item_id">
                                        <option value="">Select item...</option>
                                        @foreach($allContractItems as $item)
                                            <option value="{{ $item->id }}">
                                                {{ $item->itemSpecification->catalogItem->name }} ({{ $item->contract->contract_po_ib_number }})
                                            </option>
                                        @endforeach
                                    </flux:select>
                                    <x-input-error for="selected_contract_item_id" class="mt-2" />

                                    @if($isIcsItem)
                                        <div class="rounded-md bg-yellow-50 p-4 dark:bg-yellow-500/10">
                                            <div class="flex">
                                                <div class="flex-shrink-0">
                                                    <svg class="h-5 w-5 text-yellow-400 dark:text-yellow-300" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                        <path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" />
                                                    </svg>
                                                </div>
                                                <div class="ml-3">
                                                    <h3 class="text-sm font-medium text-yellow-800 dark:text-yellow-300">
                                                        Attention needed
                                                    </h3>
                                                    <div class="mt-2 text-sm text-yellow-700 dark:text-yellow-200">
                                                        <p>{{ $itemWarningMessage }}</p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endif

                                    <div class="grid grid-cols-2 gap-4">
                                        <flux:input type="number" wire:model="quantity" label="Quantity" min="1" />
                                        <flux:input wire:model="unit_cost" label="Unit Cost" id="unit_cost" type="text" readonly>
                                            <x-slot:leading>
                                                <span class="text-stone-500">₱</span>
                                            </x-slot:leading>
                                        </flux:input>
                                    </div>
                                    <x-input-error for="batch" class="mt-2" />

                                    <div class="flex justify-end">
                                        <flux:button type="button" wire:click="addBatch" variant="filled" :disabled="$isIcsItem">
                                            Add Item
                                        </flux:button>
                                    </div>
                                </div>
                            </div>

                            <!-- Items Table -->
                            @if(!empty($batches))
                                <div class="mt-8 flow-root">
                                    <div class="-mx-4 -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
                                        <div class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">
                                            <div class="overflow-hidden rounded-lg border border-stone-200 dark:border-stone-700">
                                                <table class="min-w-full divide-y divide-stone-200 dark:divide-stone-700">
                                                    <thead class="bg-stone-50 dark:bg-stone-800">
                                                        <tr>
                                                            <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-stone-900 dark:text-stone-100 sm:pl-6">Item</th>
                                                            <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-stone-900 dark:text-stone-100">Qty x Unit Cost</th>
                                                            <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-stone-900 dark:text-stone-100">Total</th>
                                                            <th scope="col" class="relative py-3.5 pl-3 pr-4 sm:pr-6">
                                                                <span class="sr-only">Remove</span>
                                                            </th>
                                                        </tr>
                                                    </thead>
                                                    <tbody class="divide-y divide-stone-200 bg-white dark:divide-stone-600 dark:bg-stone-900">
                                                        @foreach($batches as $index => $batch)
                                                            <tr wire:key="batch-{{ $index }}">
                                                                <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-stone-900 dark:text-stone-100 sm:pl-6">{{ $batch['item_name'] }}</td>
                                                                <td class="whitespace-nowrap px-3 py-4 text-sm text-stone-500 dark:text-stone-400">{{ $batch['quantity'] }} x ₱{{ number_format($batch['unit_cost'], 2) }}</td>
                                                                <td class="whitespace-nowrap px-3 py-4 text-sm text-stone-500 dark:text-stone-400">₱{{ number_format($batch['total_cost'], 2) }}</td>
                                                                <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-6">
                                                                    <button type="button" wire:click="removeBatch({{ $index }})" class="text-rose-600 hover:text-rose-900 dark:text-rose-400 dark:hover:text-rose-300">Remove</button>
                                                                </td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                    <tfoot class="border-t border-stone-200 bg-stone-50 dark:border-stone-700 dark:bg-stone-800">
                                                        <tr>
                                                            <td colspan="2" class="px-3 py-3.5 pl-4 pr-3 text-right text-sm font-semibold text-stone-900 dark:text-stone-100 sm:pl-6">Total</td>
                                                            <td colspan="2" class="px-3 py-3.5 pl-3 pr-4 text-left text-sm font-semibold text-stone-900 dark:text-stone-100 sm:pr-6">₱{{ number_format(array_sum(array_column($batches, 'total_cost')), 2) }}</td>
                                                        </tr>
                                                    </tfoot>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endif
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
                        <flux:select wire:model.live="employee_id" label="Assign To Employee" id="employee_id" required>
                            <option value="">Select an employee</option>
                            @foreach($this->allEmployees as $employee)
                                <option value="{{ $employee->id }}">{{ $employee->name }}</option>
                            @endforeach
                        </flux:select>
                        <x-input-error for="employee_id" class="mt-2" />
                    </div>
                </div>

                <!-- Document Details -->
                <div class="overflow-hidden rounded-lg border border-stone-200 bg-white shadow-sm dark:border-stone-700 dark:bg-stone-800">
                    <div class="border-b border-stone-200 px-4 py-3 dark:border-stone-700">
                        <h3 class="font-semibold text-stone-800 dark:text-stone-200">Document Details</h3>
                    </div>
                    <div class="space-y-6 p-6">
                        <flux:input wire:model="par_number" label="PAR Number" required readonly />
                        
                        <div>
                             <flux:input
                                x-mask="99/99/9999"
                                wire:model.blur="date_acquired"
                                type="text"
                                label="Date Acquired"
                                placeholder="MM/DD/YYYY"
                                required
                                :readonly="$use_current_date"
                            />
                            <x-input-error for="date_acquired" class="mt-2" />
                            <div class="mt-2">
                                <flux:checkbox
                                    wire:model.live="use_current_date"
                                    label="Use current date"
                                    id="use_current_date"
                                />
                            </div>
                        </div>

                        <flux:input wire:model.blur="account_code" label="Account Code" />
                        <x-input-error for="account_code" class="mt-2" />

                        <flux:input wire:model.blur="area_code" label="Area Code" />
                        <x-input-error for="area_code" class="mt-2" />

                        <flux:input wire:model.blur="building_code" label="Building/Room Code" />
                        <x-input-error for="building_code" class="mt-2" />

                        <flux:textarea wire:model.blur="remarks" label="Remarks" placeholder="Add any notes or remarks here..." />
                        <x-input-error for="remarks" class="mt-2" />
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>
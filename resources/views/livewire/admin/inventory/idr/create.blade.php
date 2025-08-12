<?php

use App\Models\Contract;
use App\Models\ContractItem;
use App\Models\Employee;
use App\Models\IdrNumber;
use App\Models\IdrItemBatch;
use App\Models\Supplier;
use App\Services\ToastService;
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
    public ?int $supplier_id = null;
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
    public ?int $estimated_useful_life = null;

    // Display only property
    public ?float $unit_price = 0.0;
    
    public array $batches = [];

    // Auto-complete data
    public string $supplier_search = '';
    public array $supplier_suggestions = [];
    public bool $show_supplier_suggestions = false;
    public ?string $selected_supplier_name = null;
    public bool $creating_new_supplier = false;

    public string $contract_search = '';
    public array $contract_suggestions = [];
    public bool $show_contract_suggestions = false;
    public ?string $selected_contract_name = null;
    public bool $creating_new_contract = false;

    public string $item_search = '';
    public array $item_suggestions = [];
    public bool $show_item_suggestions = false;
    public ?string $selected_item_name = null;

    public string $assigned_employee_search = '';
    public array $assigned_employee_suggestions = [];
    public bool $show_assigned_employee_suggestions = false;
    public ?string $selected_assigned_employee_name = null;

    public string $approving_employee_search = '';
    public array $approving_employee_suggestions = [];
    public bool $show_approving_employee_suggestions = false;
    public ?string $selected_approving_employee_name = null;

    public string $received_by_search = '';
    public array $received_by_suggestions = [];
    public bool $show_received_by_suggestions = false;
    public ?string $selected_received_by_name = null;

    public string $received_from_search = '';
    public array $received_from_suggestions = [];
    public bool $show_received_from_suggestions = false;
    public ?string $selected_received_from_name = null;


    public function mount(): void
    {
        if (!auth()->user()->hasAdminPermission('create_inventory')) {
            abort(403);
        }
        
        $this->date_prepared = now()->format('Y-m-d');
        $this->date_accepted = now()->format('Y-m-d');
        $this->date = now()->format('Y-m-d');
        
        $this->generateIdrNumber();
        $this->updatedQuantity($this->quantity);
    }

    public function generateIdrNumber(): void
    {
        // Find the highest numeric IDR number
        $lastIdr = IdrNumber::orderByRaw('CAST(number AS UNSIGNED) DESC')->first();
        
        if ($lastIdr) {
            // Increment by 1
            $this->number = (string)(((int) $lastIdr->number) + 1);
        } else {
            // If no previous IDR numbers, start with 1
            $this->number = '1';
        }
    }

    // Supplier autocomplete methods
    public function updatedSupplierSearch($value): void
    {
        $this->searchSuppliers($value);
    }

    public function showAllSuppliers(): void
    {
        $this->searchSuppliers($this->supplier_search);
        if (count($this->supplier_suggestions) > 0) {
            $this->show_supplier_suggestions = true;
        }
    }

    public function searchSuppliers($query): void
    {
        if (strlen(trim($query)) === 0) {
            $suppliers = Supplier::orderBy('name')->get();
            $this->supplier_suggestions = $suppliers->map(function ($supplier) {
                return ['id' => $supplier->id, 'name' => $supplier->name, 'type' => 'existing'];
            })->toArray();
        } else {
            $suppliers = Supplier::whereRaw('LOWER(name) LIKE LOWER(?)', ['%' . $query . '%'])
                ->orderBy('name')
                ->get();

            $this->supplier_suggestions = $suppliers->map(function ($supplier) {
                return ['id' => $supplier->id, 'name' => $supplier->name, 'type' => 'existing'];
            })->toArray();

            $exactExists = collect($this->supplier_suggestions)->contains(function ($supplier) use ($query) {
                return strtolower($supplier['name']) === strtolower($query);
            });
            
            if (!$exactExists && strlen(trim($query)) >= 2) {
                array_unshift($this->supplier_suggestions, [
                    'id' => 'new',
                    'name' => $query,
                    'type' => 'new'
                ]);
            }
        }

        $this->show_supplier_suggestions = count($this->supplier_suggestions) > 0;
    }

    public function selectSupplier($supplierData): void
    {
        if ($supplierData['type'] === 'existing') {
            $this->supplier_id = $supplierData['id'];
            $this->supplier_search = $supplierData['name'];
            $this->selected_supplier_name = $supplierData['name'];
            $this->creating_new_supplier = false;
        } elseif ($supplierData['type'] === 'new') {
            $this->supplier_id = null;
            $this->supplier_search = $supplierData['name'];
            $this->selected_supplier_name = $supplierData['name'] . ' (new)';
            $this->creating_new_supplier = true;
        }

        $this->show_supplier_suggestions = false;
        $this->resetContractData();
        $this->dispatch('focus-contract');
    }

    // Contract autocomplete methods
    public function updatedContractSearch($value): void
    {
        $this->searchContracts($value);

        if (!preg_match('/^[a-zA-Z0-9-]*$/', $value)) {
            $this->addError('contract_search_error', 'Contract number can only contain letters, numbers, and hyphens.');
        } else {
            $this->resetValidation('contract_search_error');
        }
    }

    public function showAllContracts(): void
    {
        $this->searchContracts($this->contract_search);
        if (count($this->contract_suggestions) > 0) {
            $this->show_contract_suggestions = true;
        }
    }

    public function searchContracts($query): void
    {
        if (!$this->supplier_id && !$this->creating_new_supplier) {
            $this->contract_suggestions = [];
            $this->show_contract_suggestions = false;
            return;
        }

        if (strlen(trim($query)) === 0) {
            if ($this->supplier_id) {
                $contracts = Contract::where('supplier_id', $this->supplier_id)->orderBy('contract_po_ib_number')->get();
            } else {
                $contracts = Contract::orderBy('contract_po_ib_number')->get();
            }
        } else {
            if ($this->supplier_id) {
                $contracts = Contract::where('supplier_id', $this->supplier_id)
                    ->whereRaw('LOWER(contract_po_ib_number) LIKE LOWER(?)', ['%' . $query . '%'])
                    ->orderBy('contract_po_ib_number')
                    ->get();
            } else {
                $contracts = Contract::whereRaw('LOWER(contract_po_ib_number) LIKE LOWER(?)', ['%' . $query . '%'])
                    ->orderBy('contract_po_ib_number')
                    ->get();
            }
        }

        $this->contract_suggestions = $contracts->map(function ($contract) {
            return [
                'id' => $contract->id,
                'name' => $contract->contract_po_ib_number,
                'type' => 'existing',
                'supplier_name' => $contract->supplier->name ?? 'Unknown'
            ];
        })->toArray();

        $exactExists = collect($this->contract_suggestions)->contains(function ($contract) use ($query) {
            return strtolower($contract['name']) === strtolower($query);
        });

        if (!$exactExists && strlen(trim($query)) >= 2) {
            array_unshift($this->contract_suggestions, [
                'id' => 'new',
                'name' => $query,
                'type' => 'new'
            ]);
        }

        $this->show_contract_suggestions = count($this->contract_suggestions) > 0;
    }

    public function selectContract($contractData): void
    {
        if ($contractData['type'] === 'existing') {
            $this->contract_id = $contractData['id'];
            $this->contract_search = $contractData['name'];
            $this->selected_contract_name = $contractData['name'];
            $this->creating_new_contract = false;
        } elseif ($contractData['type'] === 'new') {
            $this->contract_id = null;
            $this->contract_search = $contractData['name'];
            $this->selected_contract_name = $contractData['name'] . ' (new)';
            $this->creating_new_contract = true;
        }

        $this->show_contract_suggestions = false;
        $this->resetItemData();
        $this->dispatch('focus-item');
    }

    // Item autocomplete methods
    public function updatedItemSearch($value): void
    {
        $this->searchItems($value);
    }

    public function showAllItems(): void
    {
        $this->searchItems($this->item_search);
        if (count($this->item_suggestions) > 0) {
            $this->show_item_suggestions = true;
        }
    }

    public function searchItems($query): void
    {
        if (!$this->contract_id && !$this->creating_new_contract) {
            $this->item_suggestions = [];
            $this->show_item_suggestions = false;
            return;
        }

        if ($this->contract_id) {
            $items = ContractItem::where('contract_id', $this->contract_id)
                ->with('itemSpecification.itemCatalog')
                ->when(strlen(trim($query)) > 0, function ($q) use ($query) {
                    $q->whereHas('itemSpecification.itemCatalog', function ($subq) use ($query) {
                        $subq->whereRaw('LOWER(name) LIKE LOWER(?)', ['%' . $query . '%']);
                    });
                })
                ->get();

            $this->item_suggestions = $items->map(function ($item) {
                return [
                    'id' => $item->id,
                    'name' => $item->itemSpecification->itemCatalog->name,
                    'type' => 'existing',
                    'unit_price' => $item->unit_price,
                    'brand' => $item->itemSpecification->brand ?? '',
                    'model' => $item->itemSpecification->model ?? ''
                ];
            })->toArray();
        } else {
            $this->item_suggestions = [];
        }

        $this->show_item_suggestions = count($this->item_suggestions) > 0;
    }

    public function selectItem($itemData): void
    {
        if ($itemData['type'] === 'existing') {
            $this->contract_item_id = $itemData['id'];
            $this->item_search = $itemData['name'];
            $this->selected_item_name = $itemData['name'];
            $this->unit_price = $itemData['unit_price'] ?? 0.0;
        }

        $this->show_item_suggestions = false;
        $this->dispatch('focus-assigned-employee');
    }

    // Employee autocomplete methods
    public function updatedAssignedEmployeeSearch($value): void
    {
        $this->searchEmployees($value, 'assigned');
    }

    public function showAllAssignedEmployees(): void
    {
        $this->searchEmployees($this->assigned_employee_search, 'assigned');
        if (count($this->assigned_employee_suggestions) > 0) {
            $this->show_assigned_employee_suggestions = true;
        }
    }

    public function updatedApprovingEmployeeSearch($value): void
    {
        $this->searchEmployees($value, 'approving');
    }

    public function showAllApprovingEmployees(): void
    {
        $this->searchEmployees($this->approving_employee_search, 'approving');
        if (count($this->approving_employee_suggestions) > 0) {
            $this->show_approving_employee_suggestions = true;
        }
    }

    public function updatedReceivedBySearch($value): void
    {
        $this->searchEmployees($value, 'received_by');
    }

    public function showAllReceivedByEmployees(): void
    {
        $this->searchEmployees($this->received_by_search, 'received_by');
        if (count($this->received_by_suggestions) > 0) {
            $this->show_received_by_suggestions = true;
        }
    }

    public function updatedReceivedFromSearch($value): void
    {
        $this->searchEmployees($value, 'received_from');
    }

    public function showAllReceivedFromEmployees(): void
    {
        $this->searchEmployees($this->received_from_search, 'received_from');
        if (count($this->received_from_suggestions) > 0) {
            $this->show_received_from_suggestions = true;
        }
    }

    public function searchEmployees($query, $type): void
    {
        if (strlen(trim($query)) === 0) {
            $employees = Employee::with('division')->orderBy('name')->get();
        } else {
            $employees = Employee::with('division')
                ->where(function ($q) use ($query) {
                    $q->whereRaw('LOWER(name) LIKE LOWER(?)', ['%' . $query . '%']);
                })
                ->orderBy('name')
                ->get();
        }

        $suggestions = $employees->map(function ($employee) {
            $description_parts = [];
            if ($employee->division) {
                $description_parts[] = $employee->division->name;
            }
            if ($employee->position) {
                $description_parts[] = $employee->position;
            }

            return [
                'id' => $employee->id,
                'name' => $employee->name,
                'description' => implode(' / ', $description_parts),
                'type' => 'existing'
            ];
        })->toArray();

        switch ($type) {
            case 'assigned':
                $this->assigned_employee_suggestions = $suggestions;
                $this->show_assigned_employee_suggestions = count($suggestions) > 0;
                break;
            case 'approving':
                $this->approving_employee_suggestions = $suggestions;
                $this->show_approving_employee_suggestions = count($suggestions) > 0;
                break;
            case 'received_by':
                $this->received_by_suggestions = $suggestions;
                $this->show_received_by_suggestions = count($suggestions) > 0;
                break;
            case 'received_from':
                $this->received_from_suggestions = $suggestions;
                $this->show_received_from_suggestions = count($suggestions) > 0;
                break;
        }
    }

    public function selectAssignedEmployee($employeeData): void
    {
        $this->assigned_employee_id = $employeeData['id'];
        $this->assigned_employee_search = $employeeData['name'];
        $this->selected_assigned_employee_name = $employeeData['name'];
        $this->show_assigned_employee_suggestions = false;
        $this->dispatch('focus-approving-employee');
    }

    public function selectApprovingEmployee($employeeData): void
    {
        $this->approving_employee_id = $employeeData['id'];
        $this->approving_employee_search = $employeeData['name'];
        $this->selected_approving_employee_name = $employeeData['name'];
        $this->show_approving_employee_suggestions = false;
        $this->dispatch('focus-received-by');
    }

    public function selectReceivedByEmployee($employeeData): void
    {
        $this->received_by_id = $employeeData['id'];
        $this->received_by_search = $employeeData['name'];
        $this->selected_received_by_name = $employeeData['name'];
        $this->show_received_by_suggestions = false;
        $this->dispatch('focus-received-from');
    }

    public function selectReceivedFromEmployee($employeeData): void
    {
        $this->received_from_id = $employeeData['id'];
        $this->received_from_search = $employeeData['name'];
        $this->selected_received_from_name = $employeeData['name'];
        $this->show_received_from_suggestions = false;
        $this->dispatch('focus-estimated-useful-life');
    }

    private function resetContractData(): void
    {
        $this->contract_id = null;
        $this->contract_search = '';
        $this->selected_contract_name = null;
        $this->creating_new_contract = false;
        $this->resetItemData();
    }

    private function resetItemData(): void
    {
        $this->contract_item_id = null;
        $this->item_search = '';
        $this->selected_item_name = null;
        $this->unit_price = 0.0;
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
            'number' => ['required', 'string', 'max:255', Rule::unique('idr_number', 'number')],
            'supplier_id' => ['required_unless:creating_new_supplier,true', 'nullable', 'integer', Rule::exists('suppliers', 'id')],
            'contract_id' => ['required_unless:creating_new_contract,true', 'nullable', 'integer', Rule::exists('contracts', 'id')],
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
            'estimated_useful_life' => ['nullable', 'integer', 'min:1'],
            'remarks' => ['nullable', 'string'],
            'batches.*.identification_data' => ['nullable', 'string', 'max:255'],
        ]);

        DB::transaction(function () use ($validated) {
            // Create new supplier if needed
            if ($this->creating_new_supplier) {
                $supplier = Supplier::create(['name' => $this->supplier_search]);
                $validated['supplier_id'] = $supplier->id;
            }

            // Create new contract if needed
            if ($this->creating_new_contract) {
                $contract = Contract::create([
                    'contract_po_ib_number' => $this->contract_search,
                    'supplier_id' => $validated['supplier_id'],
                    'procurement_type' => 'Purchase Order',
                    'date_signed' => now(),
                ]);
                $validated['contract_id'] = $contract->id;
            }

            $idr = IdrNumber::create($validated);
            foreach ($this->batches as $batchData) {
                $idr->itemBatches()->create(['identification_data' => $batchData['identification_data'] ?? null]);
            }
        });

        ToastService::created($this, "IDR record #{$this->number}");
        
        $this->dispatch('idr-created');
        session()->flash('highlighted_idr', $idr->id);
        $this->redirectRoute('admin.inventory.idr.index', navigate: true);
    }
}; ?>

<form wire:submit="store" novalidate>
    <div class="border-b border-stone-200 pb-4 dark:border-stone-700">
        <div class="flex items-center justify-between">
            <!-- Breadcrumbs as Title -->
            <div>
                <flux:breadcrumbs class="text-2xl font-semibold">
                    <flux:breadcrumbs.item :href="route('admin.dashboard')" wire:navigate icon="home" class="text-xl sm:text-2xl font-semibold text-stone-700 dark:text-stone-300" />
                    <flux:breadcrumbs.item class="text-xl sm:text-2xl font-semibold text-stone-500 dark:text-stone-400">Inventory</flux:breadcrumbs.item>
                    <flux:breadcrumbs.item :href="route('admin.inventory.idr.index')" wire:navigate class="text-xl sm:text-2xl font-semibold text-stone-700 dark:text-stone-300">IDR Management</flux:breadcrumbs.item>
                    <flux:breadcrumbs.item class="text-xl sm:text-2xl font-semibold text-stone-900 dark:text-stone-100">Create IDR</flux:breadcrumbs.item>
                </flux:breadcrumbs>
            </div>
             <div class="flex items-center gap-x-4">
                <x-action-message class="me-3" on="idr-created">
                    {{ __('Record saved successfully.') }}
                </x-action-message>
                <flux:button :href="route('admin.inventory.idr.index')" wire:navigate variant="ghost">
                    Cancel
                </flux:button>
                <flux:button type="submit" variant="primary" wire:loading.attr="disabled" wire:target="store">
                    <span wire:loading.remove wire:target="store">Save Record</span>
                    <span wire:loading wire:target="store">Saving...</span>
                </flux:button>
            </div>
        </div>
    </div>

    <div class="mt-6">
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2 xl:grid-cols-3">
            <!-- Column 1: Item Information -->
            <div class="space-y-6">
                <!-- Supplier & Contract Section -->
                <div class="overflow-hidden rounded-lg border border-stone-200 bg-white shadow-sm dark:border-stone-700 dark:bg-stone-800">
                    <div class="border-b border-stone-200 px-4 py-3 dark:border-stone-700">
                        <h3 class="font-semibold text-stone-800 dark:text-stone-200">Supplier & Contract</h3>
                                                    </div>
                    <div class="p-4">
                        <div class="grid grid-cols-1 gap-x-6 gap-y-6 sm:grid-cols-2">
                            <div>
                                <x-autocomplete id="supplier_search" wire:model.live="supplier_search" wire:suggestions="supplier_suggestions" wire:showSuggestions="show_supplier_suggestions" label="Supplier" placeholder="Type to search suppliers..." required onFocus="$wire.showAllSuppliers()" onSelect="$wire.selectSupplier" />
                                @error('supplier_id')
                                    <div class="mt-2 flex items-center text-sm text-red-600 dark:text-red-400">
                                        <svg class="mr-2 h-5 w-5 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.21 3.03-1.742 3.03H4.42c-1.532 0-2.492-1.696-1.742-3.03l5.58-9.92zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" />
                                        </svg>
                                        <span>{{ $message }}</span>
                                    </div>
                                @enderror
                                @if ($creating_new_supplier)
                                    <div class="mt-2 flex items-center rounded-lg bg-green-50 p-2 text-sm text-green-700 dark:bg-green-800/20 dark:text-green-400" role="alert">
                                        <svg class="mr-2 h-4 w-4 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5ZM9.5 4a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3ZM12 15H8a1 1 0 0 1 0-2h1v-3H8a1 1 0 0 1 0-2h2a1 1 0 0 1 1 1v4h1a1 1 0 0 1 0 2Z" />
                                        </svg>
                                        <span class="font-medium">New supplier will be created upon saving.</span>
                                    </div>
                                @endif
                            </div>

                            <div>
                                <x-autocomplete id="contract_search" wire:model.live="contract_search" wire:suggestions="contract_suggestions" wire:showSuggestions="show_contract_suggestions" label="Contract/PO/IB No." placeholder="{{ $creating_new_supplier ? 'Enter new contract number...' : 'Type to search contracts...' }}" :disabled="!$this->supplier_id && !$this->creating_new_supplier" required onFocus="$wire.showAllContracts()" onSelect="$wire.selectContract" />
                                @error('contract_id')
                                    <div class="mt-2 flex items-center text-sm text-red-600 dark:text-red-400">
                                        <svg class="mr-2 h-5 w-5 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.21 3.03-1.742 3.03H4.42c-1.532 0-2.492-1.696-1.742-3.03l5.58-9.92zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" />
                                        </svg>
                                        <span>{{ $message }}</span>
                                                    </div>
                                @enderror
                                <x-input-error for="contract_search_error" class="mt-2" />
                                @if ($creating_new_contract)
                                    <div class="mt-2 flex items-center rounded-lg bg-green-50 p-2 text-sm text-green-700 dark:bg-green-800/20 dark:text-green-400" role="alert">
                                        <svg class="mr-2 h-4 w-4 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5ZM9.5 4a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3ZM12 15H8a1 1 0 0 1 0-2h1v-3H8a1 1 0 0 1 0-2h2a1 1 0 0 1 1 1v4h1a1 1 0 0 1 0 2Z" />
                                        </svg>
                                        <span class="font-medium">New contract will be created upon saving.</span>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Item Information Section -->
                <div class="overflow-hidden rounded-lg border border-stone-200 bg-white shadow-sm dark:border-stone-700 dark:bg-stone-800">
                    <div class="border-b border-stone-200 px-4 py-3 dark:border-stone-700">
                        <h3 class="font-semibold text-stone-800 dark:text-stone-200">Item Information</h3>
                                    </div>
                    <div class="p-4">
                        <div class="space-y-4">
                            <div>
                                <x-autocomplete id="item_search" wire:model.live="item_search" wire:suggestions="item_suggestions" wire:showSuggestions="show_item_suggestions" label="Select Item" placeholder="Search by item name..." required :disabled="!$this->contract_id && !$this->creating_new_contract" onFocus="$wire.showAllItems()" onSelect="$wire.selectItem" />
                                @error('contract_item_id')
                                    <div class="mt-2 flex items-center text-sm text-red-600 dark:text-red-400">
                                        <svg class="mr-2 h-5 w-5 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.21 3.03-1.742 3.03H4.42c-1.532 0-2.492-1.696-1.742-3.03l5.58-9.92zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" />
                                        </svg>
                                        <span>{{ $message }}</span>
                                    </div>
                                @enderror
                            </div>

                            <div>
                                <flux:input wire:model="unit_price" label="Unit Cost" type="text" :disabled="true">
                                    <x-slot:leading><span class="text-stone-500">₱</span></x-slot:leading>
                                </flux:input>
                            </div>
                            </div>
                        </div>
                    </div>
                </div>

            <!-- Column 2: Personnel & Document Details -->
                        <div class="space-y-6">
                <!-- Personnel Section -->
                <div class="overflow-hidden rounded-lg border border-stone-200 bg-white shadow-sm dark:border-stone-700 dark:bg-stone-800">
                    <div class="border-b border-stone-200 px-4 py-3 dark:border-stone-700">
                        <h3 class="font-semibold text-stone-800 dark:text-stone-200">Personnel</h3>
                                        </div>
                    <div class="space-y-4 p-4">
                        <div>
                            <x-autocomplete id="assigned_employee_search" wire:model.live="assigned_employee_search" wire:suggestions="assigned_employee_suggestions" wire:showSuggestions="show_assigned_employee_suggestions" label="Assigned To (Stock Officer)" placeholder="Search employees..." required onFocus="$wire.showAllAssignedEmployees()" onSelect="$wire.selectAssignedEmployee" />
                            @error('assigned_employee_id')
                                <div class="mt-2 flex items-center text-sm text-red-600 dark:text-red-400">
                                    <svg class="mr-2 h-5 w-5 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.21 3.03-1.742 3.03H4.42c-1.532 0-2.492-1.696-1.742-3.03l5.58-9.92zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" />
                                    </svg>
                                    <span>{{ $message }}</span>
                                    </div>
                            @enderror
                            </div>

                        <div>
                            <x-autocomplete id="approving_employee_search" wire:model.live="approving_employee_search" wire:suggestions="approving_employee_suggestions" wire:showSuggestions="show_approving_employee_suggestions" label="Approving Official" placeholder="Search employees..." required onFocus="$wire.showAllApprovingEmployees()" onSelect="$wire.selectApprovingEmployee" />
                            @error('approving_employee_id')
                                <div class="mt-2 flex items-center text-sm text-red-600 dark:text-red-400">
                                    <svg class="mr-2 h-5 w-5 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.21 3.03-1.742 3.03H4.42c-1.532 0-2.492-1.696-1.742-3.03l5.58-9.92zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" />
                                    </svg>
                                    <span>{{ $message }}</span>
                        </div>
                            @enderror
                    </div>

                        <div>
                            <x-autocomplete id="received_by_search" wire:model.live="received_by_search" wire:suggestions="received_by_suggestions" wire:showSuggestions="show_received_by_suggestions" label="Received By" placeholder="Search employees..." required onFocus="$wire.showAllReceivedByEmployees()" onSelect="$wire.selectReceivedByEmployee" />
                            @error('received_by_id')
                                <div class="mt-2 flex items-center text-sm text-red-600 dark:text-red-400">
                                    <svg class="mr-2 h-5 w-5 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.21 3.03-1.742 3.03H4.42c-1.532 0-2.492-1.696-1.742-3.03l5.58-9.92zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" />
                                    </svg>
                                    <span>{{ $message }}</span>
                </div>
                            @enderror
        </div>

                        <div>
                            <x-autocomplete id="received_from_search" wire:model.live="received_from_search" wire:suggestions="received_from_suggestions" wire:showSuggestions="show_received_from_suggestions" label="Received From (Issued By)" placeholder="Search employees..." required onFocus="$wire.showAllReceivedFromEmployees()" onSelect="$wire.selectReceivedFromEmployee" />
                            @error('received_from_id')
                                <div class="mt-2 flex items-center text-sm text-red-600 dark:text-red-400">
                                    <svg class="mr-2 h-5 w-5 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.21 3.03-1.742 3.03H4.42c-1.532 0-2.492-1.696-1.742-3.03l5.58-9.92zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" />
                                    </svg>
                                    <span>{{ $message }}</span>
                                </div>
                            @enderror
                        </div>
                    </div>
                </div>
                 <div class="overflow-hidden rounded-lg border border-stone-200 bg-white shadow-sm dark:border-stone-700 dark:bg-stone-800">
                    <div class="border-b border-stone-200 px-4 py-3 dark:border-stone-700">
                        <h3 class="font-semibold text-stone-800 dark:text-stone-200">Document Details</h3>
                    </div>
                    <div class="space-y-4 p-4">
                        <div class="grid grid-cols-1 gap-x-6 sm:grid-cols-2">
                            <div>
                                <label for="idr_number_input" class="mb-1 block text-sm font-medium text-stone-700 dark:text-stone-300">
                                    IDR Number <span class="text-stone-500 dark:text-stone-400 font-normal">(Auto-generated)</span>
                                </label>
                        <div class="relative">
                            <flux:input 
                                        id="idr_number_input"
                                        wire:model.blur="number" 
                                        type="text" 
                                        readonly
                                        tabindex="-1"
                                        class="bg-stone-50 dark:bg-stone-800 text-stone-600 dark:text-stone-400 cursor-not-allowed border-stone-200 dark:border-stone-700" />
                                    <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                                        <x-flux::icon.lock-closed class="h-4 w-4 text-stone-400 dark:text-stone-500" />
                                    </div>
                                </div>
                            </div>
                            <div>
                                <flux:input wire:model="estimated_useful_life" type="number" label="Estimated Useful Life (Years)" min="1" placeholder="Optional" />
                            </div>
                        </div>

                                                <div class="grid grid-cols-1 gap-x-6 sm:grid-cols-2">
                            <div>
                                <flux:input wire:model="inventory_code" label="Inventory Code" required />
                                <x-input-error for="inventory_code" class="mt-2" />
                                </div>
                            <div>
                                <flux:input wire:model="ors" label="ORS Number" required />
                                <x-input-error for="ors" class="mt-2" />
                            </div>
                        </div>

                        <div class="grid grid-cols-1 gap-x-6 sm:grid-cols-2">
                            <div>
                                <flux:input wire:model="date_prepared" type="date" label="Date Prepared" required />
                                <x-input-error for="date_prepared" class="mt-2" />
                            </div>
                            <div>
                                <flux:input wire:model="date_accepted" type="date" label="Date Accepted" required />
                                <x-input-error for="date_accepted" class="mt-2" />
                            </div>
                        </div>

                        <div>
                            <flux:input wire:model="date" type="date" label="Date (IDR)" required />
                            <x-input-error for="date" class="mt-2" />
                                </div>
                        <div>
                            <flux:textarea wire:model="remarks" label="Remarks" placeholder="Add any notes or remarks here..." rows="6" />
                            <x-input-error for="remarks" class="mt-2" />
                        </div>
                    </div>
                </div>
                        </div>

            <!-- Column 3: Batches -->
            <div class="space-y-6 lg:col-span-2 xl:col-span-1">
                <!-- Batches & Serial Numbers -->
                <div class="overflow-hidden rounded-lg border border-stone-200 bg-white shadow-sm dark:border-stone-700 dark:bg-stone-800">
                    <div class="border-b border-stone-200 px-4 py-3 dark:border-stone-700">
                        <h3 class="font-semibold text-stone-800 dark:text-stone-200">Batches</h3>
                                </div>
                    <div class="p-4">
                        <div class="space-y-4">
                                                        <div class="w-full">
                                <x-quantity-input 
                                    id="quantity"
                                    wire:model.live="quantity" 
                                    label="Total Quantity / Number of Batches" 
                                    min="1" 
                                required
                                    class="w-full" 
                                />
                                <x-input-error for="quantity" class="mt-2" />
                            </div>

                            <div class="space-y-4">
                                @foreach ($batches as $batchIndex => $batch)
                                    <div wire:key="batch-{{ $batchIndex }}" class="rounded-lg border border-stone-300 bg-white p-0 dark:border-stone-600 dark:bg-stone-800/50">
                                        <div class="flex items-center justify-between p-3 bg-stone-50 dark:bg-stone-700/50 rounded-t-lg border-b border-stone-200 dark:border-stone-700">
                                            <h4 class="font-semibold text-stone-800 dark:text-stone-200 flex items-center space-x-2">
                                                <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-stone-200 dark:bg-stone-600 text-sm font-medium">
                                                    {{ $loop->iteration }}
                                                </span>
                                                <span>Batch #{{ $loop->iteration }}</span>
                                            </h4>
                                            <div class="flex items-center space-x-2">
                                                @if ($quantity > 1)
                                                    <flux:button type="button" variant="danger" size="sm" wire:click.prevent="removeBatch({{ $batchIndex }})">
                                                        <x-flux::icon.trash class="h-4 w-4" />
                                                    </flux:button>
                                                @endif
                                </div>
                        </div>

                                        <div class="p-3">
                            <flux:input 
                                                wire:model="batches.{{ $batchIndex }}.identification_data" 
                                                label="Serial Number/Asset Tag" 
                                                placeholder="Enter serial number, asset tag or other identifying info" />
                                        </div>
                                    </div>
                                        @endforeach
                                
                                <div class="mt-4 text-center">
                                    <flux:button type="button" variant="outline" wire:click.prevent="$set('quantity', {{ $quantity + 1 }})" class="w-full">
                                        <div class="flex items-center justify-center">
                                            <x-flux::icon.plus class="mr-2 h-4 w-4" />
                                            <span>Add Another Batch</span>
                                </div>
                                    </flux:button>
                        </div>
                    </div>
                </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form> 
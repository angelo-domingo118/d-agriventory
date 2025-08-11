<?php

use App\Models\Contract;
use App\Models\ContractItem;
use App\Models\Employee;
use App\Models\IdrNumber;
use App\Models\IdrItemBatch;
use App\Models\Supplier;
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
            $employees = Employee::with('division', 'position')->orderBy('name')->get();
        } else {
            $employees = Employee::with('division', 'position')
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
                $description_parts[] = $employee->position->title;
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

        session()->flash('success', "IDR record #{$this->number} created successfully.");
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
                    <div class="border-b border-stone-200 px-4 py-3 dark:border-stone-700"><h3 class="font-semibold text-stone-800 dark:text-stone-200">Supplier & Contract Information</h3></div>
                    <div class="p-6">
                        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                            <div class="relative">
                                <flux:input 
                                    wire:model.live.debounce.300ms="supplier_search" 
                                    wire:focus="showAllSuppliers" 
                                    label="Supplier" 
                                    placeholder="Search or type to create new supplier..."
                                    required
                                />
                                @error('supplier_search') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                
                                @if($show_supplier_suggestions && count($supplier_suggestions) > 0)
                                    <div class="absolute z-50 mt-1 w-full rounded-md border border-stone-300 bg-white shadow-lg dark:border-stone-600 dark:bg-stone-800">
                                        <ul class="max-h-60 overflow-auto rounded-md py-1">
                                            @foreach($supplier_suggestions as $supplier)
                                                <li wire:click="selectSupplier(@js($supplier))" class="cursor-pointer px-3 py-2 hover:bg-stone-100 dark:hover:bg-stone-700 {{ $supplier['type'] === 'new' ? 'border-b border-stone-200 bg-blue-50 dark:border-stone-600 dark:bg-blue-900/20' : '' }}">
                                                    <div class="font-medium text-stone-900 dark:text-stone-100">
                                                        {{ $supplier['name'] }}
                                                        @if($supplier['type'] === 'new')
                                                            <span class="text-blue-600 dark:text-blue-400">(Create New)</span>
                                                        @endif
                                                    </div>
                                                </li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif
                            </div>

                            <div class="relative">
                                <flux:input 
                                    wire:model.live.debounce.300ms="contract_search" 
                                    wire:focus="showAllContracts" 
                                    label="Contract / PO Number" 
                                    placeholder="Search or type to create new contract..."
                                    :disabled="!$supplier_id && !$creating_new_supplier"
                                    required
                                />
                                @error('contract_search') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                @error('contract_search_error') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                
                                @if($show_contract_suggestions && count($contract_suggestions) > 0)
                                    <div class="absolute z-50 mt-1 w-full rounded-md border border-stone-300 bg-white shadow-lg dark:border-stone-600 dark:bg-stone-800">
                                        <ul class="max-h-60 overflow-auto rounded-md py-1">
                                            @foreach($contract_suggestions as $contract)
                                                <li wire:click="selectContract(@js($contract))" class="cursor-pointer px-3 py-2 hover:bg-stone-100 dark:hover:bg-stone-700 {{ $contract['type'] === 'new' ? 'border-b border-stone-200 bg-blue-50 dark:border-stone-600 dark:bg-blue-900/20' : '' }}">
                                                    <div class="font-medium text-stone-900 dark:text-stone-100">
                                                        {{ $contract['name'] }}
                                                        @if($contract['type'] === 'new')
                                                            <span class="text-blue-600 dark:text-blue-400">(Create New)</span>
                                                        @endif
                                                    </div>
                                                    @if($contract['type'] === 'existing' && isset($contract['supplier_name']))
                                                        <div class="text-sm text-stone-500 dark:text-stone-400">{{ $contract['supplier_name'] }}</div>
                                                    @endif
                                                </li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <div class="overflow-hidden rounded-lg border border-stone-200 bg-white shadow-sm dark:border-stone-700 dark:bg-stone-800">
                    <div class="border-b border-stone-200 px-4 py-3 dark:border-stone-700"><h3 class="font-semibold text-stone-800 dark:text-stone-200">Item Information</h3></div>
                    <div class="p-6">
                        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                            <div class="relative">
                                <flux:input 
                                    wire:model.live.debounce.300ms="item_search" 
                                    wire:focus="showAllItems" 
                                    label="Item / Article" 
                                    placeholder="Search items from selected contract..."
                                    :disabled="!$contract_id && !$creating_new_contract"
                                    required
                                />
                                @error('item_search') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                
                                @if($show_item_suggestions && count($item_suggestions) > 0)
                                    <div class="absolute z-50 mt-1 w-full rounded-md border border-stone-300 bg-white shadow-lg dark:border-stone-600 dark:bg-stone-800">
                                        <ul class="max-h-60 overflow-auto rounded-md py-1">
                                            @foreach($item_suggestions as $item)
                                                <li wire:click="selectItem(@js($item))" class="cursor-pointer px-3 py-2 hover:bg-stone-100 dark:hover:bg-stone-700">
                                                    <div class="font-medium text-stone-900 dark:text-stone-100">{{ $item['name'] }}</div>
                                                    @if($item['brand'] || $item['model'])
                                                        <div class="text-sm text-stone-500 dark:text-stone-400">{{ collect([$item['brand'], $item['model']])->filter()->join(' / ') }}</div>
                                                    @endif
                                                    <div class="text-sm text-green-600 dark:text-green-400">₱{{ number_format($item['unit_price'], 2) }}</div>
                                                </li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif
                            </div>

                            <div>
                                <flux:input wire:model="unit_price" label="Unit Cost" type="text" :disabled="true">
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
                        <div class="relative">
                            <flux:input 
                                wire:model.live.debounce.300ms="assigned_employee_search" 
                                wire:focus="showAllAssignedEmployees" 
                                label="Assigned To (Stock Officer)" 
                                placeholder="Search employees..."
                                required
                            />
                            @error('assigned_employee_search') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            
                            @if($show_assigned_employee_suggestions && count($assigned_employee_suggestions) > 0)
                                <div class="absolute z-50 mt-1 w-full rounded-md border border-stone-300 bg-white shadow-lg dark:border-stone-600 dark:bg-stone-800">
                                    <ul class="max-h-60 overflow-auto rounded-md py-1">
                                        @foreach($assigned_employee_suggestions as $employee)
                                            <li wire:click="selectAssignedEmployee(@js($employee))" class="cursor-pointer px-3 py-2 hover:bg-stone-100 dark:hover:bg-stone-700">
                                                <div class="font-medium text-stone-900 dark:text-stone-100">{{ $employee['name'] }}</div>
                                                @if($employee['description'])
                                                    <div class="text-sm text-stone-500 dark:text-stone-400">{{ $employee['description'] }}</div>
                                                @endif
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif
                        </div>

                        <div class="relative">
                            <flux:input 
                                wire:model.live.debounce.300ms="approving_employee_search" 
                                wire:focus="showAllApprovingEmployees" 
                                label="Approving Official" 
                                placeholder="Search employees..."
                                required
                            />
                            @error('approving_employee_search') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            
                            @if($show_approving_employee_suggestions && count($approving_employee_suggestions) > 0)
                                <div class="absolute z-50 mt-1 w-full rounded-md border border-stone-300 bg-white shadow-lg dark:border-stone-600 dark:bg-stone-800">
                                    <ul class="max-h-60 overflow-auto rounded-md py-1">
                                        @foreach($approving_employee_suggestions as $employee)
                                            <li wire:click="selectApprovingEmployee(@js($employee))" class="cursor-pointer px-3 py-2 hover:bg-stone-100 dark:hover:bg-stone-700">
                                                <div class="font-medium text-stone-900 dark:text-stone-100">{{ $employee['name'] }}</div>
                                                @if($employee['description'])
                                                    <div class="text-sm text-stone-500 dark:text-stone-400">{{ $employee['description'] }}</div>
                                                @endif
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif
                        </div>

                        <div class="relative">
                            <flux:input 
                                wire:model.live.debounce.300ms="received_by_search" 
                                wire:focus="showAllReceivedByEmployees" 
                                label="Received By" 
                                placeholder="Search employees..."
                                required
                            />
                            @error('received_by_search') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            
                            @if($show_received_by_suggestions && count($received_by_suggestions) > 0)
                                <div class="absolute z-50 mt-1 w-full rounded-md border border-stone-300 bg-white shadow-lg dark:border-stone-600 dark:bg-stone-800">
                                    <ul class="max-h-60 overflow-auto rounded-md py-1">
                                        @foreach($received_by_suggestions as $employee)
                                            <li wire:click="selectReceivedByEmployee(@js($employee))" class="cursor-pointer px-3 py-2 hover:bg-stone-100 dark:hover:bg-stone-700">
                                                <div class="font-medium text-stone-900 dark:text-stone-100">{{ $employee['name'] }}</div>
                                                @if($employee['description'])
                                                    <div class="text-sm text-stone-500 dark:text-stone-400">{{ $employee['description'] }}</div>
                                                @endif
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif
                        </div>

                        <div class="relative">
                            <flux:input 
                                wire:model.live.debounce.300ms="received_from_search" 
                                wire:focus="showAllReceivedFromEmployees" 
                                label="Received From (Issued By)" 
                                placeholder="Search employees..."
                                required
                            />
                            @error('received_from_search') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            
                            @if($show_received_from_suggestions && count($received_from_suggestions) > 0)
                                <div class="absolute z-50 mt-1 w-full rounded-md border border-stone-300 bg-white shadow-lg dark:border-stone-600 dark:bg-stone-800">
                                    <ul class="max-h-60 overflow-auto rounded-md py-1">
                                        @foreach($received_from_suggestions as $employee)
                                            <li wire:click="selectReceivedFromEmployee(@js($employee))" class="cursor-pointer px-3 py-2 hover:bg-stone-100 dark:hover:bg-stone-700">
                                                <div class="font-medium text-stone-900 dark:text-stone-100">{{ $employee['name'] }}</div>
                                                @if($employee['description'])
                                                    <div class="text-sm text-stone-500 dark:text-stone-400">{{ $employee['description'] }}</div>
                                                @endif
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="overflow-hidden rounded-lg border border-stone-200 bg-white shadow-sm dark:border-stone-700 dark:bg-stone-800">
                    <div class="border-b border-stone-200 px-4 py-3 dark:border-stone-700"><h3 class="font-semibold text-stone-800 dark:text-stone-200">Document Details</h3></div>
                    <div class="space-y-6 p-6">
                         <flux:input wire:model="number" label="IDR Number" required />
                         <flux:input wire:model="inventory_code" label="Inventory Code" required />
                         <flux:input wire:model="ors" label="ORS Number" required />
                         <flux:input wire:model="estimated_useful_life" type="number" label="Estimated Useful Life (Years)" min="1" placeholder="e.g. 5" />
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
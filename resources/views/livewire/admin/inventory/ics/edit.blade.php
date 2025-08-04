<?php

use App\Models\Contract;
use App\Models\ContractItem;
use App\Models\Employee;
use App\Models\IcsNumber;
use App\Models\ItemsCatalog;
use App\Models\ItemSpecification;
use App\Models\SecondaryCategory;
use App\Models\PrimaryCategory;
use App\Models\Supplier;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

new #[Layout('components.layouts.app')] class extends Component {
    public IcsNumber $icsNumber;

    public bool $showTransferModal = false;

    // Form state
    public string $ics_number = '';
    public ?int $supplier_id = null;
    public ?int $contract_id = null;
    public ?int $items_catalog_id = null;
    public ?string $item_specification_id = null;
    public ?int $contract_item_id = null;
    public ?int $assigned_employee_id = null;
    public string $ics_type = 'SPLV';
    public int $quantity = 1;
    public ?int $estimated_useful_life = 1;
    public string $date_prepared = '';
    public ?string $date_accepted = null;
    public ?string $remarks = null;

    // New fields for main item
    public ?string $main_item_brand = null;
    public ?string $main_item_model = null;
    public ?string $detailed_specifications = null;
    public ?string $main_item_serial_number = null;

    // Category fields for new items
    public ?int $primary_category_id = null;
    public ?int $secondary_category_id = null;
    public ?string $unit_of_measure = '';

    // Display only property
    public ?float $unit_price = 0.0;
    public bool $isParItem = false;
    public bool $isDesktopComputer = false;

    // Transfer state
    public ?int $transfer_to_employee_id = null;
    public string $transfer_date = '';
    public ?int $original_assigned_employee_id = null; // Track original employee for transfer detection

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
    public bool $creating_new_item = false;

    public string $specification_search = '';
    public array $specification_suggestions = [];
    public bool $show_specification_suggestions = false;
    public ?string $selected_specification_name = null;
    public bool $creating_new_specification = false;

    public string $employee_search = '';
    public array $employee_suggestions = [];
    public bool $show_employee_suggestions = false;
    public ?string $selected_employee_name = null;
    public bool $creating_new_employee = false;

    // Unit of measure autocomplete
    public string $unit_search = '';
    public array $unit_suggestions = [];
    public bool $show_unit_suggestions = false;
    public ?string $selected_unit = null;
    public bool $creating_new_unit = false;

    // Brand autocomplete
    public string $brand_search = '';
    public array $brand_suggestions = [];
    public bool $show_brand_suggestions = false;
    public ?string $selected_brand = null;
    public bool $creating_new_brand = false;

    // Model autocomplete
    public string $model_search = '';
    public array $model_suggestions = [];
    public bool $show_model_suggestions = false;
    public ?string $selected_model = null;
    public bool $creating_new_model = false;

    public Collection $allContracts;
    public Collection $allEmployees;

    public function mount(IcsNumber $icsNumber): void
    {
        if (!auth()->user()->hasAdminPermission('edit_inventory')) {
            abort(403);
        }

        $this->icsNumber = $icsNumber->load('itemBatches.components', 'contractItem.contract.supplier', 'contractItem.itemSpecification.itemCatalog.secondaryCategory.primaryCategory', 'assignedEmployee.division', 'assignedEmployee.position', 'transfers.fromEmployee.division', 'transfers.toEmployee.division');
        $this->loadOriginalData();
    }

    private function loadOriginalData(): void
    {
        $this->fill($this->icsNumber->toArray()); // Pre-fill form from the model
        $this->date_prepared = $this->icsNumber->date_prepared->format('Y-m-d');
        $this->date_accepted = $this->icsNumber->date_accepted?->format('Y-m-d');
        $this->quantity = $this->icsNumber->itemBatches->count();

        // Initialize autocomplete fields with existing data
        if ($this->icsNumber->contractItem) {
            $contractItem = $this->icsNumber->contractItem;
            $this->contract_id = $contractItem->contract_id;
            $this->contract_item_id = $contractItem->id;
            $this->unit_price = $contractItem->unit_price;

            if ($contractItem->contract) {
                $this->supplier_id = $contractItem->contract->supplier_id;
                $this->supplier_search = $contractItem->contract->supplier->name ?? '';
                $this->selected_supplier_name = $contractItem->contract->supplier->name ?? '';
                $this->contract_search = $contractItem->contract->contract_po_ib_number;
                $this->selected_contract_name = $contractItem->contract->contract_po_ib_number;
            }

            if ($contractItem->itemSpecification) {
                $spec = $contractItem->itemSpecification;
                $this->item_specification_id = $spec->id;
                $this->main_item_brand = $spec->brand;
                $this->main_item_model = $spec->model;
                $this->detailed_specifications = $spec->detailed_specifications;
                $this->brand_search = $spec->brand ?? '';
                $this->selected_brand = $spec->brand ?? '';
                $this->model_search = $spec->model ?? '';
                $this->selected_model = $spec->model ?? '';

                if ($spec->itemCatalog) {
                    $catalog = $spec->itemCatalog;
                    $this->items_catalog_id = $catalog->id;
                    $this->item_search = $catalog->name;
                    $this->selected_item_name = $catalog->name;
                    $this->unit_of_measure = $catalog->unit;
                    $this->unit_search = $catalog->unit;
                    $this->selected_unit = $catalog->unit;

                    if ($catalog->secondaryCategory) {
                        $this->secondary_category_id = $catalog->secondary_category_id;
                        if ($catalog->secondaryCategory->primaryCategory) {
                            $this->primary_category_id = $catalog->secondaryCategory->primary_category_id;
                        }
                    }
                }
            }
        }

        if ($this->icsNumber->assignedEmployee) {
            $this->employee_search = $this->icsNumber->assignedEmployee->name;
            $this->selected_employee_name = $this->icsNumber->assignedEmployee->name;
        }

        // Track the original employee for transfer detection
        $this->original_assigned_employee_id = $this->assigned_employee_id;

        // Pre-load data for select dropdowns
        $this->allContracts = Contract::with('supplier:id,name')
            ->orderBy('contract_po_ib_number')
            ->get(['id', 'contract_po_ib_number', 'supplier_id']);

        $this->allEmployees = Employee::orderBy('name')
            ->get(['id', 'name']);

        // Reset all "creating new" flags and suggestions
        $this->resetCreationFlags();

        // Populate batches for editing
        $this->batches = [];
        foreach ($this->icsNumber->itemBatches as $batch) {
            $components = $batch->components->map(fn($c) => $c->toArray())->all();
            if (empty($components)) {
                // If a batch has no components, add a blank one for editing
                $components[] = ['id' => null, 'component_type' => '', 'brand' => '', 'model' => '', 'serial_number' => '', '_destroy' => false];
            } else {
                // Add _destroy flag to existing components
                $components = array_map(fn($c) => array_merge($c, ['_destroy' => false]), $components);
            }

            $this->batches[] = [
                'id' => $batch->id,
                'identification_data' => $batch->identification_data,
                'components' => $components,
                '_destroy' => false,
            ];
        }

        // If no batches exist, create one based on quantity
        if ($this->icsNumber->itemBatches->isEmpty()) {
            $this->updatedQuantity($this->icsNumber->quantity);
        }

        // Update item type based on unit price
        $this->updateItemType();

        $this->transfer_date = now()->format('Y-m-d');
    }

    private function resetCreationFlags(): void
    {
        $this->creating_new_supplier = false;
        $this->creating_new_contract = false;
        $this->creating_new_item = false;
        $this->creating_new_specification = false;
        $this->creating_new_employee = false;
        $this->creating_new_unit = false;
        $this->creating_new_brand = false;
        $this->creating_new_model = false;

        // Reset suggestion arrays and flags
        $this->supplier_suggestions = [];
        $this->show_supplier_suggestions = false;
        $this->contract_suggestions = [];
        $this->show_contract_suggestions = false;
        $this->item_suggestions = [];
        $this->show_item_suggestions = false;
        $this->specification_suggestions = [];
        $this->show_specification_suggestions = false;
        $this->employee_suggestions = [];
        $this->show_employee_suggestions = false;
        $this->unit_suggestions = [];
        $this->show_unit_suggestions = false;
        $this->brand_suggestions = [];
        $this->show_brand_suggestions = false;
        $this->model_suggestions = [];
        $this->show_model_suggestions = false;
    }

    public function resetForm(): void
    {
        // Reload the original model data from database
        $this->icsNumber->refresh();
        $this->icsNumber->load('itemBatches.components', 'contractItem.contract.supplier', 'contractItem.itemSpecification.itemCatalog.secondaryCategory.primaryCategory', 'assignedEmployee.division', 'assignedEmployee.position', 'transfers.fromEmployee.division', 'transfers.toEmployee.division');
        
        // Reset all form data to original values
        $this->loadOriginalData();
        
        // Clear any validation errors
        $this->resetValidation();
        
        // Dispatch event to notify user
        $this->dispatch('form-reset');
        
        // Dispatch success toast notification (same as create.blade.php)
        $this->dispatch('notify', id: uniqid(), heading: 'Success!', text: 'Form has been reset to original values.', variant: 'success');
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

        if ($this->creating_new_supplier) {
            if (strlen(trim($query)) >= 2) {
                $this->contract_suggestions = [['id' => 'new', 'name' => $query, 'type' => 'new']];
            } else {
                $this->contract_suggestions = [];
            }
            $this->show_contract_suggestions = count($this->contract_suggestions) > 0;
            return;
        }

        if (strlen(trim($query)) === 0) {
            $contracts = $this->supplier_id ? Contract::where('supplier_id', $this->supplier_id)->orderBy('contract_po_ib_number')->get() : collect();
        } else {
            $contracts = $this->supplier_id ? Contract::where('supplier_id', $this->supplier_id)
                ->whereRaw('LOWER(contract_po_ib_number) LIKE LOWER(?)', ['%' . $query . '%'])
                ->orderBy('contract_po_ib_number')
                ->get() : collect();
        }

        $this->contract_suggestions = $contracts->map(function ($contract) {
            return [
                'id' => $contract->id,
                'name' => $contract->contract_po_ib_number,
                'type' => 'existing'
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
        if (strlen(trim($query)) === 0) {
            $items = ItemsCatalog::orderBy('name')->limit(20)->get();
        } else {
            $items = ItemsCatalog::where('name', 'like', '%' . $query . '%')
                ->orderBy('name')
                ->limit(20)
                ->get();
        }

        $this->item_suggestions = $items->map(function ($item) {
            return [
                'id' => $item->id,
                'name' => $item->name,
                'unit' => $item->unit,
                'type' => 'existing'
            ];
        })->toArray();

        $exactExists = collect($this->item_suggestions)->contains(function ($item) use ($query) {
            return strtolower($item['name']) === strtolower($query);
        });

        if (!$exactExists && strlen(trim($query)) >= 2) {
            array_unshift($this->item_suggestions, [
                'id' => 'new',
                'name' => "Create new item catalog: \"{$query}\"",
                'description' => 'This will create a new entry in the item catalog.',
                'type' => 'new'
            ]);
        }
        
        $this->show_item_suggestions = count($this->item_suggestions) > 0;
    }

    public function selectItem($itemData): void
    {
        if ($itemData['type'] === 'existing') {
            $this->items_catalog_id = $itemData['id'];
            $this->item_search = $itemData['name'];
            $this->selected_item_name = $itemData['name'];
            
            $itemCatalog = ItemsCatalog::find($itemData['id']);
            if ($itemCatalog) {
                $this->unit_of_measure = $itemCatalog->unit;
                $this->unit_search = $itemCatalog->unit;
                $this->selected_unit = $itemCatalog->unit;
                $this->primary_category_id = $itemCatalog->secondaryCategory->primary_category_id ?? null;
                $this->secondary_category_id = $itemCatalog->secondary_category_id;
            }
            
            $this->creating_new_item = false;
            $this->creating_new_unit = false;
            $this->dispatch('focus-specification');

        } elseif ($itemData['type'] === 'new') {
            preg_match('/"([^"]+)"/', $itemData['name'], $matches);
            $newItemName = $matches[1] ?? $this->item_search;

            $this->items_catalog_id = null;
            $this->item_search = $newItemName;
            $this->selected_item_name = $newItemName . ' (new)';
            $this->creating_new_item = true;
            $this->creating_new_specification = true;
            $this->item_specification_id = 'new';

            $this->main_item_brand = null;
            $this->main_item_model = null;
            $this->detailed_specifications = null;
            $this->unit_of_measure = '';
            $this->unit_search = '';
            $this->selected_unit = '';
            $this->primary_category_id = null;
            $this->secondary_category_id = null;
            
            $this->dispatch('focus-primary-category');
        }
        
        $this->show_item_suggestions = false;
        $this->updateItemType();
    }

    // Employee autocomplete methods
    public function updatedEmployeeSearch($value): void
    {
        $this->searchEmployees($value);
    }

    public function showAllEmployees(): void
    {
        $this->searchEmployees($this->employee_search);
        if (count($this->employee_suggestions) > 0) {
            $this->show_employee_suggestions = true;
        }
    }

    public function searchEmployees($query): void
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

        $this->employee_suggestions = $employees->map(function ($employee) {
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

        $exactExists = collect($this->employee_suggestions)->contains(function ($employee) use ($query) {
            return strtolower($employee['name']) === strtolower($query);
        });

        if (!$exactExists && strlen(trim($query)) >= 2) {
            array_unshift($this->employee_suggestions, [
                'id' => 'new',
                'name' => $query,
                'type' => 'new'
            ]);
        }

        $this->show_employee_suggestions = count($this->employee_suggestions) > 0;
    }

    public function selectEmployee($employeeData): void
    {
        if ($employeeData['type'] === 'existing') {
            $this->assigned_employee_id = $employeeData['id'];
            $this->employee_search = $employeeData['name'];
            $this->selected_employee_name = $employeeData['name'];
            $this->creating_new_employee = false;
        } elseif ($employeeData['type'] === 'new') {
            $this->assigned_employee_id = null;
            $this->employee_search = $employeeData['name'];
            $this->selected_employee_name = $employeeData['name'] . ' (new)';
            $this->creating_new_employee = true;
        }

        $this->show_employee_suggestions = false;
        $this->dispatch('focus-estimated-useful-life');
    }

    // Unit of measure autocomplete methods
    public function updatedUnitSearch($value): void
    {
        $this->searchUnits($value);
    }

    public function showAllUnits(): void
    {
        $this->searchUnits($this->unit_search);
        if (count($this->unit_suggestions) > 0) {
            $this->show_unit_suggestions = true;
        }
    }

    public function searchUnits($query): void
    {
        if (strlen(trim($query)) === 0) {
            $units = ItemsCatalog::select('unit')
                ->distinct()
                ->orderBy('unit')
                ->pluck('unit')
                ->toArray();
        } else {
            $units = ItemsCatalog::select('unit')
                ->whereRaw('LOWER(unit) LIKE LOWER(?)', ['%' . $query . '%'])
                ->distinct()
                ->orderBy('unit')
                ->pluck('unit')
                ->toArray();
        }
        
        $this->unit_suggestions = array_map(function($unit) {
            return [
                'id' => $unit,
                'name' => $unit,
                'type' => 'existing'
            ];
        }, $units);
        
        $exactExists = collect($this->unit_suggestions)->contains(function ($unit) use ($query) {
            return strtolower($unit['name']) === strtolower($query);
        });
        
        if (!$exactExists && strlen(trim($query)) >= 2) {
            array_unshift($this->unit_suggestions, [
                'id' => 'new',
                'name' => $query,
                'type' => 'new'
            ]);
        }
        
        $this->show_unit_suggestions = count($this->unit_suggestions) > 0;
    }

    public function selectUnit($unitData): void
    {
        if ($unitData['type'] === 'existing') {
            $this->unit_of_measure = $unitData['name'];
            $this->unit_search = $unitData['name'];
            $this->selected_unit = $unitData['name'];
            $this->creating_new_unit = false;
        } elseif ($unitData['type'] === 'new') {
            $this->unit_of_measure = $unitData['name'];
            $this->unit_search = $unitData['name'];
            $this->selected_unit = $unitData['name'] . ' (new)';
            $this->creating_new_unit = true;
        }
        
        $this->show_unit_suggestions = false;
        $this->dispatch('focus-employee');
    }

    // Brand autocomplete methods
    public function updatedBrandSearch($value): void
    {
        $this->searchBrands($value);
    }

    public function showAllBrands(): void
    {
        $this->searchBrands($this->brand_search);
        if (count($this->brand_suggestions) > 0) {
            $this->show_brand_suggestions = true;
        }
    }

    public function searchBrands($query): void
    {
        if (strlen(trim($query)) === 0) {
            $brands = ItemSpecification::select('brand')
                ->whereNotNull('brand')
                ->where('brand', '!=', '')
                ->distinct()
                ->orderBy('brand')
                ->pluck('brand')
                ->toArray();
        } else {
            $brands = ItemSpecification::select('brand')
                ->whereNotNull('brand')
                ->where('brand', '!=', '')
                ->whereRaw('LOWER(brand) LIKE LOWER(?)', ['%' . $query . '%'])
                ->distinct()
                ->orderBy('brand')
                ->pluck('brand')
                ->toArray();
        }
        
        $this->brand_suggestions = array_map(function($brand) {
            return [
                'id' => $brand,
                'name' => $brand,
                'type' => 'existing'
            ];
        }, $brands);
        
        $exactExists = collect($this->brand_suggestions)->contains(function ($brand) use ($query) {
            return strtolower($brand['name']) === strtolower($query);
        });
        
        if (!$exactExists && strlen(trim($query)) >= 2) {
            array_unshift($this->brand_suggestions, [
                'id' => 'new',
                'name' => $query,
                'type' => 'new'
            ]);
        }
        
        $this->show_brand_suggestions = count($this->brand_suggestions) > 0;
    }

    public function selectBrand($brandData): void
    {
        if ($brandData['type'] === 'existing') {
            $this->main_item_brand = $brandData['name'];
            $this->brand_search = $brandData['name'];
            $this->selected_brand = $brandData['name'];
            $this->creating_new_brand = false;
        } elseif ($brandData['type'] === 'new') {
            $this->main_item_brand = $brandData['name'];
            $this->brand_search = $brandData['name'];
            $this->selected_brand = $brandData['name'] . ' (new)';
            $this->creating_new_brand = true;
        }
        
        $this->show_brand_suggestions = false;
        $this->dispatch('focus-model');
    }

    // Model autocomplete methods
    public function updatedModelSearch($value): void
    {
        $this->searchModels($value);
    }

    public function showAllModels(): void
    {
        $this->searchModels($this->model_search);
        if (count($this->model_suggestions) > 0) {
            $this->show_model_suggestions = true;
        }
    }

    public function searchModels($query): void
    {
        if (strlen(trim($query)) === 0) {
            $models = ItemSpecification::select('model')
                ->whereNotNull('model')
                ->where('model', '!=', '')
                ->distinct()
                ->orderBy('model')
                ->pluck('model')
                ->toArray();
        } else {
            $models = ItemSpecification::select('model')
                ->whereNotNull('model')
                ->where('model', '!=', '')
                ->whereRaw('LOWER(model) LIKE LOWER(?)', ['%' . $query . '%'])
                ->distinct()
                ->orderBy('model')
                ->pluck('model')
                ->toArray();
        }
        
        $this->model_suggestions = array_map(function($model) {
            return [
                'id' => $model,
                'name' => $model,
                'type' => 'existing'
            ];
        }, $models);
        
        $exactExists = collect($this->model_suggestions)->contains(function ($model) use ($query) {
            return strtolower($model['name']) === strtolower($query);
        });
        
        if (!$exactExists && strlen(trim($query)) >= 2) {
            array_unshift($this->model_suggestions, [
                'id' => 'new',
                'name' => $query,
                'type' => 'new'
            ]);
        }
        
        $this->show_model_suggestions = count($this->model_suggestions) > 0;
    }

    public function selectModel($modelData): void
    {
        if ($modelData['type'] === 'existing') {
            $this->main_item_model = $modelData['name'];
            $this->model_search = $modelData['name'];
            $this->selected_model = $modelData['name'];
            $this->creating_new_model = false;
        } elseif ($modelData['type'] === 'new') {
            $this->main_item_model = $modelData['name'];
            $this->model_search = $modelData['name'];
            $this->selected_model = $modelData['name'] . ' (new)';
            $this->creating_new_model = true;
        }
        
        $this->show_model_suggestions = false;
        $this->dispatch('focus-detailed-specs');
    }

    // Helper methods
    private function resetContractData(): void
    {
        $this->contract_id = null;
        $this->contract_search = '';
        $this->selected_contract_name = null;
        $this->contract_suggestions = [];
        $this->show_contract_suggestions = false;
        $this->resetItemData();
    }

    private function resetItemData(): void
    {
        $this->contract_item_id = null;
        $this->items_catalog_id = null;
        $this->item_specification_id = null;
        $this->item_search = '';
        $this->selected_item_name = null;
        $this->creating_new_item = false;
        $this->item_suggestions = [];
        $this->show_item_suggestions = false;

        $this->specification_search = '';
        $this->specification_suggestions = [];
        $this->show_specification_suggestions = false;
        $this->selected_specification_name = null;
        $this->creating_new_specification = false; 

        $this->unit_price = 0;
        $this->isDesktopComputer = false;
        $this->isParItem = false;
        $this->main_item_brand = null;
        $this->main_item_model = null;
        $this->detailed_specifications = null;
        $this->unit_of_measure = '';
        $this->unit_search = '';
        $this->selected_unit = '';
        $this->creating_new_unit = false;
        
        $this->brand_search = '';
        $this->brand_suggestions = [];
        $this->show_brand_suggestions = false;
        $this->selected_brand = null;
        $this->creating_new_brand = false;
        
        $this->model_search = '';
        $this->model_suggestions = [];
        $this->show_model_suggestions = false;
        $this->selected_model = null;
        $this->creating_new_model = false;
        
        $this->primary_category_id = null;
        $this->secondary_category_id = null;
    }

    private function updateItemType(): void
    {
        $this->isDesktopComputer = str_contains(strtoupper($this->selected_item_name ?? ''), 'DESKTOP COMPUTER');
        
        if ($this->unit_price >= 50000) {
            $this->isParItem = true;
            $this->ics_type = 'Not applicable - Item requires PAR (₱50,000 or more)';
        } elseif ($this->unit_price > 5000) {
            $this->isParItem = false;
            $this->ics_type = 'SPHV - ₱5,001 to ₱49,999';
        } else {
            $this->isParItem = false;
            $this->ics_type = 'SPLV - ₱5,000 or less';
        }
    }

    public function updateItemTypeFromPrice($price): void
    {
        $this->unit_price = $price;
        $this->updateItemType();
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
        $this->updateItemType();
    }

    public function updatedUnitPrice(): void
    {
        $this->updateItemType();
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

    public function updatedQuantity($value): void
    {
        $value = (int) $value;
        if ($value < 1) {
            $this->quantity = 1;
            $value = 1;
        }

        $currentCount = count(array_filter($this->batches, fn($b) => !($b['_destroy'] ?? false)));

        if ($value > $currentCount) {
            for ($i = 0; $i < $value - $currentCount; $i++) {
                $this->addBatch();
            }
        } elseif ($value < $currentCount) {
            $toRemove = $currentCount - $value;
            $removedCount = 0;
            // Iterate backwards to safely remove items
            for ($i = count($this->batches) - 1; $i >= 0 && $removedCount < $toRemove; $i--) {
                if (!($this->batches[$i]['_destroy'] ?? false)) {
                    $this->removeBatch($i);
                    $removedCount++;
                }
            }
        }
    }

    public function addBatch(): void
    {
        $this->batches[] = [
            'id' => null,
            'identification_data' => null,
            'components' => [['id' => null, 'component_type' => '', 'brand' => '', 'model' => '', 'serial_number' => '', '_destroy' => false]],
            '_destroy' => false,
        ];
    }

    public function removeBatch(int $index): void
    {
        if (isset($this->batches[$index])) {
            if (!empty($this->batches[$index]['id'])) {
                // If it's an existing batch, mark for deletion
                $this->batches[$index]['_destroy'] = true;
            } else {
                // If it's a new batch, just remove it
                array_splice($this->batches, $index, 1);
            }
        }
        // This ensures the quantity visually reflects the number of non-destroyed batches.
        $this->quantity = count(array_filter($this->batches, fn($b) => !($b['_destroy'] ?? false)));
    }

    public function addComponent(int $batchIndex): void
    {
        $this->batches[$batchIndex]['components'][] = ['id' => null, 'component_type' => '', 'brand' => '', 'model' => '', 'serial_number' => '', '_destroy' => false];
    }

    public function removeComponent(int $batchIndex, int $componentIndex): void
    {
        if (isset($this->batches[$batchIndex]['components'][$componentIndex])) {
            if (!empty($this->batches[$batchIndex]['components'][$componentIndex]['id'])) {
                $this->batches[$batchIndex]['components'][$componentIndex]['_destroy'] = true;
            } else {
                array_splice($this->batches[$batchIndex]['components'], $componentIndex, 1);
            }
        }
    }

    public function transferItem(): void
    {
        if (!auth()->user()->hasAdminPermission('transfer_inventory')) {
            abort(403);
        }

        $validated = $this->validate([
            'transfer_to_employee_id' => ['required', 'integer', Rule::exists('employees', 'id'), Rule::notIn([$this->icsNumber->assigned_employee_id])],
            'transfer_date' => ['required', 'date'],
        ]);

        DB::transaction(function () use ($validated) {
            // Create a transfer record
            $this->icsNumber->transfers()->create([
                'from_employee_id' => $this->icsNumber->assigned_employee_id,
                'to_employee_id' => $validated['transfer_to_employee_id'],
                'transfer_date' => $validated['transfer_date'],
            ]);

            // Update the current custodian on the ICS record
            $this->icsNumber->update([
                'assigned_employee_id' => $validated['transfer_to_employee_id'],
            ]);

            // For the form state
            $this->assigned_employee_id = $validated['transfer_to_employee_id'];
        });

        // Update the employee search fields to reflect the new assignment
        $newEmployee = Employee::find($validated['transfer_to_employee_id']);
        if ($newEmployee) {
            $this->employee_search = $newEmployee->name;
            $this->selected_employee_name = $newEmployee->name;
        }

        $this->showTransferModal = false;
        $this->dispatch('ics-transferred');
        
        // Dispatch success toast notification (same as create.blade.php)
        $this->dispatch('notify', id: uniqid(), heading: 'Success!', text: "Item successfully transferred.", variant: 'success');

        // Reload the model to get fresh transfer history and employee info
        $this->icsNumber->load('assignedEmployee.division', 'assignedEmployee.position', 'transfers.fromEmployee.division', 'transfers.toEmployee.division');
    }



    public function update(): void
    {
        // Extract the code from the descriptive ICS type string for validation
        $icsTypeCode = strtok($this->ics_type, ' ');
        
        $rules = [
            'ics_number' => ['required', 'string', 'max:255', Rule::unique('ics_number', 'ics_number')->ignore($this->icsNumber->id)],
            'assigned_employee_id' => 'required_unless:creating_new_employee,true|nullable|exists:employees,id',
            'supplier_id' => 'required_unless:creating_new_supplier,true|nullable|exists:suppliers,id',
            'contract_id' => 'required_unless:creating_new_contract,true|nullable|exists:contracts,id',
            'items_catalog_id' => 'required_unless:creating_new_item,true|nullable|exists:items_catalog,id',
            'item_specification_id' => 'nullable|string',
            'quantity' => ['required', 'integer', 'min:1', function ($attribute, $value, $fail) {
                $activeBatches = count(array_filter($this->batches, fn($b) => !($b['_destroy'] ?? false)));
                if ($value != $activeBatches) {
                    $fail("The quantity ($value) must match the number of active batches ($activeBatches).");
                }
            }],
            'unit_price' => 'required|numeric|gt:0',
            'unit_of_measure' => 'required|string|max:50',
            'estimated_useful_life' => ['nullable', 'integer', 'min:1'],
            'date_prepared' => ['required', 'date'],
            'date_accepted' => ['nullable', 'date', 'after_or_equal:date_prepared'],
            'remarks' => ['nullable', 'string'],
            'batches.*.components.*.component_type' => ['required_with:batches.*.components.*.serial_number', 'nullable', 'string', 'max:255'],
            'batches.*.components.*.brand' => ['nullable', 'string', 'max:255'],
            'batches.*.components.*.model' => ['nullable', 'string', 'max:255'],
            'batches.*.components.*.serial_number' => ['nullable', 'string', 'max:255'],
        ];

        // Add custom validation for ICS type code
        if (!in_array($icsTypeCode, ['SPLV', 'SPHV']) && !str_contains($this->ics_type, 'Not applicable')) {
            $this->addError('ics_type', 'The selected ICS type is invalid.');
            return;
        }

        if ($this->creating_new_supplier) {
            $rules['supplier_search'] = 'required|string|max:255|unique:suppliers,name';
            $rules['primary_category_id'] = 'required|exists:primary_categories,id';
            $rules['secondary_category_id'] = 'required|exists:secondary_categories,id';
            $rules['unit_of_measure'] = 'required|string|max:50';
        }
        if ($this->creating_new_contract) {
            $rules['contract_search'] = 'required|string|max:255|unique:contracts,contract_po_ib_number';
        }
        if ($this->creating_new_item) {
            $rules['item_search'] = 'required|string|max:255|unique:items_catalog,name';
            $rules['primary_category_id'] = 'required|exists:primary_categories,id';
            $rules['secondary_category_id'] = 'required|exists:secondary_categories,id';
        }
        if ($this->creating_new_specification) {
            $rules['main_item_brand'] = 'nullable|string|max:255';
            $rules['main_item_model'] = 'nullable|string|max:255';
            $rules['detailed_specifications'] = 'nullable|string';
        }
        if ($this->creating_new_employee) {
            $rules['employee_search'] = 'required|string|max:255';
        }
        
        if ($this->isDesktopComputer) {
            $rules['batches.*.components.*.component_type'] = 'required|string|max:255';
            $rules['batches.*.components.*.brand'] = 'nullable|string|max:255';
            $rules['batches.*.components.*.model'] = 'nullable|string|max:255';
            $rules['batches.*.components.*.serial_number'] = 'nullable|string|max:255';
        }

        $messages = [
            'supplier_id.required_unless' => 'Please select a supplier or specify a new one.',
            'contract_id.required_unless' => 'Please select a contract or specify a new one.',
            'items_catalog_id.required_unless' => 'Please select an item from the catalog or specify a new one.',
            'assigned_employee_id.required_unless' => 'Please assign this item to an employee.',
            'date_prepared.required' => 'The "Date Prepared" field is required.',
            'date_accepted.required' => 'The "Date Accepted" field is required.',
            'unit_price.required' => 'The "Unit Cost" field is required.',
            'unit_of_measure.required' => 'The "Unit of Measure" field is required.',
            'quantity.min' => 'The quantity must be at least 1.',
            'quantity.integer' => 'The quantity must be a whole number.',
            'unit_price.gt' => 'The "Unit Cost" must be greater than zero.',
        ];

        $validated = $this->validate($rules, $messages);

        try {
            $transferCreated = false;
            $recordChanged = false;
            DB::transaction(function () use ($validated, $icsTypeCode, &$transferCreated, &$recordChanged) {
                if ($this->creating_new_supplier) {
                    $newSupplier = Supplier::create(['name' => $this->supplier_search]);
                    $this->supplier_id = $newSupplier->id;
                }

                if ($this->creating_new_contract) {
                    $newContract = Contract::create([
                        'contract_po_ib_number' => $this->contract_search,
                        'supplier_id' => $this->supplier_id,
                        'po_date' => now(),
                    ]);
                    $this->contract_id = $newContract->id;
                }

                if ($this->creating_new_employee) {
                    $newEmployee = Employee::create([
                        'name' => $this->employee_search,
                        'employee_number' => 'EMP-' . str_pad(Employee::count() + 1, 4, '0', STR_PAD_LEFT),
                    ]);
                    $this->assigned_employee_id = $newEmployee->id;
                }

                $spec_id = $this->item_specification_id;
                $catalog_id = $this->items_catalog_id;

                if ($this->creating_new_item) {
                     $newItem = ItemsCatalog::create([
                        'name' => $this->item_search,
                        'secondary_category_id' => $this->secondary_category_id,
                        'unit' => $this->unit_of_measure,
                        'code' => 'new-' . time(),
                    ]);
                    $catalog_id = $newItem->id;
                }

                if ($this->creating_new_specification) {
                    $newSpec = ItemSpecification::create([
                        'item_catalog_id' => $catalog_id,
                        'brand' => $this->main_item_brand,
                        'model' => $this->main_item_model,
                        'detailed_specifications' => $this->detailed_specifications,
                    ]);
                    $spec_id = $newSpec->id;
                }

                // Find or update ContractItem
                $final_contract_item = ContractItem::updateOrCreate(
                    ['contract_id' => $this->contract_id, 'item_specification_id' => $spec_id],
                    ['unit_price' => $this->unit_price, 'item_type' => $this->isParItem ? 'PAR' : 'ICS']
                );

                // 1. Update the main IcsNumber record
                $this->icsNumber->fill([
                    'ics_number' => $validated['ics_number'],
                    'assigned_employee_id' => $this->assigned_employee_id,
                    'contract_item_id' => $final_contract_item->id,
                    'ics_type' => $icsTypeCode,
                    'quantity' => $validated['quantity'],
                    'estimated_useful_life' => $validated['estimated_useful_life'],
                    'remarks' => $validated['remarks'],
                    'date_prepared' => $validated['date_prepared'],
                    'date_accepted' => $validated['date_accepted'],
                ]);
                if ($this->icsNumber->isDirty()) {
                    $this->icsNumber->save();
                    $recordChanged = true;
                }

                // 2. Create transfer record if employee changed
                if ($this->original_assigned_employee_id && 
                    $this->original_assigned_employee_id !== $this->assigned_employee_id) {
                    $transferCreated = true;
                    $this->icsNumber->transfers()->create([
                        'from_employee_id' => $this->original_assigned_employee_id,
                        'to_employee_id' => $this->assigned_employee_id,
                        'transfer_date' => now()->format('Y-m-d'),
                    ]);
                }

                $activeBatchIds = [];
                foreach ($this->batches as $batchData) {
                    // 3. Handle batch creation/update
                    $batch = null;
                    if (!($batchData['_destroy'] ?? false)) {
                        $batch = $this->icsNumber->itemBatches()->updateOrCreate(
                            ['id' => $batchData['id'] ?? null],
                            ['identification_data' => $batchData['identification_data'] ?? null]
                        );
                        $activeBatchIds[] = $batch->id;

                        $activeComponentIds = [];
                        foreach ($batchData['components'] as $componentData) {
                            // 4. Handle component creation/update
                            if (!($componentData['_destroy'] ?? false)) {
                                // Only save if there's some data
                                if ($componentData['component_type'] || $componentData['serial_number'] || $componentData['brand'] || $componentData['model']) {
                                    $component = $batch->components()->updateOrCreate(
                                        ['id' => $componentData['id'] ?? null],
                                        [
                                            'component_type' => $componentData['component_type'],
                                            'brand' => $componentData['brand'],
                                            'model' => $componentData['model'],
                                            'serial_number' => $componentData['serial_number'],
                                        ]
                                    );
                                    $activeComponentIds[] = $component->id;
                                }
                            }
                        }
                        // 5. Delete components marked for destruction in this batch
                        $batch->components()->whereNotIn('id', $activeComponentIds)->delete();
                    }
                }
                // 6. Delete batches marked for destruction
                $this->icsNumber->itemBatches()->whereNotIn('id', $activeBatchIds)->delete();
            });

            $this->dispatch('ics-updated');
            
            $anyChanges = $recordChanged || $transferCreated;
            if (! $anyChanges) {
                $this->dispatch('notify', id: uniqid(), heading: 'No changes', text: 'Nothing to save.', variant: 'info');
                return;
            }

            $successMessage = "ICS record {$this->icsNumber->ics_number} updated successfully.";
            if ($this->original_assigned_employee_id && 
                $this->original_assigned_employee_id !== $this->assigned_employee_id) {
                $successMessage .= " Transfer record has been created.";
            }
            
            // Reset the original employee tracker to the current employee
            $this->original_assigned_employee_id = $this->assigned_employee_id;
            // Dispatch success toast notification (same as create.blade.php)
            $this->dispatch('notify', id: uniqid(), heading: 'Success!', text: $successMessage, variant: 'success');
            // Reload original data to reflect changes without leaving the page
            $this->loadOriginalData();
        } catch (\Exception $e) {
            \Log::error('Error updating ICS record: ' . $e->getMessage());
            
            // Dispatch an error toast notification (same as create.blade.php)
            $this->dispatch('notify', id: uniqid(), heading: 'Error', text: 'An error occurred while updating the record: ' . $e->getMessage(), variant: 'danger');
        }
    }



    public function destroy(): void
    {
        if (!auth()->user()->hasAdminPermission('delete_inventory')) {
            abort(403);
        }

        try {
            $icsNumber = $this->icsNumber->ics_number; // Store number before deletion
            $this->icsNumber->delete();
            
            // Dispatch a success toast notification (same as create.blade.php)
            $this->dispatch('notify', id: uniqid(), heading: 'Success!', text: "ICS record #{$icsNumber} deleted successfully.", variant: 'success');
            
            $this->dispatch('ics-deleted');
            $this->redirect(route('admin.inventory.ics.index'), navigate: true);
        } catch (\Illuminate\Database\QueryException $e) {
            \Log::error('Database error during ICS deletion: ' . $e->getMessage());
            if (($e->errorInfo[1] ?? null) === 1451) { // FK constraint
                // Dispatch an error toast notification
                $this->dispatch('notify', id: uniqid(), heading: 'Error', text: 'Cannot delete this record because it is referenced by other records.', variant: 'danger');
            } else {
                // Dispatch an error toast notification
                $this->dispatch('notify', id: uniqid(), heading: 'Error', text: 'An unexpected database error occurred while deleting the record.', variant: 'danger');
            }
        } catch (\Throwable $e) {
            // Log the exception for debugging
            \Log::error('Error deleting ICS record: ' . $e->getMessage());
            
            // Dispatch an error toast notification
            $this->dispatch('notify', id: uniqid(), heading: 'Error', text: 'An unexpected error occurred while deleting the record.', variant: 'danger');
        }
    }
}; ?>

<div>
<div>
    <form wire:submit="update">
        <div class="border-b border-stone-200 pb-4 dark:border-stone-700">
            <div class="flex items-center justify-between">
                <!-- Breadcrumbs as Title -->
                <div>
                    <flux:breadcrumbs class="text-2xl font-semibold">
                        <flux:breadcrumbs.item :href="route('admin.dashboard')" wire:navigate icon="home" class="text-xl sm:text-2xl font-semibold text-stone-700 dark:text-stone-300" />
                        <flux:breadcrumbs.item href="#" class="text-xl sm:text-2xl font-semibold text-stone-500 dark:text-stone-400">Inventory</flux:breadcrumbs.item>
                        <flux:breadcrumbs.item :href="route('admin.inventory.ics.index')" wire:navigate class="text-xl sm:text-2xl font-semibold text-stone-700 dark:text-stone-300">ICS Management</flux:breadcrumbs.item>
                        <flux:breadcrumbs.item class="text-xl sm:text-2xl font-semibold text-stone-900 dark:text-stone-100">Edit ICS {{ $icsNumber->ics_number }}</flux:breadcrumbs.item>
                    </flux:breadcrumbs>
                </div>
                <div class="flex items-center gap-x-4">
                    <x-action-message class="me-3" on="ics-updated">
                        {{ __('Record updated successfully.') }}
                    </x-action-message>
                    <x-action-message class="me-3" on="form-reset">
                        {{ __('Form reset to original values.') }}
                    </x-action-message>
                    <flux:button type="button" variant="ghost" @click="history.back()">
                        Cancel
                    </flux:button>
                    <flux:button type="button" variant="filled" wire:click="resetForm" wire:loading.attr="disabled" wire:target="resetForm">
                        <span wire:loading.remove wire:target="resetForm">Reset</span>
                        <span wire:loading wire:target="resetForm">Resetting...</span>
                    </flux:button>
                    <flux:modal.trigger name="delete-ics-confirmation">
                        <flux:button type="button" variant="danger" wire:loading.attr="disabled" wire:target="destroy">
                            <span wire:loading.remove wire:target="destroy">Delete</span>
                            <span wire:loading wire:target="destroy">Deleting...</span>
                        </flux:button>
                    </flux:modal.trigger>
                    <flux:button type="submit" variant="primary" wire:loading.attr="disabled" wire:target="update">
                        <span wire:loading.remove wire:target="update">Save Changes</span>
                        <span wire:loading wire:target="update">Saving...</span>
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
                                <!-- Item Catalog Selection -->
                                <div>
                                    <h4 class="mb-4 font-medium text-stone-800 dark:text-stone-200">Item Catalog</h4>
                                    <div>
                                        <x-autocomplete id="item_search" wire:model.live="item_search" wire:suggestions="item_suggestions" wire:showSuggestions="show_item_suggestions" label="Select Item" placeholder="Search by item name..." required :disabled="!$this->contract_id && !$this->creating_new_contract" onFocus="$wire.showAllItems()" onSelect="$wire.selectItem" />
                                        @error('items_catalog_id')
                                            <div class="mt-2 flex items-center text-sm text-red-600 dark:text-red-400">
                                                <svg class="mr-2 h-5 w-5 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.21 3.03-1.742 3.03H4.42c-1.532 0-2.492-1.696-1.742-3.03l5.58-9.92zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" />
                                                </svg>
                                                <span>{{ $message }}</span>
                                            </div>
                                        @enderror
                                        @if ($creating_new_item)
                                            <div class="mt-2 flex items-center rounded-lg bg-green-50 p-2 text-sm text-green-700 dark:bg-green-800/20 dark:text-green-400" role="alert">
                                                <svg class="mr-2 h-4 w-4 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                                                    <path d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5ZM9.5 4a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3ZM12 15H8a1 1 0 0 1 0-2h1v-3H8a1 1 0 0 1 0-2h2a1 1 0 0 1 1 1v4h1a1 1 0 0 1 0 2Z" />
                                                </svg>
                                                <span class="font-medium">New item catalog will be created upon saving.</span>
                                            </div>
                                        @endif
                                    </div>
                                </div>

                                <!-- Item Specifications Section -->
                                <div class="border-t border-stone-200 pt-4 dark:border-stone-700">
                                    <h4 class="mb-4 font-medium text-stone-800 dark:text-stone-200">Item Specifications</h4>
                                    <div class="grid grid-cols-1 gap-x-6 gap-y-4 sm:grid-cols-2">
                                        <div>
                                            <x-autocomplete id="brand_search" wire:model.live="brand_search" wire:suggestions="brand_suggestions" wire:showSuggestions="show_brand_suggestions" label="Brand" placeholder="e.g., HP, Dell, Samsung" onFocus="$wire.showAllBrands()" onSelect="$wire.selectBrand" @keydown.tab="if (!event.shiftKey) { event.preventDefault(); $wire.dispatch('focus-model'); }" />
                                            @if ($creating_new_brand)
                                                <div class="mt-2 flex items-center rounded-lg bg-green-50 p-2 text-sm text-green-700 dark:bg-green-800/20 dark:text-green-400" role="alert">
                                                    <svg class="mr-2 h-4 w-4 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                                                        <path d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5ZM9.5 4a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3ZM12 15H8a1 1 0 0 1 0-2h1v-3H8a1 1 0 0 1 0-2h2a1 1 0 0 1 1 1v4h1a1 1 0 0 1 0 2Z" />
                                                    </svg>
                                                    <span class="font-medium">New brand will be used.</span>
                                                </div>
                                            @endif
                                        </div>
                                        <div>
                                            <x-autocomplete id="model_search" wire:model.live="model_search" wire:suggestions="model_suggestions" wire:showSuggestions="show_model_suggestions" label="Model" placeholder="e.g., ProBook 450 G9, XPS 15" onFocus="$wire.showAllModels()" onSelect="$wire.selectModel" @keydown.tab="if (!event.shiftKey) { event.preventDefault(); $wire.dispatch('focus-detailed-specs'); }" />
                                            @if ($creating_new_model)
                                                <div class="mt-2 flex items-center rounded-lg bg-green-50 p-2 text-sm text-green-700 dark:bg-green-800/20 dark:text-green-400" role="alert">
                                                    <svg class="mr-2 h-4 w-4 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                                                        <path d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5ZM9.5 4a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3ZM12 15H8a1 1 0 0 1 0-2h1v-3H8a1 1 0 0 1 0-2h2a1 1 0 0 1 1 1v4h1a1 1 0 0 1 0 2Z" />
                                                    </svg>
                                                    <span class="font-medium">New model will be used.</span>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="mt-4">
                                        <flux:textarea id="detailed_specifications" wire:model="detailed_specifications" label="Detailed Specifications" placeholder="Enter detailed specifications here, e.g., RAM, CPU, Storage, etc." rows="3" @keydown.tab="if (!event.shiftKey) { event.preventDefault(); $wire.dispatch('focus-unit-cost'); }" />
                                    </div>
                                </div>

                                <!-- Pricing Section -->
                                <div class="border-t border-stone-200 pt-4 dark:border-stone-700">
                                    <h4 class="mb-4 font-medium text-stone-800 dark:text-stone-200">Pricing Information</h4>
                                    <div class="grid grid-cols-1 gap-x-6 gap-y-4 sm:grid-cols-2">
                                        <div>
                                            <label for="unit_cost" class="mb-1 block text-sm font-medium text-stone-700 dark:text-stone-300">Unit Cost</label>
                                            <div class="relative" x-data="{
                                                formattedValue: '',
                                                rawValue: 0,
                                                
                                                init() {
                                                    if ($wire.unit_price !== null && $wire.unit_price !== undefined) {
                                                        this.rawValue = $wire.unit_price;
                                                        this.updateFormattedValue();
                                                    }
                                                    
                                                    $watch('$wire.unit_price', (value) => {
                                                        if (value !== this.rawValue) {
                                                            this.rawValue = value;
                                                            this.updateFormattedValue();
                                                        }
                                                    });
                                                },
                                                
                                                updateFormattedValue() {
                                                    this.formattedValue = this.rawValue.toLocaleString('en-US', {
                                                        minimumFractionDigits: 2,
                                                        maximumFractionDigits: 2
                                                    });
                                                },
                                                
                                                updatePrice(event) {
                                                    const cursorPos = event.target.selectionStart;
                                                    
                                                    let value = event.target.value.replace(/[^0-9.]/g, '');
                                                    
                                                    const decimalPoints = value.match(/\./g);
                                                    if (decimalPoints && decimalPoints.length > 1) {
                                                        const firstDecimalPos = value.indexOf('.');
                                                        value = value.substring(0, firstDecimalPos + 1) + 
                                                               value.substring(firstDecimalPos + 1).replace(/\./g, '');
                                                    }
                                                    
                                                    if (value.includes('.')) {
                                                        const parts = value.split('.');
                                                        if (parts[1].length > 2) {
                                                            parts[1] = parts[1].substring(0, 2);
                                                            value = parts.join('.');
                                                        }
                                                    }
                                                    
                                                    this.rawValue = parseFloat(value) || 0;
                                                    
                                                    const oldFormatted = this.formattedValue;
                                                    this.updateFormattedValue();
                                                    
                                                    $wire.set('unit_price', this.rawValue);
                                                    $wire.updateItemTypeFromPrice(this.rawValue);
                                                    
                                                    this.$nextTick(() => {
                                                        const oldCommas = (oldFormatted.match(/,/g) || []).length;
                                                        const newCommas = (this.formattedValue.match(/,/g) || []).length;
                                                        const commaDiff = newCommas - oldCommas;
                                                        
                                                        try {
                                                            event.target.setSelectionRange(
                                                                Math.max(0, cursorPos + commaDiff), 
                                                                Math.max(0, cursorPos + commaDiff)
                                                            );
                                                        } catch (e) {
                                                            // Ignore errors with cursor position
                                                        }
                                                    });
                                                }
                                            }">
                                                <flux:input 
                                                    id="unit_cost" 
                                                    x-model="formattedValue"
                                                    type="text" 
                                                    inputmode="decimal" 
                                                    placeholder="e.g., 1500.00"
                                                    @input="updatePrice($event)"
                                                    @keydown.tab="if (!event.shiftKey) { event.preventDefault(); $wire.dispatch('focus-unit'); }" />
                                            </div>
                                            @error('unit_price')
                                                <div class="mt-2 flex items-center text-sm text-red-600 dark:text-red-400">
                                                    <svg class="mr-2 h-5 w-5 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.21 3.03-1.742 3.03H4.42c-1.532 0-2.492-1.696-1.742-3.03l5.58-9.92zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" />
                                                    </svg>
                                                    <span>{{ $message }}</span>
                                                </div>
                                            @enderror
                                        </div>
                                        <div>
                                            <x-autocomplete id="unit_search_pricing" wire:model.live="unit_search" wire:suggestions="unit_suggestions" wire:showSuggestions="show_unit_suggestions" label="Unit of Measure" placeholder="e.g., piece, unit, kg" required onFocus="$wire.showAllUnits()" onSelect="$wire.selectUnit" @keydown.tab="if (event.shiftKey) { event.preventDefault(); $wire.dispatch('focus-unit-cost'); } else { event.preventDefault(); $wire.dispatch('focus-employee'); }" />
                                            @error('unit_of_measure')
                                                <div class="mt-2 flex items-center text-sm text-red-600 dark:text-red-400">
                                                    <svg class="mr-2 h-5 w-5 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.21 3.03-1.742 3.03H4.42c-1.532 0-2.492-1.696-1.742-3.03l5.58-9.92zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" />
                                                    </svg>
                                                    <span>{{ $message }}</span>
                                                </div>
                                            @enderror
                                            @if ($creating_new_unit)
                                                <div class="mt-2 flex items-center rounded-lg bg-green-50 p-2 text-sm text-green-700 dark:bg-green-800/20 dark:text-green-400" role="alert">
                                                    <svg class="mr-2 h-4 w-4 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                                                        <path d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5ZM9.5 4a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3ZM12 15H8a1 1 0 0 1 0-2h1v-3H8a1 1 0 0 1 0-2h2a1 1 0 0 1 1 1v4h1a1 1 0 0 1 0 2Z" />
                                                    </svg>
                                                    <span class="font-medium">New unit of measure will be used.</span>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                    @if ($isParItem)
                                        <div class="mt-4 flex w-full items-center rounded-lg bg-red-50 p-3 text-sm text-red-700 dark:bg-red-800/20 dark:text-red-400" role="alert">
                                            <svg class="mr-3 h-5 w-5 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 002 0v-3a1 1 0 00-2 0z" clip-rule="evenodd" />
                                            </svg>
                                            <span class="font-medium">
                                                The unit cost of ₱{{ number_format($this->unit_price, 2) }} for this item requires it to be recorded on a Property Accountability Receipt (PAR), not an ICS.
                                            </span>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>


                </div>

                <!-- Column 2: Employee Custody & Document Details -->
                <div class="space-y-6">
                    <!-- Current Employee Assignment & Transfer History Section -->
                    <div class="overflow-hidden rounded-lg border border-stone-200 bg-white shadow-sm dark:border-stone-700 dark:bg-stone-800">
                        <div class="border-b border-stone-200 px-4 py-3 dark:border-stone-700">
                            <h3 class="font-semibold text-stone-800 dark:text-stone-200">Employee Custody</h3>
                            <p class="text-xs text-stone-500 dark:text-stone-400 mt-1">
                                Change the assigned employee to automatically create a transfer record
                            </p>
                        </div>
                        <div class="p-4 space-y-6">
                            <!-- Currently Assigned Employee -->
                            <div>
                                <h4 class="text-sm font-medium text-stone-700 dark:text-stone-300 mb-3">Currently Assigned Employee</h4>
                                <x-autocomplete id="employee_search" wire:model.live="employee_search" wire:suggestions="employee_suggestions" wire:showSuggestions="show_employee_suggestions" label="Assign To Employee" placeholder="Type to search employees..." required onFocus="$wire.showAllEmployees()" onSelect="$wire.selectEmployee" />
                                @error('assigned_employee_id')
                                    <div class="mt-2 flex items-center text-sm text-red-600 dark:text-red-400">
                                        <svg class="mr-2 h-5 w-5 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.21 3.03-1.742 3.03H4.42c-1.532 0-2.492-1.696-1.742-3.03l5.58-9.92zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" />
                                        </svg>
                                        <span>{{ $message }}</span>
                                    </div>
                                @enderror
                                @if ($creating_new_employee)
                                    <div class="mt-2 flex items-center rounded-lg bg-green-50 p-2 text-sm text-green-700 dark:bg-green-800/20 dark:text-green-400" role="alert">
                                        <svg class="mr-2 h-4 w-4 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5ZM9.5 4a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3ZM12 15H8a1 1 0 0 1 0-2h1v-3H8a1 1 0 0 1 0-2h2a1 1 0 0 1 1 1v4h1a1 1 0 0 1 0 2Z" />
                                        </svg>
                                        <span class="font-medium">New employee will be created upon saving.</span>
                                    </div>
                                @endif

                                <!-- Transfer Notification -->
                                @if ($this->original_assigned_employee_id && $this->assigned_employee_id && $this->original_assigned_employee_id !== $this->assigned_employee_id)
                                    <div class="mt-2 flex items-center rounded-lg bg-amber-50 p-2 text-sm text-amber-700 dark:bg-amber-800/20 dark:text-amber-400" role="alert">
                                        <x-flux::icon.arrow-path class="mr-2 h-4 w-4 flex-shrink-0" />
                                        <span class="font-medium">A transfer record will be created when you save changes.</span>
                                    </div>
                                @endif
                                
                                <!-- Current Assignment Info -->
                                @if ($this->icsNumber->assignedEmployee && !$creating_new_employee)
                                    <div class="mt-3 p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800">
                                        <div class="flex items-center space-x-2 mb-1">
                                            <x-flux::icon.user class="h-4 w-4 text-blue-600 dark:text-blue-400" />
                                            <span class="text-sm font-medium text-blue-900 dark:text-blue-200">
                                                Currently Assigned To:
                                            </span>
                                        </div>
                                        <div class="ml-6">
                                            <div class="text-sm font-semibold text-blue-900 dark:text-blue-100">
                                                {{ $this->icsNumber->assignedEmployee->name }}
                                            </div>
                                            @if ($this->icsNumber->assignedEmployee->division || $this->icsNumber->assignedEmployee->position)
                                                <div class="text-xs text-blue-700 dark:text-blue-300">
                                                    @if ($this->icsNumber->assignedEmployee->division)
                                                        {{ $this->icsNumber->assignedEmployee->division->name }}
                                                    @endif
                                                    @if ($this->icsNumber->assignedEmployee->division && $this->icsNumber->assignedEmployee->position)
                                                        •
                                                    @endif
                                                    @if ($this->icsNumber->assignedEmployee->position)
                                                        {{ $this->icsNumber->assignedEmployee->position->title }}
                                                    @endif
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                @endif
                            </div>

                            <!-- Transfer History -->
                            @if ($this->icsNumber->transfers->isNotEmpty())
                                <div class="border-t border-stone-200 dark:border-stone-700 pt-4">
                                    <h4 class="text-sm font-medium text-stone-700 dark:text-stone-300 mb-3">Transfer History</h4>
                                    <div class="space-y-3 max-h-48 overflow-y-auto">
                                        @foreach ($this->icsNumber->transfers->sortByDesc('transfer_date') as $transfer)
                                            <div wire:key="transfer-{{ $transfer->id }}" class="flex items-start space-x-3 p-3 bg-stone-50 dark:bg-stone-800/50 rounded-lg">
                                                <div class="flex-shrink-0 mt-0.5">
                                                    <x-flux::icon.arrow-right class="h-4 w-4 text-stone-400" />
                                                </div>
                                                <div class="flex-1 min-w-0">
                                                    <div class="flex items-center justify-between">
                                                        <div class="text-sm">
                                                            <span class="font-medium text-stone-600 dark:text-stone-300">
                                                                {{ $transfer->fromEmployee?->name ?? 'N/A' }}
                                                            </span>
                                                            <span class="text-stone-500 dark:text-stone-400 mx-1">→</span>
                                                            <span class="font-medium text-stone-900 dark:text-stone-100">
                                                                {{ $transfer->toEmployee?->name ?? 'N/A' }}
                                                            </span>
                                                        </div>
                                                        <span class="text-xs text-stone-500 dark:text-stone-400 flex-shrink-0">
                                                            {{ $transfer->transfer_date->format('M d, Y') }}
                                                        </span>
                                                    </div>
                                                    @if ($transfer->fromEmployee?->division || $transfer->toEmployee?->division)
                                                        <div class="mt-1 text-xs text-stone-500 dark:text-stone-400">
                                                            @if ($transfer->fromEmployee?->division)
                                                                {{ $transfer->fromEmployee->division->name }}
                                                            @endif
                                                            @if ($transfer->fromEmployee?->division && $transfer->toEmployee?->division)
                                                                →
                                                            @endif
                                                            @if ($transfer->toEmployee?->division)
                                                                {{ $transfer->toEmployee->division->name }}
                                                            @endif
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @else
                                <div class="border-t border-stone-200 dark:border-stone-700 pt-4">
                                    <h4 class="text-sm font-medium text-stone-700 dark:text-stone-300 mb-3">Transfer History</h4>
                                    <div class="text-center py-6">
                                        <x-flux::icon.clock class="h-8 w-8 text-stone-300 dark:text-stone-600 mx-auto mb-2" />
                                        <p class="text-sm text-stone-500 dark:text-stone-400">No transfers recorded</p>
                                        <p class="text-xs text-stone-400 dark:text-stone-500 mt-1">This item has remained with its original assignee</p>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Document Details -->
                    <div class="overflow-hidden rounded-lg border border-stone-200 bg-white shadow-sm dark:border-stone-700 dark:bg-stone-800"
                        x-data="{
                            formatDate(event) {
                                let value = event.target.value.replace(/\D/g, '');
                                if (value.length > 8) {
                                    value = value.substring(0, 8);
                                }

                                let month = value.substring(0, 2);
                                let day = value.substring(2, 4);
                                let year = value.substring(4, 8);

                                if (month.length === 2) {
                                    let monthInt = parseInt(month, 10);
                                    if (monthInt > 12) month = '12';
                                    else if (monthInt === 0) month = '01';
                                }

                                if (day.length === 2 && month.length === 2) {
                                    let dayInt = parseInt(day, 10);
                                    let monthInt = parseInt(month, 10);
                                    let yearInt = year.length === 4 ? parseInt(year, 10) : new Date().getFullYear();
                                    
                                    let maxDaysInMonth = new Date(yearInt, monthInt, 0).getDate();

                                    if (dayInt > maxDaysInMonth) {
                                        day = maxDaysInMonth.toString();
                                    } else if (dayInt === 0) {
                                        day = '01';
                                    }
                                }
                                
                                value = month + day + year;
                                
                                let formattedValue = '';
                                if (value.length > 4) {
                                    formattedValue = `${value.substring(0, 2)}/${value.substring(2, 4)}/${value.substring(4, 8)}`;
                                } else if (value.length > 2) {
                                    formattedValue = `${value.substring(0, 2)}/${value.substring(2, 4)}`;
                                } else {
                                    formattedValue = value;
                                }

                                event.target.value = formattedValue;
                            }
                        }">
                        <div class="border-b border-stone-200 px-4 py-3 dark:border-stone-700">
                            <h3 class="font-semibold text-stone-800 dark:text-stone-200">Document Details</h3>
                        </div>
                        <div class="space-y-4 p-4">
                            <div class="grid grid-cols-1 gap-x-6 sm:grid-cols-2">
                                <div>
                                    <label for="ics_number_input" class="mb-1 block text-sm font-medium text-stone-700 dark:text-stone-300">
                                        ICS Number <span class="text-stone-500 dark:text-stone-400 font-normal">(Auto-generated)</span>
                                    </label>
                                    <div class="relative">
                                        <flux:input 
                                            id="ics_number_input"
                                            wire:model.blur="ics_number" 
                                            readonly
                                            tabindex="-1"
                                            class="bg-stone-50 dark:bg-stone-800 text-stone-600 dark:text-stone-400 cursor-not-allowed border-stone-200 dark:border-stone-700" />
                                        <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                                            <x-flux::icon.lock-closed class="h-4 w-4 text-stone-400 dark:text-stone-500" />
                                        </div>
                                    </div>
                                    <x-input-error for="ics_number" class="mt-2" />
                                </div>
                                <div x-data="{ 
                                    validateNumber(e) {
                                        e.target.value = e.target.value.replace(/[^\d]/g, '');
                                        $wire.set('estimated_useful_life', e.target.value ? parseInt(e.target.value) : null);
                                    }
                                }">
                                    <x-quantity-input 
                                        id="estimated_useful_life_wrapper"
                                        wire:model="estimated_useful_life" 
                                        label="Estimated Useful Life (Years)" 
                                        placeholder="Optional" 
                                        :disabled="$isParItem"
                                        type="number"
                                        @input="validateNumber($event)"
                                        @keydown.tab="if (!event.shiftKey) { event.preventDefault(); $wire.dispatch('focus-date-prepared'); }" />
                                    <x-input-error for="estimated_useful_life" class="mt-2" />
                                </div>
                            </div>

                            <div>
                                <label for="ics_type" class="mb-1 block text-sm font-medium text-stone-700 dark:text-stone-300">
                                    ICS Type <span class="text-stone-500 dark:text-stone-400 font-normal">(Auto-generated)</span>
                                </label>
                                <div class="relative">
                                    <flux:input 
                                        id="ics_type" 
                                        wire:model="ics_type" 
                                        readonly 
                                        tabindex="-1" 
                                        :value="$ics_type"
                                        class="bg-stone-50 dark:bg-stone-800 text-stone-600 dark:text-stone-400 cursor-not-allowed border-stone-200 dark:border-stone-700" />
                                    <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                                        <x-flux::icon.lock-closed class="h-4 w-4 text-stone-400 dark:text-stone-500" />
                                    </div>
                                </div>
                                <x-input-error for="ics_type" class="mt-2" />
                            </div>

                            <div class="grid grid-cols-1 gap-x-6 sm:grid-cols-2">
                                <div>
                                    <flux:input
                                        id="date_prepared"
                                        wire:model.blur="date_prepared"
                                        type="text"
                                        label="Date Prepared"
                                        placeholder="MM/DD/YYYY"
                                        :disabled="$isParItem"
                                        tabindex="513"
                                        @input="formatDate($event)"
                                        @keydown.tab="if (!event.shiftKey) { event.preventDefault(); $wire.dispatch('focus-date-accepted'); }"
                                    />
                                </div>

                                <div>
                                    <flux:input
                                        id="date_accepted"
                                        wire:model.blur="date_accepted"
                                        type="text"
                                        label="Date Accepted"
                                        placeholder="MM/DD/YYYY"
                                        :disabled="$isParItem"
                                        tabindex="514"
                                        @input="formatDate($event)"
                                        @keydown.tab="if (!event.shiftKey) { event.preventDefault(); $wire.dispatch('focus-remarks'); }"
                                    />
                                </div>
                            </div>

                            <flux:textarea 
                                id="remarks"
                                wire:model="remarks" 
                                label="Remarks" 
                                placeholder="Add any notes or remarks here..." 
                                :disabled="$isParItem" 
                                tabindex="515" 
                                rows="10" 
                                @keydown.tab="if (!event.shiftKey) { event.preventDefault(); $wire.dispatch('focus-quantity'); }" 
                            />
                        </div>
                    </div>
                </div>

                <!-- Column 3: Batches -->
                <div class="space-y-6 lg:col-span-2 xl:col-span-1">
                    <!-- Batches & Components -->
                    <div class="overflow-hidden rounded-lg border border-stone-200 bg-white shadow-sm dark:border-stone-700 dark:bg-stone-800">
                        <div class="border-b border-stone-200 px-4 py-3 dark:border-stone-700">
                            <h3 class="font-semibold text-stone-800 dark:text-stone-200">
                                Batches
                            </h3>
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
                                        @keydown.tab="if (!event.shiftKey) { event.preventDefault(); $wire.dispatch('focus-auto-populate'); }"
                                    />
                                    <x-input-error for="quantity" class="mt-2" />
                                </div>
                                
                                <!-- Global settings for batches -->
                                <div class="flex items-center mb-4" x-data="{ autoSerialNumbers: true }">
                                    <div class="flex items-center h-5">
                                        <input id="auto-serial-numbers" x-model="autoSerialNumbers" type="checkbox" 
                                            class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 focus:ring-2"
                                            @change="$wire.set('batches', $wire.batches.map(batch => {
                                                if (autoSerialNumbers) {
                                                    // Add 'Serial Number: ' prefix if it doesn't exist
                                                    if (!batch.identification_data || batch.identification_data.trim() === '') {
                                                        batch.identification_data = 'Serial Number: ';
                                                    }
                                                } else {
                                                    // Remove 'Serial Number: ' prefix if it exists
                                                    if (batch.identification_data && batch.identification_data.startsWith('Serial Number: ')) {
                                                        if (batch.identification_data === 'Serial Number: ' || batch.identification_data === 'Serial Number:') {
                                                            batch.identification_data = '';
                                                        } else {
                                                            // Preserve any text after the prefix
                                                            const userInput = batch.identification_data.substring('Serial Number: '.length).trim();
                                                            batch.identification_data = userInput;
                                                        }
                                                    }
                                                }
                                                return batch;
                                            }))"
                                            @keydown.tab="if (!event.shiftKey) { event.preventDefault(); $wire.dispatch('focus-serial-number'); }"
                                            checked>
                                    </div>
                                    <label for="auto-serial-numbers" class="ms-2 text-sm font-medium text-stone-800 dark:text-stone-200">
                                        Auto-populate "Serial Number: " field for all batches
                                    </label>
                                </div>

                                <div class="space-y-6">
                                    @foreach ($batches as $batchIndex => $batch)
                                        @if (!($batch['_destroy'] ?? false))
                                            <div wire:key="batch-{{ $batchIndex }}" class="rounded-lg border border-stone-300 bg-white p-0 dark:border-stone-600 dark:bg-stone-800/50" x-data="{
                                                expanded: true,
                                                setDefaultSerial() {
                                                    if (document.getElementById('auto-serial-numbers').checked && !@this.get('batches.{{ $batchIndex }}.identification_data')) {
                                                        @this.set('batches.{{ $batchIndex }}.identification_data', 'Serial Number: ');
                                                    }
                                                }
                                            }" x-init="setDefaultSerial()">
                                                <div class="flex items-center justify-between p-3 bg-stone-50 dark:bg-stone-700/50 rounded-t-lg border-b border-stone-200 dark:border-stone-700">
                                                    <h4 class="font-semibold text-stone-800 dark:text-stone-200 flex items-center space-x-2">
                                                        <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-stone-200 dark:bg-stone-600 text-sm font-medium">
                                                            {{ $loop->iteration }}
                                                        </span>
                                                        <span>Batch #{{ $loop->iteration }}</span>
                                                        @if ($isDesktopComputer)
                                                            <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-purple-100 text-purple-800 dark:bg-purple-800/20 dark:text-purple-300">
                                                                Desktop Computer
                                                            </span>
                                                        @endif
                                                    </h4>
                                                    <div class="flex items-center space-x-2">
                                                        <button 
                                                            type="button" 
                                                            @click="expanded = !expanded" 
                                                            class="text-stone-400 hover:text-stone-600 dark:hover:text-stone-300 focus:outline-none"
                                                        >
                                                            <x-flux::icon.chevron-up x-show="expanded" class="h-5 w-5" />
                                                            <x-flux::icon.chevron-down x-show="!expanded" class="h-5 w-5" />
                                                        </button>
                                                        @if ($quantity > 1)
                                                            <flux:button type="button" variant="danger" size="sm" wire:click.prevent="removeBatch({{ $batchIndex }})">
                                                                <x-flux::icon.trash class="h-4 w-4" />
                                                            </flux:button>
                                                        @endif
                                                    </div>
                                                </div>
                                                
                                                <div x-show="expanded" class="p-3">
                                                    <!-- Identification Data (Serial number, Asset tag, etc.) -->
                                                    <div>
                                                        <div class="relative">
                                                            <flux:input 
                                                                id="{{ $batchIndex === 0 ? 'serial_number_0' : '' }}"
                                                                wire:model="batches.{{ $batchIndex }}.identification_data" 
                                                                label="Serial Number/Asset Tag" 
                                                                placeholder="Enter serial number, asset tag or other identifying info" 
                                                                tabindex="{{ 10 + ((int) $batchIndex * 100) }}"
                                                                @focus="if ($el.value === 'Serial Number: ') { $el.select(); }" />
                                                        </div>
                                                    </div>

                                                @if ($isDesktopComputer)
                                                    <div x-data="{ showComponents: true }">
                                                        <div class="mt-4 border-t border-stone-200 pt-3 dark:border-stone-700">
                                                            <flux:button 
                                                                type="button" 
                                                                variant="outline" 
                                                                size="sm" 
                                                                @click="showComponents = !showComponents"
                                                                class="w-full flex justify-between items-center"
                                                            >
                                                                <span>Batch Components</span>
                                                                <span x-show="!showComponents"><x-flux::icon.chevron-down class="h-4 w-4" /></span>
                                                                <span x-show="showComponents"><x-flux::icon.chevron-up class="h-4 w-4" /></span>
                                                            </flux:button>
                                                        </div>
                                                        
                                                        <div x-show="showComponents" class="mt-3">
                                                            <div class="space-y-4">
                                                                @foreach ($batch['components'] as $componentIndex => $component)
                                                                    @if (!($component['_destroy'] ?? false))
                                                                        <div wire:key="component-{{ $batchIndex }}-{{ $componentIndex }}" class="relative rounded-lg border border-stone-200 bg-white p-4 dark:border-stone-600 dark:bg-stone-700">
                                                                            <div class="flex justify-between items-center mb-2">
                                                                                <h5 class="font-medium text-stone-800 dark:text-stone-200">
                                                                                    Component #{{ $loop->iteration }}
                                                                                </h5>
                                                                                @if (count(array_filter($batch['components'], fn($c) => !($c['_destroy'] ?? false))) > 1)
                                                                                    <flux:button type="button" variant="danger" size="xs" wire:click.prevent="removeComponent({{ $batchIndex }}, {{ $componentIndex }})">
                                                                                        <x-flux::icon.trash class="h-4 w-4" />
                                                                                    </flux:button>
                                                                                @endif
                                                                            </div>
                                                                            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                                                                <flux:input wire:model="batches.{{ $batchIndex }}.components.{{ $componentIndex }}.component_type" label="Component Type" placeholder="e.g., Monitor, Casing, UPS" required tabindex="{{ 11 + ((int) $batchIndex * 100) + ((int) $componentIndex * 4) + 1 }}" />
                                                                                <x-input-error :for="'batches.' . $batchIndex . '.components.' . $componentIndex . '.component_type'" class="mt-2" />
                                                                                <flux:input wire:model="batches.{{ $batchIndex }}.components.{{ $componentIndex }}.serial_number" label="Serial Number" tabindex="{{ 11 + ((int) $batchIndex * 100) + ((int) $componentIndex * 4) + 2 }}" />
                                                                                <x-input-error :for="'batches.' . $batchIndex . '.components.' . $componentIndex . '.serial_number'" class="mt-2" />
                                                                                <flux:input wire:model="batches.{{ $batchIndex }}.components.{{ $componentIndex }}.brand" label="Brand" tabindex="{{ 11 + ((int) $batchIndex * 100) + ((int) $componentIndex * 4) + 3 }}" />
                                                                                <x-input-error :for="'batches.' . $batchIndex . '.components.' . $componentIndex . '.brand'" class="mt-2" />
                                                                                <flux:input wire:model="batches.{{ $batchIndex }}.components.{{ $componentIndex }}.model" label="Model" tabindex="{{ 11 + ((int) $batchIndex * 100) + ((int) $componentIndex * 4) + 4 }}" />
                                                                                <x-input-error :for="'batches.' . $batchIndex . '.components.' . $componentIndex . '.model'" class="mt-2" />
                                                                            </div>
                                                                        </div>
                                                                    @endif
                                                                @endforeach
                                                            </div>

                                                            <div class="mt-4 text-center">
                                                                <flux:button type="button" variant="outline" wire:click.prevent="addComponent({{ $batchIndex }})" class="w-full">
                                                                    <div class="flex items-center justify-center">
                                                                        <x-flux::icon.plus class="mr-2 h-4 w-4" />
                                                                        <span>Add Component</span>
                                                                    </div>
                                                                </flux:button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                @endif
                                                </div>
                                            </div>
                                        @endif
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



    <!-- Transfer Modal -->
    <flux:modal title="Transfer Item Custody" :show="$showTransferModal" max-width="lg" @close="$wire.set('showTransferModal', false)">
        <x-slot:content>
            <div class="p-6">
                <!-- Current Assignment Info -->
                @if ($this->icsNumber->assignedEmployee)
                    <div class="mb-6 p-4 bg-stone-50 dark:bg-stone-800/50 rounded-lg border border-stone-200 dark:border-stone-700">
                        <h4 class="text-sm font-medium text-stone-700 dark:text-stone-300 mb-2">Currently Assigned To:</h4>
                        <div class="flex items-center space-x-2">
                            <x-flux::icon.user class="h-4 w-4 text-stone-500" />
                            <span class="font-medium text-stone-900 dark:text-stone-100">
                                {{ $this->icsNumber->assignedEmployee->name }}
                            </span>
                        </div>
                        @if ($this->icsNumber->assignedEmployee->division || $this->icsNumber->assignedEmployee->position)
                            <div class="mt-1 text-sm text-stone-600 dark:text-stone-400 ml-6">
                                @if ($this->icsNumber->assignedEmployee->division)
                                    {{ $this->icsNumber->assignedEmployee->division->name }}
                                @endif
                                @if ($this->icsNumber->assignedEmployee->division && $this->icsNumber->assignedEmployee->position)
                                    • 
                                @endif
                                @if ($this->icsNumber->assignedEmployee->position)
                                    {{ $this->icsNumber->assignedEmployee->position->title }}
                                @endif
                            </div>
                        @endif
                    </div>
                @endif

                <div class="space-y-4">
                    <div>
                        <flux:select wire:model="transfer_to_employee_id" label="Transfer To Employee" required>
                            <option value="">Select the new custodian</option>
                            @foreach ($this->allEmployees as $employee)
                                <option value="{{ $employee->id }}" @if($employee->id === $assigned_employee_id) disabled @endif>
                                    {{ $employee->name }}
                                    @if($employee->id === $assigned_employee_id) (Current Custodian) @endif
                                </option>
                            @endforeach
                        </flux:select>
                        @error('transfer_to_employee_id')
                            <div class="mt-2 flex items-center text-sm text-red-600 dark:text-red-400">
                                <svg class="mr-2 h-5 w-5 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.21 3.03-1.742 3.03H4.42c-1.532 0-2.492-1.696-1.742-3.03l5.58-9.92zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" />
                                </svg>
                                <span>{{ $message }}</span>
                            </div>
                        @enderror
                    </div>

                    <div>
                        <flux:input type="date" wire:model="transfer_date" label="Transfer Date" required />
                        @error('transfer_date')
                            <div class="mt-2 flex items-center text-sm text-red-600 dark:text-red-400">
                                <svg class="mr-2 h-5 w-5 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.21 3.03-1.742 3.03H4.42c-1.532 0-2.492-1.696-1.742-3.03l5.58-9.92zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" />
                                </svg>
                                <span>{{ $message }}</span>
                            </div>
                        @enderror
                    </div>
                </div>

                <div class="mt-6 p-4 bg-amber-50 dark:bg-amber-900/20 rounded-lg border border-amber-200 dark:border-amber-800">
                    <div class="flex items-start space-x-3">
                        <x-flux::icon.exclamation-triangle class="h-5 w-5 text-amber-600 dark:text-amber-400 flex-shrink-0 mt-0.5" />
                        <div>
                            <h5 class="text-sm font-medium text-amber-900 dark:text-amber-200">Transfer Notice</h5>
                            <p class="mt-1 text-sm text-amber-700 dark:text-amber-300">
                                This action will create a transfer record and update the current custodian. The transfer will be logged in the system for audit purposes.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </x-slot:content>

        <x-slot:footer>
            <div class="flex justify-end gap-x-4">
                <flux:button variant="ghost" wire:click="$set('showTransferModal', false)">Cancel</flux:button>
                <flux:button variant="primary" wire:click="transferItem" wire:loading.attr="disabled" wire:target="transferItem">
                    <span wire:loading.remove wire:target="transferItem">Complete Transfer</span>
                    <span wire:loading wire:target="transferItem">Processing...</span>
                </flux:button>
            </div>
        </x-slot:footer>
    </flux:modal>

    <!-- Delete Confirmation Modal -->
    <flux:modal name="delete-ics-confirmation" class="min-w-[26rem]">
        <div class="space-y-6">
            <div class="flex items-start space-x-3">
                <div class="flex-shrink-0">
                    <x-flux::icon.exclamation-triangle class="h-6 w-6 text-red-500" />
                </div>
                <div>
                    <h3 class="text-lg font-semibold text-stone-900 dark:text-stone-100">Delete ICS Record?</h3>
                    <div class="mt-2 space-y-1">
                        <p class="text-sm text-stone-600 dark:text-stone-400">
                            You're about to delete ICS record <span class="font-medium text-stone-900 dark:text-stone-100">#{{ $icsNumber->ics_number }}</span>.
                        </p>
                        <p class="text-sm font-medium text-red-700 dark:text-red-300">
                            This action cannot be undone.
                        </p>
                    </div>
                </div>
            </div>
            <div class="flex gap-3 pt-2">
                <flux:spacer />
                <flux:modal.close>
                    <flux:button variant="ghost">Cancel</flux:button>
                </flux:modal.close>
                <flux:button wire:click="destroy" variant="danger" wire:loading.attr="disabled" wire:target="destroy">
                    <span wire:loading.remove wire:target="destroy">Delete Record</span>
                    <span wire:loading wire:target="destroy">Deleting...</span>
                </flux:button>
            </div>
        </div>
    </flux:modal>
</div>
</div>

@script
<script>
    document.addEventListener('livewire:initialized', () => {
        const focusOn = (elementId, inner = false) => {
            setTimeout(() => {
                const element = document.getElementById(elementId);
                if (element) {
                    if (inner) {
                        const input = element.querySelector('input, textarea');
                        if (input) {
                            input.focus();
                            return;
                        }
                    }
                    element.focus();
                }
            }, 0);
        };

        @this.on('focus-contract', () => focusOn('contract_search'));
        @this.on('focus-item', () => focusOn('item_search'));
        @this.on('focus-specification', () => focusOn('specification_search'));
        @this.on('focus-employee', () => focusOn('employee_search'));
        @this.on('focus-primary-category', () => focusOn('primary_category_search'));
        @this.on('focus-secondary-category', () => focusOn('secondary_category_search'));
        @this.on('focus-brand', () => focusOn('brand_search'));
        @this.on('focus-model', () => focusOn('model_search'));
        @this.on('focus-detailed-specs', () => focusOn('detailed_specifications'));
        @this.on('focus-unit-cost', () => focusOn('unit_cost'));
        @this.on('focus-unit', () => focusOn('unit_search_pricing'));
        @this.on('focus-estimated-useful-life', () => focusOn('estimated_useful_life_wrapper', true));
        @this.on('focus-date-prepared', () => focusOn('date_prepared'));
        @this.on('focus-date-accepted', () => focusOn('date_accepted'));
        @this.on('focus-remarks', () => focusOn('remarks'));
        @this.on('focus-quantity', () => focusOn('quantity', true));
        @this.on('focus-auto-populate', () => focusOn('auto-serial-numbers'));
        @this.on('focus-serial-number', () => focusOn('serial_number_0'));
        
        // Skip focus on ICS Number input by redirecting immediately to estimated useful life
        const icsField = document.getElementById('ics_number_input');
        if (icsField) {
            icsField.addEventListener('focus', () => focusOn('estimated_useful_life_wrapper', true));
        }
        
        // Removed autofocus on supplier field for better UX
        
        // Handle automatically adding serial number prefix to new batches
        @this.on('batch-added', () => {
            const autoSerialCheckbox = document.getElementById('auto-serial-numbers');
            if (autoSerialCheckbox && autoSerialCheckbox.checked) {
                const batchesLength = @this.batches.length;
                if (batchesLength > 0) {
                    const lastBatchIndex = batchesLength - 1;
                    if (!@this.batches[lastBatchIndex].identification_data) {
                        @this.set(`batches.${lastBatchIndex}.identification_data`, 'Serial Number: ');
                    }
                }
            }
        });
    });
</script>
@endscript 
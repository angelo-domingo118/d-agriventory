<?php

use App\Models\Contract;
use App\Models\ContractItem;
use App\Models\Employee;
use App\Models\ItemsCatalog;
use App\Models\ItemSpecification;
use App\Models\SecondaryCategory;
use App\Models\PrimaryCategory;
use App\Models\Supplier;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use App\Models\IcsNumber;
use Illuminate\Support\Facades\DB;
use App\Models\IcsItemBatch;
use App\Models\ItemComponent;
use App\Services\ToastService;
use Flux\Flux;

new #[Layout('components.layouts.app')] class extends Component {
    // Form state
    public string $ics_number = '';
    public ?int $supplier_id = null;
    public ?int $contract_id = null;
    public ?int $items_catalog_id = null; // Changed from item_specification_id
    public ?string $item_specification_id = null; // Can now be "new" or an integer ID
    public ?int $contract_item_id = null;
    public ?int $assigned_employee_id = null;
    public string $ics_type = 'SPLV - Semi-Expendable Property (Low Value) - ₱5,000 or less';
    public int $quantity = 1;
    public ?int $estimated_useful_life = null;
    public ?string $date_prepared = null;
    public ?string $date_accepted = null;
    public string $remarks = '';

    // Validation rules for live validation
    protected function rules()
    {
        return [
            'quantity' => 'integer|min:0', // Allow 0 during editing, but validation at submission requires min:1
            'estimated_useful_life' => 'nullable|integer|min:1',
        ];
    }

    // New fields for main item
    public ?string $main_item_brand = null;
    public ?string $main_item_model = null;
    public ?string $detailed_specifications = null;
    public ?string $main_item_serial_number = null;

    // Category fields for new items
    public ?int $primary_category_id = null;
    public ?int $secondary_category_id = null;
    public ?string $unit_of_measure = '';
    
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

    // Display only property
    public ?float $unit_price = null;
    public bool $isParItem = false;
    public bool $isDesktopComputer = false;

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

    // Primary Category Autocomplete
    public string $primary_category_search = '';
    public array $primary_category_suggestions = [];
    public bool $show_primary_category_suggestions = false;
    public ?string $selected_primary_category_name = null;

    // Secondary Category Autocomplete
    public string $secondary_category_search = '';
    public array $secondary_category_suggestions = [];
    public bool $show_secondary_category_suggestions = false;
    public ?string $selected_secondary_category_name = null;

    public Collection $allPrimaryCategories;
    public Collection $allSecondaryCategories;

    public function mount(): void
    {
        if (!auth()->user()->hasAdminPermission('create_inventory')) {
            abort(403);
        }
        
        // Auto-generate ICS number
        $this->generateIcsNumber();
        
        // Start with one batch by default
        $this->quantity = 1;
        $this->updateBatches();
        
        // Set default unit of measure - empty string initially
        $this->unit_of_measure = '';
        $this->unit_search = '';
        $this->selected_unit = '';
        
        // Initialize brand and model search fields
        $this->brand_search = '';
        $this->model_search = '';
        $this->selected_brand = '';
        $this->selected_model = '';
        
        // Set default ICS type with full description
        $this->updateItemType();
    }

    public function generateIcsNumber(): void
    {
        // Find the highest numeric ICS number and its batch count
        $lastIcs = IcsNumber::orderByRaw('CAST(ics_number AS UNSIGNED) DESC')->first();
        
        if ($lastIcs) {
            // Count the number of batches for the last ICS number
            $batchCount = IcsItemBatch::where('ics_number_id', $lastIcs->id)->count();
            
            // If there are no batches, default to 1
            if ($batchCount === 0) {
                $batchCount = 1;
            }
            
            // Calculated based on previous ICS number + batch count
            $this->ics_number = (string)(((int) $lastIcs->ics_number) + $batchCount);
        } else {
            // If no previous ICS numbers, start with 1
            $this->ics_number = '1';
        }
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
        // Get all distinct units from the database
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
        
        // Create suggestions array
        $this->unit_suggestions = array_map(function($unit) {
            return [
                'id' => $unit,
                'name' => $unit,
                'type' => 'existing'
            ];
        }, $units);
        
        // Add "new" option if the query doesn't match exactly
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
        // Guard against null or invalid data
        if (!$unitData || !is_array($unitData) || !isset($unitData['type'])) {
            return;
        }
        
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
        // Get all distinct brands from the database
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
        
        // Create suggestions array
        $this->brand_suggestions = array_map(function($brand) {
            return [
                'id' => $brand,
                'name' => $brand,
                'type' => 'existing'
            ];
        }, $brands);
        
        // Add "new" option if the query doesn't match exactly
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
        // Guard against null or invalid data
        if (!$brandData || !is_array($brandData) || !isset($brandData['type'])) {
            return;
        }
        
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
        // Get all distinct models from the database
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
        
        // Create suggestions array
        $this->model_suggestions = array_map(function($model) {
            return [
                'id' => $model,
                'name' => $model,
                'type' => 'existing'
            ];
        }, $models);
        
        // Add "new" option if the query doesn't match exactly
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
        // Guard against null or invalid data
        if (!$modelData || !is_array($modelData) || !isset($modelData['type'])) {
            return;
        }
        
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
        // Guard against null or invalid data
        if (!$supplierData || !is_array($supplierData) || !isset($supplierData['type'])) {
            return;
        }
        
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
        // Guard against null or invalid data
        if (!$contractData || !is_array($contractData) || !isset($contractData['type'])) {
            return;
        }
        
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

    // Item autocomplete methods (updated)
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
                'id' => $item->id, // This is items_catalog_id
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
        // Guard against null or invalid data
        if (!$itemData || !is_array($itemData) || !isset($itemData['type'])) {
            return;
        }
        
        // Reset all item-related properties when a new catalog item is selected
        $this->resetItemData();
        
        if ($itemData['type'] === 'existing') {
            $this->items_catalog_id = $itemData['id'];
            $this->item_search = $itemData['name'];
            $this->selected_item_name = $itemData['name'];
            
            // Set unit of measure from the selected item
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

            // Let user select a specification
            $this->dispatch('focus-specification');

        } elseif ($itemData['type'] === 'new') {
            preg_match('/"([^"]+)"/', $itemData['name'], $matches);
            $newItemName = $matches[1] ?? $this->item_search;

            $this->items_catalog_id = null;
            $this->item_search = $newItemName;
            $this->selected_item_name = $newItemName . ' (new)';
            $this->unit_price = 0.0;
            $this->creating_new_item = true;
            $this->creating_new_specification = true; // New item requires new spec
            $this->item_specification_id = 'new';

            // Reset spec fields for new item entry
            $this->main_item_brand = null;
            $this->main_item_model = null;
            $this->detailed_specifications = null;
            $this->unit_of_measure = '';
            $this->unit_search = '';
            $this->selected_unit = '';
            $this->primary_category_id = null;
            $this->secondary_category_id = null;
            
            // Focus on primary category instead of skipping to employee
            $this->dispatch('focus-primary-category');
        }
        
        $this->show_item_suggestions = false;
        $this->updateItemType();
    }

    public function updatedSpecificationSearch($value): void
    {
        $this->searchSpecifications($value);
    }

    public function showAllSpecifications(): void
    {
        $this->searchSpecifications($this->specification_search);
        if (count($this->specification_suggestions) > 0) {
            $this->show_specification_suggestions = true;
        }
    }

    public function searchSpecifications($query): void
    {
        if (!$this->items_catalog_id) {
            $this->specification_suggestions = [];
            $this->show_specification_suggestions = false;
            return;
        }

        $specsQuery = ItemSpecification::where('item_catalog_id', $this->items_catalog_id);

        if (strlen(trim($query)) > 0) {
            $specsQuery->where(function ($q) use ($query) {
                $q->where('brand', 'like', '%' . $query . '%')
                ->orWhere('model', 'like', '%' . $query . '%');
            });
        }

        $specs = $specsQuery->orderBy('brand')->orderBy('model')->get();
        
        $this->specification_suggestions = $specs->map(function ($spec) {
            $specName = collect([$spec->brand, $spec->model])->filter()->implode(' ');
            return [
                'id' => $spec->id,
                'name' => $specName ?: 'Default Specification',
                'type' => 'existing',
                'brand' => $spec->brand,
                'model' => $spec->model,
                'description' => $spec->detailed_specifications,
            ];
        })->toArray();

        // Always add a "Create New" option at the top
        array_unshift($this->specification_suggestions, [
            'id' => 'new',
            'name' => '+ Create New Specification',
            'type' => 'new',
            'description' => 'Add a new specification for this item catalog entry.',
        ]);

        $this->show_specification_suggestions = count($this->specification_suggestions) > 0;
    }

    public function selectSpecification($specData): void
    {
        // Guard against null or invalid data
        if (!$specData || !is_array($specData) || !isset($specData['type'])) {
            return;
        }
        
        if ($specData['type'] === 'existing') {
            $this->item_specification_id = $specData['id'];
            $this->specification_search = $specData['name'];
            $this->selected_specification_name = $specData['name'];
            $this->creating_new_specification = false;

            $this->main_item_brand = $specData['brand'];
            $this->main_item_model = $specData['model'];
            $this->detailed_specifications = $specData['description'];

            if ($this->contract_id) {
                $contractItem = ContractItem::where('item_specification_id', $this->item_specification_id)
                    ->where('contract_id', $this->contract_id)
                    ->first();
                $this->unit_price = $contractItem->unit_price ?? 0.0;
                $this->contract_item_id = $contractItem?->id;
            } else {
                $this->unit_price = 0.0;
            }
            $this->dispatch('focus-employee');

        } elseif ($specData['type'] === 'new') {
            $this->item_specification_id = 'new';
            $this->specification_search = '';
            $this->selected_specification_name = 'Creating new specification...';
            $this->creating_new_specification = true;

            // Reset fields for new spec entry
            $this->main_item_brand = null;
            $this->main_item_model = null;
            $this->detailed_specifications = null;
            $this->unit_price = 0.0;
            $this->contract_item_id = null;
            $this->dispatch('focus-brand');
        }

        $this->show_specification_suggestions = false;
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
            $employees = Employee::with('division')
                ->whereNotNull('name')
                ->where('name', '!=', '')
                ->orderBy('name')
                ->get();
        } else {
            $employees = Employee::with('division')
                ->whereNotNull('name')
                ->where('name', '!=', '')
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
                $description_parts[] = $employee->position;
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
        // Guard against null or invalid data
        if (!$employeeData || !is_array($employeeData) || !isset($employeeData['type'])) {
            return;
        }
        
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

    // Primary Category autocomplete methods
    public function updatedPrimaryCategorySearch($value): void
    {
        $this->searchPrimaryCategories($value);
    }

    public function showAllPrimaryCategories(): void
    {
        $this->searchPrimaryCategories($this->primary_category_search);
        if (count($this->primary_category_suggestions) > 0) {
            $this->show_primary_category_suggestions = true;
        }
    }

    public function searchPrimaryCategories($query): void
    {
        if (strlen(trim($query)) === 0) {
            $categories = PrimaryCategory::orderBy('name')->get();
        } else {
            $categories = PrimaryCategory::where('name', 'like', '%' . $query . '%')
                ->orderBy('name')
                ->get();
        }

        $this->primary_category_suggestions = $categories->map(function ($category) {
            return [
                'id' => $category->id,
                'name' => $category->name,
                'type' => 'existing'
            ];
        })->toArray();

        $this->show_primary_category_suggestions = count($this->primary_category_suggestions) > 0;
    }

    public function selectPrimaryCategory($categoryData): void
    {
        // Guard against null or invalid data
        if (!$categoryData || !is_array($categoryData) || !isset($categoryData['type'])) {
            return;
        }
        
        if ($categoryData['type'] === 'existing') {
            $this->primary_category_id = $categoryData['id'];
            $this->primary_category_search = $categoryData['name'];
            $this->selected_primary_category_name = $categoryData['name'];
        }

        $this->show_primary_category_suggestions = false;
        // Reset secondary category when primary changes
        $this->secondary_category_id = null;
        $this->secondary_category_search = '';
        $this->selected_secondary_category_name = null;
        $this->dispatch('focus-secondary-category');
    }

    // Secondary Category autocomplete methods
    public function updatedSecondaryCategorySearch($value): void
    {
        $this->searchSecondaryCategories($value);
    }

    public function showAllSecondaryCategories(): void
    {
        $this->searchSecondaryCategories($this->secondary_category_search);
        if (count($this->secondary_category_suggestions) > 0) {
            $this->show_secondary_category_suggestions = true;
        }
    }

    public function searchSecondaryCategories($query): void
    {
        if (!$this->primary_category_id) {
            $this->secondary_category_suggestions = [];
            $this->show_secondary_category_suggestions = false;
            return;
        }

        $queryBuilder = SecondaryCategory::where('primary_category_id', $this->primary_category_id);

        if (strlen(trim($query)) > 0) {
            $queryBuilder->where('name', 'like', '%' . $query . '%');
        }

        $categories = $queryBuilder->orderBy('name')->get();

        $this->secondary_category_suggestions = $categories->map(function ($category) {
            return [
                'id' => $category->id,
                'name' => $category->name,
                'type' => 'existing'
            ];
        })->toArray();

        $this->show_secondary_category_suggestions = count($this->secondary_category_suggestions) > 0;
    }

    public function selectSecondaryCategory($categoryData): void
    {
        // Guard against null or invalid data
        if (!$categoryData || !is_array($categoryData) || !isset($categoryData['type'])) {
            return;
        }
        
        if ($categoryData['type'] === 'existing') {
            $this->secondary_category_id = $categoryData['id'];
            $this->secondary_category_search = $categoryData['name'];
            $this->selected_secondary_category_name = $categoryData['name'];
        }

        $this->show_secondary_category_suggestions = false;
        $this->dispatch('focus-brand');
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
        $this->items_catalog_id = null; // Changed
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
        
        // Reset brand autocomplete fields
        $this->brand_search = '';
        $this->brand_suggestions = [];
        $this->show_brand_suggestions = false;
        $this->selected_brand = null;
        $this->creating_new_brand = false;
        
        // Reset model autocomplete fields
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
        
        // Format unit price and set ICS type based on value
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
    
    // Custom method that can be called from Alpine
    public function updateItemTypeFromPrice($price): void
    {
        $this->unit_price = $price;
        $this->updateItemType();
    }

    #[Computed]
    public function specifications()
    {
        if (!$this->items_catalog_id) {
            return collect();
        }
        return ItemSpecification::where('item_catalog_id', $this->items_catalog_id)->get();
    }

    public function updatedItemSpecificationId($value)
    {
        if ($value === 'new') {
            $this->creating_new_specification = true;
            // Reset fields for new spec entry
            $this->main_item_brand = null;
            $this->main_item_model = null;
            $this->detailed_specifications = null;
            $this->unit_price = 0.0; // Reset price as it's tied to spec in contract
            $this->contract_item_id = null;
            
            // Reset brand and model autocomplete fields
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

        } elseif ($value) {
            $this->creating_new_specification = false;
            $spec = ItemSpecification::find($value);
            if ($spec) {
                $this->main_item_brand = $spec->brand;
                $this->main_item_model = $spec->model;
                $this->detailed_specifications = $spec->detailed_specifications;
                
                // Set brand and model search fields
                $this->brand_search = $spec->brand ?? '';
                $this->selected_brand = $spec->brand ?? '';
                $this->creating_new_brand = false;
                
                $this->model_search = $spec->model ?? '';
                $this->selected_model = $spec->model ?? '';
                $this->creating_new_model = false;

                if ($this->contract_id) {
                    $contractItem = ContractItem::where('item_specification_id', $spec->id)
                        ->where('contract_id', $this->contract_id)
                        ->first();
                    $this->unit_price = $contractItem->unit_price ?? 0.0;
                    $this->contract_item_id = $contractItem?->id;
                } else {
                    $this->unit_price = 0.0;
                }
            }
        } else {
             $this->main_item_brand = null;
             $this->main_item_model = null;
             $this->detailed_specifications = null;
             $this->unit_price = 0.0;
             $this->contract_item_id = null;
             
             // Reset brand and model autocomplete fields
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
        }

        $this->updateItemType();
    }

    public function updatedUnitPrice(): void
    {
        $this->updateItemType();
    }

    public function updatedSecondaryCategoryId()
    {
        $this->dispatch('focus-brand');
    }

    #[Computed]
    public function filteredSecondaryCategories()
    {
        if (!$this->primary_category_id) {
            return collect();
        }
        return SecondaryCategory::where('primary_category_id', $this->primary_category_id)
            ->orderBy('name')
            ->get();
    }

    public function updatedQuantity($value): void
    {
        // Handle empty or invalid input
        if (empty($value) || !is_numeric($value)) {
            $this->quantity = 0;
            $this->resetValidation('quantity');
            $this->updateBatches();
            return;
        }

        $numericValue = (int) $value;
        
        // Allow any positive value or 0
        if ($numericValue < 0) {
            $this->quantity = 0;
            $this->resetValidation('quantity');
            $this->updateBatches();
            return;
        }

        // Set the valid quantity
        $this->quantity = $numericValue;
        $this->resetValidation('quantity');
        $this->updateBatches();
    }

    private function updateBatches(): void
    {
        $currentCount = count($this->batches);

        // Adjust batches based on the new quantity
        if ($this->quantity > $currentCount) {
            for ($i = 0; $i < $this->quantity - $currentCount; $i++) {
                $this->addBatch();
            }
        } elseif ($this->quantity < $currentCount) {
            // Remove excess batches, but ensure we don't have negative array splicing
            array_splice($this->batches, max(0, $this->quantity));
        }
    }

    public function incrementQuantity(): void
    {
        $this->quantity++;
        $this->updateBatches();
    }

    public function decrementQuantity(): void
    {
        if ($this->quantity > 0) {
            $this->quantity--;
            $this->updateBatches();
        }
    }

    public function incrementEstimatedLife(): void
    {
        if ($this->estimated_useful_life === null) {
            $this->estimated_useful_life = 1;
        } else {
            $this->estimated_useful_life++;
        }
        $this->resetValidation('estimated_useful_life');
    }

    public function decrementEstimatedLife(): void
    {
        if ($this->estimated_useful_life !== null && $this->estimated_useful_life > 1) {
            $this->estimated_useful_life--;
            $this->resetValidation('estimated_useful_life');
        }
    }

    public function addBatch(): void
    {
        $this->batches[] = [
            'id' => null,
            'identification_data' => null,
            'components' => [['id' => null, 'component_type' => '', 'brand' => '', 'model' => '', 'serial_number' => '']],
        ];
        $this->dispatch('batch-added');
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



    public function store(): void
    {
        if (!auth()->user()->hasAdminPermission('create_inventory')) {
            abort(403);
        }

        if (empty($this->ics_number)) {
            $this->generateIcsNumber();
        }

        $rules = [
            'ics_number' => ['required', 'integer', 'min:1', Rule::unique('ics_number')],
            'assigned_employee_id' => 'required_unless:creating_new_employee,true|nullable|exists:employees,id',
            'supplier_id' => 'required_unless:creating_new_supplier,true|nullable|exists:suppliers,id',
            'contract_id' => 'required_unless:creating_new_contract,true|nullable|exists:contracts,id',
            'items_catalog_id' => 'required_unless:creating_new_item,true|nullable|exists:items_catalog,id',
            'item_specification_id' => 'nullable|string',
            'quantity' => 'required|integer|min:1', // At least 1 batch is required for submission
            'unit_price' => 'required|numeric|gt:0',
            'unit_of_measure' => 'required|string|max:50',
            'estimated_useful_life' => 'nullable|integer|min:1',
            'date_prepared' => 'required|date',
            'date_accepted' => 'required|date',
            'batches.*.identification_data' => 'nullable|string|max:255',
            'remarks' => 'nullable|string',
        ];

        if ($this->creating_new_supplier) {
            $rules['supplier_search'] = 'required|string|max:255|unique:suppliers,name';
        }
        if ($this->creating_new_contract) {
            // Contract numbers should be unique per supplier, not globally
            $supplierId = $this->supplier_id ?? 'NULL';
            $rules['contract_search'] = [
                'required',
                'string',
                'max:255',
                "unique:contracts,contract_po_ib_number,NULL,id,supplier_id,{$supplierId},deleted_at,NULL"
            ];
        }
        if ($this->creating_new_item) {
            $rules['item_search'] = 'required|string|max:255|unique:items_catalog,name';
            $rules['primary_category_id'] = 'required|exists:primary_categories,id';
            $rules['secondary_category_id'] = 'required|exists:secondary_categories,id';
            // Unit of measure is already in base rules, no need to duplicate
        }
        if ($this->creating_new_specification) {
            $rules['main_item_brand'] = 'nullable|string|max:255';
            $rules['main_item_model'] = 'nullable|string|max:255';
            $rules['detailed_specifications'] = 'nullable|string';
        }
        if ($this->creating_new_employee) {
            // Add validation for new employee fields if you add them
            $rules['employee_search'] = 'required|string|max:255';
        }
        
        if ($this->isDesktopComputer) {
            // Add validation for desktop computer components
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
            'unit_price.gt' => 'The "Unit Cost" must be greater than zero.',
            'quantity.required' => 'At least 1 batch is required.',
            'quantity.min' => 'At least 1 batch is required.',
            'contract_search.unique' => 'This contract number already exists for this supplier. Please use a different contract number.',
        ];

        $this->validate($rules, $messages);

        try {
            DB::transaction(function () {
                if ($this->creating_new_supplier) {
                    $newSupplier = Supplier::create(['name' => $this->supplier_search]);
                    $this->supplier_id = $newSupplier->id;
                }

                if ($this->creating_new_contract) {
                    // Assuming you have more fields for a contract, this would be more complex
                    $newContract = Contract::create([
                        'contract_po_ib_number' => $this->contract_search,
                        'supplier_id' => $this->supplier_id,
                        'po_date' => now(), // Or get this from the form
                    ]);
                    $this->contract_id = $newContract->id;
                }

                if ($this->creating_new_employee) {
                    // This would be more complex, requiring first name, last name, etc.
                    // For now, let's assume a simple case.
                    $newEmployee = Employee::create([
                        'name' => $this->employee_search,
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
                        'code' => 'new-' . time(), // temp code
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

                // Find or create ContractItem
                $final_contract_item = ContractItem::firstOrCreate(
                    ['contract_id' => $this->contract_id, 'item_specification_id' => $spec_id],
                    ['unit_price' => $this->unit_price, 'item_type' => $this->isParItem ? 'PAR' : 'ICS']
                );

                // Extract the code from the descriptive string, e.g., "SPLV - ₱5,000 or less" -> "SPLV"
                $icsTypeCode = strtok($this->ics_type, ' ');

                $icsNumber = IcsNumber::create([
                    'ics_number' => $this->ics_number,
                    'assigned_employee_id' => $this->assigned_employee_id,
                    'contract_item_id' => $final_contract_item->id,
                    'ics_type' => $icsTypeCode,
                    'quantity' => $this->quantity,
                    'estimated_useful_life' => $this->estimated_useful_life ?: null,
                    'remarks' => $this->remarks ?: null,
                    'date_prepared' => $this->date_prepared ? Carbon::parse($this->date_prepared) : null,
                    'date_accepted' => $this->date_accepted ? Carbon::parse($this->date_accepted) : null,
                ]);

                foreach ($this->batches as $batch) {
                    // Here we need to link to the contract_item_id or items_catalog_id
                    $itemBatch = IcsItemBatch::create([
                        'ics_number_id' => $icsNumber->id,
                        'identification_data' => $batch['identification_data'] ?? null,
                    ]);

                    if ($this->isDesktopComputer && isset($batch['components'])) {
                        foreach ($batch['components'] as $component) {
                            if (!empty($component['component_type'])) {
                                ItemComponent::create([
                                    'ics_item_batch_id' => $itemBatch->id,
                                    'component_type' => $component['component_type'],
                                    'brand' => $component['brand'],
                                    'model' => $component['model'],
                                    'serial_number' => $component['serial_number'],
                                ]);
                            }
                        }
                    }
                }

                // Dispatch success toast notification
                ToastService::created($this, 'ICS record');

                session()->flash('highlighted_ics', $icsNumber->id);
                $this->redirectRoute('admin.inventory.ics.index', navigate: true);
            });
        } catch (\Exception $e) {
            // Log the exception for debugging
            \Log::error('Error creating ICS record: ' . $e->getMessage());

            // Dispatch error toast notification
            ToastService::error($this, $e->getMessage());
        }
    }

}; ?>

<div>
<form wire:submit="store" novalidate @keydown.enter.prevent="if (event.target.type !== 'submit') { return false; }">
    <div class="border-b border-stone-200 pb-4 dark:border-stone-700">
        <div class="flex items-center justify-between">
            <!-- Breadcrumbs as Title -->
            <div>
                <flux:breadcrumbs class="text-2xl font-semibold">
                    <flux:breadcrumbs.item :href="route('admin.dashboard')" wire:navigate icon="home" class="text-xl sm:text-2xl font-semibold text-stone-700 dark:text-stone-300" />
                    <flux:breadcrumbs.item class="text-xl sm:text-2xl font-semibold text-stone-500 dark:text-stone-400">Inventory</flux:breadcrumbs.item>
                    <flux:breadcrumbs.item :href="route('admin.inventory.ics.index')" wire:navigate class="text-xl sm:text-2xl font-semibold text-stone-700 dark:text-stone-300">ICS Management</flux:breadcrumbs.item>
                    <flux:breadcrumbs.item class="text-xl sm:text-2xl font-semibold text-stone-900 dark:text-stone-100">Create ICS</flux:breadcrumbs.item>
                </flux:breadcrumbs>
            </div>
            <div class="flex items-center gap-x-4">
                <x-action-message class="me-3" on="ics-created">
                    {{ __('Record saved successfully.') }}
                </x-action-message>
                <flux:button :href="route('admin.inventory.ics.index')" wire:navigate variant="ghost">
                    Cancel
                </flux:button>
                <flux:button type="submit" variant="primary" :disabled="$isParItem" wire:loading.attr="disabled" wire:target="store">
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

                            <!-- Categories Section - Only when creating new item -->
                            @if ($creating_new_item)
                                <div class="border-t border-stone-200 pt-4 dark:border-stone-700">
                                    <h4 class="mb-4 font-medium text-stone-800 dark:text-stone-200">Item Categories</h4>
                                    <div class="grid grid-cols-1 gap-x-6 gap-y-4 sm:grid-cols-2">
                                        <div>
                                            <x-autocomplete 
                                                id="primary_category_search" 
                                                wire:model.live="primary_category_search" 
                                                wire:suggestions="primary_category_suggestions" 
                                                wire:showSuggestions="show_primary_category_suggestions" 
                                                label="Primary Category" 
                                                placeholder="Search primary categories..." 
                                                required 
                                                onFocus="$wire.showAllPrimaryCategories()" 
                                                onSelect="$wire.selectPrimaryCategory" 
                                                error="primary_category_id" />
                                            <x-input-error for="primary_category_id" class="mt-2" />
                                        </div>
                                        <div>
                                            <x-autocomplete 
                                                id="secondary_category_search" 
                                                wire:model.live="secondary_category_search" 
                                                wire:suggestions="secondary_category_suggestions" 
                                                wire:showSuggestions="show_secondary_category_suggestions" 
                                                label="Secondary Category" 
                                                placeholder="Search secondary categories..." 
                                                required 
                                                :disabled="!$this->primary_category_id" 
                                                onFocus="$wire.showAllSecondaryCategories()" 
                                                onSelect="$wire.selectSecondaryCategory" 
                                                error="secondary_category_id" />
                                            <x-input-error for="secondary_category_id" class="mt-2" />
                                        </div>
                                    </div>
                                </div>
                            @endif

                            <!-- Item Specifications Section -->
                            <div class="border-t border-stone-200 pt-4 dark:border-stone-700">
                                <h4 class="mb-4 font-medium text-stone-800 dark:text-stone-200">Item Specifications</h4>
                                @if ($this->items_catalog_id && !$creating_new_item)
                                    <div class="mb-4">
                                        <x-autocomplete id="specification_search" wire:model.live="specification_search" wire:suggestions="specification_suggestions" wire:showSuggestions="show_specification_suggestions" label="Specification Template" placeholder="Search existing specifications..." onFocus="$wire.showAllSpecifications()" onSelect="$wire.selectSpecification" error="item_specification_id" />
                                        @if ($creating_new_specification)
                                            <div class="mt-2 flex items-center rounded-lg bg-green-50 p-2 text-sm text-green-700 dark:bg-green-800/20 dark:text-green-400" role="alert">
                                                <svg class="mr-2 h-4 w-4 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                                                    <path d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5ZM9.5 4a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3ZM12 15H8a1 1 0 0 1 0-2h1v-3H8a1 1 0 0 1 0-2h2a1 1 0 0 1 1 1v4h1a1 1 0 0 1 0 2Z" />
                                                </svg>
                                                <span class="font-medium">New specification will be created upon saving.</span>
                                            </div>
                                        @endif
                                    </div>
                                @endif
                                <div class="grid grid-cols-1 gap-x-6 gap-y-4 sm:grid-cols-2">
                                    <div>
                                        <x-autocomplete id="brand_search" wire:model.live="brand_search" wire:suggestions="brand_suggestions" wire:showSuggestions="show_brand_suggestions" label="Brand" placeholder="e.g., HP, Dell, Samsung" :disabled="!$creating_new_item && !$creating_new_specification" onFocus="$wire.showAllBrands()" onSelect="$wire.selectBrand" @keydown.tab="if (!event.shiftKey) { event.preventDefault(); $wire.dispatch('focus-model'); }" />
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
                                        <x-autocomplete id="model_search" wire:model.live="model_search" wire:suggestions="model_suggestions" wire:showSuggestions="show_model_suggestions" label="Model" placeholder="e.g., ProBook 450 G9, XPS 15" :disabled="!$creating_new_item && !$creating_new_specification" onFocus="$wire.showAllModels()" onSelect="$wire.selectModel" @keydown.tab="if (!event.shiftKey) { event.preventDefault(); $wire.dispatch('focus-detailed-specs'); }" />
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
                                    <flux:textarea id="detailed_specifications" wire:model="detailed_specifications" label="Detailed Specifications" placeholder="Enter detailed specifications here, e.g., RAM, CPU, Storage, etc." :disabled="!$creating_new_item && !$creating_new_specification" rows="3" @keydown.tab="if (!event.shiftKey) { event.preventDefault(); $wire.dispatch('focus-unit-cost'); }" />
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
                                                    // Initialize with any existing value
                                                    if ($wire.unit_price !== null && $wire.unit_price !== undefined) {
                                                        this.rawValue = $wire.unit_price;
                                                        this.updateFormattedValue();
                                                    }
                                                    
                                                    // Watch for external changes to unit_price
                                                    $watch('$wire.unit_price', (value) => {
                                                        if (value !== this.rawValue) {
                                                            this.rawValue = value;
                                                            this.updateFormattedValue();
                                                        }
                                                    });
                                                },
                                                
                                                updateFormattedValue() {
                                                    // Format as currency with commas and 2 decimal places
                                                    this.formattedValue = this.rawValue.toLocaleString('en-US', {
                                                        minimumFractionDigits: 2,
                                                        maximumFractionDigits: 2
                                                    });
                                                },
                                                
                                                updatePrice(event) {
                                                    // Get the current cursor position
                                                    const cursorPos = event.target.selectionStart;
                                                    
                                                    // Get raw value without formatting
                                                    let value = event.target.value.replace(/[^0-9.]/g, '');
                                                    
                                                    // Handle multiple decimal points - keep only the first one
                                                    const decimalPoints = value.match(/\./g);
                                                    if (decimalPoints && decimalPoints.length > 1) {
                                                        const firstDecimalPos = value.indexOf('.');
                                                        value = value.substring(0, firstDecimalPos + 1) + 
                                                               value.substring(firstDecimalPos + 1).replace(/\./g, '');
                                                    }
                                                    
                                                    // Limit to 2 decimal places
                                                    if (value.includes('.')) {
                                                        const parts = value.split('.');
                                                        if (parts[1].length > 2) {
                                                            parts[1] = parts[1].substring(0, 2);
                                                            value = parts.join('.');
                                                        }
                                                    }
                                                    
                                                    // Update raw numeric value
                                                    this.rawValue = parseFloat(value) || 0;
                                                    
                                                    // Update formatted display value
                                                    const oldFormatted = this.formattedValue;
                                                    this.updateFormattedValue();
                                                    
                                                    // Update Livewire property
                                                    $wire.set('unit_price', this.rawValue);
                                                    $wire.updateItemTypeFromPrice(this.rawValue);
                                                    
                                                    // Adjust cursor position after formatting
                                                    this.$nextTick(() => {
                                                        // Count added/removed commas to adjust cursor
                                                        const oldCommas = (oldFormatted.match(/,/g) || []).length;
                                                        const newCommas = (this.formattedValue.match(/,/g) || []).length;
                                                        const commaDiff = newCommas - oldCommas;
                                                        
                                                        // Set adjusted cursor position
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
                                                    @keydown.enter.prevent=""
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

            <!-- Column 2: Supplier, Contract, and Details -->
            <div class="space-y-6">
                <!-- Employee Assignment Section -->
                <div class="overflow-hidden rounded-lg border border-stone-200 bg-white shadow-sm dark:border-stone-700 dark:bg-stone-800">
                    <div class="border-b border-stone-200 px-4 py-3 dark:border-stone-700">
                        <h3 class="font-semibold text-stone-800 dark:text-stone-200">Employee Assignment</h3>
                    </div>
                    <div class="p-4">
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
                                        type="number" 
                                        readonly
                                        tabindex="-1"
                                        class="bg-stone-50 dark:bg-stone-800 text-stone-600 dark:text-stone-400 cursor-not-allowed border-stone-200 dark:border-stone-700" />
                                    <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                                        <x-flux::icon.lock-closed class="h-4 w-4 text-stone-400 dark:text-stone-500" />
                                    </div>
                                </div>
                            </div>
                            <div>
                                <label for="estimated_useful_life_wrapper" class="mb-1 block text-sm font-medium text-stone-700 dark:text-stone-300">
                                    Estimated Useful Life (Years)
                                </label>
                                <div class="flex items-center space-x-2" x-data="{ 
                                    validateNumber(e) {
                                        // Remove non-numeric characters
                                        let value = e.target.value.replace(/[^\d]/g, '');
                                        $wire.set('estimated_useful_life', value ? parseInt(value) : null);
                                    }
                                }">
                                    <!-- Minus Button -->
                                    <flux:button
                                        type="button"
                                        variant="outline"
                                        size="sm"
                                        wire:click="decrementEstimatedLife"
                                        :disabled="$isParItem || ($estimated_useful_life === null || $estimated_useful_life <= 1)"
                                        class="flex-shrink-0 w-10 h-10 p-0 flex items-center justify-center"
                                        @keydown.enter.prevent=""
                                    >
                                        <x-flux::icon.minus class="h-4 w-4" />
                                    </flux:button>
                                    
                                    <!-- Input Field -->
                                    <flux:input 
                                        id="estimated_useful_life_wrapper"
                                        wire:model="estimated_useful_life" 
                                        placeholder="Optional" 
                                        :disabled="$isParItem"
                                        type="number"
                                        min="1"
                                        class="flex-1 text-center"
                                        @input="validateNumber($event)"
                                        @keydown.enter.prevent=""
                                        @keydown.tab="if (!event.shiftKey) { event.preventDefault(); $wire.dispatch('focus-date-prepared'); }" />
                                    
                                    <!-- Plus Button -->
                                    <flux:button
                                        type="button"
                                        variant="outline"
                                        size="sm"
                                        wire:click="incrementEstimatedLife"
                                        :disabled="$isParItem"
                                        class="flex-shrink-0 w-10 h-10 p-0 flex items-center justify-center"
                                        @keydown.enter.prevent=""
                                    >
                                        <x-flux::icon.plus class="h-4 w-4" />
                                    </flux:button>
                                </div>
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
                                    @keydown.enter.prevent=""
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
                                    @keydown.enter.prevent=""
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
                            @keydown.enter="if (!event.ctrlKey && !event.metaKey) { event.stopPropagation(); }"
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
                                <label for="quantity" class="mb-1 block text-sm font-medium text-stone-700 dark:text-stone-300">
                                    Number of Batches
                                </label>
                                <div class="flex items-center space-x-2" x-data="{ 
                                    validateNumber(e) {
                                        // Remove non-numeric characters
                                        let value = e.target.value.replace(/[^\d]/g, '');
                                        // Allow empty value - will be handled by Livewire
                                        e.target.value = value;
                                        $wire.set('quantity', value ? parseInt(value) : 0);
                                    }
                                }">
                                    <!-- Minus Button -->
                                    <flux:button
                                        type="button"
                                        variant="outline"
                                        size="sm"
                                        wire:click="decrementQuantity"
                                        :disabled="$quantity <= 0"
                                        class="flex-shrink-0 w-10 h-10 p-0 flex items-center justify-center"
                                        @keydown.enter.prevent=""
                                    >
                                        <x-flux::icon.minus class="h-4 w-4" />
                                    </flux:button>
                                    
                                    <!-- Input Field -->
                                    <flux:input 
                                        id="quantity"
                                        wire:model.live="quantity" 
                                        type="number"
                                        min="0" 
                                        class="flex-1 text-center"
                                        @input="validateNumber($event)"
                                        @keydown.enter.prevent=""
                                        @keydown.tab="if (!event.shiftKey) { event.preventDefault(); $wire.dispatch('focus-auto-populate'); }"
                                    />
                                    
                                    <!-- Plus Button -->
                                    <flux:button
                                        type="button"
                                        variant="outline"
                                        size="sm"
                                        wire:click="incrementQuantity"
                                        class="flex-shrink-0 w-10 h-10 p-0 flex items-center justify-center"
                                        @keydown.enter.prevent=""
                                    >
                                        <x-flux::icon.plus class="h-4 w-4" />
                                    </flux:button>
                                </div>
                                @error('quantity')
                                    <div class="mt-2 flex items-center text-sm text-red-600 dark:text-red-400">
                                        <svg class="mr-2 h-5 w-5 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.21 3.03-1.742 3.03H4.42c-1.532 0-2.492-1.696-1.742-3.03l5.58-9.92zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" />
                                        </svg>
                                        <span>{{ $message }}</span>
                                    </div>
                                @enderror
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
                                                @if ($quantity > 0)
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
                                                        @focus="if ($el.value === 'Serial Number: ') { $el.select(); }"
                                                        @keydown.enter.prevent="" />
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
                                                            <div wire:key="component-{{ $batchIndex }}-{{ $componentIndex }}" class="relative rounded-lg border border-stone-200 bg-white p-4 dark:border-stone-600 dark:bg-stone-700">
                                                                <div class="flex justify-between items-center mb-2">
                                                                    <h5 class="font-medium text-stone-800 dark:text-stone-200">
                                                                        Component #{{ $loop->iteration }}
                                                                    </h5>
                                                                    @if (count($batch['components']) > 1)
                                                                        <flux:button type="button" variant="danger" size="xs" wire:click.prevent="removeComponent({{ $batchIndex }}, {{ $componentIndex }})">
                                                                            <x-flux::icon.trash class="h-4 w-4" />
                                                                        </flux:button>
                                                                    @endif
                                                                </div>
                                                                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                                                    <flux:input wire:model="batches.{{ $batchIndex }}.components.{{ $componentIndex }}.component_type" label="Component Type" placeholder="e.g., Monitor, Casing, UPS" required tabindex="{{ 11 + ((int) $batchIndex * 100) + ((int) $componentIndex * 4) + 1 }}" @keydown.enter.prevent="" />
                                                                    <x-input-error :for="'batches.' . $batchIndex . '.components.' . $componentIndex . '.component_type'" class="mt-2" />
                                                                    <flux:input wire:model="batches.{{ $batchIndex }}.components.{{ $componentIndex }}.serial_number" label="Serial Number" tabindex="{{ 11 + ((int) $batchIndex * 100) + ((int) $componentIndex * 4) + 2 }}" @keydown.enter.prevent="" />
                                                                    <x-input-error :for="'batches.' . $batchIndex . '.components.' . $componentIndex . '.serial_number'" class="mt-2" />
                                                                    <flux:input wire:model="batches.{{ $batchIndex }}.components.{{ $componentIndex }}.brand" label="Brand" tabindex="{{ 11 + ((int) $batchIndex * 100) + ((int) $componentIndex * 4) + 3 }}" @keydown.enter.prevent="" />
                                                                    <x-input-error :for="'batches.' . $batchIndex . '.components.' . $componentIndex . '.brand'" class="mt-2" />
                                                                    <flux:input wire:model="batches.{{ $batchIndex }}.components.{{ $componentIndex }}.model" label="Model" tabindex="{{ 11 + ((int) $batchIndex * 100) + ((int) $componentIndex * 4) + 4 }}" @keydown.enter.prevent="" />
                                                                    <x-input-error :for="'batches.' . $batchIndex . '.components.' . $componentIndex . '.model'" class="mt-2" />
                                                                </div>
                                                            </div>
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
        
        // Automatically focus Supplier field on initial load
        focusOn('supplier_search');
        
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
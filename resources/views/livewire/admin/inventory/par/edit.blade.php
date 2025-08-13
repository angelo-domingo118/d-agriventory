<?php

use App\Models\Contract;
use App\Models\ContractItem;
use App\Models\Employee;
use App\Models\ItemsCatalog;
use App\Models\ItemSpecification;
use App\Models\SecondaryCategory;
use App\Models\PrimaryCategory;
use App\Models\Supplier;
use App\Models\ParNumber;
use App\Models\ParItemBatch;
use App\Models\ParTransfer;
use App\Models\ItemComponent;
use App\Services\ToastService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\{Computed, On, Layout};
use Livewire\Volt\Component;

new #[Layout('components.layouts.app')] class extends Component {
    public ParNumber $par;

    // Form state
    public string $par_number = '';
    public ?int $supplier_id = null;
    public ?int $contract_id = null;
    public ?int $items_catalog_id = null;
    public ?string $item_specification_id = null;
    public ?int $contract_item_id = null;
    public ?int $assigned_employee_id = null;
    public int $quantity = 1;
    public int $quantity_per_batch = 1;
    public ?string $date_prepared = null;
    public ?string $date_accepted = null;

    public string $area_code = '';
    public string $building_code = '';
    public string $account_code = '';
    public string $inventory_code = '';
    public string $remarks = '';

    // New fields for main item
    public ?string $main_item_brand = null;
    public ?string $main_item_model = null;
    public ?string $detailed_specifications = null;
    public ?string $main_item_serial_number = null;

    // Display only property
    public ?float $unit_price = null;
    public bool $isIcsItem = false;
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

    // Delete confirmation state
    public bool $confirmingDeletion = false;

    // Original data for reset functionality
    private array $originalData = [];
    private array $originalBatches = [];

    public function mount(ParNumber $par): void
    {
        if (!auth()->user()->hasAdminPermission('edit_inventory')) {
            abort(403);
        }

        $this->par = $par->load([
            'assignedEmployee.division',
            'contractItem.itemSpecification.itemCatalog.secondaryCategory.primaryCategory',
            'contractItem.contract.supplier',
            'itemBatches.itemComponents'
        ]);
        
        $this->loadOriginalData();
        $this->resetCreationFlags();
    }

    private function loadOriginalData(): void
    {
        // Load PAR details
        $this->par_number = $this->par->par_number;
        $this->assigned_employee_id = $this->par->assigned_employee_id;
        $this->quantity = $this->par->quantity;
        $this->quantity_per_batch = $this->par->quantity_per_batch ?? 1;
        $this->date_prepared = $this->par->date_prepared ? $this->par->date_prepared->format('m/d/Y') : null;
        $this->date_accepted = $this->par->date_accepted ? $this->par->date_accepted->format('m/d/Y') : null;

        $this->area_code = $this->par->area_code ?? '';
        $this->building_code = $this->par->building_code ?? '';
        $this->account_code = $this->par->account_code ?? '';
        $this->inventory_code = $this->par->inventory_code ?? '';
        $this->remarks = $this->par->remarks ?? '';

        // Load contract item details
        if ($this->par->contractItem) {
            $contractItem = $this->par->contractItem;
            $this->contract_item_id = $contractItem->id;
            $this->unit_price = (float) $contractItem->unit_price;

            // Load contract details
            if ($contractItem->contract) {
                $this->contract_id = $contractItem->contract->id;
                $this->contract_search = $contractItem->contract->contract_po_ib_number;
                $this->selected_contract_name = $contractItem->contract->contract_po_ib_number;

                // Load supplier details
                if ($contractItem->contract->supplier) {
                    $this->supplier_id = $contractItem->contract->supplier->id;
                    $this->supplier_search = $contractItem->contract->supplier->name;
                    $this->selected_supplier_name = $contractItem->contract->supplier->name;
                }
            }

            // Load item specification details
            if ($contractItem->itemSpecification) {
                $specification = $contractItem->itemSpecification;
                $this->item_specification_id = $specification->id;
                $this->main_item_brand = $specification->brand;
                $this->main_item_model = $specification->model;
                $this->detailed_specifications = $specification->detailed_specifications;
                
                // Set brand and model search fields
                $this->brand_search = $specification->brand ?? '';
                $this->selected_brand = $specification->brand ?? '';
                $this->model_search = $specification->model ?? '';
                $this->selected_model = $specification->model ?? '';

                // Load items catalog details
                if ($specification->itemCatalog) {
                    $itemCatalog = $specification->itemCatalog;
                    $this->items_catalog_id = $itemCatalog->id;
                    $this->item_search = $itemCatalog->name;
                    $this->selected_item_name = $itemCatalog->name;
                    $this->unit_search = $itemCatalog->unit ?? '';
                    $this->selected_unit = $itemCatalog->unit ?? '';
                }
            }
        }

        // Load employee details
        if ($this->par->assignedEmployee) {
            $this->employee_search = $this->par->assignedEmployee->name;
            $this->selected_employee_name = $this->par->assignedEmployee->name;
        }

        // Load batches
        $this->batches = [];
        foreach ($this->par->itemBatches as $batch) {
            $batchData = [
                'id' => $batch->id,
                'identification_data' => $batch->identification_data,
                'components' => []
            ];

            // Load components if this is a desktop computer
            foreach ($batch->itemComponents as $component) {
                $batchData['components'][] = [
                    'id' => $component->id,
                    'component_type' => $component->component_type,
                    'brand' => $component->brand,
                    'model' => $component->model,
                    'serial_number' => $component->serial_number,
                ];
            }

            // If no components but it's a desktop computer, add empty component
            if (empty($batchData['components']) && $this->isDesktopComputer) {
                $batchData['components'][] = [
                    'id' => null,
                    'component_type' => '',
                    'brand' => '',
                    'model' => '',
                    'serial_number' => '',
                ];
            }

            $this->batches[] = $batchData;
        }

        $this->updateItemType();
        $this->storeOriginalData();
    }

    private function storeOriginalData(): void
    {
        $this->originalData = [
            'par_number' => $this->par_number,
            'supplier_id' => $this->supplier_id,
            'contract_id' => $this->contract_id,
            'items_catalog_id' => $this->items_catalog_id,
            'item_specification_id' => $this->item_specification_id,
            'contract_item_id' => $this->contract_item_id,
            'assigned_employee_id' => $this->assigned_employee_id,
            'quantity' => $this->quantity,
            'quantity_per_batch' => $this->quantity_per_batch,
            'unit_price' => $this->unit_price,
            'date_prepared' => $this->date_prepared,
            'date_accepted' => $this->date_accepted,

            'area_code' => $this->area_code,
            'building_code' => $this->building_code,
            'account_code' => $this->account_code,
            'inventory_code' => $this->inventory_code,
            'remarks' => $this->remarks,
        ];
        
        $this->originalBatches = $this->batches;
    }

    private function resetCreationFlags(): void
    {
        $this->creating_new_supplier = false;
        $this->creating_new_contract = false;
        $this->creating_new_item = false;
        $this->creating_new_employee = false;
        $this->creating_new_unit = false;
        $this->creating_new_brand = false;
        $this->creating_new_model = false;
    }

    public function resetForm(): void
    {
        // Reset to original data
        foreach ($this->originalData as $key => $value) {
            $this->$key = $value;
        }
        
        $this->batches = $this->originalBatches;
        $this->resetCreationFlags();
        
        // Clear all autocomplete suggestions
        $this->supplier_suggestions = [];
        $this->show_supplier_suggestions = false;
        $this->contract_suggestions = [];
        $this->show_contract_suggestions = false;
        $this->item_suggestions = [];
        $this->show_item_suggestions = false;
        $this->employee_suggestions = [];
        $this->show_employee_suggestions = false;
        $this->unit_suggestions = [];
        $this->show_unit_suggestions = false;
        $this->brand_suggestions = [];
        $this->show_brand_suggestions = false;
        $this->model_suggestions = [];
        $this->show_model_suggestions = false;

        ToastService::info($this, 'Form reset to original values');
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
        // Guard against null or invalid data
        if (!is_string($query)) {
            $query = '';
        }

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
        }

        $this->show_supplier_suggestions = false;
        $this->resetContractData();
    }

    // Contract autocomplete methods
    public function updatedContractSearch($value): void
    {
        $this->searchContracts($value);
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
        // Guard against null or invalid data
        if (!is_string($query)) {
            $query = '';
        }

        if (!$this->supplier_id) {
            $this->contract_suggestions = [];
            $this->show_contract_suggestions = false;
            return;
        }

        if (strlen(trim($query)) === 0) {
            $contracts = Contract::where('supplier_id', $this->supplier_id)->orderBy('contract_po_ib_number')->get();
        } else {
            $contracts = Contract::where('supplier_id', $this->supplier_id)
                ->whereRaw('LOWER(contract_po_ib_number) LIKE LOWER(?)', ['%' . $query . '%'])
                ->orderBy('contract_po_ib_number')
                ->get();
        }

        $this->contract_suggestions = $contracts->map(function ($contract) {
            return [
                'id' => $contract->id,
                'name' => $contract->contract_po_ib_number,
                'type' => 'existing'
            ];
        })->toArray();

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
        }

        $this->show_contract_suggestions = false;
        $this->resetContractItemOnly();
    }

    // Item catalog autocomplete methods
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
        // Guard against null or invalid data
        if (!is_string($query)) {
            $query = '';
        }

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

        $this->show_item_suggestions = count($this->item_suggestions) > 0;
    }

    public function selectItem($itemData): void
    {
        // Guard against null or invalid data
        if (!$itemData || !is_array($itemData) || !isset($itemData['type'])) {
            return;
        }

        if ($itemData['type'] === 'existing') {
            $this->items_catalog_id = $itemData['id'];
            $this->item_search = $itemData['name'];
            $this->selected_item_name = $itemData['name'];
            
            // Set unit of measure from the selected item
            $itemCatalog = ItemsCatalog::find($itemData['id']);
            if ($itemCatalog) {
                $this->unit_search = $itemCatalog->unit;
                $this->selected_unit = $itemCatalog->unit;
            }
            
            $this->creating_new_item = false;
            $this->creating_new_unit = false;
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
        // Guard against null or invalid data
        if (!is_string($query)) {
            $query = '';
        }

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
        }

        $this->show_employee_suggestions = false;
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
        // Guard against null or invalid data
        if (!is_string($query)) {
            $query = '';
        }

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

        $this->show_unit_suggestions = count($this->unit_suggestions) > 0;
    }

    public function selectUnit($unitData): void
    {
        // Guard against null or invalid data
        if (!$unitData || !is_array($unitData) || !isset($unitData['type'])) {
            return;
        }

        if ($unitData['type'] === 'existing') {
            $this->unit_search = $unitData['name'];
            $this->selected_unit = $unitData['name'];
            $this->creating_new_unit = false;
        }

        $this->show_unit_suggestions = false;
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
        // Guard against null or invalid data
        if (!is_string($query)) {
            $query = '';
        }

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
        }

        $this->show_brand_suggestions = false;
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
        // Guard against null or invalid data
        if (!is_string($query)) {
            $query = '';
        }

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
        }

        $this->show_model_suggestions = false;
    }

    // Helper methods
    private function resetContractData(): void
    {
        $this->contract_id = null;
        $this->contract_search = '';
        $this->selected_contract_name = null;
        $this->contract_suggestions = [];
        $this->show_contract_suggestions = false;
        $this->resetContractItemOnly();
    }

    private function resetContractDataOnly(): void
    {
        $this->contract_id = null;
        $this->contract_search = '';
        $this->selected_contract_name = null;
        $this->creating_new_contract = false;
        $this->contract_suggestions = [];
        $this->show_contract_suggestions = false;
    }

    private function resetContractItemOnly(): void
    {
        $this->contract_item_id = null;
        $this->unit_price = null;
        $this->updateItemType();
    }

    private function resetItemData(): void
    {
        $this->items_catalog_id = null;
        $this->item_specification_id = null;
        $this->item_search = '';
        $this->selected_item_name = null;
        $this->creating_new_item = false;
        $this->item_suggestions = [];
        $this->show_item_suggestions = false;

        $this->isDesktopComputer = false;
        $this->isIcsItem = false;
        $this->main_item_brand = null;
        $this->main_item_model = null;
        $this->detailed_specifications = null;
        
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

        $this->resetContractItemOnly();
    }

    private function updateItemType(): void
    {
        $this->isDesktopComputer = str_contains(strtoupper($this->selected_item_name ?? ''), 'DESKTOP COMPUTER');
        
        // Check if unit price is under ₱50,000 (should be ICS instead)
        if ($this->unit_price > 0 && $this->unit_price < 50000) {
            $this->isIcsItem = true;
        } else {
            $this->isIcsItem = false;
        }
    }

    // Custom method that can be called from Alpine
    public function updateItemTypeFromPrice($price): void
    {
        $this->unit_price = $price;
        $this->updateItemType();
    }

    #[Computed]
    public function selectedContractItem()
    {
        if ($this->contract_id && $this->item_specification_id && is_numeric($this->item_specification_id)) {
            return ContractItem::where('contract_id', $this->contract_id)
                ->where('item_specification_id', $this->item_specification_id)
                ->first();
        }
        return null;
    }

    public function updatedContractItemId($value): void
    {
        $contractItem = ContractItem::find($value);
        $this->unit_price = $contractItem ? (float) $contractItem->unit_price : null;
        $this->updateItemType();
    }

    public function updatedUnitPrice(): void
    {
        $this->updateItemType();
    }

    #[Computed]
    public function contractItems()
    {
        if (!$this->contract_id || !$this->items_catalog_id) {
            return collect();
        }
        
        return ContractItem::where('contract_id', $this->contract_id)
            ->whereHas('itemSpecification', function ($query) {
                $query->where('item_catalog_id', $this->items_catalog_id);
            })
            ->with('itemSpecification')
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

    public function updateBatches(): void
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

    public function transferItem(): void
    {
        $this->validate([
            'assigned_employee_id' => 'required|exists:employees,id'
        ]);

        if ($this->assigned_employee_id === $this->par->assigned_employee_id) {
            ToastService::error($this, 'Cannot transfer to the same employee who already has this item.');
            return;
        }

        DB::transaction(function () {
            // Create transfer record
            ParTransfer::create([
                'par_number_id' => $this->par->id,
                'from_employee_id' => $this->par->assigned_employee_id,
                'to_employee_id' => $this->assigned_employee_id,
                'transfer_date' => now(),
                'remarks' => $this->remarks ?? 'Transferred via PAR edit form',
                'transferred_by' => auth()->id(),
            ]);

            // Update PAR with new employee
            $this->par->update([
                'assigned_employee_id' => $this->assigned_employee_id,
            ]);

            // Update search field display
            $newEmployee = Employee::find($this->assigned_employee_id);
            if ($newEmployee) {
                $this->employee_search = $newEmployee->name;
                $this->selected_employee_name = $newEmployee->name;
            }
        });

        ToastService::success($this, 'Item successfully transferred to ' . ($newEmployee->name ?? 'new employee'));
        $this->loadOriginalData(); // Refresh original data after transfer
    }

    public function update(): void
    {
        if (!auth()->user()->hasAdminPermission('edit_inventory')) {
            abort(403);
        }

        $rules = [
            'par_number' => ['required', 'string', 'max:255', Rule::unique('par_number', 'par_number')->ignore($this->par->id)],
            'assigned_employee_id' => 'required|exists:employees,id',
            'supplier_id' => 'required|exists:suppliers,id',
            'contract_id' => 'required|exists:contracts,id',
            'items_catalog_id' => 'required|exists:items_catalog,id',
            'item_specification_id' => 'required|integer|exists:item_specifications,id',
            'contract_item_id' => 'required|exists:contract_items,id',
            'quantity' => 'required|integer|min:1',
            'quantity_per_batch' => 'required|integer|min:1',
            'unit_price' => 'required|numeric|gt:0',
            'date_prepared' => 'nullable|date',
            'date_accepted' => 'nullable|date',

            'area_code' => 'nullable|string|max:255',
            'building_code' => 'nullable|string|max:255',
            'account_code' => 'nullable|string|max:255',
            'inventory_code' => 'nullable|string|max:255',
            'remarks' => 'nullable|string',
            'batches.*.identification_data' => 'nullable|string|max:255',
        ];

        if ($this->isDesktopComputer) {
            // Add validation for desktop computer components
            $rules['batches.*.components.*.component_type'] = 'required|string|max:255';
            $rules['batches.*.components.*.brand'] = 'nullable|string|max:255';
            $rules['batches.*.components.*.model'] = 'nullable|string|max:255';
            $rules['batches.*.components.*.serial_number'] = 'nullable|string|max:255';
        }

        $messages = [
            'assigned_employee_id.required' => 'Please assign this item to an employee.',
            'date_prepared.required' => 'The "Date Prepared" field is required.',
            'date_accepted.required' => 'The "Date Accepted" field is required.',

            'unit_price.required' => 'The "Unit Cost" field is required.',
            'unit_price.gt' => 'The "Unit Cost" must be greater than zero.',
            'quantity.required' => 'At least 1 batch is required.',
            'quantity.min' => 'At least 1 batch is required.',
        ];

        $this->validate($rules, $messages);

        try {
            DB::transaction(function () {
                // Update PAR main record
                $this->par->update([
                    'par_number' => $this->par_number,
                    'assigned_employee_id' => $this->assigned_employee_id,
                    'quantity' => $this->quantity,
                    'quantity_per_batch' => $this->quantity_per_batch,
                    'date_prepared' => $this->date_prepared ? Carbon::parse($this->date_prepared) : null,
                    'date_accepted' => $this->date_accepted ? Carbon::parse($this->date_accepted) : null,

                    'area_code' => $this->area_code,
                    'building_code' => $this->building_code,
                    'account_code' => $this->account_code,
                    'inventory_code' => $this->inventory_code,
                    'remarks' => $this->remarks,
                ]);

                // Update contract item
                if ($this->contract_item_id) {
                    $contractItem = ContractItem::find($this->contract_item_id);
                    if ($contractItem) {
                        $contractItem->update([
                            'unit_price' => $this->unit_price,
                            'item_type' => 'PAR'
                        ]);
                    }
                }
            
            // Update batches
                $existingBatchIds = collect($this->batches)->pluck('id')->filter();
            $this->par->itemBatches()->whereNotIn('id', $existingBatchIds)->delete();

                foreach ($this->batches as $batchData) {
                if ($batchData['id']) {
                    // Update existing batch
                        $batch = ParItemBatch::find($batchData['id']);
                        if ($batch) {
                            $batch->update([
                        'identification_data' => $batchData['identification_data'],
                    ]);

                            // Handle components for desktop computers
                            if ($this->isDesktopComputer && isset($batchData['components'])) {
                                // Delete existing components
                                $batch->itemComponents()->delete();

                                // Add new components
                                foreach ($batchData['components'] as $componentData) {
                                    if (!empty($componentData['component_type'])) {
                                        ItemComponent::create([
                                            'par_item_batch_id' => $batch->id,
                                            'component_type' => $componentData['component_type'],
                                            'brand' => $componentData['brand'],
                                            'model' => $componentData['model'],
                                            'serial_number' => $componentData['serial_number'],
                                        ]);
                                    }
                                }
                            }
                        }
                } else {
                    // Create new batch
                        $batch = ParItemBatch::create([
                        'par_number_id' => $this->par->id,
                        'identification_data' => $batchData['identification_data'],
                    ]);

                        // Handle components for desktop computers
                        if ($this->isDesktopComputer && isset($batchData['components'])) {
                            foreach ($batchData['components'] as $componentData) {
                                if (!empty($componentData['component_type'])) {
                                    ItemComponent::create([
                                        'par_item_batch_id' => $batch->id,
                                        'component_type' => $componentData['component_type'],
                                        'brand' => $componentData['brand'],
                                        'model' => $componentData['model'],
                                        'serial_number' => $componentData['serial_number'],
                                    ]);
                                }
                            }
                        }
                    }
                }
            });

            // Dispatch success toast notification
            ToastService::updated($this, 'PAR record');

            session()->flash('highlighted_par', $this->par->id);
            $this->redirectRoute('admin.inventory.par.index', navigate: true);

        } catch (\Exception $e) {
            // Log the exception for debugging
            \Log::error('Error updating PAR record: ' . $e->getMessage());

            // Dispatch error toast notification
            ToastService::error($this, $e->getMessage());
        }
    }

    public function confirmDelete(): void
    {        
        $this->confirmingDeletion = true;
        $this->dispatch('open-delete-modal');
    }

    public function cancelDelete(): void
    {
        $this->confirmingDeletion = false;
        $this->dispatch('close-delete-modal');
    }

    public function destroy(): void
    {
        if (!auth()->user()->hasAdminPermission('delete_inventory')) {
            abort(403);
        }

        try {
            DB::transaction(function () {
                // Delete all related components first
                foreach ($this->par->itemBatches as $batch) {
                    $batch->itemComponents()->delete();
                }
                
                // Delete all batches
                $this->par->itemBatches()->delete();
                
                // Delete transfers
                $this->par->transfers()->delete();
                
                // Finally delete the PAR record
                $this->par->delete();
            });

            ToastService::deleted($this, 'PAR record');
            $this->redirectRoute('admin.inventory.par.index', navigate: true);

        } catch (\Exception $e) {
            \Log::error('Error deleting PAR record: ' . $e->getMessage());
            ToastService::error($this, 'Failed to delete PAR record: ' . $e->getMessage());
            
            $this->cancelDelete();
        }
    }

    #[On('call-delete')]
    public function handleDelete(): void
    {
        $this->destroy();
    }

    #[On('call-cancel-delete')]
    public function handleCancelDelete(): void
    {
        $this->cancelDelete();
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
                            <flux:input wire:model="par_number" label="PAR Number" required />
                        </div>

                        <div class="sm:col-span-3">
                            <flux:select wire:model="employee_id" label="Assigned Employee" required>
                                <option value="">Select employee...</option>
                                @foreach($allEmployees as $employee)
                                    <option value="{{ $employee->id }}">{{ $employee->name }}</option>
                                @endforeach
                            </flux:select>
                        </div>

                        <div class="sm:col-span-3">
                            <flux:input wire:model="quantity" type="number" label="Quantity" min="1" required />
                        </div>

                        <div class="sm:col-span-3">
                            <flux:input wire:model="inventory_code" label="Inventory Code" />
                        </div>

                        <div class="sm:col-span-2">
                            <flux:input wire:model="date_prepared" type="date" label="Date Prepared" />
                        </div>

                        <div class="sm:col-span-2">
                            <flux:input wire:model="date_accepted" type="date" label="Date Accepted" />
                        </div>

                        <div class="sm:col-span-2">

                        </div>
                        
                        <div class="sm:col-span-2">
                            <flux:input wire:model="area_code" label="Area Code" />
                        </div>

                        <div class="sm:col-span-2">
                            <flux:input wire:model="building_code" label="Building Code" />
                        </div>

                        <div class="sm:col-span-2">
                            <flux:input wire:model="account_code" label="Account Code" />
                        </div>

                        <div class="col-span-full">
                            <flux:textarea wire:model="remarks" label="Remarks" rows="3" />
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Identification Data Batches --}}
        <div class="lg:col-span-4">
            <div class="rounded-lg border border-stone-200 bg-white dark:border-stone-700 dark:bg-stone-800">
                <div class="border-b border-stone-200 px-4 py-5 dark:border-stone-700 sm:px-6">
                    <div class="flex items-center justify-between">
                        <h3 class="text-base font-semibold leading-6 text-stone-900 dark:text-stone-50">
                            Identification Data
                        </h3>
                        <flux:button wire:click="addBatch" variant="outline" size="sm">
                            Add Batch
                        </flux:button>
                    </div>
                    <p class="mt-1 text-sm text-stone-500 dark:text-stone-400">
                        Add serial numbers, asset tags, or other identification data for individual items.
                    </p>
                </div>
                
                @if(!empty($batches))
                    <div class="p-6 space-y-4">
                        @foreach($batches as $index => $batch)
                            <div wire:key="batch-{{ $batch['id'] ?? 'new-'.$index }}" class="flex items-start gap-2">
                                <div class="flex-1">
                                    <flux:textarea 
                                        wire:model="batches.{{ $index }}.identification_data" 
                                        placeholder="Enter serial numbers, asset tags, or other identification data..."
                                        rows="2"
                                    />
                                </div>
                                <flux:button 
                                    wire:click="removeBatch({{ $index }})" 
                                    variant="danger" 
                                    size="sm"
                                    class="mt-1"
                                >
                                    Remove
                                </flux:button>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="p-6">
                        <p class="text-sm text-stone-500 dark:text-stone-400">
                            No identification data batches added yet. Click "Add Batch" to start.
                        </p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</form> 
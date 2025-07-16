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

new #[Layout('components.layouts.app')] class extends Component {
    // Form state
    public string $ics_number = '';
    public ?int $supplier_id = null;
    public ?int $contract_id = null;
    public ?int $items_catalog_id = null; // Changed from item_specification_id
    public ?string $item_specification_id = null; // Can now be "new" or an integer ID
    public ?int $contract_item_id = null;
    public ?int $assigned_employee_id = null;
    public string $ics_type = 'SPLV';
    public int $quantity = 1;
    public ?int $estimated_useful_life = null;
    public ?string $date_prepared = null;
    public ?string $date_accepted = null;
    public string $remarks = '';

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

    // Display only property
    public ?float $unit_price = 0.0;
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

    public Collection $allPrimaryCategories;
    public Collection $allSecondaryCategories;

    public function mount(): void
    {
        if (!auth()->user()->hasAdminPermission('create_inventory')) {
            abort(403);
        }
        
        $this->generateIcsNumber();
        
        // Pre-load data for dropdowns
        $this->allPrimaryCategories = PrimaryCategory::orderBy('name')->get();
        $this->allSecondaryCategories = SecondaryCategory::orderBy('name')->get();

        // Start with one batch
        $this->updatedQuantity($this->quantity);
        
        // Set default unit of measure - empty string initially
        $this->unit_of_measure = '';
        $this->unit_search = '';
        $this->selected_unit = '';
    }

    public function generateIcsNumber(): void
    {
        $currentYear = now()->format('Y');
        $currentMonth = now()->format('m');
        $currentDay = now()->format('d');
        
        // Format: YYYYMMDD## (e.g., 2024010101 for January 1, 2024, first ICS of the day)
        $datePrefix = $currentYear . $currentMonth . $currentDay;
        
        $lastIcs = IcsNumber::where('ics_number', 'like', $datePrefix . '%')
            ->orderBy('ics_number', 'desc')
            ->first();

        if ($lastIcs) {
            $lastSequence = (int) substr($lastIcs->ics_number, -2);
            $newSequence = str_pad($lastSequence + 1, 2, '0', STR_PAD_LEFT);
        } else {
            $newSequence = '01';
        }

        $this->ics_number = $datePrefix . $newSequence;
    }

    public function validateIcsNumber($value): void
    {
        if (!preg_match('/^\d{10}$/', $value)) {
            $this->addError('ics_number', 'ICS number must be exactly 10 digits (YYYYMMDD##).');
            return;
        }

        // Check if it already exists
        if (IcsNumber::where('ics_number', $value)->exists()) {
            $this->addError('ics_number', 'This ICS number already exists.');
            return;
        }

        // Validate date portion
        $year = substr($value, 0, 4);
        $month = substr($value, 4, 2);
        $day = substr($value, 6, 2);
        
        if (!checkdate($month, $day, $year)) {
            $this->addError('ics_number', 'Invalid date in ICS number (YYYYMMDD##).');
            return;
        }

        // Check if it's not lower than the highest existing number for the same date
        $datePrefix = substr($value, 0, 8);
        $sequence = (int) substr($value, 8, 2);
        
        $highestInDay = IcsNumber::where('ics_number', 'like', $datePrefix . '%')
            ->orderBy('ics_number', 'desc')
            ->first();

        if ($highestInDay) {
            $highestSequence = (int) substr($highestInDay->ics_number, 8, 2);
            if ($sequence <= $highestSequence) {
                $this->addError('ics_number', "ICS number must be higher than {$highestInDay->ics_number} for date {$year}-{$month}-{$day}.");
            }
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
        // Reset all item-related properties when a new catalog item is selected
        $this->resetItemData();
        
        if ($itemData['type'] === 'existing') {
            $this->items_catalog_id = $itemData['id'];
            $this->item_search = $itemData['name'];
            $this->selected_item_name = $itemData['name'];
            
            // Set unit of measure from the selected item
            $this->unit_of_measure = $itemData['unit'];
            $this->unit_search = $itemData['unit'];
            $this->selected_unit = $itemData['unit'];
            
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
                'description' => $spec->detailed_specifications
            ];
        })->toArray();

        $exactExists = collect($this->specification_suggestions)->contains(function ($spec) use ($query) {
            return strtolower($spec['name']) === strtolower($query);
        });

        if (!$exactExists && strlen(trim($query)) >= 2) {
            array_unshift($this->specification_suggestions, [
                'id' => 'new',
                'name' => "Create new specification: \"{$query}\"",
                'type' => 'new'
            ]);
        }
        
        $this->show_specification_suggestions = count($this->specification_suggestions) > 0;
    }

    public function selectSpecification($specData): void
    {
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

        } elseif ($specData['type'] === 'new') {
            preg_match('/"([^"]+)"/', $specData['name'], $matches);
            $newSpecName = $matches[1] ?? $this->specification_search;
            
            $this->item_specification_id = 'new';
            $this->specification_search = $newSpecName;
            $this->selected_specification_name = $newSpecName . ' (new)';
            $this->creating_new_specification = true;
            
            // Reset fields for new spec entry
            $this->main_item_brand = null;
            $this->main_item_model = null;
            $this->detailed_specifications = null;
            $this->unit_price = 0.0;
            $this->contract_item_id = null;
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
        $this->primary_category_id = null;
        $this->secondary_category_id = null;
    }

    private function updateItemType(): void
    {
        $this->isDesktopComputer = str_contains(strtoupper($this->selected_item_name ?? ''), 'DESKTOP COMPUTER');
        
        // Format unit price and set ICS type based on value
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

        } elseif ($value) {
            $this->creating_new_specification = false;
            $spec = ItemSpecification::find($value);
            if ($spec) {
                $this->main_item_brand = $spec->brand;
                $this->main_item_model = $spec->model;
                $this->detailed_specifications = $spec->detailed_specifications;

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
        }

        $this->updateItemType();
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
            'id' => null,
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

    private function createNewItem(): int
    {
        $this->validate([
            'item_search' => 'required|string|max:255',
            'primary_category_id' => 'required|exists:primary_categories,id',
            'secondary_category_id' => 'required|exists:secondary_categories,id',
            'unit_price' => 'required|numeric|min:0',
        ]);

        // This is complex. When creating a new item, we need a catalog entry,
        // a specification, and a contract_item.
        // For simplicity, this will just create the catalog item.
        // The store logic will need to handle creating the specification and contract item.
        $newItem = ItemsCatalog::create([
            'name' => $this->item_search,
            'secondary_category_id' => $this->secondary_category_id,
            'unit' => $this->unit_of_measure, // Use the selected unit
            'code' => 'new-' . time(), // temp code
        ]);

        return $newItem->id;
    }

    public function store(): void
    {
        if (!auth()->user()->hasAdminPermission('create_inventory')) {
            abort(403);
        }

        $this->validateIcsNumber($this->ics_number);

        $rules = [
            'ics_number' => ['required', 'string', 'size:10', Rule::unique('ics_numbers')],
            'assigned_employee_id' => 'required_without:creating_new_employee|nullable|exists:employees,id',
            'supplier_id' => 'required_without:creating_new_supplier|nullable|exists:suppliers,id',
            'contract_id' => 'required_without:creating_new_contract|nullable|exists:contracts,id',
            'items_catalog_id' => 'required_without:creating_new_item|nullable|exists:items_catalog,id',
            'item_specification_id' => 'required|string',
            'quantity' => 'required|integer|min:1',
            'estimated_useful_life' => 'nullable|integer|min:1',
            'date_prepared' => 'nullable|date',
        ];

        if ($this->creating_new_supplier) {
            $rules['supplier_search'] = 'required|string|max:255|unique:suppliers,name';
        }
        if ($this->creating_new_contract) {
            $rules['contract_search'] = 'required|string|max:255|unique:contracts,contract_po_ib_number';
        }
        if ($this->creating_new_item) {
            $rules['item_search'] = 'required|string|max:255|unique:items_catalog,name';
            $rules['primary_category_id'] = 'required|exists:primary_categories,id';
            $rules['secondary_category_id'] = 'required|exists:secondary_categories,id';
            $rules['unit_of_measure'] = 'required|string|max:50';
        }
        if ($this->creating_new_specification) {
            $rules['main_item_brand'] = 'nullable|string|max:255';
            $rules['main_item_model'] = 'nullable|string|max:255';
        }
        if ($this->creating_new_employee) {
            // Add validation for new employee fields if you add them
            $rules['employee_search'] = 'required|string|max:255';
        }

        $this->validate($rules);

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


            $icsNumber = IcsNumber::create([
                'ics_number' => $this->ics_number,
                'assigned_employee_id' => $this->assigned_employee_id,
                'contract_item_id' => $final_contract_item->id,
                'ics_type' => $this->ics_type,
                'quantity' => $this->quantity,
                'estimated_useful_life' => $this->estimated_useful_life,
                'remarks' => $this->remarks,
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

            session()->flash('success', 'ICS record created successfully.');
            $this->redirectRoute('admin.inventory.ics.index');
        });
    }

}; ?>

<form wire:submit="store">
    <div class="border-b border-stone-200 pb-4 dark:border-stone-700">
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

    <div class="mt-6">
        @if ($isParItem)
            <div class="mb-4 rounded-lg border-l-4 border-red-500 bg-red-50 p-4 dark:bg-red-900/20">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-red-400 dark:text-red-300" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-red-700 dark:text-red-200">
                            High-Value Item Alert
                        </p>
                        <p class="mt-1 text-sm text-red-600 dark:text-red-300">
                            This item's value is ₱{{ number_format($this->unit_price, 2) }} per {{ $unit_of_measure ?: '' }}. Items valued at ₱50,000 or more should be registered as Property, Plant, and Equipment (PPE) using a <strong>Property Acknowledgement Receipt (PAR)</strong>, not an ICS.
                        </p>
                    </div>
                </div>
            </div>
        @endif

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <!-- Column 1: Supplier, Contract, and Details -->
            <div class="space-y-6">
                <!-- Supplier & Contract Section -->
                <div class="overflow-hidden rounded-lg border border-stone-200 bg-white shadow-sm dark:border-stone-700 dark:bg-stone-800">
                    <div class="border-b border-stone-200 px-4 py-3 dark:border-stone-700">
                        <h3 class="font-semibold text-stone-800 dark:text-stone-200">Supplier & Contract</h3>
                    </div>
                    <div class="p-4">
                        <div class="grid grid-cols-1 gap-x-6 gap-y-6 sm:grid-cols-2">
                            <div>
                                <x-autocomplete id="supplier_search" wire:model.live="supplier_search" wire:suggestions="supplier_suggestions" wire:showSuggestions="show_supplier_suggestions" label="Supplier" placeholder="Type to search suppliers..." required onFocus="$wire.showAllSuppliers()" onSelect="$wire.selectSupplier" error="supplier_id" />
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
                                <x-autocomplete id="contract_search" wire:model.live="contract_search" wire:suggestions="contract_suggestions" wire:showSuggestions="show_contract_suggestions" label="Contract/PO/IB Number" placeholder="{{ $creating_new_supplier ? 'Enter new contract number...' : 'Type to search contracts...' }}" :disabled="!$this->supplier_id && !$this->creating_new_supplier" required onFocus="$wire.showAllContracts()" onSelect="$wire.selectContract" error="contract_id" />
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

                <!-- Document Details -->
                <div class="overflow-hidden rounded-lg border border-stone-200 bg-white shadow-sm dark:border-stone-700 dark:bg-stone-800"
                    x-data="{
                        formatDate(event) {
                            let value = event.target.value.replace(/\D/g, '');
                            if (value.length > 8) {
                                value = value.substring(0, 8);
                            }
                            const month = value.substring(0, 2);
                            const day = value.substring(2, 4);
                            const year = value.substring(4, 8);

                            let formattedValue = '';
                            if (value.length > 4) {
                                formattedValue = `${month}/${day}/${year}`;
                            } else if (value.length > 2) {
                                formattedValue = `${month}/${day}`;
                            } else if (value.length > 0) {
                                formattedValue = month;
                            }
                            
                            event.target.value = formattedValue;
                        }
                    }">
                    <div class="border-b border-stone-200 px-4 py-3 dark:border-stone-700">
                        <h3 class="font-semibold text-stone-800 dark:text-stone-200">Document Details</h3>
                    </div>
                    <div class="space-y-4 p-4">
                        <div>
                            <flux:input wire:model="ics_number" label="ICS Number (YYYYMMDD##)" required tabindex="510" pattern="\d{10}" maxlength="10" />
                            <x-input-error for="ics_number" class="mt-2" />
                            <p class="mt-1 text-xs text-stone-500 dark:text-stone-400">
                                Format: Year+Month+Day+2-digit sequence (e.g., 2024010101)
                            </p>
                        </div>

                        <flux:select wire:model="ics_type" label="ICS Type" required :disabled="$isParItem || !$this->selected_item_name" tabindex="511">
                            <option value="">Select Type</option>
                            <option value="SPLV">SPLV - ₱5,000.00 or less</option>
                            <option value="SPHV">SPHV - ₱5,001.00 to ₱49,999.99</option>
                        </flux:select>

                        <flux:input wire:model="estimated_useful_life" type="number" label="Estimated Useful Life (Years)" min="1" :disabled="$isParItem" tabindex="512" />

                        <div class="grid grid-cols-1 gap-x-6 sm:grid-cols-2">
                            <div>
                                <flux:input
                                    wire:model.blur="date_prepared"
                                    type="text"
                                    label="Date Prepared"
                                    placeholder="MM/DD/YYYY"
                                    :disabled="$isParItem"
                                    tabindex="513"
                                    @input="formatDate($event)"
                                />
                                <x-input-error for="date_prepared" class="mt-2" />
                            </div>

                            <div>
                                <flux:input
                                    wire:model.blur="date_accepted"
                                    type="text"
                                    label="Date Accepted"
                                    placeholder="MM/DD/YYYY"
                                    :disabled="$isParItem"
                                    tabindex="514"
                                    @input="formatDate($event)"
                                />
                                <x-input-error for="date_accepted" class="mt-2" />
                            </div>
                        </div>

                        <flux:textarea wire:model="remarks" label="Remarks" placeholder="Add any notes or remarks here..." :disabled="$isParItem" tabindex="515" />
                    </div>
                </div>
            </div>

            <!-- Column 2: Item Information -->
            <div class="space-y-6">
                <!-- Employee Assignment Section -->
                <div class="overflow-hidden rounded-lg border border-stone-200 bg-white shadow-sm dark:border-stone-700 dark:bg-stone-800">
                    <div class="border-b border-stone-200 px-4 py-3 dark:border-stone-700">
                        <h3 class="font-semibold text-stone-800 dark:text-stone-200">Employee Assignment</h3>
                    </div>
                    <div class="p-4">
                        <x-autocomplete id="employee_search" wire:model.live="employee_search" wire:suggestions="employee_suggestions" wire:showSuggestions="show_employee_suggestions" label="Assign To Employee" placeholder="Type to search employees..." required onFocus="$wire.showAllEmployees()" onSelect="$wire.selectEmployee" error="assigned_employee_id" />
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
                                    <x-autocomplete id="item_search" wire:model.live="item_search" wire:suggestions="item_suggestions" wire:showSuggestions="show_item_suggestions" label="Select Item" placeholder="Search by item name..." required :disabled="!$this->contract_id && !$this->creating_new_contract" onFocus="$wire.showAllItems()" onSelect="$wire.selectItem" error="items_catalog_id" />
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
                                            <flux:select wire:model.live="primary_category_id" label="Primary Category" required>
                                                <option value="">Select primary category</option>
                                                @foreach ($allPrimaryCategories as $category)
                                                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                                                @endforeach
                                            </flux:select>
                                            <x-input-error for="primary_category_id" class="mt-2" />
                                        </div>
                                        <div>
                                            <flux:select wire:model="secondary_category_id" label="Secondary Category" :disabled="!$this->primary_category_id" required>
                                                <option value="">Select secondary category</option>
                                                @foreach ($this->filteredSecondaryCategories as $category)
                                                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                                                @endforeach
                                            </flux:select>
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
                                        <x-autocomplete id="specification_search" wire:model.live="specification_search" wire:suggestions="specification_suggestions" wire:showSuggestions="show_specification_suggestions" label="Specification Template" placeholder="Search by brand/model or create new..." required onFocus="$wire.showAllSpecifications()" onSelect="$wire.selectSpecification" error="item_specification_id" />
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
                                        <flux:input wire:model="main_item_brand" label="Brand" placeholder="e.g., HP, Dell, Samsung" :disabled="!$creating_new_item && !$creating_new_specification" />
                                        <x-input-error for="main_item_brand" class="mt-2" />
                                    </div>
                                    <div>
                                        <flux:input wire:model="main_item_model" label="Model" placeholder="e.g., ProBook 450 G9, XPS 15" :disabled="!$creating_new_item && !$creating_new_specification" />
                                        <x-input-error for="main_item_model" class="mt-2" />
                                    </div>
                                </div>
                                <div class="mt-4">
                                    <flux:textarea wire:model="detailed_specifications" label="Detailed Specifications" placeholder="Enter detailed specifications here, e.g., RAM, CPU, Storage, etc." :disabled="!$creating_new_item && !$creating_new_specification" rows="3" />
                                    <x-input-error for="detailed_specifications" class="mt-2" />
                                </div>
                            </div>

                            <!-- Pricing Section -->
                            <div class="border-t border-stone-200 pt-4 dark:border-stone-700">
                                <h4 class="mb-4 font-medium text-stone-800 dark:text-stone-200">Pricing Information</h4>
                                <div class="grid grid-cols-1 gap-x-6 gap-y-4 sm:grid-cols-2">
                                    <div>
                                        <flux:input wire:model="unit_price" label="Unit Cost" type="number" step="0.01" min="0" :disabled="!$creating_new_item && !$creating_new_specification">
                                            <x-slot:leading>
                                                <span class="text-stone-500">₱</span>
                                            </x-slot:leading>
                                        </flux:input>
                                        <x-input-error for="unit_price" class="mt-2" />
                                    </div>
                                    <div>
                                        <x-autocomplete id="unit_search_pricing" wire:model.live="unit_search" wire:suggestions="unit_suggestions" wire:showSuggestions="show_unit_suggestions" label="Unit of Measure" placeholder="e.g., piece, unit, kg" required onFocus="$wire.showAllUnits()" onSelect="$wire.selectUnit" error="unit_of_measure" />
                                        <x-input-error for="unit_of_measure" class="mt-2" />
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
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Column 3: Batches -->
            <div class="space-y-6">
                <!-- Batches & Components -->
                <div class="overflow-hidden rounded-lg border border-stone-200 bg-white shadow-sm dark:border-stone-700 dark:bg-stone-800">
                    <div class="border-b border-stone-200 px-4 py-3 dark:border-stone-700">
                        <h3 class="font-semibold text-stone-800 dark:text-stone-200">
                            Batches @if ($isDesktopComputer)
                                & Components
                            @endif
                        </h3>
                    </div>
                    <div class="p-4">
                        <div class="space-y-4">
                            <flux:input type="number" wire:model.live="quantity" label="Total Quantity / Number of Batches" min="1" required tabindex="10" />

                            <div class="space-y-6">
                                @foreach ($batches as $batchIndex => $batch)
                                    <div wire:key="batch-{{ $batchIndex }}" class="rounded-lg border border-stone-300 bg-stone-50 p-3 dark:border-stone-600 dark:bg-stone-800/50">
                                        <div class="flex items-center justify-between border-b border-stone-200 pb-2 dark:border-stone-700">
                                            <h4 class="font-semibold text-stone-800 dark:text-stone-200">
                                                Batch #{{ $loop->iteration }}
                                            </h4>
                                            @if ($quantity > 1)
                                                <flux:button type="button" variant="danger" size="sm" wire:click.prevent="removeBatch({{ $batchIndex }})">
                                                    <x-flux::icon.trash class="h-4 w-4" />
                                                </flux:button>
                                            @endif
                                        </div>

                                        @if ($isDesktopComputer)
                                            <div class="mt-4 space-y-4">
                                                @foreach ($batch['components'] as $componentIndex => $component)
                                                    <div wire:key="component-{{ $batchIndex }}-{{ $componentIndex }}" class="relative rounded-lg border border-stone-200 bg-white p-4 dark:border-stone-600 dark:bg-stone-700">
                                                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                                            <flux:input wire:model="batches.{{ $batchIndex }}.components.{{ $componentIndex }}.component_type" label="Component Type" placeholder="e.g., Monitor, Casing, UPS" tabindex="{{ 11 + ($batchIndex * 100) + ($componentIndex * 4) + 1 }}" />
                                                            <flux:input wire:model="batches.{{ $batchIndex }}.components.{{ $componentIndex }}.serial_number" label="Serial Number" tabindex="{{ 11 + ($batchIndex * 100) + ($componentIndex * 4) + 2 }}" />
                                                            <flux:input wire:model="batches.{{ $batchIndex }}.components.{{ $componentIndex }}.brand" label="Brand" tabindex="{{ 11 + ($batchIndex * 100) + ($componentIndex * 4) + 3 }}" />
                                                            <flux:input wire:model="batches.{{ $batchIndex }}.components.{{ $componentIndex }}.model" label="Model" tabindex="{{ 11 + ($batchIndex * 100) + ($componentIndex * 4) + 4 }}" />
                                                        </div>
                                                        @if (count($batch['components']) > 1)
                                                            <div class="absolute -right-2 -top-2">
                                                                <flux:button type="button" variant="danger" size="sm" wire:click.prevent="removeComponent({{ $batchIndex }}, {{ $componentIndex }})">
                                                                    <x-flux::icon.x-mark class="h-4 w-4" />
                                                                </flux:button>
                                                            </div>
                                                        @endif
                                                    </div>
                                                @endforeach
                                            </div>

                                            <div class="mt-4 border-t border-stone-200 pt-3 dark:border-stone-700">
                                                <flux:button type="button" variant="ghost" wire:click.prevent="addComponent({{ $batchIndex }})">
                                                    <x-flux::icon.plus class="mr-2 h-4 w-4" />
                                                    Add Component
                                                </flux:button>
                                            </div>
                                        @else
                                            <div class="mt-4">
                                                <p class="text-sm text-stone-600 dark:text-stone-400">
                                                    Components are only shown for desktop computers.
                                                </p>
                                            </div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>

@script
<script>
    document.addEventListener('livewire:initialized', () => {
        const focusOn = (elementId) => {
            setTimeout(() => {
                const element = document.getElementById(elementId);
                if (element) {
                    element.focus();
                }
            }, 0);
        };

        @this.on('focus-contract', () => focusOn('contract_search'));
        @this.on('focus-item', () => focusOn('item_search'));
        @this.on('focus-specification', () => focusOn('specification_search'));
        @this.on('focus-employee', () => focusOn('employee_search'));
        @this.on('focus-primary-category', () => focusOn('primary_category_id'));
        @this.on('focus-unit', () => focusOn('unit_search'));
    });
</script>
@endscript
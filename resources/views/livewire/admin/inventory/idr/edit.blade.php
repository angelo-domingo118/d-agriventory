<?php

use App\Models\Contract;
use App\Models\ContractItem;
use App\Models\Employee;
use App\Models\IdrNumber;
use App\Services\ToastService;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

new #[Layout('components.layouts.app')] class extends Component {
    public IdrNumber $idrNumber;
    public bool $showDeleteModal = false;
    public bool $showTransferModal = false;

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
    public string $date_prepared = '';
    public string $date_accepted = '';
    public string $date = '';
	public ?string $remarks = '';

    // Display only property
    public ?float $unit_price = 0.0;
    
    public array $batches = [];

    // Transfer state
    public ?int $transfer_to_employee_id = null;
    public string $transfer_date = '';
    public ?int $original_assigned_employee_id = null; // Track original employee for transfer detection

    // Employee search for transfer
    public string $transfer_employee_search = '';
    public array $transfer_employee_suggestions = [];
    public bool $show_transfer_employee_suggestions = false;
    public ?string $selected_transfer_employee_name = null;

    public Collection $allContracts;
    public Collection $allEmployees;

    public function mount(IdrNumber $idrNumber): void
    {
        if (!auth()->user()->hasAdminPermission('edit_inventory')) {
            abort(403);
        }

        $this->idrNumber = $idrNumber->load('itemBatches');
        $this->fill($this->idrNumber->toArray());
        $this->date_prepared = $this->idrNumber->date_prepared->format('Y-m-d');
        $this->date_accepted = $this->idrNumber->date_accepted->format('Y-m-d');
        $this->date = $this->idrNumber->date->format('Y-m-d');
        $this->quantity = $this->idrNumber->itemBatches->count();

        if ($this->idrNumber->contractItem) {
            $this->contract_id = $this->idrNumber->contractItem->contract_id;
        }

        $this->allContracts = Contract::with('supplier:id,name')->orderBy('contract_po_ib_number')->get(['id', 'contract_po_ib_number', 'supplier_id']);
        $this->allEmployees = Employee::orderBy('name')->get(['id', 'name']);
        
        // Initialize transfer date
        $this->transfer_date = now()->format('Y-m-d');
        
        // Track the original employee for transfer detection
        $this->original_assigned_employee_id = $this->assigned_employee_id;
        
        foreach ($this->idrNumber->itemBatches as $batch) {
            $this->batches[] = ['id' => $batch->id, 'identification_data' => $batch->identification_data, '_destroy' => false];
        }
        
        if ($this->idrNumber->itemBatches->isEmpty()) {
            $this->updatedQuantity($this->idrNumber->quantity);
        }
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
        $this->unit_price = $this->selectedContractItem?->unit_price ?? 0.0;
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
        $this->batches[] = ['id' => null, 'identification_data' => null, '_destroy' => false];
    }

    public function removeBatch(int $index): void
    {
        if (isset($this->batches[$index])) {
            if (!empty($this->batches[$index]['id'])) {
                $this->batches[$index]['_destroy'] = true;
            } else {
                array_splice($this->batches, $index, 1);
            }
        }
        $this->quantity = count(array_filter($this->batches, fn($b) => !($b['_destroy'] ?? false)));
    }
    
    #[Computed]
    public function contractItems()
    {
        if (!$this->contract_id) {
            return collect();
        }
        return ContractItem::where('contract_id', $this->contract_id)->with('itemSpecification.itemCatalog:id,name')->get();
    }

    // Transfer employee search methods
    public function updatedTransferEmployeeSearch($value): void
    {
        $this->searchTransferEmployees($value);
    }

    public function showAllTransferEmployees(): void
    {
        $this->searchTransferEmployees($this->transfer_employee_search);
        if (count($this->transfer_employee_suggestions) > 0) {
            $this->show_transfer_employee_suggestions = true;
        }
    }

    public function searchTransferEmployees($query): void
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

        $this->transfer_employee_suggestions = $employees->map(function ($employee) {
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

        $this->show_transfer_employee_suggestions = count($this->transfer_employee_suggestions) > 0;
    }

    public function selectTransferEmployee($employeeData): void
    {
        $this->transfer_to_employee_id = $employeeData['id'];
        $this->transfer_employee_search = $employeeData['name'];
        $this->selected_transfer_employee_name = $employeeData['name'];
        $this->show_transfer_employee_suggestions = false;
    }

    public function transferItem(): void
    {
        if (!auth()->user()->hasAdminPermission('transfer_inventory')) {
            abort(403);
        }

        $validated = $this->validate([
            'transfer_to_employee_id' => ['required', 'integer', Rule::exists('employees', 'id'), Rule::notIn([$this->idrNumber->assigned_employee_id])],
            'transfer_date' => ['required', 'date'],
        ]);

        DB::transaction(function () use ($validated) {
            // For IDR, we might want to create a simple transfer log or just update the assigned employee
            // Since IDR doesn't have a transfers relationship like ICS, let's update the assigned employee
            $this->idrNumber->update([
                'assigned_employee_id' => $validated['transfer_to_employee_id'],
            ]);

            // For the form state
            $this->assigned_employee_id = $validated['transfer_to_employee_id'];
        });

        // Update the form to reflect the new assignment
        $newEmployee = Employee::find($validated['transfer_to_employee_id']);
        if ($newEmployee) {
            // Update any employee search fields if they exist
        }

        $this->showTransferModal = false;
        $this->dispatch('idr-transferred');

        session()->flash('success', "IDR record #{$this->idrNumber->number} transferred successfully to {$newEmployee->name}.");
    }

    public function update(): void
    {
        $validated = $this->validate([
            'number' => ['required', 'string', 'max:255', Rule::unique('idr_number', 'number')->ignore($this->idrNumber->id)],
            'contract_id' => ['required', 'integer', Rule::exists('contracts', 'id')],
            'contract_item_id' => ['required', 'integer', Rule::exists('contract_items', 'id')],
            'assigned_employee_id' => ['required', 'integer', Rule::exists('employees', 'id')],
            'approving_employee_id' => ['required', 'integer', Rule::exists('employees', 'id')],
            'received_by_id' => ['required', 'integer', Rule::exists('employees', 'id')],
            'received_from_id' => ['required', 'integer', Rule::exists('employees', 'id')],
            'quantity' => ['required', 'integer', 'min:1', fn ($attribute, $value, $fail) => $value != count(array_filter($this->batches, fn($b) => !($b['_destroy'] ?? false))) && $fail("The quantity must match the number of active batches.")],
            'inventory_code' => ['required', 'string', 'max:255'],
            'ors' => ['required', 'string', 'max:255'],
            'date_prepared' => ['required', 'date'],
            'date_accepted' => ['required', 'date'],
            'date' => ['required', 'date'],
            'remarks' => ['nullable', 'string'],
            'batches.*.identification_data' => ['nullable', 'string', 'max:255'],
        ]);

        DB::transaction(function () use ($validated) {
            $this->idrNumber->update($validated);
            $activeBatchIds = [];
            foreach ($this->batches as $batchData) {
                if (!($batchData['_destroy'] ?? false)) {
                    $batch = $this->idrNumber->itemBatches()->updateOrCreate(
                        ['id' => $batchData['id'] ?? null],
                        ['identification_data' => $batchData['identification_data'] ?? null]
                    );
                    $activeBatchIds[] = $batch->id;
                }
            }
            $this->idrNumber->itemBatches()->whereNotIn('id', $activeBatchIds)->delete();
        });

        ToastService::updated($this, "IDR record {$this->idrNumber->number}");
        $this->redirect(route('admin.inventory.idr.show', $this->idrNumber), navigate: true);
    }

    public function destroy(): void
    {
        if (!auth()->user()->hasAdminPermission('delete_inventory')) {
            abort(403);
        }
        $this->idrNumber->delete();
        ToastService::deleted($this, 'IDR record');
        $this->redirect(route('admin.inventory.idr.index'), navigate: true);
    }
}; ?>

<div x-data="{ showDeleteModal: @entangle('showDeleteModal') }">
    <form wire:submit="update">
        <div class="border-b border-stone-200 pb-5 dark:border-stone-700">
            <div class="flex items-center justify-between">
                 <div>
                    <flux:breadcrumbs class="text-2xl font-semibold">
                        <flux:breadcrumbs.item :href="route('admin.dashboard')" wire:navigate icon="home" class="text-xl sm:text-2xl font-semibold text-stone-700 dark:text-stone-300" />
                        <flux:breadcrumbs.item class="text-xl sm:text-2xl font-semibold text-stone-500 dark:text-stone-400">Inventory</flux:breadcrumbs.item>
                        <flux:breadcrumbs.item :href="route('admin.inventory.idr.index')" wire:navigate class="text-xl sm:text-2xl font-semibold text-stone-500 dark:text-stone-400">IDR Management</flux:breadcrumbs.item>
                        <flux:breadcrumbs.item class="text-xl sm:text-2xl font-semibold text-stone-900 dark:text-stone-100">Edit IDR: {{ $idrNumber->number }}</flux:breadcrumbs.item>
                    </flux:breadcrumbs>
                    <p class="mt-1 text-sm text-stone-600 dark:text-stone-400">Update the details for this IDR.</p>
                </div>
                <div class="flex items-center gap-x-4">
                    @can('transfer_inventory')
                        <flux:button variant="outline" @click="$set('showTransferModal', true)" type="button">
                            <x-flux::icon.arrows-right-left class="mr-2 h-4 w-4" />
                            Transfer Item
                        </flux:button>
                    @endcan
                    <flux:button variant="ghost" :href="route('admin.inventory.idr.show', $idrNumber)" wire:navigate>Cancel</flux:button>
                    <flux:button type="submit" variant="primary">Save Changes</flux:button>
                </div>
            </div>
        </div>

        <div class="mt-6">
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-2 xl:grid-cols-3">
                <!-- Column 1: Item & Contract Information -->
                <div class="space-y-6">
                    <!-- Item & Contract Section -->
                    <div class="overflow-hidden rounded-lg border border-stone-200 bg-white shadow-sm dark:border-stone-700 dark:bg-stone-800">
                        <div class="border-b border-stone-200 px-4 py-3 dark:border-stone-700">
                            <h3 class="font-semibold text-stone-800 dark:text-stone-200">Item & Contract Information</h3>
                        </div>
                        <div class="p-4">
                            <div class="space-y-4">
                                <div>
                                    <flux:select wire:model.live="contract_id" label="Contract" required>
                                        <option value="">Select a contract</option>
                                        @foreach($this->allContracts as $contract)
                                            <option value="{{ $contract->id }}">{{ $contract->contract_po_ib_number }} ({{ $contract->supplier->name }})</option>
                                        @endforeach
                                    </flux:select>
                                </div>
                                <div>
                                    <flux:select wire:model.live="contract_item_id" label="Item" :disabled="!$this->contract_id" required>
                                        <option value="">Select an item</option>
                                        @if ($this->contractItems)
                                            @foreach ($this->contractItems as $item)
                                                <option value="{{ $item->id }}">{{ $item->itemSpecification->itemCatalog->name }}</option>
                                            @endforeach
                                        @endif
                                    </flux:select>
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
                            <flux:select wire:model="assigned_employee_id" label="Assigned To (Stock Officer)" required>
                                <option value="">Select</option>
                                @foreach($this->allEmployees as $employee)
                                    <option value="{{ $employee->id }}">{{ $employee->name }}</option>
                                @endforeach
                            </flux:select>
                            <flux:select wire:model="approving_employee_id" label="Approving Official" required>
                                <option value="">Select</option>
                                @foreach($this->allEmployees as $employee)
                                    <option value="{{ $employee->id }}">{{ $employee->name }}</option>
                                @endforeach
                            </flux:select>
                            <flux:select wire:model="received_by_id" label="Received By" required>
                                <option value="">Select</option>
                                @foreach($this->allEmployees as $employee)
                                    <option value="{{ $employee->id }}">{{ $employee->name }}</option>
                                @endforeach
                            </flux:select>
                            <flux:select wire:model="received_from_id" label="Issued By" required>
                                <option value="">Select</option>
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
                        <div class="space-y-4 p-4">
                            <flux:input wire:model="number" label="IDR Number" required />
                            <flux:input wire:model="inventory_code" label="Inventory Code" required />
                            <flux:input wire:model="ors" label="ORS Number" required />
                            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <flux:input wire:model="date_prepared" type="date" label="Date Prepared" required />
                                <flux:input wire:model="date_accepted" type="date" label="Date Accepted" required />
                            </div>
                            <flux:input wire:model="date" type="date" label="IDR Date" required />
                            <flux:textarea wire:model="remarks" label="Remarks" placeholder="Add any notes or remarks here..." rows="2" />
                        </div>
                    </div>
                </div>

                <!-- Column 3: Batches -->
                <div class="space-y-6 lg:col-span-2 xl:col-span-1">
                    <!-- Batches & Serial Numbers -->
                    <div class="overflow-hidden rounded-lg border border-stone-200 bg-white shadow-sm dark:border-stone-700 dark:bg-stone-800">
                        <div class="border-b border-stone-200 px-4 py-3 dark:border-stone-700">
                            <h3 class="font-semibold text-stone-800 dark:text-stone-200">Batches / Serial Numbers</h3>
                        </div>
                        <div class="p-4">
                            <div class="space-y-4">
                                <div class="w-full">
                                    <label for="quantity" class="mb-1 block text-sm font-medium text-stone-700 dark:text-stone-300">
                                        Total Quantity / Number of Batches
                                    </label>
                                    <div class="flex items-center space-x-2" x-data="{ 
                                        localQuantity: $wire.entangle('quantity'),
                                        
                                        validateNumber(e) {
                                            let value = e.target.value.replace(/[^\d]/g, '');
                                            const numValue = value ? parseInt(value) : 1;
                                            this.localQuantity = Math.max(1, numValue);
                                        },
                                        
                                        increment() {
                                            this.localQuantity++;
                                        },
                                        
                                        decrement() {
                                            if (this.localQuantity > 1) {
                                                this.localQuantity--;
                                            }
                                        }
                                    }">
                                        <!-- Minus Button -->
                                        <flux:button
                                            type="button"
                                            variant="outline"
                                            size="sm"
                                            @click="decrement()"
                                            x-bind:disabled="localQuantity <= 1"
                                            class="flex-shrink-0 w-10 h-10 p-0 flex items-center justify-center"
                                        >
                                            <x-flux::icon.minus class="h-4 w-4" />
                                        </flux:button>
                                        
                                        <!-- Input Field -->
                                        <flux:input 
                                            id="quantity"
                                            x-model="localQuantity"
                                            type="number"
                                            min="1" 
                                            class="flex-1 text-center"
                                            @input="validateNumber($event)"
                                        />
                                        
                                        <!-- Plus Button -->
                                        <flux:button
                                            type="button"
                                            variant="outline"
                                            size="sm"
                                            @click="increment()"
                                            class="flex-shrink-0 w-10 h-10 p-0 flex items-center justify-center"
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

                                <div class="space-y-4">
                                    @foreach ($batches as $batchIndex => $batch)
                                        @if (!($batch['_destroy'] ?? false))
                                            <div wire:key="batch-{{ $batchIndex }}" class="rounded-lg border border-stone-300 bg-white p-4 dark:border-stone-600 dark:bg-stone-800/50">
                                                <div class="flex items-center justify-between mb-3">
                                                    <h4 class="font-semibold text-stone-800 dark:text-stone-200 flex items-center space-x-2">
                                                        <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-stone-200 dark:bg-stone-600 text-sm font-medium">
                                                            {{ $loop->iteration }}
                                                        </span>
                                                        <span>Batch #{{ $loop->iteration }}</span>
                                                    </h4>
                                                    @if ($quantity > 1)
                                                        <flux:button type="button" variant="danger" size="sm" wire:click.prevent="removeBatch({{ $batchIndex }})">
                                                            <x-flux::icon.trash class="h-4 w-4" />
                                                        </flux:button>
                                                    @endif
                                                </div>
                                                <flux:input 
                                                    wire:model="batches.{{ $batchIndex }}.identification_data" 
                                                    label="Serial/Identification Data"
                                                    placeholder="e.g. SN: 12345, Asset Tag: 67890" 
                                                />
                                            </div>
                                        @endif
                                    @endforeach
                                    
                                    <div class="mt-4 text-center">
                                        <flux:button type="button" variant="outline" wire:click.prevent="addBatch" class="w-full">
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

                    <!-- Danger Zone -->
                    @can('delete_inventory')
                    <div class="overflow-hidden rounded-lg border border-red-500 bg-red-50 shadow-sm dark:border-red-600/50 dark:bg-red-900/10">
                        <div class="border-b border-red-200 px-4 py-3 dark:border-red-600/20">
                            <h3 class="font-semibold text-red-800 dark:text-red-200">Danger Zone</h3>
                        </div>
                        <div class="p-4">
                            <p class="text-sm text-red-700 dark:text-red-300 mb-4">Deleting this IDR record is a permanent action and cannot be undone.</p>
                            <flux:button type="button" variant="danger" @click="showDeleteModal = true">Delete this IDR Record</flux:button>
                        </div>
                    </div>
                    @endcan
                </div>
            </div>
        </div>
    </form>

    <flux:modal title="Delete IDR Record" :show="$showDeleteModal" max-width="lg" @close="$set('showDeleteModal', false)">
        <x-slot:content><p class="p-4 text-sm text-stone-600 dark:text-stone-400">Are you sure you want to delete this record? This action cannot be undone.</p></x-slot:content>
        <x-slot:footer>
            <div class="flex justify-end gap-x-4">
                <flux:button variant="ghost" @click="$set('showDeleteModal', false)">Cancel</flux:button>
                <flux:button variant="danger" wire:click="destroy">Delete</flux:button>
            </div>
        </x-slot:footer>
    </flux:modal>

    <!-- Transfer Modal -->
    <flux:modal title="Transfer IDR Item" :show="$showTransferModal" max-width="lg" @close="$set('showTransferModal', false)">
        <x-slot:content>
            <div class="space-y-6 p-4">
                <div class="rounded-md bg-blue-50 p-4 dark:bg-blue-900/10">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <x-flux::icon.document-text class="h-5 w-5 text-blue-400" />
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-blue-700 dark:text-blue-300">
                                This will transfer the IDR item <strong>{{ $idrNumber->number }}</strong> to a new employee.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-stone-700 dark:text-stone-300">Currently Assigned To</label>
                        <p class="mt-1 text-sm text-stone-600 dark:text-stone-400">{{ $idrNumber->assignedEmployee->name }} ({{ $idrNumber->assignedEmployee->division->name ?? 'No division' }})</p>
                    </div>

                    <div class="relative">
                        <flux:input 
                            wire:model.live.debounce.300ms="transfer_employee_search" 
                            wire:focus="showAllTransferEmployees" 
                            label="Transfer To" 
                            placeholder="Search employees..."
                            required
                        />
                        @error('transfer_to_employee_id') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        
                        @if($show_transfer_employee_suggestions && count($transfer_employee_suggestions) > 0)
                            <div class="absolute z-50 mt-1 w-full rounded-md border border-stone-300 bg-white shadow-lg dark:border-stone-600 dark:bg-stone-800">
                                <ul class="max-h-60 overflow-auto rounded-md py-1">
                                    @foreach($transfer_employee_suggestions as $employee)
                                        @if($employee['id'] !== $idrNumber->assigned_employee_id)
                                            <li wire:click="selectTransferEmployee(@js($employee))" class="cursor-pointer px-3 py-2 hover:bg-stone-100 dark:hover:bg-stone-700">
                                                <div class="font-medium text-stone-900 dark:text-stone-100">{{ $employee['name'] }}</div>
                                                @if($employee['description'])
                                                    <div class="text-sm text-stone-500 dark:text-stone-400">{{ $employee['description'] }}</div>
                                                @endif
                                            </li>
                                        @endif
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                    </div>

                    <div>
                        <flux:input wire:model="transfer_date" type="date" label="Transfer Date" required />
                        @error('transfer_date') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>
                </div>
            </div>
        </x-slot:content>
        <x-slot:footer>
            <div class="flex justify-end gap-x-4">
                <flux:button variant="ghost" @click="$set('showTransferModal', false)">Cancel</flux:button>
                <flux:button variant="primary" wire:click="transferItem" :disabled="!$transfer_to_employee_id">Transfer Item</flux:button>
            </div>
        </x-slot:footer>
    </flux:modal>
</div> 
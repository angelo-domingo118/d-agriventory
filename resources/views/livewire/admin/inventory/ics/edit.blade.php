<?php

use App\Models\Contract;
use App\Models\ContractItem;
use App\Models\Employee;
use App\Models\IcsNumber;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

new #[Layout('components.layouts.app')] class extends Component {
    public IcsNumber $icsNumber;
    public bool $showDeleteModal = false;
    public bool $showTransferModal = false;

    // Form state
    public string $ics_number = '';
    public ?int $contract_id = null;
    public ?int $contract_item_id = null;
    public ?int $assigned_employee_id = null;
    public string $ics_type = 'SPLV';
    public int $quantity = 1;
    public ?int $estimated_useful_life = 1;
    public string $date_prepared = '';
    public ?string $date_accepted = null;
    public ?string $remarks = null;

    // Display only property
    public ?float $unit_price = 0.0;

    // Transfer state
    public ?int $transfer_to_employee_id = null;
    public string $transfer_date = '';

    public array $batches = [];

    public Collection $allContracts;
    public Collection $allEmployees;

    public function mount(IcsNumber $icsNumber): void
    {
        if (!auth()->user()->hasAdminPermission('edit_inventory')) {
            abort(403);
        }

        $this->icsNumber = $icsNumber->load('itemBatches.components');
        $this->fill($icsNumber->toArray()); // Pre-fill form from the model
        $this->date_prepared = $icsNumber->date_prepared->format('Y-m-d');
        $this->date_accepted = $icsNumber->date_accepted?->format('Y-m-d');
        $this->quantity = $icsNumber->itemBatches->count();

        // Manually set contract_id for the dependent dropdown
        if ($this->icsNumber->contractItem) {
            $this->contract_id = $this->icsNumber->contractItem->contract_id;
        }

        // Pre-load data for select dropdowns
        $this->allContracts = Contract::with('supplier:id,name')
            ->orderBy('contract_po_ib_number')
            ->get(['id', 'contract_po_ib_number', 'supplier_id']);

        $this->allEmployees = Employee::orderBy('name')
            ->get(['id', 'name']);

        // Populate batches for editing
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

        $this->transfer_date = now()->format('Y-m-d');
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
        $this->ics_type = ($this->unit_price ?? 0) > 50000 ? 'SPHV' : 'SPLV';
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

        $this->showTransferModal = false;
        $this->dispatch('ics-transferred');
        session()->flash('success', "Item successfully transferred.");

        // Reload the model to get fresh transfer history
        $this->icsNumber->load('transfers.fromEmployee', 'transfers.toEmployee');
    }

    #[Computed]
    public function contractItems()
    {
        if (!$this->contract_id) {
            return collect();
        }

        return ContractItem::where('contract_id', $this->contract_id)
            ->with('itemSpecification.itemCatalog:id,name') // Eager load only necessary columns
            ->get();
    }

    public function update(): void
    {
        $validated = $this->validate([
            'ics_number' => ['required', 'string', 'max:255', Rule::unique('ics_number', 'ics_number')->ignore($this->icsNumber->id)],
            'contract_id' => ['required', 'integer', Rule::exists('contracts', 'id')],
            'contract_item_id' => ['required', 'integer', Rule::exists('contract_items', 'id')],
            'assigned_employee_id' => ['required', 'integer', Rule::exists('employees', 'id')],
            'ics_type' => ['required', 'string', Rule::in(['SPLV', 'SPHV'])],
            'quantity' => ['required', 'integer', 'min:1', function ($attribute, $value, $fail) {
                $activeBatches = count(array_filter($this->batches, fn($b) => !($b['_destroy'] ?? false)));
                if ($value != $activeBatches) {
                    $fail("The quantity ($value) must match the number of active batches ($activeBatches).");
                }
            }],
            'estimated_useful_life' => ['required', 'integer', 'min:1'],
            'date_prepared' => ['required', 'date'],
            'date_accepted' => ['nullable', 'date', 'after_or_equal:date_prepared'],
            'remarks' => ['nullable', 'string'],
            'batches.*.components.*.component_type' => ['required_with:batches.*.components.*.serial_number', 'nullable', 'string', 'max:255'],
            'batches.*.components.*.brand' => ['nullable', 'string', 'max:255'],
            'batches.*.components.*.model' => ['nullable', 'string', 'max:255'],
            'batches.*.components.*.serial_number' => ['nullable', 'string', 'max:255'],
        ]);

        DB::transaction(function () use ($validated) {
            // 1. Update the main IcsNumber record
            $this->icsNumber->update($validated);

            $activeBatchIds = [];
            foreach ($this->batches as $batchData) {
                // 2. Handle batch creation/update
                $batch = null;
                if (!($batchData['_destroy'] ?? false)) {
                    $batch = $this->icsNumber->itemBatches()->updateOrCreate(
                        ['id' => $batchData['id'] ?? null],
                        ['identification_data' => $batchData['identification_data'] ?? null]
                    );
                    $activeBatchIds[] = $batch->id;

                    $activeComponentIds = [];
                    foreach ($batchData['components'] as $componentData) {
                        // 3. Handle component creation/update
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
                    // 4. Delete components marked for destruction in this batch
                    $batch->components()->whereNotIn('id', $activeComponentIds)->delete();
                }
            }
            // 5. Delete batches marked for destruction
            $this->icsNumber->itemBatches()->whereNotIn('id', $activeBatchIds)->delete();
        });

        $this->dispatch('ics-updated');
        session()->flash('success', "ICS record {$this->icsNumber->ics_number} updated successfully.");
        $this->redirect(route('admin.inventory.ics.show', $this->icsNumber), navigate: true);
    }

    public function destroy(): void
    {
        if (!auth()->user()->hasAdminPermission('delete_inventory')) {
            abort(403);
        }

        try {
            $this->icsNumber->delete();
            $this->dispatch('ics-deleted');
            session()->flash('success', 'ICS record deleted successfully.');
            $this->redirect(route('admin.inventory.ics.index'), navigate: true);
        } catch (\Illuminate\Database\QueryException $e) {
            if (($e->errorInfo[1] ?? null) === 1451) { // FK constraint
                session()->flash('error', 'Cannot delete this record because it is referenced by other records.');
            } else {
                session()->flash('error', 'An unexpected database error occurred while deleting the record.');
            }
            \Log::warning('ICS delete failed', ['ics_id' => $this->icsNumber->id, 'error' => $e->getMessage()]);
        } catch (\Throwable $e) {
            // Log the exception for debugging
            \Illuminate\Support\Facades\Log::error('Error deleting ICS record: ' . $e->getMessage());
            session()->flash('error', 'An unexpected error occurred while deleting the record.');
        }
    }
}; ?>

<div x-data="{ showDeleteModal: @entangle('showDeleteModal'), showTransferModal: @entangle('showTransferModal') }">
    <form wire:submit="update">
        <div class="border-b border-stone-200 pb-4 dark:border-stone-700">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-semibold text-stone-900 dark:text-stone-100">
                        Edit ICS: {{ $icsNumber->ics_number }}
                    </h1>
                    <p class="mt-1 text-sm text-stone-600 dark:text-stone-400">
                        Update the details for Inventory Custodian Slip #{{ $this->icsNumber->ics_number }}.
                    </p>
                </div>
                <div class="flex items-center gap-x-4">
                    <x-action-message class="me-3" on="ics-updated">
                        {{ __('Record updated successfully.') }}
                    </x-action-message>
                    <flux:button variant="ghost" :href="route('admin.inventory.ics.show', $icsNumber)" wire:navigate>
                        Cancel
                    </flux:button>
                    <flux:button type="button" variant="filled" @click="$wire.set('showTransferModal', true)">
                        Transfer Item
                    </flux:button>
                    <flux:button type="submit" variant="primary" wire:loading.attr="disabled" wire:target="update">
                        <span wire:loading.remove wire:target="update">Save Changes</span>
                        <span wire:loading wire:target="update">Saving...</span>
                    </flux:button>
                </div>
            </div>
        </div>

        <div class="mt-6">
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-2 xl:grid-cols-3">
                <!-- Column 1: Item & Contract Information -->
                <div class="space-y-6">
                    <!-- Item & Contract Details -->
                    <div class="overflow-hidden rounded-lg border border-stone-200 bg-white shadow-sm dark:border-stone-700 dark:bg-stone-800">
                        <div class="border-b border-stone-200 px-4 py-3 dark:border-stone-700">
                            <h3 class="font-semibold text-stone-800 dark:text-stone-200">Item & Contract Information</h3>
                        </div>
                        <div class="p-4">
                            <div class="space-y-6">
                                <div class="grid grid-cols-1 gap-x-6 gap-y-6 sm:grid-cols-2">
                                    <div>
                                        <flux:select wire:model.live="contract_id" label="Contract" id="contract_id" required>
                                            <option value="">Select a contract</option>
                                            @foreach($this->allContracts as $contract)
                                                <option value="{{ $contract->id }}">{{ $contract->contract_po_ib_number }} ({{ $contract->supplier->name }})</option>
                                            @endforeach
                                        </flux:select>
                                        <x-input-error for="contract_id" class="mt-2" />
                                    </div>
                                    <div>
                                        <flux:select wire:model.live="contract_item_id" label="Item" id="contract_item_id" :disabled="!$this->contract_id" required>
                                            <option value="">Select an item</option>
                                            @if ($this->contractItems)
                                                @foreach ($this->contractItems as $item)
                                                    <option value="{{ $item->id }}">{{ $item->itemSpecification->itemCatalog->name }}</option>
                                                @endforeach
                                            @endif
                                        </flux:select>
                                        <x-input-error for="contract_item_id" class="mt-2" />
                                    </div>
                                </div>

                                <!-- Pricing Information -->
                                <div class="border-t border-stone-200 pt-4 dark:border-stone-700">
                                    <h4 class="mb-4 font-medium text-stone-800 dark:text-stone-200">Pricing Information</h4>
                                    <div>
                                        <flux:input
                                            wire:model="unit_price"
                                            label="Unit Cost"
                                            id="unit_cost"
                                            type="text"
                                            :disabled="true"
                                        >
                                            <x-slot:leading>
                                                <span class="text-stone-500">₱</span>
                                            </x-slot:leading>
                                        </flux:input>
                                        @if($this->unit_price && $this->unit_price >= 50000)
                                            <div class="mt-2 flex w-full items-center rounded-lg bg-red-50 p-3 text-sm text-red-700 dark:bg-red-800/20 dark:text-red-400" role="alert">
                                                <svg class="mr-3 h-5 w-5 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 002 0v-3a1 1 0 00-2 0z" clip-rule="evenodd" />
                                                </svg>
                                                <span class="font-medium">
                                                    This item cost requires it to be recorded on a Property Accountability Receipt (PAR), not an ICS.
                                                </span>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Transfer History -->
                    <div class="overflow-hidden rounded-lg border border-stone-200 bg-white shadow-sm dark:border-stone-700 dark:bg-stone-800">
                        <div class="border-b border-stone-200 px-4 py-3 dark:border-stone-700">
                            <h3 class="font-semibold text-stone-800 dark:text-stone-200">Transfer History</h3>
                        </div>
                        <div class="p-4">
                            @if ($this->icsNumber->transfers->isNotEmpty())
                                <ul class="space-y-4">
                                    @foreach ($this->icsNumber->transfers->sortByDesc('transfer_date') as $transfer)
                                        <li wire:key="transfer-{{ $transfer->id }}" class="text-sm">
                                            <p class="font-medium text-stone-700 dark:text-stone-300">
                                                From: <span class="font-normal">{{ $transfer->fromEmployee?->name ?? 'N/A' }}</span>
                                            </p>
                                            <p class="font-medium text-stone-700 dark:text-stone-300">
                                                To: <span class="font-normal">{{ $transfer->toEmployee?->name ?? 'N/A' }}</span>
                                            </p>
                                            <p class="text-xs text-stone-500 dark:text-stone-400">
                                                {{ $transfer->transfer_date->format('F d, Y') }}
                                            </p>
                                        </li>
                                    @endforeach
                                </ul>
                            @else
                                <p class="text-sm text-stone-500 dark:text-stone-400">No transfer history for this item.</p>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Column 2: Employee Assignment & Document Details -->
                <div class="space-y-6">
                    <!-- Custodian Information -->
                    <div class="overflow-hidden rounded-lg border border-stone-200 bg-white shadow-sm dark:border-stone-700 dark:bg-stone-800">
                        <div class="border-b border-stone-200 px-4 py-3 dark:border-stone-700">
                            <h3 class="font-semibold text-stone-800 dark:text-stone-200">Employee Assignment</h3>
                        </div>
                        <div class="p-4">
                            <flux:select wire:model.live="assigned_employee_id" label="Assign To Employee" id="assigned_employee_id" required>
                                <option value="">Select an employee</option>
                                @foreach($this->allEmployees as $employee)
                                    <option value="{{ $employee->id }}" @if($employee->id === $assigned_employee_id) selected @endif>
                                        {{ $employee->name }}
                                    </option>
                                @endforeach
                            </flux:select>
                            <x-input-error for="assigned_employee_id" class="mt-2" />
                        </div>
                    </div>

                    <!-- Document Details -->
                    <div class="overflow-hidden rounded-lg border border-stone-200 bg-white shadow-sm dark:border-stone-700 dark:bg-stone-800">
                        <div class="border-b border-stone-200 px-4 py-3 dark:border-stone-700">
                            <h3 class="font-semibold text-stone-800 dark:text-stone-200">Document Details</h3>
                        </div>
                        <div class="space-y-4 p-4">
                            <div class="grid grid-cols-1 gap-x-6 sm:grid-cols-2">
                                <div>
                                    <flux:input wire:model="ics_number" label="ICS Number" required />
                                    <x-input-error for="ics_number" class="mt-2" />
                                </div>
                                <div>
                                    <flux:input wire:model="estimated_useful_life" type="number" label="Estimated Useful Life (Years)" min="1" required />
                                    <x-input-error for="estimated_useful_life" class="mt-2" />
                                </div>
                            </div>

                            <div>
                                <flux:select wire:model="ics_type" label="ICS Type" required>
                                    <option value="SPLV">Small-Value (SPLV)</option>
                                    <option value="SPHV">High-Value (SPHV)</option>
                                </flux:select>
                                <x-input-error for="ics_type" class="mt-2" />
                            </div>

                            <div class="grid grid-cols-1 gap-x-6 sm:grid-cols-2">
                                <div>
                                    <flux:input wire:model="date_prepared" type="date" label="Date Prepared" required />
                                    <x-input-error for="date_prepared" class="mt-2" />
                                </div>
                                <div>
                                    <flux:input wire:model="date_accepted" type="date" label="Date Accepted" />
                                    <x-input-error for="date_accepted" class="mt-2" />
                                </div>
                            </div>

                            <flux:textarea wire:model="remarks" label="Remarks" placeholder="Add any notes or remarks here..." rows="4" />
                        </div>
                    </div>

                    <!-- Danger Zone -->
                    <div class="overflow-hidden rounded-lg border border-red-500 bg-red-50 shadow-sm dark:border-red-600/50 dark:bg-red-900/10">
                        <div class="border-b border-red-200 px-4 py-3 dark:border-red-600/20">
                            <h3 class="font-semibold text-red-800 dark:text-red-200">Danger Zone</h3>
                        </div>
                        <div class="p-4">
                            <p class="text-sm text-red-700 dark:text-red-300">
                                Deleting this ICS record is a permanent action and cannot be undone. All associated batch and component data will be removed.
                            </p>
                            <flux:button type="button" variant="danger" class="mt-4" @click="showDeleteModal = true">
                                Delete this ICS Record
                            </flux:button>
                        </div>
                    </div>
                </div>

                <!-- Column 3: Batches & Components -->
                <div class="space-y-6 lg:col-span-2 xl:col-span-1">
                    <div class="overflow-hidden rounded-lg border border-stone-200 bg-white shadow-sm dark:border-stone-700 dark:bg-stone-800">
                        <div class="border-b border-stone-200 px-4 py-3 dark:border-stone-700">
                            <h3 class="font-semibold text-stone-800 dark:text-stone-200">Batches</h3>
                        </div>
                        <div class="p-4">
                            <div class="space-y-4">
                                <div class="w-full">
                                    <flux:input type="number" wire:model.live="quantity" label="Total Quantity / Number of Batches" min="1" required class="w-full" />
                                    <x-input-error for="quantity" class="mt-2" />
                                </div>

                                <div class="space-y-6">
                                    @foreach ($batches as $batchIndex => $batch)
                                        @if (!($batch['_destroy'] ?? false))
                                            <div wire:key="batch-{{ $batchIndex }}" class="rounded-lg border border-stone-300 bg-white p-0 dark:border-stone-600 dark:bg-stone-800/50" x-data="{ expanded: true }">
                                                <div class="flex items-center justify-between p-3 bg-stone-50 dark:bg-stone-700/50 rounded-t-lg border-b border-stone-200 dark:border-stone-700">
                                                    <h4 class="font-semibold text-stone-800 dark:text-stone-200 flex items-center space-x-2">
                                                        <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-stone-200 dark:bg-stone-600 text-sm font-medium">
                                                            {{ $loop->iteration }}
                                                        </span>
                                                        <span>Batch #{{ $loop->iteration }}</span>
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
                                                    <!-- Identification Data -->
                                                    <div class="mb-4">
                                                        <flux:input 
                                                            wire:model="batches.{{ $batchIndex }}.identification_data" 
                                                            label="Serial Number/Asset Tag" 
                                                            placeholder="Enter serial number, asset tag or other identifying info" />
                                                    </div>

                                                    <!-- Components Section -->
                                                    @if(!empty($batch['components']) && count(array_filter($batch['components'], fn($c) => !($c['_destroy'] ?? false))) > 0)
                                                        <div x-data="{ showComponents: true }">
                                                            <div class="border-t border-stone-200 pt-3 dark:border-stone-700">
                                                                <flux:button 
                                                                    type="button" 
                                                                    variant="outline" 
                                                                    size="sm" 
                                                                    @click="showComponents = !showComponents"
                                                                    class="w-full flex justify-between items-center mb-3"
                                                                >
                                                                    <span>Batch Components</span>
                                                                    <span x-show="!showComponents"><x-flux::icon.chevron-down class="h-4 w-4" /></span>
                                                                    <span x-show="showComponents"><x-flux::icon.chevron-up class="h-4 w-4" /></span>
                                                                </flux:button>
                                                            </div>
                                                            
                                                            <div x-show="showComponents" class="space-y-4">
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
                                                                                <flux:input wire:model="batches.{{ $batchIndex }}.components.{{ $componentIndex }}.component_type" label="Component Type" placeholder="e.g., Monitor, Casing, UPS" />
                                                                                <flux:input wire:model="batches.{{ $batchIndex }}.components.{{ $componentIndex }}.serial_number" label="Serial Number" />
                                                                                <flux:input wire:model="batches.{{ $batchIndex }}.components.{{ $componentIndex }}.brand" label="Brand" />
                                                                                <flux:input wire:model="batches.{{ $batchIndex }}.components.{{ $componentIndex }}.model" label="Model" />
                                                                            </div>
                                                                        </div>
                                                                    @endif
                                                                @endforeach

                                                                <div class="text-center">
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
                                    
                                    <div class="text-center">
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

    <!-- Delete Confirmation Modal -->
    <flux:modal title="Delete ICS Record" :show="$showDeleteModal" max-width="lg" @close="$set('showDeleteModal', false)">
        <x-slot:content>
            <div class="p-4">
                <p class="text-sm text-stone-600 dark:text-stone-400">
                    Are you sure you want to delete this item? This action cannot be undone.
                </p>
            </div>
        </x-slot:content>

        <x-slot:footer>
            <div class="flex justify-end gap-x-4">
                <flux:button variant="ghost" wire:click="$set('showDeleteModal', false)">Cancel</flux:button>
                <flux:button variant="danger" wire:click="destroy">Delete</flux:button>
            </div>
        </x-slot:footer>
    </flux:modal>

    <!-- Transfer Modal -->
    <flux:modal title="Transfer Item" :show="$showTransferModal" max-width="lg" @close="$set('showTransferModal', false)">
        <div class="p-6">
            <div class="space-y-4">
                <flux:select wire:model="transfer_to_employee_id" label="Transfer to Employee" required>
                    <option value="">Select an employee</option>
                    @foreach ($this->allEmployees as $employee)
                        <option value="{{ $employee->id }}" @if($employee->id === $assigned_employee_id) disabled @endif>
                            {{ $employee->name }}
                        </option>
                    @endforeach
                </flux:select>

                <flux:input type="date" wire:model="transfer_date" label="Transfer Date" required />
            </div>
        </div>

        <x-slot:footer>
            <div class="flex justify-end gap-x-4">
                <flux:button variant="ghost" wire:click="$set('showTransferModal', false)">Cancel</flux:button>
                <flux:button variant="primary" wire:click="transferItem">Transfer</flux:button>
            </div>
        </x-slot:footer>
    </flux:modal>
</div> 
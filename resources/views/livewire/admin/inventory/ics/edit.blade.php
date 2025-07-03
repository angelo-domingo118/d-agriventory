<?php

use App\Models\Contract;
use App\Models\ContractItem;
use App\Models\Employee;
use App\Models\IcsNumber;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.app')] class extends Component {
    public IcsNumber $icsNumber;
    public bool $showDeleteModal = false;

    // Form state
    public string $ics_number = '';
    public ?int $contract_id = null;
    public ?int $contract_item_id = null;
    public ?int $assigned_employee_id = null;
    public string $ics_type = 'SPLV';
    public ?int $quantity = 1;
    public ?int $estimated_useful_life = 1;
    public string $date_prepared = '';
    public string $remarks = '';

    public $allContracts;
    public $allEmployees;

    public function mount(IcsNumber $icsNumber): void
    {
        if (!auth()->user()->hasAdminPermission('edit_inventory')) {
            abort(403);
        }

        $this->icsNumber = $icsNumber;
        $this->fill($icsNumber); // Pre-fill form from the model
        $this->date_prepared = $icsNumber->date_prepared->format('Y-m-d');

        // Manually set contract_id for the dependent dropdown
        if ($this->icsNumber->contractItem) {
            $this->contract_id = $this->icsNumber->contractItem->contract_id;
        }

        // Pre-load data to prevent re-querying on each render
        $this->allContracts = Contract::with('supplier:id,name')->get(['id', 'contract_number', 'supplier_id']);
        $this->allEmployees = Employee::orderBy('name')->get(['id', 'name']);
    }

    #[Computed]
    public function contractItems()
    {
        if (!$this->contract_id) {
            return collect();
        }

        return ContractItem::where('contract_id', $this->contract_id)
            ->with('itemSpecification.catalogItem:id,name') // Eager load only necessary columns
            ->get();
    }

    public function update(): void
    {
        $validated = $this->validate([
            'ics_number' => ['required', 'string', 'max:255', Rule::unique('ics_numbers', 'ics_number')->ignore($this->icsNumber->id)],
            'contract_id' => ['required', 'integer', Rule::exists('contracts', 'id')],
            'contract_item_id' => ['required', 'integer', Rule::exists('contract_items', 'id')],
            'assigned_employee_id' => ['required', 'integer', Rule::exists('employees', 'id')],
            'ics_type' => ['required', 'string', Rule::in(['SPLV', 'SPHV'])],
            'quantity' => ['required', 'integer', 'min:1'],
            'estimated_useful_life' => ['required', 'integer', 'min:1'],
            'date_prepared' => ['required', 'date'],
            'remarks' => ['nullable', 'string'],
        ]);

        $this->icsNumber->update($validated);

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
        } catch (\Exception $e) {
            // Log the exception for debugging
            \Illuminate\Support\Facades\Log::error('Error deleting ICS record: ' . $e->getMessage());

            // Check for foreign key constraint violation
            if ($e instanceof \Illuminate\Database\QueryException && str_contains($e->getMessage(), 'Foreign key constraint fails')) {
                session()->flash('error', 'Cannot delete this record because it is referenced by other records.');
            } else {
                session()->flash('error', 'An unexpected error occurred while deleting the record.');
            }
        }
    }
}; ?>

<div x-data="{ showDeleteModal: @entangle('showDeleteModal') }">
    <form wire:submit="update" class="space-y-6">
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-semibold text-stone-900 dark:text-stone-100">
                Edit ICS Record: {{ $icsNumber->ics_number }}
            </h1>
            <div class="flex items-center gap-x-4">
                <flux:button variant="ghost" :href="route('admin.inventory.ics.show', $icsNumber)" wire:navigate>
                    Cancel
                </flux:button>
                <flux:button type="submit" variant="primary">
                    Save Changes
                </flux:button>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
            <div class="md:col-span-2">
                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                    <flux:input wire:model="ics_number" label="ICS Number" required />

                    <flux:select wire:model.live="contract_id" label="Contract" required>
                        <option value="">Select a contract</option>
                        @foreach($this->allContracts as $contract)
                            <option value="{{ $contract->id }}">
                                {{ $contract->contract_number }} - {{ $contract->supplier->name }}
                            </option>
                        @endforeach
                    </flux:select>

                    <flux:select wire:model="contract_item_id" label="Item from Contract" required :disabled="!$this->contract_id">
                        <option value="">Select an item</option>
                        @foreach($this->contractItems as $item)
                            <option value="{{ $item->id }}">
                                {{ $item->itemSpecification->catalogItem->name }}
                            </option>
                        @endforeach
                    </flux:select>

                    <flux:select wire:model="assigned_employee_id" label="Assign to Employee" required>
                        <option value="">Select an employee</option>
                        @foreach($this->allEmployees as $employee)
                            <option value="{{ $employee->id }}">{{ $employee->name }}</option>
                        @endforeach
                    </flux:select>

                    <flux:input wire:model="quantity" type="number" label="Quantity" min="1" required />

                    <flux:input wire:model="estimated_useful_life" type="number" label="Estimated Useful Life (Years)" min="1" required />
                </div>
            </div>
            <div class="md:col-span-1">
                <div class="space-y-6">
                    <flux:select wire:model="ics_type" label="ICS Type" required>
                        <option value="SPLV">Small Value (SPLV)</option>
                        <option value="SPHV">High Value (SPHV)</option>
                    </flux:select>

                    <flux:input wire:model="date_prepared" type="date" label="Date Prepared" required />

                    <flux:textarea wire:model="remarks" label="Remarks" rows="5" />
                </div>
            </div>
        </div>
        <div class="border-t border-stone-200 pt-6 dark:border-stone-700">
            <h3 class="text-lg font-medium text-red-600 dark:text-red-500">Danger Zone</h3>
            <p class="mt-1 text-sm text-stone-600 dark:text-stone-400">
                This action is permanent and cannot be undone.
            </p>
            <flux:button type="button" variant="danger" class="mt-4" @click="showDeleteModal = true">
                Delete this ICS Record
            </flux:button>
        </div>
    </form>

    <!-- Delete Confirmation Modal -->
    <flux:modal name="delete-ics-modal" :show="showDeleteModal" focusable class="max-w-lg">
        <div class="p-6">
            <h2 class="text-lg font-medium text-stone-900 dark:text-stone-100">
                Are you sure you want to delete this record?
            </h2>
            <p class="mt-1 text-sm text-stone-600 dark:text-stone-400">
                This action is permanent. All associated data will be removed.
            </p>
            <div class="mt-6 flex justify-end gap-x-4">
                <flux:button variant="ghost" type="button" @click="showDeleteModal = false">
                    Cancel
                </flux:button>
                <flux:button type="button" variant="danger" wire:click="destroy">
                    Yes, Delete Record
                </flux:button>
            </div>
        </div>
    </flux:modal>
</div> 
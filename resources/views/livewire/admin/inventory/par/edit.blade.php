<?php

use App\Models\ContractItem;
use App\Models\Employee;
use App\Models\ParNumber;
use App\Models\ParItemBatch;
use App\Services\ToastService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.app')] class extends Component {
    public ParNumber $par;

    // Form state
    public string $par_number = '';
    public ?int $employee_id = null;
    public ?string $date_prepared = null;
    public ?string $date_accepted = null;
    public ?string $date_acquired = null;
    public string $area_code = '';
    public string $building_code = '';
    public string $account_code = '';
    public string $inventory_code = '';
    public string $remarks = '';
    public int $quantity = 1;

    // Batch management - only for identification data
    public array $batches = [];

    public Collection $allEmployees;

    public function mount(ParNumber $par): void
    {
        if (!auth()->user()->hasAdminPermission('edit_inventory')) {
            abort(403);
        }

        $this->par = $par;
        $this->fill($par->toArray());
        
        // Format dates for form display
        $this->date_prepared = $par->date_prepared ? $par->date_prepared->format('Y-m-d') : null;
        $this->date_accepted = $par->date_accepted ? $par->date_accepted->format('Y-m-d') : null;
        $this->date_acquired = $par->date_acquired ? $par->date_acquired->format('Y-m-d') : null;
        
        $this->employee_id = $par->assigned_employee_id;

        // Load identification data batches
        foreach ($par->itemBatches as $batch) {
            $this->batches[] = [
                'id' => $batch->id,
                'identification_data' => $batch->identification_data,
            ];
        }

        // Pre-load data for select dropdowns
        $this->allEmployees = Employee::orderBy('name')->get(['id', 'name']);
    }
    
    public function addBatch(): void
    {
        $this->batches[] = [
            'id' => null, // new batch
            'identification_data' => '',
        ];
    }

    public function removeBatch(int $index): void
    {
        if (isset($this->batches[$index])) {
            array_splice($this->batches, $index, 1);
        }
    }

    public function update(): void
    {
        $validated = $this->validate([
            'par_number' => ['required', 'string', 'max:255', Rule::unique('par_number', 'par_number')->ignore($this->par->id)],
            'employee_id' => ['required', 'integer', Rule::exists('employees', 'id')],
            'quantity' => ['required', 'integer', 'min:1'],
            'date_prepared' => ['nullable', 'date'],
            'date_accepted' => ['nullable', 'date'],
            'date_acquired' => ['nullable', 'date'],
            'area_code' => ['nullable', 'string', 'max:255'],
            'building_code' => ['nullable', 'string', 'max:255'],
            'account_code' => ['nullable', 'string', 'max:255'],
            'inventory_code' => ['nullable', 'string', 'max:255'],
            'remarks' => ['nullable', 'string'],
            'batches' => ['array'],
            'batches.*.id' => ['nullable', 'integer'],
            'batches.*.identification_data' => ['nullable', 'string', 'max:65535'],
        ]);
        
        DB::transaction(function () use ($validated) {
            $this->par->update([
                'par_number' => $validated['par_number'],
                'assigned_employee_id' => $validated['employee_id'],
                'quantity' => $validated['quantity'],
                'date_prepared' => $validated['date_prepared'],
                'date_accepted' => $validated['date_accepted'],
                'date_acquired' => $validated['date_acquired'],
                'area_code' => $validated['area_code'],
                'building_code' => $validated['building_code'],
                'account_code' => $validated['account_code'],
                'inventory_code' => $validated['inventory_code'],
                'remarks' => $validated['remarks'],
            ]);
            
            // Update batches
            $existingBatchIds = collect($validated['batches'])->pluck('id')->filter();
            $this->par->itemBatches()->whereNotIn('id', $existingBatchIds)->delete();

            foreach ($validated['batches'] as $batchData) {
                if ($batchData['id']) {
                    // Update existing batch
                    ParItemBatch::where('id', $batchData['id'])->update([
                        'identification_data' => $batchData['identification_data'],
                    ]);
                } else {
                    // Create new batch
                    ParItemBatch::create([
                        'par_number_id' => $this->par->id,
                        'identification_data' => $batchData['identification_data'],
                    ]);
                }
            }
        });

        ToastService::updated($this, "PAR record");
        $this->redirect(route('admin.inventory.par.index'), navigate: true);
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
                            <flux:input wire:model="date_acquired" type="date" label="Date Acquired" />
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
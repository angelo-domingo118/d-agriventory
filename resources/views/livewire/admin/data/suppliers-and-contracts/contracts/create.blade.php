<?php

use App\Livewire\Traits\HasItemSpecifications;
use App\Models\Contract;
use App\Models\Supplier;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.app')] class extends Component {
    use HasItemSpecifications;

    public string $contract_po_ib_number = '';
    public ?int $supplier_id = null;
    public array $items = [];
    public string $previousView = 'tree';

    public function mount(): void
    {
        if (!auth()->user()->hasAdminPermission('manage_data')) {
            abort(403, 'You do not have permission to manage this data.');
        }
        
        $this->previousView = request()->query('view', 'tree');
    }

    public function addItem(): void
    {
        $this->items[] = [
            'item_specification_id' => null,
            'unit_price' => '',
            'item_type' => 'ICS',
        ];
    }

    public function removeItem(int $index): void
    {
        unset($this->items[$index]);
        $this->items = array_values($this->items);
    }

    public function save(): void
    {
        $this->validate([
            'contract_po_ib_number' => ['required', 'string', 'max:255', Rule::unique('contracts', 'contract_po_ib_number')],
            'supplier_id' => ['required', 'integer', Rule::exists('suppliers', 'id')],
            'items' => ['array', 'min:1'],
            'items.*.item_specification_id' => ['required', 'integer', Rule::exists('item_specifications', 'id')],
            'items.*.unit_price' => ['required', 'numeric', 'min:0.01'],
            'items.*.item_type' => ['required', 'string', 'in:ICS,PAR,IDR'],
        ]);

        DB::transaction(function () {
            $contract = Contract::create([
                'contract_po_ib_number' => $this->contract_po_ib_number,
                'supplier_id' => $this->supplier_id,
            ]);

            foreach ($this->items as $item) {
                $contract->contractItems()->create([
                    'item_specification_id' => $item['item_specification_id'],
                    'unit_price' => $item['unit_price'],
                    'item_type' => $item['item_type'],
                ]);
            }
        });

        session()->flash('success', 'Contract created successfully.');
        $this->redirect(route('admin.data.suppliers-and-contracts', ['currentTab' => 'contracts', 'view' => $this->previousView]), navigate: true);
    }

    #[Computed]
    public function suppliers()
    {
        return Supplier::orderBy('name')->get();
    }

    public function with(): array
    {
        return [
            'suppliers' => $this->suppliers,
            'specifications' => $this->itemSpecifications,
        ];
    }
}; ?>

<form wire:submit="save">
    <!-- Breadcrumbs -->
    <div class="flex items-center justify-between mb-4">
        <div>
            <flux:breadcrumbs class="text-2xl font-semibold">
                <flux:breadcrumbs.item :href="route('admin.dashboard')" wire:navigate icon="home" class="text-xl sm:text-2xl font-semibold text-stone-700 dark:text-stone-300" />
                <flux:breadcrumbs.item class="text-xl sm:text-2xl font-semibold text-stone-500 dark:text-stone-400">Data</flux:breadcrumbs.item>
                <flux:breadcrumbs.item :href="route('admin.data.suppliers-and-contracts', ['currentTab' => 'contracts'])" wire:navigate class="text-xl sm:text-2xl font-semibold text-stone-500 dark:text-stone-400">Suppliers & Contracts</flux:breadcrumbs.item>
                <flux:breadcrumbs.item class="text-xl sm:text-2xl font-semibold text-stone-900 dark:text-stone-100">Create Contract</flux:breadcrumbs.item>
            </flux:breadcrumbs>
        </div>
    </div>

    <div class="border-b border-stone-200 pb-5 dark:border-stone-700">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-semibold text-stone-900 dark:text-stone-100">
                    Create New Contract
                </h1>
                <p class="mt-1 text-sm text-stone-600 dark:text-stone-400">
                    Add a new contract to the system.
                </p>
            </div>
            <div class="flex items-center gap-x-4">
                <flux:button :href="route('admin.data.suppliers-and-contracts', ['currentTab' => 'contracts', 'view' => $previousView])" variant="ghost" wire:navigate>
                    Cancel
                </flux:button>
                <flux:button type="submit" variant="primary">
                    Save Contract
                </flux:button>
            </div>
        </div>
    </div>

    <div class="mt-8 space-y-8">
        <!-- Contract Details -->
        <div class="overflow-hidden rounded-lg border border-stone-200 bg-white shadow-sm dark:border-stone-700 dark:bg-stone-800">
            <div class="border-b border-stone-200 px-4 py-3 dark:border-stone-700">
                <h3 class="font-semibold text-stone-800 dark:text-stone-200">Contract Details</h3>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                    <flux:select wire:model="supplier_id" label="Supplier" required>
                        <option value="">Select a supplier</option>
                        @foreach($suppliers as $supplier)
                            <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                        @endforeach
                    </flux:select>
                    
                    <flux:input wire:model="contract_po_ib_number" label="Contract/PO/IB Number" required />
                </div>
            </div>
        </div>

        <!-- Items -->
        <div class="overflow-hidden rounded-lg border border-stone-200 bg-white shadow-sm dark:border-stone-700 dark:bg-stone-800">
            <div class="border-b border-stone-200 px-4 py-3 dark:border-stone-700">
                <h3 class="font-semibold text-stone-800 dark:text-stone-200">Contract Items</h3>
            </div>
            <div class="p-6">
                <div class="space-y-4">
                    @foreach($items as $index => $item)
                        <div wire:key="item-{{ $index }}" class="rounded-md border border-stone-200 bg-stone-50 p-4 dark:border-stone-700 dark:bg-stone-800/50">
                            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 md:grid-cols-5">
                                <div class="col-span-1 sm:col-span-2">
                                    <flux:select wire:model="items.{{ $index }}.item_specification_id" label="Item Specification" required>
                                        <option value="">Select an item specification</option>
                                        @foreach($specifications as $spec)
                                            <option value="{{ $spec['id'] }}">{{ $spec['label'] }}</option>
                                        @endforeach
                                    </flux:select>
                                </div>
                                <flux:input type="number" wire:model="items.{{ $index }}.unit_price" label="Unit Price" min="0.01" step="0.01" required />
                                <flux:select wire:model="items.{{ $index }}.item_type" label="Item Type" required>
                                    <option value="ICS">ICS</option>
                                    <option value="PAR">PAR</option>
                                    <option value="IDR">IDR</option>
                                </flux:select>
                                <div class="flex items-end">
                                    <flux:button type="button" variant="danger" wire:click="removeItem({{ $index }})">
                                        <x-flux::icon.trash class="h-5 w-5" />
                                    </flux:button>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
                
                <div class="mt-6">
                    <flux:button type="button" wire:click="addItem">Add Another Item</flux:button>
                </div>
            </div>
        </div>
    </div>
</form> 
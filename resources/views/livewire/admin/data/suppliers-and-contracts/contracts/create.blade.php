<?php

use App\Livewire\Traits\HasCatalogItems;
use App\Models\Contract;
use App\Models\ItemsCatalog;
use App\Models\Supplier;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.app')] class extends Component {
    use HasCatalogItems;

    public string $contract_number = '';
    public string $title = '';
    public ?int $supplier_id = null;
    public string $date_awarded = '';
    public ?string $description = '';
    public array $items = [];
    public string $previousView = 'tree';

    public function mount(): void
    {
        if (!auth()->user()->hasAdminPermission('manage_data')) {
            abort(403, 'You do not have permission to manage this data.');
        }
        
        $this->date_awarded = date('Y-m-d');
        $this->previousView = request()->query('view', 'tree');
    }

    public function addItem(): void
    {
        $this->items[] = [
            'item_catalog_id' => null,
            'unit_price' => '',
            'quantity' => 1,
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
            'contract_number' => ['required', 'string', 'max:50', Rule::unique('contracts', 'contract_number')],
            'title' => ['required', 'string', 'max:255'],
            'supplier_id' => ['required', 'integer', Rule::exists('suppliers', 'id')],
            'date_awarded' => ['required', 'date'],
            'description' => ['nullable', 'string'],
            'items' => ['array', 'min:1'],
            'items.*.item_catalog_id' => ['required', 'integer', Rule::exists('items_catalog', 'id')],
            'items.*.unit_price' => ['required', 'numeric', 'min:0.01'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
        ]);

        DB::transaction(function () {
            $contract = Contract::create([
                'contract_number' => $this->contract_number,
                'title' => $this->title,
                'supplier_id' => $this->supplier_id,
                'date_awarded' => $this->date_awarded,
                'description' => $this->description,
            ]);

            foreach ($this->items as $item) {
                $contract->items()->create([
                    'item_catalog_id' => $item['item_catalog_id'],
                    'unit_price' => $item['unit_price'],
                    'quantity' => $item['quantity'],
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
            'catalogItems' => $this->catalogItems,
        ];
    }
}; ?>

<form wire:submit="save">
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
                <x-action-message class="me-3" on="contract-created">
                    {{ __('Contract created successfully.') }}
                </x-action-message>
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
                        @foreach($this->suppliers as $supplier)
                            <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                        @endforeach
                    </flux:select>
                    
                    <flux:input wire:model="contract_number" label="Contract/PO/IB Number" required />
                    <flux:input wire:model="title" label="Title" required />
                    <flux:input wire:model="date_awarded" type="date" label="Date Awarded" required />
                    <flux:textarea wire:model="description" label="Description" placeholder="Enter any specific details for this contract..."></flux:textarea>
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
                                    <flux:select wire:model="items.{{ $index }}.item_catalog_id" label="Item" required>
                                        <option value="">Select an item</option>
                                        @foreach($this->catalogItems as $catalogItem)
                                            <option value="{{ $catalogItem['id'] }}">{{ $catalogItem['label'] }}</option>
                                        @endforeach
                                    </flux:select>
                                </div>
                                <flux:input type="number" wire:model="items.{{ $index }}.unit_price" label="Unit Price" min="0.01" step="0.01" required />
                                <flux:input type="number" wire:model="items.{{ $index }}.quantity" label="Quantity" min="1" step="1" required />
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
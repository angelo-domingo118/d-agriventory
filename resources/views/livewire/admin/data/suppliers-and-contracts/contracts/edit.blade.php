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
use Illuminate\Support\Facades\Cache;

new #[Layout('components.layouts.app')] class extends Component {
    use HasCatalogItems;

    public Contract $contract;
    public string $contract_number;
    public string $title;
    public int $supplier_id;
    public string $date_awarded;
    public ?string $description;
    public array $items = [];
    public string $previousView = 'tree';

    public function mount(Contract $contract): void
    {
        if (!auth()->user()->hasAdminPermission('manage_data')) {
            abort(403, 'You do not have permission to manage this data.');
        }
        
        $this->contract = $contract;
        $this->contract_number = $contract->contract_number;
        $this->title = $contract->title;
        $this->supplier_id = $contract->supplier_id;
        $this->date_awarded = $contract->date_awarded->format('Y-m-d');
        $this->description = $contract->description;
        
        $this->loadContractItems();
        $this->previousView = request()->query('view', 'tree');
    }

    public function loadContractItems(): void
    {
        $this->items = $this->contract->items->map(function ($item) {
            return [
                'id' => $item->id,
                'item_catalog_id' => $item->item_catalog_id,
                'unit_price' => $item->unit_price,
                'quantity' => $item->quantity,
                '_destroy' => false,
            ];
        })->toArray();
    }

    public function addItem(): void
    {
        $this->items[] = [
            'id' => null,
            'item_catalog_id' => null,
            'unit_price' => '',
            'quantity' => 1,
            '_destroy' => false,
        ];
    }

    public function removeItem(int $index): void
    {
        if (isset($this->items[$index]['id']) && $this->items[$index]['id']) {
            $this->items[$index]['_destroy'] = true;
        } else {
            unset($this->items[$index]);
            $this->items = array_values($this->items);
        }
    }

    public function save(): void
    {
        $this->validate([
            'contract_number' => ['required', 'string', 'max:50', Rule::unique('contracts', 'contract_number')->ignore($this->contract->id)],
            'title' => ['required', 'string', 'max:255'],
            'supplier_id' => ['required', 'integer', Rule::exists('suppliers', 'id')],
            'date_awarded' => ['required', 'date'],
            'description' => ['nullable', 'string'],
            'items' => ['array', 'min:1'],
            'items.*.item_catalog_id' => ['nullable', 'integer', Rule::exists('items_catalog', 'id')],
            'items.*.unit_price' => ['nullable', 'numeric', 'min:0.01'],
            'items.*.quantity' => ['nullable', 'integer', 'min:1'],
        ]);

        foreach ($this->items as $index => $item) {
            if (empty($item['_destroy'])) {
                if (empty($item['item_catalog_id'])) {
                    $this->addError("items.{$index}.item_catalog_id", 'The item selection is required.');
                }
                if (empty($item['unit_price'])) {
                    $this->addError("items.{$index}.unit_price", 'The unit price is required.');
                }
                if (empty($item['quantity'])) {
                    $this->addError("items.{$index}.quantity", 'The quantity is required.');
                }
            }
        }

        if ($this->getErrorBag()->isNotEmpty()) {
            return;
        }

        DB::transaction(function () {
            $this->contract->update([
                'contract_number' => $this->contract_number,
                'title' => $this->title,
                'supplier_id' => $this->supplier_id,
                'date_awarded' => $this->date_awarded,
                'description' => $this->description,
            ]);

            // Process items
            $activeItemIds = [];

            foreach ($this->items as $itemData) {
                if (!empty($itemData['_destroy'])) {
                    continue;
                }

                $item = null;
                if (!empty($itemData['id'])) {
                    $item = $this->contract->items()->find($itemData['id']);
                }

                if ($item) {
                    $item->update([
                        'item_catalog_id' => $itemData['item_catalog_id'],
                        'unit_price' => $itemData['unit_price'],
                        'quantity' => $itemData['quantity'],
                    ]);
                } else {
                    $item = $this->contract->items()->create([
                        'item_catalog_id' => $itemData['item_catalog_id'],
                        'unit_price' => $itemData['unit_price'],
                        'quantity' => $itemData['quantity'],
                    ]);
                }

                $activeItemIds[] = $item->id;
            }

            // Delete removed items
            $this->contract->items()->whereNotIn('id', $activeItemIds)->delete();
        });

        session()->flash('success', 'Contract updated successfully.');
        $this->redirect(route('admin.data.suppliers-and-contracts', ['currentTab' => 'contracts', 'view' => $this->previousView]), navigate: true);
    }

    public function deleteContract(): void
    {
        DB::transaction(function () {
            // Delete all contract items first
            $this->contract->items()->delete();
            
            // Then delete the contract
            $this->contract->delete();
        });

        session()->flash('success', 'Contract deleted successfully.');
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
                    Edit Contract
                </h1>
                <p class="mt-1 text-sm text-stone-600 dark:text-stone-400">
                    Update the details of this contract.
                </p>
            </div>
            <div class="flex items-center gap-x-4">
                <flux:button type="button" variant="danger" wire:click="deleteContract" wire:confirm="Are you sure you want to delete this contract? This action cannot be undone.">
                    Delete
                </flux:button>
                <flux:button :href="route('admin.data.suppliers-and-contracts', ['currentTab' => 'contracts', 'view' => $previousView])" variant="ghost" wire:navigate>
                    Cancel
                </flux:button>
                <flux:button type="submit" variant="primary">
                    Save Changes
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
    @if(empty($item['_destroy']))
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
    @endif
@endforeach
                </div>

                <div class="mt-6">
                    <flux:button type="button" wire:click="addItem">Add Another Item</flux:button>
                </div>
            </div>
        </div>
    </div>
</form> 
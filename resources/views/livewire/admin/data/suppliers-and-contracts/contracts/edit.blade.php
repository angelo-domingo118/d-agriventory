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

    public Contract $contract;
    public string $contract_po_ib_number;
    public ?int $supplier_id;
    public array $items = [];
    public string $previousView = 'tree';

    public function mount(Contract $contract): void
    {
        if (!auth()->user()->hasAdminPermission('manage_data')) {
            abort(403, 'You do not have permission to manage this data.');
        }
        
        $this->contract = $contract;
        $this->contract_po_ib_number = $contract->contract_po_ib_number;
        $this->supplier_id = $contract->supplier_id;
        
        $this->loadContractItems();
        $this->previousView = request()->query('view', 'tree');
    }

    public function loadContractItems(): void
    {
        $this->items = $this->contract->contractItems->map(function ($item) {
            return [
                'id' => $item->id,
                'item_specification_id' => $item->item_specification_id,
                'unit_price' => $item->unit_price,
                'item_type' => $item->item_type,
                '_destroy' => false,
            ];
        })->toArray();
    }

    public function addItem(): void
    {
        $this->items[] = [
            'id' => null,
            'item_specification_id' => null,
            'unit_price' => '',
            'item_type' => 'ICS',
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
            'contract_po_ib_number' => ['required', 'string', 'max:255', Rule::unique('contracts', 'contract_po_ib_number')->ignore($this->contract->id)],
            'supplier_id' => ['required', 'integer', Rule::exists('suppliers', 'id')],
            'items' => ['array', 'min:1'],
            'items.*.item_specification_id' => ['nullable', 'integer', Rule::exists('item_specifications', 'id')],
            'items.*.unit_price' => ['nullable', 'numeric', 'min:0.01'],
            'items.*.item_type' => ['nullable', 'string', 'in:ICS,PAR,IDR'],
        ]);

        foreach ($this->items as $index => $item) {
            if (empty($item['_destroy'])) {
                if (empty($item['item_specification_id'])) {
                    $this->addError("items.{$index}.item_specification_id", 'The item selection is required.');
                }
                if (empty($item['unit_price'])) {
                    $this->addError("items.{$index}.unit_price", 'The unit price is required.');
                }
                if (empty($item['item_type'])) {
                    $this->addError("items.{$index}.item_type", 'The item type is required.');
                }
            }
        }

        if ($this->getErrorBag()->isNotEmpty()) {
            return;
        }

        DB::transaction(function () {
            $this->contract->update([
                'contract_po_ib_number' => $this->contract_po_ib_number,
                'supplier_id' => $this->supplier_id,
            ]);

            $activeItemIds = [];

            foreach ($this->items as $itemData) {
                if (!empty($itemData['_destroy'])) {
                    continue;
                }

                $item = null;
                if (!empty($itemData['id'])) {
                    $item = $this->contract->contractItems()->find($itemData['id']);
                }

                if ($item) {
                    $item->update([
                        'item_specification_id' => $itemData['item_specification_id'],
                        'unit_price' => $itemData['unit_price'],
                        'item_type' => $itemData['item_type'],
                    ]);
                } else {
                    $item = $this->contract->contractItems()->create([
                        'item_specification_id' => $itemData['item_specification_id'],
                        'unit_price' => $itemData['unit_price'],
                        'item_type' => $itemData['item_type'],
                    ]);
                }

                $activeItemIds[] = $item->id;
            }

            $this->contract->contractItems()->whereNotIn('id', $activeItemIds)->delete();
        });

        session()->flash('success', 'Contract updated successfully.');
        $this->redirect(route('admin.data.suppliers-and-contracts', ['currentTab' => 'contracts', 'view' => $this->previousView]), navigate: true);
    }

    public function deleteContract(): void
    {
        DB::transaction(function () {
            $this->contract->contractItems()->delete();
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
            'specifications' => $this->itemSpecifications,
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
                <flux:button :href="route('admin.data.suppliers-and-contracts', ['currentTab' => 'contracts', 'view' => $this->previousView])" variant="ghost" wire:navigate>
                    Cancel
                </flux:button>
                <flux:button type="submit" variant="primary">
                    Save Changes
                </flux:button>
            </div>
        </div>
    </div>

    <div class="mt-8 space-y-8">
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
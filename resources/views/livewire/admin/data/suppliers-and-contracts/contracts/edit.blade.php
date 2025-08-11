<?php

use App\Livewire\Traits\HasItemSpecifications;
use App\Models\Contract;
use App\Models\Supplier;
use App\Services\ToastService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Volt\Component;
use Flux\Flux;

new class extends Component {
    use HasItemSpecifications;

    public Contract $contract;
    public string $contract_po_ib_number;
    public ?int $supplier_id;
    public array $items = [];

    public function mount(Contract $contract): void
    {
        if (!auth()->user()->hasAdminPermission('manage_data')) {
            abort(403, 'You do not have permission to manage this data.');
        }
        
        $this->contract = $contract;
        $this->contract_po_ib_number = $contract->contract_po_ib_number;
        $this->supplier_id = $contract->supplier_id;
        
        $this->loadContractItems();
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

        // Show success toast
        ToastService::updated($this, 'Contract');

        // Close the modal and refresh the parent component
        $this->dispatch('contract-updated');
        Flux::modal('edit-contract')->close();
    }

    public function confirmDeleteContract(): void
    {        
        // Show the delete confirmation modal
        Flux::modal('delete-contract-confirmation')->show();
    }

    public function cancelDeleteContract(): void
    {
        // Close the delete confirmation modal
        Flux::modal('delete-contract-confirmation')->close();
    }

    public function deleteContract(): void
    {
        try {
            DB::transaction(function () {
                $this->contract->contractItems()->delete();
                $this->contract->delete();
            });

            // Show success toast
            ToastService::deleted($this, 'Contract');

            // Close both modals and refresh the parent component
            Flux::modal('delete-contract-confirmation')->close();
            Flux::modal('edit-contract')->close();
            $this->dispatch('contract-deleted');
        } catch (\Exception $e) {
            // Handle any errors during deletion
            ToastService::error($this, 'An error occurred while deleting the contract. Please try again.');
            Flux::modal('delete-contract-confirmation')->close();
        }
    }

    #[On('call-delete-contract')]
    public function handleDeleteContract(): void
    {
        $this->deleteContract();
    }

    #[On('call-cancel-delete-contract')]
    public function handleCancelDeleteContract(): void
    {
        $this->cancelDeleteContract();
    }

    public function cancel(): void
    {
        Flux::modal('edit-contract')->close();
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

<div class="space-y-6">
    <div>
        <flux:heading size="lg">Edit Contract</flux:heading>
        <flux:text class="mt-2">Update the details of this contract.</flux:text>
    </div>

    <form wire:submit="save" class="space-y-6">
        <!-- Contract Details -->
        <div class="space-y-4">
            <flux:heading size="md">Contract Details</flux:heading>
            
            <flux:select wire:model="supplier_id" label="Supplier" placeholder="Select a supplier" required>
                <option value="">Select a supplier</option>
                @foreach($suppliers as $supplier)
                    <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                @endforeach
            </flux:select>
            
            <flux:input wire:model="contract_po_ib_number" label="Contract/PO/IB Number" placeholder="Enter contract number" required />
        </div>

        <!-- Contract Items -->
        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <flux:heading size="md">Contract Items</flux:heading>
                <flux:button type="button" variant="ghost" wire:click="addItem">
                    <x-flux::icon.plus class="mr-1.5 h-4 w-4" />
                    Add Item
                </flux:button>
            </div>
            
            <div class="max-h-64 overflow-y-auto space-y-3">
                @foreach($items as $index => $item)
                    @if(empty($item['_destroy']))
                        <div wire:key="item-{{ $index }}" class="rounded-md border border-stone-200 bg-stone-50 p-3 dark:border-stone-700 dark:bg-stone-800/50">
                            <div class="grid grid-cols-1 gap-3 sm:grid-cols-4">
                                <div class="sm:col-span-2">
                                    <flux:select wire:model="items.{{ $index }}.item_specification_id" label="Item Specification" required>
                                        <option value="">Select item</option>
                                        @foreach($specifications as $spec)
                                            <option value="{{ $spec['id'] }}">{{ $spec['label'] }}</option>
                                        @endforeach
                                    </flux:select>
                                </div>
                                <flux:input type="number" wire:model="items.{{ $index }}.unit_price" label="Unit Price" min="0.01" step="0.01" required />
                                <div class="flex gap-2 items-end">
                                    <flux:select wire:model="items.{{ $index }}.item_type" label="Type" required>
                                        <option value="">Type</option>
                                        <option value="ICS">ICS</option>
                                        <option value="PAR">PAR</option>
                                        <option value="IDR">IDR</option>
                                    </flux:select>
                                    <flux:button type="button" variant="danger" wire:click="removeItem({{ $index }})" class="p-2">
                                        <x-flux::icon.trash class="h-4 w-4" />
                                    </flux:button>
                                </div>
                            </div>
                        </div>
                    @endif
                @endforeach
            </div>
        </div>

        <!-- Modal Actions -->
        <div class="flex gap-2 pt-4 border-t border-stone-200 dark:border-stone-700">
            <flux:button type="button" variant="danger" wire:click="confirmDeleteContract">
                Delete
            </flux:button>
            <flux:spacer />
            <flux:button type="button" variant="ghost" wire:click="cancel">
                Cancel
            </flux:button>
            <flux:button type="submit" variant="primary" wire:loading.attr="disabled">
                <span wire:loading.remove>Save Changes</span>
                <span wire:loading>Saving...</span>
            </flux:button>
        </div>
    </form>
</div> 
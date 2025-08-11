<?php

use App\Livewire\Traits\HasItemSpecifications;
use App\Models\ContractItem;
use App\Models\Contract;
use App\Services\ToastService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Volt\Component;
use Flux\Flux;

new class extends Component {
    use HasItemSpecifications;

    public ContractItem $contractItem;
    public ?int $contract_id;
    public ?int $item_specification_id;
    public string $unit_price;
    public string $item_type;

    public function mount(ContractItem $contractItem): void
    {
        if (!auth()->user()->hasAdminPermission('manage_data')) {
            abort(403, 'You do not have permission to manage this data.');
        }
        
        $this->contractItem = $contractItem;
        $this->contract_id = $contractItem->contract_id;
        $this->item_specification_id = $contractItem->item_specification_id;
        $this->unit_price = (string) $contractItem->unit_price;
        $this->item_type = $contractItem->item_type;
    }

    public function save(): void
    {
        $this->validate([
            'contract_id' => ['required', 'integer', Rule::exists('contracts', 'id')],
            'item_specification_id' => ['required', 'integer', Rule::exists('item_specifications', 'id')],
            'unit_price' => ['required', 'numeric', 'min:0.01'],
            'item_type' => ['required', 'string', 'in:ICS,PAR,IDR'],
        ]);

        $this->contractItem->update([
            'contract_id' => $this->contract_id,
            'item_specification_id' => $this->item_specification_id,
            'unit_price' => $this->unit_price,
            'item_type' => $this->item_type,
        ]);

        // Show success toast
        ToastService::updated($this, 'Contract Item');

        // Close the modal and refresh the parent component
        $this->dispatch('contract-item-updated');
        Flux::modal('edit-contract-item')->close();
    }

    public function confirmDeleteContractItem(): void
    {        
        // Show the delete confirmation modal
        Flux::modal('delete-contract-item-confirmation')->show();
    }

    public function cancelDeleteContractItem(): void
    {
        // Close the delete confirmation modal
        Flux::modal('delete-contract-item-confirmation')->close();
    }

    public function deleteContractItem(): void
    {
        try {
            // Check for any related records that would prevent deletion
            $hasRelatedRecords = $this->contractItem->icsNumbers()->exists() 
                || $this->contractItem->parNumbers()->exists() 
                || $this->contractItem->idrNumbers()->exists();

            if ($hasRelatedRecords) {
                ToastService::error($this, 'Cannot delete this contract item because it has associated inventory records (ICS, PAR, or IDR numbers). Please remove those records first.');
                Flux::modal('delete-contract-item-confirmation')->close();
                return;
            }

            $this->contractItem->delete();

            // Show success toast
            ToastService::deleted($this, 'Contract Item');

            // Close both modals and refresh the parent component
            Flux::modal('delete-contract-item-confirmation')->close();
            Flux::modal('edit-contract-item')->close();
            $this->dispatch('contract-item-deleted');
        } catch (\Exception $e) {
            // Handle any errors during deletion
            ToastService::error($this, 'An error occurred while deleting the contract item. Please try again.');
            Flux::modal('delete-contract-item-confirmation')->close();
        }
    }

    #[On('call-delete-contract-item')]
    public function handleDeleteContractItem(): void
    {
        $this->deleteContractItem();
    }

    #[On('call-cancel-delete-contract-item')]
    public function handleCancelDeleteContractItem(): void
    {
        $this->cancelDeleteContractItem();
    }

    public function cancel(): void
    {
        Flux::modal('edit-contract-item')->close();
    }

    #[Computed]
    public function contracts()
    {
        return Contract::with('supplier')->orderBy('contract_po_ib_number')->get();
    }

    public function with(): array
    {
        return [
            'contracts' => $this->contracts,
            'specifications' => $this->itemSpecifications,
        ];
    }
}; ?>

<div class="mx-auto max-w-lg">
    <form wire:submit="save" class="space-y-6">
        <!-- Contract Item Details -->
        <div class="space-y-4">
            <flux:select wire:model="contract_id" label="Contract" required>
                <option value="">Select a contract</option>
                @foreach($contracts as $contract)
                    <option value="{{ $contract->id }}">{{ $contract->contract_po_ib_number }} - {{ $contract->supplier->name }}</option>
                @endforeach
            </flux:select>
            
            <flux:select wire:model="item_specification_id" label="Item Specification" required>
                <option value="">Select an item specification</option>
                @foreach($specifications as $spec)
                    <option value="{{ $spec['id'] }}">{{ $spec['label'] }}</option>
                @endforeach
            </flux:select>
            
            <flux:input type="number" wire:model="unit_price" label="Unit Price (₱)" placeholder="0.00" min="0.01" step="0.01" required />
            
            <flux:select wire:model="item_type" label="Item Type" required>
                <option value="ICS">ICS - Inventory Custodian Slip</option>
                <option value="PAR">PAR - Property Acknowledgment Receipt</option>
                <option value="IDR">IDR - Inventory Delivery Receipt</option>
            </flux:select>
        </div>

        <!-- Modal Actions -->
        <div class="flex gap-2 pt-4 border-t border-stone-200 dark:border-stone-700">
            <flux:button type="button" variant="danger" wire:click="confirmDeleteContractItem">
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

<?php
use App\Models\Supplier;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Volt\Component;

new class extends Component {
    public string $search = '';

    public function mount(): void
    {
        if (!auth()->user()->hasAdminPermission('manage_data')) {
            abort(403, 'You do not have permission to manage this data.');
        }
    }

    #[On('supplier-created')]
    #[On('contract-created')]
    public function refreshData(): void
    {
        // Force refresh of computed properties
        unset($this->suppliers);
        unset($this->expandableIds);
        $this->dispatch('$refresh');
    }

    #[Computed]
    public function isSearching(): bool
    {
        return filled($this->search);
    }

    #[Computed]
    public function suppliers(): Collection
    {
        $allSuppliers = Supplier::with([
            'contracts' => function ($query) {
                $query->withCount('contractItems as items_count')->orderBy('contract_po_ib_number');
            },
            'contracts.contractItems.itemSpecification.itemCatalog',
        ])
            ->withCount('contracts')
            ->orderBy('name')
            ->get();

        if (blank($this->search)) {
            return $allSuppliers;
        }

        $search = strtolower($this->search);

        return $allSuppliers
            ->map(function ($supplier) use ($search) {
                if (str_contains(strtolower($supplier->name), $search)) {
                    $supplier->contracts->each(function ($contract) {
                        $contract->items_count = $contract->contractItems->count();
                    });
                    $supplier->contracts_count = $supplier->contracts->count();
                    return $supplier;
                }

                $supplier->contracts = $supplier->contracts
                    ->map(function ($contract) use ($search) {
                        if (str_contains(strtolower($contract->contract_po_ib_number), $search)) {
                            $contract->items_count = $contract->contractItems->count();
                            return $contract;
                        }

                        $contract->contractItems = $contract->contractItems
                            ->filter(function ($item) use ($search) {
                                $itemName = $item->itemSpecification->itemCatalog->name ?? '';
                                return str_contains(strtolower($itemName), $search);
                            });

                        $contract->items_count = $contract->contractItems->count();
                        return $contract;
                    })
                    ->filter(function ($contract) use ($search) {
                        return str_contains(strtolower($contract->contract_po_ib_number), $search) || $contract->contractItems->isNotEmpty();
                    });

                $supplier->contracts_count = $supplier->contracts->count();
                return $supplier;
            })
            ->filter(fn($supplier) => str_contains(strtolower($supplier->name), $search) || $supplier->contracts->isNotEmpty());
    }


    #[Computed]
    public function expandableIds(): array
    {
        $ids = [];

        foreach ($this->suppliers() as $supplier) {
            if ($supplier->contracts_count > 0) {
                $ids[] = 'supplier-' . $supplier->id;

                foreach ($supplier->contracts as $contract) {
                    if ($contract->items_count > 0) {
                        $ids[] = 'contract-' . $contract->id;
                    }
                }
            }
        }

        return $ids;
    }
}; ?>

<div>
    <x-tree.index
        :items="$this->suppliers"
        :expandable-ids="$this->expandableIds"
        :is-searching="$this->isSearching"
        empty-message="No Suppliers Found"
        create-modal-name="create-supplier"
        create-text="Create Supplier"
    >
    @foreach ($this->suppliers as $supplier)
        <x-tree.item
            :id="'supplier-'.$supplier->id"
            :title="$supplier->name"
            :subtitle="$supplier->contracts_count . ' contracts'"
            :edit-url="route('admin.data.suppliers-and-contracts.suppliers.edit', $supplier)"
            add-modal-name="create-contract"
            add-text="Add Contract"
            :has-children="$supplier->contracts_count > 0"
            :search-terms="[$this->search]"
        >
            @forelse($supplier->contracts as $contract)
                <x-tree.item
                    :id="'contract-'.$contract->id"
                    :title="$contract->contract_po_ib_number"
                    :subtitle="$contract->created_at->format('M d, Y') . ' | ' . $contract->items_count . ' items'"
                    :edit-url="route('admin.data.suppliers-and-contracts.contracts.edit', $contract)"
                    :level="1"
                    :has-children="$contract->items_count > 0"
                    :search-terms="[$this->search]"
                >
                    @forelse($contract->contractItems as $item)
                        <x-tree.item
                            :id="'item-'.$item->id"
                            :title="$item->itemSpecification->itemCatalog->name"
                            :subtitle="'Unit Price: ₱' . number_format($item->unit_price, 2) . ' | Qty: ' . $item->quantity"
                            :level="2"
                            :has-children="false"
                            :search-terms="[$this->search]"
                        />
                    @empty
                        <p class="py-2 text-sm italic text-stone-500 dark:text-stone-400">No items in this contract.</p>
                    @endforelse
                </x-tree.item>
            @empty
                <p class="text-sm italic text-stone-500 dark:text-stone-400">No contracts found for this supplier.</p>
            @endforelse
        </x-tree.item>
    @endforeach
    </x-tree.index>

    <!-- Create Supplier Modal -->
    <x-admin.modal-form-wrapper name="create-supplier" maxWidth="2xl">
        <livewire:admin.data.suppliers-and-contracts.suppliers.create />
    </x-admin.modal-form-wrapper>

    <!-- Create Contract Modal -->
    <x-admin.modal-form-wrapper name="create-contract" maxWidth="2xl">
        <livewire:admin.data.suppliers-and-contracts.contracts.create />
    </x-admin.modal-form-wrapper>
</div> 
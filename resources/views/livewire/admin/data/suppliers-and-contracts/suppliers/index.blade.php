<?php

use App\Models\Supplier;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new #[Layout('components.layouts.app')] class extends Component {
    use WithPagination;

    public ?Supplier $editing = null;
    public bool $showCreateModal = false;
    public string $search = '';

    public function mount()
    {
        if (!auth()->user()->hasAdminPermission('manage_data')) {
            abort(403, 'You do not have permission to manage this data.');
        }
    }

    public function getSuppliersProperty()
    {
        return Supplier::when($this->search, function ($query, $search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('address', 'like', "%{$search}%")
                    ->orWhere('contact_person', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            })
            ->orderBy('name')
            ->paginate(10);
    }

    public function newSupplier(): void
    {
        $this->editing = new Supplier();
        $this->showCreateModal = true;
    }

    public function edit(Supplier $supplier): void
    {
        $this->editing = $supplier;
        $this->showCreateModal = true;
    }

    public function save(): void
    {
        $validated = $this->validate([
            'editing.name' => ['required', 'string', 'max:255', Rule::unique('suppliers', 'name')->ignore($this->editing->id)],
            'editing.address' => ['nullable', 'string', 'max:255'],
            'editing.contact_person' => ['nullable', 'string', 'max:255'],
            'editing.email' => ['nullable', 'email', 'max:255', Rule::unique('suppliers', 'email')->ignore($this->editing->id)],
            'editing.phone' => ['nullable', 'string', 'max:50'],
        ]);

        $this->editing->save();

        $this->showCreateModal = false;
        $this->dispatch('supplier-saved');
        session()->flash('success', 'Supplier saved successfully.');
    }

    public function with(): array
    {
        return [
            'suppliers' => $this->suppliers,
        ];
    }
}; ?>

<div>
    <div class="flex items-center justify-between">
        <h2 class="text-lg font-semibold text-stone-700 dark:text-stone-200">Suppliers</h2>
        <div class="flex items-center gap-x-4">
            <flux:input wire:model.live.debounce.300ms="search" placeholder="Search suppliers..." />
            <flux:button wire:click="newSupplier" variant="primary">New Supplier</flux:button>
        </div>
    </div>

    <div class="mt-4 overflow-hidden rounded-lg border border-stone-200 bg-white shadow-sm dark:border-stone-700 dark:bg-stone-800">
        <table class="min-w-full divide-y divide-stone-200 dark:divide-stone-700">
            <thead class="bg-stone-50 dark:bg-stone-800">
                <tr>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-stone-500 dark:text-stone-400">Name</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-stone-500 dark:text-stone-400">Contact Person</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-stone-500 dark:text-stone-400">Email</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-stone-500 dark:text-stone-400">Phone</th>
                    <th scope="col" class="relative px-6 py-3">
                        <span class="sr-only">Edit</span>
                    </th>
                </tr>
            </thead>
            <tbody class="divide-y divide-stone-200 bg-white dark:divide-stone-800 dark:bg-stone-900">
                @forelse($suppliers as $supplier)
                    <tr wire:key="supplier-{{ $supplier->id }}">
                        <td class="whitespace-nowrap px-6 py-4 text-sm font-medium text-stone-900 dark:text-stone-100">{{ $supplier->name }}</td>
                        <td class="whitespace-nowrap px-6 py-4 text-sm text-stone-500 dark:text-stone-400">{{ $supplier->contact_person }}</td>
                        <td class="whitespace-nowrap px-6 py-4 text-sm text-stone-500 dark:text-stone-400">{{ $supplier->email }}</td>
                        <td class="whitespace-nowrap px-6 py-4 text-sm text-stone-500 dark:text-stone-400">{{ $supplier->phone }}</td>
                        <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-medium">
                            <flux:button wire:click="edit({{ $supplier->id }})" variant="ghost" class="text-primary-600 hover:text-primary-900 dark:text-primary-400 dark:hover:text-primary-200">Edit</flux:button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-6 py-12 text-center text-sm text-stone-500 dark:text-stone-400">
                            No suppliers found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $suppliers->links() }}
    </div>

    <!-- Create/Edit Modal -->
    @if($editing)
        <flux:modal :show="$showCreateModal" max-width="xl" @close="$set('showCreateModal', false)">
            <form wire:submit.prevent="save">
                <x-slot:title>
                    {{ $editing->exists ? 'Edit' : 'Create' }} Supplier
                </x-slot:title>

                <div class="space-y-4 p-6">
                    <flux:input wire:model="editing.name" label="Supplier Name" required />
                    <flux:input wire:model="editing.address" label="Address" />
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <flux:input wire:model="editing.contact_person" label="Contact Person" />
                        <flux:input wire:model="editing.email" label="Email Address" type="email" />
                        <flux:input wire:model="editing.phone" label="Phone Number" />
                    </div>
                </div>

                <x-slot:footer>
                    <div class="flex justify-end gap-x-4">
                        <flux:button variant="ghost" @click="$set('showCreateModal', false)">Cancel</flux:button>
                        <flux:button type="submit" variant="primary">Save</flux:button>
                    </div>
                </x-slot:footer>
            </form>
        </flux:modal>
    @endif
</div> 
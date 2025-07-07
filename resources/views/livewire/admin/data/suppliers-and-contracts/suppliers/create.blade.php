<?php

use App\Models\Supplier;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.app')] class extends Component {
    public Supplier $supplier;

    public function mount(): void
    {
        if (!auth()->user()->hasAdminPermission('manage_data')) {
            abort(403, 'You do not have permission to manage this data.');
        }
        $this->supplier = new Supplier();
    }

    public function save(): void
    {
        $this->validate([
            'supplier.name' => ['required', 'string', 'max:255', Rule::unique('suppliers', 'name')->where(fn ($query) => $query->whereNull('deleted_at'))],
            'supplier.address' => ['nullable', 'string', 'max:255'],
            'supplier.contact_person' => ['nullable', 'string', 'max:255'],
            'supplier.email' => ['nullable', 'email', 'max:255', Rule::unique('suppliers', 'email')->where(fn ($query) => $query->whereNull('deleted_at'))],
            'supplier.phone' => ['nullable', 'string', 'max:50'],
        ]);

        $this->supplier->save();

        session()->flash('success', 'Supplier created successfully.');
        $this->redirectRoute('admin.data.suppliers-and-contracts.index', ['currentTab' => 'suppliers']);
    }
}; ?>

<div>
    <div class="border-b border-stone-200 pb-5 dark:border-stone-700">
        <h1 class="text-2xl font-semibold text-stone-900 dark:text-stone-100">
            Create Supplier
        </h1>
        <p class="mt-1 text-sm text-stone-600 dark:text-stone-400">
            Add a new supplier to the database.
        </p>
    </div>

    <div class="mb-4 flex items-center gap-x-4">
        <a href="{{ route('admin.data.suppliers-and-contracts') }}"
            class="flex items-center gap-x-2 text-sm text-stone-600 dark:text-stone-400 hover:text-stone-900 dark:hover:text-stone-100">
            <x-flux::icon.arrow-left class="h-4 w-4" />
            Back
        </a>
    </div>

    <form wire:submit.prevent="save" class="mt-8">
        <div class="max-w-2xl">
            <div class="space-y-6">
                <flux:input wire:model="supplier.name" label="Supplier Name" required />
                <flux:input wire:model="supplier.address" label="Address" />
                <flux:input wire:model="supplier.contact_person" label="Contact Person" />
                <flux:input wire:model="supplier.email" label="Email Address" type="email" />
                <flux:input wire:model="supplier.phone" label="Phone Number" />
            </div>

            <div class="mt-8 flex justify-end gap-x-4">
                <flux:button variant="ghost" :href="route('admin.data.suppliers-and-contracts.suppliers.index')" wire:navigate>Cancel</flux:button>
                <flux:button type="submit" variant="primary">Create Supplier</flux:button>
            </div>
        </div>
    </form>
</div> 
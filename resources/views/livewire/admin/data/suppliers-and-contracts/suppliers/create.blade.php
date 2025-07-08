<?php

use App\Models\Supplier;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.app')] class extends Component {
    public string $name = '';
    public string $address = '';
    public string $contact_person = '';
    public string $email = '';
    public string $phone = '';

    public function mount(): void
    {
        if (!auth()->user()->hasAdminPermission('manage_data')) {
            abort(403, 'You do not have permission to manage this data.');
        }
    }

    public function save(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('suppliers', 'name')->where(fn ($query) => $query->whereNull('deleted_at'))],
            'address' => ['nullable', 'string', 'max:255'],
            'contact_person' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('suppliers', 'email')->where(fn ($query) => $query->whereNull('deleted_at'))],
            'phone' => ['nullable', 'string', 'max:50'],
        ]);

        try {
            Supplier::create($validated);

            session()->flash('success', 'Supplier created successfully.');
            $this->redirectRoute('admin.data.suppliers-and-contracts', ['currentTab' => 'suppliers']);
        } catch (\Exception $e) {
            session()->flash('error', 'There was an error creating the supplier. Please try again.');
        }
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
                <flux:input wire:model="name" label="Supplier Name" required />
                <flux:input wire:model="address" label="Address" />
                <flux:input wire:model="contact_person" label="Contact Person" />
                <flux:input wire:model="email" label="Email Address" type="email" />
                <flux:input wire:model="phone" label="Phone Number" />
            </div>

            <div class="mt-8 flex justify-end gap-x-4">
                <flux:button variant="ghost" :href="route('admin.data.suppliers-and-contracts.suppliers.index')" wire:navigate>Cancel</flux:button>
                <flux:button type="submit" variant="primary">Create Supplier</flux:button>
            </div>
        </div>
    </form>
</div> 
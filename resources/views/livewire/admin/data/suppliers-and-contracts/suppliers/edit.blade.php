<?php

use App\Models\Supplier;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.app')] class extends Component {
    public Supplier $supplier;
    public string $name;
    public string $address;
    public string $contact_person;
    public string $email;
    public string $phone;
    public string $previousView = 'tree';

    public function mount(Supplier $supplier): void
    {
        if (!auth()->user()->hasAdminPermission('manage_data')) {
            abort(403, 'You do not have permission to manage this data.');
        }
        $this->supplier = $supplier;
        $this->name = $supplier->name;
        $this->address = $supplier->address ?? '';
        $this->contact_person = $supplier->contact_person ?? '';
        $this->email = $supplier->email ?? '';
        $this->phone = $supplier->phone ?? '';
        
        $this->previousView = request()->query('view', 'tree');
    }

    public function save(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('suppliers', 'name')->ignore($this->supplier->id)],
            'address' => ['nullable', 'string', 'max:255'],
            'contact_person' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('suppliers', 'email')->ignore($this->supplier->id)],
            'phone' => ['nullable', 'string', 'max:50'],
        ]);

        $this->supplier->update($validated);

        session()->flash('success', 'Supplier updated successfully.');
        $this->redirect(route('admin.data.suppliers-and-contracts', ['currentTab' => 'suppliers', 'view' => $this->previousView]), navigate: true);
    }

    public function delete(): void
    {
        DB::transaction(function () {
            // Check if the supplier has any contracts
            if ($this->supplier->contracts()->exists()) {
                session()->flash('error', 'Cannot delete a supplier that has contracts.');
                return;
            }

            $this->supplier->delete();
            session()->flash('success', 'Supplier deleted successfully.');
            $this->redirect(route('admin.data.suppliers-and-contracts', ['currentTab' => 'suppliers', 'view' => $this->previousView]), navigate: true);
        });
    }
}; ?>

<form wire:submit="save">
    <!-- Breadcrumbs -->
    <div class="flex items-center justify-between mb-4">
        <div>
            <flux:breadcrumbs class="text-2xl font-semibold">
                <flux:breadcrumbs.item :href="route('admin.dashboard')" wire:navigate icon="home" class="text-xl sm:text-2xl font-semibold text-stone-700 dark:text-stone-300" />
                <flux:breadcrumbs.item class="text-xl sm:text-2xl font-semibold text-stone-500 dark:text-stone-400">Data</flux:breadcrumbs.item>
                <flux:breadcrumbs.item :href="route('admin.data.suppliers-and-contracts', ['currentTab' => 'suppliers'])" wire:navigate class="text-xl sm:text-2xl font-semibold text-stone-500 dark:text-stone-400">Suppliers & Contracts</flux:breadcrumbs.item>
                <flux:breadcrumbs.item class="text-xl sm:text-2xl font-semibold text-stone-900 dark:text-stone-100">Edit Supplier</flux:breadcrumbs.item>
            </flux:breadcrumbs>
        </div>
    </div>

    <div class="border-b border-stone-200 pb-5 dark:border-stone-700">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-semibold text-stone-900 dark:text-stone-100">
                    Edit Supplier
                </h1>
                <p class="mt-1 text-sm text-stone-600 dark:text-stone-400">
                    Update the details for this supplier.
                </p>
            </div>
            <div class="flex items-center gap-x-4">
                <flux:button type="button" variant="danger" wire:click="delete" wire:confirm="Are you sure you want to delete this supplier? This action cannot be undone.">
                    Delete
                </flux:button>
                <flux:button :href="route('admin.data.suppliers-and-contracts', ['currentTab' => 'suppliers', 'view' => $this->previousView])" variant="ghost" wire:navigate>
                    Cancel
                </flux:button>
                <flux:button type="submit" variant="primary">
                    Save Changes
                </flux:button>
            </div>
        </div>
    </div>

    <div class="mt-8">
        <div class="grid grid-cols-1 gap-8">
            <div class="overflow-hidden rounded-lg border border-stone-200 bg-white shadow-sm dark:border-stone-700 dark:bg-stone-800">
                <div class="border-b border-stone-200 px-4 py-3 dark:border-stone-700">
                    <h3 class="font-semibold text-stone-800 dark:text-stone-200">Supplier Details</h3>
                </div>
                <div class="p-6">
                    <div class="max-w-2xl">
                        <div class="space-y-6">
                            <flux:input wire:model="name" label="Supplier Name" required />
                            <flux:input wire:model="address" label="Address" />
                            <flux:input wire:model="contact_person" label="Contact Person" />
                            <flux:input wire:model="email" label="Email Address" type="email" />
                            <flux:input wire:model="phone" label="Phone Number" />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form> 
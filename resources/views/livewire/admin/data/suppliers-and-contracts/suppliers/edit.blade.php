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
        $this->redirectRoute('admin.data.suppliers-and-contracts', ['currentTab' => 'suppliers']);
    }

    public function delete(): void
    {
        DB::transaction(function () {
            if ($this->supplier->contracts()->exists()) {
                session()->flash('error', 'Cannot delete a supplier with active contracts.');
                return;
            }

            $this->supplier->delete();

            session()->flash('success', 'Supplier deleted successfully.');
            $this->redirectRoute('admin.data.suppliers-and-contracts', ['currentTab' => 'suppliers']);
        });
    }
}; ?>

<div>
    <div class="border-b border-stone-200 pb-5 dark:border-stone-700">
        <h1 class="text-2xl font-semibold text-stone-900 dark:text-stone-100">
            Edit Supplier
        </h1>
        <p class="mt-1 text-sm text-stone-600 dark:text-stone-400">
            Update the details for this supplier.
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
        <div
            class="space-y-6 rounded-lg border border-stone-200 bg-white p-6 shadow-sm dark:border-stone-700 dark:bg-stone-800">
            <h3 class="text-lg font-semibold text-stone-900 dark:text-stone-100">Supplier Details</h3>
            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <flux:input wire:model="name" label="Supplier Name" required />
                </div>
                <div class="sm:col-span-2">
                    <flux:input wire:model="contact_person" label="Contact Person" />
                </div>
                <flux:input wire:model="email" label="Email" type="email" />
                <flux:input wire:model="phone" label="Phone" />
                <div class="sm:col-span-2">
                    <flux:textarea wire:model="address" label="Address" />
                </div>
            </div>
        </div>

        <div class="mt-8 flex justify-end gap-x-4">
            <a href="{{ route('admin.data.suppliers-and-contracts') }}" class="text-sm text-stone-600 dark:text-stone-400 hover:text-stone-900 dark:hover:text-stone-100">Cancel</a>
            <flux:button type="submit" variant="primary">Save Changes</flux:button>
        </div>
    </form>
</div> 
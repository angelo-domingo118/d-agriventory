<?php

use App\Models\Supplier;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Volt\Component;
use Flux\Flux;

new class extends Component {
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

        // Close the modal and refresh the parent component
        $this->dispatch('supplier-updated');
        Flux::modal('edit-supplier')->close();
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
            
            // Close the modal and refresh the parent component
            $this->dispatch('supplier-deleted');
            Flux::modal('edit-supplier')->close();
        });
    }

    public function cancel(): void
    {
        Flux::modal('edit-supplier')->close();
    }
}; ?>

<div class="space-y-6">
    <div>
        <flux:heading size="lg">Edit Supplier</flux:heading>
        <flux:text class="mt-2">Update the details for this supplier.</flux:text>
    </div>

    <form wire:submit="save" class="space-y-4">
        <flux:input wire:model="name" label="Supplier Name" placeholder="Enter supplier name" required />
        <flux:input wire:model="address" label="Address" placeholder="Enter supplier address" />
        <flux:input wire:model="contact_person" label="Contact Person" placeholder="Enter contact person name" />
        <flux:input wire:model="email" label="Email Address" type="email" placeholder="Enter email address" />
        <flux:input wire:model="phone" label="Phone Number" placeholder="Enter phone number" />
        
        <div class="flex gap-2 pt-4 border-t border-stone-200 dark:border-stone-700">
            <flux:button type="button" variant="danger" wire:click="delete" wire:confirm="Are you sure you want to delete this supplier? This action cannot be undone.">
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
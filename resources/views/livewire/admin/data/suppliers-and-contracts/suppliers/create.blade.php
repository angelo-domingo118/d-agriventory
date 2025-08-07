<?php

use App\Models\Supplier;
use Illuminate\Validation\Rule;
use Livewire\Volt\Component;
use Flux\Flux;

new class extends Component {
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
            'name' => ['required', 'string', 'max:255', Rule::unique('suppliers', 'name')],
            'address' => ['nullable', 'string', 'max:255'],
            'contact_person' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('suppliers', 'email')],
            'phone' => ['nullable', 'string', 'max:50'],
        ]);

        Supplier::create($validated);

        // Close the modal and refresh the parent component
        $this->dispatch('supplier-created');
        Flux::modal('create-supplier')->close();
        
        // Reset form
        $this->reset(['name', 'address', 'contact_person', 'email', 'phone']);
    }

    public function cancel(): void
    {
        Flux::modal('create-supplier')->close();
        $this->reset(['name', 'address', 'contact_person', 'email', 'phone']);
    }
}; ?>

<div class="space-y-6">
    <div>
        <flux:heading size="lg">Create Supplier</flux:heading>
        <flux:text class="mt-2">Add a new supplier to the system.</flux:text>
    </div>

    <form wire:submit="save" class="space-y-4">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div class="sm:col-span-2">
                <flux:input wire:model="name" label="Supplier Name" placeholder="Enter supplier company name" required />
            </div>
            <div class="sm:col-span-2">
                <flux:textarea wire:model="address" label="Address" placeholder="Enter complete business address" rows="2" />
            </div>
            <div>
                <flux:input wire:model="contact_person" label="Contact Person" placeholder="Enter primary contact name" />
            </div>
            <div>
                <flux:input wire:model="phone" label="Phone Number" placeholder="Enter phone/mobile number" />
            </div>
            <div class="sm:col-span-2">
                <flux:input wire:model="email" type="email" label="Email Address" placeholder="Enter business email address" />
            </div>
        </div>
        
        <div class="flex gap-2 pt-4 border-t border-stone-200 dark:border-stone-700">
            <flux:spacer />
            <flux:button type="button" variant="ghost" wire:click="cancel">
                Cancel
            </flux:button>
            <flux:button type="submit" variant="primary" wire:loading.attr="disabled">
                <span wire:loading.remove">Create Supplier</span>
                <span wire:loading>Creating...</span>
            </flux:button>
        </div>
    </form>
</div> 
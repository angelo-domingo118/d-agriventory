<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;

new #[Layout('components.layouts.app')] class extends Component
{
    public string $section = 'Create Transfer';
    
    // Form properties
    public string $transfer_id = '';
    public string $item = '';
    public string $source = '';
    public string $destination = '';
    public string $status = 'Pending';
    public string $date = '';
    
    public function mount()
    {
        // Generate a transfer ID with format TR-XXX
        $this->transfer_id = 'TR-' . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT);
        $this->date = date('Y-m-d');
    }
    
    public function save()
    {
        $this->validate([
            'item' => 'required|string|max:255',
            'source' => 'required|string|max:255',
            'destination' => 'required|string|max:255',
            'status' => 'required|in:Pending,In Transit,Completed',
            'date' => 'required|date',
        ]);
        
        // In a real implementation, save to database here
        
        session()->flash('success', 'Transfer created successfully.');
        return redirect()->route('inventory-manager.transfers.index');
    }
}

?>

<div>
    <x-inventory-manager.layout heading="Create Transfer" subheading="Create a new item transfer between divisions and departments">
        <div class="bg-white dark:bg-stone-800 shadow sm:rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <form wire:submit="save">
                    <div class="grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-6">
                        <div class="sm:col-span-3">
                            <flux:input 
                                wire:model="transfer_id" 
                                :label="__('Transfer ID')" 
                                readonly
                                help="Automatically generated"
                            />
                        </div>
                        
                        <div class="sm:col-span-3">
                            <flux:input 
                                wire:model="date" 
                                type="date" 
                                :label="__('Date')" 
                                required
                            />
                        </div>
                        
                        <div class="sm:col-span-6">
                            <flux:input 
                                wire:model="item" 
                                :label="__('Item')" 
                                required
                                placeholder="Enter the item name"
                            />
                        </div>
                        
                        <div class="sm:col-span-3">
                            <flux:input 
                                wire:model="source" 
                                :label="__('Source')" 
                                required
                                placeholder="Enter source division/department"
                            />
                        </div>
                        
                        <div class="sm:col-span-3">
                            <flux:input 
                                wire:model="destination" 
                                :label="__('Destination')" 
                                required
                                placeholder="Enter destination division/department"
                            />
                        </div>
                        
                        <div class="sm:col-span-6">
                            <!-- In a real implementation, this would be a proper select component -->
                            <flux:input
                                wire:model="status"
                                :label="__('Status')"
                                required
                                disabled
                                help="Status is set to Pending for new transfers"
                            />
                        </div>
                        
                        <div class="sm:col-span-6 flex justify-end space-x-3">
                            <flux:button 
                                variant="ghost" 
                                :href="route('inventory-manager.transfers.index')" 
                                wire:navigate>
                                {{ __('Cancel') }}
                            </flux:button>
                            
                            <flux:button 
                                variant="primary" 
                                type="submit">
                                {{ __('Create Transfer') }}
                            </flux:button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </x-inventory-manager.layout>
</div> 
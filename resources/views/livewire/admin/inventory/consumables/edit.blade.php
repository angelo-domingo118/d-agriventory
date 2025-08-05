<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use App\Models\ConsumableRecord;
use App\Models\ConsumableItem;
use App\Models\ItemSpecification;
use Livewire\Attributes\Validate;

new #[Layout('components.layouts.app')] class extends Component
{
    public ConsumableRecord $record;
    
    #[Validate('required|string')]
    public $record_number = '';
    
    #[Validate('required|date')]
    public $date_received = '';
    
    #[Validate('nullable|string|max:1000')]
    public $remarks = '';
    
    public $items = [];
    public $originalItems = [];

    public function mount(ConsumableRecord $record)
    {
        // Check admin permissions
        if (!auth()->user()->hasAdminPermission('edit_inventory')) {
            abort(403, 'Unauthorized access.');
        }
        
        $this->record = $record->load(['items.specification.itemCatalog', 'division']);
        $this->record_number = $this->record->record_number;
        $this->date_received = $this->record->date_received->format('Y-m-d');
        $this->remarks = $this->record->remarks;
        
        $this->items = $this->record->items->map(function ($item) {
            return [
                'id' => $item->id,
                'item_specification_id' => $item->item_specification_id,
                'initial_quantity' => $item->initial_quantity,
                'current_quantity' => $item->current_quantity,
            ];
        })->toArray();
        
        $this->originalItems = $this->items;
    }
    
    public function addItem()
    {
        $this->items[] = [
            'id' => null,
            'item_specification_id' => '',
            'initial_quantity' => 1,
            'current_quantity' => 1,
        ];
    }
    
    public function removeItem($index)
    {
        if (count($this->items) > 1) {
            unset($this->items[$index]);
            $this->items = array_values($this->items);
        }
    }
    
    public function getItemSpecifications()
    {
        return ItemSpecification::with('itemCatalog')->get();
    }
    
    public function save()
    {
        $this->validate();
        
        // Validate items
        foreach ($this->items as $index => $item) {
            if (empty($item['item_specification_id'])) {
                $this->addError("items.{$index}.item_specification_id", 'Please select an item.');
            }
            if ($item['initial_quantity'] < 1) {
                $this->addError("items.{$index}.initial_quantity", 'Quantity must be at least 1.');
            }
            if ($item['current_quantity'] < 0) {
                $this->addError("items.{$index}.current_quantity", 'Current quantity cannot be negative.');
            }
        }
        
        if ($this->getErrorBag()->count() > 0) {
            return;
        }
        
        // Update record
        $this->record->update([
            'record_number' => $this->record_number,
            'date_received' => $this->date_received,
            'remarks' => $this->remarks,
        ]);
        
        // Get current item IDs
        $existingItemIds = collect($this->items)->pluck('id')->filter()->toArray();
        
        // Delete items that were removed
        $this->record->items()->whereNotIn('id', $existingItemIds)->delete();
        
        // Update or create items
        foreach ($this->items as $item) {
            if ($item['id']) {
                // Update existing item
                ConsumableItem::where('id', $item['id'])->update([
                    'item_specification_id' => $item['item_specification_id'],
                    'initial_quantity' => $item['initial_quantity'],
                    'current_quantity' => $item['current_quantity'],
                ]);
            } else {
                // Create new item
                ConsumableItem::create([
                    'consumable_record_id' => $this->record->id,
                    'item_specification_id' => $item['item_specification_id'],
                    'initial_quantity' => $item['initial_quantity'],
                    'current_quantity' => $item['current_quantity'],
                ]);
            }
        }
        
        session()->flash('success', 'Consumable record updated successfully.');
        return redirect()->route('admin.inventory.consumables.show', $this->record);
    }
}

?>

<div class="py-6">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 md:px-8">
        <!-- Breadcrumbs -->
        <div class="flex items-center justify-between mb-4">
            <div>
                <flux:breadcrumbs class="text-2xl font-semibold">
                    <flux:breadcrumbs.item :href="route('admin.dashboard')" wire:navigate icon="home" class="text-xl sm:text-2xl font-semibold text-stone-700 dark:text-stone-300" />
                    <flux:breadcrumbs.item class="text-xl sm:text-2xl font-semibold text-stone-500 dark:text-stone-400">Inventory</flux:breadcrumbs.item>
                    <flux:breadcrumbs.item :href="route('admin.inventory.consumables.index')" wire:navigate class="text-xl sm:text-2xl font-semibold text-stone-500 dark:text-stone-400">Consumables</flux:breadcrumbs.item>
                    <flux:breadcrumbs.item class="text-xl sm:text-2xl font-semibold text-stone-900 dark:text-stone-100">Edit Record</flux:breadcrumbs.item>
                </flux:breadcrumbs>
            </div>
        </div>

        <!-- Header -->
        <div class="mb-8">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold leading-7 text-stone-900 dark:text-stone-100 sm:truncate sm:text-3xl sm:tracking-tight">
                        Edit Consumable Record
                    </h1>
                    <div class="mt-1 flex flex-col sm:mt-0 sm:flex-row sm:flex-wrap sm:space-x-6">
                        <div class="mt-2 flex items-center text-sm text-stone-500">
                            <x-flux::icon.building-office class="mr-1.5 h-5 w-5 flex-shrink-0 text-stone-400" />
                            {{ $record->division->name }}
                        </div>
                        <div class="mt-2 flex items-center text-sm text-stone-500">
                            <x-flux::icon.identification class="mr-1.5 h-5 w-5 flex-shrink-0 text-stone-400" />
                            {{ $record->record_number }}
                        </div>
                    </div>
                </div>
                <div class="flex space-x-2">
                    <flux:button 
                        variant="ghost" 
                        :href="route('admin.inventory.consumables.show', $record)" 
                        wire:navigate>
                        <x-flux::icon.arrow-left class="mr-1.5 h-4 w-4" />
                        Cancel
                    </flux:button>
                </div>
            </div>
        </div>

        <form wire:submit="save">
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                <!-- Record Information -->
                <div class="lg:col-span-1">
                    <div class="overflow-hidden rounded-lg bg-white shadow dark:bg-stone-800">
                        <div class="px-4 py-5 sm:p-6">
                            <h3 class="text-base font-semibold leading-6 text-stone-900 dark:text-stone-100">Record Information</h3>
                            <div class="mt-5 space-y-6">
                                <flux:input 
                                    wire:model="record_number" 
                                    label="Record Number" 
                                    type="text"
                                    required 
                                />

                                <flux:input 
                                    wire:model="date_received" 
                                    label="Date Received" 
                                    type="date"
                                    required 
                                />

                                <flux:textarea 
                                    wire:model="remarks" 
                                    label="Remarks" 
                                    rows="3"
                                    placeholder="Optional remarks..."
                                />

                                <div class="mt-6">
                                    <flux:button type="submit" variant="primary" class="w-full">
                                        <x-flux::icon.check class="mr-1.5 h-4 w-4" />
                                        Update Record
                                    </flux:button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Items -->
                <div class="lg:col-span-2">
                    <div class="overflow-hidden rounded-lg bg-white shadow dark:bg-stone-800">
                        <div class="px-4 py-5 sm:p-6">
                            <div class="flex items-center justify-between">
                                <h3 class="text-base font-semibold leading-6 text-stone-900 dark:text-stone-100">Items</h3>
                                <flux:button type="button" variant="ghost" wire:click="addItem">
                                    <x-flux::icon.plus class="mr-1.5 h-4 w-4" />
                                    Add Item
                                </flux:button>
                            </div>
                            
                            <div class="mt-5 space-y-4">
                                @foreach($items as $index => $item)
                                <div wire:key="item-{{ $index }}" class="rounded-lg border border-stone-200 p-4 dark:border-stone-700">
                                    <div class="flex items-start justify-between">
                                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4 flex-1">
                                            <div class="sm:col-span-2">
                                                <flux:select 
                                                    wire:model="items.{{ $index }}.item_specification_id" 
                                                    label="Item"
                                                    placeholder="Select an item..."
                                                    required>
                                                    @foreach($this->getItemSpecifications() as $spec)
                                                    <option value="{{ $spec->id }}">
                                                        {{ $spec->itemCatalog->name }}
                                                        @if($spec->brand || $spec->model)
                                                            ({{ collect([$spec->brand, $spec->model])->filter()->join(' / ') }})
                                                        @endif
                                                    </option>
                                                    @endforeach
                                                </flux:select>
                                            </div>

                                            <div>
                                                <flux:input 
                                                    wire:model="items.{{ $index }}.initial_quantity" 
                                                    label="Initial Quantity" 
                                                    type="number"
                                                    min="1"
                                                    required 
                                                />
                                            </div>

                                            <div>
                                                <flux:input 
                                                    wire:model="items.{{ $index }}.current_quantity" 
                                                    label="Current Quantity" 
                                                    type="number"
                                                    min="0"
                                                    required 
                                                />
                                            </div>
                                        </div>
                                        
                                        @if(count($items) > 1)
                                        <div class="ml-4">
                                            <flux:button type="button" variant="danger" size="sm" wire:click="removeItem({{ $index }})">
                                                <x-flux::icon.trash class="h-4 w-4" />
                                            </flux:button>
                                        </div>
                                        @endif
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div> 
<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use App\Models\ConsumableRecord;
use App\Models\ConsumableItem;
use App\Models\ItemSpecification;
use App\Services\ToastService;
use Livewire\Attributes\Validate;

new #[Layout('components.layouts.app')] class extends Component
{
    public ConsumableRecord $record;
    public $division;
    
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
        $user = auth()->user()->load('divisionInventoryManager.division');
        $this->division = $user->divisionInventoryManager->division;
        
        // Ensure the record belongs to the user's division
        if ($record->division_id !== $this->division->id) {
            abort(403, 'Unauthorized access to this consumable record.');
        }
        
        $this->record = $record->load(['items.specification.itemCatalog']);
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
        
        ToastService::updated($this, 'Consumable record');
        return redirect()->route('inventory-manager.consumables.show', $this->record);
    }
}

?>

<div>
    <div class="flex items-center justify-between mb-6">
        <!-- Breadcrumbs as Title -->
        <div>
            <flux:breadcrumbs class="text-2xl font-semibold">
                <flux:breadcrumbs.item :href="route('inventory-manager.dashboard')" wire:navigate icon="home" class="text-xl sm:text-2xl font-semibold text-stone-700 dark:text-stone-300" />
                <flux:breadcrumbs.item :href="route('inventory-manager.consumables.index')" wire:navigate class="text-xl sm:text-2xl font-semibold text-stone-500 dark:text-stone-400">Consumables</flux:breadcrumbs.item>
                <flux:breadcrumbs.item :href="route('inventory-manager.consumables.show', $record)" wire:navigate class="text-xl sm:text-2xl font-semibold text-stone-500 dark:text-stone-400">{{ $record->record_number }}</flux:breadcrumbs.item>
                <flux:breadcrumbs.item class="text-xl sm:text-2xl font-semibold text-stone-900 dark:text-stone-100">Edit</flux:breadcrumbs.item>
            </flux:breadcrumbs>
            <p class="mt-1 text-sm text-stone-600 dark:text-stone-400">
                Update consumable record for {{ $division->name }}
            </p>
        </div>
        <div class="flex items-center gap-x-2">
            <flux:button variant="outline" :href="route('inventory-manager.consumables.show', $record)" wire:navigate>
                Cancel
            </flux:button>
            <flux:button type="submit" variant="primary" form="consumable-edit-form">
                <span wire:loading.remove wire:target="save">Update Record</span>
                <span wire:loading wire:target="save">Updating...</span>
            </flux:button>
        </div>
    </div>

    <div class="bg-white dark:bg-stone-800 shadow sm:rounded-lg">
        <div class="px-4 py-5 sm:p-6">
            <form wire:submit="save" id="consumable-edit-form">
                    <div class="grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-6">
                        <div class="sm:col-span-3">
                            <flux:input 
                                wire:model="record_number" 
                                :label="__('Record Number')" 
                                required
                                :error="$errors->first('record_number')"
                            />
                        </div>
                        
                        <div class="sm:col-span-3">
                            <flux:input 
                                wire:model="date_received" 
                                type="date" 
                                :label="__('Date Received')" 
                                required
                                :error="$errors->first('date_received')"
                            />
                        </div>
                        
                        <div class="sm:col-span-6">
                            <flux:textarea 
                                wire:model="remarks" 
                                :label="__('Remarks')" 
                                placeholder="Optional notes about this consumable record"
                                rows="3"
                                :error="$errors->first('remarks')"
                            />
                        </div>
                    </div>
                    
                    <!-- Items Section -->
                    <div class="mt-8">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-medium text-stone-800 dark:text-stone-200">Items</h3>
                            <flux:button 
                                type="button"
                                variant="outline" 
                                wire:click="addItem">
                                <flux:icon.plus class="h-4 w-4 mr-2" />
                                Add Item
                            </flux:button>
                        </div>
                        
                        <div class="space-y-4">
                            @foreach($items as $index => $item)
                            <div class="border border-stone-200 dark:border-stone-700 rounded-lg p-4">
                                <div class="flex items-center justify-between mb-4">
                                    <h4 class="font-medium text-stone-800 dark:text-stone-200">
                                        Item {{ $index + 1 }}
                                        @if($item['id'])
                                            <span class="text-xs text-stone-500 dark:text-stone-400 ml-2">(Existing)</span>
                                        @else
                                            <span class="text-xs text-green-500 ml-2">(New)</span>
                                        @endif
                                    </h4>
                                    @if(count($items) > 1)
                                        <flux:button 
                                            type="button"
                                            variant="danger" 
                                            size="sm"
                                            wire:click="removeItem({{ $index }})">
                                            <flux:icon.trash class="h-4 w-4" />
                                        </flux:button>
                                    @endif
                                </div>
                                
                                <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                                    <div>
                                        <flux:select 
                                            wire:model="items.{{ $index }}.item_specification_id"
                                            :label="__('Item Specification')"
                                            placeholder="Select an item..."
                                            :error="$errors->first('items.'.$index.'.item_specification_id')">
                                            @foreach($this->getItemSpecifications() as $spec)
                                                <flux:option value="{{ $spec->id }}">
                                                    {{ $spec->itemCatalog->name }} 
                                                    @if($spec->brand) - {{ $spec->brand }} @endif
                                                    @if($spec->model) {{ $spec->model }} @endif
                                                </flux:option>
                                            @endforeach
                                        </flux:select>
                                    </div>
                                    
                                    <div>
                                        <flux:input 
                                            wire:model="items.{{ $index }}.initial_quantity"
                                            type="number" 
                                            min="1"
                                            :label="__('Initial Quantity')" 
                                            required
                                            :error="$errors->first('items.'.$index.'.initial_quantity')"
                                        />
                                    </div>
                                    
                                    <div>
                                        <flux:input 
                                            wire:model="items.{{ $index }}.current_quantity"
                                            type="number" 
                                            min="0"
                                            :label="__('Current Quantity')" 
                                            required
                                            help="Available quantity now"
                                            :error="$errors->first('items.'.$index.'.current_quantity')"
                                        />
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </form>
            </div>
        </div>
</div>

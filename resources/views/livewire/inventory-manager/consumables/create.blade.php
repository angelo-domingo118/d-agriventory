<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use App\Models\ConsumableRecord;
use App\Models\ConsumableItem;
use App\Models\ItemSpecification;
use App\Models\ItemsCatalog;
use Livewire\Attributes\Validate;

new #[Layout('components.layouts.app')] class extends Component
{
    public $division;
    
    #[Validate('required|string|unique:consumable_records,record_number')]
    public $record_number = '';
    
    #[Validate('required|date')]
    public $date_received = '';
    
    #[Validate('nullable|string|max:1000')]
    public $remarks = '';
    
    public $items = [];
    
    public function mount()
    {
        $user = auth()->user()->load('divisionInventoryManager.division');
        $this->division = $user->divisionInventoryManager->division;
        $this->date_received = now()->format('Y-m-d');
        $this->record_number = 'CR-' . str_pad(rand(1, 99999), 5, '0', STR_PAD_LEFT);
        $this->addItem();
    }
    
    public function addItem()
    {
        $this->items[] = [
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
    
    public function updatedItems($value, $key)
    {
        if (str_ends_with($key, '.initial_quantity')) {
            $index = (int) explode('.', $key)[0];
            $this->items[$index]['current_quantity'] = $value;
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
        }
        
        if ($this->getErrorBag()->count() > 0) {
            return;
        }
        
        $record = ConsumableRecord::create([
            'record_number' => $this->record_number,
            'division_id' => $this->division->id,
            'date_received' => $this->date_received,
            'remarks' => $this->remarks,
        ]);
        
        foreach ($this->items as $item) {
            ConsumableItem::create([
                'consumable_record_id' => $record->id,
                'item_specification_id' => $item['item_specification_id'],
                'initial_quantity' => $item['initial_quantity'],
                'current_quantity' => $item['current_quantity'],
            ]);
        }
        
        session()->flash('success', 'Consumable record created successfully.');
        return redirect()->route('inventory-manager.consumables.index');
    }
}

?>

<div>
    <x-inventory-manager.layout 
        heading="Create Consumable Record" 
        :subheading="'Add new consumable inventory for ' . $this->division->name">
        
        <div class="bg-white dark:bg-stone-800 shadow sm:rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <form wire:submit="save">
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
                                variant="ghost" 
                                wire:click="addItem">
                                Add Item
                            </flux:button>
                        </div>
                        
                        <div class="space-y-4">
                            @foreach($items as $index => $item)
                            <div class="border border-stone-200 dark:border-stone-700 rounded-lg p-4">
                                <div class="flex items-center justify-between mb-4">
                                    <h4 class="font-medium text-stone-800 dark:text-stone-200">Item {{ $index + 1 }}</h4>
                                    @if(count($items) > 1)
                                        <flux:button 
                                            type="button"
                                            variant="ghost" 
                                            size="sm"
                                            wire:click="removeItem({{ $index }})">
                                            Remove
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
                                            :label="__('Quantity')" 
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
                                            help="Usually same as initial quantity"
                                            :error="$errors->first('items.'.$index.'.current_quantity')"
                                        />
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    
                    <div class="mt-8 flex justify-end space-x-3">
                        <flux:button 
                            variant="ghost" 
                            :href="route('inventory-manager.consumables.index')" 
                            wire:navigate>
                            Cancel
                        </flux:button>
                        
                        <flux:button 
                            variant="primary" 
                            type="submit">
                            Create Record
                        </flux:button>
                    </div>
                </form>
            </div>
        </div>
    </x-inventory-manager.layout>
</div>

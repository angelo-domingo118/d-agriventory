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
        
        <form wire:submit="save" novalidate>
            <div class="border-b border-stone-200 pb-4 dark:border-stone-700">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-2xl font-semibold text-stone-900 dark:text-stone-100">
                            Create New Consumable Record
                        </h1>
                        <p class="mt-1 text-sm text-stone-600 dark:text-stone-400">
                            Add consumable items to your division's inventory
                        </p>
                    </div>
                    <div class="flex items-center gap-x-4">
                        <flux:button variant="ghost" :href="route('inventory-manager.consumables.index')" wire:navigate>
                            Cancel
                        </flux:button>
                        <flux:button type="submit" variant="primary">
                            <span wire:loading.remove>Save Record</span>
                            <span wire:loading>Saving...</span>
                        </flux:button>
                    </div>
                </div>
            </div>

            <div class="mt-6">
                <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                    <!-- Column 1: Record Information -->
                    <div class="space-y-6">
                        <div class="overflow-hidden rounded-lg border border-stone-200 bg-white shadow-sm dark:border-stone-700 dark:bg-stone-800">
                            <div class="border-b border-stone-200 px-4 py-3 dark:border-stone-700">
                                <h3 class="font-semibold text-stone-800 dark:text-stone-200">Record Information</h3>
                            </div>
                            <div class="p-4">
                                                                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                        <div>
                                            <flux:input 
                                                wire:model="record_number" 
                                                :label="__('Record Number')" 
                                                required
                                                :error="$errors->first('record_number')"
                                            />
                                        </div>
                                        
                                        <div>
                                            <flux:input 
                                                wire:model="date_received" 
                                                type="date" 
                                                :label="__('Date Received')" 
                                                required
                                                :error="$errors->first('date_received')"
                                            />
                                        </div>
                                    </div>
                                    
                                    <div>
                                        <flux:textarea 
                                            wire:model="remarks" 
                                            :label="__('Remarks')" 
                                            placeholder="Optional notes about this consumable record..."
                                            rows="4"
                                            :error="$errors->first('remarks')"
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Column 2: Items Section -->
                    <div class="space-y-6 lg:col-span-2">
                        <div class="overflow-hidden rounded-lg border border-stone-200 bg-white shadow-sm dark:border-stone-700 dark:bg-stone-800">
                            <div class="border-b border-stone-200 px-4 py-3 dark:border-stone-700">
                                <div class="flex items-center justify-between">
                                    <h3 class="font-semibold text-stone-800 dark:text-stone-200">Items</h3>
                                    <flux:button 
                                        type="button"
                                        variant="ghost" 
                                        size="sm"
                                        wire:click="addItem">
                                        <flux:icon.plus class="h-4 w-4 mr-2" />
                                        Add Item
                                    </flux:button>
                                </div>
                            </div>
                                                            <div class="space-y-6">
                                    @foreach($items as $index => $item)
                                    <div wire:key="item-{{ $index }}" class="rounded-lg border border-stone-300 bg-white p-0 dark:border-stone-600 dark:bg-stone-800/50">
                                        <div class="flex items-center justify-between p-3 bg-stone-50 dark:bg-stone-700/50 rounded-t-lg border-b border-stone-200 dark:border-stone-700">
                                            <h4 class="font-semibold text-stone-800 dark:text-stone-200 flex items-center space-x-2">
                                                <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-stone-200 dark:bg-stone-600 text-sm font-medium">
                                                    {{ $loop->iteration }}
                                                </span>
                                                <span>Item #{{ $loop->iteration }}</span>
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
                                        
                                        <div class="p-4">
                                            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                                                <div class="sm:col-span-3">
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
                                                        :error="$errors->first('items.'.$index.'.current_quantity')"
                                                    />
                                                    <p class="mt-1 text-sm text-stone-500 dark:text-stone-400">Usually same as initial quantity</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    @endforeach
                                    
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </x-inventory-manager.layout>
</div>

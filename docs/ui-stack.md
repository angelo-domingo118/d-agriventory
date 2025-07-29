# UI Stack Guide

Modern frontend architecture using Livewire 3, Volt single-file components, and Tailwind CSS 4 for reactive interfaces.

## livewire-3-fundamentals

Livewire 3 enables full-stack development using only PHP, eliminating the need for separate JavaScript frameworks whilst maintaining reactive user interfaces. Components handle both server-side logic and frontend presentation, communicating via AJAX to update specific page sections without full page reloads.

Key improvements in Livewire 3 include enhanced performance through wire:navigate for SPA-like navigation, streamlined property binding with wire:model.live for instant updates, and simplified component communication through events. The framework automatically handles CSRF protection, request validation, and state management between HTTP requests.

Components are stateful across requests, meaning properties persist during user interactions. This allows complex form handling, real-time validation, and dynamic content updates whilst maintaining server-side security and validation. Livewire's wire:loading directives provide immediate feedback during server communication, whilst wire:offline handles connection issues gracefully.

The framework integrates seamlessly with Alpine.js for client-side interactions that don't require server communication, such as modal toggles, dropdown menus, and form field visibility. This hybrid approach provides the reactivity users expect whilst keeping business logic on the server where it's secure and easily tested.

D'Agriventory leverages Livewire for all interactive features including inventory management forms, real-time search, data tables with sorting and filtering, and dynamic report generation. This approach reduces JavaScript complexity whilst maintaining excellent user experience through progressive enhancement and optimistic UI updates.

## volt-component-anatomy

```php
<?php
// resources/views/livewire/admin/inventory/create-item.blade.php

use function Livewire\Volt\{state, rules, mount, save};
use App\Models\{InventoryItem, Division, Category};

// Component state definition
state([
    'name' => '',
    'description' => '',
    'category_id' => null,
    'division_id' => null,
    'cost' => 0.00,
    'categories' => [],
    'divisions' => [],
]);

// Validation rules
rules([
    'name' => 'required|string|max:100',
    'description' => 'required|string|max:500',
    'category_id' => 'required|exists:categories,id',
    'division_id' => 'required|exists:divisions,id',
    'cost' => 'required|numeric|min:0',
]);

// Component initialisation
mount(function () {
    $this->categories = Category::orderBy('name')->get();
    $this->divisions = Division::orderBy('name')->get();
});

// Action methods
$save = function () {
    $this->validate();
    
    InventoryItem::create($this->only([
        'name', 'description', 'category_id', 'division_id', 'cost'
    ]));
    
    $this->reset();
    $this->dispatch('item-created');
};

?>

<div class="max-w-2xl mx-auto p-6">
    <form wire:submit="save" class="space-y-6">
        {{-- Form title --}}
        <h2 class="text-xl font-semibold text-gray-900">
            Create Inventory Item
        </h2>
        
        {{-- Name input --}}
        <div>
            <label for="name" class="block text-sm font-medium text-gray-700">
                Item Name
            </label>
            <input 
                type="text" 
                id="name"
                wire:model.live="name"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm 
                       focus:border-blue-500 focus:ring-blue-500"
                placeholder="Enter item name"
            >
            @error('name')
                <span class="text-sm text-red-600">{{ $message }}</span>
            @enderror
        </div>
        
        {{-- Category selection --}}
        <div>
            <label for="category" class="block text-sm font-medium text-gray-700">
                Category
            </label>
            <select 
                id="category"
                wire:model="category_id"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
            >
                <option value="">Select category</option>
                @foreach($categories as $category)
                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                @endforeach
            </select>
            @error('category_id')
                <span class="text-sm text-red-600">{{ $message }}</span>
            @enderror
        </div>
        
        {{-- Submit button with loading state --}}
        <button 
            type="submit"
            wire:loading.attr="disabled"
            class="w-full bg-blue-600 text-white py-2 px-4 rounded-md 
                   hover:bg-blue-700 disabled:opacity-50"
        >
            <span wire:loading.remove>Create Item</span>
            <span wire:loading>Creating...</span>
        </button>
    </form>
</div>
```

## tailwind-configuration

D'Agriventory uses Tailwind CSS 4 with Oxide engine for enhanced performance and simplified configuration.

**Configuration**: [`vite.config.js`](../vite.config.js) includes `@tailwindcss/vite` plugin

### Documentation Links

- **Tailwind CSS v4**: [Official v4 Alpha Documentation](https://tailwindcss.com/docs/v4-alpha)
- **Flux UI**: [Component Library Documentation](https://flux-ui.com/docs)
- **Livewire 3**: [Framework Documentation](https://livewire.laravel.com/docs)
- **Volt**: [Single-File Components Guide](https://livewire.laravel.com/docs/volt)
- **Alpine.js**: [Getting Started Guide](https://alpinejs.dev/start-here)
- **Vite**: [Build Tool Documentation](https://vitejs.dev/guide/)

### custom-utilities

The project includes custom animations and component styles in [`resources/css/animated-grid.css`](../resources/css/animated-grid.css) for enhanced visual feedback during loading states and data transitions.
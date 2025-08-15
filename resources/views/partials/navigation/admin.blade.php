<!-- MAIN Section -->
<x-enhanced-navlist-group :heading="__('MAIN')" icon="layout-dashboard" color="green">
    <flux:navlist.item 
        icon="layout-dashboard" 
        :href="route('admin.dashboard')" 
        :current="request()->routeIs('admin.dashboard')" 
        wire:navigate 
        class="relative mb-2 rounded-lg px-3 py-3 text-sm font-medium transition-[background-color,transform] duration-200 ease-in-out hover:bg-stone-100/80 hover:scale-[1.02] dark:hover:bg-stone-800/60 group"
    >
        <span class="flex items-center">
            {{ __('Dashboard') }}
            @if(request()->routeIs('admin.dashboard'))
                <span class="ml-auto h-2 w-2 rounded-full bg-green-500 animate-pulse"></span>
            @endif
        </span>
    </flux:navlist.item>
    
    <flux:navlist.item 
        icon="chart-line" 
        :href="route('admin.main.reports.index')" 
        :current="request()->routeIs('admin.main.reports.*')" 
        wire:navigate 
        class="relative mb-2 rounded-lg px-3 py-3 text-sm font-medium transition-[background-color,transform] duration-200 ease-in-out hover:bg-stone-100/80 hover:scale-[1.02] dark:hover:bg-stone-800/60 group"
    >
        <span class="flex items-center">
            {{ __('Report Generation') }}
            @if(request()->routeIs('admin.main.reports.*'))
                <span class="ml-auto h-2 w-2 rounded-full bg-green-500 animate-pulse"></span>
            @endif
        </span>
    </flux:navlist.item>
</x-enhanced-navlist-group>

<!-- INVENTORY Section -->
<x-enhanced-navlist-group :heading="__('INVENTORY')" icon="box" color="blue">
    <flux:navlist.item 
        icon="box" 
        :href="route('admin.inventory.ics.index')" 
        :current="request()->routeIs('admin.inventory.ics.*')" 
        wire:navigate 
        class="relative mb-2 rounded-lg px-3 py-3 text-sm font-medium transition-[background-color,transform] duration-200 ease-in-out hover:bg-blue-50/80 hover:scale-[1.02] dark:hover:bg-blue-900/20 group"
    >
        <span class="flex items-center">
            {{ __('ICS Management') }}
            @if(request()->routeIs('admin.inventory.ics.*'))
                <span class="ml-auto h-2 w-2 rounded-full bg-blue-500 animate-pulse"></span>
            @endif
        </span>
    </flux:navlist.item>
    
    <flux:navlist.item 
        icon="boxes" 
        :href="route('admin.inventory.par.index')" 
        :current="request()->routeIs('admin.inventory.par.*')" 
        wire:navigate 
        class="relative mb-2 rounded-lg px-3 py-3 text-sm font-medium transition-[background-color,transform] duration-200 ease-in-out hover:bg-blue-50/80 hover:scale-[1.02] dark:hover:bg-blue-900/20 group"
    >
        <span class="flex items-center">
            {{ __('PAR Management') }}
            @if(request()->routeIs('admin.inventory.par.*'))
                <span class="ml-auto h-2 w-2 rounded-full bg-blue-500 animate-pulse"></span>
            @endif
        </span>
    </flux:navlist.item>
    
    <flux:navlist.item 
        icon="package" 
        :href="route('admin.inventory.idr.index')" 
        :current="request()->routeIs('admin.inventory.idr.*')" 
        wire:navigate 
        class="relative mb-2 rounded-lg px-3 py-3 text-sm font-medium transition-[background-color,transform] duration-200 ease-in-out hover:bg-blue-50/80 hover:scale-[1.02] dark:hover:bg-blue-900/20 group"
    >
        <span class="flex items-center">
            {{ __('IDR Management') }}
            @if(request()->routeIs('admin.inventory.idr.*'))
                <span class="ml-auto h-2 w-2 rounded-full bg-blue-500 animate-pulse"></span>
            @endif
        </span>
    </flux:navlist.item>
    
    <flux:navlist.item 
        icon="package-check" 
        :href="route('admin.inventory.consumables.index')" 
        :current="request()->routeIs('admin.inventory.consumables.*')" 
        wire:navigate 
        class="relative mb-2 rounded-lg px-3 py-3 text-sm font-medium transition-[background-color,transform] duration-200 ease-in-out hover:bg-blue-50/80 hover:scale-[1.02] dark:hover:bg-blue-900/20 group"
    >
        <span class="flex items-center">
            {{ __('Consumables') }}
            @if(request()->routeIs('admin.inventory.consumables.*'))
                <span class="ml-auto h-2 w-2 rounded-full bg-blue-500 animate-pulse"></span>
            @endif
        </span>
    </flux:navlist.item>
</x-enhanced-navlist-group>

<!-- DATA Section -->
<x-enhanced-navlist-group :heading="__('DATA')" icon="layout-grid" color="purple">
    <flux:navlist.item 
        icon="layout-grid" 
        :href="route('admin.data.items-and-categories')" 
        :current="request()->routeIs('admin.data.items-and-categories*')" 
        wire:navigate 
        class="relative mb-2 rounded-lg px-3 py-3 text-sm font-medium transition-[background-color,transform] duration-200 ease-in-out hover:bg-purple-50/80 hover:scale-[1.02] dark:hover:bg-purple-900/20 group"
    >
        <span class="flex items-center">
            {{ __('Items & Categories') }}
            @if(request()->routeIs('admin.data.items-and-categories*'))
                <span class="ml-auto h-2 w-2 rounded-full bg-purple-500 animate-pulse"></span>
            @endif
        </span>
    </flux:navlist.item>
    
    <flux:navlist.item 
        icon="users" 
        :href="route('admin.data.employees-and-divisions')" 
        :current="request()->routeIs('admin.data.employees-and-divisions*')" 
        wire:navigate 
        class="relative mb-2 rounded-lg px-3 py-3 text-sm font-medium transition-[background-color,transform] duration-200 ease-in-out hover:bg-purple-50/80 hover:scale-[1.02] dark:hover:bg-purple-900/20 group"
    >
        <span class="flex items-center">
            {{ __('Employees & Divisions') }}
            @if(request()->routeIs('admin.data.employees-and-divisions*'))
                <span class="ml-auto h-2 w-2 rounded-full bg-purple-500 animate-pulse"></span>
            @endif
        </span>
    </flux:navlist.item>
    
    <flux:navlist.item 
        icon="file-text" 
        :href="route('admin.data.suppliers-and-contracts')" 
        :current="request()->routeIs('admin.data.suppliers-and-contracts*')" 
        wire:navigate 
        class="relative mb-2 rounded-lg px-3 py-3 text-sm font-medium transition-[background-color,transform] duration-200 ease-in-out hover:bg-purple-50/80 hover:scale-[1.02] dark:hover:bg-purple-900/20 group"
    >
        <span class="flex items-center">
            {{ __('Suppliers & Contracts') }}
            @if(request()->routeIs('admin.data.suppliers-and-contracts*'))
                <span class="ml-auto h-2 w-2 rounded-full bg-purple-500 animate-pulse"></span>
            @endif
        </span>
    </flux:navlist.item>
</x-enhanced-navlist-group>

<!-- SYSTEM Section -->
<x-enhanced-navlist-group :heading="__('SYSTEM')" icon="folder-git-2" color="red">
    <flux:navlist.item 
        icon="folder-git-2" 
        :href="route('admin.system.audit-logs.index')" 
        :current="request()->routeIs('admin.system.audit-logs.*')" 
        wire:navigate 
        class="relative mb-2 rounded-lg px-3 py-3 text-sm font-medium transition-[background-color,transform] duration-200 ease-in-out hover:bg-red-50/80 hover:scale-[1.02] dark:hover:bg-red-900/20 group"
    >
        <span class="flex items-center">
            {{ __('Audit Logs') }}
            @if(request()->routeIs('admin.system.audit-logs.*'))
                <span class="ml-auto h-2 w-2 rounded-full bg-red-500 animate-pulse"></span>
            @endif
        </span>
    </flux:navlist.item>
    
    <flux:navlist.item 
        icon="user" 
        :href="route('admin.system.users.index')" 
        :current="request()->routeIs('admin.system.users.*')" 
        wire:navigate 
        class="relative mb-2 rounded-lg px-3 py-3 text-sm font-medium transition-[background-color,transform] duration-200 ease-in-out hover:bg-red-50/80 hover:scale-[1.02] dark:hover:bg-red-900/20 group"
    >
        <span class="flex items-center">
            {{ __('User Management') }}
            @if(request()->routeIs('admin.system.users.*'))
                <span class="ml-auto h-2 w-2 rounded-full bg-red-500 animate-pulse"></span>
            @endif
        </span>
    </flux:navlist.item>
</x-enhanced-navlist-group> 
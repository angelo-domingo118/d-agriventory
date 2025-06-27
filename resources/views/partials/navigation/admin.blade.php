<!-- MAIN -->
<flux:navlist.group :heading="__('MAIN')">
    <flux:navlist.item icon="layout-dashboard" :href="route('admin.dashboard')" :current="request()->routeIs('admin.dashboard')" wire:navigate class="py-6 text-lg">{{ __('Dashboard') }}</flux:navlist.item>
    <flux:navlist.item icon="chart-line" :href="route('admin.reports.index')" :current="request()->routeIs('admin.reports.*')" wire:navigate class="py-6 text-lg">{{ __('Report Generation') }}</flux:navlist.item>
</flux:navlist.group>

<!-- INVENTORY -->
<flux:navlist.group :heading="__('INVENTORY')">
    <flux:navlist.item icon="box" :href="route('admin.inventory.ics.index')" :current="request()->routeIs('admin.inventory.ics.*')" wire:navigate class="py-6 text-lg">{{ __('ICS Management') }}</flux:navlist.item>
    <flux:navlist.item icon="boxes" :href="route('admin.inventory.par.index')" :current="request()->routeIs('admin.inventory.par.*')" wire:navigate class="py-6 text-lg">{{ __('PAR Management') }}</flux:navlist.item>
    <flux:navlist.item icon="package" :href="route('admin.inventory.idr.index')" :current="request()->routeIs('admin.inventory.idr.*')" wire:navigate class="py-6 text-lg">{{ __('IDR Management') }}</flux:navlist.item>
    <flux:navlist.item icon="package-check" :href="route('admin.inventory.transfers.index')" :current="request()->routeIs('admin.inventory.transfers.*')" wire:navigate class="py-6 text-lg">{{ __('Transfers') }}</flux:navlist.item>
    <flux:navlist.item icon="clipboard-list" :href="route('admin.inventory.consumables.index')" :current="request()->routeIs('admin.inventory.consumables.*')" wire:navigate class="py-6 text-lg">{{ __('Consumables') }}</flux:navlist.item>
</flux:navlist.group>

<!-- DATA -->
<flux:navlist.group :heading="__('DATA')">
    <flux:navlist.item icon="layout-grid" :href="route('admin.inventory.items.index')" :current="request()->routeIs('admin.inventory.items.*')" wire:navigate class="py-6 text-lg">{{ __('Items & Categories') }}</flux:navlist.item>
    <flux:navlist.item icon="users" :href="route('admin.employees.index')" :current="request()->routeIs('admin.employees.*')" wire:navigate class="py-6 text-lg">{{ __('Employees & Divisions') }}</flux:navlist.item>
    <flux:navlist.item icon="file-text" :href="route('admin.inventory.contracts.index')" :current="request()->routeIs('admin.inventory.contracts.*')" wire:navigate class="py-6 text-lg">{{ __('Suppliers & Contracts') }}</flux:navlist.item>
</flux:navlist.group>

<!-- SYSTEM -->
<flux:navlist.group :heading="__('SYSTEM')">
    <flux:navlist.item icon="folder-git-2" :href="route('admin.logs.index')" :current="request()->routeIs('admin.logs.*')" wire:navigate class="py-6 text-lg">{{ __('Audit Logs') }}</flux:navlist.item>
    <flux:navlist.item icon="user" :href="route('admin.users.index')" :current="request()->routeIs('admin.users.management.*')" wire:navigate class="py-6 text-lg">{{ __('User Management') }}</flux:navlist.item>
</flux:navlist.group> 
<flux:navlist.group :heading="__('Manager')" class="grid">
    <flux:navlist.item icon="layout-dashboard" :href="route('inventory-manager.dashboard')" :current="request()->routeIs('inventory-manager.dashboard')" wire:navigate>{{ __('Dashboard') }}</flux:navlist.item>
    <flux:navlist.item icon="boxes" :href="route('inventory-manager.items.index')" :current="request()->routeIs('inventory-manager.items.*')" wire:navigate>{{ __('Items') }}</flux:navlist.item>
    <flux:navlist.item icon="folder-git-2" :href="route('inventory-manager.transfers.index')" :current="request()->routeIs('inventory-manager.transfers.*')" wire:navigate>{{ __('Transfers') }}</flux:navlist.item>
    <flux:navlist.item icon="chart-line" :href="route('inventory-manager.reports.index')" :current="request()->routeIs('inventory-manager.reports.*')" wire:navigate>{{ __('Reports') }}</flux:navlist.item>
</flux:navlist.group> 
<flux:navlist.group :heading="__('Admin')" class="grid">
    <flux:navlist.item icon="layout-dashboard" :href="route('admin.dashboard')" :current="request()->routeIs('admin.dashboard')" wire:navigate>{{ __('Admin Dashboard') }}</flux:navlist.item>
    <flux:navlist.item icon="users" :href="route('admin.users.index')" :current="request()->routeIs('admin.users.*')" wire:navigate>{{ __('Users') }}</flux:navlist.item>
    <flux:navlist.item icon="box" :href="route('admin.inventory.index')" :current="request()->routeIs('admin.inventory.*')" wire:navigate>{{ __('Inventory') }}</flux:navlist.item>
    <flux:navlist.item icon="chart-bar" :href="route('admin.reports.index')" :current="request()->routeIs('admin.reports.*')" wire:navigate>{{ __('Reports') }}</flux:navlist.item>
</flux:navlist.group> 
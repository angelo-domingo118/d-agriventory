<div class="flex items-center justify-between mb-6">
    <!-- Breadcrumbs as Title -->
    <div>
        <flux:breadcrumbs class="text-2xl font-semibold">
            <flux:breadcrumbs.item :href="route('admin.dashboard')" wire:navigate icon="home" class="text-xl sm:text-2xl font-semibold text-stone-700 dark:text-stone-300" />
            <flux:breadcrumbs.item class="text-xl sm:text-2xl font-semibold text-stone-900 dark:text-stone-100">Settings</flux:breadcrumbs.item>
        </flux:breadcrumbs>
        <p class="mt-1 text-sm text-stone-600 dark:text-stone-400">
            Manage your profile and account preferences
        </p>
    </div>
    <div class="flex items-center gap-x-2">
        <div class="text-sm text-stone-500 dark:text-stone-400">
            Signed in as <strong class="text-stone-700 dark:text-stone-300">{{ auth()->user()->name }}</strong>
        </div>
    </div>
</div>

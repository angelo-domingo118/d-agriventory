<div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
    <h1 class="text-2xl font-semibold text-stone-900 dark:text-stone-100 mb-6">Inventory Manager - {{ $section ?? 'Dashboard' }}</h1>
    <!-- Common layout elements -->
    
    <div class="bg-white dark:bg-stone-800 shadow-sm rounded-lg p-6">
        @if(isset($slot))
            {{ $slot }}
        @else
            <!-- Placeholder content -->
            <p class="text-stone-600 dark:text-stone-400">Content will be displayed here.</p>
        @endif
    </div>
</div> 
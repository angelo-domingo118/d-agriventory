<div class="relative mb-6 w-full">
    <!-- Compact Settings Header -->
    <div class="bg-gradient-to-r from-blue-50 via-indigo-50 to-violet-50 dark:from-blue-950/30 dark:via-indigo-950/30 dark:to-violet-950/30 rounded-xl p-4 sm:p-5 border border-blue-200/50 dark:border-blue-800/50 shadow-sm backdrop-blur-sm">
        <div class="flex items-center">
            <div class="flex-shrink-0 p-2 bg-gradient-to-br from-blue-100 to-indigo-100 dark:from-blue-900/40 dark:to-indigo-900/40 rounded-lg mr-4">
                <x-flux::icon.cog-6-tooth class="h-5 w-5 text-blue-600 dark:text-blue-400" />
            </div>
            <div class="flex-1">
                <h1 class="text-xl sm:text-2xl font-bold bg-gradient-to-r from-blue-600 via-indigo-600 to-violet-600 dark:from-blue-400 dark:via-indigo-400 dark:to-violet-400 bg-clip-text text-transparent">
                    {{ __('Settings') }}
                </h1>
                <p class="mt-1 text-sm text-stone-600 dark:text-stone-300">
                    {{ __('Manage your profile and account preferences') }}
                </p>
                <div class="flex items-center mt-2 text-xs text-stone-500 dark:text-stone-400">
                    <x-flux::icon.user-circle class="h-3 w-3 mr-1" />
                    <span>Signed in as <strong class="text-stone-700 dark:text-stone-300">{{ auth()->user()->name }}</strong></span>
                </div>
            </div>
        </div>
    </div>
</div>

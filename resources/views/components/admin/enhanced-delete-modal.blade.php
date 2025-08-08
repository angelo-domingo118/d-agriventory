@props([
    'name' => 'delete-confirmation',
    'maxWidth' => 'lg',
    'title' => 'Delete Item',
    'entityType' => 'item',
    'entityName' => '',
    'deleteAction' => '',
    'cancelAction' => '',
    'associationCounts' => [],
    'hasAssociatedData' => false,
    'customMessage' => null
])

@php
$maxWidthClass = [
    'sm' => 'sm:max-w-sm',
    'md' => 'sm:max-w-md',
    'lg' => 'sm:max-w-lg',
    'xl' => 'sm:max-w-xl',
    '2xl' => 'sm:max-w-2xl',
    '3xl' => 'sm:max-w-3xl',
    '4xl' => 'sm:max-w-4xl',
    '5xl' => 'sm:max-w-5xl',
    '6xl' => 'sm:max-w-6xl',
    '7xl' => 'sm:max-w-7xl',
][$maxWidth];
@endphp

<flux:modal :name="$name" variant="bare" class="w-full {{ $maxWidthClass }}" style="position: relative; z-index: 9999;">
    <div class="rounded-lg bg-white p-6 shadow-xl dark:bg-stone-800">
        <div class="space-y-6">
            <!-- Warning Header -->
            <div class="text-center">
                <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-red-100 dark:bg-red-900/20">
                    <!-- Warning Triangle SVG -->
                    <svg class="h-8 w-8 text-red-600 dark:text-red-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="mt-4">
                    <flux:heading size="xl" class="text-stone-900 dark:text-stone-100">
                        {{ $title }}
                    </flux:heading>
                    <flux:text class="mt-2 text-stone-600 dark:text-stone-400">
                        @if($customMessage)
                            {{ $customMessage }}
                        @elseif($hasAssociatedData)
                            This {{ $entityType }} has associated data that will be affected.
                        @else
                            This action is permanent and cannot be undone.
                        @endif
                    </flux:text>
                </div>
            </div>

            <!-- Entity Information -->
            <div class="rounded-lg border border-red-200 bg-red-50 p-4 dark:border-red-800/50 dark:bg-red-900/10">
                <div class="flex items-start">
                    <div class="flex-shrink-0">
                        <!-- Warning Circle SVG -->
                        <svg class="h-5 w-5 text-red-500 dark:text-red-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-3a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 7zm0 8a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-red-800 dark:text-red-200">
                            You're about to permanently delete
                            @if($entityName)
                                <span class="font-bold">"{{ $entityName }}"</span>
                            @else
                                this {{ $entityType }}
                            @endif
                        </p>
                        @if($hasAssociatedData && count($associationCounts) > 0)
                            <p class="mt-1 text-sm text-red-700 dark:text-red-300">
                                This will also delete:
                            </p>
                            <ul class="mt-2 text-sm text-red-700 dark:text-red-300 list-disc list-inside">
                                @foreach($associationCounts as $type => $count)
                                    @if($count > 0)
                                        <li><strong>{{ $count }}</strong> {{ $count === 1 ? \Illuminate\Support\Str::singular($type) : $type }}</li>
                                    @endif
                                @endforeach
                            </ul>
                            <p class="mt-2 text-sm font-medium text-red-800 dark:text-red-200">
                                All associated data will be permanently removed and cannot be recovered.
                            </p>
                        @elseif($hasAssociatedData)
                            <p class="mt-1 text-sm text-red-700 dark:text-red-300">
                                This {{ $entityType }} has associated data that will be permanently removed.
                            </p>
                        @else
                            <p class="mt-1 text-sm text-red-700 dark:text-red-300">
                                This {{ $entityType }} has no associated data.
                            </p>
                        @endif
                    </div>
                </div>
            </div>

            @if($hasAssociatedData)
                <!-- Force Delete Warning -->
                <div class="rounded-lg border border-amber-200 bg-amber-50 p-4 dark:border-amber-800/50 dark:bg-amber-900/10">
                    <div class="flex items-start">
                        <div class="flex-shrink-0">
                            <!-- Exclamation Triangle SVG -->
                            <svg class="h-5 w-5 text-amber-500 dark:text-amber-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-amber-800 dark:text-amber-200">
                                Force Delete Options
                            </p>
                            <p class="mt-1 text-sm text-amber-700 dark:text-amber-300">
                                You can either cancel this deletion to preserve the associated data, or force delete everything.
                            </p>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Action Buttons -->
            <div class="flex flex-col-reverse gap-3 pt-6 border-t border-stone-200 dark:border-stone-700 sm:flex-row sm:justify-end">
                @if($cancelAction)
                    <flux:button 
                        type="button" 
                        variant="outline" 
                        wire:click="{{ $cancelAction }}"
                        class="sm:w-auto w-full justify-center"
                    >
                        <div class="flex items-center">
                            <x-flux::icon.x-mark class="mr-2 h-4 w-4" />
                            Cancel
                        </div>
                    </flux:button>
                @else
                    <flux:modal.close>
                        <flux:button 
                            type="button" 
                            variant="outline"
                            class="sm:w-auto w-full justify-center"
                        >
                            <div class="flex items-center">
                                <x-flux::icon.x-mark class="mr-2 h-4 w-4" />
                                Cancel
                            </div>
                        </flux:button>
                    </flux:modal.close>
                @endif
                
                <flux:button 
                    type="button" 
                    variant="danger" 
                    wire:click="{{ $deleteAction }}" 
                    wire:loading.attr="disabled"
                    class="sm:w-auto w-full justify-center bg-red-600 hover:bg-red-700 focus:ring-red-500 dark:bg-red-600 dark:hover:bg-red-700"
                >
                    <span wire:loading.remove wire:target="{{ $deleteAction }}" class="flex items-center">
                        <!-- Delete/Trash SVG -->
                        <svg class="mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                        </svg>
                        @if($hasAssociatedData)
                            Force Delete All
                        @else
                            Delete {{ ucfirst($entityType) }}
                        @endif
                    </span>
                    <span wire:loading wire:target="{{ $deleteAction }}" class="flex items-center">
                        <x-flux::icon.rotate-cw class="mr-2 h-4 w-4 animate-spin" />
                        Deleting...
                    </span>
                </flux:button>
            </div>
        </div>
    </div>
</flux:modal>

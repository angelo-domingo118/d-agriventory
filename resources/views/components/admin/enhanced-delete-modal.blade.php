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
    'customMessage' => null,
    'riskLevel' => 'low',
    'riskMessage' => null,
    'blockDeletion' => false
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

// Risk-based styling
$riskConfig = [
    'safe' => [
        'icon_bg' => 'bg-green-100 dark:bg-green-900/20',
        'icon_color' => 'text-green-600 dark:text-green-400',
        'border_color' => 'border-green-200 dark:border-green-800/50',
        'bg_color' => 'bg-green-50 dark:bg-green-900/10',
        'text_color' => 'text-green-800 dark:text-green-200',
        'text_secondary' => 'text-green-700 dark:text-green-300',
        'button_class' => 'bg-green-600 hover:bg-green-700 focus:ring-green-500 dark:bg-green-600 dark:hover:bg-green-700'
    ],
    'low' => [
        'icon_bg' => 'bg-yellow-100 dark:bg-yellow-900/20',
        'icon_color' => 'text-yellow-600 dark:text-yellow-400',
        'border_color' => 'border-yellow-200 dark:border-yellow-800/50',
        'bg_color' => 'bg-yellow-50 dark:bg-yellow-900/10',
        'text_color' => 'text-yellow-800 dark:text-yellow-200',
        'text_secondary' => 'text-yellow-700 dark:text-yellow-300',
        'button_class' => 'bg-yellow-600 hover:bg-yellow-700 focus:ring-yellow-500 dark:bg-yellow-600 dark:hover:bg-yellow-700'
    ],
    'medium' => [
        'icon_bg' => 'bg-orange-100 dark:bg-orange-900/20',
        'icon_color' => 'text-orange-600 dark:text-orange-400',
        'border_color' => 'border-orange-200 dark:border-orange-800/50',
        'bg_color' => 'bg-orange-50 dark:bg-orange-900/10',
        'text_color' => 'text-orange-800 dark:text-orange-200',
        'text_secondary' => 'text-orange-700 dark:text-orange-300',
        'button_class' => 'bg-orange-600 hover:bg-orange-700 focus:ring-orange-500 dark:bg-orange-600 dark:hover:bg-orange-700'
    ],
    'high' => [
        'icon_bg' => 'bg-red-100 dark:bg-red-900/20',
        'icon_color' => 'text-red-600 dark:text-red-400',
        'border_color' => 'border-red-200 dark:border-red-800/50',
        'bg_color' => 'bg-red-50 dark:bg-red-900/10',
        'text_color' => 'text-red-800 dark:text-red-200',
        'text_secondary' => 'text-red-700 dark:text-red-300',
        'button_class' => 'bg-red-600 hover:bg-red-700 focus:ring-red-500 dark:bg-red-600 dark:hover:bg-red-700'
    ]
];

$config = $riskConfig[$riskLevel] ?? $riskConfig['low'];
@endphp

<flux:modal :name="$name" variant="bare" class="w-full {{ $maxWidthClass }}" style="position: relative; z-index: 9999;">
    <div class="rounded-lg bg-white p-6 shadow-xl dark:bg-stone-800">
        <div class="space-y-6">
            <!-- Warning Header -->
            <div class="text-center">
                <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full {{ $config['icon_bg'] }}">
                    @if($blockDeletion)
                        <!-- Block/Stop SVG -->
                        <svg class="h-8 w-8 {{ $config['icon_color'] }}" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z" clip-rule="evenodd" />
                        </svg>
                    @elseif($riskLevel === 'high')
                        <!-- Danger SVG -->
                        <svg class="h-8 w-8 {{ $config['icon_color'] }}" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" />
                        </svg>
                    @elseif($riskLevel === 'safe')
                        <!-- Check Circle SVG -->
                        <svg class="h-8 w-8 {{ $config['icon_color'] }}" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.236 4.53L8.667 10.5a.75.75 0 00-1.334.73l1.423 2.615a.75.75 0 001.275.042l3.857-5.383z" clip-rule="evenodd" />
                        </svg>
                    @else
                        <!-- Warning Triangle SVG -->
                        <svg class="h-8 w-8 {{ $config['icon_color'] }}" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" />
                        </svg>
                    @endif
                </div>
                <div class="mt-4">
                    <flux:heading size="xl" class="text-stone-900 dark:text-stone-100">
                        {{ $title }}
                        @if($riskLevel === 'high')
                            <span class="ml-2 text-red-600 dark:text-red-400">(High Risk)</span>
                        @elseif($riskLevel === 'medium')
                            <span class="ml-2 text-orange-600 dark:text-orange-400">(Medium Risk)</span>
                        @elseif($riskLevel === 'safe')
                            <span class="ml-2 text-green-600 dark:text-green-400">(Safe)</span>
                        @endif
                    </flux:heading>
                    <flux:text class="mt-2 text-stone-600 dark:text-stone-400">
                        @if($blockDeletion)
                            This deletion has been blocked due to high risk.
                        @elseif($riskMessage)
                            {{ $riskMessage }}
                        @elseif($customMessage)
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
            <div class="rounded-lg border {{ $config['border_color'] }} {{ $config['bg_color'] }} p-4">
                <div class="flex items-start">
                    <div class="flex-shrink-0">
                        <!-- Warning Circle SVG -->
                        <svg class="h-5 w-5 {{ $config['icon_color'] }}" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-3a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 7zm0 8a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium {{ $config['text_color'] }}">
                            @if($blockDeletion)
                                Deletion blocked for 
                            @else
                                You're about to permanently delete
                            @endif
                            @if($entityName)
                                <span class="font-bold">"{{ $entityName }}"</span>
                            @else
                                this {{ $entityType }}
                            @endif
                        </p>
                        @if($hasAssociatedData && count($associationCounts) > 0)
                            <p class="mt-1 text-sm {{ $config['text_secondary'] }}">
                                This will also delete:
                            </p>
                            <ul class="mt-2 text-sm {{ $config['text_secondary'] }} list-disc list-inside">
                                @foreach($associationCounts as $type => $count)
                                    @if($count > 0)
                                        <li><strong>{{ $count }}</strong> {{ $count === 1 ? \Illuminate\Support\Str::singular($type) : $type }}</li>
                                    @endif
                                @endforeach
                            </ul>
                            <p class="mt-2 text-sm font-medium {{ $config['text_color'] }}">
                                @if($blockDeletion)
                                    This {{ $entityType }} cannot be deleted because it has active inventory records.
                                @else
                                    All associated data will be permanently removed and cannot be recovered.
                                @endif
                            </p>
                        @elseif($hasAssociatedData)
                            <p class="mt-1 text-sm {{ $config['text_secondary'] }}">
                                This {{ $entityType }} has associated data that will be permanently removed.
                            </p>
                        @else
                            <p class="mt-1 text-sm {{ $config['text_secondary'] }}">
                                This {{ $entityType }} has no associated data.
                            </p>
                        @endif
                    </div>
                </div>
            </div>

            @if($hasAssociatedData && !$blockDeletion)
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
                                @if($riskLevel === 'high')
                                    High Risk Deletion
                                @elseif($riskLevel === 'medium')  
                                    Proceed with Caution
                                @else
                                    Force Delete Options
                                @endif
                            </p>
                            <p class="mt-1 text-sm text-amber-700 dark:text-amber-300">
                                @if($riskLevel === 'high')
                                    This action will permanently remove critical inventory data. Consider archiving instead.
                                @elseif($riskLevel === 'medium')
                                    This will remove procurement history and contract data. Ensure this is intended.
                                @else
                                    You can either cancel this deletion to preserve the associated data, or force delete everything.
                                @endif
                            </p>
                        </div>
                    </div>
                </div>
            @elseif($blockDeletion)
                <!-- Blocked Deletion Info -->
                <div class="rounded-lg border border-blue-200 bg-blue-50 p-4 dark:border-blue-800/50 dark:bg-blue-900/10">
                    <div class="flex items-start">
                        <div class="flex-shrink-0">
                            <!-- Info Circle SVG -->
                            <svg class="h-5 w-5 text-blue-500 dark:text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a.75.75 0 000 1.5h.253a.25.25 0 01.244.304l-.459 2.066A1.75 1.75 0 0010.747 15H11a.75.75 0 000-1.5h-.253a.25.25 0 01-.244-.304l.459-2.066A1.75 1.75 0 009.253 9H9z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-blue-800 dark:text-blue-200">
                                Alternative Actions
                            </p>
                            <p class="mt-1 text-sm text-blue-700 dark:text-blue-300">
                                Consider marking this item as discontinued or archived instead of deletion to preserve historical data.
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
                            {{ $blockDeletion ? 'Close' : 'Cancel' }}
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
                                {{ $blockDeletion ? 'Close' : 'Cancel' }}
                            </div>
                        </flux:button>
                    </flux:modal.close>
                @endif
                
                @if(!$blockDeletion)
                    <flux:button 
                        type="button" 
                        variant="danger" 
                        wire:click="{{ $deleteAction }}" 
                        wire:loading.attr="disabled"
                        class="sm:w-auto w-full justify-center {{ $config['button_class'] }}"
                    >
                        <span wire:loading.remove wire:target="{{ $deleteAction }}" class="flex items-center">
                            @if($riskLevel === 'safe')
                                <!-- Check/Safe SVG -->
                                <svg class="mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                </svg>
                            @else
                                <!-- Delete/Trash SVG -->
                                <svg class="mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                                </svg>
                            @endif
                            @if($riskLevel === 'safe')
                                Delete {{ ucfirst($entityType) }}
                            @elseif($riskLevel === 'high')
                                 Force Delete (High Risk)
                            @elseif($riskLevel === 'medium')  
                                 Delete with Caution
                            @elseif($hasAssociatedData)
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
                @endif
            </div>
        </div>
    </div>
</flux:modal>

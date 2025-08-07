@props([
    'id',
    'title',
    'subtitle' => null,
    'editUrl' => null,
    'editModalName' => null,
    'editClick' => null,
    'addUrl' => null,
    'addModalName' => null,
    'addClick' => null,
    'addText' => 'Add',
    'level' => 0,
    'hasChildren' => true,
    'icon' => null,
    'searchTerms' => [],
])

@php
    $isTopLevel = $level === 0;
@endphp

<div @class([
    'overflow-hidden rounded-lg border shadow-sm',
    'border-stone-200 bg-white dark:border-stone-700 dark:bg-stone-800' => $isTopLevel,
    'border-stone-300 bg-white dark:border-stone-600 dark:bg-stone-800' => !$isTopLevel,
])>
    <div 
        @if($hasChildren)
            x-on:click="open.includes('{{ $id }}') ? open = open.filter(i => i !== '{{ $id }}') : open.push('{{ $id }}')"
        @endif
        @class([
            'flex items-center justify-between group',
            'hover:bg-stone-50 dark:hover:bg-stone-700/50',
            'p-4' => $isTopLevel,
            'p-3' => !$isTopLevel,
            'cursor-pointer' => $hasChildren,
            'transition duration-150 ease-in-out' => $hasChildren,
        ])
    >
        <div class="flex items-center gap-x-4">
            @if ($hasChildren)
                <x-flux::icon.chevron-right 
                    @class([
                        'text-stone-400 transition-transform duration-200 ease-in-out', 
                        'h-5 w-5 group-hover:text-stone-500' => $isTopLevel, 
                        'h-4 w-4 group-hover:text-stone-500' => !$isTopLevel
                    ]) 
                    x-bind:class="{ 'rotate-90': open.includes('{{ $id }}') }" 
                />
            @else
                @if($icon)
                    <x-dynamic-component :component="'flux::icon.'.$icon" @class(['text-stone-500', 'h-5 w-5' => $isTopLevel, 'h-4 w-4' => !$isTopLevel]) />
                @else
                    <span class="h-1.5 w-1.5 rounded-full bg-stone-400 dark:bg-stone-500"></span>
                @endif
            @endif
            <div class="flex-grow">
                <h3 @class([
                    'font-semibold text-stone-800 dark:text-stone-200', 
                    'text-lg' => $isTopLevel, 
                ])>{!! \App\Helpers\TextHelper::highlight($title, $searchTerms) !!}</h3>
                @if ($subtitle)
                    <p @class([
                        'text-stone-500 dark:text-stone-400', 
                        'text-sm' => $isTopLevel, 
                        'text-xs' => !$isTopLevel,
                    ])>{!! \App\Helpers\TextHelper::highlight($subtitle, $searchTerms) !!}</p>
                @endif
            </div>
        </div>
        <div class="flex items-center gap-2">
            @if($addModalName)
                <flux:modal.trigger :name="$addModalName">
                    <button
                        @class([
                            'inline-flex items-center rounded-md border border-stone-300 bg-white px-2.5 py-1.5 font-semibold text-stone-900 shadow-sm hover:bg-stone-50 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-200 dark:hover:bg-stone-700/50',
                            'text-sm' => $isTopLevel,
                            'text-xs' => !$isTopLevel,
                        ])
                        @click.stop
                    >
                        <x-flux::icon.plus class="-ml-0.5 mr-1.5 h-4 w-4" />
                        {{ $addText }}
                    </button>
                </flux:modal.trigger>
            @elseif($addUrl)
                <a href="{{ $addUrl . (str_contains($addUrl, '?') ? '&' : '?') . 'view=tree' }}" wire:navigate
                    @class([
                        'inline-flex items-center rounded-md border border-stone-300 bg-white px-2.5 py-1.5 font-semibold text-stone-900 shadow-sm hover:bg-stone-50 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-200 dark:hover:bg-stone-700/50',
                        'text-sm' => $isTopLevel,
                        'text-xs' => !$isTopLevel,
                    ])
                    @click.stop
                >
                    <x-flux::icon.plus class="-ml-0.5 mr-1.5 h-4 w-4" />
                    {{ $addText }}
                </a>
            @elseif($addClick)
                <button
                    @class([
                        'inline-flex items-center rounded-md border border-stone-300 bg-white px-2.5 py-1.5 font-semibold text-stone-900 shadow-sm hover:bg-stone-50 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-200 dark:hover:bg-stone-700/50',
                        'text-sm' => $isTopLevel,
                        'text-xs' => !$isTopLevel,
                    ])
                    wire:click.stop="{{ $addClick }}"
                >
                    <x-flux::icon.plus class="-ml-0.5 mr-1.5 h-4 w-4" />
                    {{ $addText }}
                </button>
            @endif
            @if($editClick)
                <button
                    @class([
                        'inline-flex items-center rounded-md border border-stone-300 bg-white px-2.5 py-1.5 font-semibold text-stone-900 shadow-sm hover:bg-stone-50 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-200 dark:hover:bg-stone-700/50',
                        'text-sm' => $isTopLevel,
                        'text-xs' => !$isTopLevel,
                    ])
                    wire:click.stop="{{ $editClick }}"
                >
                    <x-flux::icon.pencil class="-ml-0.5 mr-1.5 h-4 w-4" />
                    Edit
                </button>
            @elseif($editModalName)
                <flux:modal.trigger :name="$editModalName">
                    <button
                        @class([
                            'inline-flex items-center rounded-md border border-stone-300 bg-white px-2.5 py-1.5 font-semibold text-stone-900 shadow-sm hover:bg-stone-50 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-200 dark:hover:bg-stone-700/50',
                            'text-sm' => $isTopLevel,
                            'text-xs' => !$isTopLevel,
                        ])
                        @click.stop
                    >
                        <x-flux::icon.pencil class="-ml-0.5 mr-1.5 h-4 w-4" />
                        Edit
                    </button>
                </flux:modal.trigger>
            @elseif ($editUrl)
                <a href="{{ $editUrl . (str_contains($editUrl, '?') ? '&' : '?') . 'view=tree' }}" wire:navigate
                    @class([
                        'inline-flex items-center rounded-md border border-stone-300 bg-white px-2.5 py-1.5 font-semibold text-stone-900 shadow-sm hover:bg-stone-50 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-200 dark:hover:bg-stone-700/50',
                        'text-sm' => $isTopLevel,
                        'text-xs' => !$isTopLevel,
                    ])
                    @click.stop
                >
                    <x-flux::icon.pencil class="-ml-0.5 mr-1.5 h-4 w-4" />
                    Edit
                </a>
            @endif
        </div>
    </div>

    @if ($hasChildren)
        <div 
            x-show="open.includes('{{ $id }}')" 
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 transform -translate-y-2"
            x-transition:enter-end="opacity-100 transform translate-y-0"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 transform translate-y-0"
            x-transition:leave-end="opacity-0 transform -translate-y-2"
            @class([
                'border-t',
                'bg-stone-50/50 dark:bg-stone-900/20',
                'border-stone-200 p-4 pl-12 dark:border-stone-700' => $isTopLevel,
                'border-stone-300 p-3 pl-10 dark:border-stone-600' => !$isTopLevel,
            ])
        >
             <div class="space-y-3">
                {{ $slot }}
            </div>
        </div>
    @endif
</div> 
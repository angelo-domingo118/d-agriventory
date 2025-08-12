<?php

use App\Models\AuditLog;
use App\Models\User;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Validate;
use Carbon\Carbon;

new #[Layout('components.layouts.app')] class extends Component {
    use WithPagination;
    
    #[Validate('nullable|string|max:100')]
    public string $search = '';
    public bool $showFilters = false;
    public int $perPage = 10;
    public string $density = 'spacious';
    public string $textOverflow = 'nowrap';
    public string $fontSize = 'medium';
    
    public string $actionType = '';
    public string $tableName = '';
    public string $userId = '';
    public string $dateFrom = '';
    public string $dateTo = '';
    public string $sortField = 'created_at';
    public string $sortDirection = 'desc';
    
    public function updatedPerPage(): void
    {
        $this->resetPage();
    }
    
    public function updatedSearch()
    {
        // Sanitize search input
        $this->search = htmlspecialchars($this->search, ENT_QUOTES, 'UTF-8');
        $this->resetPage();
    }
    
    public function updatedDateFrom()
    {
        $this->resetPage();
    }
    
    public function updatedDateTo()
    {
        $this->resetPage();
    }
    
    public function resetSorting(): void
    {
        $this->sortField = 'created_at';
        $this->sortDirection = 'desc';
    }
    
    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortDirection = $this->sortField === 'created_at' ? 'desc' : 'asc';
        }
        
        $this->sortField = $field;
    }
    
    #[Computed]
    public function actionTypes()
    {
        return AuditLog::select('action_type')
            ->distinct()
            ->orderBy('action_type')
            ->pluck('action_type')
            ->toArray();
    }
    
    #[Computed]
    public function tableNames()
    {
        return AuditLog::select('table_name')
            ->distinct()
            ->orderBy('table_name')
            ->pluck('table_name')
            ->toArray();
    }
    
    #[Computed]
    public function users()
    {
        return User::select('id', 'name')
            ->whereHas('auditLogs')
            ->orderBy('name')
            ->get();
    }
    
    public function render(): mixed
    {
        $auditLogs = AuditLog::query()
            ->with(['user:id,name,username'])
            ->when($this->search, function ($query) {
                $query->where(function ($query) {
                    $query->where('table_name', 'like', '%' . $this->search . '%')
                        ->orWhere('action_type', 'like', '%' . $this->search . '%')
                        ->orWhere('description', 'like', '%' . $this->search . '%')
                        ->orWhere('record_id', 'like', '%' . $this->search . '%')
                        ->orWhereHas('user', function ($query) {
                            $query->where('name', 'like', '%' . $this->search . '%')
                                ->orWhere('username', 'like', '%' . $this->search . '%');
                        });
                });
            })
            ->when($this->actionType, function ($query) {
                $query->where('action_type', $this->actionType);
            })
            ->when($this->tableName, function ($query) {
                $query->where('table_name', $this->tableName);
            })
            ->when($this->userId, function ($query) {
                $query->where('user_id', $this->userId);
            })
            ->when($this->dateFrom, function ($query) {
                $query->whereDate('created_at', '>=', $this->dateFrom);
            })
            ->when($this->dateTo, function ($query) {
                $query->whereDate('created_at', '<=', $this->dateTo);
            })
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate($this->perPage);
            
        return view('livewire.admin.system.audit-logs.index', [
            'auditLogs' => $auditLogs,
        ]);
    }
    
    public function getActionTypeBadgeClass($actionType)
    {
        return match($actionType) {
            'CREATE' => 'bg-green-100 text-green-800 dark:bg-green-900/50 dark:text-green-300',
            'UPDATE' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/50 dark:text-yellow-300',
            'DELETE' => 'bg-red-100 text-red-800 dark:bg-red-900/50 dark:text-red-300',
            default => 'bg-stone-100 text-stone-800 dark:bg-stone-700 dark:text-stone-300',
        };
    }
    
    public function formatTableName($tableName)
    {
        return ucwords(str_replace('_', ' ', $tableName));
    }
}; ?>

<div x-data="{ 
    showFilters: @entangle('showFilters'),
    ...tableSettings('audit_logs_settings')
}">
    <div x-data="tableResizer('audit_logs_column_widths', { user: 180, action: 120, table: 180, record: 100, description: 300, timestamp: 180, actions: 100 })">
        <div class="flex items-center justify-between mb-4">
            <!-- Breadcrumbs as Title -->
            <div>
                <flux:breadcrumbs class="text-2xl font-semibold">
                    <flux:breadcrumbs.item :href="route('admin.dashboard')" wire:navigate icon="home" class="text-xl sm:text-2xl font-semibold text-stone-700 dark:text-stone-300" />
                    <flux:breadcrumbs.item class="text-xl sm:text-2xl font-semibold text-stone-500 dark:text-stone-400">System</flux:breadcrumbs.item>
                    <flux:breadcrumbs.item class="text-xl sm:text-2xl font-semibold text-stone-900 dark:text-stone-100">Audit Logs</flux:breadcrumbs.item>
                </flux:breadcrumbs>
            </div>
            
            <!-- Action Buttons -->
            <div class="flex items-center gap-x-2">
                <div x-data="{ open: false }" class="relative">
                    <flux:button variant="outline" x-on:click="open = !open" class="!p-2">
                        <x-flux::icon.settings-2 class="h-5 w-5" />
                        <span class="sr-only">Toggle View Options</span>
                    </flux:button>
                    <div x-show="open" x-on:click.outside="open = false" x-transition class="absolute right-0 z-10 mt-2 w-80 origin-top-right rounded-md bg-white py-1 shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none dark:bg-stone-800 dark:ring-stone-700" style="display: none;">
                        <!-- Density -->
                        <div class="px-3 py-2">
                            <div class="text-xs font-semibold uppercase text-stone-500 dark:text-stone-400">Density</div>
                            <div class="mt-2 flex overflow-hidden rounded-md border border-stone-200 dark:border-stone-700">
                                <button 
                                    @click="updateSetting('density', 'compact')" 
                                    class="flex-1 px-3 py-1.5 text-center text-sm focus:z-10 focus:outline-none focus:ring-2 focus:ring-primary-500 {{ $density === 'compact' ? 'bg-stone-100 dark:bg-stone-700' : 'hover:bg-stone-50 dark:hover:bg-stone-900/50' }}"
                                >
                                    Compact
                                </button>
                                <button 
                                    @click="updateSetting('density', 'comfortable')" 
                                    class="-ml-px flex-1 border-x border-stone-200 px-3 py-1.5 text-center text-sm focus:z-10 focus:outline-none focus:ring-2 focus:ring-primary-500 dark:border-stone-700 {{ $density === 'comfortable' ? 'bg-stone-100 dark:bg-stone-700' : 'hover:bg-stone-50 dark:hover:bg-stone-900/50' }}"
                                >
                                    Comfortable
                                </button>
                                <button 
                                    @click="updateSetting('density', 'spacious')" 
                                    class="-ml-px flex-1 px-3 py-1.5 text-center text-sm focus:z-10 focus:outline-none focus:ring-2 focus:ring-primary-500 {{ $density === 'spacious' ? 'bg-stone-100 dark:bg-stone-700' : 'hover:bg-stone-50 dark:hover:bg-stone-900/50' }}"
                                >
                                    Spacious
                                </button>
                                                    </div>
                    </div>

                    <!-- Text Overflow -->
                    <div class="border-t border-stone-200 px-3 py-2 dark:border-stone-700">
                        <div class="text-xs font-semibold uppercase text-stone-500 dark:text-stone-400">Text Overflow</div>
                        <div class="mt-2 flex overflow-hidden rounded-md border border-stone-200 dark:border-stone-700">
                            <button 
                                @click="updateSetting('textOverflow', 'nowrap')" 
                                class="flex-1 px-3 py-1.5 text-center text-sm focus:z-10 focus:outline-none focus:ring-2 focus:ring-primary-500 {{ $textOverflow === 'nowrap' ? 'bg-stone-100 dark:bg-stone-700' : 'hover:bg-stone-50 dark:hover:bg-stone-900/50' }}"
                            >
                                No Wrap
                            </button>
                            <button 
                                @click="updateSetting('textOverflow', 'wrap')" 
                                class="-ml-px flex-1 border-x border-stone-200 px-3 py-1.5 text-center text-sm focus:z-10 focus:outline-none focus:ring-2 focus:ring-primary-500 dark:border-stone-700 {{ $textOverflow === 'wrap' ? 'bg-stone-100 dark:bg-stone-700' : 'hover:bg-stone-50 dark:hover:bg-stone-900/50' }}"
                            >
                                Wrap Text
                            </button>
                            <button 
                                @click="updateSetting('textOverflow', 'scroll')" 
                                class="-ml-px flex-1 px-3 py-1.5 text-center text-sm focus:z-10 focus:outline-none focus:ring-2 focus:ring-primary-500 {{ $textOverflow === 'scroll' ? 'bg-stone-100 dark:bg-stone-700' : 'hover:bg-stone-50 dark:hover:bg-stone-900/50' }}"
                            >
                                Scroll
                            </button>
                        </div>
                    </div>
                    
                    <!-- Font Size -->
                    <div class="border-t border-stone-200 px-3 py-2 dark:border-stone-700">
                        <div class="text-xs font-semibold uppercase text-stone-500 dark:text-stone-400">Font Size</div>
                        <div class="mt-2 grid grid-cols-2 gap-1 overflow-hidden rounded-md border border-stone-200 dark:border-stone-700">
                            <button 
                                @click="updateSetting('fontSize', 'small')" 
                                class="px-2 py-1.5 text-center text-xs focus:z-10 focus:outline-none focus:ring-2 focus:ring-primary-500 rounded-tl-sm {{ $fontSize === 'small' ? 'bg-stone-100 dark:bg-stone-700' : 'hover:bg-stone-50 dark:hover:bg-stone-900/50' }}"
                            >
                                Small
                            </button>
                            <button 
                                @click="updateSetting('fontSize', 'medium')" 
                                class="px-2 py-1.5 text-center text-xs focus:z-10 focus:outline-none focus:ring-2 focus:ring-primary-500 rounded-tr-sm {{ $fontSize === 'medium' ? 'bg-stone-100 dark:bg-stone-700' : 'hover:bg-stone-50 dark:hover:bg-stone-900/50' }}"
                            >
                                Medium
                            </button>
                            <button 
                                @click="updateSetting('fontSize', 'large')" 
                                class="px-2 py-1.5 text-center text-xs focus:z-10 focus:outline-none focus:ring-2 focus:ring-primary-500 rounded-bl-sm {{ $fontSize === 'large' ? 'bg-stone-100 dark:bg-stone-700' : 'hover:bg-stone-50 dark:hover:bg-stone-900/50' }}"
                            >
                                Large
                            </button>
                            <button 
                                @click="updateSetting('fontSize', 'xl')" 
                                class="px-2 py-1.5 text-center text-xs focus:z-10 focus:outline-none focus:ring-2 focus:ring-primary-500 rounded-br-sm {{ $fontSize === 'xl' ? 'bg-stone-100 dark:bg-stone-700' : 'hover:bg-stone-50 dark:hover:bg-stone-900/50' }}"
                            >
                                Extra Large
                            </button>
                        </div>
                    </div>

                    <!-- Items per Page -->
                        <div class="border-t border-stone-200 px-3 py-2 dark:border-stone-700">
                            <div class="text-xs font-semibold uppercase text-stone-500 dark:text-stone-400">Items per Page</div>
                            <div class="mt-2 flex overflow-hidden rounded-md border border-stone-200 dark:border-stone-700">
                                @foreach ([5, 10, 25, 50, 100] as $count)
                                    <button
                                        @click="updateSetting('perPage', {{ $count }})"
                                        class="@if(!$loop->first) -ml-px border-l border-stone-200 dark:border-stone-700 @endif flex-1 px-3 py-1.5 text-center text-sm focus:z-10 focus:outline-none focus:ring-2 focus:ring-primary-500 {{ $perPage == $count ? 'bg-stone-100 dark:bg-stone-700' : 'hover:bg-stone-50 dark:hover:bg-stone-900/50' }}"
                                    >
                                        {{ $count }}
                                    </button>
                                @endforeach
                            </div>
                        </div>

                        <!-- Table Customization -->
                        <div class="border-t border-stone-200 px-3 py-2 dark:border-stone-700">
                            <div class="mb-2 text-xs font-semibold uppercase text-stone-500 dark:text-stone-400">Table Customization</div>
                            <div class="space-y-2">
                                <flux:button
                                    variant="outline"
                                    x-on:click="$dispatch('reset-column-widths')"
                                    class="w-full justify-center"
                                >
                                    <x-flux::icon.rotate-cw class="mr-2 h-4 w-4" />
                                    Reset Column Widths
                                </flux:button>
                                <flux:button
                                    variant="outline"
                                    wire:click="resetSorting"
                                    class="w-full justify-center"
                                >
                                    <div class="flex items-center">
                                        <x-flux::icon.chevrons-up-down class="mr-2 h-4 w-4" />
                                        Reset Sort Order
                                    </div>
                                </flux:button>
                            </div>
                        </div>
                    </div>
                </div>
                <flux:button variant="outline" wire:click="$refresh" class="!p-2">
                    <x-flux::icon.rotate-cw class="h-5 w-5" wire:loading.class="animate-spin" />
                    <span class="sr-only">Refresh</span>
                </flux:button>
                <flux:button variant="outline" x-on:click="$wire.showFilters = !$wire.showFilters" class="!p-2">
                    <x-flux::icon.filter class="h-5 w-5" />
                    <span class="sr-only">Toggle Filters</span>
                </flux:button>
            </div>
        </div>
    
        <div x-show="$wire.showFilters" x-collapse class="mt-4">
            <div class="overflow-hidden rounded-lg border border-stone-200 bg-white shadow-sm dark:border-stone-700 dark:bg-stone-800">
                <div class="border-b border-stone-200 p-4 dark:border-stone-700">
                    <h3 class="font-semibold text-stone-800 dark:text-stone-200">Filter Options</h3>
                </div>
                <div class="p-4">
                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                        <div class="sm:col-span-1">
                            <flux:select wire:model.live="actionType" id="actionType" :label="__('Action Type')">
                                <option value="">{{ __('All Actions') }}</option>
                                @foreach($this->actionTypes as $action)
                                    <option value="{{ $action }}">{{ $action }}</option>
                                @endforeach
                            </flux:select>
                        </div>
                        <div class="sm:col-span-1">
                            <flux:select wire:model.live="tableName" id="tableName" :label="__('Table')">
                                <option value="">{{ __('All Tables') }}</option>
                                @foreach($this->tableNames as $table)
                                    <option value="{{ $table }}">{{ $this->formatTableName($table) }}</option>
                                @endforeach
                            </flux:select>
                        </div>
                        <div class="sm:col-span-1">
                            <flux:select wire:model.live="userId" id="userId" :label="__('User')">
                                <option value="">{{ __('All Users') }}</option>
                                @foreach($this->users as $user)
                                    <option value="{{ $user->id }}">{{ $user->name }}</option>
                                @endforeach
                            </flux:select>
                        </div>
                        <div class="sm:col-span-1">
                            <flux:input 
                                wire:model.live="dateFrom" 
                                type="date" 
                                :label="__('Date From')"
                                id="dateFrom"
                            />
                        </div>
                        <div class="sm:col-span-1">
                            <flux:input 
                                wire:model.live="dateTo" 
                                type="date" 
                                :label="__('Date To')"
                                id="dateTo"
                            />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    
        <div class="mt-4 flex items-center justify-between">
            <div class="text-sm text-stone-600 dark:text-stone-400">
                 @if ($auditLogs->total() > 0)
                    <span>Showing {{ $auditLogs->firstItem() }} to {{ $auditLogs->lastItem() }} of <strong>{{ $auditLogs->total() }}</strong> results.</span>
                @else
                    <span>No results found.</span>
                @endif
            </div>
            <div class="w-full max-w-xs">
                <flux:input
                    wire:model.live.debounce.300ms="search"
                    placeholder="Search logs..."
                    icon="magnifying-glass"
                    clearable
                />
            </div>
        </div>

        @php
            $densityClasses = [
                'table_header' => match($density) {
                    'compact' => 'py-2 px-4',
                    'comfortable' => 'py-2.5 px-4',
                    default => 'py-3 px-4',
                },
                'table_cell' => match($density) {
                    'compact' => 'py-2 px-4',
                    'comfortable' => 'py-3 px-4',
                    default => 'py-4 px-4',
                },
                'text_header' => match($fontSize) {
                    'small' => 'text-xs',
                    'large' => 'text-base',
                    'xl' => 'text-lg',
                    default => match($density) {
                        'compact' => 'text-xs',
                        default => 'text-sm',
                    },
                },
                'text_base' => match($fontSize) {
                    'small' => 'text-xs',
                    'large' => 'text-base',
                    'xl' => 'text-lg',
                    default => match($density) {
                        'compact' => 'text-xs',
                        default => 'text-sm',
                    },
                },
                'text_overflow' => match($textOverflow) {
                    'wrap' => 'break-words',
                    'scroll' => 'whitespace-nowrap',
                    default => 'whitespace-nowrap truncate',
                },
                'table_wrapper' => match($textOverflow) {
                    'scroll' => 'overflow-x-auto',
                    default => 'overflow-x-auto',
                },
            ];
        @endphp
    
        <div class="mt-4 flow-root">
            <div class="-mx-4 -my-2 {{ $densityClasses['table_wrapper'] }} sm:-mx-6 lg:-mx-8">
                <div class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">
                    <div class="overflow-hidden rounded-lg shadow ring-1 ring-black ring-opacity-5 dark:ring-stone-700">
                        <table class="min-w-full divide-y divide-stone-300 dark:divide-stone-700 table-fixed">
                            <thead class="bg-stone-50 dark:bg-stone-800">
                                <tr class="divide-x divide-stone-200 dark:divide-stone-700">
                                    <th scope="col" :style="`width: ${columnWidths.user}px`" class="relative {{ $densityClasses['table_header'] }} text-left {{ $densityClasses['text_header'] }} font-medium uppercase tracking-wider text-stone-500 dark:text-stone-400">
                                        <div wire:click="sortBy('user_id')" class="flex items-center cursor-pointer">
                                            User
                                            @if($sortField === 'user_id')
                                                @if($sortDirection === 'asc') <x-flux::icon.chevron-up class="ml-2 h-4 w-4" /> @else <x-flux::icon.chevron-down class="ml-2 h-4 w-4" /> @endif
                                            @else
                                                <x-flux::icon.chevrons-up-down class="ml-2 h-4 w-4 text-stone-400 dark:text-stone-500" />
                                            @endif
                                        </div>
                                        <div @mousedown="startResize($event, 'user')" class="absolute top-0 right-0 z-10 w-1.5 h-full cursor-col-resize select-none"></div>
                                    </th>
                                    <th scope="col" :style="`width: ${columnWidths.action}px`" class="relative {{ $densityClasses['table_header'] }} text-left {{ $densityClasses['text_header'] }} font-medium uppercase tracking-wider text-stone-500 dark:text-stone-400">
                                        <div wire:click="sortBy('action_type')" class="flex items-center cursor-pointer">
                                            Action
                                            @if($sortField === 'action_type')
                                                @if($sortDirection === 'asc') <x-flux::icon.chevron-up class="ml-2 h-4 w-4" /> @else <x-flux::icon.chevron-down class="ml-2 h-4 w-4" /> @endif
                                            @else
                                                <x-flux::icon.chevrons-up-down class="ml-2 h-4 w-4 text-stone-400 dark:text-stone-500" />
                                            @endif
                                        </div>
                                        <div @mousedown="startResize($event, 'action')" class="absolute top-0 right-0 z-10 w-1.5 h-full cursor-col-resize select-none"></div>
                                    </th>
                                    <th scope="col" :style="`width: ${columnWidths.table}px`" class="relative {{ $densityClasses['table_header'] }} text-left {{ $densityClasses['text_header'] }} font-medium uppercase tracking-wider text-stone-500 dark:text-stone-400">
                                        <div wire:click="sortBy('table_name')" class="flex items-center cursor-pointer">
                                            Table
                                            @if($sortField === 'table_name')
                                                @if($sortDirection === 'asc') <x-flux::icon.chevron-up class="ml-2 h-4 w-4" /> @else <x-flux::icon.chevron-down class="ml-2 h-4 w-4" /> @endif
                                            @else
                                                <x-flux::icon.chevrons-up-down class="ml-2 h-4 w-4 text-stone-400 dark:text-stone-500" />
                                            @endif
                                        </div>
                                        <div @mousedown="startResize($event, 'table')" class="absolute top-0 right-0 z-10 w-1.5 h-full cursor-col-resize select-none"></div>
                                    </th>
                                    <th scope="col" :style="`width: ${columnWidths.record}px`" class="relative {{ $densityClasses['table_header'] }} text-left {{ $densityClasses['text_header'] }} font-medium uppercase tracking-wider text-stone-500 dark:text-stone-400">
                                        <div wire:click="sortBy('record_id')" class="flex items-center cursor-pointer">
                                            Record ID
                                            @if($sortField === 'record_id')
                                                @if($sortDirection === 'asc') <x-flux::icon.chevron-up class="ml-2 h-4 w-4" /> @else <x-flux::icon.chevron-down class="ml-2 h-4 w-4" /> @endif
                                            @else
                                                <x-flux::icon.chevrons-up-down class="ml-2 h-4 w-4 text-stone-400 dark:text-stone-500" />
                                            @endif
                                        </div>
                                        <div @mousedown="startResize($event, 'record')" class="absolute top-0 right-0 z-10 w-1.5 h-full cursor-col-resize select-none"></div>
                                    </th>
                                    <th scope="col" :style="`width: ${columnWidths.description}px`" class="relative {{ $densityClasses['table_header'] }} text-left {{ $densityClasses['text_header'] }} font-medium uppercase tracking-wider text-stone-500 dark:text-stone-400">
                                        Description
                                        <div @mousedown="startResize($event, 'description')" class="absolute top-0 right-0 z-10 w-1.5 h-full cursor-col-resize select-none"></div>
                                    </th>
                                    <th scope="col" :style="`width: ${columnWidths.timestamp}px`" class="relative {{ $densityClasses['table_header'] }} text-left {{ $densityClasses['text_header'] }} font-medium uppercase tracking-wider text-stone-500 dark:text-stone-400">
                                        <div wire:click="sortBy('created_at')" class="flex items-center cursor-pointer">
                                            Timestamp
                                            @if($sortField === 'created_at')
                                                @if($sortDirection === 'asc') <x-flux::icon.chevron-up class="ml-2 h-4 w-4" /> @else <x-flux::icon.chevron-down class="ml-2 h-4 w-4" /> @endif
                                            @else
                                                <x-flux::icon.chevrons-up-down class="ml-2 h-4 w-4 text-stone-400 dark:text-stone-500" />
                                            @endif
                                        </div>
                                        <div @mousedown="startResize($event, 'timestamp')" class="absolute top-0 right-0 z-10 w-1.5 h-full cursor-col-resize select-none"></div>
                                    </th>
                                    <th scope="col" :style="`width: ${columnWidths.actions}px`" class="relative {{ $densityClasses['table_header'] }} text-left {{ $densityClasses['text_header'] }} font-medium uppercase tracking-wider text-stone-500 dark:text-stone-400">
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-stone-200 bg-white dark:divide-stone-800 dark:bg-stone-900">
                                @forelse ($auditLogs as $log)
                                    <tr wire:key="{{ $log->id }}" class="divide-x divide-stone-200 dark:divide-stone-700 hover:bg-stone-50 dark:hover:bg-stone-800/50">
                                        <td class="{{ $densityClasses['text_overflow'] }} {{ $densityClasses['table_cell'] }} {{ $densityClasses['text_base'] }}" title="@if($log->user){{ $log->user->name }} ({{ $log->user->username }})@else System @endif">
                                            @if($log->user)
                                                <div class="flex items-center">
                                                    <div class="h-8 w-8 flex-shrink-0">
                                                        <div class="flex h-8 w-8 items-center justify-center rounded-full bg-stone-100 dark:bg-stone-700 text-xs">
                                                            {{ $log->user->initials() }}
                                                        </div>
                                                    </div>
                                                    <div class="ml-3">
                                                        <div class="font-medium text-stone-900 dark:text-stone-100">{{ $log->user->name }}</div>
                                                        <div class="text-stone-500 dark:text-stone-400 text-xs">{{ $log->user->username }}</div>
                                                    </div>
                                                </div>
                                            @else
                                                <span class="text-stone-500 dark:text-stone-400 italic">System</span>
                                            @endif
                                        </td>
                                        <td class="{{ $densityClasses['text_overflow'] }} {{ $densityClasses['table_cell'] }} {{ $densityClasses['text_base'] }} text-stone-500 dark:text-stone-400" title="{{ $log->action_type }}">
                                            <span class="inline-flex rounded-full px-2 text-xs font-semibold leading-5 {{ $this->getActionTypeBadgeClass($log->action_type) }}">
                                                {{ $log->action_type }}
                                            </span>
                                        </td>
                                        <td class="{{ $densityClasses['text_overflow'] }} {{ $densityClasses['table_cell'] }} {{ $densityClasses['text_base'] }} text-stone-900 dark:text-stone-100" title="{{ $this->formatTableName($log->table_name) }} ({{ $log->table_name }})">
                                            <div class="font-medium">{{ $this->formatTableName($log->table_name) }}</div>
                                            <div class="text-stone-500 dark:text-stone-400 text-xs">{{ $log->table_name }}</div>
                                        </td>
                                        <td class="{{ $densityClasses['text_overflow'] }} {{ $densityClasses['table_cell'] }} {{ $densityClasses['text_base'] }} text-stone-900 dark:text-stone-100" title="{{ $log->record_id }}">
                                            <span class="font-mono bg-stone-100 dark:bg-stone-700 px-2 py-1 rounded text-xs">
                                                {{ $log->record_id }}
                                            </span>
                                        </td>
                                        <td class="{{ $densityClasses['text_overflow'] }} {{ $densityClasses['table_cell'] }} {{ $densityClasses['text_base'] }} text-stone-500 dark:text-stone-400" title="{{ $log->description }}">
                                            <div>
                                                {{ $log->description ?: 'No description' }}
                                            </div>
                                        </td>
                                        <td class="{{ $densityClasses['text_overflow'] }} {{ $densityClasses['table_cell'] }} {{ $densityClasses['text_base'] }} text-stone-500 dark:text-stone-400" title="{{ $log->created_at->format('Y-m-d H:i:s T') }}">
                                            <div class="text-stone-900 dark:text-stone-100">{{ $log->created_at->format('M j, Y') }}</div>
                                            <div class="text-stone-500 dark:text-stone-400 text-xs">{{ $log->created_at->format('g:i A') }}</div>
                                        </td>
                                        <td class="relative whitespace-nowrap {{ $densityClasses['table_cell'] }} text-right {{ $densityClasses['text_base'] }} font-medium">
                                            <div x-data="{ open: false }" class="relative">
                                                <button 
                                                    class="inline-flex items-center rounded-md border border-stone-300 bg-white px-2.5 py-1.5 {{ $densityClasses['text_base'] }} font-semibold text-stone-900 shadow-sm hover:bg-stone-50 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-200 dark:hover:bg-stone-700/50"
                                                    x-on:click="open = !open">
                                                    <x-flux::icon.eye class="mr-1.5 h-4 w-4" />
                                                    View
                                                </button>
                                                
                                                <div x-show="open" x-on:click.outside="open = false" x-transition class="absolute right-0 z-10 mt-2 w-96 origin-top-right rounded-md bg-white py-2 shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none dark:bg-stone-800 dark:ring-stone-700" style="display: none;">
                                                    <div class="px-4 py-3">
                                                        <h4 class="text-sm font-semibold text-stone-900 dark:text-stone-100 mb-2">Log Details</h4>
                                                        
                                                        @if($log->old_values)
                                                            <div class="mb-3">
                                                                <h5 class="text-xs font-medium text-stone-700 dark:text-stone-300 mb-1">Old Values:</h5>
                                                                <pre class="text-xs bg-stone-100 dark:bg-stone-700 p-2 rounded overflow-x-auto max-h-32">{{ json_encode($log->old_values, JSON_PRETTY_PRINT) }}</pre>
                                                            </div>
                                                        @endif
                                                        
                                                        @if($log->new_values)
                                                            <div class="mb-3">
                                                                <h5 class="text-xs font-medium text-stone-700 dark:text-stone-300 mb-1">New Values:</h5>
                                                                <pre class="text-xs bg-stone-100 dark:bg-stone-700 p-2 rounded overflow-x-auto max-h-32">{{ json_encode($log->new_values, JSON_PRETTY_PRINT) }}</pre>
                                                            </div>
                                                        @endif
                                                        
                                                        <div class="text-xs text-stone-500 dark:text-stone-400">
                                                            <strong>Full timestamp:</strong> {{ $log->created_at->format('Y-m-d H:i:s T') }}
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="whitespace-nowrap {{ $densityClasses['table_cell'] }} text-center {{ $densityClasses['text_base'] }} text-stone-500 dark:text-stone-400">
                                            <div class="flex flex-col items-center">
                                                <x-flux::icon.document-text class="h-12 w-12 text-stone-400 dark:text-stone-500 mb-3" />
                                                <p class="text-lg font-medium text-stone-900 dark:text-stone-100 mb-1">No audit logs found</p>
                                                <p class="text-stone-500 dark:text-stone-400">No audit logs match your current search criteria.</p>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
                <div class="mt-4">
            {{ $auditLogs->links() }}
        </div>
    </div>
</div>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('tableResizer', (storageKey, defaultWidths) => ({
            columnWidths: {},
            resizingColumn: null,
            startX: 0,
            startWidth: 0,
            init() {
                const storedWidths = JSON.parse(localStorage.getItem(storageKey) || '{}');
                this.columnWidths = { ...defaultWidths, ...storedWidths };
                
                this.$root.addEventListener('reset-column-widths', () => {
                    this.columnWidths = { ...defaultWidths };
                    localStorage.removeItem(storageKey);
                });
            },
            startResize(event, column) {
                this.resizingColumn = column;
                this.startX = event.clientX;
                this.startWidth = this.columnWidths[column];
                event.preventDefault();

                const mouseMoveHandler = (e) => {
                    if (!this.resizingColumn) return;
                    const diffX = e.clientX - this.startX;
                    const newWidth = this.startWidth + diffX;
                    if (newWidth > 40) { // min width 40px
                        this.columnWidths[this.resizingColumn] = newWidth;
                    }
                };

                const mouseUpHandler = () => {
                    if (!this.resizingColumn) return;
                    this.resizingColumn = null;
                    localStorage.setItem(storageKey, JSON.stringify(this.columnWidths));
                    window.removeEventListener('mousemove', mouseMoveHandler);
                    window.removeEventListener('mouseup', mouseUpHandler);
                };

                window.addEventListener('mousemove', mouseMoveHandler);
                window.addEventListener('mouseup', mouseUpHandler);
            }
        }));

        Alpine.data('tableSettings', (storageKey) => ({
            init() {
                const saved = JSON.parse(localStorage.getItem(storageKey) || '{}');
                if (saved.density) this.$wire.density = saved.density;
                if (saved.perPage) this.$wire.perPage = saved.perPage;
                if (saved.textOverflow) this.$wire.textOverflow = saved.textOverflow;
                if (saved.fontSize) this.$wire.fontSize = saved.fontSize;
            },
            updateSetting(key, value) {
                this.$wire[key] = value;
                const saved = JSON.parse(localStorage.getItem(storageKey) || '{}');
                saved[key] = value;
                localStorage.setItem(storageKey, JSON.stringify(saved));
            }
        }));
    });
</script> 
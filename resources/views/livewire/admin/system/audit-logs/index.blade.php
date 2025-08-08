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

<div>
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

                <div x-show="open" x-on:click.outside="open = false" x-transition class="absolute right-0 z-10 mt-2 w-72 origin-top-right rounded-md bg-white py-1 shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none dark:bg-stone-800 dark:ring-stone-700" style="display: none;">
                    <div class="px-3 py-2">
                        <div class="text-xs font-semibold uppercase text-stone-500 dark:text-stone-400">Items per Page</div>
                        <flux:select wire:model.live="perPage" id="perPage" class="mt-1">
                            <option value="5">5</option>
                            <option value="10">10</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                        </flux:select>
                    </div>
                    <div class="border-t border-stone-200 px-3 py-2 dark:border-stone-700">
                        <div class="mb-2 text-xs font-semibold uppercase text-stone-500 dark:text-stone-400">Column Layout</div>
                        <flux:button variant="ghost" x-on:click="$dispatch('reset-column-widths')" class="w-full justify-center">
                            <x-flux::icon.rotate-cw class="mr-2 h-4 w-4" />
                            Reset Column Widths
                        </flux:button>
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

    <div x-data="tableResizer('audit_logs_column_widths', { user: 180, action: 120, table: 180, record: 100, description: 300, timestamp: 180, actions: 100 })">
    
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
    
        <div class="mt-4 flow-root">
            <div class="-mx-4 -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
                <div class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">
                    <div class="overflow-hidden rounded-lg shadow ring-1 ring-black ring-opacity-5 dark:ring-stone-700">
                        <table class="min-w-full divide-y divide-stone-300 dark:divide-stone-700 table-fixed">
                            <thead class="bg-stone-50 dark:bg-stone-800">
                                <tr class="divide-x divide-stone-200 dark:divide-stone-700">
                                    <th scope="col" :style="`width: ${columnWidths.user}px`" class="relative py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-stone-900 dark:text-stone-100 sm:pl-6">
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
                                    <th scope="col" :style="`width: ${columnWidths.action}px`" class="relative px-3 py-3.5 text-left text-sm font-semibold text-stone-900 dark:text-stone-100">
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
                                    <th scope="col" :style="`width: ${columnWidths.table}px`" class="relative px-3 py-3.5 text-left text-sm font-semibold text-stone-900 dark:text-stone-100">
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
                                    <th scope="col" :style="`width: ${columnWidths.record}px`" class="relative px-3 py-3.5 text-left text-sm font-semibold text-stone-900 dark:text-stone-100">
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
                                    <th scope="col" :style="`width: ${columnWidths.description}px`" class="relative px-3 py-3.5 text-left text-sm font-semibold text-stone-900 dark:text-stone-100">
                                        Description
                                        <div @mousedown="startResize($event, 'description')" class="absolute top-0 right-0 z-10 w-1.5 h-full cursor-col-resize select-none"></div>
                                    </th>
                                    <th scope="col" :style="`width: ${columnWidths.timestamp}px`" class="relative px-3 py-3.5 text-left text-sm font-semibold text-stone-900 dark:text-stone-100">
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
                                    <th scope="col" :style="`width: ${columnWidths.actions}px`" class="relative py-3.5 pl-3 pr-4 sm:pr-6 text-left text-sm font-semibold text-stone-900 dark:text-stone-100">
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-stone-200 bg-white dark:divide-stone-800 dark:bg-stone-900">
                                @forelse ($auditLogs as $log)
                                    <tr wire:key="{{ $log->id }}" class="divide-x divide-stone-200 dark:divide-stone-700">
                                        <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm sm:pl-6">
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
                                        <td class="whitespace-nowrap px-3 py-4 text-sm text-stone-500 dark:text-stone-400">
                                            <span class="inline-flex rounded-full px-2 text-xs font-semibold leading-5 {{ $this->getActionTypeBadgeClass($log->action_type) }}">
                                                {{ $log->action_type }}
                                            </span>
                                        </td>
                                        <td class="whitespace-nowrap px-3 py-4 text-sm text-stone-900 dark:text-stone-100">
                                            <div class="font-medium">{{ $this->formatTableName($log->table_name) }}</div>
                                            <div class="text-stone-500 dark:text-stone-400 text-xs">{{ $log->table_name }}</div>
                                        </td>
                                        <td class="whitespace-nowrap px-3 py-4 text-sm text-stone-900 dark:text-stone-100">
                                            <span class="font-mono bg-stone-100 dark:bg-stone-700 px-2 py-1 rounded text-xs">
                                                {{ $log->record_id }}
                                            </span>
                                        </td>
                                        <td class="px-3 py-4 text-sm text-stone-500 dark:text-stone-400">
                                            <div class="max-w-xs truncate" title="{{ $log->description }}">
                                                {{ $log->description ?: 'No description' }}
                                            </div>
                                        </td>
                                        <td class="whitespace-nowrap px-3 py-4 text-sm text-stone-500 dark:text-stone-400">
                                            <div class="text-stone-900 dark:text-stone-100">{{ $log->created_at->format('M j, Y') }}</div>
                                            <div class="text-stone-500 dark:text-stone-400 text-xs">{{ $log->created_at->format('g:i A') }}</div>
                                        </td>
                                        <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-6">
                                            <div x-data="{ open: false }" class="relative">
                                                <flux:button variant="ghost" x-on:click="open = !open" class="!p-2">
                                                    <x-flux::icon.eye class="h-4 w-4" />
                                                    <span class="sr-only">View Details</span>
                                                </flux:button>
                                                
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
                                        <td colspan="7" class="whitespace-nowrap px-3 py-12 text-center text-sm text-stone-500 dark:text-stone-400">
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
    });
</script> 
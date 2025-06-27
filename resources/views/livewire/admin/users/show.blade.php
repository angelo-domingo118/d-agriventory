<?php

use App\Models\User;
use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\WithPagination;

new #[Layout('components.layouts.app')] class extends Component {
    use WithPagination;
    
    public User $user;
    
    public function mount(User $user): void
    {
        $this->user = $user->load(['adminUser', 'divisionInventoryManager']);
    }
    
    public function getAuditLogsProperty()
    {
        return $this->user->auditLogs()->latest()->paginate(5);
    }
    
    public function getFriendlyModelName(string $modelClass): string
    {
        $modelMap = [
            'App\\Models\\User' => 'User',
            'App\\Models\\AdminUser' => 'Admin User',
            'App\\Models\\Division' => 'Division',
            'App\\Models\\Employee' => 'Employee',
            'App\\Models\\DivisionInventoryManager' => 'Division Manager',
            'App\\Models\\IcsNumber' => 'ICS Number',
            'App\\Models\\ParNumber' => 'PAR Number',
            'App\\Models\\IdrNumber' => 'IDR Number',
        ];
        
        return $modelMap[$modelClass] ?? class_basename($modelClass);
    }
}; ?>

<div>
    <div class="max-w-7xl mx-auto py-10 sm:px-6 lg:px-8">
        <div class="mb-5 flex justify-between items-center">
            <h1 class="text-2xl font-semibold text-stone-900 dark:text-stone-100">{{ __('User Details') }}</h1>
            <div class="flex space-x-2">
                <flux:button :href="route('admin.users.index')" wire:navigate variant="ghost">
                    {{ __('Back to Users') }}
                </flux:button>
                
                @adminpermission('edit_users')
                <flux:button :href="route('admin.users.edit', $user)" wire:navigate variant="primary">
                    {{ __('Edit User') }}
                </flux:button>
                @endadminpermission
            </div>
        </div>

        <div class="bg-white dark:bg-stone-900 border-stone-200 dark:border-stone-700 shadow overflow-hidden sm:rounded-lg">
            <div class="px-4 py-5 sm:px-6 bg-stone-50 dark:bg-stone-800/50">
                <div class="flex items-center">
                    <div class="flex-shrink-0 h-12 w-12 flex items-center justify-center bg-stone-100 dark:bg-stone-700 rounded-full">
                        {{ $user->initials() }}
                    </div>
                    <div class="ml-4">
                        <h3 class="text-lg leading-6 font-medium text-stone-900 dark:text-stone-100">{{ $user->name }}</h3>
                        <p class="text-sm text-stone-500 dark:text-stone-400">
                            {{ $user->email }}
                            @if ($user->email_verified_at)
                                <span class="ml-2 text-green-600 dark:text-green-400">{{ __('Verified') }}</span>
                            @else
                                <span class="ml-2 text-red-600 dark:text-red-400">{{ __('Not Verified') }}</span>
                            @endif
                        </p>
                    </div>
                </div>
            </div>
            <div class="border-t border-stone-200 dark:border-stone-700">
                <dl>
                    <div class="bg-white dark:bg-stone-900 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-stone-500 dark:text-stone-400">{{ __('Username') }}</dt>
                        <dd class="mt-1 text-sm text-stone-900 dark:text-stone-100 sm:mt-0 sm:col-span-2">{{ $user->username }}</dd>
                    </div>
                    <div class="bg-stone-50 dark:bg-stone-800/50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-stone-500 dark:text-stone-400">{{ __('User Type') }}</dt>
                        <dd class="mt-1 text-sm text-stone-900 dark:text-stone-100 sm:mt-0 sm:col-span-2">
                            @if ($user->adminUser)
                                <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 dark:bg-blue-900/50 text-blue-800 dark:text-blue-300">
                                    {{ __('Admin') }}
                                </span>
                            @elseif ($user->divisionInventoryManager)
                                <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 dark:bg-green-900/50 text-green-800 dark:text-green-300">
                                    {{ __('Division Inventory Manager') }}
                                </span>
                            @else
                                <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-stone-100 dark:bg-stone-700 text-stone-800 dark:text-stone-300">
                                    {{ __('Regular User') }}
                                </span>
                            @endif
                        </dd>
                    </div>
                    <div class="bg-white dark:bg-stone-900 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-stone-500 dark:text-stone-400">{{ __('Status') }}</dt>
                        <dd class="mt-1 text-sm text-stone-900 dark:text-stone-100 sm:mt-0 sm:col-span-2">
                            @if ($user->adminUser)
                                <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full {{ $user->adminUser->is_active ? 'bg-green-100 text-green-800 dark:bg-green-900/50 dark:text-green-300' : 'bg-red-100 text-red-800 dark:bg-red-900/50 dark:text-red-300' }}">
                                    {{ $user->adminUser->is_active ? __('Active') : __('Inactive') }}
                                </span>
                            @else
                                <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 dark:bg-green-900/50 text-green-800 dark:text-green-300">
                                    {{ __('Active') }}
                                </span>
                            @endif
                        </dd>
                    </div>
                    @if ($user->adminUser)
                    <div class="bg-stone-50 dark:bg-stone-800/50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-stone-500 dark:text-stone-400">{{ __('Last Login') }}</dt>
                        <dd class="mt-1 text-sm text-stone-900 dark:text-stone-100 sm:mt-0 sm:col-span-2">
                            {{ $user->adminUser->last_login_at ? $user->adminUser->last_login_at->format('F j, Y g:i A') : __('Never') }}
                        </dd>
                    </div>
                    <div class="bg-white dark:bg-stone-900 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-stone-500 dark:text-stone-400">{{ __('Permissions') }}</dt>
                        <dd class="mt-1 text-sm text-stone-900 dark:text-stone-100 sm:mt-0 sm:col-span-2">
                            <p>{{ __('Full Access (All Permissions)') }}</p>
                        </dd>
                    </div>
                    @endif

                    <div class="{{ $user->adminUser ? 'bg-stone-50 dark:bg-stone-800/50' : 'bg-white dark:bg-stone-900' }} px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-stone-500 dark:text-stone-400">{{ __('Created At') }}</dt>
                        <dd class="mt-1 text-sm text-stone-900 dark:text-stone-100 sm:mt-0 sm:col-span-2">
                            {{ $user->created_at->format('F j, Y g:i A') }}
                        </dd>
                    </div>
                </dl>
            </div>
        </div>

        <!-- Recent Activity Section -->
        <div class="mt-8">
            <h2 class="text-lg font-medium text-stone-900 dark:text-stone-100 mb-4">{{ __('Recent Activity') }}</h2>
            <div class="bg-white dark:bg-stone-900 border-stone-200 dark:border-stone-700 shadow overflow-hidden sm:rounded-lg">
                @if($this->auditLogs->count() > 0)
                    <table class="min-w-full divide-y divide-stone-200 dark:divide-stone-700">
                        <thead class="bg-stone-50 dark:bg-stone-800">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-stone-500 dark:text-stone-400 uppercase tracking-wider">
                                    {{ __('Action') }}
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-stone-500 dark:text-stone-400 uppercase tracking-wider">
                                    {{ __('Model') }}
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-stone-500 dark:text-stone-400 uppercase tracking-wider">
                                    {{ __('Date') }}
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-stone-900 divide-y divide-stone-200 dark:divide-stone-700">
                            @foreach($this->auditLogs as $log)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-stone-900 dark:text-stone-100">
                                        {{ ucfirst($log->action) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-stone-500 dark:text-stone-400">
                                        {{ $this->getFriendlyModelName($log->auditable_type) }} #{{ $log->auditable_id }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-stone-500 dark:text-stone-400">
                                        {{ $log->created_at->format('M j, Y g:i A') }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <div class="px-4 py-3 bg-white dark:bg-stone-900 border-t border-stone-200 dark:border-stone-700 sm:px-6">
                        {{ $this->auditLogs->links() }}
                    </div>
                @else
                    <div class="px-6 py-4 text-sm text-stone-500 dark:text-stone-400">
                        {{ __('No recent activity found.') }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</div> 
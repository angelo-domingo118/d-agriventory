@props([
    'permissions' => [], // Current permissions
    'disabled' => false,
    'groupedPermissions' => [],
    'availableRoles' => []
])

<div class="mt-6 space-y-8">
    <div>
        <button 
            type="button"
            x-data
            x-on:click="$dispatch('permissions-reset', { role: $refs.roleSelect.value })"
            class="text-sm flex items-center gap-x-1.5 mb-4 px-4 py-2 bg-gray-200 rounded-md"
        >
            <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" />
            </svg>
            {{ __('Reset to role defaults') }}
        </button>

        <div class="relative z-10 mb-3">
            <select 
                x-ref="roleSelect"
                class="block w-full rounded-md border-0 py-1.5 pr-10 text-gray-900 ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-emerald-600 sm:text-sm sm:leading-6"
            >
                @foreach($availableRoles as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>
    </div>

    @foreach($groupedPermissions as $category => $categoryPermissions)
        <div class="border border-gray-200 rounded-lg overflow-hidden">
            <div class="bg-gray-50 px-4 py-3 border-b border-gray-200">
                <h3 class="text-sm font-medium text-gray-900 capitalize">{{ str_replace('_', ' ', $category) }} Permissions</h3>
            </div>
            <div class="divide-y divide-gray-200">
                @foreach($categoryPermissions as $permission)
                    <div class="px-4 py-3 flex items-start">
                        <div class="flex h-6 items-center">
                            <input 
                                id="{{ $permission['name'] }}" 
                                name="permissions[{{ $permission['name'] }}]" 
                                type="checkbox" 
                                class="h-4 w-4 rounded border-gray-300 text-emerald-600 focus:ring-emerald-600"
                                @if(isset($permissions[$permission['name']]) && $permissions[$permission['name']]) checked @endif
                            >
                        </div>
                        <div class="ml-3 text-sm leading-6">
                            <label for="{{ $permission['name'] }}" class="font-medium text-gray-900">{{ $permission['label'] }}</label>
                            <p class="text-gray-500 text-xs">{{ __("Allows $permission[action] access to {$category} resources") }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endforeach
</div>

<script>
document.addEventListener('permissions-reset', event => {
    const role = event.detail.role;
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    
    // Show loading state
    const button = event.target;
    const originalText = button.innerHTML;
    button.innerHTML = 'Loading...';
    button.disabled = true;
    
    // Make an AJAX request to get default permissions for the role
    fetch(`/admin/permissions/defaults/${role}`, {
        method: 'GET',
        headers: {
            'Accept': 'application/json',
            'X-CSRF-TOKEN': csrfToken
        }
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.json();
    })
    .then(data => {
        // Update all checkboxes based on the response
        Object.entries(data.permissions).forEach(([permission, value]) => {
            const checkbox = document.getElementById(permission);
            if (checkbox) {
                checkbox.checked = value;
            }
        });
        
        // Reset button state
        button.innerHTML = originalText;
        button.disabled = false;
    })
    .catch(error => {
        console.error('Error fetching permissions:', error);
        alert('There was an error resetting permissions. Please try again.');
        
        // Reset button state
        button.innerHTML = originalText;
        button.disabled = false;
    });
});
</script> 
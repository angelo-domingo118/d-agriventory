@props([
    'title',
    'value',
    'icon'
])

<div class="bg-white overflow-hidden shadow-sm rounded-lg">
    <div class="p-6">
        <div class="flex items-center">
            <div class="flex-shrink-0 bg-green-100 rounded-md p-3">
                <div class="h-6 w-6 text-green-600">
                    {{ $icon }}
                </div>
            </div>
            <div class="ml-5 w-0 flex-1">
                <dl>
                    <dt class="text-sm font-medium text-gray-500 truncate">{{ $title }}</dt>
                    <dd>
                        <div class="text-lg font-medium text-gray-900">{{ $value }}</div>
                    </dd>
                </dl>
            </div>
        </div>
    </div>
</div> 
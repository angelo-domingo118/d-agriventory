@props([
    'headers' => [],
    'data' => [],
    'ariaLabel' => 'Data table',
    'caption' => null,
    'noDataMessage' => 'No data available'
])

<div class="overflow-x-auto">
    <table {{ $attributes->merge(['class' => 'min-w-full divide-y divide-stone-200 dark:divide-stone-700']) }} aria-label="{{ $ariaLabel }}">
        @if ($caption)
            <caption class="sr-only">{{ $caption }}</caption>
        @endif
        <thead>
            <tr>
                @foreach ($headers as $header)
                    <th scope="col" class="px-6 py-3 bg-stone-100 dark:bg-stone-700 text-left text-xs font-medium text-stone-500 dark:text-stone-300 uppercase tracking-wider">
                        {{ $header }}
                    </th>
                @endforeach
            </tr>
        </thead>
        <tbody class="bg-white dark:bg-stone-800 divide-y divide-stone-200 dark:divide-stone-700">
            @if (count($data) > 0)
                {{ $slot }}
            @else
                <tr>
                    <td colspan="{{ count($headers) }}" class="px-6 py-4 text-center text-sm text-stone-500 dark:text-stone-400">
                        {{ $noDataMessage }}
                    </td>
                </tr>
            @endif
        </tbody>
    </table>
</div> 
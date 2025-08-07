@props([
    'name' => 'delete-confirmation',
    'title' => 'Confirm Deletion',
    'itemType' => 'item',
    'itemName' => '',
    'deleteAction' => '',
    'cancelAction' => '',
    'message' => null
])

<x-admin.modal-form-wrapper :name="$name" maxWidth="md">
    <div class="space-y-6">
        <div class="flex items-center space-x-3">
            <div class="flex h-12 w-12 items-center justify-center rounded-full bg-red-100 dark:bg-red-900/20">
                <x-flux::icon.exclamation-triangle class="h-6 w-6 text-red-600 dark:text-red-400" />
            </div>
            <div>
                <flux:heading size="lg">{{ $title }}</flux:heading>
                <flux:text class="mt-1 text-stone-600 dark:text-stone-400">
                    {{ $message ?: "This action cannot be undone." }}
                </flux:text>
            </div>
        </div>

        <div class="rounded-lg bg-stone-50 p-4 dark:bg-stone-800/50">
            <p class="text-sm text-stone-700 dark:text-stone-300">
                Are you sure you want to delete 
                @if($itemName)
                    <strong class="font-semibold">{{ $itemName }}</strong>
                @else
                    this {{ $itemType }}
                @endif
                ? This action cannot be undone.
            </p>
        </div>

        <div class="flex gap-2 pt-4 border-t border-stone-200 dark:border-stone-700">
            <flux:spacer />
            @if($cancelAction)
                <flux:button 
                    type="button" 
                    variant="ghost" 
                    wire:click="{{ $cancelAction }}"
                >
                    Cancel
                </flux:button>
            @else
                <flux:modal.close>
                    <flux:button type="button" variant="ghost">
                        Cancel
                    </flux:button>
                </flux:modal.close>
            @endif
            <flux:button 
                type="button" 
                variant="danger" 
                wire:click="{{ $deleteAction }}" 
                wire:loading.attr="disabled"
            >
                <span wire:loading.remove wire:target="{{ $deleteAction }}">
                    Delete {{ ucfirst($itemType) }}
                </span>
                <span wire:loading wire:target="{{ $deleteAction }}">
                    Deleting...
                </span>
            </flux:button>
        </div>
    </div>
</x-admin.modal-form-wrapper>

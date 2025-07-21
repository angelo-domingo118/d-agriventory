@props([
    'position' => 'bottom-right',
    'duration' => 5000,
])

<div
    x-data="{
        toasts: [],
        position: '{{ $position }}',
        duration: {{ $duration }},
        add(toast) {
            this.toasts.push(toast);
            this.fire(toast.id);
        },
        fire(id) {
            setTimeout(() => {
                this.remove(id);
            }, this.duration);
        },
        remove(id) {
            const i = this.toasts.findIndex(toast => toast.id === id);
            if (i > -1) {
                this.toasts.splice(i, 1);
            }
        },
    }"
    @notify.window="add($event.detail)"
    x-cloak
    :class="{
        'fixed z-50 p-4 space-y-4': true,
        'top-0 right-0': position === 'top-right',
        'top-0 left-0': position === 'top-left',
        'bottom-0 right-0': position === 'bottom-right',
        'bottom-0 left-0': position === 'bottom-left',
    }"
>
    <template x-for="toast in toasts" :key="toast.id">
        <div
            x-show="true"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="transform opacity-0 translate-y-2"
            x-transition:enter-end="transform opacity-100"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="transform opacity-100"
            x-transition:leave-end="transform opacity-0 -translate-y-2"
            class="w-80 rounded-lg shadow-lg"
            :class="{
                'bg-green-500': toast.variant === 'success',
                'bg-yellow-500': toast.variant === 'warning',
                'bg-red-500': toast.variant === 'danger',
                'bg-stone-800': !toast.variant,
            }"
        >
            <div class="p-4">
                <div class="flex items-start">
                    <div class="flex-shrink-0">
                        <!-- Heroicon name: check-circle / x-circle / exclamation -->
                        <svg x-show="toast.variant === 'success'" class="h-6 w-6 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <svg x-show="toast.variant === 'warning'" class="h-6 w-6 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                        <svg x-show="toast.variant === 'danger'" class="h-6 w-6 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div class="ml-3 w-0 flex-1 pt-0.5">
                        <p class="text-sm font-medium text-white" x-text="toast.heading"></p>
                        <p class="mt-1 text-sm text-stone-200" x-text="toast.text"></p>
                    </div>
                    <div class="ml-4 flex-shrink-0 flex">
                        <button @click="remove(toast.id)" class="inline-flex text-white">
                            <span class="sr-only">Close</span>
                            <!-- Heroicon name: x -->
                            <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </template>
</div> 
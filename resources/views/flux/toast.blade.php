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
            // Ensure the toast has a unique id
            if (!toast.id) {
                toast.id = Date.now().toString();
            }
            // Set duration from toast or use default
            toast.duration = toast.duration || this.duration;
            this.toasts.push(toast);
            if (toast.duration > 0) {
                this.fire(toast.id, toast.duration);
            }
        },
        fire(id, duration) {
            setTimeout(() => {
                this.remove(id);
            }, duration);
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
        'fixed z-50 p-4 space-y-3': true,
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
            x-transition:enter-start="transform opacity-0 translate-y-2 scale-95"
            x-transition:enter-end="transform opacity-100 translate-y-0 scale-100"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="transform opacity-100 translate-y-0 scale-100"
            x-transition:leave-end="transform opacity-0 -translate-y-2 scale-95"
            class="w-80 rounded-xl shadow-lg border backdrop-blur-sm"
            :class="{
                // Success variant
                'bg-green-50 border-green-200 dark:bg-green-950/90 dark:border-green-800': toast.variant === 'success',
                // Warning variant
                'bg-amber-50 border-amber-200 dark:bg-amber-950/90 dark:border-amber-800': toast.variant === 'warning',
                // Danger variant
                'bg-red-50 border-red-200 dark:bg-red-950/90 dark:border-red-800': toast.variant === 'danger',
                // Info variant (fixing the transparency issue)
                'bg-blue-50 border-blue-200 dark:bg-blue-950/90 dark:border-blue-800': toast.variant === 'info',
                // Default variant
                'bg-white border-stone-200 dark:bg-stone-800/90 dark:border-stone-700': !toast.variant || toast.variant === 'default',
            }"
        >
            <div class="p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <!-- Success icon -->
                        <svg x-show="toast.variant === 'success'" class="h-5 w-5 text-green-600 dark:text-green-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.236 4.53L7.73 10.063a.75.75 0 00-1.06 1.06l2.25 2.25a.75.75 0 001.14-.094l3.75-5.25z" clip-rule="evenodd" />
                        </svg>
                        <!-- Warning icon -->
                        <svg x-show="toast.variant === 'warning'" class="h-5 w-5 text-amber-600 dark:text-amber-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" />
                        </svg>
                        <!-- Danger icon -->
                        <svg x-show="toast.variant === 'danger'" class="h-5 w-5 text-red-600 dark:text-red-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z" clip-rule="evenodd" />
                        </svg>
                        <!-- Info icon -->
                        <svg x-show="toast.variant === 'info'" class="h-5 w-5 text-blue-600 dark:text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a.75.75 0 000 1.5h.253a.25.25 0 01.244.304l-.459 2.066A1.75 1.75 0 0010.747 15H11a.75.75 0 000-1.5h-.253a.25.25 0 01-.244-.304l.459-2.066A1.75 1.75 0 009.253 9H9z" clip-rule="evenodd" />
                        </svg>
                        <!-- Default icon -->
                        <svg x-show="!toast.variant || toast.variant === 'default'" class="h-5 w-5 text-stone-600 dark:text-stone-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a.75.75 0 000 1.5h.253a.25.25 0 01.244.304l-.459 2.066A1.75 1.75 0 0010.747 15H11a.75.75 0 000-1.5h-.253a.25.25 0 01-.244-.304l.459-2.066A1.75 1.75 0 009.253 9H9z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3 w-0 flex-1">
                        <p 
                            x-show="toast.heading" 
                            class="text-sm font-semibold leading-5"
                            :class="{
                                'text-green-900 dark:text-green-100': toast.variant === 'success',
                                'text-amber-900 dark:text-amber-100': toast.variant === 'warning',
                                'text-red-900 dark:text-red-100': toast.variant === 'danger',
                                'text-blue-900 dark:text-blue-100': toast.variant === 'info',
                                'text-stone-900 dark:text-stone-100': !toast.variant || toast.variant === 'default',
                            }"
                            x-text="toast.heading"
                        ></p>
                        <p 
                            class="text-sm leading-5"
                            :class="{
                                'text-green-700 dark:text-green-200': toast.variant === 'success',
                                'text-amber-700 dark:text-amber-200': toast.variant === 'warning',
                                'text-red-700 dark:text-red-200': toast.variant === 'danger',
                                'text-blue-700 dark:text-blue-200': toast.variant === 'info',
                                'text-stone-700 dark:text-stone-300': !toast.variant || toast.variant === 'default',
                                'mt-1': toast.heading
                            }"
                            x-text="toast.text"
                        ></p>
                    </div>
                    <div class="ml-4 flex-shrink-0 flex">
                        <button 
                            @click="remove(toast.id)" 
                            class="inline-flex rounded-md p-1.5 transition-colors duration-150 focus:outline-none focus:ring-2 focus:ring-offset-2"
                            :class="{
                                'text-green-400 hover:text-green-500 focus:ring-green-600': toast.variant === 'success',
                                'text-amber-400 hover:text-amber-500 focus:ring-amber-600': toast.variant === 'warning',
                                'text-red-400 hover:text-red-500 focus:ring-red-600': toast.variant === 'danger',
                                'text-blue-400 hover:text-blue-500 focus:ring-blue-600': toast.variant === 'info',
                                'text-stone-400 hover:text-stone-500 focus:ring-stone-600': !toast.variant || toast.variant === 'default',
                            }"
                        >
                            <span class="sr-only">Close</span>
                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </template>
</div>
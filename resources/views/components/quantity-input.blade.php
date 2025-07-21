@props([
    'disabled' => false,
    'label' => '',
    'min' => 0,
    'step' => 1,
    'showLabel' => true,
    'required' => false,
])

<div x-data="{
    value: @entangle($attributes->wire('model')),
    min: {{ $min }},
    step: {{ $step }},
    isFloat: {{ str_contains((string) $step, '.') ? 'true' : 'false' }},
    increment() {
        let numValue = this.isFloat ? parseFloat(this.value) : parseInt(this.value);
        if (isNaN(numValue)) numValue = 0;
        this.value = (numValue + this.step).toFixed(this.isFloat ? 2 : 0);
    },
    decrement() {
        let numValue = this.isFloat ? parseFloat(this.value) : parseInt(this.value);
        if (isNaN(numValue)) numValue = 0;
        if (numValue - this.step >= this.min) {
            this.value = (numValue - this.step).toFixed(this.isFloat ? 2 : 0);
        }
    },
    onInput(event) {
        let val = event.target.value;
        if (val === '') {
            this.value = '';
            return;
        }
        let numVal = this.isFloat ? parseFloat(val) : parseInt(val);
        if (!isNaN(numVal)) {
            if (numVal < this.min) {
                this.value = this.min;
            } else {
                this.value = numVal;
            }
        }
    }
}" {{ $attributes->whereDoesntStartWith('wire:model') }}>
    @if ($showLabel && $label)
        <label class="mb-1 block text-sm font-medium text-stone-700 dark:text-stone-300">
            {{ $label }}
            @if ($required)
                <span class="text-red-500">*</span>
            @endif
        </label>
    @endif
    <div class="relative flex max-w-[10rem] items-center">
        <button type="button" x-on:click="decrement" {{ $disabled ? 'disabled' : '' }}
            class="h-11 rounded-s-lg border border-stone-300 bg-stone-100 p-3 hover:bg-stone-200 focus:outline-none focus:ring-2 focus:ring-stone-400 dark:border-stone-600 dark:bg-stone-700 dark:hover:bg-stone-600 dark:focus:ring-stone-500">
            <svg class="h-3 w-3 text-stone-900 dark:text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg"
                fill="none" viewBox="0 0 18 2">
                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M1 1h16" />
            </svg>
        </button>
        <input type="text" x-model.lazy="value" @change="onInput($event)" {{ $disabled ? 'disabled' : '' }}
            class="z-10 block h-11 w-full border-x-0 border-stone-300 bg-stone-50 text-center text-sm text-stone-900 focus:border-blue-500 focus:ring-blue-500 dark:border-stone-600 dark:bg-stone-700 dark:text-white dark:placeholder-stone-400 dark:focus:border-blue-500 dark:focus:ring-blue-500"
            placeholder="{{ $min }}" {{ $required ? 'required' : '' }}>
        <button type="button" x-on:click="increment" {{ $disabled ? 'disabled' : '' }}
            class="h-11 rounded-e-lg border border-stone-300 bg-stone-100 p-3 hover:bg-stone-200 focus:outline-none focus:ring-2 focus:ring-stone-400 dark:border-stone-600 dark:bg-stone-700 dark:hover:bg-stone-600 dark:focus:ring-stone-500">
            <svg class="h-3 w-3 text-stone-900 dark:text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg"
                fill="none" viewBox="0 0 18 18">
                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M9 1v16M1 9h16" />
            </svg>
        </button>
    </div>
</div> 
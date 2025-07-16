@props([
    'id' => null,
    'wire:model' => null,
    'label' => '',
    'placeholder' => 'Type to search...',
    'required' => false,
    'disabled' => false,
    'suggestions' => [],
    'showSuggestions' => false,
    'onFocus' => null,
    'onSelect' => null,
    'createNew' => false,
    'error' => null
])

<div class="relative" 
    x-data="{
        suggestions: @entangle($attributes->wire('suggestions')),
        showSuggestions: @entangle($attributes->wire('showSuggestions')),
        createNew: @js($createNew),
        inputRef: null,
        selectedIndex: -1,
        init() {
            this.inputRef = this.$refs.input;
            this.positionDropdown();
            window.addEventListener('resize', () => this.positionDropdown());
            window.addEventListener('scroll', () => this.positionDropdown());
        },
        positionDropdown() {
            requestAnimationFrame(() => {
                if (this.inputRef && this.$refs.dropdown) {
                    const rect = this.inputRef.getBoundingClientRect();
                    const dropdown = this.$refs.dropdown;
                    const dropdownHeight = dropdown.offsetHeight;
                    const spaceBelow = window.innerHeight - rect.bottom;

                    if (spaceBelow < dropdownHeight && rect.top > dropdownHeight) {
                        // Not enough space below, but enough above: show above
                        dropdown.style.top = (rect.top - dropdownHeight - 4) + 'px';
                        dropdown.style.bottom = 'auto';
                    } else {
                        // Default to below
                        dropdown.style.top = (rect.bottom + 4) + 'px';
                        dropdown.style.bottom = 'auto';
                    }

                    dropdown.style.left = rect.left + 'px';
                    dropdown.style.width = rect.width + 'px';
                }
            });
        },
        handleKeydown(event) {
            if (!this.showSuggestions || this.suggestions.length === 0) {
                if (event.key === 'Tab' && this.createNew && this.inputRef.value.trim() !== '') {
                    const existingSuggestion = this.suggestions.find(s => s.name && s.name.toLowerCase() === this.inputRef.value.trim().toLowerCase());
                    if (!existingSuggestion) {
                        this.selectSuggestion({ id: 'new', name: this.inputRef.value.trim(), type: 'new' });
                    }
                }
                return;
            }
            
            switch(event.key) {
                case 'ArrowDown':
                    event.preventDefault();
                    this.selectedIndex = Math.min(this.selectedIndex + 1, this.suggestions.length - 1);
                    this.scrollToSelected();
                    break;
                case 'ArrowUp':
                    event.preventDefault();
                    this.selectedIndex = Math.max(this.selectedIndex - 1, -1);
                    this.scrollToSelected();
                    break;
                case 'Enter':
                    if (this.selectedIndex >= 0) {
                        event.preventDefault();
                        this.selectSuggestion(this.suggestions[this.selectedIndex]);
                    } else if (this.createNew && this.inputRef.value.trim() !== '') {
                        const existingSuggestion = this.suggestions.find(s => s.name && s.name.toLowerCase() === this.inputRef.value.trim().toLowerCase());
                        if (!existingSuggestion) {
                            event.preventDefault();
                            this.selectSuggestion({ id: 'new', name: this.inputRef.value.trim(), type: 'new' });
                        }
                    }
                    break;
                case 'Tab':
                    if (this.selectedIndex >= 0) {
                        event.preventDefault();
                        this.selectSuggestion(this.suggestions[this.selectedIndex]);
                    } else if (this.createNew && this.inputRef.value.trim() !== '') {
                        const existingSuggestion = this.suggestions.find(s => s.name && s.name.toLowerCase() === this.inputRef.value.trim().toLowerCase());
                        if (!existingSuggestion) {
                            // Don't prevent default, allow tabbing
                            this.selectSuggestion({ id: 'new', name: this.inputRef.value.trim(), type: 'new' });
                        }
                    }
                    break;
                case 'Escape':
                    this.showSuggestions = false;
                    this.selectedIndex = -1;
                    break;
            }
        },
        handleBlur() {
            // Use a timeout to allow click events on suggestions to register before closing
            setTimeout(() => {
                if (this.showSuggestions) { // If a suggestion was clicked, this will likely be false already
                    if (this.createNew && this.inputRef.value.trim() !== '') {
                        const existingSuggestion = this.suggestions.find(s => s.name && s.name.toLowerCase() === this.inputRef.value.trim().toLowerCase());
                        const hasNewOption = this.suggestions.some(s => s.type === 'new');

                        if (!existingSuggestion && hasNewOption) {
                           this.selectSuggestion({ id: 'new', name: this.inputRef.value.trim(), type: 'new' });
                        }
                    }
                    this.showSuggestions = false;
                }
            }, 200);
        },
        scrollToSelected() {
            if (this.selectedIndex >= 0) {
                const dropdown = this.$refs.dropdown;
                const selected = dropdown.children[this.selectedIndex];
                if (selected) {
                    selected.scrollIntoView({ block: 'nearest' });
                }
            }
        },
        selectSuggestion(suggestion) {
            @if($onSelect)
                {{ $onSelect }}(suggestion);
            @else
                $wire.selectSuggestion(suggestion);
            @endif
            this.selectedIndex = -1;
        }
    }" 
    x-effect="if (showSuggestions) { $nextTick(() => positionDropdown()); selectedIndex = -1; }" 
    x-init="init()"
>
    <flux:input
        id="{{ $id }}"
        {{ $attributes->except(['wire:suggestions', 'wire:showSuggestions', 'onFocus', 'onSelect']) }}
        :label="$label"
        :placeholder="$placeholder"
        :required="$required"
        :disabled="$disabled"
        autocomplete="off"
        x-ref="input"
        x-on:focus="{{ $onFocus }}; $nextTick(() => positionDropdown())"
        x-on:click="{{ $onFocus }}; $nextTick(() => positionDropdown())"
        x-on:blur="handleBlur()"
        x-on:keydown="handleKeydown($event)"
    />
    
    @if($error)
        <x-input-error for="{{ $error }}" class="mt-2" />
    @endif
    
    <!-- Suggestions Dropdown -->
    <div x-show="showSuggestions && suggestions.length > 0" 
         x-ref="dropdown"
         x-transition
         class="fixed z-[9999] bg-white dark:bg-stone-800 border border-stone-300 dark:border-stone-600 rounded-lg shadow-xl max-h-60 overflow-auto"
         style="position: fixed !important;"
    >
        <template x-for="(suggestion, index) in suggestions" :key="suggestion.id + '-' + index">
            <div x-on:click="selectSuggestion(suggestion)"
                 x-on:mouseenter="selectedIndex = index"
                 class="px-4 py-3 hover:bg-stone-50 dark:hover:bg-stone-700 cursor-pointer border-b border-stone-100 dark:border-stone-700 last:border-b-0 transition-colors duration-150"
                 :class="{
                     'bg-stone-100 dark:bg-stone-600': selectedIndex === index,
                 }">
                <div class="flex items-center justify-between">
                    <div class="flex-1 min-w-0">
                        <div class="font-medium text-stone-900 dark:text-stone-100 truncate" x-text="suggestion.name || suggestion.label"></div>
                        <div x-show="suggestion.description" class="mt-1 text-sm text-stone-500 dark:text-stone-400" x-text="suggestion.description"></div>
                    </div>
                    <div class="ml-3 flex-shrink-0">
                        <div x-show="suggestion.type === 'new'" class="inline-flex items-center rounded-md bg-blue-50 px-2 py-1 text-xs font-medium text-blue-700 ring-1 ring-inset ring-blue-700/10 dark:bg-blue-900/10 dark:text-blue-400 dark:ring-blue-600">
                            + Create New
                        </div>
                    </div>
                </div>
            </div>
        </template>
    </div>
</div>
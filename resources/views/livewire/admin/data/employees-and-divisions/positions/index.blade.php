<?php

use App\Models\Position;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new #[Layout('components.layouts.app')] class extends Component {
    use WithPagination;

    public ?Position $editing = null;
    public bool $showCreateModal = false;

    public function mount()
    {
        if (!auth()->user()->hasAdminPermission('manage_data')) {
            abort(403, 'You do not have permission to manage this data.');
        }
    }

    public function getPositionsProperty()
    {
        return Position::orderBy('title')->paginate(10);
    }

    public function newPosition(): void
    {
        $this->editing = new Position();
        $this->showCreateModal = true;
    }

    public function edit(Position $position): void
    {
        $this->editing = $position;
        $this->showCreateModal = true;
    }

    public function save(): void
    {
        $validated = $this->validate([
            'editing.title' => ['required', 'string', 'max:255', Rule::unique('positions', 'title')->ignore($this->editing->id)],
        ]);

        $this->editing->save();

        $this->showCreateModal = false;
        $this->dispatch('position-saved');
        session()->flash('success', 'Position saved successfully.');
    }

    public function with(): array
    {
        return [
            'positions' => $this->positions,
        ];
    }
}; ?>

<div>
    <div class="flex items-center justify-between">
        <h2 class="text-lg font-semibold text-stone-700 dark:text-stone-200">Positions</h2>
        <flux:button wire:click="newPosition" variant="primary">New Position</flux:button>
    </div>

    <div class="mt-4 overflow-hidden rounded-lg border border-stone-200 bg-white shadow-sm dark:border-stone-700 dark:bg-stone-800">
        <table class="min-w-full divide-y divide-stone-200 dark:divide-stone-700">
            <thead class="bg-stone-50 dark:bg-stone-800">
                <tr>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-stone-500 dark:text-stone-400">Title</th>
                    <th scope="col" class="relative px-6 py-3">
                        <span class="sr-only">Edit</span>
                    </th>
                </tr>
            </thead>
            <tbody class="divide-y divide-stone-200 bg-white dark:divide-stone-800 dark:bg-stone-900">
                @forelse($positions as $position)
                    <tr wire:key="position-{{ $position->id }}">
                        <td class="whitespace-nowrap px-6 py-4 text-sm font-medium text-stone-900 dark:text-stone-100">{{ $position->title }}</td>
                        <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-medium">
                            <flux:button wire:click="edit({{ $position->id }})" variant="ghost" class="text-primary-600 hover:text-primary-900 dark:text-primary-400 dark:hover:text-primary-200">Edit</flux:button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="2" class="px-6 py-12 text-center text-sm text-stone-500 dark:text-stone-400">
                            No positions found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $positions->links() }}
    </div>

    <!-- Create/Edit Modal -->
    @if($editing)
        <flux:modal :show="$showCreateModal" max-width="lg" @close="$set('showCreateModal', false)">
            <form wire:submit.prevent="save">
                <x-slot:title>
                    {{ $editing->exists ? 'Edit' : 'Create' }} Position
                </x-slot:title>

                <div class="space-y-4 p-6">
                    <flux:input wire:model="editing.title" label="Title" required />
                </div>

                <x-slot:footer>
                    <div class="flex justify-end gap-x-4">
                        <flux:button variant="ghost" @click="$set('showCreateModal', false)">Cancel</flux:button>
                        <flux:button type="submit" variant="primary">Save</flux:button>
                    </div>
                </x-slot:footer>
            </form>
        </flux:modal>
    @endif
</div> 
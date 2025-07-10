<?php

use App\Models\Division;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Volt\Component;

new class extends Component {
    public string $search = '';

    public function mount(): void
    {
        if (!auth()->user()->hasAdminPermission('manage_data')) {
            abort(403, 'You do not have permission to manage this data.');
        }
    }

    #[Computed]
    public function isSearching(): bool
    {
        return filled($this->search);
    }

    #[Computed]
    public function divisions(): Collection
    {
        $search = $this->search;

        $divisions = Division::query()
            ->with([
                'employees' => function ($query) use ($search) {
                    $query->with('position')->orderBy('name');
                    if (filled($search)) {
                        $query->where('name', 'like', '%' . $search . '%');
                    }
                },
            ])
            ->when(filled($search), function ($query) use ($search) {
                $query->where('name', 'like', '%' . $search . '%')
                    ->orWhereHas('employees', function ($q) use ($search) {
                        $q->where('name', 'like', '%' . $search . '%');
                    });
            })
            ->orderBy('name')
            ->get();

        // Manually set the employees_count based on the filtered employees collection
        // And filter out divisions that have no matching employees and their name doesn't match
        return $divisions
            ->map(function ($division) {
                $division->employees_count = $division->employees->count();
                return $division;
            })
            ->filter(function ($division) use ($search) {
                if (filled($search)) {
                    return str_contains(strtolower($division->name), strtolower($search)) ||
                        $division->employees->isNotEmpty();
                }

                return true;
            });
    }

    #[Computed]
    public function expandableIds(): array
    {
        return $this->divisions()
            ->where('employees_count', '>', 0)
            ->pluck('id')
            ->map(fn($id) => 'division-' . $id)
            ->all();
    }
}; ?>

<x-tree.index 
    :items="$this->divisions" 
    :expandable-ids="$this->expandableIds" 
    :is-searching="$this->isSearching"
    empty-message="No Divisions Found"
    create-url="{{ route('admin.data.employees-and-divisions.divisions.create') }}" 
    create-text="Create Division">
    @foreach ($this->divisions as $division)
        <x-tree.item 
            :id="'division-'.$division->id" 
            :title="$division->name" 
            :subtitle="$division->employees_count . ' employees'" 
            :edit-url="route('admin.data.employees-and-divisions.divisions.edit', $division)"
            :add-url="route('admin.data.employees-and-divisions.employees.create', ['division_id' => $division->id])"
            add-text="Add Employee"
            :has-children="$division->employees_count > 0"
            :search-terms="[$this->search]">
            @forelse($division->employees as $employee)
                <x-tree.item 
                    :id="'employee-'.$employee->id" 
                    :title="$employee->name" 
                    :subtitle="$employee->position->title ?? 'No position'"
                    :edit-url="route('admin.data.employees-and-divisions.employees.edit', $employee)" 
                    :level="1" 
                    :has-children="false" 
                    icon="user"
                    :search-terms="[$this->search]" />
            @empty
                <p class="py-4 text-sm italic text-stone-500 dark:text-stone-400">No employees found in this division.
                </p>
            @endforelse
        </x-tree.item>
    @endforeach
</x-tree.index> 
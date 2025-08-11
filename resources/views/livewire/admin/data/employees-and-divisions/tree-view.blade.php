<?php

use App\Models\Division;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Volt\Component;

new class extends Component {
    public string $search = '';

    public function mount(): void
    {
        if (!auth()->user()->hasAdminPermission('manage_data')) {
            abort(403, 'You do not have permission to manage this data.');
        }
    }

    #[On('division-created')]
    #[On('employee-created')]
    public function refreshData(): void
    {
        // Force refresh of computed properties
        unset($this->divisions);
        unset($this->expandableIds);
        $this->dispatch('$refresh');
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
                    $query->orderBy('name');
                    if (filled($search)) {
                        $query->where('name', 'like', '%' . $search . '%');
                    }
                },
            ])
            ->when(filled($search), function ($query) use ($search) {
                $query->where('name', 'like', '%' . $search . '%')
                    ->orWhereHas('employees', function ($q) use ($search) {
                        $q->where('name', 'like', '%' . $search . '%')
                            ->orWhere('position_title', 'like', '%' . $search . '%')
                            ->orWhere('position_code', 'like', '%' . $search . '%');
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

<div>
    <x-tree.index 
        :items="$this->divisions" 
        :expandable-ids="$this->expandableIds" 
        :is-searching="$this->isSearching"
        empty-message="No Divisions Found"
        create-modal-name="create-division"
        create-text="Create Division"
    >
    @foreach ($this->divisions as $division)
        <x-tree.item 
            :id="'division-'.$division->id" 
            :title="$division->name" 
            :subtitle="$division->employees_count . ' employees'" 
            :edit-url="route('admin.data.employees-and-divisions.divisions.edit', $division)"
            add-modal-name="create-employee"
            add-text="Add Employee"
            :has-children="$division->employees_count > 0"
            :search-terms="[$this->search]"
        >
            @forelse($division->employees as $employee)
                <x-tree.item 
                    :id="'employee-'.$employee->id" 
                    :title="$employee->name" 
                    :subtitle="$employee->position_title ?: 'No position'"
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

    <!-- Create Division Modal -->
    <x-admin.modal-form-wrapper name="create-division" maxWidth="lg">
        <livewire:admin.data.employees-and-divisions.divisions.create />
    </x-admin.modal-form-wrapper>

    <!-- Create Employee Modal -->
    <x-admin.modal-form-wrapper name="create-employee" maxWidth="xl">
        <livewire:admin.data.employees-and-divisions.employees.create />
    </x-admin.modal-form-wrapper>
</div> 
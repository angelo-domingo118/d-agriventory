<?php

use App\Models\Division;
use App\Models\IcsNumber;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new #[Layout('components.layouts.app')] class extends Component {
    use WithPagination;

    public string $search = '';

    public ?int $divisionId = null;

    public array $headers = [
        'ICS Number',
        'Item Name',
        'Current Custodian',
        'Division',
        'Date Prepared',
        'Actions',
    ];

    public function mount(): void
    {
        if (!auth()->user()->hasAdminPermission('view_inventory')) {
            abort(403);
        }
    }

    #[Computed]
    public function icsNumbers()
    {
        $search = addcslashes($this->search, '%_');

        return IcsNumber::with([
                'assignedEmployee.division',
                'contractItem.itemSpecification.catalogItem'
            ])
            ->when($this->search, function ($query) use ($search) {
                $query->where(function($q) use ($search) {
                    $q->where('ics_number', 'like', '%' . $search . '%')
                        ->orWhereHas('contractItem.itemSpecification.catalogItem', function ($subq) use ($search) {
                            $subq->where('name', 'like', '%' . $search . '%');
                        })->orWhereHas('assignedEmployee', function ($subq) use ($search) {
                            $subq->where('name', 'like', '%' . $search . '%');
                        });
                });
            })
            ->when($this->divisionId, function ($query) {
                $query->whereHas('assignedEmployee.division', function ($q) {
                    $q->where('id', $this->divisionId);
                });
            })
            ->latest('created_at')
            ->paginate(10);
    }

    #[Computed]
    public function divisions()
    {
        return Division::all();
    }

    public function with(): array
    {
        return [
            'icsNumbers' => $this->icsNumbers,
            'divisions' => $this->divisions,
        ];
    }

    public function create(): void
    {
        $this->redirect(route('admin.inventory.ics.create'), navigate: true);
    }
}; ?>

<div>
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-semibold text-stone-900 dark:text-stone-100">
            ICS Management
        </h1>
        @can('create_inventory')
            <flux:button variant="primary" wire:click="create">
                Create ICS
            </flux:button>
        @endcan
    </div>

    <div class="mt-4 flex items-center justify-between">
        <div class="w-1/3">
            <flux:input
                wire:model.live.debounce.300ms="search"
                label="Search"
                placeholder="Search by item or employee..."
            />
        </div>
        <div class="w-1/4">
            <flux:select
                wire:model.live="divisionId"
                label="Filter by Division"
            >
                <option value="">All Divisions</option>
                @foreach ($this->divisions as $division)
                    <option value="{{ $division->id }}">{{ $division->name }}</option>
                @endforeach
            </flux:select>
        </div>
    </div>

    <div class="mt-6">
        <x-data-table
            :headers="$headers"
            :data="$this->icsNumbers"
            aria-label="ICS Records"
            no-data-message="No ICS records found."
        >
            @foreach ($this->icsNumbers as $ics)
                <tr wire:key="{{ $ics->id }}">
                    <td class="whitespace-nowrap px-6 py-4 text-sm text-stone-600 dark:text-stone-300">
                        {{ $ics->ics_number }}
                    </td>
                    <td class="whitespace-nowrap px-6 py-4 text-sm text-stone-600 dark:text-stone-300">
                        {{ $ics->contractItem->itemSpecification->catalogItem->name ?? 'N/A' }}
                    </td>
                    <td class="whitespace-nowrap px-6 py-4 text-sm text-stone-600 dark:text-stone-300">
                        {{ $ics->assignedEmployee->name ?? 'N/A' }}
                    </td>
                    <td class="whitespace-nowrap px-6 py-4 text-sm text-stone-600 dark:text-stone-300">
                        {{ $ics->assignedEmployee->division->name ?? 'N/A' }}
                    </td>
                    <td class="whitespace-nowrap px-6 py-4 text-sm text-stone-600 dark:text-stone-300">
                        {{ $ics->date_prepared?->format('F d, Y') ?? 'N/A' }}
                    </td>
                    <td class="whitespace-nowrap px-6 py-4 text-sm text-stone-600 dark:text-stone-300">
                        <a href="{{ route('admin.inventory.ics.show', $ics) }}" class="font-medium text-primary-600 hover:text-primary-500" wire:navigate>View</a>
                    </td>
                </tr>
            @endforeach
        </x-data-table>
    </div>
    <div class="mt-4">
        {{ $this->icsNumbers->links() }}
    </div>
</div> 
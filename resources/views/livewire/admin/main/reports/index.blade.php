<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Computed;

new #[Layout('components.layouts.app')] class extends Component {
    public string $reportType = 'idr';
    public string $reportFormat = 'batch';
    public float $zoom = 1.0;

    public function updatedReportType(string $value): void
    {
        $this->reportFormat = match ($value) {
            'ics' => 'by_number',
            'par' => 'by_number',
            'idr' => 'batch',
            default => '',
        };
        $this->resetZoom();
    }

    public function updatedReportFormat(): void
    {
        $this->resetZoom();
    }

    public function zoomIn(): void
    {
        if ($this->zoom < 2.0) {
            $this->zoom = round($this->zoom + 0.1, 1);
        }
    }

    public function zoomOut(): void
    {
        if ($this->zoom > 0.2) {
            $this->zoom = round($this->zoom - 0.1, 1);
        }
    }

    public function resetZoom(): void
    {
        if (
            ($this->reportType === 'ics' && $this->reportFormat === 'by_employee') ||
            ($this->reportType === 'par' && $this->reportFormat === 'by_employee')
        ) {
            $this->zoom = 0.7;
        } else {
            $this->zoom = 1.0;
        }
    }

    public function generatePreview(): void
    {
        // This is for demonstration to show loading state
        sleep(1);
    }

    #[Computed]
    public function isPreviewAvailable(): bool
    {
        $availableCombos = [
            'ics' => ['by_number', 'by_employee'],
            'par' => ['by_number', 'by_employee'],
            'idr' => ['batch'],
        ];

        return isset($availableCombos[$this->reportType]) && in_array($this->reportFormat, $availableCombos[$this->reportType]);
    }
}; ?>

<div>
    <div class="mt-8 grid grid-cols-1 gap-12 lg:grid-cols-3">
        <div class="col-span-1 space-y-8">
            {{-- Report Types --}}
            <div class="space-y-4">
                <h2 class="text-base font-semibold text-stone-900 dark:text-stone-100">Report Types</h2>
                <div class="space-y-2">
                    <div class="space-y-2">
                        <button wire:click="$set('reportType', 'ics')" type="button"
                            class="flex w-full items-start gap-x-4 rounded-lg border p-4 text-left transition {{ $reportType === 'ics' ? 'border-primary-500 bg-primary-50 ring-2 ring-primary-500 dark:bg-primary-500/20' : 'border-stone-200 bg-white hover:border-stone-300 dark:border-stone-700 dark:bg-stone-900 hover:dark:border-stone-600' }}">
                            <x-flux::icon.book-open-text class="h-6 w-6 text-stone-600 dark:text-stone-400" />
                            <div>
                                <h3 class="font-semibold text-stone-800 dark:text-stone-200">ICS Reports</h3>
                                <p class="text-sm text-stone-600 dark:text-stone-400">Inventory Custodian Slip reports.</p>
                            </div>
                        </button>

                        @if ($reportType === 'ics')
                        <div class="ml-10 space-y-2">
                            <button wire:click="$set('reportFormat', 'by_number')" type="button"
                                class="flex w-full items-start gap-x-4 rounded-lg border p-3 text-left transition {{ $reportFormat === 'by_number' ? 'border-primary-500 bg-primary-50 ring-1 ring-primary-500 dark:bg-primary-500/20' : 'border-stone-200 bg-white hover:border-stone-300 dark:border-stone-700 dark:bg-stone-900 hover:dark:border-stone-600' }}">
                                <x-flux::icon.document-magnifying-glass
                                    class="h-5 w-5 text-stone-600 dark:text-stone-400" />
                                <div>
                                    <h3 class="font-semibold text-stone-800 dark:text-stone-200">By ICS Number</h3>
                                    <p class="text-sm text-stone-600 dark:text-stone-400">Generate report for a
                                        specific ICS batch</p>
                                </div>
                            </button>
                            <button wire:click="$set('reportFormat', 'by_employee')" type="button"
                                class="flex w-full items-start gap-x-4 rounded-lg border p-3 text-left transition {{ $reportFormat === 'by_employee' ? 'border-primary-500 bg-primary-50 ring-1 ring-primary-500 dark:bg-primary-500/20' : 'border-stone-200 bg-white hover:border-stone-300 dark:border-stone-700 dark:bg-stone-900 hover:dark:border-stone-600' }}">
                                <x-flux::icon.user-circle class="h-5 w-5 text-stone-600 dark:text-stone-400" />
                                <div>
                                    <h3 class="font-semibold text-stone-800 dark:text-stone-200">By Employee</h3>
                                    <p class="text-sm text-stone-600 dark:text-stone-400">Generate a summary for an
                                        employee</p>
                                </div>
                            </button>
                        </div>
                        @endif
                    </div>

                    <div class="space-y-2">
                        <button wire:click="$set('reportType', 'par')" type="button"
                            class="flex w-full items-start gap-x-4 rounded-lg border p-4 text-left transition {{ $reportType === 'par' ? 'border-primary-500 bg-primary-50 ring-2 ring-primary-500 dark:bg-primary-500/20' : 'border-stone-200 bg-white hover:border-stone-300 dark:border-stone-700 dark:bg-stone-900 hover:dark:border-stone-600' }}">
                            <x-flux::icon.clipboard-document-list class="h-6 w-6 text-stone-600 dark:text-stone-400" />
                            <div>
                                <h3 class="font-semibold text-stone-800 dark:text-stone-200">PAR Reports</h3>
                                <p class="text-sm text-stone-600 dark:text-stone-400">Property Acknowledgment Receipt
                                    reports</p>
                            </div>
                        </button>

                        @if ($reportType === 'par')
                        <div class="ml-10 space-y-2">
                            <button wire:click="$set('reportFormat', 'by_number')" type="button"
                                class="flex w-full items-start gap-x-4 rounded-lg border p-3 text-left transition {{ $reportFormat === 'by_number' ? 'border-primary-500 bg-primary-50 ring-1 ring-primary-500 dark:bg-primary-500/20' : 'border-stone-200 bg-white hover:border-stone-300 dark:border-stone-700 dark:bg-stone-900 hover:dark:border-stone-600' }}">
                                <x-flux::icon.document-magnifying-glass
                                    class="h-5 w-5 text-stone-600 dark:text-stone-400" />
                                <div>
                                    <h3 class="font-semibold text-stone-800 dark:text-stone-200">By PAR Number</h3>
                                    <p class="text-sm text-stone-600 dark:text-stone-400">Generate report for a
                                        specific PAR batch</p>
                                </div>
                            </button>
                            <button wire:click="$set('reportFormat', 'by_employee')" type="button"
                                class="flex w-full items-start gap-x-4 rounded-lg border p-3 text-left transition {{ $reportFormat === 'by_employee' ? 'border-primary-500 bg-primary-50 ring-1 ring-primary-500 dark:bg-primary-500/20' : 'border-stone-200 bg-white hover:border-stone-300 dark:border-stone-700 dark:bg-stone-900 hover:dark:border-stone-600' }}">
                                <x-flux::icon.user-circle class="h-5 w-5 text-stone-600 dark:text-stone-400" />
                                <div>
                                    <h3 class="font-semibold text-stone-800 dark:text-stone-200">By Employee</h3>
                                    <p class="text-sm text-stone-600 dark:text-stone-400">Generate a summary for an
                                        employee</p>
                                </div>
                            </button>
                        </div>
                        @endif
                    </div>

                    <div class="space-y-2">
                        <button wire:click="$set('reportType', 'idr')" type="button"
                            class="flex w-full items-start gap-x-4 rounded-lg border p-4 text-left transition {{ $reportType === 'idr' ? 'border-primary-500 bg-primary-50 ring-2 ring-primary-500 dark:bg-primary-500/20' : 'border-stone-200 bg-white hover:border-stone-300 dark:border-stone-700 dark:bg-stone-900 hover:dark:border-stone-600' }}">
                            <x-flux::icon.users class="h-6 w-6 text-stone-600 dark:text-stone-400" />
                            <div>
                                <h3 class="font-semibold text-stone-800 dark:text-stone-200">IDR Reports</h3>
                                <p class="text-sm text-stone-600 dark:text-stone-400">Inventory Distribution Receipt
                                    reports</p>
                            </div>
                        </button>

                        @if ($reportType === 'idr')
                        <div class="ml-10 space-y-2">
                            <button wire:click="$set('reportFormat', 'batch')" type="button"
                                class="flex w-full items-start gap-x-4 rounded-lg border p-3 text-left transition {{ $reportFormat === 'batch' ? 'border-primary-500 bg-primary-50 ring-1 ring-primary-500 dark:bg-primary-500/20' : 'border-stone-200 bg-white hover:border-stone-300 dark:border-stone-700 dark:bg-stone-900 hover:dark:border-stone-600' }}">
                                <x-flux::icon.document-check class="h-5 w-5 text-stone-600 dark:text-stone-400" />
                                <div>
                                    <h3 class="font-semibold text-stone-800 dark:text-stone-200">IDR Batch</h3>
                                    <p class="text-sm text-stone-600 dark:text-stone-400">Generate report for IDR
                                        batch</p>
                                </div>
                            </button>
                            <button wire:click="$set('reportFormat', 'summary')" type="button"
                                class="flex w-full items-start gap-x-4 rounded-lg border p-3 text-left transition {{ $reportFormat === 'summary' ? 'border-primary-500 bg-primary-50 ring-1 ring-primary-500 dark:bg-primary-500/20' : 'border-stone-200 bg-white hover:border-stone-300 dark:border-stone-700 dark:bg-stone-900 hover:dark:border-stone-600' }}">
                                <x-flux::icon.document-text class="h-5 w-5 text-stone-600 dark:text-stone-400" />
                                <div>
                                    <h3 class="font-semibold text-stone-800 dark:text-stone-200">IDR Summary</h3>
                                    <p class="text-sm text-stone-600 dark:text-stone-400">Comprehensive IDR summary
                                        report</p>
                                </div>
                            </button>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Parameters --}}
            <div class="space-y-4">
                <h2 class="text-base font-semibold text-stone-900 dark:text-stone-100">Parameters</h2>
                <div class="rounded-lg border bg-white p-4 dark:border-stone-700 dark:bg-stone-900">
                    @if ($reportType === 'ics')
                        @if ($reportFormat === 'by_number')
                            <flux:field>
                                <flux:label for="ics_number" required>ICS Number</flux:label>
                                <flux:input id="ics_number" type="number" placeholder="Enter ICS number" />
                            </flux:field>
                        @elseif($reportFormat === 'by_employee')
                            <flux:field>
                                <flux:label for="employee_name_ics" required>Employee Name</flux:label>
                                <flux:input id="employee_name_ics" type="text"
                                    placeholder="Search for an employee..." />
                            </flux:field>
                        @endif
                    @elseif ($reportType === 'par')
                        @if ($reportFormat === 'by_number')
                            <flux:field>
                                <flux:label for="par_number" required>PAR Number</flux:label>
                                <flux:input id="par_number" type="number" placeholder="Enter PAR number" />
                            </flux:field>
                        @elseif($reportFormat === 'by_employee')
                            <flux:field>
                                <flux:label for="employee_name_par" required>Employee Name</flux:label>
                                <flux:input id="employee_name_par" type="text"
                                    placeholder="Search for an employee..." />
                            </flux:field>
                        @endif
                    @elseif ($reportType === 'idr')
                        @if ($reportFormat === 'batch')
                            <flux:field>
                                <flux:label for="idr_batch" required>IDR Batch #</flux:label>
                                <flux:input id="idr_batch" type="text" placeholder="Search batch..." />
                            </flux:field>
                        @endif
                    @endif

                    @if ($reportType === 'idr' && in_array($reportFormat, ['summary']))
                        <div class="text-sm text-stone-600 dark:text-stone-400">
                            No additional parameters required for this report format.
                        </div>
                    @endif

                    <div class="mt-4 border-t pt-4 dark:border-stone-700">
                        <flux:button variant="primary" class="w-full" wire:click="generatePreview">
                            Generate Preview
                        </flux:button>
                    </div>
                </div>
            </div>
        </div>

        <div class="relative lg:col-span-2">
            <div wire:loading wire:target="generatePreview"
                class="absolute inset-0 z-20 flex items-center justify-center rounded-lg bg-stone-100/80 dark:bg-stone-900/80">
                <x-flux::icon.arrow-path class="h-8 w-8 animate-spin text-stone-500" />
            </div>

            <div class="sticky top-8 h-fit">
                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-semibold text-stone-900 dark:text-stone-100">
                        Report Preview
                    </h2>
                    <div class="flex items-center gap-x-2">
                        <flux:button wire:click="zoomOut" variant="ghost" icon="minus" :disabled="$this->zoom <= 0.2" />
                        <div class="w-12 text-center text-sm text-stone-600 dark:text-stone-400">
                            {{ (int) ($this->zoom * 100) }}%</div>
                        <flux:button wire:click="zoomIn" variant="ghost" icon="plus" :disabled="$this->zoom >= 2.0" />
                        <flux:button wire:click="resetZoom" variant="ghost" icon="computer-desktop" />

                        <div class="mx-2 h-6 w-px bg-stone-200 dark:bg-stone-700"></div>

                        <flux:button variant="ghost" :disabled="!$this->isPreviewAvailable">
                            <x-flux::icon.arrow-down-tray class="-ml-1 h-5 w-5" />
                            Download to PDF
                        </flux:button>
                        <flux:button variant="ghost" :disabled="!$this->isPreviewAvailable">
                            <x-flux::icon.printer class="-ml-1 h-5 w-5" />
                            Print
                        </flux:button>
                    </div>
                </div>

                <div x-data="{
                    isDragging: false,
                    startX: 0,
                    startY: 0,
                    initialScrollLeft: 0,
                    initialScrollTop: 0,
                }" @mousedown.prevent="
                    isDragging = true;
                    startX = $event.pageX;
                    startY = $event.pageY;
                    initialScrollLeft = $el.scrollLeft;
                    initialScrollTop = $el.scrollTop;
                    $el.style.cursor = 'grabbing';
                " @mouseup.prevent.window="isDragging = false; $el.style.cursor = 'grab';"
                    @mousemove.prevent="
                        if (!isDragging) return;
                        const dx = $event.pageX - startX;
                        const dy = $event.pageY - startY;
                        $el.scrollLeft = initialScrollLeft - dx;
                        $el.scrollTop = initialScrollTop - dy;
                    "
                    class="mt-6 flex max-h-[calc(100vh-12rem)] items-start justify-center overflow-auto rounded-lg border bg-stone-100 p-8 dark:border-stone-700 dark:bg-stone-900/50"
                    style="cursor: grab;">
                    <div id="report-preview-content" class="origin-top space-y-8"
                        style="transform: scale({{ $this->zoom }});">
                        @if ($this->isPreviewAvailable)
                            @switch($reportType)
                                @case('ics')
                                    @if ($reportFormat === 'by_number')
                                        @include('livewire.admin.main.reports.formats.ics.by-number')
                                    @elseif($reportFormat === 'by_employee')
                                        @include('livewire.admin.main.reports.formats.ics.by-employee')
                                    @endif
                                @break

                                @case('par')
                                    @if ($reportFormat === 'by_number')
                                        @include('livewire.admin.main.reports.formats.par.by-number')
                                    @elseif($reportFormat === 'by_employee')
                                        @include('livewire.admin.main.reports.formats.par.by-employee')
                                    @endif
                                @break

                                @case('idr')
                                    @if ($reportFormat === 'batch')
                                        @include('livewire.admin.main.reports.formats.idr.batch')
                                    @endif
                                @break
                            @endswitch
                        @else
                            @include('livewire.admin.main.reports.formats.unavailable')
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div> 
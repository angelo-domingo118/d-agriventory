<?php

use Barryvdh\DomPDF\Facade\Pdf;
use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Computed;
use Illuminate\Support\Facades\Request;
use App\Services\ToastService;
use App\Models\IcsNumber;
use App\Models\ParNumber;
use App\Models\IdrNumber;
use App\Models\Employee;

new #[Layout('components.layouts.app')] class extends Component {
    /**
     * Initialize without any pre-selected report type or format.
     */
    public string $reportType = '';
    public string $reportFormat = '';
    public string $idrSignatoryStyle = 'default';
    public float $zoom = 1.0;
    public bool $previewGenerated = false;
    
    // Parameter inputs
    public ?string $ics_number = null;
    public ?string $par_number = null;
    public ?string $employee_name_ics = null;
    public ?string $employee_name_par = null;
    public ?string $idr_batch = null;
    public ?string $idr_employee = null;
    public ?string $rsmi_number = null;

    // Autocomplete state
    public array $icsNumberSuggestions = [];
    public bool $icsNumberShowSuggestions = false;
    public array $employeeSuggestions = [];
    public bool $employeeShowSuggestions = false;

    public function mount(): void
    {
        // Process query parameters if present
        $queryParams = Request::query();
        
        if (isset($queryParams['reportType']) && in_array($queryParams['reportType'], ['ics', 'par', 'idr'])) {
            $this->reportType = $queryParams['reportType'];
        }
        
        if (isset($queryParams['reportFormat'])) {
            // Set the format if it's valid for the current report type
            $validFormats = [
                'ics' => ['by_number', 'by_employee'],
                'par' => ['by_number', 'by_employee'],
                'idr' => ['batch', 'by_employee', 'by_rsmi_number'],
            ];
            
            if (isset($validFormats[$this->reportType]) && in_array($queryParams['reportFormat'], $validFormats[$this->reportType])) {
                $this->reportFormat = $queryParams['reportFormat'];
            }
        }
        
        // Prefill input fields based on the query parameters
        if ($this->reportType === 'ics') {
            if ($this->reportFormat === 'by_number' && isset($queryParams['ics_number'])) {
                $this->ics_number = $queryParams['ics_number'];
            } elseif ($this->reportFormat === 'by_employee' && isset($queryParams['employee_name'])) {
                $this->employee_name_ics = $queryParams['employee_name'];
            }
        } elseif ($this->reportType === 'par') {
            if ($this->reportFormat === 'by_number' && isset($queryParams['par_number'])) {
                $this->par_number = $queryParams['par_number'];
            } elseif ($this->reportFormat === 'by_employee' && isset($queryParams['employee_name'])) {
                $this->employee_name_par = $queryParams['employee_name'];
            }
        } elseif ($this->reportType === 'idr') {
            if ($this->reportFormat === 'batch' && isset($queryParams['idr_batch'])) {
                $this->idr_batch = $queryParams['idr_batch'];
            } elseif ($this->reportFormat === 'by_employee' && isset($queryParams['employee_name'])) {
                $this->idr_employee = $queryParams['employee_name'];
            } elseif ($this->reportFormat === 'by_rsmi_number' && isset($queryParams['rsmi_number'])) {
                $this->rsmi_number = $queryParams['rsmi_number'];
            }
        }
        
        // If we have parameters, automatically generate the preview
        if (count(array_intersect_key($queryParams, array_flip(['ics_number', 'par_number', 'employee_name', 'idr_batch', 'rsmi_number']))) > 0) {
            $this->generatePreview();
        }
        
        $this->resetZoom();
    }

    public function updatedReportType(string $value): void
    {
        // Keep all dropdowns closed until the user explicitly chooses a format.
        $this->reportFormat = '';
        $this->resetZoom();
        $this->previewGenerated = false;
    }

    public function updatedReportFormat(): void
    {
        $this->resetZoom();
        $this->previewGenerated = false;
    }

    public function updatedIdrSignatoryStyle(): void
    {
        $this->previewGenerated = false;
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
            $this->zoom = 0.9;
        } else {
            $this->zoom = 1.0;
        }
    }

    public function generatePreview(): void
    {
        // Ensure all required inputs are present
        if (! $this->isPreviewAvailable()) {
            ToastService::validationError($this, 'Please complete the required parameters before generating a preview.');
            return;
        }

        // Validate that the parameters exist in the database
        if (! $this->validateParametersExist()) {
            return; // Error messages are handled in validateParametersExist()
        }

        // This is for demonstration to show loading state
        sleep(1);
        $this->previewGenerated = true;
    }

    private function validateParametersExist(): bool
    {
        switch ([$this->reportType, $this->reportFormat]) {
            case ['ics', 'by_number']:
                if (! IcsNumber::where('ics_number', $this->ics_number)->exists()) {
                    ToastService::validationError($this, "ICS Number '{$this->ics_number}' not found in the database.");
                    return false;
                }
                break;

            case ['ics', 'by_employee']:
                if (! Employee::where('name', $this->employee_name_ics)->exists()) {
                    ToastService::validationError($this, "Employee '{$this->employee_name_ics}' not found in the database.");
                    return false;
                }
                // Also check if the employee has any ICS records
                if (! IcsNumber::whereHas('assignedEmployee', fn($q) => $q->where('name', $this->employee_name_ics))->exists()) {
                    ToastService::validationError($this, "No ICS records found for employee '{$this->employee_name_ics}'.");
                    return false;
                }
                break;

            case ['par', 'by_number']:
                if (! ParNumber::where('par_number', $this->par_number)->exists()) {
                    ToastService::validationError($this, "PAR Number '{$this->par_number}' not found in the database.");
                    return false;
                }
                break;

            case ['par', 'by_employee']:
                if (! Employee::where('name', $this->employee_name_par)->exists()) {
                    ToastService::validationError($this, "Employee '{$this->employee_name_par}' not found in the database.");
                    return false;
                }
                // Also check if the employee has any PAR records
                if (! ParNumber::whereHas('assignedEmployee', fn($q) => $q->where('name', $this->employee_name_par))->exists()) {
                    ToastService::validationError($this, "No PAR records found for employee '{$this->employee_name_par}'.");
                    return false;
                }
                break;

            case ['idr', 'batch']:
                // Check if IDR batch exists
                if (! IdrNumber::where('batch_number', $this->idr_batch)->exists()) {
                    ToastService::validationError($this, "IDR Batch '{$this->idr_batch}' not found in the database.");
                    return false;
                }
                break;

            case ['idr', 'by_employee']:
                if (! Employee::where('name', $this->idr_employee)->exists()) {
                    ToastService::validationError($this, "Employee '{$this->idr_employee}' not found in the database.");
                    return false;
                }
                // Also check if the employee has any IDR records
                if (! IdrNumber::whereHas('assignedEmployee', fn($q) => $q->where('name', $this->idr_employee))->exists()) {
                    ToastService::validationError($this, "No IDR records found for employee '{$this->idr_employee}'.");
                    return false;
                }
                break;

            case ['idr', 'by_rsmi_number']:
                // Check if RSMI number exists (assuming it's stored in IdrNumber or related model)
                if (! IdrNumber::where('rsmi_number', $this->rsmi_number)->exists()) {
                    ToastService::validationError($this, "RSMI Number '{$this->rsmi_number}' not found in the database.");
                    return false;
                }
                break;
        }

        return true;
    }

    public function downloadPdf()
    {
        $viewPath = 'livewire.admin.main.reports.formats.' . $this->reportType . '.';
        
        switch ($this->reportType) {
            case 'idr':
                $viewPath .= match ($this->reportFormat) {
                    'batch'             => $this->idrSignatoryStyle === 'default' ? 'batch-combined' : 'batch-detailed',
                    'by_employee'       => 'by-employee',
                    'by_rsmi_number'    => 'by-rsmi-number',
                    default             => $this->reportFormat,
                };
                break;

            default:
                // Convert snake_case to kebab-case for view names (e.g., by_number -> by-number)
                $viewPath .= str_replace('_', '-', $this->reportFormat);
                break;
        }

        $pdfData = [];
        
        // Populate data based on report type and format
        switch ([$this->reportType, $this->reportFormat]) {
            case ['ics', 'by_number']:
                $pdfData['ics'] = $this->icsBatch;
                break;
                
            case ['ics', 'by_employee']:
                $pdfData['items'] = $this->icsItemsByEmployee;
                $pdfData['employeeName'] = $this->employee_name_ics;
                break;
                
            case ['par', 'by_number']:
                $pdfData['par'] = $this->parBatch;
                break;
                
            case ['par', 'by_employee']:
                $pdfData['items'] = $this->parItemsByEmployee;
                $pdfData['employeeName'] = $this->employee_name_par;
                break;
                
            case ['idr', 'batch']:
                $pdfData['idrBatch'] = $this->idrBatch;
                $pdfData['idrSignatoryStyle'] = $this->idrSignatoryStyle;
                break;
                
            case ['idr', 'by_employee']:
                $pdfData['items'] = $this->idrItemsByEmployee;
                $pdfData['employeeName'] = $this->idr_employee;
                break;
                
            case ['idr', 'by_rsmi_number']:
                $pdfData['items'] = $this->idrItemsByRsmi;
                $pdfData['rsmiNumber'] = $this->rsmi_number;
                break;
        }

        // Make the report format available inside the Blade template (used for CSS tweaks)
        $pdfData['reportFormat'] = $this->reportFormat;
        $pdfData['reportType']   = $this->reportType;

        // Determine orientation - employee reports should be landscape
        $orientation = ($this->reportFormat === 'by_employee') ? 'landscape' : 'portrait';
        
        $pdf = Pdf::loadView($viewPath, $pdfData)->setPaper('a4', $orientation);

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, 'report.pdf');
    }

    #[Computed]
    public function isPreviewAvailable(): bool
    {
        // Valid type/format combinations
        $validCombos = [
            'ics' => ['by_number', 'by_employee'],
            'par' => ['by_number', 'by_employee'],
            'idr' => ['batch', 'by_employee', 'by_rsmi_number'],
        ];

        // Must first be a valid combination
        if (! isset($validCombos[$this->reportType]) || ! in_array($this->reportFormat, $validCombos[$this->reportType])) {
            return false;
        }

        // Now ensure the required parameters are present
        return match ([$this->reportType, $this->reportFormat]) {
            ['ics', 'by_number']       => filled($this->ics_number),
            ['ics', 'by_employee']     => filled($this->employee_name_ics),
            ['par', 'by_number']       => filled($this->par_number),
            ['par', 'by_employee']     => filled($this->employee_name_par),
            ['idr', 'batch']           => filled($this->idr_batch),
            ['idr', 'by_employee']     => filled($this->idr_employee),
            ['idr', 'by_rsmi_number']  => filled($this->rsmi_number),
            default                    => false,
        };
    }

    #[Computed]
    public function icsBatch(): ?\App\Models\IcsNumber
    {
        if ($this->reportType === 'ics' && $this->reportFormat === 'by_number' && $this->ics_number) {
            return IcsNumber::with([
                'assignedEmployee.division',
                'contractItem.itemSpecification.itemCatalog',
                'itemBatches.components',
            ])->where('ics_number', $this->ics_number)->first();
        }
        return null;
    }

    #[Computed]
    public function icsItemsByEmployee()
    {
        if ($this->reportType === 'ics' && $this->reportFormat === 'by_employee' && $this->employee_name_ics) {
            $employeeName = $this->employee_name_ics;
            return IcsNumber::with([
                'contractItem.itemSpecification.itemCatalog',
                'itemBatches.components',
            ])->whereHas('assignedEmployee', fn($q) => $q->where('name', $employeeName))->get();
        }
        return collect();
    }

    #[Computed]
    public function parBatch(): ?\App\Models\ParNumber
    {
        if ($this->reportType === 'par' && $this->reportFormat === 'by_number' && $this->par_number) {
            return ParNumber::with([
                'assignedEmployee.division',
                'contractItem.itemSpecification.itemCatalog',
                'itemBatches.components',
            ])->where('par_number', $this->par_number)->first();
        }
        return null;
    }

    #[Computed]
    public function parItemsByEmployee()
    {
        if ($this->reportType === 'par' && $this->reportFormat === 'by_employee' && $this->employee_name_par) {
            $employeeName = $this->employee_name_par;
            return ParNumber::with([
                'contractItem.itemSpecification.itemCatalog',
                'itemBatches.components',
            ])->whereHas('assignedEmployee', fn($q) => $q->where('name', $employeeName))->get();
        }
        return collect();
    }

    #[Computed]
    public function idrBatch()
    {
        if ($this->reportType === 'idr' && $this->reportFormat === 'batch' && $this->idr_batch) {
            return IdrNumber::with([
                'assignedEmployee.division',
                'itemBatches.components',
            ])->where('batch_number', $this->idr_batch)->get();
        }
        return collect();
    }

    #[Computed]
    public function idrItemsByEmployee()
    {
        if ($this->reportType === 'idr' && $this->reportFormat === 'by_employee' && $this->idr_employee) {
            $employeeName = $this->idr_employee;
            return IdrNumber::with([
                'itemBatches.components',
            ])->whereHas('assignedEmployee', fn($q) => $q->where('name', $employeeName))->get();
        }
        return collect();
    }

    #[Computed]
    public function idrItemsByRsmi()
    {
        if ($this->reportType === 'idr' && $this->reportFormat === 'by_rsmi_number' && $this->rsmi_number) {
            return IdrNumber::with([
                'assignedEmployee.division',
                'itemBatches.components',
            ])->where('rsmi_number', $this->rsmi_number)->get();
        }
        return collect();
    }

    /*
    |-------------------------------------------------------------------------
    | Autocomplete helpers
    |-------------------------------------------------------------------------
    */

    public function updatedIcsNumber(string $value): void
    {
        $this->icsNumberSuggestions = IcsNumber::query()
            ->where('ics_number', 'like', $value . '%')
            ->orderBy('ics_number')
            ->limit(10)
            ->get()
            ->map(fn (IcsNumber $ics) => ['id' => $ics->id, 'name' => (string) $ics->ics_number])
            ->toArray();

        $this->icsNumberShowSuggestions = count($this->icsNumberSuggestions) > 0;
    }

    public function updatedEmployeeNameIcs(string $value): void
    {
        $this->employeeSuggestions = Employee::query()
            ->where('name', 'like', '%' . $value . '%')
            ->orderBy('name')
            ->limit(10)
            ->get()
            ->map(fn (Employee $emp) => ['id' => $emp->id, 'name' => $emp->name])
            ->toArray();

        $this->employeeShowSuggestions = count($this->employeeSuggestions) > 0;
    }

    public function loadInitialIcsNumberSuggestions(): void
    {
        $this->icsNumberSuggestions = IcsNumber::query()
            ->orderBy('ics_number')
            ->limit(10)
            ->get()
            ->map(fn (IcsNumber $ics) => ['id' => $ics->id, 'name' => (string) $ics->ics_number])
            ->toArray();
        $this->icsNumberShowSuggestions = true;
    }

    public function loadInitialEmployeeSuggestions(): void
    {
        $this->employeeSuggestions = Employee::query()
            ->orderBy('name')
            ->limit(10)
            ->get()
            ->map(fn (Employee $emp) => ['id' => $emp->id, 'name' => $emp->name])
            ->toArray();
        $this->employeeShowSuggestions = true;
    }

    public function selectIcsNumberSuggestion(array $suggestion): void
    {
        $this->ics_number = $suggestion['name'] ?? '';
        $this->icsNumberShowSuggestions = false;
    }

    public function selectEmployeeSuggestion(array $suggestion): void
    {
        $this->employee_name_ics = $suggestion['name'] ?? '';
        $this->employeeShowSuggestions = false;
    }
}; ?>

<div>
    <div class="flex items-center justify-between mb-4">
        <!-- Breadcrumbs as Title -->
        <div>
            <flux:breadcrumbs class="text-2xl font-semibold">
                <flux:breadcrumbs.item :href="route('admin.dashboard')" wire:navigate icon="home" class="text-xl sm:text-2xl font-semibold text-stone-700 dark:text-stone-300" />
                <flux:breadcrumbs.item class="text-xl sm:text-2xl font-semibold text-stone-500 dark:text-stone-400">Main</flux:breadcrumbs.item>
                <flux:breadcrumbs.item class="text-xl sm:text-2xl font-semibold text-stone-900 dark:text-stone-100">Reports</flux:breadcrumbs.item>
            </flux:breadcrumbs>
        </div>
    </div>

    <style>
        @media print {
            .print-hide {
                display: none !important;
            }

            .print-area {
                position: absolute;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
            }

            .print-area .print-scroll-container {
                overflow: visible !important;
                max-height: none !important;
                border: none !important;
                padding: 0 !important;
                background: none !important;
            }

            .print-area #report-preview-content {
                transform: scale(1.0) !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
        #report-preview-content .report-page + .report-page {
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 2px dashed #d6d3d1;
        }
        .dark #report-preview-content .report-page + .report-page {
            border-top-color: #44403c;
        }
    </style>
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div class="col-span-1 space-y-8 print-hide">
            {{-- Report Types --}}
            <div class="space-y-4">
                <h2 class="text-lg font-semibold text-stone-900 dark:text-stone-100">Report Types</h2>
                <div class="mt-6 space-y-2">
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
                                    <h3 class="font-semibold text-stone-800 dark:text-stone-200">INVENTORY CUSTODIAN SLIP</h3>
                                    <p class="text-sm text-stone-600 dark:text-stone-400">Generate report for a
                                        specific ICS batch</p>
                                </div>
                            </button>
                            <button wire:click="$set('reportFormat', 'by_employee')" type="button"
                                class="flex w-full items-start gap-x-4 rounded-lg border p-3 text-left transition {{ $reportFormat === 'by_employee' ? 'border-primary-500 bg-primary-50 ring-1 ring-primary-500 dark:bg-primary-500/20' : 'border-stone-200 bg-white hover:border-stone-300 dark:border-stone-700 dark:bg-stone-900 hover:dark:border-stone-600' }}">
                                <x-flux::icon.user-circle class="h-5 w-5 text-stone-600 dark:text-stone-400" />
                                <div>
                                    <h3 class="font-semibold text-stone-800 dark:text-stone-200">LIST OF "I.C.S." ISSUED TO EMPLOYEE</h3>
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
                                    <h3 class="font-semibold text-stone-800 dark:text-stone-200">PROPERTY ACKNOWLEDGEMENT RECEIPT</h3>
                                    <p class="text-sm text-stone-600 dark:text-stone-400">Generate report for a
                                        specific PAR</p>
                                </div>
                            </button>
                            <button wire:click="$set('reportFormat', 'by_employee')" type="button"
                                class="flex w-full items-start gap-x-4 rounded-lg border p-3 text-left transition {{ $reportFormat === 'by_employee' ? 'border-primary-500 bg-primary-50 ring-1 ring-primary-500 dark:bg-primary-500/20' : 'border-stone-200 bg-white hover:border-stone-300 dark:border-stone-700 dark:bg-stone-900 hover:dark:border-stone-600' }}">
                                <x-flux::icon.user-circle class="h-5 w-5 text-stone-600 dark:text-stone-400" />
                                <div>
                                    <h3 class="font-semibold text-stone-800 dark:text-stone-200">LIST OF "P.A.R." ISSUED TO EMPLOYEE</h3>
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
                            <x-flux::icon.clipboard-document-check class="h-6 w-6 text-stone-600 dark:text-stone-400" />
                            <div>
                                <h3 class="font-semibold text-stone-800 dark:text-stone-200">IDR Reports</h3>
                                <p class="text-sm text-stone-600 dark:text-stone-400">Inventory and Distribution
                                    Reports</p>
                            </div>
                        </button>

                        @if ($reportType === 'idr')
                        <div class="ml-10 space-y-2">
                            <button wire:click="$set('reportFormat', 'batch')" type="button"
                                class="flex w-full items-start gap-x-4 rounded-lg border p-3 text-left transition {{ $reportFormat === 'batch' ? 'border-primary-500 bg-primary-50 ring-1 ring-primary-500 dark:bg-primary-500/20' : 'border-stone-200 bg-white hover:border-stone-300 dark:border-stone-700 dark:bg-stone-900 hover:dark:border-stone-600' }}">
                                <x-flux::icon.archive-box class="h-5 w-5 text-stone-600 dark:text-stone-400" />
                                <div>
                                    <h3 class="font-semibold text-stone-800 dark:text-stone-200">INVENTORY AND DISTRIBUTION REPORT</h3>
                                    <p class="text-sm text-stone-600 dark:text-stone-400">Generate report for a
                                        specific IDR batch</p>
                                </div>
                            </button>
                            <button wire:click="$set('reportFormat', 'by_employee')" type="button"
                                class="flex w-full items-start gap-x-4 rounded-lg border p-3 text-left transition {{ $reportFormat === 'by_employee' ? 'border-primary-500 bg-primary-50 ring-1 ring-primary-500 dark:bg-primary-500/20' : 'border-stone-200 bg-white hover:border-stone-300 dark:border-stone-700 dark:bg-stone-900 hover:dark:border-stone-600' }}">
                                <x-flux::icon.user-circle class="h-5 w-5 text-stone-600 dark:text-stone-400" />
                                <div>
                                    <h3 class="font-semibold text-stone-800 dark:text-stone-200">SUMMARY OF IDR BY EMPLOYEE</h3>
                                    <p class="text-sm text-stone-600 dark:text-stone-400">Generate a summary for an
                                        employee</p>
                                </div>
                            </button>
                            <button wire:click="$set('reportFormat', 'by_rsmi_number')" type="button"
                                class="flex w-full items-start gap-x-4 rounded-lg border p-3 text-left transition {{ $reportFormat === 'by_rsmi_number' ? 'border-primary-500 bg-primary-50 ring-1 ring-primary-500 dark:bg-primary-500/20' : 'border-stone-200 bg-white hover:border-stone-300 dark:border-stone-700 dark:bg-stone-900 hover:dark:border-stone-600' }}">
                                <x-flux::icon.document-text class="h-5 w-5 text-stone-600 dark:text-stone-400" />
                                <div>
                                    <h3 class="font-semibold text-stone-800 dark:text-stone-200">REPORT OF SUPPLIES AND MATERIALS ISSUED</h3>
                                    <p class="text-sm text-stone-600 dark:text-stone-400">Report of Supplies and
                                        Materials Issued</p>
                                </div>
                            </button>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Parameters --}}
            <div class="space-y-4" x-show="$wire.reportFormat !== ''" x-transition>
                <h2 class="text-base font-semibold text-stone-900 dark:text-stone-100">Parameters</h2>
                <div class="rounded-lg border bg-white p-4 dark:border-stone-700 dark:bg-stone-900">
                    @if ($reportType === 'ics')
                        @if ($reportFormat === 'by_number')
                            <x-autocomplete
                                id="ics_number"
                                wire:model.live="ics_number"
                                label="ICS Number"
                                placeholder="Search ICS number..."
                                wire:suggestions="icsNumberSuggestions"
                                wire:showSuggestions="icsNumberShowSuggestions"
                                required
                                onFocus="$wire.loadInitialIcsNumberSuggestions()"
                                onSelect="$wire.selectIcsNumberSuggestion"
                            />
                        @elseif($reportFormat === 'by_employee')
                            <x-autocomplete
                                id="employee_name_ics"
                                wire:model.live="employee_name_ics"
                                label="Employee Name"
                                placeholder="Search for an employee..."
                                wire:suggestions="employeeSuggestions"
                                wire:showSuggestions="employeeShowSuggestions"
                                required
                                onFocus="$wire.loadInitialEmployeeSuggestions()"
                                onSelect="$wire.selectEmployeeSuggestion"
                            />
                        @endif
                    @elseif ($reportType === 'par')
                        @if ($reportFormat === 'by_number')
                            <flux:field>
                                <flux:label for="par_number" required>PAR Number</flux:label>
                                <flux:input id="par_number" type="number" placeholder="Enter PAR number" wire:model="par_number" />
                            </flux:field>
                        @elseif($reportFormat === 'by_employee')
                            <flux:field>
                                <flux:label for="employee_name_par" required>Employee Name</flux:label>
                                <flux:input id="employee_name_par" type="text"
                                    placeholder="Search for an employee..." wire:model="employee_name_par" />
                            </flux:field>
                        @endif
                    @elseif ($reportType === 'idr')
                        @if ($reportFormat === 'batch')
                            <flux:field>
                                <flux:label for="idr_batch" required>IDR Batch #</flux:label>
                                <flux:input id="idr_batch" type="text" placeholder="Search batch..." wire:model="idr_batch" />
                            </flux:field>
                            <div class="mt-4 space-y-2 border-t border-stone-200 pt-4 dark:border-stone-700">
                                <flux:label>Signatory Style</flux:label>
                                <fieldset class="mt-2">
                                    <legend class="sr-only">Signatory style</legend>
                                    <div class="space-y-3">
                                        <div class="flex items-center">
                                            <input id="idr_signatory_default" name="idr_signatory_style" type="radio"
                                                wire:model.live="idrSignatoryStyle" value="default"
                                                class="h-4 w-4 border-stone-300 text-primary-600 focus:ring-primary-600 dark:border-stone-600 dark:bg-stone-800 dark:checked:bg-primary-600">
                                            <label for="idr_signatory_default"
                                                class="ml-3 block text-sm font-medium leading-6 text-stone-900 dark:text-stone-100">
                                                IDR (Combined)
                                                <span class="ml-2 text-xs text-stone-500 dark:text-stone-400">
                                                    Single "Received & Approved by" signatory.
                                                </span>
                                            </label>
                                        </div>
                                        <div class="flex items-center">
                                            <input id="idr_signatory_detailed" name="idr_signatory_style" type="radio"
                                                wire:model.live="idrSignatoryStyle" value="detailed"
                                                class="h-4 w-4 border-stone-300 text-primary-600 focus:ring-primary-600 dark:border-stone-600 dark:bg-stone-800 dark:checked:bg-primary-600">
                                            <label for="idr_signatory_detailed"
                                                class="ml-3 block text-sm font-medium leading-6 text-stone-900 dark:text-stone-100">
                                                IDR (Detailed)
                                                <span class="ml-2 text-xs text-stone-500 dark:text-stone-400">
                                                    Separate "Received by" and "Approved by" signatories.
                                                </span>
                                            </label>
                                        </div>
                                    </div>
                                </fieldset>
                            </div>
                        @elseif ($reportFormat === 'by_employee')
                            <flux:field>
                                <flux:label for="idr_employee" required>Employee</flux:label>
                                <flux:input id="idr_employee" type="text" placeholder="Search employee..." wire:model="idr_employee" />
                            </flux:field>
                        @elseif($reportFormat === 'by_rsmi_number')
                            <flux:field>
                                <flux:label for="rsmi_number" required>RSMI Number</flux:label>
                                <flux:input id="rsmi_number" type="text" placeholder="Enter RSMI number" wire:model="rsmi_number" />
                            </flux:field>
                        @endif

                        @if ($reportFormat === 'summary')
                            <div class="text-sm text-stone-600 dark:text-stone-400">
                                No additional parameters required for this report format.
                            </div>
                        @endif
                    @endif

                    <div class="mt-4 border-t pt-4 dark:border-stone-700">
                        <flux:button variant="primary" class="w-full" wire:click="generatePreview" :disabled="!$this->isPreviewAvailable">
                            Generate Preview
                        </flux:button>
                    </div>
                </div>
            </div>
        </div>

        <div class="relative lg:col-span-2 print-area">
            <div class="sticky top-8 h-fit">
                <div class="flex items-center justify-between print-hide">
                    <h2 class="text-lg font-semibold text-stone-900 dark:text-stone-100">
                        Report Preview
                    </h2>
                    <div class="flex items-center gap-x-2">
                        <flux:button wire:click="zoomOut" variant="ghost" icon="minus" :disabled="$this->zoom <= 0.2" />
                        <div class="w-12 text-center text-sm text-stone-600 dark:text-stone-400">
                            {{ (int) ($this->zoom * 100) }}%</div>
                        <flux:button wire:click="zoomIn" variant="ghost" icon="plus" :disabled="$this->zoom >= 2.0" />
                        <flux:button wire:click="resetZoom" variant="ghost" icon="arrows-pointing-in" />

                        <div class="mx-2 h-6 w-px bg-stone-200 dark:bg-stone-700"></div>

                        <flux:button wire:click="downloadPdf" variant="ghost" :disabled="!$this->isPreviewAvailable" wire:loading.attr="disabled"
                            wire:target="downloadPdf">
                            <div class="flex items-center">
                                <x-flux::icon.arrow-down-tray wire:loading.remove wire:target="downloadPdf"
                                    class="h-5 w-5" />
                                <x-flux::icon.arrow-path wire:loading wire:target="downloadPdf"
                                    class="h-5 w-5 animate-spin" />
                                <span class="ml-2">
                                    <span wire:loading.remove wire:target="downloadPdf">Download to PDF</span>
                                    <span wire:loading wire:target="downloadPdf">Downloading...</span>
                                </span>
                            </div>
                        </flux:button>
                        <flux:button x-on:click.prevent="window.print()" variant="ghost"
                            :disabled="!$this->isPreviewAvailable">
                            <div class="flex items-center">
                                <x-flux::icon.printer class="h-5 w-5" />
                                <span class="ml-2">Print</span>
                            </div>
                        </flux:button>
                    </div>
                </div>

                {{-- Show loading state --}}
                <div wire:loading.block wire:target="generatePreview">
                    <div class="mt-6 flex max-h-[calc(100vh-12rem)] items-start justify-center overflow-y-auto overflow-x-hidden rounded-lg border bg-stone-100 p-8 dark:border-stone-700 dark:bg-stone-900/50 print-scroll-container"
                        x-data="{
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
                        @wheel="
                            if ($event.ctrlKey) {
                                $event.preventDefault();
                                if ($event.deltaY < 0) {
                                    $wire.zoomIn();
                                } else if ($event.deltaY > 0) {
                                    $wire.zoomOut();
                                }
                            }
                        "
                        style="cursor: grab;">
                        <div id="report-preview-content" class="origin-top max-w-full"
                            style="transform: scale({{ $this->zoom }}); max-width: calc(100% / {{ $this->zoom }});">
                            @if ($reportFormat === 'by_employee')
                                {{-- Landscape Skeleton --}}
                                <div
                                    class="w-[1024px] aspect-[1.414/1] animate-pulse rounded-lg bg-stone-200 p-8 dark:bg-stone-800/50">
                                    <div class="h-6 rounded bg-stone-300 dark:bg-stone-700 w-1/3 mx-auto"></div>
                                    <div class="mt-8 flex justify-between">
                                        <div class="h-4 rounded bg-stone-300 dark:bg-stone-700 w-1/3"></div>
                                        <div class="h-4 rounded bg-stone-300 dark:bg-stone-700 w-1/4"></div>
                                    </div>
                                    <div
                                        class="mt-6 p-4 border-2 border-stone-300 dark:border-stone-700 rounded-md">
                                        <div class="h-8 rounded bg-stone-300/50 dark:bg-stone-700/50"></div>
                                        <div class="space-y-4 mt-4">
                                            @for ($i = 0; $i < 5; $i++)
                                                <div
                                                    class="h-10 rounded bg-stone-300/50 dark:bg-stone-700/50">
                                                </div>
                                            @endfor
                                        </div>
                                    </div>
                                    <div class="absolute bottom-8 left-8 right-8 flex justify-between">
                                        <div class="h-4 rounded bg-stone-300 dark:bg-stone-700 w-1/6"></div>
                                        <div class="h-4 rounded bg-stone-300 dark:bg-stone-700 w-1/6"></div>
                                    </div>
                                </div>
                            @else
                                {{-- Portrait Skeleton --}}
                                <div
                                    class="w-[724px] aspect-[1/1.414] animate-pulse rounded-lg bg-stone-200 p-8 dark:bg-stone-800/50">
                                    <div class="h-6 rounded bg-stone-300 dark:bg-stone-700 w-1/3 mx-auto"></div>
                                    <div class="mt-8 flex justify-between">
                                        <div class="space-y-2 w-1/2">
                                            <div class="h-4 rounded bg-stone-300 dark:bg-stone-700 w-3/4">
                                            </div>
                                            <div class="h-4 rounded bg-stone-300 dark:bg-stone-700 w-full">
                                            </div>
                                            <div class="h-4 rounded bg-stone-300 dark:bg-stone-700 w-1/2">
                                            </div>
                                        </div>
                                        <div class="h-4 rounded bg-stone-300 dark:bg-stone-700 w-1/4"></div>
                                    </div>
                                    <div
                                        class="mt-6 border-2 border-stone-300 dark:border-stone-700 rounded-md">
                                        <div class="h-64"></div>
                                    </div>
                                    <div class="mt-8 grid grid-cols-2 gap-16">
                                        <div>
                                            <div class="h-4 rounded bg-stone-300 dark:bg-stone-700 w-1/4">
                                            </div>
                                            <div
                                                class="h-6 rounded bg-stone-300 dark:bg-stone-700 w-full mt-10">
                                            </div>
                                            <div
                                                class="h-6 rounded bg-stone-300 dark:bg-stone-700 w-full mt-6">
                                            </div>
                                            <div
                                                class="h-6 rounded bg-stone-300 dark:bg-stone-700 w-full mt-6">
                                            </div>
                                        </div>
                                        <div>
                                            <div class="h-4 rounded bg-stone-300 dark:bg-stone-700 w-1/4">
                                            </div>
                                            <div
                                                class="h-6 rounded bg-stone-300 dark:bg-stone-700 w-full mt-10">
                                            </div>
                                            <div
                                                class="h-6 rounded bg-stone-300 dark:bg-stone-700 w-full mt-6">
                                            </div>
                                            <div
                                                class="h-6 rounded bg-stone-300 dark:bg-stone-700 w-full mt-6">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Show preview or placeholder --}}
                <div wire:loading.remove.block wire:target="generatePreview">
                    @if ($previewGenerated && $this->isPreviewAvailable)
                        {{-- Scrollable container for actual previews --}}
                        <div class="mt-6 flex max-h-[calc(100vh-12rem)] items-start justify-center overflow-y-auto overflow-x-hidden rounded-lg border bg-stone-100 p-8 dark:border-stone-700 dark:bg-stone-900/50 print-scroll-container"
                            x-data="{
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
                            @wheel="
                                if ($event.ctrlKey) {
                                    $event.preventDefault();
                                    if ($event.deltaY < 0) {
                                        $wire.zoomIn();
                                    } else if ($event.deltaY > 0) {
                                        $wire.zoomOut();
                                    }
                                }
                            "
                            style="cursor: grab;">
                            <div id="report-preview-content" class="origin-top max-w-full"
                                style="transform: scale({{ $this->zoom }}); max-width: calc(100% / {{ $this->zoom }});">
                                @switch($reportType)
                                    @case('ics')
                                        @if ($reportFormat === 'by_number')
                                            @include('livewire.admin.main.reports.formats.ics.by-number', [
                                                'ics' => $this->icsBatch,
                                                'reportFormat' => $reportFormat,
                                                'reportType' => $reportType
                                            ])
                                        @elseif($reportFormat === 'by_employee')
                                            @include('livewire.admin.main.reports.formats.ics.by-employee', [
                                                'items' => $this->icsItemsByEmployee,
                                                'employeeName' => $this->employee_name_ics,
                                                'reportFormat' => $reportFormat,
                                                'reportType' => $reportType
                                            ])
                                        @endif
                                    @break

                                    @case('par')
                                        @if ($reportFormat === 'by_number')
                                            @include('livewire.admin.main.reports.formats.par.by-number', [
                                                'par' => $this->parBatch,
                                                'reportFormat' => $reportFormat,
                                                'reportType' => $reportType
                                            ])
                                        @elseif($reportFormat === 'by_employee')
                                            @include('livewire.admin.main.reports.formats.par.by-employee', [
                                                'items' => $this->parItemsByEmployee,
                                                'employeeName' => $this->employee_name_par,
                                                'reportFormat' => $reportFormat,
                                                'reportType' => $reportType
                                            ])
                                        @endif
                                    @break

                                    @case('idr')
                                        @if ($reportFormat === 'batch')
                                            @if ($idrSignatoryStyle === 'default')
                                                @include('livewire.admin.main.reports.formats.idr.batch-combined', [
                                                    'idrBatch' => $this->idrBatch,
                                                    'idrSignatoryStyle' => $idrSignatoryStyle,
                                                    'reportFormat' => $reportFormat,
                                                    'reportType' => $reportType
                                                ])
                                            @else
                                                @include('livewire.admin.main.reports.formats.idr.batch-detailed', [
                                                    'idrBatch' => $this->idrBatch,
                                                    'idrSignatoryStyle' => $idrSignatoryStyle,
                                                    'reportFormat' => $reportFormat,
                                                    'reportType' => $reportType
                                                ])
                                            @endif
                                        @elseif($reportFormat === 'by_employee')
                                            @include('livewire.admin.main.reports.formats.idr.by-employee', [
                                                'items' => $this->idrItemsByEmployee,
                                                'employeeName' => $this->idr_employee,
                                                'reportFormat' => $reportFormat,
                                                'reportType' => $reportType
                                            ])
                                        @elseif($reportFormat === 'by_rsmi_number')
                                            @include('livewire.admin.main.reports.formats.idr.by-rsmi-number', [
                                                'items' => $this->idrItemsByRsmi,
                                                'rsmiNumber' => $this->rsmi_number,
                                                'reportFormat' => $reportFormat,
                                                'reportType' => $reportType
                                            ])
                                        @endif
                                        @break
                                @endswitch
                            </div>
                        </div>
                    @else
                        {{-- Styled placeholder container matching the document preview container --}}
                        <div class="mt-6 flex items-center justify-center min-h-[calc(100vh-12rem)] rounded-lg border bg-stone-100 p-8 dark:border-stone-700 dark:bg-stone-900/50">
                            <div class="text-center">
                                <x-flux::icon.document-magnifying-glass
                                    class="mx-auto h-12 w-12 text-stone-400 dark:text-stone-500" />
                                <h3 class="mt-2 text-sm font-semibold text-stone-900 dark:text-stone-100">No Preview Available</h3>
                                <p class="mt-1 text-sm text-stone-500 dark:text-stone-400">
                                    Generate a report to see a preview.
                                </p>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div> 
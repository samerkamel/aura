{{-- Convert to Contract Modal --}}
<div class="modal fade" id="convertToContractModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form action="{{ route('accounting.estimates.convert-with-project', $estimate) }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="ti ti-transform me-2"></i>Convert to Contract
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    {{-- Conversion Preview --}}
                    <div class="alert alert-info mb-4">
                        <h6 class="alert-heading mb-2"><i class="ti ti-info-circle me-1"></i>Conversion Preview</h6>
                        <div class="row">
                            <div class="col-md-6">
                                <small class="d-block"><strong>Contract Number:</strong> {{ \Modules\Accounting\Models\Contract::previewNextContractNumber(now()->toDateString()) }}</small>
                                <small class="d-block"><strong>Total Amount:</strong> EGP {{ number_format($estimate->total, 2) }}</small>
                            </div>
                            <div class="col-md-6">
                                <small class="d-block"><strong>Payment Milestones:</strong> {{ $estimate->items->count() }}</small>
                                <small class="d-block"><strong>Client:</strong> {{ $estimate->client_name }}</small>
                            </div>
                        </div>
                    </div>

                    {{-- Contract Start Date --}}
                    <div class="mb-4">
                        <label class="form-label" for="contract_start_date">Contract Start Date</label>
                        <input type="date" class="form-control" id="contract_start_date" name="contract_start_date"
                               value="{{ now()->format('Y-m-d') }}" required>
                        <small class="text-muted">The contract number will be based on this date's year.</small>
                    </div>

                    {{-- Project Linking Options --}}
                    <div class="mb-4">
                        <label class="form-label">Project Linking</label>
                        <div class="border rounded p-3">
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="radio" name="project_action" id="projectNone"
                                       value="none" {{ $estimate->project_id ? '' : 'checked' }}>
                                <label class="form-check-label" for="projectNone">
                                    Don't link to any project
                                </label>
                            </div>

                            @if($estimate->project_id)
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="radio" name="project_action" id="projectLinked"
                                           value="link_existing" checked>
                                    <label class="form-check-label" for="projectLinked">
                                        Link to current project: <strong>{{ $estimate->project->name }}</strong>
                                    </label>
                                    <input type="hidden" name="linked_project_id" value="{{ $estimate->project_id }}">
                                </div>
                            @endif

                            <div class="form-check mb-2">
                                <input class="form-check-input" type="radio" name="project_action" id="projectExisting"
                                       value="link_existing" {{ $estimate->project_id ? '' : '' }}>
                                <label class="form-check-label" for="projectExisting">
                                    Link to existing project
                                </label>
                            </div>

                            <div class="ms-4 mb-3" id="existingProjectSection" style="display: none;">
                                <select class="form-select" name="project_id" id="existingProjectSelect">
                                    <option value="">Select a project...</option>
                                    @foreach(\Modules\Project\Models\Project::active()->orderBy('name')->get() as $project)
                                        <option value="{{ $project->id }}"
                                            {{ $estimate->project_id == $project->id ? 'selected' : '' }}>
                                            {{ $project->code }} - {{ $project->name }}
                                            @if($project->customer_id == $estimate->customer_id)
                                                (Same customer)
                                            @endif
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="project_action" id="projectNew"
                                       value="create_new">
                                <label class="form-check-label" for="projectNew">
                                    Create new project
                                </label>
                            </div>

                            <div class="ms-4 mt-2" id="newProjectSection" style="display: none;">
                                <div class="row g-2">
                                    <div class="col-md-4">
                                        <label class="form-label small">Project Code</label>
                                        <input type="text" class="form-control form-control-sm" name="project_code"
                                               placeholder="e.g., PRJ2026001">
                                        <small class="text-muted">Leave empty to auto-generate</small>
                                    </div>
                                    <div class="col-md-8">
                                        <label class="form-label small">Project Name</label>
                                        <input type="text" class="form-control form-control-sm" name="project_name"
                                               value="{{ $estimate->title }}" placeholder="Project name">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Revenue Allocation (shown when project is selected) --}}
                    <div class="mb-4" id="allocationSection" style="display: none;">
                        <label class="form-label">Revenue Allocation to Project</label>
                        <div class="border rounded p-3">
                            <div class="row align-items-center">
                                <div class="col-md-4">
                                    <select class="form-select" name="allocation_type" id="allocationType">
                                        <option value="percentage">Percentage</option>
                                        <option value="amount">Fixed Amount</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <div class="input-group">
                                        <input type="number" class="form-control" name="allocation_value"
                                               id="allocationValue" value="100" min="0" step="0.01">
                                        <span class="input-group-text" id="allocationSuffix">%</span>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <small class="text-muted" id="allocationPreview">
                                        = EGP {{ number_format($estimate->total, 2) }}
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Sync Option --}}
                    <div class="mb-3" id="syncOption" style="display: none;">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="sync_to_project"
                                   id="syncToProject" value="1" checked>
                            <label class="form-check-label" for="syncToProject">
                                <strong>Sync to Project Revenues</strong>
                                <small class="text-muted d-block">
                                    Automatically create project revenue entries from contract payments.
                                </small>
                            </label>
                        </div>
                    </div>

                    {{-- Payment Preview --}}
                    <div class="mb-3">
                        <label class="form-label">Payment Milestones (from estimate items)</label>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>#</th>
                                        <th>Description</th>
                                        <th class="text-end">Amount</th>
                                        <th>Due Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($estimate->items as $index => $item)
                                        <tr>
                                            <td>{{ $index + 1 }}</td>
                                            <td>{{ Str::limit($item->description, 40) }}</td>
                                            <td class="text-end">EGP {{ number_format($item->amount, 2) }}</td>
                                            <td>{{ now()->addDays(30 * ($index + 1))->format('M d, Y') }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <small class="text-muted">Payment due dates can be adjusted after contract creation.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="ti ti-transform me-1"></i>Convert to Contract
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('page-script')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const projectActions = document.querySelectorAll('input[name="project_action"]');
    const existingProjectSection = document.getElementById('existingProjectSection');
    const newProjectSection = document.getElementById('newProjectSection');
    const allocationSection = document.getElementById('allocationSection');
    const syncOption = document.getElementById('syncOption');
    const allocationType = document.getElementById('allocationType');
    const allocationValue = document.getElementById('allocationValue');
    const allocationSuffix = document.getElementById('allocationSuffix');
    const allocationPreview = document.getElementById('allocationPreview');
    const estimateTotal = {{ $estimate->total }};

    function updateProjectSections() {
        const selectedAction = document.querySelector('input[name="project_action"]:checked').value;

        existingProjectSection.style.display = selectedAction === 'link_existing' ? 'block' : 'none';
        newProjectSection.style.display = selectedAction === 'create_new' ? 'block' : 'none';

        const showProjectOptions = selectedAction !== 'none';
        allocationSection.style.display = showProjectOptions ? 'block' : 'none';
        syncOption.style.display = showProjectOptions ? 'block' : 'none';
    }

    function updateAllocationPreview() {
        const type = allocationType.value;
        const value = parseFloat(allocationValue.value) || 0;

        if (type === 'percentage') {
            allocationSuffix.textContent = '%';
            const amount = (estimateTotal * value / 100);
            allocationPreview.textContent = '= EGP ' + amount.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        } else {
            allocationSuffix.textContent = 'EGP';
            const percentage = estimateTotal > 0 ? (value / estimateTotal * 100) : 0;
            allocationPreview.textContent = '= ' + percentage.toFixed(1) + '% of total';
        }
    }

    projectActions.forEach(radio => {
        radio.addEventListener('change', updateProjectSections);
    });

    allocationType.addEventListener('change', function() {
        if (this.value === 'percentage') {
            allocationValue.value = '100';
            allocationValue.max = '100';
        } else {
            allocationValue.value = estimateTotal.toFixed(2);
            allocationValue.removeAttribute('max');
        }
        updateAllocationPreview();
    });

    allocationValue.addEventListener('input', updateAllocationPreview);

    // Initial setup
    updateProjectSections();
    updateAllocationPreview();
});
</script>
@endpush

@extends('layouts/layoutMaster')

@section('title', 'Edit Expense Schedule')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-0">Edit Expense Schedule</h5>
                    <small class="text-muted">Update {{ $expenseSchedule->name }}</small>
                </div>
                <a href="{{ route('accounting.expenses.show', $expenseSchedule) }}" class="btn btn-outline-secondary">
                    <i class="ti ti-arrow-left me-1"></i>Back to Details
                </a>
            </div>

            <div class="card-body">
                <form action="{{ route('accounting.expenses.update', $expenseSchedule) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="row">
                        <!-- Form fields similar to create but pre-filled -->
                        <div class="col-lg-8">
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h6 class="card-title mb-0">Basic Information</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row g-3">
                                        <div class="col-12">
                                            <label for="name" class="form-label">Name <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control @error('name') is-invalid @enderror"
                                                   id="name" name="name" value="{{ old('name', $expenseSchedule->name) }}">
                                            @error('name')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="col-12">
                                            <label for="category_id" class="form-label">Category <span class="text-danger">*</span></label>
                                            <select class="form-select @error('category_id') is-invalid @enderror"
                                                    id="category_id" name="category_id">
                                                @foreach($categories as $category)
                                                    <option value="{{ $category->id }}"
                                                            {{ old('category_id', $expenseSchedule->category_id) == $category->id ? 'selected' : '' }}>
                                                        {{ str_repeat('│  ', $category->tree_depth) }}{{ $category->tree_depth > 0 ? '├─ ' : '' }}{{ $category->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error('category_id')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="col-12">
                                            <label for="description" class="form-label">Description</label>
                                            <textarea class="form-control @error('description') is-invalid @enderror"
                                                      id="description" name="description" rows="3">{{ old('description', $expenseSchedule->description) }}</textarea>
                                            @error('description')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Amount & Frequency -->
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h6 class="card-title mb-0">Amount & Frequency</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label for="amount" class="form-label">Amount <span class="text-danger">*</span></label>
                                            <div class="input-group">
                                                <span class="input-group-text">EGP</span>
                                                <input type="number" class="form-control @error('amount') is-invalid @enderror"
                                                       id="amount" name="amount" value="{{ old('amount', $expenseSchedule->amount) }}"
                                                       step="0.01" min="0" max="999999.99">
                                                @error('amount')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>

                                        <div class="col-md-6">
                                            <label for="frequency_type" class="form-label">Frequency Type <span class="text-danger">*</span></label>
                                            <select class="form-select @error('frequency_type') is-invalid @enderror"
                                                    id="frequency_type" name="frequency_type">
                                                <option value="weekly" {{ old('frequency_type', $expenseSchedule->frequency_type) === 'weekly' ? 'selected' : '' }}>Weekly</option>
                                                <option value="bi-weekly" {{ old('frequency_type', $expenseSchedule->frequency_type) === 'bi-weekly' ? 'selected' : '' }}>Bi-weekly</option>
                                                <option value="monthly" {{ old('frequency_type', $expenseSchedule->frequency_type) === 'monthly' ? 'selected' : '' }}>Monthly</option>
                                                <option value="quarterly" {{ old('frequency_type', $expenseSchedule->frequency_type) === 'quarterly' ? 'selected' : '' }}>Quarterly</option>
                                                <option value="yearly" {{ old('frequency_type', $expenseSchedule->frequency_type) === 'yearly' ? 'selected' : '' }}>Yearly</option>
                                            </select>
                                            @error('frequency_type')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="col-md-6">
                                            <label for="frequency_value" class="form-label">Frequency Interval <span class="text-danger">*</span></label>
                                            <input type="number" class="form-control @error('frequency_value') is-invalid @enderror"
                                                   id="frequency_value" name="frequency_value"
                                                   value="{{ old('frequency_value', $expenseSchedule->frequency_value) }}"
                                                   min="1" max="100">
                                            @error('frequency_value')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="col-md-6">
                                            <label class="form-label">Monthly Equivalent</label>
                                            <div class="input-group">
                                                <span class="input-group-text">EGP</span>
                                                <input type="text" class="form-control bg-light" id="monthlyEquivalent" readonly>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Scheduling Options -->
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h6 class="card-title mb-0">Scheduling Options</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label for="start_date" class="form-label">Start Date <span class="text-danger">*</span></label>
                                            <input type="date" class="form-control @error('start_date') is-invalid @enderror"
                                                   id="start_date" name="start_date"
                                                   value="{{ old('start_date', $expenseSchedule->start_date->format('Y-m-d')) }}">
                                            @error('start_date')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="col-md-6">
                                            <label for="end_date" class="form-label">End Date (Optional)</label>
                                            <input type="date" class="form-control @error('end_date') is-invalid @enderror"
                                                   id="end_date" name="end_date"
                                                   value="{{ old('end_date', $expenseSchedule->end_date ? $expenseSchedule->end_date->format('Y-m-d') : '') }}">
                                            @error('end_date')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="col-12">
                                            <div class="form-check">
                                                <input type="checkbox" class="form-check-input"
                                                       id="skip_weekends" name="skip_weekends" value="1"
                                                       {{ old('skip_weekends', $expenseSchedule->skip_weekends) ? 'checked' : '' }}>
                                                <label class="form-check-label" for="skip_weekends">Skip weekends</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Payment Information (for paid expenses) -->
                            @if($expenseSchedule->payment_status === 'paid' || $expenseSchedule->expense_type === 'one_time')
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h6 class="card-title mb-0">
                                        <i class="ti ti-wallet me-2"></i>Payment Information
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label for="paid_from_account_id" class="form-label">Paid From Account</label>
                                            <select class="form-select @error('paid_from_account_id') is-invalid @enderror"
                                                    id="paid_from_account_id" name="paid_from_account_id">
                                                <option value="">-- Select Account --</option>
                                                @foreach($accounts as $account)
                                                    <option value="{{ $account->id }}"
                                                            {{ old('paid_from_account_id', $expenseSchedule->paid_from_account_id) == $account->id ? 'selected' : '' }}>
                                                        {{ $account->name }} ({{ $account->type_display }})
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error('paid_from_account_id')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="col-md-6">
                                            <label for="paid_date" class="form-label">Payment Date</label>
                                            <input type="date" class="form-control @error('paid_date') is-invalid @enderror"
                                                   id="paid_date" name="paid_date"
                                                   value="{{ old('paid_date', $expenseSchedule->paid_date?->format('Y-m-d')) }}">
                                            @error('paid_date')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="col-md-6">
                                            <label for="paid_amount" class="form-label">Paid Amount</label>
                                            <div class="input-group">
                                                <span class="input-group-text">EGP</span>
                                                <input type="number" class="form-control @error('paid_amount') is-invalid @enderror"
                                                       id="paid_amount" name="paid_amount"
                                                       value="{{ old('paid_amount', $expenseSchedule->paid_amount) }}"
                                                       step="0.01" min="0" max="999999.99">
                                                @error('paid_amount')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>

                                        <div class="col-md-6">
                                            <label for="payment_notes" class="form-label">Payment Notes</label>
                                            <input type="text" class="form-control @error('payment_notes') is-invalid @enderror"
                                                   id="payment_notes" name="payment_notes"
                                                   value="{{ old('payment_notes', $expenseSchedule->payment_notes) }}"
                                                   placeholder="Optional notes about payment">
                                            @error('payment_notes')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @endif
                        </div>

                        <!-- Current Status Panel -->
                        <div class="col-lg-4">
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h6 class="card-title mb-0">Current Status</h6>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label class="form-label">Status</label>
                                        <div>
                                            <span class="badge bg-{{ $expenseSchedule->is_active ? 'success' : 'secondary' }}">
                                                {{ $expenseSchedule->is_active ? 'Active' : 'Inactive' }}
                                            </span>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Current Monthly Equivalent</label>
                                        <div class="h5 text-warning">{{ number_format($expenseSchedule->monthly_equivalent_amount, 2) }} EGP</div>
                                    </div>

                                    @if($expenseSchedule->next_payment_date)
                                        <div class="mb-3">
                                            <label class="form-label">Next Payment</label>
                                            <div>{{ $expenseSchedule->next_payment_date->format('M j, Y') }}</div>
                                            <small class="text-muted">{{ $expenseSchedule->next_payment_date->diffForHumans() }}</small>
                                        </div>
                                    @endif
                                </div>
                            </div>

                            <!-- Attachments -->
                            <div class="card">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h6 class="card-title mb-0">
                                        <i class="ti ti-paperclip me-2"></i>Attachments
                                    </h6>
                                    <span class="badge bg-label-primary" id="attachment-count">{{ $expenseSchedule->attachments->count() }}</span>
                                </div>
                                <div class="card-body">
                                    <!-- Upload Zone -->
                                    <div class="mb-3">
                                        <div class="dropzone-wrapper border border-dashed rounded p-3 text-center" id="attachment-dropzone">
                                            <input type="file" id="attachment-input" multiple accept=".pdf,.jpg,.jpeg,.png,.gif,.doc,.docx,.xls,.xlsx" class="d-none">
                                            <i class="ti ti-cloud-upload ti-lg text-muted mb-2"></i>
                                            <p class="mb-0 text-muted small">Drop files here or <a href="#" onclick="document.getElementById('attachment-input').click(); return false;">browse</a></p>
                                            <p class="mb-0 text-muted small">PDF, Images, Word, Excel (max 10MB)</p>
                                        </div>
                                    </div>

                                    <!-- Existing Attachments -->
                                    <div id="attachments-list">
                                        @forelse($expenseSchedule->attachments as $attachment)
                                            <div class="attachment-item d-flex align-items-center justify-content-between p-2 border rounded mb-2" data-id="{{ $attachment->id }}">
                                                <div class="d-flex align-items-center">
                                                    <i class="ti {{ $attachment->icon_class }} ti-md me-2 text-primary"></i>
                                                    <div>
                                                        <a href="{{ route('accounting.expenses.attachments.download', [$expenseSchedule, $attachment]) }}" class="text-body fw-medium" target="_blank">
                                                            {{ \Illuminate\Support\Str::limit($attachment->original_name, 25) }}
                                                        </a>
                                                        <br><small class="text-muted">{{ $attachment->human_file_size }}</small>
                                                    </div>
                                                </div>
                                                <button type="button" class="btn btn-sm btn-icon btn-text-danger delete-attachment" data-id="{{ $attachment->id }}">
                                                    <i class="ti ti-trash"></i>
                                                </button>
                                            </div>
                                        @empty
                                            <p class="text-muted small mb-0" id="no-attachments-msg">No attachments yet</p>
                                        @endforelse
                                    </div>

                                    <!-- Upload Progress -->
                                    <div id="upload-progress" class="d-none">
                                        <div class="progress">
                                            <div class="progress-bar" role="progressbar" style="width: 0%"></div>
                                        </div>
                                        <small class="text-muted">Uploading...</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-footer d-flex justify-content-between">
                                    <a href="{{ route('accounting.expenses.show', $expenseSchedule) }}" class="btn btn-outline-secondary">
                                        <i class="ti ti-x me-1"></i>Cancel
                                    </a>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="ti ti-device-floppy me-1"></i>Save Changes
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const amountInput = document.getElementById('amount');
    const frequencyTypeSelect = document.getElementById('frequency_type');
    const frequencyValueInput = document.getElementById('frequency_value');
    const monthlyEquivalentInput = document.getElementById('monthlyEquivalent');

    function calculateMonthlyEquivalent() {
        const amount = parseFloat(amountInput.value) || 0;
        const frequencyType = frequencyTypeSelect.value;
        const frequencyValue = parseInt(frequencyValueInput.value) || 1;

        if (amount > 0 && frequencyType) {
            let multiplier;
            switch(frequencyType) {
                case 'weekly':
                    multiplier = 4.33 / frequencyValue;
                    break;
                case 'bi-weekly':
                    multiplier = 2.17 / frequencyValue;
                    break;
                case 'monthly':
                    multiplier = 1 / frequencyValue;
                    break;
                case 'quarterly':
                    multiplier = 1 / (frequencyValue * 3);
                    break;
                case 'yearly':
                    multiplier = 1 / (frequencyValue * 12);
                    break;
                default:
                    multiplier = 1;
            }

            const monthlyAmount = (amount * multiplier).toFixed(2);
            monthlyEquivalentInput.value = monthlyAmount;
        } else {
            monthlyEquivalentInput.value = '0.00';
        }
    }

    [amountInput, frequencyTypeSelect, frequencyValueInput].forEach(element => {
        element.addEventListener('input', calculateMonthlyEquivalent);
        element.addEventListener('change', calculateMonthlyEquivalent);
    });

    // Initial calculation
    calculateMonthlyEquivalent();

    // Attachment handling
    const expenseId = {{ $expenseSchedule->id }};
    const attachmentInput = document.getElementById('attachment-input');
    const dropzone = document.getElementById('attachment-dropzone');
    const attachmentsList = document.getElementById('attachments-list');
    const attachmentCount = document.getElementById('attachment-count');
    const uploadProgress = document.getElementById('upload-progress');
    const noAttachmentsMsg = document.getElementById('no-attachments-msg');

    // File input change
    attachmentInput.addEventListener('change', function(e) {
        if (e.target.files.length > 0) {
            uploadFiles(e.target.files);
        }
    });

    // Drag and drop
    dropzone.addEventListener('dragover', function(e) {
        e.preventDefault();
        dropzone.classList.add('bg-light');
    });

    dropzone.addEventListener('dragleave', function(e) {
        e.preventDefault();
        dropzone.classList.remove('bg-light');
    });

    dropzone.addEventListener('drop', function(e) {
        e.preventDefault();
        dropzone.classList.remove('bg-light');
        if (e.dataTransfer.files.length > 0) {
            uploadFiles(e.dataTransfer.files);
        }
    });

    // Upload files
    function uploadFiles(files) {
        const formData = new FormData();
        for (let i = 0; i < files.length; i++) {
            formData.append('attachments[]', files[i]);
        }

        uploadProgress.classList.remove('d-none');

        fetch(`/accounting/expenses/${expenseId}/attachments`, {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        })
        .then(response => response.json())
        .then(data => {
            uploadProgress.classList.add('d-none');
            if (data.success) {
                if (noAttachmentsMsg) {
                    noAttachmentsMsg.remove();
                }
                data.attachments.forEach(att => {
                    addAttachmentToList(att);
                });
                updateAttachmentCount(1);
                showToast('success', data.message);
            } else {
                showToast('error', data.message || 'Upload failed');
            }
        })
        .catch(error => {
            uploadProgress.classList.add('d-none');
            showToast('error', 'Upload failed: ' + error.message);
        });

        attachmentInput.value = '';
    }

    // Add attachment to list
    function addAttachmentToList(att) {
        const html = `
            <div class="attachment-item d-flex align-items-center justify-content-between p-2 border rounded mb-2" data-id="${att.id}">
                <div class="d-flex align-items-center">
                    <i class="ti ${att.icon} ti-md me-2 text-primary"></i>
                    <div>
                        <a href="${att.url}" class="text-body fw-medium" target="_blank">${att.name.substring(0, 25)}</a>
                        <br><small class="text-muted">${att.size}</small>
                    </div>
                </div>
                <button type="button" class="btn btn-sm btn-icon btn-text-danger delete-attachment" data-id="${att.id}">
                    <i class="ti ti-trash"></i>
                </button>
            </div>
        `;
        attachmentsList.insertAdjacentHTML('beforeend', html);
    }

    // Delete attachment
    attachmentsList.addEventListener('click', function(e) {
        const deleteBtn = e.target.closest('.delete-attachment');
        if (deleteBtn) {
            const attachmentId = deleteBtn.dataset.id;
            if (confirm('Are you sure you want to delete this attachment?')) {
                fetch(`/accounting/expenses/${expenseId}/attachments/${attachmentId}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Content-Type': 'application/json'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        deleteBtn.closest('.attachment-item').remove();
                        updateAttachmentCount(-1);
                        showToast('success', data.message);
                    } else {
                        showToast('error', data.message || 'Delete failed');
                    }
                })
                .catch(error => {
                    showToast('error', 'Delete failed: ' + error.message);
                });
            }
        }
    });

    // Update attachment count
    function updateAttachmentCount(delta) {
        const current = parseInt(attachmentCount.textContent) || 0;
        attachmentCount.textContent = current + delta;
    }

    // Toast notification
    function showToast(type, message) {
        if (typeof Toastify !== 'undefined') {
            Toastify({
                text: message,
                duration: 3000,
                gravity: 'top',
                position: 'right',
                backgroundColor: type === 'success' ? '#28a745' : '#dc3545'
            }).showToast();
        } else {
            alert(message);
        }
    }
});
</script>
@endsection
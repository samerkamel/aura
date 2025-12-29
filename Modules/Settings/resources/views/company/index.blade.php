@extends('layouts/layoutMaster')

@section('title', 'Company Settings')

@section('content')
<div class="row">
    <div class="col-12">
        @if (session('success'))
            <div class="alert alert-success alert-dismissible mb-4">
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                {{ session('success') }}
            </div>
        @endif

        <form action="{{ route('settings.company.update') }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <div class="row">
                <!-- Main Content -->
                <div class="col-lg-8">
                    <!-- Company Information -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="ti tabler-building me-2"></i>Company Information
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="company_name" class="form-label">Company Name (English) <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('company_name') is-invalid @enderror"
                                           id="company_name" name="company_name"
                                           value="{{ old('company_name', $settings->company_name) }}"
                                           placeholder="Enter company name">
                                    @error('company_name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-6">
                                    <label for="company_name_ar" class="form-label">Company Name (Arabic)</label>
                                    <input type="text" class="form-control @error('company_name_ar') is-invalid @enderror"
                                           id="company_name_ar" name="company_name_ar"
                                           value="{{ old('company_name_ar', $settings->company_name_ar) }}"
                                           placeholder="Enter company name in Arabic" dir="rtl">
                                    @error('company_name_ar')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-6">
                                    <label for="address" class="form-label">Address (English)</label>
                                    <textarea class="form-control @error('address') is-invalid @enderror"
                                              id="address" name="address" rows="3"
                                              placeholder="Enter company address">{{ old('address', $settings->address) }}</textarea>
                                    @error('address')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-6">
                                    <label for="address_ar" class="form-label">Address (Arabic)</label>
                                    <textarea class="form-control @error('address_ar') is-invalid @enderror"
                                              id="address_ar" name="address_ar" rows="3"
                                              placeholder="Enter company address in Arabic" dir="rtl">{{ old('address_ar', $settings->address_ar) }}</textarea>
                                    @error('address_ar')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-4">
                                    <label for="phone" class="form-label">Phone</label>
                                    <input type="text" class="form-control @error('phone') is-invalid @enderror"
                                           id="phone" name="phone"
                                           value="{{ old('phone', $settings->phone) }}"
                                           placeholder="+20 123 456 7890">
                                    @error('phone')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-4">
                                    <label for="email" class="form-label">Email</label>
                                    <input type="email" class="form-control @error('email') is-invalid @enderror"
                                           id="email" name="email"
                                           value="{{ old('email', $settings->email) }}"
                                           placeholder="info@company.com">
                                    @error('email')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-4">
                                    <label for="website" class="form-label">Website</label>
                                    <input type="url" class="form-control @error('website') is-invalid @enderror"
                                           id="website" name="website"
                                           value="{{ old('website', $settings->website) }}"
                                           placeholder="https://www.company.com">
                                    @error('website')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tax & Legal Information -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="ti tabler-file-certificate me-2"></i>Tax & Legal Information
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label for="tax_id" class="form-label">Tax Registration Number</label>
                                    <input type="text" class="form-control @error('tax_id') is-invalid @enderror"
                                           id="tax_id" name="tax_id"
                                           value="{{ old('tax_id', $settings->tax_id) }}"
                                           placeholder="Tax ID">
                                    @error('tax_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-4">
                                    <label for="commercial_register" class="form-label">Commercial Register</label>
                                    <input type="text" class="form-control @error('commercial_register') is-invalid @enderror"
                                           id="commercial_register" name="commercial_register"
                                           value="{{ old('commercial_register', $settings->commercial_register) }}"
                                           placeholder="CR Number">
                                    @error('commercial_register')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-2">
                                    <label for="default_vat_rate" class="form-label">Default VAT % <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <input type="number" class="form-control @error('default_vat_rate') is-invalid @enderror"
                                               id="default_vat_rate" name="default_vat_rate"
                                               value="{{ old('default_vat_rate', $settings->default_vat_rate) }}"
                                               step="0.01" min="0" max="100">
                                        <span class="input-group-text">%</span>
                                        @error('default_vat_rate')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <div class="col-md-2">
                                    <label for="currency" class="form-label">Currency <span class="text-danger">*</span></label>
                                    <select class="form-select @error('currency') is-invalid @enderror"
                                            id="currency" name="currency">
                                        <option value="EGP" {{ old('currency', $settings->currency) === 'EGP' ? 'selected' : '' }}>EGP</option>
                                        <option value="USD" {{ old('currency', $settings->currency) === 'USD' ? 'selected' : '' }}>USD</option>
                                        <option value="EUR" {{ old('currency', $settings->currency) === 'EUR' ? 'selected' : '' }}>EUR</option>
                                        <option value="GBP" {{ old('currency', $settings->currency) === 'GBP' ? 'selected' : '' }}>GBP</option>
                                        <option value="SAR" {{ old('currency', $settings->currency) === 'SAR' ? 'selected' : '' }}>SAR</option>
                                        <option value="AED" {{ old('currency', $settings->currency) === 'AED' ? 'selected' : '' }}>AED</option>
                                    </select>
                                    @error('currency')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Fiscal & Cycle Settings -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="ti tabler-calendar-stats me-2"></i>Fiscal & Payroll Cycle Settings
                            </h5>
                            <small class="text-muted">Configure your fiscal year and payroll cycle periods</small>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="cycle_start_day" class="form-label">Cycle Start Day <span class="text-danger">*</span></label>
                                    <select class="form-select @error('cycle_start_day') is-invalid @enderror"
                                            id="cycle_start_day" name="cycle_start_day">
                                        @for($day = 1; $day <= 28; $day++)
                                            <option value="{{ $day }}" {{ old('cycle_start_day', $settings->cycle_start_day ?? 1) == $day ? 'selected' : '' }}>
                                                {{ $day }}{{ $day == 1 ? 'st' : ($day == 2 ? 'nd' : ($day == 3 ? 'rd' : 'th')) }} of each month
                                            </option>
                                        @endfor
                                    </select>
                                    @error('cycle_start_day')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="text-muted">Day when payroll/fiscal periods begin (e.g., 26 means 26th to 25th)</small>
                                </div>

                                <div class="col-md-6">
                                    <label for="fiscal_year_start_month" class="form-label">Fiscal Year Start Month <span class="text-danger">*</span></label>
                                    <select class="form-select @error('fiscal_year_start_month') is-invalid @enderror"
                                            id="fiscal_year_start_month" name="fiscal_year_start_month">
                                        @php
                                            $months = ['January', 'February', 'March', 'April', 'May', 'June',
                                                       'July', 'August', 'September', 'October', 'November', 'December'];
                                        @endphp
                                        @foreach($months as $index => $month)
                                            <option value="{{ $index + 1 }}" {{ old('fiscal_year_start_month', $settings->fiscal_year_start_month ?? 1) == ($index + 1) ? 'selected' : '' }}>
                                                {{ $month }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('fiscal_year_start_month')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="text-muted">Month when your fiscal year begins</small>
                                </div>

                                <div class="col-12">
                                    <div class="alert alert-info mb-0">
                                        <i class="ti tabler-info-circle me-2"></i>
                                        <strong>Current Period:</strong> {{ $settings->getPeriodLabel() ?? 'Not configured' }}<br>
                                        <strong>Fiscal Year:</strong> {{ $settings->getFiscalYearLabel() ?? 'Not configured' }}
                                        ({{ $settings->getFiscalYearStart()?->format('M d, Y') }} - {{ $settings->getFiscalYearEnd()?->format('M d, Y') }})
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Bank Details -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="ti tabler-building-bank me-2"></i>Bank Details
                            </h5>
                            <small class="text-muted">These details will appear on estimates and invoices</small>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="bank_name" class="form-label">Bank Name</label>
                                    <input type="text" class="form-control @error('bank_name') is-invalid @enderror"
                                           id="bank_name" name="bank_name"
                                           value="{{ old('bank_name', $settings->bank_details['bank_name'] ?? '') }}"
                                           placeholder="e.g., Commercial International Bank">
                                    @error('bank_name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-6">
                                    <label for="account_name" class="form-label">Account Name</label>
                                    <input type="text" class="form-control @error('account_name') is-invalid @enderror"
                                           id="account_name" name="account_name"
                                           value="{{ old('account_name', $settings->bank_details['account_name'] ?? '') }}"
                                           placeholder="Account holder name">
                                    @error('account_name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-4">
                                    <label for="account_number" class="form-label">Account Number</label>
                                    <input type="text" class="form-control @error('account_number') is-invalid @enderror"
                                           id="account_number" name="account_number"
                                           value="{{ old('account_number', $settings->bank_details['account_number'] ?? '') }}"
                                           placeholder="Account number">
                                    @error('account_number')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-5">
                                    <label for="iban" class="form-label">IBAN</label>
                                    <input type="text" class="form-control @error('iban') is-invalid @enderror"
                                           id="iban" name="iban"
                                           value="{{ old('iban', $settings->bank_details['iban'] ?? '') }}"
                                           placeholder="IBAN number">
                                    @error('iban')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-3">
                                    <label for="swift" class="form-label">SWIFT/BIC</label>
                                    <input type="text" class="form-control @error('swift') is-invalid @enderror"
                                           id="swift" name="swift"
                                           value="{{ old('swift', $settings->bank_details['swift'] ?? '') }}"
                                           placeholder="SWIFT code">
                                    @error('swift')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="col-lg-4">
                    <!-- Dashboard Logo -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="ti tabler-layout-dashboard me-2"></i>Dashboard Logo
                            </h5>
                            <small class="text-muted">Displayed in sidebar and navbar</small>
                        </div>
                        <div class="card-body text-center">
                            @if($settings->dashboard_logo_path)
                                <div class="mb-3">
                                    <img src="{{ $settings->dashboard_logo_url }}" alt="Dashboard Logo"
                                         class="img-fluid rounded" style="max-height: 100px;">
                                </div>
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="checkbox" id="remove_dashboard_logo" name="remove_dashboard_logo" value="1">
                                    <label class="form-check-label text-danger" for="remove_dashboard_logo">
                                        Remove current logo
                                    </label>
                                </div>
                            @else
                                <div class="mb-3">
                                    <div class="border rounded p-3 bg-light">
                                        <i class="ti tabler-photo-off text-muted" style="font-size: 2rem;"></i>
                                        <p class="text-muted mb-0 mt-2 small">No logo uploaded</p>
                                    </div>
                                </div>
                            @endif

                            <div class="mb-0">
                                <label for="dashboard_logo" class="form-label">Upload Dashboard Logo</label>
                                <input type="file" class="form-control form-control-sm @error('dashboard_logo') is-invalid @enderror"
                                       id="dashboard_logo" name="dashboard_logo" accept="image/jpeg,image/png,image/gif,image/svg+xml">
                                @error('dashboard_logo')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="text-muted d-block mt-1">
                                    Recommended: 150x40px
                                </small>
                            </div>
                        </div>
                    </div>

                    <!-- Document Logo -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="ti tabler-file-invoice me-2"></i>Document Logo
                            </h5>
                            <small class="text-muted">Used in estimates, invoices & PDFs</small>
                        </div>
                        <div class="card-body text-center">
                            @if($settings->logo_path)
                                <div class="mb-3">
                                    <img src="{{ $settings->logo_url }}" alt="Document Logo"
                                         class="img-fluid rounded" style="max-height: 120px;">
                                </div>
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="checkbox" id="remove_logo" name="remove_logo" value="1">
                                    <label class="form-check-label text-danger" for="remove_logo">
                                        Remove current logo
                                    </label>
                                </div>
                            @else
                                <div class="mb-3">
                                    <div class="border rounded p-3 bg-light">
                                        <i class="ti tabler-photo-off text-muted" style="font-size: 2rem;"></i>
                                        <p class="text-muted mb-0 mt-2 small">No logo uploaded</p>
                                    </div>
                                </div>
                            @endif

                            <div class="mb-0">
                                <label for="logo" class="form-label">Upload Document Logo</label>
                                <input type="file" class="form-control form-control-sm @error('logo') is-invalid @enderror"
                                       id="logo" name="logo" accept="image/jpeg,image/png,image/gif,image/svg+xml">
                                @error('logo')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="text-muted d-block mt-1">
                                    Max 2MB. Use transparent PNG for best results
                                </small>
                            </div>
                        </div>
                    </div>

                    <!-- Tips -->
                    <div class="card">
                        <div class="card-header">
                            <h6 class="card-title mb-0">
                                <i class="ti tabler-bulb me-2"></i>Tips
                            </h6>
                        </div>
                        <div class="card-body">
                            <ul class="mb-0 ps-3">
                                <li class="mb-2"><strong>Dashboard logo:</strong> Appears in the sidebar navigation</li>
                                <li class="mb-2"><strong>Document logo:</strong> Used on estimates, invoices, and PDFs</li>
                                <li class="mb-2">VAT rate is used as default for new estimates</li>
                                <li class="mb-2">Bank details appear on document footers</li>
                                <li class="mb-2"><strong>Cycle Start Day:</strong> All payroll periods and reports will use this day as the start of each month</li>
                                <li class="mb-0"><strong>Fiscal Year:</strong> Used for annual reports and income/expense tracking</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Form Actions -->
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-footer d-flex justify-content-end">
                            <button type="submit" class="btn btn-primary">
                                <i class="ti tabler-device-floppy me-1"></i>Save Settings
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

@endsection

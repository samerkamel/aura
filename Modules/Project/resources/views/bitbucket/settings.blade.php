@extends('layouts/layoutMaster')

@section('title', 'Bitbucket Settings')

@section('vendor-style')
@vite(['resources/assets/vendor/libs/toastr/toastr.scss'])
@endsection

@section('vendor-script')
@vite(['resources/assets/vendor/libs/toastr/toastr.js'])
@endsection

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('projects.index') }}">Projects</a></li>
            <li class="breadcrumb-item active">Bitbucket Settings</li>
        </ol>
    </nav>

    <div class="row">
        <!-- Settings Form -->
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <h5 class="mb-0">
                        <i class="ti ti-brand-bitbucket me-2"></i>Bitbucket Integration Settings
                    </h5>
                    @if($settings->isConfigured())
                        <span class="badge bg-{{ $connectionStatus['success'] ?? false ? 'success' : 'danger' }}">
                            {{ $connectionStatus['success'] ?? false ? 'Connected' : 'Not Connected' }}
                        </span>
                    @endif
                </div>
                <div class="card-body">
                    <form action="{{ route('projects.bitbucket.update-settings') }}" method="POST">
                        @csrf

                        <div class="mb-3">
                            <label class="form-label" for="workspace">Workspace <span class="text-danger">*</span></label>
                            <input type="text"
                                   class="form-control @error('workspace') is-invalid @enderror"
                                   id="workspace"
                                   name="workspace"
                                   value="{{ old('workspace', $settings->workspace) }}"
                                   placeholder="e.g., my-team">
                            <div class="form-text">Your Bitbucket workspace slug (found in your Bitbucket URL)</div>
                            @error('workspace')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="email">Email <span class="text-danger">*</span></label>
                            <input type="email"
                                   class="form-control @error('email') is-invalid @enderror"
                                   id="email"
                                   name="email"
                                   value="{{ old('email', $settings->email) }}"
                                   placeholder="your-email@example.com">
                            <div class="form-text">The email associated with your Bitbucket account</div>
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="api_token">API Token</label>
                            <div class="input-group">
                                <input type="password"
                                       class="form-control @error('api_token') is-invalid @enderror"
                                       id="api_token"
                                       name="api_token"
                                       placeholder="{{ $settings->api_token ? '••••••••••••' : 'Enter your Bitbucket API token' }}">
                                <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                    <i class="ti ti-eye"></i>
                                </button>
                            </div>
                            <div class="form-text">
                                Create an API token in Bitbucket: Workspace settings → API tokens → Create token
                                (needs Repository read scope)
                            </div>
                            @error('api_token')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <hr class="my-4">

                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="sync_enabled" name="sync_enabled" value="1"
                                       {{ old('sync_enabled', $settings->sync_enabled) ? 'checked' : '' }}>
                                <label class="form-check-label" for="sync_enabled">Enable Automatic Sync</label>
                            </div>
                            <div class="form-text">Automatically sync commits from linked repositories</div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="sync_frequency">Sync Frequency</label>
                            <select class="form-select @error('sync_frequency') is-invalid @enderror" id="sync_frequency" name="sync_frequency">
                                <option value="manual" {{ old('sync_frequency', $settings->sync_frequency) == 'manual' ? 'selected' : '' }}>Manual Only</option>
                                <option value="hourly" {{ old('sync_frequency', $settings->sync_frequency) == 'hourly' ? 'selected' : '' }}>Hourly</option>
                                <option value="daily" {{ old('sync_frequency', $settings->sync_frequency) == 'daily' ? 'selected' : '' }}>Daily</option>
                            </select>
                            @error('sync_frequency')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="ti ti-device-floppy me-1"></i>Save Settings
                            </button>
                            <button type="button" class="btn btn-outline-secondary" id="testConnection" {{ !$settings->isConfigured() ? 'disabled' : '' }}>
                                <i class="ti ti-plug me-1"></i>Test Connection
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Connection Status & Help -->
        <div class="col-lg-4">
            <!-- Connection Status -->
            @if($settings->isConfigured() && $connectionStatus)
                <div class="card mb-4">
                    <div class="card-header">
                        <h6 class="mb-0">
                            <i class="ti ti-plug me-2"></i>Connection Status
                        </h6>
                    </div>
                    <div class="card-body">
                        @if($connectionStatus['success'])
                            <div class="d-flex align-items-center mb-3">
                                <div class="avatar avatar-sm bg-label-success rounded-circle me-3">
                                    <span class="avatar-initial"><i class="ti ti-check"></i></span>
                                </div>
                                <div>
                                    <h6 class="mb-0">Connected</h6>
                                    <small class="text-muted">{{ $connectionStatus['user'] ?? 'Unknown User' }}</small>
                                </div>
                            </div>
                        @else
                            <div class="d-flex align-items-center mb-3">
                                <div class="avatar avatar-sm bg-label-danger rounded-circle me-3">
                                    <span class="avatar-initial"><i class="ti ti-x"></i></span>
                                </div>
                                <div>
                                    <h6 class="mb-0">Connection Failed</h6>
                                    <small class="text-danger">{{ $connectionStatus['message'] ?? 'Unknown error' }}</small>
                                </div>
                            </div>
                        @endif

                        @if($settings->last_sync_at)
                            <div class="d-flex align-items-center">
                                <i class="ti ti-clock me-2 text-muted"></i>
                                <small class="text-muted">Last sync: {{ $settings->last_sync_at->diffForHumans() }}</small>
                            </div>
                        @endif
                    </div>
                </div>
            @endif

            <!-- Help Card -->
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">
                        <i class="ti ti-help me-2"></i>How to Configure
                    </h6>
                </div>
                <div class="card-body">
                    <ol class="ps-3 mb-0">
                        <li class="mb-2">Log in to your Bitbucket account</li>
                        <li class="mb-2">Go to <strong>Workspace settings</strong> → <strong>API tokens</strong></li>
                        <li class="mb-2">Click <strong>Create token</strong></li>
                        <li class="mb-2">Give it a name (e.g., "AURA Integration")</li>
                        <li class="mb-2">Select scopes:
                            <ul class="mt-1">
                                <li>Repository: Read</li>
                            </ul>
                        </li>
                        <li class="mb-2">Copy the generated token</li>
                        <li>Paste it in the API Token field above</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <!-- Linked Projects -->
    @if($settings->isConfigured())
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5 class="mb-0">
                    <i class="ti ti-git-branch me-2"></i>Linked Projects
                </h5>
                <div class="d-flex gap-2">
                    <a href="{{ route('projects.bitbucket.link-projects') }}" class="btn btn-sm btn-outline-primary">
                        <i class="ti ti-link me-1"></i>Bulk Link Projects
                    </a>
                    <button type="button" class="btn btn-sm btn-primary" id="syncAllBtn">
                        <i class="ti ti-refresh me-1"></i>Sync All
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Project</th>
                                <th>Repository</th>
                                <th>Commits</th>
                                <th>Last Sync</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="linkedProjectsTable">
                            <!-- Will be populated via AJAX or you can pass linked projects -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif
</div>
@endsection

@section('page-script')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Toggle password visibility
    const togglePassword = document.getElementById('togglePassword');
    const passwordInput = document.getElementById('api_token');

    if (togglePassword && passwordInput) {
        togglePassword.addEventListener('click', function() {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            this.querySelector('i').classList.toggle('ti-eye');
            this.querySelector('i').classList.toggle('ti-eye-off');
        });
    }

    // Test Connection
    const testBtn = document.getElementById('testConnection');
    if (testBtn) {
        testBtn.addEventListener('click', function() {
            testBtn.disabled = true;
            testBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Testing...';

            fetch('{{ route("projects.bitbucket.test-connection") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    toastr.success('Connected successfully as ' + (data.user || 'Unknown'));
                } else {
                    toastr.error(data.message || 'Connection failed');
                }
            })
            .catch(error => {
                toastr.error('An error occurred while testing connection');
            })
            .finally(() => {
                testBtn.disabled = false;
                testBtn.innerHTML = '<i class="ti ti-plug me-1"></i>Test Connection';
            });
        });
    }

    // Sync All
    const syncAllBtn = document.getElementById('syncAllBtn');
    if (syncAllBtn) {
        syncAllBtn.addEventListener('click', function() {
            syncAllBtn.disabled = true;
            syncAllBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Syncing...';

            fetch('{{ route("projects.bitbucket.sync-all") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success !== undefined) {
                    toastr.success(`Synced ${data.success} projects successfully, ${data.failed} failed`);
                } else {
                    toastr.info('Sync completed');
                }
                // Reload to show updated data
                setTimeout(() => location.reload(), 1500);
            })
            .catch(error => {
                toastr.error('An error occurred during sync');
            })
            .finally(() => {
                syncAllBtn.disabled = false;
                syncAllBtn.innerHTML = '<i class="ti ti-refresh me-1"></i>Sync All';
            });
        });
    }
});
</script>
@endsection

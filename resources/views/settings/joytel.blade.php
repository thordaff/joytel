@extends('layouts.app')

@section('title', 'JoyTel Settings - Configuration')
@section('page-title', 'JoyTel API Configuration')

@section('content')
<div class="row">
    <!-- Credential Status -->
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-key"></i> Credential Status</h5>
            </div>
            <div class="card-body">
                <!-- Warehouse API Status -->
                <div class="mb-4">
                    <h6 class="fw-bold">
                        <i class="bi bi-building"></i> Warehouse API (System 1)
                        @if($credentialStatus['warehouse']['valid'])
                            <span class="badge bg-success ms-2">Connected</span>
                        @else
                            <span class="badge bg-danger ms-2">{{ ucwords(str_replace('_', ' ', $credentialStatus['warehouse']['status'])) }}</span>
                        @endif
                    </h6>
                    
                    <div class="alert alert-{{ $credentialStatus['warehouse']['valid'] ? 'success' : 'warning' }}" role="alert">
                        <div class="d-flex align-items-start">
                            <i class="bi bi-{{ $credentialStatus['warehouse']['valid'] ? 'check-circle' : 'exclamation-triangle' }} me-2 mt-1"></i>
                            <div>
                                <div class="fw-bold">{{ $credentialStatus['warehouse']['message'] }}</div>
                                @if(!$credentialStatus['warehouse']['valid'] && isset($credentialStatus['warehouse']['action_required']))
                                    <small>{{ $credentialStatus['warehouse']['action_required'] }}</small>
                                @endif
                                @if($credentialStatus['warehouse']['valid'] && isset($credentialStatus['warehouse']['last_tested']))
                                    <small>Last tested: {{ $credentialStatus['warehouse']['last_tested'] }}</small>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <label class="form-label">Customer Code</label>
                            <div class="input-group">
                                <input type="text" class="form-control" value="{{ $currentConfig['customer_code'] ?: 'Not configured' }}" readonly>
                                <span class="input-group-text">
                                    <i class="bi bi-{{ $currentConfig['customer_code'] ? 'check-circle text-success' : 'x-circle text-danger' }}"></i>
                                </span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Customer Auth</label>
                            <div class="input-group">
                                <input type="password" class="form-control" value="{{ $currentConfig['customer_auth'] ?: 'Not configured' }}" readonly>
                                <span class="input-group-text">
                                    <i class="bi bi-{{ $currentConfig['customer_auth'] ? 'check-circle text-success' : 'x-circle text-danger' }}"></i>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- RSP API Status -->
                <div class="mb-4">
                    <h6 class="fw-bold">
                        <i class="bi bi-sim"></i> RSP API (System 2)
                        @if($credentialStatus['rsp']['valid'])
                            <span class="badge bg-success ms-2">Connected</span>
                        @else
                            <span class="badge bg-danger ms-2">{{ ucwords(str_replace('_', ' ', $credentialStatus['rsp']['status'])) }}</span>
                        @endif
                    </h6>
                    
                    <div class="alert alert-{{ $credentialStatus['rsp']['valid'] ? 'success' : 'warning' }}" role="alert">
                        <div class="d-flex align-items-start">
                            <i class="bi bi-{{ $credentialStatus['rsp']['valid'] ? 'check-circle' : 'exclamation-triangle' }} me-2 mt-1"></i>
                            <div>
                                <div class="fw-bold">{{ $credentialStatus['rsp']['message'] }}</div>
                                @if(!$credentialStatus['rsp']['valid'] && isset($credentialStatus['rsp']['action_required']))
                                    <small>{{ $credentialStatus['rsp']['action_required'] }}</small>
                                @endif
                                @if($credentialStatus['rsp']['valid'] && isset($credentialStatus['rsp']['last_tested']))
                                    <small>Last tested: {{ $credentialStatus['rsp']['last_tested'] }}</small>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <label class="form-label">App ID</label>
                            <div class="input-group">
                                <input type="text" class="form-control" value="{{ $currentConfig['app_id'] ?: 'Not configured' }}" readonly>
                                <span class="input-group-text">
                                    <i class="bi bi-{{ $currentConfig['app_id'] ? 'check-circle text-success' : 'x-circle text-danger' }}"></i>
                                </span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">App Secret</label>
                            <div class="input-group">
                                <input type="password" class="form-control" value="{{ $currentConfig['app_secret'] ?: 'Not configured' }}" readonly>
                                <span class="input-group-text">
                                    <i class="bi bi-{{ $currentConfig['app_secret'] ? 'check-circle text-success' : 'x-circle text-danger' }}"></i>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Test Connection Button -->
                <div class="text-center">
                    <button type="button" class="btn btn-outline-primary" id="testConnectionBtn">
                        <i class="bi bi-wifi"></i> Test Connection
                    </button>
                </div>
            </div>
        </div>

        <!-- Configuration Instructions -->
        <div class="card mt-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-gear"></i> Configuration Guide</h5>
            </div>
            <div class="card-body">
                <div class="alert alert-info" role="alert">
                    <i class="bi bi-info-circle"></i>
                    <strong>Security Note:</strong> Credentials are configured in the <code>.env</code> file for security reasons. 
                    They cannot be changed through the web interface.
                </div>

                <h6>Environment Variables:</h6>
                <div class="bg-light p-3 rounded">
                    <code>
# Warehouse API Credentials<br>
JOYTEL_CUSTOMER_CODE=your_customer_code_here<br>
JOYTEL_CUSTOMER_AUTH=your_customer_auth_here<br><br>

# RSP API Credentials<br>
JOYTEL_APP_ID=your_app_id_here<br>
JOYTEL_APP_SECRET=your_app_secret_here<br><br>

# Optional Settings<br>
JOYTEL_SANDBOX_MODE={{ $currentConfig['sandbox_mode'] ? 'true' : 'false' }}<br>
JOYTEL_DEMO_MODE={{ $currentConfig['demo_mode'] ? 'true' : 'false' }}
                    </code>
                </div>
            </div>
        </div>
    </div>

    <!-- Partnership Information -->
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-handshake"></i> Partnership Information</h5>
            </div>
            <div class="card-body">
                <h6 class="fw-bold">Get Official Credentials</h6>
                <p class="small text-muted">
                    JoyTel credentials must be obtained through official partnership registration.
                </p>

                <div class="mb-3">
                    <label class="form-label fw-bold">Contact Information:</label>
                    <ul class="list-unstyled small">
                        <li><i class="bi bi-envelope"></i> {{ $partnershipInfo['partnership_email'] }}</li>
                        <li><i class="bi bi-headset"></i> {{ $partnershipInfo['technical_support'] }}</li>
                        <li><i class="bi bi-globe"></i> <a href="{{ $partnershipInfo['business_website'] }}" target="_blank">Partnership Portal</a></li>
                        <li><i class="bi bi-book"></i> <a href="{{ $partnershipInfo['documentation'] }}" target="_blank">API Documentation</a></li>
                    </ul>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">Required Documents:</label>
                    <ul class="small">
                        @foreach($partnershipInfo['required_documents'] as $doc)
                            <li>{{ $doc }}</li>
                        @endforeach
                    </ul>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">Integration Process:</label>
                    <ol class="small">
                        @foreach($partnershipInfo['integration_process'] as $step)
                            <li>{{ $step }}</li>
                        @endforeach
                    </ol>
                </div>

                @if($partnershipInfo['sandbox_environment']['available'])
                <div class="alert alert-info small" role="alert">
                    <i class="bi bi-cloud"></i>
                    <strong>Sandbox Available:</strong> Development environment is configured for testing.
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.getElementById('testConnectionBtn').addEventListener('click', function() {
    const btn = this;
    btn.disabled = true;
    btn.innerHTML = '<i class="bi bi-spinner-border spinner-border-sm"></i> Testing...';
    
    fetch('/settings/test-connection', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Content-Type': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Show results in a modal or alert
            let message = 'Connection Test Results:\n\n';
            
            if (data.data.warehouse.valid) {
                message += '✅ Warehouse API: Connected\n';
            } else {
                message += '❌ Warehouse API: ' + data.data.warehouse.message + '\n';
            }
            
            if (data.data.rsp.valid) {
                message += '✅ RSP API: Connected\n';
            } else {
                message += '❌ RSP API: ' + data.data.rsp.message + '\n';
            }
            
            alert(message);
        } else {
            alert('Connection test failed: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Connection test failed. Please check the console for details.');
    })
    .finally(() => {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-wifi"></i> Test Connection';
    });
});
</script>
@endpush
@extends('layouts.app')

@section('title', 'API Logs - JoyTel Dashboard')
@section('page-title', 'API Logs')

@section('page-actions')
    <div class="btn-group">
        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="location.reload()">
            <i class="bi bi-arrow-clockwise"></i> Refresh
        </button>
        <button type="button" class="btn btn-sm btn-outline-danger" onclick="clearFilters()">
            <i class="bi bi-x-circle"></i> Clear Filters
        </button>
    </div>
@endsection

@section('content')
    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('logs.index') }}" class="row g-3">
                <div class="col-md-2">
                    <label class="form-label">System Type</label>
                    <select name="system_type" class="form-select">
                        <option value="">All Systems</option>
                        <option value="warehouse" {{ request('system_type') == 'warehouse' ? 'selected' : '' }}>Warehouse</option>
                        <option value="rsp" {{ request('system_type') == 'rsp' ? 'selected' : '' }}>RSP</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Endpoint</label>
                    <input type="text" name="endpoint" class="form-control" placeholder="/customerApi/..." value="{{ request('endpoint') }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Order TID</label>
                    <input type="text" name="order_tid" class="form-control" placeholder="JT2024..." value="{{ request('order_tid') }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Response Code</label>
                    <select name="response_code" class="form-select">
                        <option value="">All Codes</option>
                        <option value="000" {{ request('response_code') == '000' ? 'selected' : '' }}>000 (Success)</option>
                        <option value="401" {{ request('response_code') == '401' ? 'selected' : '' }}>401 (Invalid ID)</option>
                        <option value="403" {{ request('response_code') == '403' ? 'selected' : '' }}>403 (Encryption)</option>
                        <option value="407" {{ request('response_code') == '407' ? 'selected' : '' }}>407 (IP Whitelist)</option>
                        <option value="500" {{ request('response_code') == '500' ? 'selected' : '' }}>500 (Parameter)</option>
                        <option value="600" {{ request('response_code') == '600' ? 'selected' : '' }}>600 (Business)</option>
                        <option value="999" {{ request('response_code') == '999' ? 'selected' : '' }}>999 (System)</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">&nbsp;</label>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-search"></i> Filter Logs
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Logs Table -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">API Logs</h5>
            <span class="text-muted">{{ $logs->total() }} total logs</span>
        </div>
        <div class="card-body p-0">
            @if($logs->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover table-sm mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Timestamp</th>
                                <th>System</th>
                                <th>Endpoint</th>
                                <th>Order TID</th>
                                <th>Transaction ID</th>
                                <th>Response</th>
                                <th>Duration</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($logs as $log)
                            <tr>
                                <td>
                                    {{ $log->created_at->format('M j H:i:s') }}
                                    <br><small class="text-muted">{{ $log->created_at->diffForHumans() }}</small>
                                </td>
                                <td>
                                    <span class="badge bg-{{ $log->system_type === 'warehouse' ? 'primary' : 'info' }}">
                                        {{ ucfirst($log->system_type) }}
                                    </span>
                                </td>
                                <td>
                                    <code style="font-size: 0.75rem;">{{ $log->endpoint }}</code>
                                    <br><small class="text-muted">{{ strtoupper($log->method) }}</small>
                                </td>
                                <td>
                                    @if($log->order_tid)
                                        <a href="{{ route('orders.index') }}?search={{ $log->order_tid }}" class="text-decoration-none">
                                            <small>{{ $log->order_tid }}</small>
                                        </a>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>
                                    @if($log->transaction_id)
                                        <small><code>{{ Str::limit($log->transaction_id, 15) }}</code></small>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>
                                    <!-- Response Code -->
                                    @if($log->response_code)
                                        <span class="badge bg-{{ $log->response_code === '000' ? 'success' : 'danger' }} mb-1">
                                            {{ $log->response_code }}
                                        </span>
                                        <br>
                                    @endif
                                    
                                    <!-- HTTP Status -->
                                    @if($log->response_status)
                                        <small class="text-muted">HTTP {{ $log->response_status }}</small>
                                    @endif
                                    
                                    <!-- Error Message -->
                                    @if($log->error_message)
                                        <br><small class="text-danger" title="{{ $log->error_message }}">
                                            <i class="bi bi-exclamation-triangle"></i> Error
                                        </small>
                                    @endif
                                </td>
                                <td>
                                    @if($log->response_time)
                                        <span class="badge bg-{{ $log->response_time > 5 ? 'warning' : ($log->response_time > 2 ? 'info' : 'success') }}">
                                            {{ $log->response_time }}s
                                        </span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>
                                    <button type="button" class="btn btn-outline-primary btn-sm" onclick="viewLogDetails({{ $log->id }})" title="View Details">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="card-footer">
                    {{ $logs->withQueryString()->links() }}
                </div>
            @else
                <div class="text-center py-5">
                    <i class="bi bi-inbox text-muted" style="font-size: 4rem;"></i>
                    <h5 class="text-muted mt-3">No logs found</h5>
                    <p class="text-muted">Try adjusting your filters or check if any API calls have been made.</p>
                </div>
            @endif
        </div>
    </div>
@endsection

<!-- Log Details Modal -->
<div class="modal fade" id="logDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">API Log Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="logDetailsContent">Loading...</div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function clearFilters() {
    window.location.href = '{{ route('logs.index') }}';
}

function viewLogDetails(logId) {
    const modal = new bootstrap.Modal(document.getElementById('logDetailsModal'));
    const content = document.getElementById('logDetailsContent');
    
    content.innerHTML = 'Loading...';
    modal.show();
    
    fetch(`/api/logs/${logId}`, {
        headers: {
            'Accept': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const log = data.data;
            let html = `
                <div class="row">
                    <div class="col-md-6">
                        <h6>Request Information</h6>
                        <table class="table table-sm">
                            <tr><td><strong>System:</strong></td><td>${log.system_type}</td></tr>
                            <tr><td><strong>Endpoint:</strong></td><td><code>${log.endpoint}</code></td></tr>
                            <tr><td><strong>Method:</strong></td><td>${log.method}</td></tr>
                            <tr><td><strong>Transaction ID:</strong></td><td>${log.transaction_id || 'N/A'}</td></tr>
                            <tr><td><strong>Order TID:</strong></td><td>${log.order_tid || 'N/A'}</td></tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <h6>Response Information</h6>
                        <table class="table table-sm">
                            <tr><td><strong>Response Code:</strong></td><td>${log.response_code || 'N/A'}</td></tr>
                            <tr><td><strong>HTTP Status:</strong></td><td>${log.response_status || 'N/A'}</td></tr>
                            <tr><td><strong>Response Time:</strong></td><td>${log.response_time || 'N/A'}s</td></tr>
                            <tr><td><strong>Timestamp:</strong></td><td>${new Date(log.created_at).toLocaleString()}</td></tr>
                        </table>
                    </div>
                </div>
            `;
            
            if (log.request_headers) {
                html += `
                    <h6 class="mt-3">Request Headers</h6>
                    <pre class="bg-light p-2 rounded small">${JSON.stringify(log.request_headers, null, 2)}</pre>
                `;
            }
            
            if (log.request_body) {
                html += `
                    <h6 class="mt-3">Request Body</h6>
                    <pre class="bg-light p-2 rounded small">${JSON.stringify(log.request_body, null, 2)}</pre>
                `;
            }
            
            if (log.response_body) {
                html += `
                    <h6 class="mt-3">Response Body</h6>
                    <pre class="bg-light p-2 rounded small">${JSON.stringify(log.response_body, null, 2)}</pre>
                `;
            }
            
            if (log.error_message) {
                html += `
                    <h6 class="mt-3 text-danger">Error Message</h6>
                    <div class="alert alert-danger">${log.error_message}</div>
                `;
            }
            
            content.innerHTML = html;
        } else {
            content.innerHTML = '<div class="alert alert-danger">Failed to load log details</div>';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        content.innerHTML = '<div class="alert alert-danger">Error loading log details</div>';
    });
}
</script>
@endpush
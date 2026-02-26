@extends('layouts.app')

@section('title', 'Order Details - JoyTel Dashboard')
@section('page-title', 'Order Details')

@section('page-actions')
    <div class="btn-group">
        <a href="{{ route('orders.index') }}" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Back to Orders
        </a>
        <button type="button" class="btn btn-sm btn-outline-primary" onclick="location.reload()">
            <i class="bi bi-arrow-clockwise"></i> Refresh
        </button>
        @if($order->status === 'pending' || $order->status === 'failed')
            <button type="button" class="btn btn-sm btn-warning" onclick="retryOrder()">
                <i class="bi bi-arrow-clockwise"></i> Retry
            </button>
        @endif
    </div>
@endsection

@section('content')
    <div class="row">
        <!-- Order Information -->
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="bi bi-info-circle"></i> Order Information
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <td><strong>Order TID:</strong></td>
                                    <td><code>{{ $order->order_tid }}</code></td>
                                </tr>
                                <tr>
                                    <td><strong>Order Code:</strong></td>
                                    <td>{{ $order->order_code ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Product Code:</strong></td>
                                    <td><span class="badge bg-secondary">{{ $order->product_code }}</span></td>
                                </tr>
                                <tr>
                                    <td><strong>Quantity:</strong></td>
                                    <td>{{ $order->quantity }}</td>
                                </tr>
                                <tr>
                                    <td><strong>System Type:</strong></td>
                                    <td><span class="badge bg-info">{{ ucfirst($order->system_type) }}</span></td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <td><strong>Status:</strong></td>
                                    <td>
                                        @php
                                            $statusColors = [
                                                'pending' => 'warning',
                                                'processing' => 'primary', 
                                                'completed' => 'success',
                                                'failed' => 'danger'
                                            ];
                                        @endphp
                                        <span class="badge bg-{{ $statusColors[$order->status] ?? 'secondary' }}">
                                            {{ ucfirst($order->status) }}
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>Customer ID:</strong></td>
                                    <td>{{ $order->cid ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Created:</strong></td>
                                    <td>{{ $order->created_at->format('M j, Y H:i:s') }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Submitted:</strong></td>
                                    <td>{{ $order->submitted_at ? $order->submitted_at->format('M j, Y H:i:s') : 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Completed:</strong></td>
                                    <td>{{ $order->completed_at ? $order->completed_at->format('M j, Y H:i:s') : 'N/A' }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- eSIM Data -->
            @if($order->sn_pin || $order->qrcode)
            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="bi bi-sim"></i> eSIM Information
                    </h5>
                </div>
                <div class="card-body">
                    @if($order->sn_pin)
                        <div class="mb-3">
                            <label class="form-label"><strong>SN/PIN:</strong></label>
                            <div class="input-group">
                                <input type="text" class="form-control" value="{{ $order->sn_pin }}" readonly>
                                <button class="btn btn-outline-secondary" type="button" onclick="copyToClipboard('{{ $order->sn_pin }}')">Copy</button>
                            </div>
                        </div>
                    @endif
                    
                    @if($order->qrcode)
                        <div class="mb-3">
                            <label class="form-label"><strong>QR Code:</strong></label>
                            <div class="input-group">
                                <input type="text" class="form-control" value="{{ $order->qrcode }}" readonly>
                                <button class="btn btn-outline-secondary" type="button" onclick="copyToClipboard('{{ $order->qrcode }}')">Copy</button>
                            </div>
                        </div>
                        
                        <!-- QR Code Display -->
                        <div class="text-center">
                            <div id="qrcode" class="d-inline-block"></div>
                        </div>
                    @endif
                </div>
            </div>
            @endif

            <!-- Request/Response Data -->
            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="bi bi-code-square"></i> Technical Details
                    </h5>
                </div>
                <div class="card-body">
                    <!-- Request Data -->
                    @if($order->request_data)
                        <h6>Request Data:</h6>
                        <pre class="bg-light p-3 rounded"><code>{{ json_encode($order->request_data, JSON_PRETTY_PRINT) }}</code></pre>
                    @endif

                    <!-- Response Data -->
                    @if($order->response_data)
                        <h6 class="mt-4">Response Data:</h6>
                        <pre class="bg-light p-3 rounded"><code>{{ json_encode($order->response_data, JSON_PRETTY_PRINT) }}</code></pre>
                    @endif
                </div>
            </div>
        </div>

        <!-- Timeline & Actions -->
        <div class="col-lg-4">
            <!-- Quick Actions -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-lightning"></i> Quick Actions</h5>
                </div>
                <div class="card-body">
                    @if($order->status === 'completed' && $order->qrcode)
                        <button type="button" class="btn btn-success btn-sm w-100 mb-2" onclick="downloadQR()">
                            <i class="bi bi-download"></i> Download QR Code
                        </button>
                    @endif
                    
                    @if($order->status === 'pending' || $order->status === 'failed')
                        <button type="button" class="btn btn-warning btn-sm w-100 mb-2" onclick="retryOrder()">
                            <i class="bi bi-arrow-clockwise"></i> Retry Order
                        </button>
                    @endif
                    
                    <button type="button" class="btn btn-info btn-sm w-100 mb-2" onclick="queryStatus()">
                        <i class="bi bi-search"></i> Query Latest Status
                    </button>
                    
                    <a href="{{ route('logs.index') }}?order_tid={{ $order->order_tid }}" class="btn btn-outline-secondary btn-sm w-100">
                        <i class="bi bi-file-text"></i> View API Logs
                    </a>
                </div>
            </div>

            <!-- Order Timeline -->
            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-clock-history"></i> Order Timeline</h5>
                </div>
                <div class="card-body">
                    <div class="timeline">
                        <div class="timeline-item">
                            <div class="timeline-marker bg-primary"></div>
                            <div class="timeline-content">
                                <h6 class="mb-1">Order Created</h6>
                                <small class="text-muted">{{ $order->created_at->format('M j, Y H:i:s') }}</small>
                            </div>
                        </div>
                        
                        @if($order->submitted_at)
                        <div class="timeline-item">
                            <div class="timeline-marker bg-info"></div>
                            <div class="timeline-content">
                                <h6 class="mb-1">Submitted to API</h6>
                                <small class="text-muted">{{ $order->submitted_at->format('M j, Y H:i:s') }}</small>
                            </div>
                        </div>
                        @endif
                        
                        @if($order->sn_pin && isset($order->response_data['sn_pin_received_at']))
                        <div class="timeline-item">
                            <div class="timeline-marker bg-warning"></div>
                            <div class="timeline-content">
                                <h6 class="mb-1">SN/PIN Received</h6>
                                <small class="text-muted">{{ $order->response_data['sn_pin_received_at'] }}</small>
                            </div>
                        </div>
                        @endif
                        
                        @if($order->completed_at)
                        <div class="timeline-item">
                            <div class="timeline-marker bg-success"></div>
                            <div class="timeline-content">
                                <h6 class="mb-1">Order Completed</h6>
                                <small class="text-muted">{{ $order->completed_at->format('M j, Y H:i:s') }}</small>
                            </div>
                        </div>
                        @endif
                        
                        @if($order->status === 'failed')
                        <div class="timeline-item">
                            <div class="timeline-marker bg-danger"></div>
                            <div class="timeline-content">
                                <h6 class="mb-1">Order Failed</h6>
                                <small class="text-muted">{{ $order->updated_at->format('M j, Y H:i:s') }}</small>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcode/1.5.3/qrcode.min.js"></script>
<script>
// Generate QR Code if available
@if($order->qrcode)
document.addEventListener('DOMContentLoaded', function() {
    QRCode.toCanvas(document.getElementById('qrcode'), '{{ $order->qrcode }}', function(error) {
        if (error) console.error(error);
    });
});
@endif

function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(() => {
        // Show tooltip or alert
        alert('Copied to clipboard!');
    });
}

function retryOrder() {
    if (confirm('Are you sure you want to retry this order?')) {
        fetch(`/orders/{{ $order->id }}/retry`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Content-Type': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Failed to retry order: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while retrying the order.');
        });
    }
}

function queryStatus() {
    const btn = event.target;
    btn.disabled = true;
    btn.innerHTML = '<i class="bi bi-spinner"></i> Querying...';
    
    fetch(`/orders/{{ $order->id }}/query-status`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Content-Type': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-search"></i> Query Latest Status';
        
        if (data.success) {
            location.reload();
        } else {
            alert('Failed to query status: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-search"></i> Query Latest Status';
        alert('An error occurred while querying status.');
    });
}

function downloadQR() {
    const canvas = document.querySelector('#qrcode canvas');
    if (canvas) {
        const link = document.createElement('a');
        link.download = 'qrcode-{{ $order->order_tid }}.png';
        link.href = canvas.toDataURL();
        link.click();
    }
}
</script>

<style>
.timeline {
    position: relative;
    padding-left: 2rem;
}

.timeline-item {
    position: relative;
    padding-bottom: 1.5rem;
}

.timeline-item:not(:last-child)::before {
    content: '';
    position: absolute;
    left: -1.75rem;
    top: 1.5rem;
    width: 2px;
    height: calc(100% - 0.5rem);
    background-color: #dee2e6;
}

.timeline-marker {
    position: absolute;
    left: -2rem;
    top: 0.25rem;
    width: 0.5rem;
    height: 0.5rem;
    border-radius: 50%;
    border: 2px solid #fff;
    box-shadow: 0 0 0 2px #dee2e6;
}

.timeline-content h6 {
    font-size: 0.875rem;
}
</style>
@endpush
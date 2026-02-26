@extends('layouts.app')

@section('title', 'Orders - JoyTel Dashboard')
@section('page-title', 'Orders Management')

@section('page-actions')
    <div class="btn-group">
        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="location.reload()">
            <i class="bi bi-arrow-clockwise"></i> Refresh
        </button>
        <a href="{{ route('orders.create') }}" class="btn btn-sm btn-primary">
            <i class="bi bi-plus-circle"></i> New Order
        </a>
    </div>
@endsection

@section('content')
    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('orders.index') }}" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="">All Status</option>
                        <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="processing" {{ request('status') == 'processing' ? 'selected' : '' }}>Processing</option>
                        <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Completed</option>
                        <option value="failed" {{ request('status') == 'failed' ? 'selected' : '' }}>Failed</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">System</label>
                    <select name="system_type" class="form-select">
                        <option value="">All Systems</option>
                        <option value="warehouse" {{ request('system_type') == 'warehouse' ? 'selected' : '' }}>Warehouse</option>
                        <option value="rsp" {{ request('system_type') == 'rsp' ? 'selected' : '' }}>RSP</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Search</label>
                    <input type="text" name="search" class="form-control" placeholder="Order TID, Product Code..." value="{{ request('search') }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">&nbsp;</label>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-outline-primary">
                            <i class="bi bi-search"></i> Filter
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Orders Table -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Orders List</h5>
            <span class="text-muted">{{ $orders->total() }} total orders</span>
        </div>
        <div class="card-body p-0">
            @if($orders->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Order TID</th>
                                <th>Product Code</th>
                                <th>Quantity</th>
                                <th>Status</th>
                                <th>System</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($orders as $order)
                            <tr>
                                <td>
                                    <code>{{ $order->order_tid }}</code>
                                    @if($order->order_code)
                                        <br><small class="text-muted">{{ $order->order_code }}</small>
                                    @endif
                                </td>
                                <td>
                                    <strong>{{ $order->product_code }}</strong>
                                    @if($order->cid)
                                        <br><small class="text-muted">CID: {{ $order->cid }}</small>
                                    @endif
                                </td>
                                <td>{{ $order->quantity }}</td>
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
                                <td>
                                    <span class="badge bg-info">
                                        {{ ucfirst($order->system_type) }}
                                    </span>
                                </td>
                                <td>
                                    {{ $order->created_at->format('M j, Y H:i') }}
                                    <br><small class="text-muted">{{ $order->created_at->diffForHumans() }}</small>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('orders.show', $order->id) }}" class="btn btn-outline-primary" title="View Details">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        @if($order->status === 'pending')
                                            <button type="button" class="btn btn-outline-warning" title="Retry" onclick="retryOrder({{ $order->id }})">
                                                <i class="bi bi-arrow-clockwise"></i>
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="card-footer">
                    {{ $orders->withQueryString()->links() }}
                </div>
            @else
                <div class="text-center py-5">
                    <i class="bi bi-inbox text-muted" style="font-size: 4rem;"></i>
                    <h5 class="text-muted mt-3">No orders found</h5>
                    <p class="text-muted">Try adjusting your filters or create a new order.</p>
                    <a href="{{ route('orders.create') }}" class="btn btn-primary">
                        <i class="bi bi-plus-circle"></i> Create New Order
                    </a>
                </div>
            @endif
        </div>
    </div>
@endsection

@push('scripts')
<script>
function retryOrder(orderId) {
    if (confirm('Are you sure you want to retry this order?')) {
        fetch(`/orders/${orderId}/retry`, {
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
</script>
@endpush
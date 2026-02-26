@extends('layouts.app')

@section('title', 'JoyTel Dashboard')
@section('page-title', 'Dashboard Overview')

@section('page-actions')
    <div class="btn-group">
        <button type="button" class="btn btn-sm btn-outline-primary" onclick="location.reload()">
            <i class="bi bi-arrow-clockwise"></i> Refresh
        </button>
        <a href="{{ route('orders.create') }}" class="btn btn-sm btn-primary">
            <i class="bi bi-plus-circle"></i> New Order
        </a>
    </div>
@endsection

@section('content')
    <!-- Stats Cards -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="card card-hover bg-gradient-primary text-white mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-white-75">Total Orders</div>
                            <div class="h3 mb-0">{{ $stats['total_orders'] ?? 0 }}</div>
                        </div>
                        <div class="opacity-75">
                            <i class="bi bi-bag-check fs-1"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card card-hover bg-gradient-success text-white mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-white-75">Completed</div>
                            <div class="h3 mb-0">{{ $stats['completed_orders'] ?? 0 }}</div>
                        </div>
                        <div class="opacity-75">
                            <i class="bi bi-check2-circle fs-1"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card card-hover bg-gradient-warning text-white mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-white-75">Processing</div>
                            <div class="h3 mb-0">{{ $stats['processing_orders'] ?? 0 }}</div>
                        </div>
                        <div class="opacity-75">
                            <i class="bi bi-clock-history fs-1"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card card-hover bg-gradient-danger text-white mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-white-75">Failed</div>
                            <div class="h3 mb-0">{{ $stats['failed_orders'] ?? 0 }}</div>
                        </div>
                        <div class="opacity-75">
                            <i class="bi bi-x-circle fs-1"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Recent Orders -->
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="bi bi-clock-history"></i> Recent Orders</h5>
                    <a href="{{ route('orders.index') }}" class="btn btn-sm btn-outline-primary">View All</a>
                </div>
                <div class="card-body p-0">
                    @if(!empty($recent_orders) && count($recent_orders) > 0)
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Order TID</th>
                                        <th>Product</th>
                                        <th>Status</th>
                                        <th>System</th>
                                        <th>Created</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($recent_orders as $order)
                                    <tr>
                                        <td>
                                            <a href="{{ route('orders.show', $order->id) }}" class="text-decoration-none">
                                                <code>{{ $order->order_tid }}</code>
                                            </a>
                                        </td>
                                        <td>{{ $order->product_code }}</td>
                                        <td>
                                            @php
                                                $statusColors = [
                                                    'pending' => 'warning',
                                                    'processing' => 'primary', 
                                                    'completed' => 'success',
                                                    'failed' => 'danger'
                                                ];
                                            @endphp
                                            <span class="badge bg-{{ $statusColors[$order->status] ?? 'secondary' }} status-badge">
                                                {{ ucfirst($order->status) }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-info status-badge">
                                                {{ ucfirst($order->system_type) }}
                                            </span>
                                        </td>
                                        <td>
                                            <small class="text-muted">
                                                {{ $order->created_at->diffForHumans() }}
                                            </small>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-4">
                            <i class="bi bi-inbox text-muted" style="font-size: 3rem;"></i>
                            <p class="text-muted mt-2">No orders yet</p>
                            <a href="{{ route('orders.create') }}" class="btn btn-primary">Create First Order</a>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- System Status -->
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-gear"></i> System Status</h5>
                </div>
                <div class="card-body">
                    <!-- API Configuration Status -->
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span>Warehouse API</span>
                        @if(!empty(config('joytel.customer_code')) && !empty(config('joytel.customer_auth')))
                            <span class="badge bg-success"><i class="bi bi-check"></i> Configured</span>
                        @else
                            <span class="badge bg-danger"><i class="bi bi-x"></i> Not Configured</span>
                        @endif
                    </div>
                    
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span>RSP API</span>
                        @if(!empty(config('joytel.app_id')) && !empty(config('joytel.app_secret')))
                            <span class="badge bg-success"><i class="bi bi-check"></i> Configured</span>
                        @else
                            <span class="badge bg-danger"><i class="bi bi-x"></i> Not Configured</span>
                        @endif
                    </div>

                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span>Queue Connection</span>
                        <span class="badge bg-info">{{ config('queue.default') }}</span>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span>Environment</span>
                        <span class="badge bg-{{ app()->environment('production') ? 'success' : 'warning' }}">
                            {{ ucfirst(app()->environment()) }}
                        </span>
                    </div>

                    <hr>

                    <!-- Queue Statistics -->
                    <h6><i class="bi bi-list-task"></i> Queue Status</h6>
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span>Pending Jobs</span>
                        <span class="badge bg-warning">{{ $stats['pending_jobs'] ?? 0 }}</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span>Failed Jobs</span>
                        <span class="badge bg-danger">{{ $stats['failed_jobs'] ?? 0 }}</span>
                    </div>

                    <div class="mt-3">
                        <a href="{{ route('queue.monitor') }}" class="btn btn-outline-primary btn-sm w-100">
                            <i class="bi bi-eye"></i> Monitor Queue
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Orders Chart -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-bar-chart"></i> Orders Trend (Last 7 Days)</h5>
                </div>
                <div class="card-body">
                    <canvas id="ordersChart" height="100"></canvas>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Orders Chart
    const ctx = document.getElementById('ordersChart').getContext('2d');
    const ordersChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: {!! json_encode($chart_data['labels'] ?? ['Day 1', 'Day 2', 'Day 3', 'Day 4', 'Day 5', 'Day 6', 'Day 7']) !!},
            datasets: [{
                label: 'Completed Orders',
                data: {!! json_encode($chart_data['completed'] ?? [2, 5, 3, 8, 6, 9, 4]) !!},
                borderColor: '#28a745',
                backgroundColor: 'rgba(40, 167, 69, 0.1)',
                tension: 0.4,
                fill: true
            }, {
                label: 'Failed Orders', 
                data: {!! json_encode($chart_data['failed'] ?? [1, 0, 2, 1, 0, 1, 2]) !!},
                borderColor: '#dc3545',
                backgroundColor: 'rgba(220, 53, 69, 0.1)',
                tension: 0.4,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true
                }
            },
            plugins: {
                legend: {
                    position: 'top'
                }
            }
        }
    });
});
</script>
@endpush
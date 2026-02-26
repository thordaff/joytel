@extends('layouts.app')

@section('title', 'Queue Monitor - JoyTel Dashboard')
@section('page-title', 'Queue Monitor')

@section('page-actions')
    <div class="btn-group">
        <button type="button" class="btn btn-sm btn-outline-primary" onclick="location.reload()">
            <i class="bi bi-arrow-clockwise"></i> Refresh
        </button>
        <button type="button" class="btn btn-sm btn-outline-warning" onclick="retryFailedJobs()">
            <i class="bi bi-arrow-repeat"></i> Retry Failed
        </button>
    </div>
@endsection

@section('content')
    <!-- Queue Statistics -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card text-white bg-primary">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <div class="card-title h5">Pending Jobs</div>
                            <div class="h2 mb-0">{{ $queue_stats['pending_jobs'] }}</div>
                        </div>
                        <div class="align-self-center">
                            <i class="bi bi-clock-history" style="font-size: 2rem;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card text-white bg-danger">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <div class="card-title h5">Failed Jobs</div>
                            <div class="h2 mb-0">{{ $queue_stats['failed_jobs'] }}</div>
                        </div>
                        <div class="align-self-center">
                            <i class="bi bi-x-circle" style="font-size: 2rem;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card text-white bg-info">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <div class="card-title h5">API Calls Today</div>
                            <div class="h2 mb-0">{{ $queue_stats['total_api_calls_today'] }}</div>
                        </div>
                        <div class="align-self-center">
                            <i class="bi bi-graph-up" style="font-size: 2rem;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card text-white bg-success">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <div class="card-title h5">Success Rate</div>
                            <div class="h2 mb-0">
                                @if($queue_stats['total_api_calls_today'] > 0)
                                    {{ round(($queue_stats['successful_calls_today'] / $queue_stats['total_api_calls_today']) * 100, 1) }}%
                                @else
                                    N/A
                                @endif
                            </div>
                        </div>
                        <div class="align-self-center">
                            <i class="bi bi-check-circle" style="font-size: 2rem;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent API Calls -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="bi bi-list-ul"></i> Recent API Calls</h5>
            <span class="badge bg-secondary">Last 50 calls</span>
        </div>
        <div class="card-body p-0">
            @if($recent_logs->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Time</th>
                                <th>System</th>
                                <th>Endpoint</th>
                                <th>Order TID</th>
                                <th>Response</th>
                                <th>Duration</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($recent_logs as $log)
                            <tr>
                                <td>
                                    <small>{{ $log->created_at->format('H:i:s') }}</small>
                                    <br><span class="text-muted" style="font-size: 0.7rem;">{{ $log->created_at->diffForHumans() }}</span>
                                </td>
                                <td>
                                    <span class="badge bg-{{ $log->system_type === 'warehouse' ? 'primary' : 'info' }}">
                                        {{ ucfirst($log->system_type) }}
                                    </span>
                                </td>
                                <td>
                                    <code class="small">{{ $log->endpoint }}</code>
                                </td>
                                <td>
                                    @if($log->order_tid)
                                        <a href="{{ route('orders.show', ['order' => \App\Models\Order::where('order_tid', $log->order_tid)->first()->id ?? '#']) }}" class="text-decoration-none">
                                            <small>{{ $log->order_tid }}</small>
                                        </a>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>
                                    @if($log->response_code)
                                        <span class="badge bg-{{ $log->response_code === '000' ? 'success' : 'danger' }}">
                                            {{ $log->response_code }}
                                        </span>
                                    @else
                                        <span class="badge bg-secondary">N/A</span>
                                    @endif
                                    <br>
                                    @if($log->response_status)
                                        <small class="text-muted">HTTP {{ $log->response_status }}</small>
                                    @endif
                                </td>
                                <td>
                                    @if($log->response_time)
                                        <span class="badge bg-{{ $log->response_time > 5 ? 'warning' : 'info' }}">
                                            {{ $log->response_time }}s
                                        </span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>
                                    @if($log->error_message)
                                        <i class="bi bi-exclamation-triangle text-danger" title="{{ $log->error_message }}"></i>
                                    @elseif($log->response_code === '000')
                                        <i class="bi bi-check-circle text-success"></i>
                                    @else
                                        <i class="bi bi-x-circle text-danger"></i>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="text-center py-4">
                    <i class="bi bi-inbox text-muted" style="font-size: 3rem;"></i>
                    <p class="text-muted mt-2">No API calls logged yet</p>
                </div>
            @endif
        </div>
    </div>

    <!-- Queue Commands Help -->
    <div class="card mt-4">
        <div class="card-header">
            <h5 class="mb-0"><i class="bi bi-terminal"></i> Queue Management Commands</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <h6>Development Commands:</h6>
                    <pre class="bg-light p-2 rounded small">php artisan queue:work --queue=joytel,default
php artisan queue:failed
php artisan queue:retry all</pre>
                </div>
                <div class="col-md-6">
                    <h6>Production Commands:</h6>
                    <pre class="bg-light p-2 rounded small">sudo supervisorctl status
sudo supervisorctl restart joytel-workers:*
sudo supervisorctl start joytel-workers:*</pre>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
function retryFailedJobs() {
    if (confirm('Are you sure you want to retry all failed jobs?')) {
        // This would need to be implemented as an API endpoint
        alert('This feature needs to be implemented via artisan command: php artisan queue:retry all');
    }
}

// Auto refresh every 10 seconds
setTimeout(function() {
    window.location.reload();
}, 10000);
</script>
@endpush
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\JoytelCredentialService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

class AdminController extends Controller
{
    private JoytelCredentialService $credentialService;

    public function __construct(JoytelCredentialService $credentialService)
    {
        $this->credentialService = $credentialService;
    }

    /**
     * Application settings page
     */
    public function settings()
    {
        $credentialStatus = $this->credentialService->validateCredentials();
        $partnershipInfo = $this->credentialService->getPartnershipInfo();
        
        return view('settings.index', compact('credentialStatus', 'partnershipInfo'));
    }

    /**
     * Update application settings
     */
    public function updateSettings(Request $request)
    {
        // Handle general settings updates
        return back()->with('success', 'Settings updated successfully');
    }

    /**
     * JoyTel specific settings
     */
    public function joytelSettings()
    {
        $credentialStatus = $this->credentialService->validateCredentials();
        $partnershipInfo = $this->credentialService->getPartnershipInfo();
        
        $currentConfig = [
            'customer_code' => config('joytel.customer_code'),
            'customer_auth' => config('joytel.customer_auth') ? '****' . substr(config('joytel.customer_auth'), -4) : '',
            'app_id' => config('joytel.app_id'),
            'app_secret' => config('joytel.app_secret') ? '****' . substr(config('joytel.app_secret'), -4) : '',
            'warehouse_url' => config('joytel.warehouse_base_url'),
            'rsp_url' => config('joytel.rsp_base_url'),
            'sandbox_mode' => config('joytel.sandbox_mode', false),
            'demo_mode' => config('joytel.demo_mode', false)
        ];
        
        return view('settings.joytel', compact('credentialStatus', 'partnershipInfo', 'currentConfig'));
    }

    /**
     * Update JoyTel settings (limited to non-credential settings)
     */
    public function updateJoytelSettings(Request $request)
    {
        // For security, we don't allow updating credentials via web interface
        // Credentials must be set in .env file directly
        
        return back()->with('info', 'JoyTel credentials must be updated in .env file for security reasons');
    }

    /**
     * Test API connections
     */
    public function testConnection()
    {
        try {
            $credentialStatus = $this->credentialService->validateCredentials();
            
            return response()->json([
                'success' => true,
                'data' => $credentialStatus,
                'message' => 'Connection test completed'
            ]);
            
        } catch (\Exception $e) {
            Log::error('API connection test failed', ['error' => $e->getMessage()]);
            
            return response()->json([
                'success' => false,
                'message' => 'Connection test failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * System information
     */
    public function systemInfo()
    {
        $systemInfo = [
            'laravel_version' => app()->version(),
            'php_version' => PHP_VERSION,
            'environment' => config('app.env'),
            'debug_mode' => config('app.debug'),
            'timezone' => config('app.timezone'),
            'database' => [
                'connection' => config('database.default'),
                'host' => config('database.connections.mariadb.host'),
                'database' => config('database.connections.mariadb.database')
            ],
            'cache' => [
                'driver' => config('cache.default'),
                'stores' => config('cache.stores')
            ],
            'queue' => [
                'connection' => config('queue.default'),
                'pending_jobs' => \DB::table('jobs')->count(),
                'failed_jobs' => \DB::table('failed_jobs')->count()
            ],
            'storage' => [
                'disk_usage' => $this->getDiskUsage(),
                'logs_size' => $this->getLogsSize()
            ]
        ];
        
        return view('settings.system', compact('systemInfo'));
    }

    /**
     * System status for monitoring
     */
    public function systemStatus()
    {
        $status = [
            'database' => $this->checkDatabaseStatus(),
            'joytel_api' => $this->credentialService->validateCredentials(),
            'queue' => $this->checkQueueStatus(),
            'storage' => $this->checkStorageStatus(),
            'last_check' => now()->format('Y-m-d H:i:s')
        ];
        
        return response()->json([
            'success' => true,
            'data' => $status
        ]);
    }

    /**
     * Clear application cache
     */
    public function clearCache()
    {
        try {
            Artisan::call('cache:clear');
            Artisan::call('config:clear');
            Artisan::call('view:clear');
            Artisan::call('route:clear');
            
            return response()->json([
                'success' => true,
                'message' => 'All caches cleared successfully'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to clear cache: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Restart queue workers
     */
    public function restartQueue()
    {
        try {
            Artisan::call('queue:restart');
            
            return response()->json([
                'success' => true,
                'message' => 'Queue workers restarted successfully'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to restart queue: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * View Laravel logs
     */
    public function laravelLogs()
    {
        $logFile = storage_path('logs/laravel.log');
        $logs = [];
        
        if (file_exists($logFile)) {
            $content = file_get_contents($logFile);
            $lines = explode("\n", $content);
            
            // Get last 100 lines
            $logs = array_slice($lines, -100);
            $logs = array_reverse($logs);
        }
        
        return view('system.logs', compact('logs'));
    }

    // Helper methods
    private function checkDatabaseStatus(): array
    {
        try {
            \DB::connection()->getPdo();
            return ['status' => 'connected', 'message' => 'Database connection OK'];
        } catch (\Exception $e) {
            return ['status' => 'error', 'message' => 'Database connection failed: ' . $e->getMessage()];
        }
    }

    private function checkQueueStatus(): array
    {
        try {
            $pending = \DB::table('jobs')->count();
            $failed = \DB::table('failed_jobs')->count();
            
            return [
                'status' => 'ok',
                'pending_jobs' => $pending,
                'failed_jobs' => $failed,
                'message' => "Queue OK - {$pending} pending, {$failed} failed"
            ];
        } catch (\Exception $e) {
            return ['status' => 'error', 'message' => 'Queue check failed: ' . $e->getMessage()];
        }
    }

    private function checkStorageStatus(): array
    {
        try {
            $diskFree = disk_free_space('/');
            $diskTotal = disk_total_space('/');
            $usage = round((($diskTotal - $diskFree) / $diskTotal) * 100, 2);
            
            return [
                'status' => $usage > 90 ? 'warning' : 'ok',
                'disk_usage' => $usage . '%',
                'free_space' => $this->formatBytes($diskFree),
                'message' => "Disk usage: {$usage}%"
            ];
        } catch (\Exception $e) {
            return ['status' => 'error', 'message' => 'Storage check failed'];
        }
    }

    private function getDiskUsage(): string
    {
        try {
            $diskFree = disk_free_space('/');
            $diskTotal = disk_total_space('/');
            $usage = round((($diskTotal - $diskFree) / $diskTotal) * 100, 2);
            return $usage . '%';
        } catch (\Exception $e) {
            return 'Unknown';
        }
    }

    private function getLogsSize(): string
    {
        try {
            $logDir = storage_path('logs');
            $size = 0;
            
            if (is_dir($logDir)) {
                $files = glob($logDir . '/*');
                foreach ($files as $file) {
                    if (is_file($file)) {
                        $size += filesize($file);
                    }
                }
            }
            
            return $this->formatBytes($size);
        } catch (\Exception $e) {
            return 'Unknown';
        }
    }

    private function formatBytes(int $size, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $size > 1024 && $i < count($units) - 1; $i++) {
            $size /= 1024;
        }
        
        return round($size, $precision) . ' ' . $units[$i];
    }
}

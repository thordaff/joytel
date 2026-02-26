<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\ProductSyncService;

class SyncJoytelProducts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'joytel:sync-products {--system= : Specific system to sync (warehouse|rsp)} {--force : Force refresh cache}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync products from JoyTel API to local database';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $system = $this->option('system');
        $force = $this->option('force');
        
        $this->info('Starting JoyTel products synchronization...');
        
        if ($force) {
            $this->info('Force refresh mode enabled - clearing cache...');
        }
        
        try {
            $productSyncService = new ProductSyncService();
            
            if ($system) {
                $this->info("Syncing products for system: {$system}");
                
                if ($force) {
                    $productSyncService->refreshProducts($system);
                } else {
                    $productSyncService->getProducts($system);
                }
                
                $this->info("✅ Products synced successfully for {$system} system");
            } else {
                $systems = ['warehouse', 'rsp'];
                
                foreach ($systems as $sys) {
                    $this->info("Syncing products for system: {$sys}");
                    
                    if ($force) {
                        $productSyncService->refreshProducts($sys);
                    } else {
                        $productSyncService->getProducts($sys);
                    }
                    
                    $this->info("✅ Products synced successfully for {$sys} system");
                }
            }
            
            $this->info('🎉 All products synchronized successfully!');
            
            // Show summary
            $this->showProductSummary();
            
        } catch (\Exception $e) {
            $this->error("❌ Failed to sync products: " . $e->getMessage());
            return Command::FAILURE;
        }
        
        return Command::SUCCESS;
    }
    
    /**
     * Show product summary
     */
    private function showProductSummary()
    {
        $this->info('');
        $this->info('📊 Product Summary:');
        
        $warehouseCount = \App\Models\Product::where('system_type', 'warehouse')->count();
        $rspCount = \App\Models\Product::where('system_type', 'rsp')->count();
        
        $this->table(
            ['System Type', 'Product Count', 'Last Updated'],
            [
                ['Warehouse', $warehouseCount, now()->format('Y-m-d H:i:s')],
                ['RSP', $rspCount, now()->format('Y-m-d H:i:s')],
                ['Total', $warehouseCount + $rspCount, now()->format('Y-m-d H:i:s')]
            ]
        );
    }
}

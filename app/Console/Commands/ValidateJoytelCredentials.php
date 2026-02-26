<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\JoytelCredentialService;

class ValidateJoytelCredentials extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'joytel:validate-credentials {--test-connection : Test actual API connection}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Validate JoyTel API credentials and connection';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔍 Validating JoyTel API Credentials...');
        $this->newLine();
        
        $credentialService = new JoytelCredentialService();
        
        try {
            $validation = $credentialService->validateCredentials();
            $partnershipInfo = $credentialService->getPartnershipInfo();
            
            // Display Warehouse API Status
            $this->displayAPIStatus('Warehouse API (System 1)', $validation['warehouse']);
            $this->newLine();
            
            // Display RSP API Status  
            $this->displayAPIStatus('RSP API (System 2)', $validation['rsp']);
            $this->newLine();
            
            // Overall Status
            if ($validation['overall_status']) {
                $this->info('✅ Overall Status: At least one API system is properly configured');
            } else {
                $this->error('❌ Overall Status: No API systems are properly configured');
            }
            
            // Show configuration status
            $this->displayConfigurationStatus();
            $this->newLine();
            
            // Show partnership information if credentials are missing
            if (!$validation['warehouse']['valid'] || !$validation['rsp']['valid']) {
                $this->displayPartnershipInfo($partnershipInfo);
            }
            
            return $validation['overall_status'] ? Command::SUCCESS : Command::FAILURE;
            
        } catch (\Exception $e) {
            $this->error('❌ Validation failed: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
    
    /**
     * Display API status information
     */
    private function displayAPIStatus(string $apiName, array $status)
    {
        $this->line("📡 <fg=cyan>{$apiName}</>");
        
        if ($status['valid']) {
            $this->info("   ✅ Status: Connected");
            $this->line("   📝 Message: {$status['message']}");
            
            if (isset($status['last_tested'])) {
                $this->line("   🕐 Last Tested: {$status['last_tested']}");
            }
        } else {
            $this->error("   ❌ Status: " . ucwords(str_replace('_', ' ', $status['status'])));
            $this->line("   📝 Message: {$status['message']}");
            
            if (isset($status['action_required'])) {
                $this->warn("   🔧 Action Required: {$status['action_required']}");
            }
        }
    }
    
    /**
     * Display current configuration status
     */
    private function displayConfigurationStatus()
    {
        $this->line('⚙️  <fg=cyan>Current Configuration:</>');
        
        $configs = [
            'JOYTEL_CUSTOMER_CODE' => config('joytel.customer_code'),
            'JOYTEL_CUSTOMER_AUTH' => config('joytel.customer_auth'),
            'JOYTEL_APP_ID' => config('joytel.app_id'),
            'JOYTEL_APP_SECRET' => config('joytel.app_secret'),
            'JOYTEL_SANDBOX_MODE' => config('joytel.sandbox_mode', false) ? 'true' : 'false',
            'JOYTEL_DEMO_MODE' => config('joytel.demo_mode', false) ? 'true' : 'false'
        ];
        
        foreach ($configs as $key => $value) {
            $status = empty($value) || $value === 'false' ? '❌' : '✅';
            $displayValue = empty($value) ? 'Not set' : 
                           (in_array($key, ['JOYTEL_CUSTOMER_AUTH', 'JOYTEL_APP_SECRET']) ? 
                            '****' . substr($value, -4) : $value);
            
            $this->line("   {$status} {$key}: {$displayValue}");
        }
    }
    
    /**
     * Display partnership information
     */
    private function displayPartnershipInfo(array $partnershipInfo)
    {
        $this->warn('🤝 Partnership Information:');
        $this->newLine();
        
        $this->line('📧 <fg=yellow>Contact JoyTel for Official Credentials:</>');
        $this->line("   • Business: {$partnershipInfo['partnership_email']}");
        $this->line("   • Support: {$partnershipInfo['technical_support']}");
        $this->line("   • Website: {$partnershipInfo['business_website']}");
        $this->line("   • Documentation: {$partnershipInfo['documentation']}");
        $this->newLine();
        
        $this->line('📄 <fg=yellow>Required Documents:</>');
        foreach ($partnershipInfo['required_documents'] as $index => $doc) {
            $this->line("   " . ($index + 1) . ". {$doc}");
        }
        $this->newLine();
        
        $this->line('🔄 <fg=yellow>Integration Process:</>');
        foreach ($partnershipInfo['integration_process'] as $index => $step) {
            $this->line("   " . ($index + 1) . ". {$step}");
        }
        $this->newLine();
        
        $this->info('💡 Tip: You can use SANDBOX mode for development and testing before getting production credentials.');
    }
}

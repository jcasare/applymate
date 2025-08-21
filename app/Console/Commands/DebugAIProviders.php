<?php

namespace App\Console\Commands;

use App\Services\AI\AIAggregatorService;
use Illuminate\Console\Command;

class DebugAIProviders extends Command
{
    protected $signature = 'ai:debug';
    protected $description = 'Debug AI provider configuration and availability';

    public function handle()
    {
        $this->info('🔍 Debugging AI Provider Configuration...');
        $this->newLine();

        // Check configuration
        $config = config('ai.providers');
        $this->info('📋 Provider Configuration:');
        
        foreach ($config as $key => $providerConfig) {
            $enabled = $providerConfig['enabled'] ?? false;
            $hasKey = !empty($providerConfig['api_key']);
            
            $status = $enabled && $hasKey ? '✅' : ($enabled ? '⚠️' : '❌');
            $this->line("  {$status} {$key}: enabled=" . ($enabled ? 'true' : 'false') . ', has_key=' . ($hasKey ? 'true' : 'false'));
            
            if ($enabled && !$hasKey) {
                $this->error("    Missing API key for {$key}");
            }
        }

        $this->newLine();

        // Test AI Aggregator Service
        try {
            $this->info('🔧 Testing AI Aggregator Service...');
            $aiService = app(AIAggregatorService::class);
            
            $availableProviders = $aiService->getAvailableProviders();
            
            if (empty($availableProviders)) {
                $this->error('❌ No providers available!');
                $this->warn('This means none of your enabled providers are working.');
                $this->warn('Common issues:');
                $this->warn('  1. Invalid API keys');
                $this->warn('  2. Network connectivity issues');
                $this->warn('  3. Provider service outages');
            } else {
                $this->info('✅ Available providers:');
                foreach ($availableProviders as $key => $provider) {
                    $this->line("  • {$key}: {$provider['name']}");
                }
            }
            
        } catch (\Exception $e) {
            $this->error('❌ Error testing AI service: ' . $e->getMessage());
        }

        $this->newLine();
        $this->info('🏁 Debug completed!');
    }
}
<?php

namespace App\Console\Commands;

use App\Services\AI\AIAggregatorService;
use Illuminate\Console\Command;

class TestAIGeneration extends Command
{
    protected $signature = 'ai:test {--provider=}';
    protected $description = 'Test AI text generation with a simple prompt';

    public function handle()
    {
        $this->info('🧪 Testing AI Text Generation...');
        $this->newLine();

        try {
            $aiService = app(AIAggregatorService::class);
            
            $prompt = "Write a brief professional greeting for a job application.";
            $options = [
                'strategy' => 'fastest',
                'max_tokens' => 100,
                'temperature' => 0.7,
            ];

            if ($this->option('provider')) {
                $options['strategy'] = 'single';
                $options['provider'] = $this->option('provider');
                $this->info("Using single provider: {$this->option('provider')}");
            } else {
                $this->info("Using strategy: {$options['strategy']}");
            }

            $this->info("Prompt: {$prompt}");
            $this->newLine();
            
            $this->info('⏳ Generating...');
            $result = $aiService->generateText($prompt, $options);
            
            if (isset($result['error'])) {
                $this->error("❌ Generation failed: {$result['message']}");
            } else {
                $this->info('✅ Generation successful!');
                $this->newLine();
                $this->info('📝 Result:');
                $this->line("Provider: " . ($result['provider'] ?? 'multiple'));
                $this->line("Strategy: " . ($result['strategy'] ?? 'unknown'));
                $this->newLine();
                $this->line('Generated text:');
                $this->info($result['text']);
            }
            
        } catch (\Exception $e) {
            $this->error('❌ Test failed: ' . $e->getMessage());
            $this->line('Stack trace:');
            $this->line($e->getTraceAsString());
        }
    }
}
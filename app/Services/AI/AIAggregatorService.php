<?php

namespace App\Services\AI;

use App\Services\AI\Contracts\AIProviderInterface;
use App\Services\AI\Providers\ClaudeProvider;
use App\Services\AI\Providers\HuggingFaceProvider;
use App\Services\AI\Providers\GroqProvider;
use App\Services\AI\Providers\CohereProvider;
use App\Services\AI\Providers\GeminiProvider;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class AIAggregatorService
{
    private array $providers = [];
    private array $config;

    public function __construct()
    {
        $this->config = config('ai');
        $this->initializeProviders();
    }

    private function initializeProviders(): void
    {
        $providerClasses = [
            'claude' => ClaudeProvider::class,
            'huggingface' => HuggingFaceProvider::class,
            'groq' => GroqProvider::class,
            'cohere' => CohereProvider::class,
            'gemini' => GeminiProvider::class,
            // Note: Together AI provider would be added here when implemented
            // 'together' => TogetherProvider::class,
        ];

        foreach ($providerClasses as $key => $class) {
            if ($this->config['providers'][$key]['enabled'] ?? false) {
                if (!empty($this->config['providers'][$key]['api_key'])) {
                    try {
                        $this->providers[$key] = new $class();
                    } catch (\Exception $e) {
                        Log::warning("Failed to initialize {$key} provider", [
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            }
        }
    }

    public function generateText(string $prompt, array $options = []): array
    {
        $strategy = $options['strategy'] ?? $this->config['aggregation']['strategy'];
        
        switch ($strategy) {
            case 'weighted':
                return $this->generateTextWeighted($prompt, $options);
            case 'consensus':
                return $this->generateTextConsensus($prompt, $options);
            case 'fastest':
                return $this->generateTextFastest($prompt, $options);
            case 'single':
            default:
                return $this->generateTextSingle($prompt, $options);
        }
    }

    private function generateTextWeighted(string $prompt, array $options): array
    {
        $results = [];
        $weights = $this->config['aggregation']['weights'];
        $totalWeight = 0;
        
        foreach ($this->providers as $key => $provider) {
            if ($provider->isAvailable()) {
                $result = $provider->generateText($prompt, $options);
                if (!isset($result['error'])) {
                    $results[] = [
                        'provider' => $key,
                        'text' => $result['text'],
                        'weight' => $weights[$key] ?? 0.2,
                    ];
                    $totalWeight += $weights[$key] ?? 0.2;
                }
            }
        }

        if (empty($results)) {
            return [
                'error' => true,
                'message' => 'No providers available',
            ];
        }

        $aggregatedText = $this->combineResponses($results, $totalWeight);

        return [
            'strategy' => 'weighted',
            'text' => $aggregatedText,
            'providers_used' => array_column($results, 'provider'),
            'individual_responses' => $results,
        ];
    }

    private function generateTextConsensus(string $prompt, array $options): array
    {
        $results = [];
        $threshold = $this->config['aggregation']['consensus_threshold'];
        
        foreach ($this->providers as $key => $provider) {
            if ($provider->isAvailable()) {
                $result = $provider->generateText($prompt, $options);
                if (!isset($result['error'])) {
                    $results[] = [
                        'provider' => $key,
                        'text' => $result['text'],
                    ];
                }
            }
        }

        if (empty($results)) {
            return [
                'error' => true,
                'message' => 'No providers available',
            ];
        }

        $consensusText = $this->findConsensus($results, $threshold);

        return [
            'strategy' => 'consensus',
            'text' => $consensusText,
            'providers_used' => array_column($results, 'provider'),
            'consensus_score' => $this->calculateConsensusScore($results),
            'individual_responses' => $results,
        ];
    }

    private function generateTextFastest(string $prompt, array $options): array
    {
        $promises = [];
        
        foreach ($this->providers as $key => $provider) {
            if ($provider->isAvailable()) {
                $promises[$key] = function() use ($provider, $prompt, $options) {
                    return $provider->generateText($prompt, $options);
                };
            }
        }

        if (empty($promises)) {
            return [
                'error' => true,
                'message' => 'No providers available',
            ];
        }

        foreach ($promises as $key => $promise) {
            $result = $promise();
            if (!isset($result['error'])) {
                return [
                    'strategy' => 'fastest',
                    'text' => $result['text'],
                    'provider' => $key,
                    'response_time' => $result['metadata']['response_time'] ?? null,
                ];
            }
        }

        return [
            'error' => true,
            'message' => 'All providers failed',
        ];
    }

    private function generateTextSingle(string $prompt, array $options): array
    {
        $providerKey = $options['provider'] ?? $this->config['default_provider'];
        
        if (!isset($this->providers[$providerKey])) {
            return [
                'error' => true,
                'message' => "Provider {$providerKey} not available",
            ];
        }

        $result = $this->providers[$providerKey]->generateText($prompt, $options);
        
        return array_merge($result, [
            'strategy' => 'single',
        ]);
    }

    public function generateEmbedding(string $text, array $options = []): array
    {
        $embeddingProviders = ['huggingface', 'cohere', 'gemini'];
        $results = [];
        
        foreach ($embeddingProviders as $key) {
            if (isset($this->providers[$key]) && $this->providers[$key]->isAvailable()) {
                $result = $this->providers[$key]->generateEmbedding($text);
                if (!isset($result['error'])) {
                    $results[] = $result;
                }
            }
        }

        if (empty($results)) {
            return [
                'error' => true,
                'message' => 'No embedding providers available',
            ];
        }

        return $this->averageEmbeddings($results);
    }

    public function analyzeImage(string $imageData, string $prompt, array $options = []): array
    {
        $visionProviders = ['claude', 'groq', 'gemini'];
        $results = [];
        
        foreach ($visionProviders as $key) {
            if (isset($this->providers[$key]) && $this->providers[$key]->isAvailable()) {
                $result = $this->providers[$key]->analyzeImage($imageData, $prompt);
                if (!isset($result['error'])) {
                    $results[] = [
                        'provider' => $key,
                        'analysis' => $result['analysis'],
                    ];
                }
            }
        }

        if (empty($results)) {
            return [
                'error' => true,
                'message' => 'No vision providers available',
            ];
        }

        return [
            'analyses' => $results,
            'combined_analysis' => $this->combineAnalyses($results),
        ];
    }

    public function getAvailableProviders(): array
    {
        $available = [];
        
        foreach ($this->providers as $key => $provider) {
            if ($provider->isAvailable()) {
                $available[$key] = [
                    'name' => $provider->getName(),
                    'models' => $provider->getModelInfo(),
                    'rate_limit' => $provider->getRateLimitInfo(),
                ];
            }
        }

        return $available;
    }

    private function combineResponses(array $results, float $totalWeight): string
    {
        $combinedParts = [];
        
        foreach ($results as $result) {
            $weight = ($result['weight'] / $totalWeight);
            $sentences = preg_split('/(?<=[.!?])\s+/', $result['text']);
            
            foreach ($sentences as $i => $sentence) {
                if (!isset($combinedParts[$i])) {
                    $combinedParts[$i] = [];
                }
                $combinedParts[$i][] = [
                    'text' => $sentence,
                    'weight' => $weight,
                ];
            }
        }

        $finalText = '';
        foreach ($combinedParts as $parts) {
            usort($parts, function($a, $b) {
                return $b['weight'] <=> $a['weight'];
            });
            $finalText .= $parts[0]['text'] . ' ';
        }

        return trim($finalText);
    }

    private function findConsensus(array $results, float $threshold): string
    {
        $allSentences = [];
        
        foreach ($results as $result) {
            $sentences = preg_split('/(?<=[.!?])\s+/', $result['text']);
            foreach ($sentences as $sentence) {
                $key = $this->normalizeSentence($sentence);
                if (!isset($allSentences[$key])) {
                    $allSentences[$key] = [
                        'text' => $sentence,
                        'count' => 0,
                    ];
                }
                $allSentences[$key]['count']++;
            }
        }

        $totalProviders = count($results);
        $consensusSentences = [];
        
        foreach ($allSentences as $data) {
            $agreement = $data['count'] / $totalProviders;
            if ($agreement >= $threshold) {
                $consensusSentences[] = $data['text'];
            }
        }

        return implode(' ', $consensusSentences);
    }

    private function normalizeSentence(string $sentence): string
    {
        return strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $sentence));
    }

    private function calculateConsensusScore(array $results): float
    {
        if (count($results) < 2) {
            return 1.0;
        }

        $similarities = [];
        for ($i = 0; $i < count($results); $i++) {
            for ($j = $i + 1; $j < count($results); $j++) {
                $similarities[] = $this->calculateSimilarity(
                    $results[$i]['text'],
                    $results[$j]['text']
                );
            }
        }

        return array_sum($similarities) / count($similarities);
    }

    private function calculateSimilarity(string $text1, string $text2): float
    {
        $words1 = str_word_count(strtolower($text1), 1);
        $words2 = str_word_count(strtolower($text2), 1);
        
        $intersection = array_intersect($words1, $words2);
        $union = array_unique(array_merge($words1, $words2));
        
        return count($intersection) / count($union);
    }

    private function averageEmbeddings(array $results): array
    {
        $dimensions = $results[0]['dimensions'] ?? 0;
        $averaged = array_fill(0, $dimensions, 0);
        
        foreach ($results as $result) {
            foreach ($result['embedding'] as $i => $value) {
                $averaged[$i] += $value / count($results);
            }
        }

        return [
            'embedding' => $averaged,
            'dimensions' => $dimensions,
            'providers_used' => array_column($results, 'provider'),
        ];
    }

    private function combineAnalyses(array $results): string
    {
        $combined = "Combined Analysis from Multiple AI Providers:\n\n";
        
        foreach ($results as $result) {
            $combined .= "**{$result['provider']}**: {$result['analysis']}\n\n";
        }

        return $combined;
    }
}
<?php

namespace App\Services\AI\Providers;

use App\Services\AI\Contracts\AIProviderInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class HuggingFaceProvider implements AIProviderInterface
{
    private Client $client;
    private array $config;
    private array $rateLimitInfo = [
        'limit' => 100,
        'remaining' => 100,
        'reset' => null,
    ];

    public function __construct()
    {
        $this->config = config('ai.providers.huggingface');
        $this->client = new Client([
            'timeout' => $this->config['timeout'],
            'verify' => false, // Disable SSL verification for Windows compatibility
            'headers' => [
                'Authorization' => 'Bearer ' . $this->config['api_key'],
                'Content-Type' => 'application/json',
            ],
        ]);
    }

    public function generateText(string $prompt, array $options = []): array
    {
        try {
            $model = $options['model'] ?? $this->config['models']['text'];
            $cacheKey = 'hf_text_' . md5($prompt . json_encode($options));
            
            if (config('ai.cache.enabled')) {
                $cached = Cache::get($cacheKey);
                if ($cached) {
                    return $cached;
                }
            }

            $response = $this->client->post($this->config['base_url'] . "/models/{$model}", [
                'json' => [
                    'inputs' => $prompt,
                    'parameters' => array_merge([
                        'max_new_tokens' => $options['max_tokens'] ?? 500,
                        'temperature' => $options['temperature'] ?? 0.7,
                        'top_p' => $options['top_p'] ?? 0.95,
                        'do_sample' => true,
                    ], $options['parameters'] ?? []),
                ],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);
            
            $result = [
                'provider' => 'huggingface',
                'model' => $model,
                'text' => $data[0]['generated_text'] ?? $data['generated_text'] ?? '',
                'usage' => [
                    'prompt_tokens' => strlen($prompt) / 4,
                    'completion_tokens' => strlen($data[0]['generated_text'] ?? '') / 4,
                ],
                'metadata' => [
                    'response_time' => microtime(true) - ($_SERVER['REQUEST_TIME_FLOAT'] ?? microtime(true)),
                ],
            ];

            if (config('ai.cache.enabled')) {
                Cache::put($cacheKey, $result, config('ai.cache.ttl'));
            }

            $this->updateRateLimitInfo($response);

            return $result;
        } catch (RequestException $e) {
            Log::error('HuggingFace API Error', [
                'error' => $e->getMessage(),
                'response' => $e->hasResponse() ? $e->getResponse()->getBody()->getContents() : null,
            ]);
            
            return [
                'provider' => 'huggingface',
                'error' => true,
                'message' => $e->getMessage(),
            ];
        }
    }

    public function generateEmbedding(string $text): array
    {
        try {
            $model = $this->config['models']['embedding'];
            $cacheKey = 'hf_embed_' . md5($text);
            
            if (config('ai.cache.enabled')) {
                $cached = Cache::get($cacheKey);
                if ($cached) {
                    return $cached;
                }
            }

            $response = $this->client->post($this->config['base_url'] . "/models/{$model}", [
                'json' => [
                    'inputs' => $text,
                ],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);
            
            $result = [
                'provider' => 'huggingface',
                'model' => $model,
                'embedding' => $data,
                'dimensions' => count($data),
            ];

            if (config('ai.cache.enabled')) {
                Cache::put($cacheKey, $result, config('ai.cache.ttl'));
            }

            return $result;
        } catch (RequestException $e) {
            Log::error('HuggingFace Embedding Error', [
                'error' => $e->getMessage(),
            ]);
            
            return [
                'provider' => 'huggingface',
                'error' => true,
                'message' => $e->getMessage(),
            ];
        }
    }

    public function analyzeImage(string $imageData, string $prompt): array
    {
        return [
            'provider' => 'huggingface',
            'error' => true,
            'message' => 'Image analysis not supported by HuggingFace provider in free tier',
        ];
    }

    public function streamText(string $prompt, callable $callback, array $options = []): void
    {
        try {
            $model = $options['model'] ?? $this->config['models']['text'];
            
            $response = $this->client->post($this->config['base_url'] . "/models/{$model}", [
                'json' => [
                    'inputs' => $prompt,
                    'parameters' => array_merge([
                        'max_new_tokens' => $options['max_tokens'] ?? 500,
                        'temperature' => $options['temperature'] ?? 0.7,
                        'stream' => true,
                    ], $options['parameters'] ?? []),
                ],
                'stream' => true,
            ]);

            $body = $response->getBody();
            while (!$body->eof()) {
                $chunk = $body->read(1024);
                $callback($chunk);
            }
        } catch (RequestException $e) {
            Log::error('HuggingFace Stream Error', [
                'error' => $e->getMessage(),
            ]);
            $callback(json_encode(['error' => $e->getMessage()]));
        }
    }

    public function isAvailable(): bool
    {
        try {
            // Skip actual API call for now due to potential SSL issues on Windows
            // Just check if API key is configured
            return !empty($this->config['api_key']) && $this->config['api_key'] !== 'your_key_here';
        } catch (\Exception $e) {
            \Log::warning("HuggingFace availability check failed: " . $e->getMessage());
            return false;
        }
    }

    public function getName(): string
    {
        return 'HuggingFace';
    }

    public function getModelInfo(): array
    {
        return [
            'text_models' => [
                'meta-llama/Llama-3.2-3B-Instruct',
                'mistralai/Mistral-7B-Instruct-v0.3',
                'google/flan-t5-xxl',
            ],
            'embedding_models' => [
                'sentence-transformers/all-MiniLM-L6-v2',
                'sentence-transformers/all-mpnet-base-v2',
            ],
            'current_text_model' => $this->config['models']['text'],
            'current_embedding_model' => $this->config['models']['embedding'],
        ];
    }

    public function getRateLimitInfo(): array
    {
        return $this->rateLimitInfo;
    }

    private function updateRateLimitInfo($response): void
    {
        $headers = $response->getHeaders();
        
        if (isset($headers['X-RateLimit-Limit'])) {
            $this->rateLimitInfo['limit'] = (int) $headers['X-RateLimit-Limit'][0];
        }
        
        if (isset($headers['X-RateLimit-Remaining'])) {
            $this->rateLimitInfo['remaining'] = (int) $headers['X-RateLimit-Remaining'][0];
        }
        
        if (isset($headers['X-RateLimit-Reset'])) {
            $this->rateLimitInfo['reset'] = (int) $headers['X-RateLimit-Reset'][0];
        }
    }
}
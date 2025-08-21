<?php

namespace App\Services\AI\Providers;

use App\Services\AI\Contracts\AIProviderInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class CohereProvider implements AIProviderInterface
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
        $this->config = config('ai.providers.cohere');
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
            $cacheKey = 'cohere_text_' . md5($prompt . json_encode($options));
            
            if (config('ai.cache.enabled')) {
                $cached = Cache::get($cacheKey);
                if ($cached) {
                    return $cached;
                }
            }

            $response = $this->client->post($this->config['base_url'] . '/generate', [
                'json' => [
                    'model' => $model,
                    'prompt' => $prompt,
                    'max_tokens' => $options['max_tokens'] ?? 500,
                    'temperature' => $options['temperature'] ?? 0.7,
                    'k' => $options['top_k'] ?? 0,
                    'p' => $options['top_p'] ?? 0.95,
                    'stop_sequences' => $options['stop'] ?? [],
                    'return_likelihoods' => 'NONE',
                ],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);
            
            $result = [
                'provider' => 'cohere',
                'model' => $model,
                'text' => $data['generations'][0]['text'] ?? '',
                'usage' => [
                    'prompt_tokens' => $data['meta']['billed_units']['input_tokens'] ?? 0,
                    'completion_tokens' => $data['meta']['billed_units']['output_tokens'] ?? 0,
                ],
                'metadata' => [
                    'response_time' => microtime(true) - ($_SERVER['REQUEST_TIME_FLOAT'] ?? microtime(true)),
                    'finish_reason' => $data['generations'][0]['finish_reason'] ?? null,
                ],
            ];

            if (config('ai.cache.enabled')) {
                Cache::put($cacheKey, $result, config('ai.cache.ttl'));
            }

            $this->updateRateLimitInfo($response);

            return $result;
        } catch (RequestException $e) {
            Log::error('Cohere API Error', [
                'error' => $e->getMessage(),
                'response' => $e->hasResponse() ? $e->getResponse()->getBody()->getContents() : null,
            ]);
            
            return [
                'provider' => 'cohere',
                'error' => true,
                'message' => $e->getMessage(),
            ];
        }
    }

    public function generateEmbedding(string $text): array
    {
        try {
            $model = $this->config['models']['embedding'];
            $cacheKey = 'cohere_embed_' . md5($text);
            
            if (config('ai.cache.enabled')) {
                $cached = Cache::get($cacheKey);
                if ($cached) {
                    return $cached;
                }
            }

            $response = $this->client->post($this->config['base_url'] . '/embed', [
                'json' => [
                    'model' => $model,
                    'texts' => [$text],
                    'input_type' => 'search_document',
                ],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);
            
            $result = [
                'provider' => 'cohere',
                'model' => $model,
                'embedding' => $data['embeddings'][0] ?? [],
                'dimensions' => count($data['embeddings'][0] ?? []),
                'metadata' => [
                    'billed_units' => $data['meta']['billed_units'] ?? [],
                ],
            ];

            if (config('ai.cache.enabled')) {
                Cache::put($cacheKey, $result, config('ai.cache.ttl'));
            }

            return $result;
        } catch (RequestException $e) {
            Log::error('Cohere Embedding Error', [
                'error' => $e->getMessage(),
            ]);
            
            return [
                'provider' => 'cohere',
                'error' => true,
                'message' => $e->getMessage(),
            ];
        }
    }

    public function analyzeImage(string $imageData, string $prompt): array
    {
        return [
            'provider' => 'cohere',
            'error' => true,
            'message' => 'Image analysis not supported by Cohere provider',
        ];
    }

    public function streamText(string $prompt, callable $callback, array $options = []): void
    {
        try {
            $model = $options['model'] ?? $this->config['models']['text'];
            
            $response = $this->client->post($this->config['base_url'] . '/generate', [
                'json' => [
                    'model' => $model,
                    'prompt' => $prompt,
                    'max_tokens' => $options['max_tokens'] ?? 500,
                    'temperature' => $options['temperature'] ?? 0.7,
                    'stream' => true,
                ],
                'stream' => true,
            ]);

            $body = $response->getBody();
            $buffer = '';
            
            while (!$body->eof()) {
                $chunk = $body->read(1024);
                $buffer .= $chunk;
                
                while (($pos = strpos($buffer, "\n")) !== false) {
                    $line = substr($buffer, 0, $pos);
                    $buffer = substr($buffer, $pos + 1);
                    
                    if (!empty($line)) {
                        $json = json_decode($line, true);
                        if (isset($json['text'])) {
                            $callback($json['text']);
                        }
                    }
                }
            }
        } catch (RequestException $e) {
            Log::error('Cohere Stream Error', [
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
            \Log::warning("Cohere availability check failed: " . $e->getMessage());
            return false;
        }
    }

    public function getName(): string
    {
        return 'Cohere';
    }

    public function getModelInfo(): array
    {
        return [
            'text_models' => [
                'command-r',
                'command-r-plus',
                'command',
                'command-light',
            ],
            'embedding_models' => [
                'embed-english-v3.0',
                'embed-multilingual-v3.0',
                'embed-english-light-v3.0',
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
        
        if (isset($headers['X-API-Requests-Limit'])) {
            $this->rateLimitInfo['limit'] = (int) $headers['X-API-Requests-Limit'][0];
        }
        
        if (isset($headers['X-API-Requests-Remaining'])) {
            $this->rateLimitInfo['remaining'] = (int) $headers['X-API-Requests-Remaining'][0];
        }
        
        if (isset($headers['X-API-Requests-Reset'])) {
            $this->rateLimitInfo['reset'] = (int) $headers['X-API-Requests-Reset'][0];
        }
    }
}
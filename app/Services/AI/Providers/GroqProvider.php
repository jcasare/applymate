<?php

namespace App\Services\AI\Providers;

use App\Services\AI\Contracts\AIProviderInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class GroqProvider implements AIProviderInterface
{
    private Client $client;
    private array $config;
    private array $rateLimitInfo = [
        'limit' => 30,
        'remaining' => 30,
        'reset' => null,
    ];

    public function __construct()
    {
        $this->config = config('ai.providers.groq');
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
            $cacheKey = 'groq_text_' . md5($prompt . json_encode($options));
            
            if (config('ai.cache.enabled')) {
                $cached = Cache::get($cacheKey);
                if ($cached) {
                    return $cached;
                }
            }

            $response = $this->client->post($this->config['base_url'] . '/chat/completions', [
                'json' => [
                    'model' => $model,
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => $options['system'] ?? 'You are a helpful AI assistant for job applications.',
                        ],
                        [
                            'role' => 'user',
                            'content' => $prompt,
                        ],
                    ],
                    'max_tokens' => $options['max_tokens'] ?? 1000,
                    'temperature' => $options['temperature'] ?? 0.7,
                    'top_p' => $options['top_p'] ?? 0.95,
                    'stream' => false,
                ],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);
            
            $result = [
                'provider' => 'groq',
                'model' => $model,
                'text' => $data['choices'][0]['message']['content'] ?? '',
                'usage' => $data['usage'] ?? [],
                'metadata' => [
                    'response_time' => microtime(true) - ($_SERVER['REQUEST_TIME_FLOAT'] ?? microtime(true)),
                    'finish_reason' => $data['choices'][0]['finish_reason'] ?? null,
                ],
            ];

            if (config('ai.cache.enabled')) {
                Cache::put($cacheKey, $result, config('ai.cache.ttl'));
            }

            $this->updateRateLimitInfo($response);

            return $result;
        } catch (RequestException $e) {
            Log::error('Groq API Error', [
                'error' => $e->getMessage(),
                'response' => $e->hasResponse() ? $e->getResponse()->getBody()->getContents() : null,
            ]);
            
            return [
                'provider' => 'groq',
                'error' => true,
                'message' => $e->getMessage(),
            ];
        }
    }

    public function generateEmbedding(string $text): array
    {
        return [
            'provider' => 'groq',
            'error' => true,
            'message' => 'Embedding generation not supported by Groq provider',
        ];
    }

    public function analyzeImage(string $imageData, string $prompt): array
    {
        try {
            $model = $this->config['models']['vision'];
            $cacheKey = 'groq_vision_' . md5($imageData . $prompt);
            
            if (config('ai.cache.enabled')) {
                $cached = Cache::get($cacheKey);
                if ($cached) {
                    return $cached;
                }
            }

            $response = $this->client->post($this->config['base_url'] . '/chat/completions', [
                'json' => [
                    'model' => $model,
                    'messages' => [
                        [
                            'role' => 'user',
                            'content' => [
                                [
                                    'type' => 'text',
                                    'text' => $prompt,
                                ],
                                [
                                    'type' => 'image_url',
                                    'image_url' => [
                                        'url' => "data:image/jpeg;base64,{$imageData}",
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'max_tokens' => 1000,
                ],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);
            
            $result = [
                'provider' => 'groq',
                'model' => $model,
                'analysis' => $data['choices'][0]['message']['content'] ?? '',
                'usage' => $data['usage'] ?? [],
            ];

            if (config('ai.cache.enabled')) {
                Cache::put($cacheKey, $result, config('ai.cache.ttl'));
            }

            return $result;
        } catch (RequestException $e) {
            Log::error('Groq Vision Error', [
                'error' => $e->getMessage(),
            ]);
            
            return [
                'provider' => 'groq',
                'error' => true,
                'message' => $e->getMessage(),
            ];
        }
    }

    public function streamText(string $prompt, callable $callback, array $options = []): void
    {
        try {
            $model = $options['model'] ?? $this->config['models']['text'];
            
            $response = $this->client->post($this->config['base_url'] . '/chat/completions', [
                'json' => [
                    'model' => $model,
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => $options['system'] ?? 'You are a helpful AI assistant.',
                        ],
                        [
                            'role' => 'user',
                            'content' => $prompt,
                        ],
                    ],
                    'max_tokens' => $options['max_tokens'] ?? 1000,
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
                    
                    if (strpos($line, 'data: ') === 0) {
                        $data = substr($line, 6);
                        if ($data !== '[DONE]') {
                            $json = json_decode($data, true);
                            if (isset($json['choices'][0]['delta']['content'])) {
                                $callback($json['choices'][0]['delta']['content']);
                            }
                        }
                    }
                }
            }
        } catch (RequestException $e) {
            Log::error('Groq Stream Error', [
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
            \Log::warning("Groq availability check failed: " . $e->getMessage());
            return false;
        }
    }

    public function getName(): string
    {
        return 'Groq';
    }

    public function getModelInfo(): array
    {
        return [
            'text_models' => [
                'llama-3.1-8b-instant',
                'llama-3.1-70b-versatile',
                'mixtral-8x7b-32768',
                'gemma2-9b-it',
            ],
            'vision_models' => [
                'llava-v1.5-7b-4096-preview',
            ],
            'current_text_model' => $this->config['models']['text'],
            'current_vision_model' => $this->config['models']['vision'] ?? null,
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
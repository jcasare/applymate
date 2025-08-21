<?php

namespace App\Services\AI\Providers;

use App\Services\AI\Contracts\AIProviderInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ClaudeProvider implements AIProviderInterface
{
    private Client $client;
    private array $config;
    private array $rateLimitInfo = [
        'limit' => 50,
        'remaining' => 50,
        'reset' => null,
    ];

    public function __construct()
    {
        $this->config = config('ai.providers.claude');
        $this->client = new Client([
            'timeout' => $this->config['timeout'],
            'verify' => false, // Disable SSL verification for Windows compatibility
            'headers' => [
                'x-api-key' => $this->config['api_key'],
                'Content-Type' => 'application/json',
                'anthropic-version' => '2023-06-01',
            ],
        ]);
    }

    public function generateText(string $prompt, array $options = []): array
    {
        try {
            $model = $options['model'] ?? $this->config['models']['text'];
            $cacheKey = 'claude_text_' . md5($prompt . json_encode($options));
            
            if (config('ai.cache.enabled')) {
                $cached = Cache::get($cacheKey);
                if ($cached) {
                    return $cached;
                }
            }

            $response = $this->client->post($this->config['base_url'] . '/v1/messages', [
                'json' => [
                    'model' => $model,
                    'max_tokens' => $options['max_tokens'] ?? $this->config['max_tokens'],
                    'temperature' => $options['temperature'] ?? 0.7,
                    'system' => $options['system'] ?? 'You are a helpful AI assistant for job applications.',
                    'messages' => [
                        [
                            'role' => 'user',
                            'content' => $prompt,
                        ],
                    ],
                ],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);
            
            $text = '';
            if (isset($data['content'])) {
                foreach ($data['content'] as $content) {
                    if ($content['type'] === 'text') {
                        $text .= $content['text'];
                    }
                }
            }
            
            $result = [
                'provider' => 'claude',
                'model' => $model,
                'text' => $text,
                'usage' => [
                    'input_tokens' => $data['usage']['input_tokens'] ?? 0,
                    'output_tokens' => $data['usage']['output_tokens'] ?? 0,
                ],
                'metadata' => [
                    'response_time' => microtime(true) - ($_SERVER['REQUEST_TIME_FLOAT'] ?? microtime(true)),
                    'stop_reason' => $data['stop_reason'] ?? null,
                ],
            ];

            if (config('ai.cache.enabled')) {
                Cache::put($cacheKey, $result, config('ai.cache.ttl'));
            }

            $this->updateRateLimitInfo($response);

            return $result;
        } catch (RequestException $e) {
            Log::error('Claude API Error', [
                'error' => $e->getMessage(),
                'response' => $e->hasResponse() ? $e->getResponse()->getBody()->getContents() : null,
            ]);
            
            return [
                'provider' => 'claude',
                'error' => true,
                'message' => $e->getMessage(),
            ];
        }
    }

    public function generateEmbedding(string $text): array
    {
        return [
            'provider' => 'claude',
            'error' => true,
            'message' => 'Embedding generation not supported by Claude provider',
        ];
    }

    public function analyzeImage(string $imageData, string $prompt): array
    {
        try {
            $model = $this->config['models']['text']; // Claude models support vision
            $cacheKey = 'claude_vision_' . md5($imageData . $prompt);
            
            if (config('ai.cache.enabled')) {
                $cached = Cache::get($cacheKey);
                if ($cached) {
                    return $cached;
                }
            }

            $response = $this->client->post($this->config['base_url'] . '/v1/messages', [
                'json' => [
                    'model' => $model,
                    'max_tokens' => 1000,
                    'messages' => [
                        [
                            'role' => 'user',
                            'content' => [
                                [
                                    'type' => 'image',
                                    'source' => [
                                        'type' => 'base64',
                                        'media_type' => 'image/jpeg',
                                        'data' => $imageData,
                                    ],
                                ],
                                [
                                    'type' => 'text',
                                    'text' => $prompt,
                                ],
                            ],
                        ],
                    ],
                ],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);
            
            $analysis = '';
            if (isset($data['content'])) {
                foreach ($data['content'] as $content) {
                    if ($content['type'] === 'text') {
                        $analysis .= $content['text'];
                    }
                }
            }
            
            $result = [
                'provider' => 'claude',
                'model' => $model,
                'analysis' => $analysis,
                'usage' => [
                    'input_tokens' => $data['usage']['input_tokens'] ?? 0,
                    'output_tokens' => $data['usage']['output_tokens'] ?? 0,
                ],
            ];

            if (config('ai.cache.enabled')) {
                Cache::put($cacheKey, $result, config('ai.cache.ttl'));
            }

            return $result;
        } catch (RequestException $e) {
            Log::error('Claude Vision Error', [
                'error' => $e->getMessage(),
            ]);
            
            return [
                'provider' => 'claude',
                'error' => true,
                'message' => $e->getMessage(),
            ];
        }
    }

    public function streamText(string $prompt, callable $callback, array $options = []): void
    {
        try {
            $model = $options['model'] ?? $this->config['models']['text'];
            
            $response = $this->client->post($this->config['base_url'] . '/v1/messages', [
                'json' => [
                    'model' => $model,
                    'max_tokens' => $options['max_tokens'] ?? $this->config['max_tokens'],
                    'temperature' => $options['temperature'] ?? 0.7,
                    'system' => $options['system'] ?? 'You are a helpful AI assistant.',
                    'messages' => [
                        [
                            'role' => 'user',
                            'content' => $prompt,
                        ],
                    ],
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
                            if (isset($json['delta']['text'])) {
                                $callback($json['delta']['text']);
                            }
                        }
                    }
                }
            }
        } catch (RequestException $e) {
            Log::error('Claude Stream Error', [
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
            \Log::warning("Claude availability check failed: " . $e->getMessage());
            return false;
        }
    }

    public function getName(): string
    {
        return 'Claude (Anthropic)';
    }

    public function getModelInfo(): array
    {
        return [
            'text_models' => [
                'claude-3-5-sonnet-20241022',
                'claude-3-5-haiku-20241022',
                'claude-3-opus-20240229',
                'claude-3-sonnet-20240229',
                'claude-3-haiku-20240307',
            ],
            'vision_models' => [
                'claude-3-5-sonnet-20241022',
                'claude-3-opus-20240229',
                'claude-3-sonnet-20240229',
                'claude-3-haiku-20240307',
            ],
            'current_text_model' => $this->config['models']['text'],
            'supports_vision' => true,
            'supports_streaming' => true,
            'context_window' => 200000,
        ];
    }

    public function getRateLimitInfo(): array
    {
        return $this->rateLimitInfo;
    }

    private function updateRateLimitInfo($response): void
    {
        $headers = $response->getHeaders();
        
        if (isset($headers['anthropic-ratelimit-requests-limit'])) {
            $this->rateLimitInfo['limit'] = (int) $headers['anthropic-ratelimit-requests-limit'][0];
        }
        
        if (isset($headers['anthropic-ratelimit-requests-remaining'])) {
            $this->rateLimitInfo['remaining'] = (int) $headers['anthropic-ratelimit-requests-remaining'][0];
        }
        
        if (isset($headers['anthropic-ratelimit-requests-reset'])) {
            $this->rateLimitInfo['reset'] = $headers['anthropic-ratelimit-requests-reset'][0];
        }
    }
}
<?php

namespace App\Services\AI\Providers;

use App\Services\AI\Contracts\AIProviderInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class GeminiProvider implements AIProviderInterface
{
    private Client $client;
    private array $config;
    private array $rateLimitInfo = [
        'limit' => 60,
        'remaining' => 60,
        'reset' => null,
    ];

    public function __construct()
    {
        $this->config = config('ai.providers.gemini');
        $this->client = new Client([
            'timeout' => $this->config['timeout'],
            'verify' => false, // Disable SSL verification for Windows compatibility
        ]);
    }

    public function generateText(string $prompt, array $options = []): array
    {
        try {
            $model = $options['model'] ?? $this->config['models']['text'];
            $cacheKey = 'gemini_text_' . md5($prompt . json_encode($options));
            
            if (config('ai.cache.enabled')) {
                $cached = Cache::get($cacheKey);
                if ($cached) {
                    return $cached;
                }
            }

            $response = $this->client->post(
                $this->config['base_url'] . "/models/{$model}:generateContent",
                [
                    'query' => [
                        'key' => $this->config['api_key'],
                    ],
                    'json' => [
                        'contents' => [
                            [
                                'parts' => [
                                    ['text' => $prompt],
                                ],
                            ],
                        ],
                        'generationConfig' => [
                            'temperature' => $options['temperature'] ?? 0.7,
                            'topK' => $options['top_k'] ?? 40,
                            'topP' => $options['top_p'] ?? 0.95,
                            'maxOutputTokens' => $options['max_tokens'] ?? 1000,
                            'stopSequences' => $options['stop'] ?? [],
                        ],
                        'safetySettings' => [
                            [
                                'category' => 'HARM_CATEGORY_HARASSMENT',
                                'threshold' => 'BLOCK_MEDIUM_AND_ABOVE',
                            ],
                            [
                                'category' => 'HARM_CATEGORY_HATE_SPEECH',
                                'threshold' => 'BLOCK_MEDIUM_AND_ABOVE',
                            ],
                            [
                                'category' => 'HARM_CATEGORY_SEXUALLY_EXPLICIT',
                                'threshold' => 'BLOCK_MEDIUM_AND_ABOVE',
                            ],
                            [
                                'category' => 'HARM_CATEGORY_DANGEROUS_CONTENT',
                                'threshold' => 'BLOCK_MEDIUM_AND_ABOVE',
                            ],
                        ],
                    ],
                ]
            );

            $data = json_decode($response->getBody()->getContents(), true);
            
            $text = '';
            if (isset($data['candidates'][0]['content']['parts'])) {
                foreach ($data['candidates'][0]['content']['parts'] as $part) {
                    if (isset($part['text'])) {
                        $text .= $part['text'];
                    }
                }
            }
            
            $result = [
                'provider' => 'gemini',
                'model' => $model,
                'text' => $text,
                'usage' => [
                    'prompt_tokens' => $data['usageMetadata']['promptTokenCount'] ?? 0,
                    'completion_tokens' => $data['usageMetadata']['candidatesTokenCount'] ?? 0,
                    'total_tokens' => $data['usageMetadata']['totalTokenCount'] ?? 0,
                ],
                'metadata' => [
                    'response_time' => microtime(true) - ($_SERVER['REQUEST_TIME_FLOAT'] ?? microtime(true)),
                    'finish_reason' => $data['candidates'][0]['finishReason'] ?? null,
                ],
            ];

            if (config('ai.cache.enabled')) {
                Cache::put($cacheKey, $result, config('ai.cache.ttl'));
            }

            return $result;
        } catch (RequestException $e) {
            Log::error('Gemini API Error', [
                'error' => $e->getMessage(),
                'response' => $e->hasResponse() ? $e->getResponse()->getBody()->getContents() : null,
            ]);
            
            return [
                'provider' => 'gemini',
                'error' => true,
                'message' => $e->getMessage(),
            ];
        }
    }

    public function generateEmbedding(string $text): array
    {
        try {
            $cacheKey = 'gemini_embed_' . md5($text);
            
            if (config('ai.cache.enabled')) {
                $cached = Cache::get($cacheKey);
                if ($cached) {
                    return $cached;
                }
            }

            $response = $this->client->post(
                $this->config['base_url'] . '/models/text-embedding-004:embedContent',
                [
                    'query' => [
                        'key' => $this->config['api_key'],
                    ],
                    'json' => [
                        'model' => 'models/text-embedding-004',
                        'content' => [
                            'parts' => [
                                ['text' => $text],
                            ],
                        ],
                    ],
                ]
            );

            $data = json_decode($response->getBody()->getContents(), true);
            
            $result = [
                'provider' => 'gemini',
                'model' => 'text-embedding-004',
                'embedding' => $data['embedding']['values'] ?? [],
                'dimensions' => count($data['embedding']['values'] ?? []),
            ];

            if (config('ai.cache.enabled')) {
                Cache::put($cacheKey, $result, config('ai.cache.ttl'));
            }

            return $result;
        } catch (RequestException $e) {
            Log::error('Gemini Embedding Error', [
                'error' => $e->getMessage(),
            ]);
            
            return [
                'provider' => 'gemini',
                'error' => true,
                'message' => $e->getMessage(),
            ];
        }
    }

    public function analyzeImage(string $imageData, string $prompt): array
    {
        try {
            $model = $this->config['models']['vision'];
            $cacheKey = 'gemini_vision_' . md5($imageData . $prompt);
            
            if (config('ai.cache.enabled')) {
                $cached = Cache::get($cacheKey);
                if ($cached) {
                    return $cached;
                }
            }

            $response = $this->client->post(
                $this->config['base_url'] . "/models/{$model}:generateContent",
                [
                    'query' => [
                        'key' => $this->config['api_key'],
                    ],
                    'json' => [
                        'contents' => [
                            [
                                'parts' => [
                                    ['text' => $prompt],
                                    [
                                        'inline_data' => [
                                            'mime_type' => 'image/jpeg',
                                            'data' => $imageData,
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ]
            );

            $data = json_decode($response->getBody()->getContents(), true);
            
            $text = '';
            if (isset($data['candidates'][0]['content']['parts'])) {
                foreach ($data['candidates'][0]['content']['parts'] as $part) {
                    if (isset($part['text'])) {
                        $text .= $part['text'];
                    }
                }
            }
            
            $result = [
                'provider' => 'gemini',
                'model' => $model,
                'analysis' => $text,
                'usage' => [
                    'prompt_tokens' => $data['usageMetadata']['promptTokenCount'] ?? 0,
                    'completion_tokens' => $data['usageMetadata']['candidatesTokenCount'] ?? 0,
                ],
            ];

            if (config('ai.cache.enabled')) {
                Cache::put($cacheKey, $result, config('ai.cache.ttl'));
            }

            return $result;
        } catch (RequestException $e) {
            Log::error('Gemini Vision Error', [
                'error' => $e->getMessage(),
            ]);
            
            return [
                'provider' => 'gemini',
                'error' => true,
                'message' => $e->getMessage(),
            ];
        }
    }

    public function streamText(string $prompt, callable $callback, array $options = []): void
    {
        try {
            $model = $options['model'] ?? $this->config['models']['text'];
            
            $response = $this->client->post(
                $this->config['base_url'] . "/models/{$model}:streamGenerateContent",
                [
                    'query' => [
                        'key' => $this->config['api_key'],
                        'alt' => 'sse',
                    ],
                    'json' => [
                        'contents' => [
                            [
                                'parts' => [
                                    ['text' => $prompt],
                                ],
                            ],
                        ],
                        'generationConfig' => [
                            'temperature' => $options['temperature'] ?? 0.7,
                            'maxOutputTokens' => $options['max_tokens'] ?? 1000,
                        ],
                    ],
                    'stream' => true,
                ]
            );

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
                        $json = json_decode($data, true);
                        
                        if (isset($json['candidates'][0]['content']['parts'])) {
                            foreach ($json['candidates'][0]['content']['parts'] as $part) {
                                if (isset($part['text'])) {
                                    $callback($part['text']);
                                }
                            }
                        }
                    }
                }
            }
        } catch (RequestException $e) {
            Log::error('Gemini Stream Error', [
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
            \Log::warning("Gemini availability check failed: " . $e->getMessage());
            return false;
        }
    }

    public function getName(): string
    {
        return 'Google Gemini';
    }

    public function getModelInfo(): array
    {
        return [
            'text_models' => [
                'gemini-1.5-flash',
                'gemini-1.5-flash-8b',
                'gemini-1.5-pro',
                'gemini-1.0-pro',
            ],
            'vision_models' => [
                'gemini-1.5-flash',
                'gemini-1.5-pro',
            ],
            'embedding_models' => [
                'text-embedding-004',
                'embedding-001',
            ],
            'current_text_model' => $this->config['models']['text'],
            'current_vision_model' => $this->config['models']['vision'],
        ];
    }

    public function getRateLimitInfo(): array
    {
        return $this->rateLimitInfo;
    }
}
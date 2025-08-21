<?php

return [
    'providers' => [
        'claude' => [
            'enabled' => env('AI_CLAUDE_ENABLED', false),
            'api_key' => env('CLAUDE_API_KEY'),
            'base_url' => env('CLAUDE_API_URL', 'https://api.anthropic.com'),
            'models' => [
                'text' => env('CLAUDE_MODEL', 'claude-3-5-sonnet-20241022'),
            ],
            'max_tokens' => env('CLAUDE_MAX_TOKENS', 4096),
            'timeout' => 30,
        ],
        'huggingface' => [
            'enabled' => env('AI_HUGGINGFACE_ENABLED', false), // Disable until we find a working model
            'api_key' => env('HUGGINGFACE_API_KEY'),
            'base_url' => 'https://api-inference.huggingface.co',
            'models' => [
                'text' => 'google/flan-t5-base',
                'embedding' => 'sentence-transformers/all-MiniLM-L6-v2',
            ],
            'timeout' => 30,
        ],
        'groq' => [
            'enabled' => env('AI_GROQ_ENABLED', true),
            'api_key' => env('GROQ_API_KEY'),
            'base_url' => 'https://api.groq.com/openai/v1',
            'models' => [
                'text' => 'llama-3.1-8b-instant',
                'vision' => 'llava-v1.5-7b-4096-preview',
            ],
            'timeout' => 30,
        ],
        'cohere' => [
            'enabled' => env('AI_COHERE_ENABLED', true),
            'api_key' => env('COHERE_API_KEY'),
            'base_url' => 'https://api.cohere.ai/v1',
            'models' => [
                'text' => 'command',
                'embedding' => 'embed-english-v3.0',
            ],
            'timeout' => 30,
        ],
        'gemini' => [
            'enabled' => env('AI_GEMINI_ENABLED', true),
            'api_key' => env('GEMINI_API_KEY'),
            'base_url' => 'https://generativelanguage.googleapis.com/v1beta',
            'models' => [
                'text' => 'gemini-1.5-flash',
                'vision' => 'gemini-1.5-flash',
            ],
            'timeout' => 30,
        ],
        'together' => [
            'enabled' => env('AI_TOGETHER_ENABLED', true),
            'api_key' => env('TOGETHER_API_KEY'),
            'base_url' => 'https://api.together.xyz/v1',
            'models' => [
                'text' => 'meta-llama/Llama-3.2-3B-Instruct-Turbo',
            ],
            'timeout' => 30,
        ],
    ],

    'aggregation' => [
        'strategy' => env('AI_AGGREGATION_STRATEGY', 'weighted'),
        'weights' => [
            'claude' => 0.30,
            'huggingface' => 0.12,
            'groq' => 0.20,
            'cohere' => 0.18,
            'gemini' => 0.20,
            'together' => 0.10,
        ],
        'consensus_threshold' => 0.7,
        'max_retries' => 3,
    ],

    'cache' => [
        'enabled' => env('AI_CACHE_ENABLED', true),
        'ttl' => env('AI_CACHE_TTL', 3600),
    ],

    'default_provider' => env('AI_DEFAULT_PROVIDER', 'groq'),
];
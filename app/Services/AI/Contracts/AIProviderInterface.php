<?php

namespace App\Services\AI\Contracts;

interface AIProviderInterface
{
    public function generateText(string $prompt, array $options = []): array;
    
    public function generateEmbedding(string $text): array;
    
    public function analyzeImage(string $imageData, string $prompt): array;
    
    public function streamText(string $prompt, callable $callback, array $options = []): void;
    
    public function isAvailable(): bool;
    
    public function getName(): string;
    
    public function getModelInfo(): array;
    
    public function getRateLimitInfo(): array;
}
<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ClaudeAIService
{
    protected string $apiKey;
    protected string $apiUrl;
    protected string $model;
    protected int $maxTokens;

    public function __construct()
    {
        // Legacy service - now replaced by AI aggregation system
        // Only initialize if old config exists for backward compatibility
        $this->apiKey = config('services.claude.api_key');
        $this->apiUrl = config('services.claude.api_url');
        $this->model = config('services.claude.model');
        $this->maxTokens = config('services.claude.max_tokens', 4096);
        
        // If no API key is configured, this service is effectively disabled
        if (empty($this->apiKey)) {
            $this->apiKey = 'disabled';
        }
    }

    public function generateApplicationMaterials(array $jobData, array $profileData): array
    {
        // If API key is disabled, return error immediately
        if ($this->apiKey === 'disabled' || empty($this->apiKey)) {
            return [
                'ats_keywords' => 'Legacy Claude service is disabled. Please use the new AI aggregation system.',
                'resume_summary' => 'Legacy Claude service is disabled. Please use the new AI aggregation system.',
                'resume_experience' => 'Legacy Claude service is disabled. Please use the new AI aggregation system.',
                'cover_letter' => 'Legacy Claude service is disabled. Please use the new AI aggregation system.',
                'linkedin_post' => 'Legacy Claude service is disabled. Please use the new AI aggregation system.',
            ];
        }

        $systemPrompt = $this->getSystemPrompt();
        $userPrompt = $this->buildUserPrompt($jobData, $profileData);

        try {
            $response = Http::withHeaders([
                'x-api-key' => $this->apiKey,
                'anthropic-version' => '2023-06-01',
                'content-type' => 'application/json',
            ])->post($this->apiUrl, [
                'model' => $this->model,
                'max_tokens' => $this->maxTokens,
                'temperature' => 0.7,
                'system' => $systemPrompt,
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => $userPrompt
                    ]
                ]
            ]);

            if (!$response->successful()) {
                Log::error('Claude API error', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                throw new \Exception('Failed to generate application materials');
            }

            $content = $response->json()['content'][0]['text'];
            
            // Extract JSON from the response
            $jsonMatch = [];
            preg_match('/\{.*\}/s', $content, $jsonMatch);
            
            if (empty($jsonMatch)) {
                throw new \Exception('Invalid response format from AI');
            }

            $result = json_decode($jsonMatch[0], true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('Failed to parse AI response');
            }

            return $result;

        } catch (\Exception $e) {
            Log::error('Claude AI Service Error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Return default structure on error
            return [
                'ats_keywords' => 'Error generating keywords. Please try again.',
                'resume_summary' => 'Error generating summary. Please try again.',
                'resume_experience' => 'Error generating experience. Please try again.',
                'cover_letter' => 'Error generating cover letter. Please try again.',
                'linkedin_post' => 'Error generating LinkedIn post. Please try again.',
            ];
        }
    }

    protected function getSystemPrompt(): string
    {
        return "You are an expert career coach, resume strategist, and professional writer. 
You create tailored, ATS-friendly application materials that are authentic, persuasive, and free of generic filler. 
You must return outputs in valid JSON using the format provided.

Style Guidelines:
- Keep resume and cover letter ATS-friendly but human-sounding
- Quantify achievements wherever possible
- Avoid clichés like 'hardworking' or 'responsible for'
- Ensure LinkedIn post is engaging, positive, and share-ready
- Use action verbs for resume bullets
- Make content specific to the role and company";
    }

    protected function buildUserPrompt(array $jobData, array $profileData): string
    {
        $prompt = "Job Title: {$jobData['job_title']}\n";
        $prompt .= "Company Name: {$jobData['company_name']}\n";
        $prompt .= "Job Description:\n{$jobData['job_description']}\n\n";
        
        $prompt .= "Candidate Profile:\n";
        $prompt .= "Name: {$profileData['candidate_name']}\n";
        $prompt .= "Current Role: {$profileData['current_role']}\n";
        $prompt .= "Years of Experience: {$profileData['years_experience']}\n";
        $prompt .= "Key Skills: {$profileData['skills_list']}\n";
        $prompt .= "Career Highlights:\n{$profileData['career_highlights']}\n";
        $prompt .= "Education: {$profileData['education_details']}\n\n";
        
        $prompt .= 'Please return your output in the following JSON format:
{
    "ats_keywords": "Comma-separated list of 15-20 ATS keywords from job description",
    "resume_summary": "Tailored 3-5 sentence professional summary that highlights relevant experience and value proposition",
    "resume_experience": "5-7 bullet points of relevant experience, each starting with action verbs and including quantified achievements where possible",
    "cover_letter": "Personalized, role-specific cover letter (3-4 paragraphs) that connects experience to job requirements",
    "linkedin_post": "50-100 word engaging LinkedIn post about applying for this role, expressing enthusiasm and fit"
}';

        return $prompt;
    }
}
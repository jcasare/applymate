<?php

namespace App\Http\Controllers;

use App\Services\AI\AIAggregatorService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class AIController extends Controller
{
    private AIAggregatorService $aiService;

    public function __construct(AIAggregatorService $aiService)
    {
        $this->aiService = $aiService;
    }

    public function generateText(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'prompt' => 'required|string|min:1|max:10000',
            'strategy' => 'nullable|in:weighted,consensus,fastest,single',
            'provider' => 'nullable|string',
            'max_tokens' => 'nullable|integer|min:10|max:4000',
            'temperature' => 'nullable|numeric|min:0|max:2',
            'top_p' => 'nullable|numeric|min:0|max:1',
            'system' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => true,
                'messages' => $validator->errors(),
            ], 422);
        }

        try {
            $result = $this->aiService->generateText(
                $request->input('prompt'),
                $request->only(['strategy', 'provider', 'max_tokens', 'temperature', 'top_p', 'system'])
            );

            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json([
                'error' => true,
                'message' => 'Failed to generate text: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function generateEmbedding(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'text' => 'required|string|min:1|max:5000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => true,
                'messages' => $validator->errors(),
            ], 422);
        }

        try {
            $result = $this->aiService->generateEmbedding(
                $request->input('text')
            );

            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json([
                'error' => true,
                'message' => 'Failed to generate embedding: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function analyzeImage(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'image' => 'required|string',
            'prompt' => 'required|string|min:1|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => true,
                'messages' => $validator->errors(),
            ], 422);
        }

        try {
            $imageData = $request->input('image');
            
            if (strpos($imageData, 'data:image') === 0) {
                $imageData = explode(',', $imageData)[1];
            }
            
            $result = $this->aiService->analyzeImage(
                $imageData,
                $request->input('prompt')
            );

            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json([
                'error' => true,
                'message' => 'Failed to analyze image: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function getProviders(): JsonResponse
    {
        try {
            $providers = $this->aiService->getAvailableProviders();
            
            return response()->json([
                'providers' => $providers,
                'count' => count($providers),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => true,
                'message' => 'Failed to get providers: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function generateCoverLetter(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'job_title' => 'required|string|max:200',
            'company_name' => 'required|string|max:200',
            'job_description' => 'required|string|max:5000',
            'user_skills' => 'required|string|max:2000',
            'user_experience' => 'required|string|max:3000',
            'tone' => 'nullable|in:professional,friendly,enthusiastic',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => true,
                'messages' => $validator->errors(),
            ], 422);
        }

        $prompt = $this->buildCoverLetterPrompt($request->all());

        try {
            $result = $this->aiService->generateText($prompt, [
                'strategy' => 'weighted',
                'max_tokens' => 1500,
                'temperature' => 0.8,
                'system' => 'You are an expert career counselor and professional writer specializing in creating compelling cover letters.',
            ]);

            return response()->json([
                'cover_letter' => $result['text'],
                'metadata' => $result['providers_used'] ?? [],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => true,
                'message' => 'Failed to generate cover letter: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function optimizeResume(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'resume_content' => 'required|string|max:10000',
            'job_description' => 'required|string|max:5000',
            'optimization_type' => 'nullable|in:keywords,format,both',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => true,
                'messages' => $validator->errors(),
            ], 422);
        }

        $prompt = $this->buildResumeOptimizationPrompt($request->all());

        try {
            $result = $this->aiService->generateText($prompt, [
                'strategy' => 'consensus',
                'max_tokens' => 2000,
                'temperature' => 0.7,
                'system' => 'You are an expert resume writer and ATS optimization specialist.',
            ]);

            return response()->json([
                'optimized_resume' => $result['text'],
                'consensus_score' => $result['consensus_score'] ?? null,
                'providers_used' => $result['providers_used'] ?? [],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => true,
                'message' => 'Failed to optimize resume: ' . $e->getMessage(),
            ], 500);
        }
    }

    private function buildCoverLetterPrompt(array $data): string
    {
        $tone = $data['tone'] ?? 'professional';
        
        return "Write a compelling {$tone} cover letter for the following position:

Job Title: {$data['job_title']}
Company: {$data['company_name']}

Job Description:
{$data['job_description']}

Candidate Skills:
{$data['user_skills']}

Candidate Experience:
{$data['user_experience']}

Please create a well-structured cover letter that:
1. Shows enthusiasm for the role and company
2. Highlights relevant skills and experience
3. Demonstrates understanding of the job requirements
4. Includes specific examples of achievements
5. Maintains a {$tone} tone throughout
6. Follows standard business letter format";
    }

    private function buildResumeOptimizationPrompt(array $data): string
    {
        $type = $data['optimization_type'] ?? 'both';
        
        $prompt = "Optimize the following resume for ATS systems and the specific job description provided:

Current Resume:
{$data['resume_content']}

Target Job Description:
{$data['job_description']}

Optimization Focus: {$type}

Please provide an optimized version that:";

        if ($type === 'keywords' || $type === 'both') {
            $prompt .= "
1. Incorporates relevant keywords from the job description
2. Uses industry-standard terminology
3. Includes quantifiable achievements";
        }

        if ($type === 'format' || $type === 'both') {
            $prompt .= "
4. Uses ATS-friendly formatting
5. Organizes sections logically
6. Ensures clear hierarchy and readability";
        }

        $prompt .= "
7. Maintains truthfulness while highlighting strengths
8. Tailors content specifically to the target position";

        return $prompt;
    }
}
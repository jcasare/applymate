<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Services\AI\AIAggregatorService;
use App\Services\ResumeParserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class ApplicationController extends Controller
{
    protected AIAggregatorService $aiService;
    protected ResumeParserService $resumeParser;

    public function __construct(AIAggregatorService $aiService, ResumeParserService $resumeParser)
    {
        $this->aiService = $aiService;
        $this->resumeParser = $resumeParser;
    }

    public function index(): Response
    {
        $applications = Application::byUser(Auth::id())
            ->recent()
            ->paginate(10);

        return Inertia::render('Applications/Index', [
            'applications' => $applications,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Applications/Create', [
            'user' => Auth::user()->only(['name', 'email']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'job_title' => 'required|string|max:255',
            'company_name' => 'required|string|max:255',
            'job_description' => 'required|string|min:50',
            'candidate_name' => 'string|max:255|nullable',
            'current_role' => 'nullable|string|max:255',
            'years_experience' => 'integer|min:0|max:50|nullable',
            'skills_list' => 'string|nullable',
            'career_highlights' => 'string|min:20|nullable',
            'education_details' => 'string|nullable',
            'use_resume_upload' => 'boolean',
            'resume_file' => 'nullable|file|mimes:pdf,doc,docx|max:10240', // 10MB max
        ]);

        $resumePath = null;
        $resumeData = [];

        // Handle resume upload if provided
        if ($validated['use_resume_upload'] && $request->hasFile('resume_file')) {
            try {
                // Store the resume file in local (private) storage
                $resumePath = $request->file('resume_file')->store('resumes', 'local');
                
                // Parse resume to extract candidate details
                $parseResult = $this->resumeParser->parseResume($request->file('resume_file'));
                
                if ($parseResult['success']) {
                    $resumeData = $parseResult['data'];
                    
                    // Override form data with resume data where available
                    $validated = array_merge($validated, [
                        'candidate_name' => $resumeData['candidate_name'] ?: $validated['candidate_name'],
                        'current_role' => $resumeData['current_role'] ?: $validated['current_role'],
                        'years_experience' => $resumeData['years_experience'] ?: $validated['years_experience'],
                        'skills_list' => $resumeData['skills_list'] ?: $validated['skills_list'],
                        'career_highlights' => $resumeData['career_highlights'] ?: $validated['career_highlights'],
                        'education_details' => $resumeData['education_details'] ?: $validated['education_details'],
                    ]);
                    
                    \Log::info('Resume parsed successfully', [
                        'filename' => $request->file('resume_file')->getClientOriginalName(),
                        'extracted_name' => $resumeData['candidate_name'],
                    ]);
                } else {
                    \Log::warning('Resume parsing failed, using manual input', [
                        'error' => $parseResult['error']
                    ]);
                    
                    // Continue with manual input if parsing fails
                }
                
            } catch (\Exception $e) {
                \Log::error('Resume upload failed', [
                    'error' => $e->getMessage()
                ]);
                
                return back()->with('error', 'Resume upload failed: ' . $e->getMessage())
                            ->withInput();
            }
        }

        // Validate required fields after potential resume parsing
        $requiredFields = ['candidate_name', 'years_experience', 'skills_list', 'career_highlights', 'education_details'];
        foreach ($requiredFields as $field) {
            if (empty($validated[$field])) {
                return back()->with('error', "Please provide {$field} either manually or by uploading a resume.")
                            ->withInput();
            }
        }

        // Generate AI content using multiple providers
        $aiResponse = $this->generateApplicationMaterials($validated);

        // Save application with AI-generated content
        $application = Application::create([
            'user_id' => Auth::id(),
            'resume_path' => $resumePath,
            ...$validated,
            ...$aiResponse,
            'status' => 'generated',
        ]);

        $message = $resumePath 
            ? 'Application materials generated successfully from your resume!'
            : 'Application materials generated successfully!';

        return redirect()->route('applications.show', $application->id)
            ->with('success', $message);
    }

    public function show(Application $application): Response
    {
        // Ensure user owns this application
        if ($application->user_id !== Auth::id()) {
            abort(403);
        }

        return Inertia::render('Applications/Show', [
            'application' => $application->load('user'),
            'has_resume' => !empty($application->resume_path),
        ]);
    }

    public function downloadResume(Application $application)
    {
        // Ensure user owns this application
        if ($application->user_id !== Auth::id()) {
            abort(403);
        }

        if (!$application->resume_path) {
            abort(404, 'No resume found for this application');
        }

        return Storage::disk('local')->download($application->resume_path, 'resume_' . $application->id . '.pdf');
    }

    public function update(Request $request, Application $application)
    {
        // Ensure user owns this application
        if ($application->user_id !== Auth::id()) {
            abort(403);
        }

        $validated = $request->validate([
            'ats_keywords' => 'nullable|string',
            'resume_summary' => 'nullable|string',
            'resume_experience' => 'nullable|string',
            'cover_letter' => 'nullable|string',
            'linkedin_post' => 'nullable|string',
        ]);

        $application->update($validated);

        return back()->with('success', 'Application updated successfully!');
    }

    public function destroy(Application $application)
    {
        // Ensure user owns this application
        if ($application->user_id !== Auth::id()) {
            abort(403);
        }

        $application->delete();

        return redirect()->route('applications.index')
            ->with('success', 'Application deleted successfully!');
    }

    public function regenerate(Request $request, Application $application)
    {
        // Ensure user owns this application
        if ($application->user_id !== Auth::id()) {
            abort(403);
        }

        $validated = $request->validate([
            'strategy' => 'nullable|in:weighted,consensus,fastest,single',
            'sections' => 'nullable|array',
            'sections.*' => 'in:ats_keywords,resume_summary,resume_experience,cover_letter,linkedin_post',
        ]);

        try {
            // Prepare the data for regeneration using existing application data
            $applicationData = [
                'job_title' => $application->job_title,
                'company_name' => $application->company_name,
                'job_description' => $application->job_description,
                'candidate_name' => $application->candidate_name,
                'current_role' => $application->current_role,
                'years_experience' => $application->years_experience,
                'skills_list' => $application->skills_list,
                'career_highlights' => $application->career_highlights,
                'education_details' => $application->education_details,
            ];

            // Generate new content with specified strategy
            $strategy = $validated['strategy'] ?? 'weighted';
            $sectionsToRegenerate = $validated['sections'] ?? ['ats_keywords', 'resume_summary', 'resume_experience', 'cover_letter', 'linkedin_post'];
            
            $aiResponse = $this->generateApplicationMaterials($applicationData, [
                'strategy' => $strategy,
                'sections' => $sectionsToRegenerate,
            ]);

            // Only update the sections that were regenerated
            $updateData = [];
            foreach ($sectionsToRegenerate as $section) {
                if (isset($aiResponse[$section])) {
                    $updateData[$section] = $aiResponse[$section];
                }
            }

            // Update the application with new content
            $application->update($updateData);
            $application->touch(); // Update the updated_at timestamp

            return back()->with('success', 'Application materials regenerated successfully!')
                        ->with('regenerated_sections', $sectionsToRegenerate);

        } catch (\Exception $e) {
            \Log::error('Application Regeneration Error', [
                'application_id' => $application->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return back()->with('error', 'Failed to regenerate application materials. Please try again.');
        }
    }

    public function markAsApplied(Application $application)
    {
        // Ensure user owns this application
        if ($application->user_id !== Auth::id()) {
            abort(403);
        }

        $application->markAsApplied();

        return back()->with('success', 'Application marked as applied!');
    }

    public function export(Application $application, string $format)
    {
        // Ensure user owns this application
        if ($application->user_id !== Auth::id()) {
            abort(403);
        }

        switch ($format) {
            case 'pdf':
                return $this->exportAsPdf($application);
            case 'json':
                return response()->json($application);
            default:
                abort(404);
        }
    }

    protected function generateApplicationMaterials(array $validated, array $options = []): array
    {
        try {
            $strategy = $options['strategy'] ?? 'weighted';
            $sections = $options['sections'] ?? ['ats_keywords', 'resume_summary', 'resume_experience', 'cover_letter', 'linkedin_post'];
            
            $prompt = $this->buildApplicationPrompt($validated, $sections);
            
            $result = $this->aiService->generateText($prompt, [
                'strategy' => $strategy,
                'max_tokens' => 2000,
                'temperature' => 0.7,
                'system' => $this->getSystemPrompt(),
            ]);

            if (isset($result['error'])) {
                throw new \Exception('AI generation failed: ' . $result['message']);
            }

            // Parse the JSON response - improved parsing logic
            $content = $result['text'];
            
            // Log the raw response for debugging
            \Log::info('AI Raw Response', [
                'provider' => $result['provider'] ?? 'unknown',
                'strategy' => $strategy,
                'content' => substr($content, 0, 1000) // First 1000 chars for debugging
            ]);
            
            $parsedResult = $this->parseAIResponse($content);
            
            if (!$parsedResult) {
                throw new \Exception('Failed to parse AI response after multiple attempts');
            }

            return $parsedResult;

        } catch (\Exception $e) {
            \Log::error('AI Application Generation Error', [
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

CRITICAL: You MUST return your response as valid JSON only. No additional text, explanations, or formatting. 
Start your response with { and end with }. Do not use code blocks or markdown formatting.

Style Guidelines:
- Keep resume and cover letter ATS-friendly but human-sounding
- Quantify achievements wherever possible
- Avoid clichés like 'hardworking' or 'responsible for'
- Ensure LinkedIn post is engaging, positive, and share-ready
- Use action verbs for resume bullets
- Make content specific to the role and company

Example response format:
{
    \"ats_keywords\": \"software engineer, python, react, node.js, agile\",
    \"resume_summary\": \"Experienced software engineer with 5+ years...\",
    \"resume_experience\": \"• Led development of web applications...\",
    \"cover_letter\": \"Dear Hiring Manager...\",
    \"linkedin_post\": \"Excited to announce...\"
}";
    }

    protected function buildApplicationPrompt(array $data, array $sections = []): string
    {
        $prompt = "Job Title: {$data['job_title']}\n";
        $prompt .= "Company Name: {$data['company_name']}\n";
        $prompt .= "Job Description:\n{$data['job_description']}\n\n";
        
        $prompt .= "Candidate Profile:\n";
        $prompt .= "Name: {$data['candidate_name']}\n";
        $prompt .= "Current Role: {$data['current_role']}\n";
        $prompt .= "Years of Experience: {$data['years_experience']}\n";
        $prompt .= "Key Skills: {$data['skills_list']}\n";
        $prompt .= "Career Highlights:\n{$data['career_highlights']}\n";
        $prompt .= "Education: {$data['education_details']}\n\n";
        
        // Build JSON format based on requested sections
        if (empty($sections)) {
            $sections = ['ats_keywords', 'resume_summary', 'resume_experience', 'cover_letter', 'linkedin_post'];
        }
        
        $jsonFields = [];
        $sectionDescriptions = [
            'ats_keywords' => 'Comma-separated list of 15-20 ATS keywords from job description',
            'resume_summary' => 'Tailored 3-5 sentence professional summary that highlights relevant experience and value proposition',
            'resume_experience' => '5-7 bullet points of relevant experience, each starting with action verbs and including quantified achievements where possible',
            'cover_letter' => 'Personalized, role-specific cover letter (3-4 paragraphs) that connects experience to job requirements',
            'linkedin_post' => '50-100 word engaging LinkedIn post about applying for this role, expressing enthusiasm and fit'
        ];
        
        foreach ($sections as $section) {
            if (isset($sectionDescriptions[$section])) {
                $jsonFields[] = "    \"$section\": \"{$sectionDescriptions[$section]}\"";
            }
        }
        
        $prompt .= "Please return your output in the following JSON format:\n{\n";
        $prompt .= implode(",\n", $jsonFields);
        $prompt .= "\n}";

        return $prompt;
    }

    protected function exportAsPdf(Application $application)
    {
        // This would use a PDF library like DomPDF or Snappy
        // For now, return a simple HTML view
        $html = view('exports.application', compact('application'))->render();
        
        return response($html)
            ->header('Content-Type', 'text/html')
            ->header('Content-Disposition', 'attachment; filename="application-' . $application->id . '.html"');
    }

    protected function parseAIResponse(string $content): ?array
    {
        // Strategy 1: Try to find JSON within the response
        $jsonMatch = [];
        if (preg_match('/\{[^{}]*(?:"[^"]*"[^{}]*)*\}/s', $content, $jsonMatch)) {
            $decoded = json_decode($jsonMatch[0], true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return $decoded;
            }
        }

        // Strategy 2: Try to find JSON within code blocks
        if (preg_match('/```json\s*(\{.*?\})\s*```/s', $content, $jsonMatch)) {
            $decoded = json_decode($jsonMatch[1], true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return $decoded;
            }
        }

        // Strategy 3: Try to find JSON without code blocks
        if (preg_match('/```\s*(\{.*?\})\s*```/s', $content, $jsonMatch)) {
            $decoded = json_decode($jsonMatch[1], true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return $decoded;
            }
        }

        // Strategy 4: Extract content between first { and last }
        $startPos = strpos($content, '{');
        $endPos = strrpos($content, '}');
        if ($startPos !== false && $endPos !== false && $endPos > $startPos) {
            $jsonCandidate = substr($content, $startPos, $endPos - $startPos + 1);
            $decoded = json_decode($jsonCandidate, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return $decoded;
            }
        }

        // Strategy 5: Try to extract structured text and convert to JSON
        $fallbackData = $this->extractStructuredContent($content);
        if (!empty($fallbackData)) {
            return $fallbackData;
        }

        return null;
    }

    protected function extractStructuredContent(string $content): array
    {
        $sections = [
            'ats_keywords' => 'keywords',
            'resume_summary' => 'summary',
            'resume_experience' => 'experience',
            'cover_letter' => 'cover letter',
            'linkedin_post' => 'linkedin'
        ];

        $extractedData = [];
        
        foreach ($sections as $key => $pattern) {
            // Try to find content after section headers
            $patterns = [
                '/\*\*' . preg_quote($pattern, '/') . '\*\*:?\s*([^\*]+?)(?=\*\*|\Z)/is',
                '/' . preg_quote($pattern, '/') . ':?\s*([^\n]+)/i',
                '/^' . preg_quote($pattern, '/') . ':?\s*(.+?)$/im'
            ];
            
            foreach ($patterns as $regex) {
                if (preg_match($regex, $content, $matches)) {
                    $extractedData[$key] = trim($matches[1]);
                    break;
                }
            }
        }

        // If we found at least 2 sections, return the data
        if (count($extractedData) >= 2) {
            // Fill in missing sections with generic content
            $defaultContent = [
                'ats_keywords' => 'job title, company name, relevant skills, industry terms',
                'resume_summary' => 'Professional with relevant experience seeking new opportunities.',
                'resume_experience' => '• Relevant experience in the field\n• Strong track record of achievements\n• Proven ability to deliver results',
                'cover_letter' => 'Dear Hiring Manager,\n\nI am writing to express my interest in this position...',
                'linkedin_post' => 'Excited to apply for this new opportunity! Looking forward to bringing my skills to the team.'
            ];

            foreach ($defaultContent as $key => $default) {
                if (!isset($extractedData[$key])) {
                    $extractedData[$key] = $default;
                }
            }

            return $extractedData;
        }

        return [];
    }
}
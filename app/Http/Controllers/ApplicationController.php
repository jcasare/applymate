<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Services\ClaudeAIService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class ApplicationController extends Controller
{
    protected ClaudeAIService $claudeService;

    public function __construct(ClaudeAIService $claudeService)
    {
        $this->claudeService = $claudeService;
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
            'candidate_name' => 'required|string|max:255',
            'current_role' => 'nullable|string|max:255',
            'years_experience' => 'required|integer|min:0|max:50',
            'skills_list' => 'required|string',
            'career_highlights' => 'required|string|min:20',
            'education_details' => 'required|string',
        ]);

        // Generate AI content
        $aiResponse = $this->claudeService->generateApplicationMaterials(
            [
                'job_title' => $validated['job_title'],
                'company_name' => $validated['company_name'],
                'job_description' => $validated['job_description'],
            ],
            [
                'candidate_name' => $validated['candidate_name'],
                'current_role' => $validated['current_role'] ?? '',
                'years_experience' => $validated['years_experience'],
                'skills_list' => $validated['skills_list'],
                'career_highlights' => $validated['career_highlights'],
                'education_details' => $validated['education_details'],
            ]
        );

        // Save application with AI-generated content
        $application = Application::create([
            'user_id' => Auth::id(),
            ...$validated,
            ...$aiResponse,
            'status' => 'generated',
        ]);

        return redirect()->route('applications.show', $application->id)
            ->with('success', 'Application materials generated successfully!');
    }

    public function show(Application $application): Response
    {
        // Ensure user owns this application
        if ($application->user_id !== Auth::id()) {
            abort(403);
        }

        return Inertia::render('Applications/Show', [
            'application' => $application->load('user'),
        ]);
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

    protected function exportAsPdf(Application $application)
    {
        // This would use a PDF library like DomPDF or Snappy
        // For now, return a simple HTML view
        $html = view('exports.application', compact('application'))->render();
        
        return response($html)
            ->header('Content-Type', 'text/html')
            ->header('Content-Disposition', 'attachment; filename="application-' . $application->id . '.html"');
    }
}
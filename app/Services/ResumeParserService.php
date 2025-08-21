<?php

namespace App\Services;

use App\Services\AI\AIAggregatorService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ResumeParserService
{
    protected AIAggregatorService $aiService;

    public function __construct(AIAggregatorService $aiService)
    {
        $this->aiService = $aiService;
    }

    public function parseResume(UploadedFile $file): array
    {
        try {
            // Extract text from the resume file
            $resumeText = $this->extractTextFromFile($file);
            
            if (empty($resumeText)) {
                throw new \Exception('Unable to extract text from resume file');
            }

            // Use AI to parse and structure the resume data
            $parsedData = $this->parseResumeWithAI($resumeText);

            return [
                'success' => true,
                'data' => $parsedData,
                'raw_text' => $resumeText
            ];

        } catch (\Exception $e) {
            Log::error('Resume parsing failed', [
                'filename' => $file->getClientOriginalName(),
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    protected function extractTextFromFile(UploadedFile $file): string
    {
        $extension = strtolower($file->getClientOriginalExtension());
        
        switch ($extension) {
            case 'pdf':
                return $this->extractFromPdf($file);
            case 'doc':
            case 'docx':
                return $this->extractFromWord($file);
            default:
                throw new \Exception('Unsupported file format: ' . $extension);
        }
    }

    protected function extractFromPdf(UploadedFile $file): string
    {
        try {
            // Check if pdftotext is available (part of poppler-utils)
            $command = 'pdftotext -layout -nopgbrk "' . $file->getRealPath() . '" -';
            $output = shell_exec($command);
            
            if ($output !== null && !empty(trim($output))) {
                return trim($output);
            }

            // Fallback: try with python pdfplumber if available
            $pythonScript = '
import sys
try:
    import pdfplumber
    with pdfplumber.open(sys.argv[1]) as pdf:
        text = ""
        for page in pdf.pages:
            text += page.extract_text() or ""
    print(text.strip())
except Exception as e:
    print(f"Error: {e}", file=sys.stderr)
    sys.exit(1)
';
            
            $tempScript = tempnam(sys_get_temp_dir(), 'pdf_parser') . '.py';
            file_put_contents($tempScript, $pythonScript);
            
            $command = "python \"$tempScript\" \"" . $file->getRealPath() . "\"";
            $output = shell_exec($command);
            unlink($tempScript);
            
            if ($output !== null && !empty(trim($output))) {
                return trim($output);
            }

            throw new \Exception('Unable to extract text from PDF');

        } catch (\Exception $e) {
            Log::warning('PDF text extraction failed', ['error' => $e->getMessage()]);
            throw new \Exception('Failed to extract text from PDF file');
        }
    }

    protected function extractFromWord(UploadedFile $file): string
    {
        try {
            $extension = strtolower($file->getClientOriginalExtension());
            
            if ($extension === 'docx') {
                return $this->extractFromDocx($file);
            } else {
                // For .doc files, try using antiword if available
                $command = 'antiword "' . $file->getRealPath() . '"';
                $output = shell_exec($command);
                
                if ($output !== null && !empty(trim($output))) {
                    return trim($output);
                }
                
                throw new \Exception('Unable to extract text from DOC file');
            }

        } catch (\Exception $e) {
            Log::warning('Word document text extraction failed', ['error' => $e->getMessage()]);
            throw new \Exception('Failed to extract text from Word document');
        }
    }

    protected function extractFromDocx(UploadedFile $file): string
    {
        try {
            $zip = new \ZipArchive();
            $result = $zip->open($file->getRealPath());
            
            if ($result !== TRUE) {
                throw new \Exception('Unable to open DOCX file');
            }

            $xml = $zip->getFromName('word/document.xml');
            $zip->close();

            if ($xml === false) {
                throw new \Exception('Unable to extract content from DOCX');
            }

            // Parse XML and extract text
            $dom = new \DOMDocument();
            libxml_use_internal_errors(true);
            $dom->loadXML($xml);
            libxml_clear_errors();

            $text = '';
            $textNodes = $dom->getElementsByTagName('t');
            foreach ($textNodes as $textNode) {
                $text .= $textNode->nodeValue . ' ';
            }

            return trim($text);

        } catch (\Exception $e) {
            Log::warning('DOCX text extraction failed', ['error' => $e->getMessage()]);
            throw new \Exception('Failed to extract text from DOCX file');
        }
    }

    protected function parseResumeWithAI(string $resumeText): array
    {
        $prompt = $this->buildResumeParsingPrompt($resumeText);
        
        $result = $this->aiService->generateText($prompt, [
            'strategy' => 'single',
            'provider' => 'groq', // Use Groq for fastest parsing
            'max_tokens' => 1500,
            'temperature' => 0.3, // Lower temperature for more consistent parsing
        ]);

        if (isset($result['error'])) {
            throw new \Exception('AI parsing failed: ' . $result['message']);
        }

        return $this->parseAIResponse($result['text']);
    }

    protected function buildResumeParsingPrompt(string $resumeText): string
    {
        return "Extract and structure the following information from this resume text. Return only valid JSON format.

Resume Text:
{$resumeText}

Extract these details and return in this exact JSON format:
{
    \"candidate_name\": \"Full name of the candidate\",
    \"current_role\": \"Current or most recent job title\",
    \"years_experience\": \"Total years of professional experience (number only)\",
    \"skills_list\": \"Comma-separated list of key technical and professional skills\",
    \"career_highlights\": \"3-5 bullet points of major achievements and experience highlights\",
    \"education_details\": \"Education background including degrees, institutions, and years\",
    \"contact_info\": {
        \"email\": \"Email address if found\",
        \"phone\": \"Phone number if found\",
        \"location\": \"Location/city if mentioned\"
    }
}

Focus on:
- Technical skills, programming languages, frameworks, tools
- Quantifiable achievements (numbers, percentages, dollar amounts)
- Leadership experience and team management
- Education credentials and certifications
- Years of experience in relevant fields

If information is not available, use reasonable defaults or leave as empty string.";
    }

    protected function parseAIResponse(string $content): array
    {
        // Use multiple strategies to extract JSON
        $strategies = [
            function($content) {
                // Strategy 1: Direct JSON parsing
                $decoded = json_decode($content, true);
                return (json_last_error() === JSON_ERROR_NONE) ? $decoded : null;
            },
            function($content) {
                // Strategy 2: Find JSON in code blocks
                if (preg_match('/```(?:json)?\s*(\{.*?\})\s*```/s', $content, $matches)) {
                    $decoded = json_decode($matches[1], true);
                    return (json_last_error() === JSON_ERROR_NONE) ? $decoded : null;
                }
                return null;
            },
            function($content) {
                // Strategy 3: Find JSON between first { and last }
                $start = strpos($content, '{');
                $end = strrpos($content, '}');
                if ($start !== false && $end !== false && $end > $start) {
                    $jsonStr = substr($content, $start, $end - $start + 1);
                    $decoded = json_decode($jsonStr, true);
                    return (json_last_error() === JSON_ERROR_NONE) ? $decoded : null;
                }
                return null;
            }
        ];

        foreach ($strategies as $strategy) {
            $result = $strategy($content);
            if ($result !== null) {
                return $this->validateAndCleanData($result);
            }
        }

        // Fallback: return default structure
        Log::warning('Failed to parse AI resume response', ['content' => substr($content, 0, 500)]);
        
        return [
            'candidate_name' => '',
            'current_role' => '',
            'years_experience' => 0,
            'skills_list' => '',
            'career_highlights' => '',
            'education_details' => '',
            'contact_info' => [
                'email' => '',
                'phone' => '',
                'location' => ''
            ]
        ];
    }

    protected function validateAndCleanData(array $data): array
    {
        // Ensure required fields exist
        $defaults = [
            'candidate_name' => '',
            'current_role' => '',
            'years_experience' => 0,
            'skills_list' => '',
            'career_highlights' => '',
            'education_details' => '',
            'contact_info' => [
                'email' => '',
                'phone' => '',
                'location' => ''
            ]
        ];

        $data = array_merge($defaults, $data);

        // Clean and validate specific fields
        $data['years_experience'] = is_numeric($data['years_experience']) 
            ? (int) $data['years_experience'] 
            : 0;

        // Ensure contact_info is an array
        if (!is_array($data['contact_info'])) {
            $data['contact_info'] = $defaults['contact_info'];
        } else {
            $data['contact_info'] = array_merge($defaults['contact_info'], $data['contact_info']);
        }

        return $data;
    }
}
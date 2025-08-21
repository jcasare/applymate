<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\ResumeParserService;
use App\Services\AI\AIAggregatorService;

class TestResumeParser extends Command
{
    protected $signature = 'resume:test {--text= : Sample resume text to parse}';
    protected $description = 'Test resume parsing functionality';

    protected ResumeParserService $resumeParser;

    public function __construct(ResumeParserService $resumeParser)
    {
        parent::__construct();
        $this->resumeParser = $resumeParser;
    }

    public function handle()
    {
        $this->info('🧪 Testing Resume Parser Service');
        
        $sampleText = $this->option('text') ?: $this->getDefaultResumeText();
        
        $this->info('📋 Parsing resume text...');
        
        try {
            // Use reflection to access protected method for testing
            $reflection = new \ReflectionClass($this->resumeParser);
            $parseMethod = $reflection->getMethod('parseResumeWithAI');
            $parseMethod->setAccessible(true);
            
            $result = $parseMethod->invoke($this->resumeParser, $sampleText);
            
            $this->info('✅ Parsing completed successfully!');
            $this->newLine();
            
            $this->table(
                ['Field', 'Value'],
                collect($result)->map(function ($value, $key) {
                    if (is_array($value)) {
                        return [$key, json_encode($value, JSON_PRETTY_PRINT)];
                    }
                    return [$key, strlen($value) > 50 ? substr($value, 0, 50) . '...' : $value];
                })->toArray()
            );
            
            return Command::SUCCESS;
            
        } catch (\Exception $e) {
            $this->error('❌ Parsing failed: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
    
    private function getDefaultResumeText(): string
    {
        return "
        Sarah Johnson
        Full Stack Developer
        Email: sarah.johnson@email.com | Phone: (555) 987-6543
        Location: San Francisco, CA
        
        PROFESSIONAL SUMMARY:
        Experienced Full Stack Developer with 7+ years building scalable web applications.
        Expertise in React, Node.js, and cloud infrastructure. Passionate about clean code
        and modern development practices.
        
        EXPERIENCE:
        
        Senior Full Stack Developer | TechCorp Inc. | 2021-Present
        - Architected and developed microservices handling 5M+ daily transactions
        - Led migration from monolithic to microservices architecture
        - Reduced infrastructure costs by 35% through optimization
        - Mentored team of 8 junior developers
        
        Full Stack Developer | Innovation Labs | 2018-2021
        - Built real-time collaboration platform serving 50K+ users
        - Implemented CI/CD pipelines reducing deployment time by 70%
        - Developed RESTful APIs and GraphQL endpoints
        
        TECHNICAL SKILLS:
        Languages: JavaScript, TypeScript, Python, Java
        Frontend: React, Vue.js, Angular, HTML5, CSS3, Tailwind
        Backend: Node.js, Express, Django, Spring Boot
        Databases: PostgreSQL, MongoDB, Redis, MySQL
        Cloud: AWS, GCP, Docker, Kubernetes
        
        EDUCATION:
        Master of Computer Science
        Stanford University | 2016-2018
        
        Bachelor of Science in Computer Engineering
        UC Berkeley | 2012-2016
        ";
    }
}
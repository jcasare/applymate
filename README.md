# ApplyMate - AI-Powered Job Application Generator

ApplyMate helps job seekers create tailored application materials in seconds using multiple AI providers including free alternatives to expensive APIs.

## Features

### Core Features
- 🎯 **ATS-Optimized Keywords** - Extract and highlight relevant keywords from job descriptions
- 📝 **Professional Summaries** - Generate tailored resume summaries that match the role
- 💼 **Experience Bullets** - Create impactful, quantified experience bullet points
- ✉️ **Cover Letters** - Generate personalized, compelling cover letters
- 🔗 **LinkedIn Posts** - Create engaging posts to announce your applications
- 📊 **Application History** - Track all your generated applications
- 📥 **Export Options** - Copy to clipboard, export as PDF, or share directly

### AI Features (New!)
- 🤖 **Multiple AI Providers** - Integrates 5+ AI providers with free tiers
- 🔄 **AI Aggregation** - Combines results from multiple AIs for optimal output
- 💰 **Cost-Free Operation** - Uses free API tiers, no expensive subscriptions needed
- 🎨 **Smart Strategy Selection** - Weighted, consensus, or fastest response modes
- 🖼️ **Vision Analysis** - Analyze resume screenshots and job posting images
- 🔍 **Embeddings** - Semantic search and content matching

## Tech Stack

- **Backend**: Laravel 11 (PHP 8.2+)
- **Frontend**: Vue 3 with Inertia.js
- **Styling**: Tailwind CSS
- **Database**: MySQL (or SQLite for development)
- **AI Providers**: 
  - Claude API (Anthropic) - Premium, toggleable
  - HuggingFace (Free) - Open source models
  - Groq (Free tier) - Fast inference
  - Cohere (Free trial) - NLP specialized
  - Google Gemini (Free tier) - Multimodal AI
  - Together AI (Free credits) - Various models

## Requirements

- PHP 8.2 or higher
- Composer
- Node.js 18+ and npm
- MySQL 8.0+ (or SQLite for development)
- At least one AI API key (free options available)

## Installation

### 1. Install Dependencies

```bash
# Install PHP dependencies
composer install

# Install Node dependencies
npm install --legacy-peer-deps
```

### 2. Environment Setup

```bash
# Copy the environment file
cp .env.example .env

# Generate application key
php artisan key:generate
```

### 3. Configure Database

#### Option A: MySQL (Recommended for production)
1. Create a MySQL database named `applymate`
```sql
CREATE DATABASE applymate;
```

2. Update `.env` file:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=applymate
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

#### Option B: SQLite (For quick development)
1. Install PHP SQLite extension if not already installed
2. Update `.env` file:
```env
DB_CONNECTION=sqlite
```
3. Create the SQLite database:
```bash
touch database/database.sqlite
```

### 4. Configure AI Providers

#### Option A: Use Free AI Providers (Recommended for Getting Started)

Get free API keys from any of these providers:
- **HuggingFace**: https://huggingface.co/settings/tokens (Completely free)
- **Groq**: https://console.groq.com/keys (Free tier available)
- **Cohere**: https://dashboard.cohere.com/api-keys (Free trial)
- **Google Gemini**: https://makersuite.google.com/app/apikey (Free tier)
- **Together AI**: https://api.together.xyz/ ($25 free credits)

Add your API keys to `.env`:
```env
# Add any or all of these (at least one required)
HUGGINGFACE_API_KEY=your_key_here
GROQ_API_KEY=your_key_here
COHERE_API_KEY=your_key_here
GEMINI_API_KEY=your_key_here
TOGETHER_API_KEY=your_key_here

# AI Configuration
AI_DEFAULT_PROVIDER=groq
AI_AGGREGATION_STRATEGY=weighted
```

#### Option B: Enable Claude API (Premium)

Claude is now integrated as a toggleable provider alongside the free options:
```env
# Enable Claude (disabled by default to prioritize free providers)
AI_CLAUDE_ENABLED=true
CLAUDE_API_KEY=your_actual_claude_api_key_here
CLAUDE_MODEL=claude-3-5-sonnet-20241022
CLAUDE_MAX_TOKENS=4096
```

Get your Claude API key from: https://console.anthropic.com/

**Note**: When Claude is enabled, it receives 30% weight in aggregated responses due to its advanced capabilities.

### 5. Run Migrations

```bash
php artisan migrate
```

### 6. Build Assets

```bash
# For development
npm run dev

# For production
npm run build
```

### 7. Start the Application

```bash
# Terminal 1: Start Laravel server
php artisan serve

# Terminal 2: Start Vite dev server (for development)
npm run dev
```

Visit `http://localhost:8000` in your browser.

## Usage

1. **Register/Login**: Create an account or log in
2. **Generate Application**: 
   - Click "Generate New Application" from the dashboard
   - Fill in the job details (title, company, description)
   - Add your profile information
   - Click "Generate Application Materials"
3. **View Results**: Review the AI-generated content in organized tabs
4. **Actions**:
   - Copy individual sections to clipboard
   - Export everything as PDF
   - Share LinkedIn post directly
   - Mark as "Applied" to track your applications
5. **History**: View all your past applications in "My Applications"

## Project Structure

```
ApplyMate/
├── app/
│   ├── Http/Controllers/
│   │   ├── ApplicationController.php    # Main application logic
│   │   └── AIController.php            # AI endpoints controller
│   ├── Models/
│   │   └── Application.php              # Application model
│   └── Services/
│       ├── ClaudeAIService.php         # Claude API integration
│       └── AI/
│           ├── AIAggregatorService.php # Multi-provider aggregation
│           ├── Contracts/              # AI interfaces
│           └── Providers/              # Individual AI providers
│               ├── HuggingFaceProvider.php
│               ├── GroqProvider.php
│               ├── CohereProvider.php
│               └── GeminiProvider.php
├── resources/
│   └── js/
│       ├── Pages/
│       │   ├── Dashboard.vue           # Main dashboard
│       │   └── Applications/
│       │       ├── Create.vue          # Generation form
│       │       ├── Index.vue           # Applications list
│       │       └── Show.vue            # View application
│       └── Components/AI/
│           ├── CoverLetterGenerator.vue # AI cover letter tool
│           ├── ResumeOptimizer.vue     # Resume optimization
│           └── AIProviderStatus.vue    # Provider monitoring
├── config/
│   └── ai.php                          # AI configuration
├── database/
│   └── migrations/                     # Database migrations
└── routes/
    ├── web.php                         # Web routes
    └── api.php                         # API routes
```

## Troubleshooting

### Database Connection Issues

**MySQL Access Denied Error:**
- Ensure MySQL is running
- Verify username and password in `.env`
- Grant privileges if needed:
```sql
GRANT ALL PRIVILEGES ON applymate.* TO 'your_user'@'localhost';
FLUSH PRIVILEGES;
```

**SQLite Driver Not Found:**
- Install PHP SQLite extension:
  - Windows: Uncomment `extension=pdo_sqlite` in php.ini
  - Linux: `sudo apt-get install php-sqlite3`
  - Mac: `brew install php-sqlite`

### AI API Issues

#### Free Provider Issues
- **Rate Limits**: Free tiers have rate limits, wait a few minutes if exceeded
- **API Keys**: Ensure at least one provider's API key is configured
- **Provider Status**: Check provider status at `/api/ai/providers`

#### Claude API Issues (Premium)
- Verify your API key is correct
- Check your API usage limits at https://console.anthropic.com/
- Ensure you have sufficient credits

### NPM/Build Issues
- Use `npm install --legacy-peer-deps` if you encounter peer dependency issues
- Clear caches: `npm cache clean --force`
- Delete and reinstall: `rm -rf node_modules package-lock.json && npm install --legacy-peer-deps`

## Production Deployment

### AWS Deployment (Recommended)
- **Laravel**: Deploy on AWS Lambda using Laravel Vapor
- **Database**: Use AWS RDS for MySQL
- **Frontend Assets**: Serve from S3 + CloudFront
- **Environment Variables**: Store in AWS Systems Manager

### Environment Variables for Production
```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com
```

### Build for Production
```bash
# Optimize Laravel
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Build frontend assets
npm run build
```

## AI Aggregation Strategies

ApplyMate intelligently combines responses from multiple AI providers:

### Available Strategies
- **Weighted** (Default): Combines responses with configurable weights for optimal results
- **Consensus**: Only includes content that multiple providers agree on (70% threshold)
- **Fastest**: Returns the first successful response for time-sensitive operations
- **Single**: Uses a specific provider when needed

### API Endpoints

```
POST /api/ai/generate-text      # General text generation
POST /api/ai/cover-letter       # Generate cover letters
POST /api/ai/optimize-resume    # Optimize resumes for ATS
POST /api/ai/analyze-image      # Analyze resume screenshots
GET  /api/ai/providers          # Check provider status
```

## Security Notes

- Never commit `.env` file to version control
- Keep all API keys secure (both free and premium)
- Use HTTPS in production
- Enable CSRF protection (enabled by default)
- Implement rate limiting for API calls
- Regularly update dependencies
- API keys are validated before use
- Responses are cached to minimize API calls

## License

This project is proprietary software. All rights reserved.

## Free API Tier Limits

| Provider | Tier | Limits | Best For |
|----------|------|--------|----------|
| Claude | Premium | 50-4000 req/min | Advanced reasoning, long context |
| HuggingFace | Free | Unlimited (rate limited) | Open source models, embeddings |
| Groq | Free | 30 req/min, 14,400 req/day | Fast inference, chat |
| Cohere | Free Trial | 100 req/min | NLP tasks, embeddings |
| Google Gemini | Free | 60 req/min, 1M tokens/month | Multimodal, vision |
| Together AI | Free Credits | $25 credits | Various models |

## Support

For issues or questions:
- Check the [AI Integration Documentation](AI_INTEGRATION.md)
- Review troubleshooting section above
- Create an issue in the repository

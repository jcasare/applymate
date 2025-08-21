# AI Integration Documentation for ApplyMate

## Overview
ApplyMate now integrates multiple AI providers with free tiers to provide comprehensive AI-powered features without requiring expensive API subscriptions. The system intelligently aggregates results from multiple providers to deliver optimal results.

## Integrated AI Providers

### 1. **Claude (Anthropic)** (Premium)
- **Models**: Claude 3.5 Sonnet, Claude 3 Opus, Claude 3 Haiku
- **Features**: Advanced text generation, vision analysis, long context
- **Rate Limit**: 50-4000 requests/minute (plan dependent)
- **Get API Key**: https://console.anthropic.com/
- **Default**: Disabled (enable with `AI_CLAUDE_ENABLED=true`)

### 2. **Hugging Face** (Free Tier)
- **Models**: Llama 3.2, Mistral, Flan-T5
- **Features**: Text generation, embeddings
- **Rate Limit**: 100 requests/hour
- **Get API Key**: https://huggingface.co/settings/tokens

### 3. **Groq** (Free Tier)
- **Models**: Llama 3.1, Mixtral, Gemma
- **Features**: Fast text generation, vision analysis
- **Rate Limit**: 30 requests/minute
- **Get API Key**: https://console.groq.com/keys

### 4. **Cohere** (Free Trial)
- **Models**: Command-R, Command
- **Features**: Text generation, embeddings
- **Rate Limit**: 100 requests/minute
- **Get API Key**: https://dashboard.cohere.com/api-keys

### 5. **Google Gemini** (Free Tier)
- **Models**: Gemini 1.5 Flash, Gemini Pro
- **Features**: Text generation, vision, embeddings
- **Rate Limit**: 60 requests/minute
- **Get API Key**: https://makersuite.google.com/app/apikey

### 6. **Together AI** (Free Credits)
- **Models**: Various open-source models
- **Features**: Text generation
- **Get API Key**: https://api.together.xyz/

## Setup Instructions

### 1. Obtain API Keys
Visit each provider's website listed above to create a free account and obtain API keys.

### 2. Configure Environment Variables
Add the following to your `.env` file:

```env
# AI Provider API Keys
CLAUDE_API_KEY=your_key_here
HUGGINGFACE_API_KEY=your_key_here
GROQ_API_KEY=your_key_here
COHERE_API_KEY=your_key_here
GEMINI_API_KEY=your_key_here
TOGETHER_API_KEY=your_key_here

# AI Configuration
AI_DEFAULT_PROVIDER=groq
AI_AGGREGATION_STRATEGY=weighted
AI_CACHE_ENABLED=true
AI_CACHE_TTL=3600

# Enable/Disable Providers
AI_CLAUDE_ENABLED=false
AI_HUGGINGFACE_ENABLED=true
AI_GROQ_ENABLED=true
AI_COHERE_ENABLED=true
AI_GEMINI_ENABLED=true
AI_TOGETHER_ENABLED=false
```

### 3. Run Migrations (if needed)
```bash
php artisan migrate
```

### 4. Clear Configuration Cache
```bash
php artisan config:clear
php artisan cache:clear
```

## Features

### 1. Cover Letter Generation
- **Endpoint**: `POST /api/ai/cover-letter`
- **Strategy**: Weighted aggregation from multiple providers
- **Benefits**: Combines creativity and accuracy from different AI models

### 2. Resume Optimization
- **Endpoint**: `POST /api/ai/optimize-resume`
- **Strategy**: Consensus-based optimization
- **Benefits**: Ensures ATS compatibility through multi-provider validation

### 3. Application Regeneration ⭐ NEW!
- **Endpoint**: `POST /applications/{id}/regenerate`
- **Features**:
  - **Selective Regeneration**: Choose specific sections to regenerate
  - **Strategy Selection**: Pick from weighted, consensus, fastest, or single provider
  - **Preserve Data**: Only updates selected sections, keeps others intact
  - **Interactive UI**: Modal with checkboxes and strategy selection
- **Use Cases**: Improve existing applications, try different AI strategies, fix poor results

### 4. General Text Generation
- **Endpoint**: `POST /api/ai/generate-text`
- **Strategies Available**:
  - `weighted`: Combines responses with weighted importance
  - `consensus`: Uses agreement between providers
  - `fastest`: Returns first successful response
  - `single`: Uses specific provider

### 5. Image Analysis
- **Endpoint**: `POST /api/ai/analyze-image`
- **Providers**: Groq, Gemini, and Claude
- **Use Cases**: Resume screenshot analysis, job posting extraction

## Aggregation Strategies

### Weighted Strategy
Combines responses from multiple providers with configurable weights:
- Claude: 30% (when enabled)
- HuggingFace: 12%
- Groq: 20%
- Cohere: 18%
- Gemini: 20%
- Together: 10%

### Consensus Strategy
Only includes content that multiple providers agree on (70% threshold by default).

### Fastest Strategy
Returns the first successful response, optimal for time-sensitive operations.

## Vue Components

### CoverLetterGenerator.vue
Located at: `resources/js/Components/AI/CoverLetterGenerator.vue`
- Interactive form for cover letter generation
- Real-time AI processing with multiple providers
- Download and copy functionality

### ResumeOptimizer.vue
Located at: `resources/js/Components/AI/ResumeOptimizer.vue`
- ATS optimization with keyword matching
- Format improvements
- Consensus scoring display

### AIProviderStatus.vue
Located at: `resources/js/Components/AI/AIProviderStatus.vue`
- Real-time provider status monitoring
- Rate limit tracking
- Model information display

## Usage Example

### In Laravel Controller:
```php
use App\Services\AI\AIAggregatorService;

class JobApplicationController extends Controller
{
    private AIAggregatorService $aiService;
    
    public function generateCoverLetter(Request $request)
    {
        $result = $this->aiService->generateText(
            $request->input('prompt'),
            ['strategy' => 'weighted']
        );
        
        return response()->json($result);
    }
}
```

### In Vue Component:
```javascript
import axios from 'axios';

const generateContent = async () => {
    const response = await axios.post('/api/ai/generate-text', {
        prompt: 'Your prompt here',
        strategy: 'weighted',
        max_tokens: 1000
    });
    
    console.log(response.data.text);
};
```

## Error Handling

The system includes robust error handling:
- Automatic fallback to available providers
- Cached responses for identical requests
- Rate limit monitoring and throttling
- Detailed error logging

## Performance Optimization

### Caching
- Responses are cached for 1 hour by default
- Identical requests return cached results
- Cache can be disabled per request

### Parallel Processing
- Multiple providers are queried simultaneously
- First successful response can be returned immediately
- Aggregation happens asynchronously

## Monitoring

Check provider status:
```bash
php artisan tinker
>>> $service = app(\App\Services\AI\AIAggregatorService::class);
>>> $service->getAvailableProviders();
```

## Troubleshooting

### Provider Not Available
- Verify API key is correctly set in `.env`
- Check rate limits haven't been exceeded
- Ensure network connectivity to provider APIs

### Slow Response Times
- Enable caching in configuration
- Use 'fastest' strategy for time-sensitive operations
- Consider reducing max_tokens parameter

### Inconsistent Results
- Use 'consensus' strategy for more consistent outputs
- Increase consensus threshold for stricter agreement
- Review individual provider responses in metadata

## Cost Optimization

All integrated providers offer free tiers:
- **HuggingFace**: Completely free for moderate usage
- **Groq**: Generous free tier with fast inference
- **Cohere**: Free trial with 100 API calls/minute
- **Gemini**: Free tier with 60 requests/minute
- **Together**: $25 free credits on signup

## Security Considerations

- API keys are stored in environment variables
- Requests are validated and sanitized
- Rate limiting prevents abuse
- User authentication required for all endpoints

## Future Enhancements

Planned improvements:
- Additional free providers (Replicate, Perplexity)
- Advanced caching strategies
- WebSocket support for streaming
- Fine-tuning support for open models
- Multi-language support

## Support

For issues or questions:
1. Check provider-specific documentation
2. Review error logs in `storage/logs/laravel.log`
3. Verify API keys and rate limits
4. Test individual providers separately
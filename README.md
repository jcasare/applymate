# ApplyMate - AI-Powered Job Application Generator

ApplyMate helps job seekers create tailored application materials in seconds using Claude AI.

## Features

- 🎯 **ATS-Optimized Keywords** - Extract and highlight relevant keywords from job descriptions
- 📝 **Professional Summaries** - Generate tailored resume summaries that match the role
- 💼 **Experience Bullets** - Create impactful, quantified experience bullet points
- ✉️ **Cover Letters** - Generate personalized, compelling cover letters
- 🔗 **LinkedIn Posts** - Create engaging posts to announce your applications
- 📊 **Application History** - Track all your generated applications
- 📥 **Export Options** - Copy to clipboard, export as PDF, or share directly

## Tech Stack

- **Backend**: Laravel 11 (PHP 8.2+)
- **Frontend**: Vue 3 with Inertia.js
- **Styling**: Tailwind CSS
- **Database**: MySQL (or SQLite for development)
- **AI**: Claude API (Anthropic)

## Requirements

- PHP 8.2 or higher
- Composer
- Node.js 18+ and npm
- MySQL 8.0+ (or SQLite for development)
- Claude API key from Anthropic

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

### 4. Configure Claude API

Add your Claude API key to `.env`:
```env
CLAUDE_API_KEY=your_actual_claude_api_key_here
CLAUDE_MODEL=claude-3-5-sonnet-20241022
CLAUDE_MAX_TOKENS=4096
```

Get your API key from: https://console.anthropic.com/

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
│   │   └── ApplicationController.php    # Main application logic
│   ├── Models/
│   │   └── Application.php              # Application model
│   └── Services/
│       └── ClaudeAIService.php          # Claude API integration
├── resources/
│   └── js/
│       └── Pages/
│           ├── Dashboard.vue            # Main dashboard
│           └── Applications/
│               ├── Create.vue           # Generation form
│               ├── Index.vue            # Applications list
│               └── Show.vue             # View application
├── database/
│   └── migrations/                      # Database migrations
└── routes/
    └── web.php                          # Application routes
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

### Claude API Issues
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

## Security Notes

- Never commit `.env` file to version control
- Keep your Claude API key secure
- Use HTTPS in production
- Enable CSRF protection (enabled by default)
- Implement rate limiting for API calls
- Regularly update dependencies

## License

This project is proprietary software. All rights reserved.

## Support

For issues or questions, please contact support or create an issue in the repository.

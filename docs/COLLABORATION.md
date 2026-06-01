# SIKAP Backend - Collaboration Guide

## 🎯 Project Overview

**SIKAP** (Sorsogon Informal Workers' Kabuhayan, Application, and Posting Portal) is a Laravel 12 REST API backend connecting informal workers with employers in Sorsogon, Philippines.

### 🏗️ Tech Stack
- **Backend**: Laravel 12 (PHP 8.2)
- **Database**: Supabase PostgreSQL 15
- **Authentication**: Laravel Sanctum (Bearer Tokens)
- **Email**: Resend API
- **SMS**: Semaphore Philippines API
- **File Storage**: Supabase Storage (S3-compatible)
- **Queue**: Database Queue
- **Deployment**: Render (Docker)

## 🚀 Quick Start for Collaborators

### Prerequisites
```bash
# Required Software
- PHP 8.2+
- Composer 2.0+
- PostgreSQL 15+ (or use Supabase)
- Git
- Docker & Docker Compose (optional)
```

### Setup Steps
```bash
# 1. Clone Repository
git clone <repository-url>
cd sikap-backend

# 2. Install Dependencies
composer install

# 3. Environment Setup
cp .env.example .env
# Edit .env with your credentials

# 4. Generate App Key
php artisan key:generate

# 5. Run Migrations (only system tables)
php artisan migrate

# 6. Start Development Server
php artisan serve
```

### Docker Alternative (Recommended)
```bash
# 1. Copy Docker Environment
cp .env.docker .env

# 2. Start Services
docker-compose up -d

# 3. Setup Laravel
docker-compose exec app composer install
docker-compose exec app php artisan key:generate
docker-compose exec app php artisan migrate
```

## 📁 Project Structure

```
sikap-backend/
├── app/
│   ├── Http/
│   │   ├── Controllers/          # API Controllers
│   │   │   ├── Auth/          # Authentication endpoints
│   │   │   └── Admin/         # Admin dashboard endpoints
│   │   ├── Middleware/           # Custom middleware (RoleMiddleware)
│   │   └── Resources/           # API transformers
│   ├── Models/                  # Eloquent models with relationships
│   ├── Services/                # Business logic services
│   ├── Jobs/                   # Queue jobs
│   └── Mail/                   # Email templates
├── config/                      # Laravel configuration files
├── database/migrations/          # Only system migrations (not app tables)
├── routes/api.php               # API routes definition
├── docker/                      # Docker configuration
└── render.yaml                  # Render deployment config
```

## 🔐 Authentication & Authorization

### User Roles
- **Worker**: Can apply for jobs, manage profile
- **Employer**: Can post jobs, manage applications
- **Admin**: Full system access, user management

### API Authentication
```bash
# Login
POST /api/v1/auth/login
{
  "email": "user@example.com",
  "password": "password"
}

# Response
{
  "token": "bearer_token_here",
  "token_type": "Bearer",
  "user": { ... }
}

# Use token in headers
Authorization: Bearer {token}
```

### Role-Based Access
```php
// Middleware usage
Route::middleware('role:worker')->group(function () {
    // Worker-only routes
});

Route::middleware('role:employer')->group(function () {
    // Employer-only routes
});

Route::middleware('role:admin')->group(function () {
    // Admin-only routes
});
```

## 📊 Database Schema

### Important Notes
- **App tables already exist in Supabase** - DO NOT run migrations for them
- **Only run migrations for**: `personal_access_tokens`, `jobs`, `cache`, `sessions`
- **All models use `$connection = 'pgsql'`**

### Key Tables
- `users` - User accounts with roles
- `job_posts` - Job postings with soft deletes
- `applications` - Job applications with workflow states
- `reviews` - User ratings with reputation calculation
- `reports` - User reporting system
- `email_otps` - OTP verification system

## 🔄 Application Workflow

### 4-Stage Process
1. **Stage 1**: Worker applies → Status: `pending`
2. **Stage 2**: Employer sends request → Status: `pending_negotiation`
3. **Stage 3**: Employer confirms hire → Status: `employer_confirmed`
4. **Stage 4**: Worker accepts → Status: `accepted`

### Privacy Controls
- **Worker email**: NEVER disclosed
- **Worker phone**: Only when `contact_revealed = true`
- **Character references**: Only when `references_revealed = true`
- **Final price**: Only at `employer_confirmed` or `accepted` status

## 🧪 Testing

### Running Tests
```bash
# Run all tests
php artisan test

# Run specific test
php artisan test --filter UserRegistrationTest

# Generate coverage report
php artisan test --coverage
```

### API Testing Examples
```bash
# Register User
curl -X POST http://localhost:8000/api/v1/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "name": "John Doe",
    "email": "john@example.com",
    "password": "password123",
    "password_confirmation": "password123",
    "role": "worker",
    "phone": "09123456789",
    "barangay": "Barangay 1",
    "municipality": "Sorsogon City"
  }'

# Get Jobs
curl -X GET http://localhost:8000/api/v1/jobs \
  -H "Authorization: Bearer {token}"

# Create Job (Employer)
curl -X POST http://localhost:8000/api/v1/jobs \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "title": "Construction Worker Needed",
    "description": "Looking for experienced construction worker...",
    "category": "Construction",
    "barangay": "Barangay 1",
    "municipality": "Sorsogon City",
    "slots": 2,
    "compensation": 500.00
  }'
```

## 🔧 Development Guidelines

### Code Standards
- Follow PSR-12 coding standards
- Use Laravel conventions and best practices
- Write meaningful commit messages
- Add comments for complex business logic

### Git Workflow
```bash
# Feature branch workflow
git checkout -b feature/user-profile-update
git add .
git commit -m "feat: add worker profile update functionality"
git push origin feature/user-profile-update
# Create Pull Request
```

### Environment Management
```bash
# Development
APP_ENV=local
APP_DEBUG=true

# Production
APP_ENV=production
APP_DEBUG=false
```

## 🌐 API Documentation

### Base URL
- **Development**: `http://localhost:8000/api/v1`
- **Production**: `https://your-app.onrender.com/api/v1`

### Key Endpoints

#### Authentication
- `POST /auth/register` - User registration
- `POST /auth/login` - User login
- `POST /auth/verify-otp` - OTP verification
- `POST /auth/resend-otp` - Resend OTP
- `POST /auth/upload-id` - Upload government ID
- `POST /auth/logout` - User logout

#### Registration Status Behavior
- `pending_email_verification` — user must verify OTP
- `pending_id_upload` — user must upload ID documents
- `pending_review` — user is waiting for admin approval
- `approved` — user may log in normally
- `rejected` — user receives a rejection notice and next step guidance

#### Jobs
- `GET /jobs` - List jobs (public)
- `GET /jobs/{id}` - Get job details
- `POST /jobs` - Create job (employer)
- `PATCH /jobs/{id}` - Update job (employer)
- `DELETE /jobs/{id}` - Delete job (employer)

#### Applications
- `GET /my-applications` - Worker's applications
- `POST /jobs/{id}/apply` - Apply for job (worker)
- `PATCH /applications/{id}/accept` - Accept offer (worker)
- `PATCH /applications/{id}/reject` - Reject offer (worker)

#### Admin
- `GET /admin/users` - List all users
- `GET /admin/analytics` - System analytics
- `GET /admin/reports` - User reports
- `PATCH /admin/users/{id}/verify` - Verify user ID

## 🚨 Common Issues & Solutions

### Database Connection Issues
```bash
# Check .env configuration
DB_CONNECTION=pgsql
DB_HOST=your-supabase-host.supabase.co
DB_PORT=5432
DB_DATABASE=postgres
DB_USERNAME=postgres
DB_PASSWORD=your-password
DB_SSLMODE=require
```

### Migration Issues
```bash
# Only run system migrations
php artisan migrate --path=database/migrations/2024_01_01_000001_create_cache_table.php
php artisan migrate --path=database/migrations/2024_01_01_000002_create_jobs_table.php
```

### Queue Issues
```bash
# Clear failed jobs
php artisan queue:failed

# Restart queue worker
php artisan queue:restart
```

## 📞 Getting Help

### Debug Mode
```bash
# Enable debug in .env
APP_DEBUG=true

# Check logs
tail -f storage/logs/laravel.log
```

### Common Commands
```bash
# Clear caches
php artisan config:clear
php artisan route:clear
php artisan cache:clear

# Generate IDE helpers
php artisan ide-helper:generate
```

## 🤝 Contributing Guidelines

### Before Contributing
1. Check existing issues and pull requests
2. Discuss major changes in issues first
3. Follow the coding standards
4. Write tests for new features
5. Update documentation

### Pull Request Process
1. Fork the repository
2. Create feature branch
3. Make your changes
4. Add tests if applicable
5. Ensure all tests pass
6. Submit pull request with clear description

## 📱 External Services Setup

### Required Services
1. **Supabase** - Database and Storage
   - Create project at https://supabase.com
   - Get connection string and service role key
   - Set up storage bucket

2. **Resend** - Email Service
   - Create account at https://resend.com
   - Get API key
   - Configure domain settings

3. **Semaphore** - SMS Service
   - Create account at https://semaphore.co
   - Get API key
   - Test SMS sending

### Environment Variables
```env
# Add to .env
SUPABASE_URL=https://your-project.supabase.co
SUPABASE_SERVICE_ROLE_KEY=your-service-role-key
RESEND_API_KEY=re_your_resend_key
SEMAPHORE_API_KEY=your_semaphore_key
```

## 📞 Contact & Support

### Team Communication
- Use GitHub Issues for bug reports
- Use GitHub Discussions for questions
- Create pull requests for contributions

### Documentation
- API documentation: Available in this guide
- Database schema: See Supabase dashboard
- Deployment: See DOCKER.md

---

**Happy Coding! 🚀**

For questions or issues, please create an issue in the repository or contact the project maintainers.

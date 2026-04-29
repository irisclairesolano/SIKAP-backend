# SIKAP Backend - Docker Setup

## 🐳 Docker Development Setup

### Prerequisites
- Docker & Docker Compose installed
- Git

## 🚀 Quick Start

### 1. Clone and Setup
```bash
git clone <repository-url>
cd sikap-backend
cp .env.docker .env
```

### 2. Start Development Environment
```bash
docker-compose up -d
```

### 3. Install Dependencies (first time only)
```bash
docker-compose exec app composer install
```

### 4. Generate App Key
```bash
docker-compose exec app php artisan key:generate
```

### 5. Run Migrations
```bash
docker-compose exec app php artisan migrate
```

## 🌐 Access Points

- **API**: http://localhost:8000
- **Database**: localhost:5432 (sikap/sikap_password)
- **Redis**: localhost:6379

## 🛠️ Development Commands

### Enter App Container
```bash
docker-compose exec app bash
```

### View Logs
```bash
docker-compose logs -f app
docker-compose logs -f nginx
```

### Stop Services
```bash
docker-compose down
```

## 🏗️ Build & Deploy

### For Render Production
```bash
# Update render.yaml with your actual credentials
# Deploy to Render using Docker build
```

### Local Production Build
```bash
docker build -t sikap-api .
docker run -p 8000:80 sikap-api
```

## 📁 Docker Services

### Services Included:
- **app**: PHP 8.2 FPM with Laravel
- **nginx**: Web server with PHP-FPM proxy
- **postgres**: PostgreSQL 15 database
- **redis**: Redis 7 for caching/queues

### Volumes:
- `postgres_data`: PostgreSQL data persistence
- `redis_data`: Redis data persistence
- `./storage`: Laravel storage (shared with host)

## 🔧 Configuration

### Environment Variables
Copy `.env.docker` to `.env` and modify:

#### Required for Production:
- `SUPABASE_URL`: Your Supabase project URL
- `SUPABASE_SERVICE_ROLE_KEY`: Service role JWT
- `RESEND_API_KEY`: Resend API key
- `SEMAPHORE_API_KEY`: Semaphore API key

#### Optional for Local Development:
- Use Docker PostgreSQL/Redis (default in `.env.docker`)
- Mail set to `log` for development

## 🐛 Troubleshooting

### Permission Issues
```bash
sudo chown -R $USER:$USER storage bootstrap/cache
```

### Clear Caches
```bash
docker-compose exec app php artisan config:clear
docker-compose exec app php artisan route:clear
docker-compose exec app php artisan cache:clear
```

### Rebuild Containers
```bash
docker-compose down
docker-compose build --no-cache
docker-compose up -d
```

## 📦 Production Deployment (Render)

The `render.yaml` is configured for Docker deployment:

1. Update environment variables in Render dashboard
2. Connect your GitHub repository
3. Deploy - Render will build using Dockerfile

### Required Render Environment Variables:
- `DB_HOST`, `DB_PASSWORD`, `DB_CONNECTION`
- `RESEND_API_KEY`
- `SEMAPHORE_API_KEY`
- `SUPABASE_URL`, `SUPABASE_SERVICE_ROLE_KEY`

## 🔍 Health Check

API health endpoint: `GET /up`

```bash
curl http://localhost:8000/up
```

## 📚 Additional Resources

- [Laravel Docker Documentation](https://laravel.com/docs/deployment#docker)
- [Render Docker Documentation](https://render.com/docs/docker)
- [Docker Compose Documentation](https://docs.docker.com/compose/)

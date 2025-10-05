# Laravel Admin Panel with Filament 4

A modern Laravel application with dual-panel architecture featuring Filament 4 admin panel and API documentation, comprehensive error tracking with Sentry, and multi-subdomain support.

## Features

- **Laravel 12** - Latest Laravel framework
- **Dual Panel Architecture** - Separate admin and API panels
- **Filament 4** - Modern admin panel with beautiful UI
- **PHP 8.4** - Latest PHP version support
- **API Documentation** - Auto-generated with Scramble
- **Multi-subdomain Support** - admin.localhost and api.localhost
- **Error Tracking** - Sentry integration for monitoring
- **Media Management** - Spatie Media Library integration
- **Performance** - Laravel Octane support
- **Testing** - Pest 4 for modern testing
- **Code Quality** - Laravel Pint for code formatting

## Tech Stack

### Backend
- **Laravel 12.25** - PHP Framework
- **Filament 4.0** - Admin Panel
- **Laravel Sanctum** - API Authentication
- **Laravel Octane** - High-performance application server

### Frontend
- **Filament UI** - Modern admin interface
- **Livewire** - Dynamic interfaces
- **Alpine.js** - Lightweight JavaScript framework (built-in with Filament)

### Development Tools
- **Pest 4** - Testing framework
- **Laravel Telescope** - Application debugging
- **Laravel Debugbar** - Debug toolbar
- **Laravel Pint** - Code style fixer

### Integrations
- **Sentry** - Error tracking and monitoring
- **Scramble** - API documentation generator
- **AWS S3** - File storage
- **Resend** - Email service

## Requirements

- **PHP >= 8.4**
- **Composer**
- **MySQL/PostgreSQL** (Database)
- **Redis** (Optional, for caching and queues)

**Note:** Node.js & NPM are not required as Filament provides pre-built assets.

## Installation

### Quick Installation (Recommended)

```bash
# Install PHP dependencies
composer install

# Copy environment file and configure database
cp env.example .env
# Edit .env file with your database credentials

# Generate application key
php artisan key:generate

# Run migrations with seeding (creates default admin user)
php artisan migrate --seed

# Start the application
php artisan serve --port=9000
```

## Access URLs

After starting the server on port 9000, you can access:

- **Admin Panel**: `http://admin.localhost:9000/`
- **API Documentation**: `http://api.localhost:9000/v1/docs`

### Subdomain Configuration

The application uses subdomain-based routing:
- `admin.localhost` - Filament admin panel
- `api.localhost` - API endpoints and documentation

Make sure to configure your hosts file or use a tool like Laravel Valet for local subdomain support.

## Environment Configuration

### Required Environment Variables

Copy these variables to your `.env` file and configure them:

```env
# Application
APP_NAME="Laravel Admin Panel"
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_TIMEZONE=UTC
APP_URL=http://admin.localhost:9000

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=laravel
DB_USERNAME=root
DB_PASSWORD=

# Cache & Session
CACHE_STORE=file
SESSION_DRIVER=file
SESSION_LIFETIME=120

# Queue
QUEUE_CONNECTION=database

# Mail Configuration
MAIL_MAILER=resend
MAIL_FROM_ADDRESS="hello@example.com"
MAIL_FROM_NAME="${APP_NAME}"
RESEND_API_KEY=

# File Storage
FILESYSTEM_DISK=local
AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=
AWS_USE_PATH_STYLE_ENDPOINT=false

# Error Tracking (Sentry)
SENTRY_LARAVEL_DSN=

# API Documentation
SCRAMBLE_ENABLED=true
```

### Optional Environment Variables

```env
# Redis (for caching and queues)
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# Laravel Telescope (development only)
TELESCOPE_ENABLED=true

# Debug Tools (development only)
DEBUGBAR_ENABLED=true
```

## Development

### Running Tests
```bash
# Run all tests
php artisan test

# Run tests with coverage
php artisan test --coverage

# Run specific test suite
php artisan test --filter AdminResourceTest
```

### Code Quality
```bash
# Fix code style
./vendor/bin/pint

# Check code style
./vendor/bin/pint --test
```

### Development Tools

#### Laravel Telescope  
Access at: `http://admin.localhost:9000/telescope`
- Monitor requests, exceptions, queries, and more
- Only enabled in development environment

#### API Documentation
Access at: `http://api.localhost:9000/v1/docs`
- Auto-generated API documentation with Scramble
- Interactive API testing interface

#### Admin Panel
Access at: `http://admin.localhost:9000/`
- Filament 4 admin interface
- Manage admins and other resources
- Default admin user created via seeder

### Error Tracking

#### Sentry Configuration
1. Create a Sentry project at [sentry.io](https://sentry.io)
2. Get your DSN from the project settings
3. Add to `.env`: `SENTRY_LARAVEL_DSN=your-dsn-here`

#### Test Sentry Integration
```bash
php artisan test:sentry
```

## Project Structure

```
app/
├── Console/Commands/      # Artisan commands
├── Exceptions/           # Exception handling
├── Filament/            # Filament admin resources
│   └── Resources/
│       ├── Admins/      # Admin management
│       │   ├── AdminResource.php
│       │   ├── Pages/
│       │   ├── Schemas/  # Form schemas
│       │   └── Tables/   # Table configurations
│       └── Customers/   # Customer management
├── Http/
│   ├── Controllers/     # API controllers
│   ├── Middleware/      # Custom middleware
│   ├── Requests/        # Form requests
│   └── Resources/       # API resources
├── Models/              # Eloquent models
└── Providers/           # Service providers
```

## API Documentation

The API documentation is automatically generated using Scramble and available at `/v1/docs`. The API includes:

- **Authentication endpoints** - Login, logout, token management
- **Admin management** - CRUD operations for admins
- **Resource endpoints** - Various resource management

## Development

### Code Architecture
This project follows a specific architectural pattern for API development. See [CODE_STRUCTURE.md](CODE_STRUCTURE.md) for detailed information about:

- **Request → DTO → Action → Resource** flow
- When to use each component
- Complete examples and best practices
- File organization and naming conventions

### Quick Development Commands
```bash
# Start development server
php artisan serve --port=9000

# 🚨 MANDATORY before every commit:
./vendor/bin/pint && php artisan test
```

### ⚠️ Pre-Commit Requirements

**Before committing any code, you MUST run and pass:**
1. `./vendor/bin/pint` - Fix code style
2. `php artisan test` - Ensure all tests pass

**❌ DO NOT commit if either command fails!**



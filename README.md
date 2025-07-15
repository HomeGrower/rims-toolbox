# RIMS-Toolbox

Eine umfassende Toolbox für RIMS-Mitarbeiter und Kunden, die verschiedene Prozesse vereinfacht und automatisiert. Die Toolbox beinhaltet primär die RIMS Pre-Installation Suite sowie zusätzliche Tools wie den Datastore Builder und Configurator.

## Table of Contents

- [Overview](#overview)
- [Features](#features)
- [Technology Stack](#technology-stack)
- [System Requirements](#system-requirements)
- [Installation](#installation)
- [Configuration](#configuration)
- [Usage](#usage)
- [Architecture](#architecture)
- [API Documentation](#api-documentation)
- [Development](#development)
- [Testing](#testing)
- [Deployment](#deployment)
- [Contributing](#contributing)
- [License](#license)

## Overview

Die RIMS-Toolbox ist eine zentrale Plattform, die entwickelt wurde, um die Arbeit von RIMS-Mitarbeitern und die Erfahrung von Hotel-Kunden zu optimieren. Sie vereint verschiedene Tools und Prozesse in einer einheitlichen, benutzerfreundlichen Oberfläche.

### Hauptkomponenten

- **RIMS Pre-Installation Suite**: Das Kernstück der Toolbox für die Vorbereitung und Konfiguration von Hotelinstallationen
- **Datastore Builder**: Visuelles Tool zur Erstellung und Anpassung von Datenstrukturen
- **Configurator**: Erweiterte Konfigurationsmöglichkeiten für komplexe Systemanpassungen
- **Weitere Tools**: Kontinuierlich erweiterbares System mit zusätzlichen Werkzeugen

### Vorteile

- **Prozessoptimierung**: Vereinfachte Arbeitsabläufe für RIMS-Mitarbeiter
- **Kundenzufriedenheit**: Intuitive Benutzeroberfläche für Hotel-Kunden
- **Zeitersparnis**: Automatisierte Prozesse reduzieren manuelle Arbeit
- **Konsistenz**: Standardisierte Abläufe gewährleisten gleichbleibende Qualität
- **Skalierbarkeit**: Flexibles System, das mit den Anforderungen wächst

## Features

### 🛠️ RIMS Pre-Installation Suite
- **Projekt-Management**: Verwaltung mehrerer Hotelprojekte mit eindeutigen Zugangscodes
- **Team-basierte Workflows**: Aufgabenteilung zwischen Reservation, Marketing und IT Teams
- **Dynamische Formulare**: Automatische Anpassung basierend auf PMS-Typ und ausgewählten Modulen
- **Fortschrittsverfolgung**: Echtzeit-Übersicht über den Installationsstatus

### 📊 Datastore Builder
- **Visuelle Konfiguration**: Drag-and-Drop Interface für Datenstruktur-Anpassungen
- **Modul-Overrides**: Einfache Anpassung von Standard-Modulkonfigurationen
- **Echtzeit-Vorschau**: Sofortige Visualisierung von Änderungen
- **JSON Export/Import**: Wiederverwendbare Konfigurationen

### ⚙️ Configurator
- **Erweiterte Einstellungen**: Detaillierte Systemkonfiguration
- **Template-Management**: Verwaltung und Anpassung von Vorlagen
- **Bedingte Logik**: Dynamische Inhalte basierend auf Konfigurationen
- **Multi-Language Support**: Mehrsprachige Konfigurationsoptionen

### 🏢 Hotel & Marken-Verwaltung
- **Hierarchische Struktur**: Hotel Chains → Hotel Brands → Hotels
- **Marken-spezifisches Branding**: Individuelle Logos und Farbschemata
- **PMS-Integration**: Unterstützung verschiedener Property Management Systeme
- **Währungs- und Sprachverwaltung**: Globale Unterstützung

### 📋 Weitere Tools
- **Checklist-System**: Anpassbare Checklisten für verschiedene Prozesse
- **Greeting Text Manager**: Verwaltung dynamischer Begrüßungstexte
- **Policy Configurator**: Einrichtung von Hotel-Richtlinien
- **Backup-Management**: Automatisierte Datensicherung

### 🔐 Sicherheit & Zugriffskontrolle
- **Rollenbasierte Berechtigungen**: Super Admin, Admin, User
- **Projekt-Zugangscodes**: Sichere Zugriffsverwaltung
- **API-Authentifizierung**: Laravel Sanctum Integration
- **Verschlüsselte Datenspeicherung**: Sichere Ablage sensibler Daten

### 📊 Admin Dashboard
- **Filament Admin Panel**: Moderne Verwaltungsoberfläche
- **Echtzeit-Statistiken**: Übersicht über alle Projekte
- **Kalenderansicht**: Projektplanung und -übersicht
- **System-Monitoring**: Logs und Performance-Überwachung

## Technology Stack

### Backend
- **Framework**: Laravel 12.x
- **PHP**: ^8.2
- **Database**: MySQL 8.0
- **Admin Panel**: Filament 3.0
- **Authentication**: Laravel Sanctum
- **Backup**: Spatie Laravel Backup
- **File Storage**: Laravel Storage

### Frontend
- **Framework**: Vue 3
- **SPA Router**: Inertia.js
- **Build Tool**: Vite
- **CSS Framework**: Tailwind CSS
- **Icons**: Font Awesome, Blade Icons
- **Charts**: ApexCharts

### Infrastructure
- **Containerization**: Docker & Docker Compose
- **Web Server**: Nginx
- **Process Manager**: PHP-FPM
- **Cache**: Redis (optional)
- **Queue**: Laravel Queue

## System Requirements

### Minimum Requirements
- PHP 8.2 or higher
- MySQL 8.0 or higher
- Node.js 18.x or higher
- Composer 2.x
- Docker & Docker Compose (for containerized deployment)

### Recommended Specifications
- CPU: 4+ cores
- RAM: 8GB minimum, 16GB recommended
- Storage: 50GB+ (depending on file uploads)
- OS: Linux (Ubuntu 22.04 LTS recommended) or macOS

## Installation

### Using Docker (Recommended)

This project includes separate Docker configurations for development and production environments.

#### Development Setup

**Option 1: Automated Setup (Recommended)**
1. **Clone the repository**
   ```bash
   git clone https://github.com/your-org/rims-toolbox.git
   cd rims-toolbox/laravel-docker-app
   ```

2. **Run setup script**
   ```bash
   chmod +x setup-dev.sh
   ./setup-dev.sh
   ```

**Option 2: Manual Setup**
1. **Clone the repository**
   ```bash
   git clone https://github.com/your-org/rims-toolbox.git
   cd rims-toolbox/laravel-docker-app
   ```

2. **Copy environment file**
   ```bash
   cp .env.dev .env
   ```

3. **Start development containers**
   ```bash
   docker-compose up -d --build
   ```

4. **Install dependencies and setup**
   ```bash
   docker-compose exec app composer install
   docker-compose exec app php artisan key:generate --force
   docker-compose exec app php artisan config:cache
   docker-compose exec app npm install
   docker-compose exec app npm run build
   docker-compose exec app php artisan migrate --force
   docker-compose exec app php artisan db:seed --force
   ```

5. **Set permissions**
   ```bash
   docker-compose exec app chmod -R 775 storage bootstrap/cache
   docker-compose exec app chown -R www-data:www-data storage bootstrap/cache
   docker-compose exec app php artisan storage:link
   ```

6. **Access the application**
   - Application: http://localhost:8080
   - Admin Panel: http://localhost:8080/admin
   - Login: admin@rims.live / kaffeistkalt14
   
   **Note**: Admin user is automatically created during seeding.

**Development features:**
- Redis for caching and sessions
- Queue worker for background jobs
- Scheduler for cron jobs
- Debug mode enabled
- All production features available

#### Production Setup

1. **Clone and configure**
   ```bash
   git clone https://github.com/your-org/rims-toolbox.git
   cd rims-toolbox/laravel-docker-app
   cp .env.prod .env
   # Edit .env with production values
   ```

2. **Build assets**
   ```bash
   docker-compose exec app npm install
   docker-compose exec app npm run build
   ```

3. **Start production containers**
   ```bash
   docker-compose -f docker-compose.prod.yml up -d
   ```

4. **Initialize application**
   ```bash
   docker-compose -f docker-compose.prod.yml exec app composer install --no-dev
   docker-compose -f docker-compose.prod.yml exec app php artisan key:generate
   docker-compose -f docker-compose.prod.yml exec app php artisan migrate --force
   docker-compose -f docker-compose.prod.yml exec app php artisan db:seed --force
   docker-compose -f docker-compose.prod.yml exec app php artisan config:cache
   docker-compose -f docker-compose.prod.yml exec app php artisan route:cache
   docker-compose -f docker-compose.prod.yml exec app php artisan view:cache
   ```

**Production features:**
- Redis for caching and sessions
- Queue worker for background jobs
- Scheduler for cron jobs
- HTTPS support (configure SSL certificates)
- Optimized for performance

### Manual Installation

1. **Clone and setup**
   ```bash
   git clone https://github.com/your-org/rims-toolbox.git
   cd rims-toolbox/laravel-docker-app
   cp .env.dev .env
   cd src
   composer install
   php artisan key:generate
   ```

2. **Configure database**
   - Create a MySQL database
   - Update `.env` with database credentials

3. **Run migrations and seeders**
   ```bash
   php artisan migrate
   php artisan db:seed
   ```

4. **Install frontend dependencies**
   ```bash
   npm install
   npm run build
   ```

5. **Start the development server**
   ```bash
   php artisan serve
   npm run dev  # In another terminal for Vite
   ```

## Configuration

### Environment Variables

Key environment variables to configure:

```env
# Application
APP_NAME="RIMS-Toolbox"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8080

# Database
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=rims_toolbox
DB_USERNAME=root
DB_PASSWORD=secret

# Mail (for notifications)
MAIL_MAILER=smtp
MAIL_HOST=mailhog
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null

# Storage
FILESYSTEM_DISK=public

# Queue
QUEUE_CONNECTION=database

# Backup
BACKUP_DISK=local
```

### Storage Configuration

Configure storage paths in `config/filesystems.php`:
- Project files: `storage/app/public/projects/`
- Brand assets: `storage/app/public/brand-logos/`
- Template examples: `storage/app/public/template-examples/`
- Backups: `storage/app/backup/`

## Usage

### Admin Access

1. Navigate to `/admin`
2. Login with super admin credentials:
   - Email: admin@example.com
   - Password: password (change immediately!)

### Creating a New Project

1. From the admin panel, navigate to Projects
2. Click "New Project" or use the Project Wizard
3. Fill in required information:
   - Project name and type
   - Hotel chain and brand
   - PMS type
   - Modules to include
4. Generate unique access code
5. Share access code with hotel staff

### Hotel Staff Access

1. Navigate to the main application URL
2. Enter the project access code
3. Follow the setup wizard:
   - Complete team-specific sections
   - Upload required documents
   - Configure settings
4. Track progress on the dashboard

### Using the Datastore Builder

1. Navigate to a project's Datastore Builder
2. Select modules to configure
3. Add/modify tables and fields
4. Preview changes in real-time
5. Export configuration as JSON

## Architecture

### Directory Structure

```
rims-toolbox/
├── laravel-docker-app/
│   ├── docker/              # Docker configuration files
│   │   ├── mysql/
│   │   ├── nginx/
│   │   └── php/
│   ├── src/                 # Laravel application
│   │   ├── app/
│   │   │   ├── Filament/    # Admin panel resources
│   │   │   ├── Http/        # Controllers & Middleware
│   │   │   ├── Models/      # Eloquent models
│   │   │   └── Services/    # Business logic
│   │   ├── database/
│   │   │   ├── migrations/
│   │   │   └── seeders/
│   │   ├── resources/
│   │   │   ├── js/          # Vue components
│   │   │   ├── css/
│   │   │   └── views/       # Blade templates
│   │   ├── routes/
│   │   └── storage/
│   ├── docker-compose.yml
│   └── Makefile
└── vorlage/                 # Template files
```

### Database Schema

Key tables and relationships:

- `projects` - Main project records
- `users` - System users
- `modules` - Available modules
- `project_modules` - Module assignments
- `hotel_chains` & `hotel_brands` - Hotel hierarchy
- `pms_types` - PMS configurations
- `datastore_configurations` - JSON configurations
- `project_data` - Form submissions
- `checklist_templates` & `checklist_responses`

### API Structure

RESTful API endpoints:

- `/api/projects` - Project management
- `/api/datastore` - Datastore operations
- `/api/project-data` - Form data management
- `/api/auth` - Authentication

## Development

### Setting up Development Environment

1. **Install development dependencies**
   ```bash
   npm install --save-dev
   composer install --dev
   ```

2. **Start development servers**
   ```bash
   npm run dev      # Vite dev server
   php artisan serve # Laravel dev server
   ```

3. **Code Style**
   ```bash
   npm run lint     # ESLint for JavaScript
   ./vendor/bin/pint # Laravel Pint for PHP
   ```

### Creating New Modules

1. Create migration for module data
2. Add module seeder
3. Create Filament resource if needed
4. Add Vue components for client interface
5. Update module dependencies

### Adding New PMS Types

1. Add PMS type to database
2. Configure policy settings
3. Add example images
4. Update form generation logic

## Testing

### Running Tests

```bash
# Run all tests
php artisan test

# Run specific test suite
php artisan test --testsuite=Feature
php artisan test --testsuite=Unit

# Run with coverage
php artisan test --coverage
```

### Test Structure

- `tests/Feature/` - Integration tests
- `tests/Unit/` - Unit tests
- `tests/Pest.php` - Pest configuration

## Deployment

### Container Management

#### Development
```bash
# Start containers
docker-compose up -d

# Stop containers
docker-compose down

# Stop and remove all data (fresh start)
docker-compose down -v

# View logs
docker-compose logs -f app
```

#### Production
```bash
# Start containers
docker-compose -f docker-compose.prod.yml up -d

# Stop containers
docker-compose -f docker-compose.prod.yml down

# Monitor containers
docker-compose -f docker-compose.prod.yml logs -f app
docker-compose -f docker-compose.prod.yml logs -f queue
docker-compose -f docker-compose.prod.yml logs -f scheduler
```

### Environment Configuration

#### Development (.env)
- `APP_ENV=local`
- `APP_DEBUG=true`
- `CACHE_DRIVER=redis`
- `SESSION_DRIVER=file`
- `QUEUE_CONNECTION=redis`

#### Production (.env)
- `APP_ENV=production`
- `APP_DEBUG=false`
- `CACHE_DRIVER=redis`
- `SESSION_DRIVER=redis`
- `QUEUE_CONNECTION=redis`
- Configure all passwords and secrets
- Set up SSL certificates in `docker/nginx/ssl/`

### Maintenance Commands

```bash
# Clear all caches
docker-compose exec app php artisan cache:clear
docker-compose exec app php artisan config:clear
docker-compose exec app php artisan route:clear
docker-compose exec app php artisan view:clear

# Run backups
docker-compose exec app php artisan backup:run

# Process queue jobs manually
docker-compose exec app php artisan queue:work
```

### Database Management

#### Sync Production Data to Development
```bash
# Sync production database to development
./sync-prod-to-dev.sh
# Option 1: Provide existing SQL dump file
# Option 2: Connect directly to production database

# Create a backup of development database
./backup-dev.sh

# Restore a backup to development
./restore-dev.sh
```

**Note**: The sync script will:
- Backup your current development database
- Import production data
- Run migrations
- Clear all caches
- Keep the last 10 backups automatically

## Contributing

We welcome contributions! Please follow these guidelines:

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

### Coding Standards

- Follow PSR-12 for PHP code
- Use ESLint configuration for JavaScript
- Write tests for new features
- Update documentation as needed

---

Von der Göttin Levin
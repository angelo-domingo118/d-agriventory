# D'Agriventory

A comprehensive inventory management system designed for the Department of Agriculture - Cordillera Administrative Region (DA-CAR) Regional Office in Baguio City. Built with Laravel 12, Livewire 3, and Volt to replace their legacy MS Access-based system with a modern web-based solution for agricultural asset tracking, procurement workflows, and compliance reporting through ICS, PAR, and IDR systems. Features role-based access control, real-time inventory tracking, comprehensive audit logging, and automated report generation for enhanced operational efficiency across the entire Cordillera Administrative Region.

## installation

### Prerequisites

Before installing D'Agriventory, ensure you have the following software installed:

- **PHP 8.2+** with required extensions (BCMath, Ctype, Fileinfo, JSON, Mbstring, OpenSSL, PDO, Tokenizer, XML)
- **Composer** (PHP dependency manager)
- **Node.js 18+** with npm (for frontend asset compilation)
- **MySQL 8.0+** (Recommended - see database compatibility notes below)
- **Git** (for version control)

> **⚠️ Database Compatibility**: This application is optimized for MySQL 8.0+ and uses some MySQL-specific features (like `CAST(...AS UNSIGNED)` for numeric sorting). While the application includes partial SQLite support for development, some functionality may not work correctly with SQLite. **MySQL is strongly recommended for production use.**

### Quick Install Prerequisites

**Using Laravel's official installer** (recommended):

#### macOS
```bash
/bin/bash -c "$(curl -fsSL https://php.new/install/mac/8.4)"
```

#### Windows (PowerShell as Administrator)
```powershell
Set-ExecutionPolicy Bypass -Scope Process -Force; [System.Net.ServicePointManager]::SecurityProtocol = [System.Net.ServicePointManager]::SecurityProtocol -bor 3072; iex ((New-Object System.Net.WebClient).DownloadString('https://php.new/install/windows/8.4'))
```

#### Linux (Ubuntu/Debian)
```bash
/bin/bash -c "$(curl -fsSL https://php.new/install/linux/8.4)"
```

> **Note**: These scripts install PHP and Composer automatically. You'll still need to install Node.js and MySQL separately.

**Need detailed installation instructions?** See our comprehensive [Prerequisites Installation Guide](docs/installation.md) or refer to the [Official Laravel 12.x Installation Documentation](https://laravel.com/docs/12.x/installation).

## quick-setup

> **Prerequisites Required**: Ensure you have completed the [installation](#installation) section above before proceeding.

1. **Clone and install dependencies**:
   ```bash
   git clone https://github.com/angelo-domingo118/d-agriventory.git d-agriventory
   cd d-agriventory
   composer install && npm install
   ```

2. **Configure environment**:
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

3. **Set up database** (edit `.env` with your MySQL credentials):
   ```env
   DB_CONNECTION=mysql
   DB_DATABASE=d-agriventory
   DB_USERNAME=your_username
   DB_PASSWORD=your_password
   ```

4. **Run migrations and seed data**:
   ```bash
   php artisan migrate && php artisan db:seed
   ```
   
   **Alternative commands** (if you prefer to run them separately):
   ```bash
   # Run database migrations
   php artisan migrate
   
   # Seed the database with initial data
   php artisan db:seed
   ```

## first-run

```bash
# Start development environment (runs server, queue, and vite concurrently)
composer run dev

# Access application at http://localhost:8000
# Default admin credentials created by seeder
```

**Alternative development commands** (if you prefer manual control):
```bash
# Build frontend assets for production
npm run build

# Start Vite development server (with hot reload)
npm run dev

# Start Laravel development server
php artisan serve

# Start task scheduler (in separate terminal, if needed)
php artisan schedule:work
```

## tech-stack

- **Backend**: [Laravel 12](https://laravel.com/docs/12.x), [PHP 8.2+](https://www.php.net/manual/en/)
- **Frontend**: [Livewire 3](https://livewire.laravel.com/docs), [Volt](https://livewire.laravel.com/docs/volt), [Tailwind CSS 4](https://tailwindcss.com/docs/v4-alpha), [Flux UI](https://flux-ui.com/docs), [Alpine.js](https://alpinejs.dev/start-here)
- **Database**: [MySQL 8.0+](https://dev.mysql.com/doc/refman/8.0/en/) (Recommended - partial SQLite support available)
- **Build**: [Vite 6](https://vitejs.dev/guide/), [Concurrently](https://www.npmjs.com/package/concurrently)

## key-features

- **ICS/PAR/IDR Tracking**: Government-compliant asset management
- **Role-based Access**: Admin and Division Inventory Manager permissions
- **Real-time Reporting**: Comprehensive analytics and export capabilities
- **Audit Logging**: Complete transaction history and compliance tracking

## documentation

For complete documentation, see [Documentation Summary](summary.md) - an organized index of all project guides, technical references, and development resources.

---

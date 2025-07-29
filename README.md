# D'Agriventory

A comprehensive inventory management system designed for the Department of Agriculture - Cordillera Administrative Region (DA-CAR) Regional Office in Baguio City. Built with Laravel 12, Livewire 3, and Volt to replace their legacy MS Access-based system with a modern web-based solution for agricultural asset tracking, procurement workflows, and compliance reporting through ICS, PAR, and IDR systems. Features role-based access control, real-time inventory tracking, comprehensive audit logging, and automated report generation for enhanced operational efficiency across the entire Cordillera Administrative Region.

## quick-setup

**Requirements**: PHP 8.2+, Composer, Node.js 18+, MySQL 8.0+

> **⚠️ MySQL Required**: This application strictly requires MySQL as it uses MySQL-specific features for data retrieval that are not available in SQLite or other database systems.

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
   php artisan storage:link
   ```
   
   **Alternative commands** (if you prefer to run them separately):
   ```bash
   # Run database migrations
   php artisan migrate
   
   # Seed the database with initial data
   php artisan db:seed
   
   # Create storage symbolic link
   php artisan storage:link
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

# Start queue worker (in separate terminal)
php artisan queue:work

# Start task scheduler (in separate terminal, if needed)
php artisan schedule:work
```

## tech-stack

- **Backend**: Laravel 12, PHP 8.2+
- **Frontend**: Livewire 3, Volt, Tailwind CSS 4, Flux UI, Alpine.js
- **Database**: MySQL 8.0+ (Required - uses MySQL-specific features)
- **Testing**: Pest (unit/feature), Dusk (browser)
- **Build**: Vite 6, Concurrently

## key-features

- **ICS/PAR/IDR Tracking**: Government-compliant asset management
- **Role-based Access**: Admin and Division Inventory Manager permissions
- **Real-time Reporting**: Comprehensive analytics and export capabilities
- **Audit Logging**: Complete transaction history and compliance tracking

## documentation

- [Complete Documentation Summary](summary.md)
- [Project Overview](docs/overview.md)
- [Development Workflow](docs/workflow.md)
- [Coding Standards](docs/coding-standards.md)
- [Database Guide](docs/database.md)
- [UI Stack Guide](docs/ui-stack.md)
- [Testing Guide](docs/testing.md)
- [Production Deployment](docs/deployment.md)
- [FAQ](docs/faq.md)
- [Contributing](CONTRIBUTING.md)

## support

- 🐛 [Report Issues](../../issues)
- 📖 [View Documentation](docs/)
- 💬 [Discussions](../../discussions)

---

Built with ❤️ for DA-CAR Regional Office, Baguio City

![CodeRabbit Pull Request Reviews](https://img.shields.io/coderabbit/prs/github/angelo-domingo118/d-agriventory?utm_source=oss&utm_medium=github&utm_campaign=angelo-domingo118%2Fd-agriventory&labelColor=171717&color=FF570A&link=https%3A%2F%2Fcoderabbit.ai&label=CodeRabbit+Reviews)

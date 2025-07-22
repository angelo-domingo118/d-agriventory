# D'Agriventory

A comprehensive inventory management system designed for the Department of Agriculture - Cordillera Administrative Region (DA-CAR) Regional Office in Baguio City. Built with Laravel 12, Livewire 3, and Volt to streamline agricultural asset tracking, procurement workflows, and compliance reporting through ICS, PAR, and IDR systems following Philippine government protocols. Features role-based access control, real-time inventory tracking, comprehensive audit logging, and automated report generation for enhanced operational efficiency across the entire Cordillera Administrative Region.

## quick-setup

**Requirements**: PHP 8.2+, Composer, Node.js 18+, MySQL 8.0+

1. **Clone and install dependencies**:
   ```bash
   git clone <repository-url> d-agriventory
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
   DB_DATABASE=d_agriventory
   DB_USERNAME=your_username
   DB_PASSWORD=your_password
   ```

4. **Run migrations and seed data**:
   ```bash
   php artisan migrate && php artisan db:seed
   php artisan storage:link
   ```

## first-run

```bash
# Start development environment (runs server, queue, and vite concurrently)
composer run dev

# Access application at http://localhost:8000
# Default admin credentials created by seeder
```

## tech-stack

- **Backend**: Laravel 12, PHP 8.2+
- **Frontend**: Livewire 3, Volt, Tailwind CSS 4, Flux UI, Alpine.js
- **Database**: MySQL 8.0+
- **Testing**: Pest (unit/feature), Dusk (browser)
- **Build**: Vite 6, Concurrently

## key-features

- **ICS/PAR/IDR Tracking**: Philippine government-compliant asset management
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
- [FAQ](docs/faq.md)
- [Contributing](CONTRIBUTING.md)

## support

- 🐛 [Report Issues](../../issues)
- 📖 [View Documentation](docs/)
- 💬 [Discussions](../../discussions)

---

Built with ❤️ for DA-CAR Regional Office, Baguio City

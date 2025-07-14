# D'Agriventory

<p align="center">
  <img src="public/favicon.svg" alt="D'Agriventory Logo" width="100">
</p>

## About D'Agriventory

D'Agriventory is a comprehensive inventory management system designed specifically for the **Department of Agriculture - Cordillera Administrative Region (DA-CAR) Regional Office** based in Baguio City. The application streamlines the tracking of agricultural inventory, procurement, asset management, and reporting processes for the DA-CAR Regional Office, in compliance with Philippine government standards.

This system helps manage the inventory and assets for the regional office, which serves the entire Cordillera Administrative Region.

## Features

### Core Modules

- **👥 User & Role Management** - Manage users with various roles (admin, division inventory managers)
- **🏢 Organization Management** - Track divisions, suppliers, positions, and organizational hierarchy
- **📝 Procurement System** - Manage contracts, suppliers, and items procurement workflows
- **📦 Inventory Cataloging** - Organize items by primary and secondary categories with detailed specifications
- **📊 Asset Tracking** - Comprehensive tracking through three main systems following Philippine government protocols:
  - **ICS (Inventory Custodian Slip)** - Equipment and asset management
  - **PAR (Property Acknowledgment Receipt)** - Property transfer and assignment
  - **IDR (Inventory and Inspection of Deliveries and Receipts)** - Delivery tracking and verification
- **📈 Reporting & Analytics** - Generate detailed reports by employee, number, batch, and format
- **🔍 Audit Logging** - Keep detailed logs of all system changes and transactions
- **🔄 Transfer Management** - Handle inventory transfers between divisions
- **💊 Consumables Tracking** - Specialized tracking for consumable items and supplies

### Administrative Features

- **🛡️ Permission Management** - Fine-grained access control and permissions
- **👨‍💼 Employee & Division Management** - Hierarchical organization structure
- **🎨 Customizable Interface** - Appearance settings and user preferences
- **🔒 Security Features** - Email verification, password management, and secure authentication

## Built With

D'Agriventory is built on the modern TALL stack:

- **[PHP 8.2+](https://www.php.net)** - Programming language
- **[Laravel 12](https://laravel.com)** - PHP framework
- **[Livewire 3](https://livewire.laravel.com)** - Dynamic frontend components
- **[Volt](https://livewire.laravel.com/docs/volt)** - Single-file Livewire components
- **[Tailwind CSS 4](https://tailwindcss.com)** - Utility-first CSS framework
- **[Flux UI](https://flux-ui.com)** - Modern component library
- **[Alpine.js](https://alpinejs.dev)** - Lightweight JavaScript framework
- **[Vite 6](https://vitejs.dev)** - Fast build tool and asset bundling
- **[Pest](https://pestphp.com)** - Modern testing framework

### Additional Dependencies

- **[DomPDF](https://github.com/barryvdh/laravel-dompdf)** - PDF generation for reports
- **[Laravel Pint](https://laravel.com/docs/pint)** - Code style fixer
- **[Laravel Sail](https://laravel.com/docs/sail)** - Docker development environment
- **[Concurrently](https://github.com/open-cli-tools/concurrently)** - Run multiple processes simultaneously

## Getting Started

### Prerequisites

- **PHP 8.2 or higher** with required extensions
- **Composer** (latest version)
- **Node.js 18+** with npm
- **Database** (MySQL 8.0+, PostgreSQL 13+, or SQLite)
- **Git** for version control

### Installation

1. **Clone the repository**
   ```bash
   git clone https://github.com/yourusername/d-agriventory.git
   cd d-agriventory
   ```

2. **Install PHP dependencies**
   ```bash
   composer install
   ```

3. **Install JavaScript dependencies**
   ```bash
   npm install
   ```

4. **Setup environment configuration**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

5. **Configure your database** in the `.env` file
   ```env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=d_agriventory
   DB_USERNAME=root
   DB_PASSWORD=your_password
   
   # For SQLite (simpler setup)
   # DB_CONNECTION=sqlite
   # DB_DATABASE=/absolute/path/to/database.sqlite
   ```

6. **Run database migrations**
   ```bash
   php artisan migrate
   ```

7. **Seed the database** (recommended for development)
   ```bash
   php artisan db:seed
   ```

8. **Create storage symlink**
   ```bash
   php artisan storage:link
   ```

### Running the Application

#### Development Mode

For development with hot-reloading and automatic server restart:

```bash
composer run dev
```

This command concurrently runs:
- 🚀 Laravel development server (`php artisan serve`)
- 🔄 Queue listener (`php artisan queue:listen`)
- ⚡ Vite development server with hot reload (`npm run dev`)

#### Individual Commands

You can also run services individually:

```bash
# Laravel server only
php artisan serve

# Vite development server only
npm run dev

# Queue processing
php artisan queue:listen
```

#### Production Build

For production deployment:

```bash
npm run build
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## Development Workflow

### Testing

Run the comprehensive test suite:

```bash
# Run all tests (recommended)
composer test

# Using Pest directly (faster during development)
./vendor/bin/pest

# Run specific test file
./vendor/bin/pest tests/Feature/AdminTest.php

# Run with coverage (if configured)
./vendor/bin/pest --coverage
```

### Code Quality

Format code using Laravel Pint:

```bash
# Fix all code style issues
./vendor/bin/pint

# Check for issues without fixing
./vendor/bin/pint --test

# Format only modified files
./vendor/bin/pint --dirty
```

### Available Scripts

```bash
# Development (concurrently runs server, queue, and vite)
composer run dev

# Testing (clears config cache and runs tests)
composer test

# Code formatting
./vendor/bin/pint

# Clear all caches
php artisan optimize:clear
```

## Project Structure

D'Agriventory follows Laravel conventions with organized feature-based structure:

```
app/
├── Enums/           # Enumeration classes (User roles, etc.)
├── Http/
│   ├── Controllers/ # Traditional controllers & API endpoints
│   └── Middleware/  # Custom middleware (permissions, roles)
├── Livewire/        # 🎯 Primary business logic location
│   ├── Actions/     # User actions (logout, etc.)
│   ├── Admin/       # Administrative components
│   │   ├── Data/    # Data management (employees, suppliers, items)
│   │   ├── Inventory/ # Inventory management (ICS, PAR, IDR)
│   │   └── System/  # System management (users, permissions, audit)
│   └── Traits/      # Reusable component traits
├── Models/          # Eloquent models and relationships
├── Policies/        # Authorization policies
└── Services/        # Business logic services

resources/views/
├── components/      # Reusable Blade components
├── livewire/        # Livewire component views
│   ├── admin/       # Admin interface views
│   ├── auth/        # Authentication views
│   └── settings/    # User settings views
└── layouts/         # Application layouts

tests/
├── Feature/         # Integration and feature tests
└── Unit/           # Unit tests
```

### Key Architecture Patterns

- **Livewire Components**: Primary business logic container
- **Trait-based Features**: Reusable functionality across components
- **Policy-based Authorization**: Centralized permission management
- **Service Classes**: Complex business logic abstraction
- **Factory Pattern**: Comprehensive test data generation

## User Roles & Permissions

The system supports multiple user roles aligned with DA-CAR organizational structure:

- **🔴 Admin**: Full system access, user management, system configuration for department administrators
- **🟡 Division Inventory Manager**: Division-specific inventory management for regional offices and units

## API Documentation

The system includes API endpoints for integration:

- **Admin API**: `/api/admin/*` - Administrative operations
- **Authentication**: Standard Laravel authentication endpoints
- **Permissions**: `/api/admin/permissions` - Permission management

## Contributing

1. **Fork the repository**
2. **Create a feature branch** (`git checkout -b feature/amazing-feature`)
3. **Follow the development workflow** outlined in `docs/workflow.md`
4. **Write tests** for your changes
5. **Ensure code quality** with Pint formatting
6. **Commit your changes** (`git commit -m 'Add amazing feature'`)
7. **Push to the branch** (`git push origin feature/amazing-feature`)
8. **Open a Pull Request**

## Documentation

- 📖 **[Workflow Guide](docs/workflow.md)** - Detailed development workflow
- 🗄️ **[Entity Relationship Diagram](docs/erd.md)** - Database structure
- 🚀 **[Laravel Documentation](https://laravel.com/docs)** - Framework reference
- ⚡ **[Livewire Documentation](https://livewire.laravel.com/docs)** - Component reference

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## Support

For support and questions:

- 📧 Create an issue in this repository
- 📚 Check the documentation in the `docs/` directory
- 🔍 Review existing issues and discussions

---

<p align="center">
Built with ❤️ for the Department of Agriculture - CAR Regional Office<br>
<em>Supporting agricultural development from the Baguio City Regional Office</em>
</p>

# Project Overview

D'Agriventory serves as the central inventory management platform for the Department of Agriculture - Cordillera Administrative Region (DA-CAR) Regional Office.

## purpose

The system replaces the legacy MS Access-based inventory management with a modern web-based solution for agricultural assets across the Cordillera region. Built to comply with government standards, it manages three core inventory systems: ICS (Inventory Custodian Slip), PAR (Property Acknowledgment Receipt), and IDR (Inventory and Inspection of Deliveries and Receipts). The platform serves regional administrators and division inventory managers with role-based access control, comprehensive audit trails, and automated report generation capabilities that were not possible with their previous MS Access setup.

## tech-stack

| Component | Technology | Version | Purpose | Documentation |
|-----------|------------|---------|---------|---------------|
| **Backend Framework** | Laravel | 12.0 | Core application logic and API | [Laravel 12.x Docs](https://laravel.com/docs/12.x) |
| **Frontend Components** | Livewire | 3.x | Dynamic UI without JavaScript | [Livewire 3 Docs](https://livewire.laravel.com/docs) |
| **Single-File Components** | Volt | 1.7 | Simplified Livewire components | [Volt Documentation](https://livewire.laravel.com/docs/volt) |
| **CSS Framework** | Tailwind CSS | 4.0 | Utility-first styling | [Tailwind CSS v4 Alpha](https://tailwindcss.com/docs/v4-alpha) |
| **Component Library** | Flux UI | 2.1 | Pre-built interface components | [Flux UI Documentation](https://flux-ui.com/docs) |
| **JavaScript** | Alpine.js | 3.14 | Lightweight interactivity | [Alpine.js Documentation](https://alpinejs.dev/start-here) |
| **Build Tool** | Vite | 6.0 | Asset bundling and HMR | [Vite Documentation](https://vitejs.dev/guide/) |
| **Testing Framework** | Pest | 3.8 | Modern PHP testing | [Pest Documentation](https://pestphp.com/docs) |
| **Database** | MySQL | 8.0+ | Primary data storage | [MySQL 8.0 Documentation](https://dev.mysql.com/doc/refman/8.0/en/) |
| **PDF Generation** | DomPDF | 3.1 | Report generation | [DomPDF Documentation](https://github.com/dompdf/dompdf) |
| **Code Standards** | Laravel Pint | 1.18 | Automated formatting | [Laravel Pint Docs](https://laravel.com/docs/12.x/pint) |

## directory-structure

```
d-agriventory/
├── app/
│   ├── Enums/
│   │   └── User/
│   │       └── Role.php        # User role enumerations
│   ├── Exceptions/
│   │   └── InvalidRoleException.php # Custom application exceptions
│   ├── Helpers/
│   │   └── TextHelper.php      # Utility functions and helpers
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Api/
│   │   │   │   └── Admin/
│   │   │   │       └── PermissionsController.php
│   │   │   ├── Auth/
│   │   │   │   └── VerifyEmailController.php
│   │   │   └── Controller.php  # Base controller
│   │   └── Middleware/
│   │       ├── HasAdminPermission.php
│   │       ├── IsAdmin.php
│   │       └── IsInventoryManager.php
│   ├── Livewire/
│   │   ├── Actions/
│   │   │   └── Logout.php      # Livewire actions
│   │   └── Traits/
│   │       ├── HasCatalogItems.php
│   │       └── HasItemSpecifications.php
│   ├── Models/
│   │   ├── Traits/
│   │   │   └── ClearsDashboardCache.php
│   │   ├── User.php            # Primary user model
│   │   ├── AdminUser.php       # Admin user relationships
│   │   ├── Division.php        # Organizational divisions
│   │   ├── Employee.php        # Employee records
│   │   ├── ItemsCatalog.php    # Inventory catalog
│   │   ├── IcsNumber.php       # ICS tracking
│   │   ├── ParNumber.php       # PAR tracking
│   │   ├── IdrNumber.php       # IDR tracking
│   │   └── [+15 more models]   # Additional domain models
│   ├── Policies/
│   │   └── AdminPolicy.php     # Authorization policies
│   ├── Providers/
│   │   ├── AppServiceProvider.php
│   │   ├── AuthServiceProvider.php
│   │   └── VoltServiceProvider.php
│   └── Services/
│       └── PermissionService.php # Business logic services
├── bootstrap/
│   ├── app.php                 # Application bootstrapping
│   ├── cache/                  # Bootstrap cache files
│   └── providers.php           # Service provider registration
├── config/
│   ├── app.php                 # Application configuration
│   ├── auth.php                # Authentication settings
│   ├── database.php            # Database connections
│   ├── cache.php               # Cache configuration
│   ├── mail.php                # Email settings
│   ├── queue.php               # Queue configuration
│   └── [+6 more config files]  # Additional configurations
├── database/
│   ├── factories/
│   │   ├── UserFactory.php     # User model factory
│   │   ├── AdminUserFactory.php
│   │   ├── DivisionFactory.php
│   │   ├── EmployeeFactory.php
│   │   ├── ItemsCatalogFactory.php
│   │   └── [+15 more factories] # Additional model factories
│   ├── migrations/
│   │   ├── 0001_01_01_000000_create_users_table.php
│   │   ├── 0001_01_01_000001_create_cache_table.php
│   │   ├── 2025_06_24_081620_create_divisions_table.php
│   │   ├── 2025_06_24_081755_create_secondary_categories_table.php
│   │   └── [+25 more migrations] # Database schema definitions
│   └── seeders/
│       ├── data/               # Seeder data files
│       │   ├── desktop_computers_data.php
│       │   ├── ics_data.php
│       │   └── ics_item_batches_data.php
│       ├── AdminUserSeeder.php
│       ├── DivisionSeeder.php
│       ├── UserSeeder.php
│       ├── EmployeeSeeder.php
│       ├── ItemsCatalogSeeder.php
│       └── [+10 more seeders]  # Development and production data
├── docs/                       # Project documentation
│   ├── overview.md
│   ├── deployment.md
│   ├── database.md
│   ├── testing.md
│   ├── ui-stack.md
│   ├── workflow.md
│   ├── coding-standards.md
│   ├── faq.md
│   └── erd.md                  # Entity relationship diagram
├── public/                     # Web server document root
│   ├── build/                  # Compiled assets (generated)
│   ├── favicon.ico
│   ├── favicon.svg
│   ├── apple-touch-icon.png
│   ├── robots.txt
│   └── index.php               # Application entry point
├── resources/
│   ├── css/
│   │   ├── app.css             # Main stylesheet with Tailwind
│   │   └── animated-grid.css   # Custom animations
│   ├── js/
│   │   └── app.js              # JavaScript entry point
│   └── views/
│       ├── components/
│       │   ├── admin/
│       │   │   ├── inventory/
│       │   │   │   ├── ics/
│       │   │   │   │   ├── card.blade.php
│       │   │   │   │   └── table-row.blade.php
│       │   │   │   ├── par/
│       │   │   │   │   ├── card.blade.php
│       │   │   │   │   └── table-row.blade.php
│       │   │   │   └── placeholder-layout.blade.php
│       │   │   ├── layout.blade.php
│       │   │   ├── permissions-manager.blade.php
│       │   │   └── section.blade.php
│       │   ├── dashboard/
│       │   │   ├── action-card.blade.php
│       │   │   └── stat-card.blade.php
│       │   ├── inventory-manager/
│       │   │   └── layout.blade.php
│       │   ├── layouts/
│       │   │   ├── app/
│       │   │   │   ├── header.blade.php
│       │   │   │   └── sidebar.blade.php
│       │   │   ├── auth/
│       │   │   │   ├── card.blade.php
│       │   │   │   ├── simple.blade.php
│       │   │   │   └── split.blade.php
│       │   │   ├── app.blade.php
│       │   │   └── auth.blade.php
│       │   ├── settings/
│       │   │   └── layout.blade.php
│       │   ├── tree/
│       │   │   ├── index.blade.php
│       │   │   └── item.blade.php
│       │   ├── app-logo.blade.php
│       │   ├── app-logo-icon.blade.php
│       │   └── [+7 more components]
│       ├── flux/
│       │   ├── icon/
│       │   │   ├── arrows-right-left.blade.php
│       │   │   ├── arrows-trending-up.blade.php
│       │   │   ├── book-open-text.blade.php
│       │   │   └── [+39 more icons]
│       │   ├── navlist/
│       │   │   └── group.blade.php
│       │   └── toast.blade.php
│       ├── livewire/
│       │   ├── admin/
│       │   │   ├── data/
│       │   │   │   ├── employees-and-divisions/
│       │   │   │   │   ├── divisions/
│       │   │   │   │   │   ├── create.blade.php
│       │   │   │   │   │   ├── edit.blade.php
│       │   │   │   │   │   └── index.blade.php
│       │   │   │   │   ├── employees/
│       │   │   │   │   │   ├── create.blade.php
│       │   │   │   │   │   ├── edit.blade.php
│       │   │   │   │   │   └── index.blade.php
│       │   │   │   │   ├── positions/
│       │   │   │   │   │   ├── create.blade.php
│       │   │   │   │   │   ├── edit.blade.php
│       │   │   │   │   │   └── index.blade.php
│       │   │   │   │   ├── index.blade.php
│       │   │   │   │   └── tree-view.blade.php
│       │   │   │   ├── items-and-categories/
│       │   │   │   │   ├── items-catalog/
│       │   │   │   │   │   ├── create.blade.php
│       │   │   │   │   │   ├── edit.blade.php
│       │   │   │   │   │   └── index.blade.php
│       │   │   │   │   ├── primary-categories/
│       │   │   │   │   │   ├── create.blade.php
│       │   │   │   │   │   ├── edit.blade.php
│       │   │   │   │   │   └── index.blade.php
│       │   │   │   │   ├── secondary-categories/
│       │   │   │   │   │   ├── create.blade.php
│       │   │   │   │   │   ├── edit.blade.php
│       │   │   │   │   │   └── index.blade.php
│       │   │   │   │   ├── index.blade.php
│       │   │   │   │   └── tree-view.blade.php
│       │   │   │   └── suppliers-and-contracts/
│       │   │   │       ├── contracts/
│       │   │   │       │   ├── create.blade.php
│       │   │   │       │   ├── edit.blade.php
│       │   │   │       │   └── index.blade.php
│       │   │   │       ├── suppliers/
│       │   │   │       │   ├── create.blade.php
│       │   │   │       │   ├── edit.blade.php
│       │   │   │       │   └── index.blade.php
│       │   │   │       ├── index.blade.php
│       │   │   │       └── tree-view.blade.php
│       │   │   ├── inventory/
│       │   │   │   ├── consumables/
│       │   │   │   │   ├── details.blade.php
│       │   │   │   │   ├── edit.blade.php
│       │   │   │   │   └── index.blade.php
│       │   │   │   ├── ics/
│       │   │   │   │   ├── create.blade.php
│       │   │   │   │   ├── edit.blade.php
│       │   │   │   │   └── index.blade.php
│       │   │   │   ├── idr/
│       │   │   │   │   ├── create.blade.php
│       │   │   │   │   ├── edit.blade.php
│       │   │   │   │   └── index.blade.php
│       │   │   │   └── par/
│       │   │   │       ├── create.blade.php
│       │   │   │       ├── edit.blade.php
│       │   │   │       └── index.blade.php
│       │   │   ├── main/
│       │   │   │   ├── dashboard.blade.php
│       │   │   │   └── reports/
│       │   │   │       ├── formats/
│       │   │   │       │   ├── ics/
│       │   │   │       │   │   ├── by-employee.blade.php
│       │   │   │       │   │   └── by-number.blade.php
│       │   │   │       │   ├── idr/
│       │   │   │       │   │   ├── batch-combined.blade.php
│       │   │   │       │   │   ├── batch-detailed.blade.php
│       │   │   │       │   │   └── by-employee.blade.php
│       │   │   │       │   ├── par/
│       │   │   │       │   │   ├── by-employee.blade.php
│       │   │   │       │   │   └── by-number.blade.php
│       │   │   │       │   └── unavailable.blade.php
│       │   │   │       └── index.blade.php
│       │   │   └── system/
│       │   │       ├── audit-logs/
│       │   │       │   └── index.blade.php
│       │   │       └── users/
│       │   │           ├── create.blade.php
│       │   │           ├── edit.blade.php
│       │   │           └── index.blade.php
│       │   ├── auth/
│       │   │   ├── confirm-password.blade.php
│       │   │   ├── forgot-password.blade.php
│       │   │   ├── login.blade.php
│       │   │   ├── register.blade.php
│       │   │   ├── reset-password.blade.php
│       │   │   └── verify-email.blade.php
│       │   ├── inventory-manager/
│       │   │   ├── consumables/
│       │   │   │   ├── create.blade.php
│       │   │   │   ├── edit.blade.php
│       │   │   │   └── index.blade.php
│       │   │   ├── items/
│       │   │   │   └── index.blade.php
│       │   │   ├── reports/
│       │   │   │   └── index.blade.php
│       │   │   ├── transfers/
│       │   │   │   ├── create.blade.php
│       │   │   │   └── index.blade.php
│       │   │   └── dashboard.blade.php
│       │   └── settings/
│       │       ├── appearance.blade.php
│       │       ├── delete-user-form.blade.php
│       │       ├── password.blade.php
│       │       └── profile.blade.php
│       ├── partials/
│       │   ├── navigation/
│       │   │   ├── admin.blade.php
│       │   │   └── inventory-manager.blade.php
│       │   ├── head.blade.php
│       │   └── settings-heading.blade.php
│       ├── vendor/
│       │   └── livewire/
│       │       ├── simple-tailwind.blade.php
│       │       └── tailwind.blade.php
│       └── welcome.blade.php
├── routes/
│   ├── web.php                 # Main web routes
│   ├── auth.php                # Authentication routes
│   ├── admin.php               # Admin panel routes
│   └── console.php             # Artisan commands
├── storage/
│   ├── app/
│   │   ├── public/             # Publicly accessible files
│   │   └── private/            # Private application files
│   ├── framework/
│   │   ├── cache/              # Framework cache files
│   │   ├── sessions/           # Session files
│   │   ├── testing/            # Testing cache
│   │   └── views/              # Compiled Blade views
│   └── logs/                   # Application logs
└── tests/                      # Automated test suites
    ├── Feature/                # Feature tests
    │   ├── Admin/
    │   │   ├── Data/
    │   │   │   ├── ContractManagementTest.php
    │   │   │   ├── DivisionManagementTest.php
    │   │   │   ├── EmployeeManagementTest.php
    │   │   │   └── [+5 more tests]
    │   │   └── Inventory/
    │   │       ├── ConsumablesDivisionViewTest.php
    │   │       ├── ConsumablesPageTest.php
    │   │       ├── IcsCreationTest.php
    │   │       └── [+2 more tests]
    │   ├── Auth/
    │   │   ├── AuthenticationTest.php
    │   │   ├── EmailVerificationTest.php
    │   │   └── [+3 more tests]
    │   ├── Settings/
    │   │   ├── PasswordUpdateTest.php
    │   │   └── ProfileUpdateTest.php
    │   ├── AdminMiddlewareTest.php
    │   ├── AdminUserManagementTest.php
    │   └── DashboardTest.php
    ├── Unit/                   # Unit tests
    │   ├── Admin/
    │   │   └── Inventory/
    │   ├── ExampleTest.php
    │   └── PermissionServiceTest.php
    ├── TestCase.php            # Base test class
    └── Pest.php                # Pest configuration
```

## architecture-patterns

**Livewire-First Development**: Business logic resides primarily in Livewire components rather than traditional controllers, enabling reactive interfaces with minimal JavaScript.

**Trait-Based Features**: Reusable functionality through traits like `HasCatalogItems` and `HasItemSpecifications` promotes code reuse across components.

**Policy-Driven Authorization**: Centralised permission management through Laravel policies ensures consistent access control throughout the application.

**Service Layer**: Complex business operations are abstracted into dedicated service classes, maintaining clean component code and improving testability.

## role-hierarchy

- **Admin**: Full system access including user management, system configuration, and all inventory operations
- **Division Inventory Manager**: Division-specific inventory management with restricted administrative capabilities

## compliance-features

The system implements government inventory standards through structured workflows, mandatory approval processes, comprehensive audit logging, and standardised reporting formats required for government accountability and transparency.
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
| **Browser Testing** | Laravel Dusk | 8.3 | End-to-end automation | [Laravel Dusk Docs](https://laravel.com/docs/12.x/dusk) |
| **Database** | MySQL | 8.0+ | Primary data storage | [MySQL 8.0 Documentation](https://dev.mysql.com/doc/refman/8.0/en/) |
| **PDF Generation** | DomPDF | 3.1 | Report generation | [DomPDF Documentation](https://github.com/dompdf/dompdf) |
| **Code Standards** | Laravel Pint | 1.18 | Automated formatting | [Laravel Pint Docs](https://laravel.com/docs/12.x/pint) |

## directory-structure

```
d-agriventory/
├── app/
│   ├── Enums/                  # Type-safe enumerations (User roles)
│   ├── Exceptions/             # Custom application exceptions
│   ├── Helpers/                # Utility functions and helpers
│   ├── Http/                   # Controllers, middleware, requests
│   ├── Livewire/               # Primary business logic components
│   ├── Models/                 # Eloquent models and relationships
│   ├── Policies/               # Authorization policies
│   ├── Providers/              # Service providers and bootstrapping
│   └── Services/               # Business logic services
├── bootstrap/                  # Application bootstrapping
├── config/                     # Configuration files
├── database/
│   ├── factories/              # Model factories for testing
│   ├── migrations/             # Database schema definitions
│   └── seeders/                # Development and production data
├── docs/                       # Project documentation
├── public/                     # Web server document root
├── resources/
│   ├── css/                    # Stylesheets and Tailwind
│   ├── js/                     # JavaScript entry points
│   └── views/                  # Blade templates and components
├── routes/                     # HTTP route definitions
├── storage/                    # File storage and caching
└── tests/                      # Automated test suites
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
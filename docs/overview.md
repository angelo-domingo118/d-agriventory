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

| **Database** | MySQL | 8.0+ | Primary data storage | [MySQL 8.0 Documentation](https://dev.mysql.com/doc/refman/8.0/en/) |
| **PDF Generation** | DomPDF | 3.1 | Report generation | [DomPDF Documentation](https://github.com/dompdf/dompdf) |
| **Code Standards** | Laravel Pint | 1.18 | Automated formatting | [Laravel Pint Docs](https://laravel.com/docs/12.x/pint) |

## directory-structure

```
d-agriventory/
├── app/
│   ├── Enums/User/Role.php        # User roles (ADMIN, INVENTORY_MANAGER)
│   ├── Http/Middleware/           # Custom middleware for role-based access
│   ├── Livewire/Traits/           # Shared component functionality
│   ├── Models/                    # Core domain models
│   │   ├── User.php               # Authentication and user management
│   │   ├── IcsNumber.php          # Inventory Custodian Slip tracking
│   │   ├── ParNumber.php          # Property Acknowledgment Receipt
│   │   ├── IdrNumber.php          # Inventory Delivery Receipt
│   │   └── [+20 more models]      # Complete inventory domain
│   └── Services/PermissionService.php # Permission management logic
├── database/
│   ├── migrations/                # Database schema definitions
│   └── seeders/
│       ├── data/                  # Static data files (divisions, positions)
│       └── [seeders]              # Database population scripts
├── resources/views/
│   ├── components/                # Reusable Blade components
│   │   ├── admin/inventory/       # Admin inventory UI components
│   │   │   ├── ics/               # ICS-specific components
│   │   │   └── par/               # PAR-specific components
│   │   ├── layouts/               # Application layouts
│   │   └── dashboard/             # Dashboard UI components
│   ├── flux/icon/                 # Custom SVG icons for domain
│   ├── livewire/                  # Volt single-file components
│   │   ├── admin/                 # Admin panel functionality
│   │   │   ├── data/              # Master data management
│   │   │   ├── inventory/         # ICS/PAR/IDR management
│   │   │   ├── main/              # Dashboard and reports
│   │   │   └── system/            # User and audit management
│   │   ├── inventory-manager/     # Division manager interface
│   │   ├── auth/                  # Authentication components
│   │   └── settings/              # User preferences
│   └── partials/navigation/       # Role-specific navigation menus
├── docs/                       # Project documentation
│   ├── overview.md             # Project overview and architecture
│   ├── database.md             # Database patterns and seeding
│   ├── ui-stack.md             # Frontend technology guide
│   ├── workflow.md             # Development workflow and Git practices
│   └── erd.md                  # Entity relationship diagram
└── routes/
    ├── web.php                    # Public routes
    ├── auth.php                   # Authentication routes
    └── admin.php                  # Admin panel routes
```

## architecture-patterns

**Livewire-First Development**: Business logic resides primarily in Livewire components rather than traditional controllers, enabling reactive interfaces with minimal JavaScript.

**Trait-Based Features**: Reusable functionality through traits like `HasCatalogItems` and `HasItemSpecifications` promotes code reuse across components.

**Policy-Driven Authorization**: Centralised permission management through Laravel policies ensures consistent access control throughout the application.

**Service Layer**: Complex business operations are abstracted into dedicated service classes, maintaining clean component code and improving testability.

## role-hierarchy

- **Admin**: Full system access including user management, system configuration, and all inventory operations
- **Division Inventory Manager**: Division-specific inventory management with restricted administrative capabilities



## database-schema

The system's database architecture is built around three core inventory management systems (ICS, PAR, IDR) with comprehensive relationship mapping and audit capabilities. The schema supports multi-system integration, organizational hierarchy mapping, procurement traceability, component-level tracking, and complete audit trails.

For detailed database structure, see the [Entity-Relationship Diagram](erd.md).
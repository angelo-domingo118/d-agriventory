# Project Overview

D'Agriventory serves as the central inventory management platform for the Department of Agriculture - Cordillera Administrative Region (DA-CAR) Regional Office.

## purpose

The system digitises traditional paper-based inventory processes for agricultural assets across the Cordillera region. Built to comply with Philippine government protocols, it manages three core inventory systems: ICS (Inventory Custodian Slip), PAR (Property Acknowledgment Receipt), and IDR (Inventory and Inspection of Deliveries and Receipts). The platform serves regional administrators and division inventory managers with role-based access control, comprehensive audit trails, and automated reporting capabilities.

## tech-stack

| Component | Technology | Version | Purpose |
|-----------|------------|---------|---------|
| **Backend Framework** | Laravel | 12.0 | Core application logic and API |
| **Frontend Components** | Livewire | 3.x | Dynamic UI without JavaScript |
| **Single-File Components** | Volt | 1.7 | Simplified Livewire components |
| **CSS Framework** | Tailwind CSS | 4.0 | Utility-first styling |
| **Component Library** | Flux UI | 2.1 | Pre-built interface components |
| **JavaScript** | Alpine.js | 3.14 | Lightweight interactivity |
| **Build Tool** | Vite | 6.0 | Asset bundling and HMR |
| **Testing Framework** | Pest | 3.8 | Modern PHP testing |
| **Browser Testing** | Laravel Dusk | 8.3 | End-to-end automation |
| **Database** | MySQL | 8.0+ | Primary data storage |
| **PDF Generation** | DomPDF | 3.1 | Report generation |
| **Code Standards** | Laravel Pint | 1.18 | Automated formatting |

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

The system implements Philippine government inventory standards through structured workflows, mandatory approval processes, comprehensive audit logging, and standardised reporting formats required for government accountability and transparency.
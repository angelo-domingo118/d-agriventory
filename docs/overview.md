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
├── app/                        # Core application logic
│   ├── Enums/                  # Type-safe enumerations
│   │   └── User/
│   │       └── Role.php        # Defines user roles (ADMIN, INVENTORY_MANAGER)
│   ├── Exceptions/             # Custom application exceptions
│   │   └── InvalidRoleException.php # Thrown when invalid user role is specified
│   ├── Helpers/                # Utility functions and helpers
│   │   └── TextHelper.php      # Text manipulation utilities (truncate, format, etc.)
│   ├── Http/                   # HTTP layer components
│   │   ├── Controllers/        # Traditional Laravel controllers
│   │   │   ├── Api/            # API endpoint controllers
│   │   │   │   └── Admin/
│   │   │   │       └── PermissionsController.php # Admin permission management API
│   │   │   ├── Auth/           # Authentication controllers
│   │   │   │   └── VerifyEmailController.php # Email verification handling
│   │   │   └── Controller.php  # Base controller with common functionality
│   │   └── Middleware/         # HTTP middleware for request filtering
│   │       ├── HasAdminPermission.php # Checks specific admin permissions
│   │       ├── IsAdmin.php     # Verifies user has admin role
│   │       └── IsInventoryManager.php # Verifies user is inventory manager
│   ├── Livewire/               # Livewire component classes and traits
│   │   ├── Actions/            # Reusable Livewire actions
│   │   │   └── Logout.php      # User logout action component
│   │   └── Traits/             # Shared Livewire component functionality
│   │       ├── HasCatalogItems.php # Manages catalog item operations
│   │       └── HasItemSpecifications.php # Handles item specification logic
│   ├── Models/                 # Eloquent models for database interaction
│   │   ├── Traits/             # Shared model functionality
│   │   │   └── ClearsDashboardCache.php # Clears dashboard cache on model changes
│   │   ├── User.php            # Core user model with authentication
│   │   ├── AdminUser.php       # Admin user role and permissions
│   │   ├── Division.php        # Organizational divisions/departments
│   │   ├── Employee.php        # Employee records and assignments
│   │   ├── ItemsCatalog.php    # Master catalog of inventory items
│   │   ├── IcsNumber.php       # Inventory Custodian Slip tracking
│   │   ├── ParNumber.php       # Property Acknowledgment Receipt tracking
│   │   ├── IdrNumber.php       # Inventory and Inspection Delivery Receipt
│   │   ├── Contract.php        # Procurement contracts
│   │   ├── Supplier.php        # Vendor/supplier information
│   │   ├── PrimaryCategory.php # Top-level item categories
│   │   ├── SecondaryCategory.php # Sub-categories for items
│   │   ├── ItemSpecification.php # Detailed item specifications
│   │   ├── ConsumableItem.php  # Consumable inventory items
│   │   ├── AuditLog.php        # System audit trail
│   │   └── [+10 more models]   # Additional domain models
│   ├── Policies/               # Authorization policies
│   │   └── AdminPolicy.php     # Admin access control policies
│   ├── Providers/              # Laravel service providers
│   │   ├── AppServiceProvider.php # Main application service bindings
│   │   ├── AuthServiceProvider.php # Authentication and authorization setup
│   │   └── VoltServiceProvider.php # Volt component registration
│   └── Services/               # Business logic services
│       └── PermissionService.php # Permission management business logic
├── bootstrap/                  # Application bootstrapping files
│   ├── app.php                 # Creates Laravel application instance
│   ├── cache/                  # Bootstrap cache files for performance
│   └── providers.php           # Service provider registration and discovery
├── config/                     # Application configuration files
│   ├── app.php                 # Core application settings (name, env, timezone)
│   ├── auth.php                # Authentication guards, providers, passwords
│   ├── database.php            # Database connections and settings
│   ├── cache.php               # Cache stores and drivers configuration
│   ├── mail.php                # Email driver and SMTP settings
│   ├── queue.php               # Queue connections and job settings
│   ├── filesystems.php         # File storage disks and drivers
│   ├── logging.php             # Log channels and handlers
│   ├── services.php            # Third-party service configurations
│   ├── session.php             # Session driver and lifetime settings
│   └── [+2 more config files]  # Additional configurations
├── database/                   # Database schema and seeding
│   ├── factories/              # Model factories for generating test data
│   │   ├── UserFactory.php     # Creates fake users for testing
│   │   ├── AdminUserFactory.php # Creates admin user test data
│   │   ├── DivisionFactory.php # Generates organizational divisions
│   │   ├── EmployeeFactory.php # Creates employee test records
│   │   ├── ItemsCatalogFactory.php # Generates inventory catalog items
│   │   ├── ContractFactory.php # Creates procurement contract data
│   │   ├── SupplierFactory.php # Generates supplier information
│   │   ├── IcsNumberFactory.php # Creates ICS tracking numbers
│   │   ├── ParNumberFactory.php # Generates PAR tracking data
│   │   └── [+10 more factories] # Additional model factories
│   ├── migrations/             # Database schema version control
│   │   ├── 0001_01_01_000000_create_users_table.php # Core user table
│   │   ├── 0001_01_01_000001_create_cache_table.php # Cache storage table
│   │   ├── 2025_06_24_081620_create_divisions_table.php # Organizational divisions
│   │   ├── 2025_06_24_081755_create_secondary_categories_table.php # Item sub-categories
│   │   ├── 2025_06_24_082113_create_ics_number_table.php # ICS tracking system
│   │   ├── 2025_06_24_082140_create_par_number_table.php # PAR tracking system
│   │   ├── 2025_06_24_082200_create_idr_number_table.php # IDR tracking system
│   │   ├── 2025_06_24_082300_create_contracts_table.php # Procurement contracts
│   │   ├── 2025_06_24_082400_create_audit_logs_table.php # System audit trail
│   │   └── [+20 more migrations] # Additional database tables
│   └── seeders/                # Database population scripts
│       ├── data/               # Static seeder data files
│       │   ├── desktop_computers_data.php # Computer specifications
│       │   ├── ics_data.php    # Sample ICS records
│       │   ├── ics_item_batches_data.php # ICS batch data
│       │   ├── divisions_data.php # DA-CAR organizational structure
│       │   └── positions_data.php # Government position definitions
│       ├── DatabaseSeeder.php  # Main seeder orchestrator
│       ├── AdminUserSeeder.php # Creates default admin accounts
│       ├── DivisionSeeder.php  # Seeds organizational divisions
│       ├── UserSeeder.php      # Creates sample users
│       ├── EmployeeSeeder.php  # Populates employee records
│       ├── ItemsCatalogSeeder.php # Seeds inventory catalog
│       ├── PrimaryCategorySeeder.php # Creates main item categories
│       ├── SecondaryCategorySeeder.php # Creates item sub-categories
│       └── [+5 more seeders]   # Additional data seeders
├── docs/                       # Project documentation
│   ├── overview.md             # Project overview and architecture
│   ├── deployment.md           # Production deployment guide
│   ├── database.md             # Database patterns and seeding
│   ├── testing.md              # Testing strategy and examples
│   ├── ui-stack.md             # Frontend technology guide
│   ├── workflow.md             # Development workflow and Git practices
│   ├── coding-standards.md     # Code style and conventions
│   ├── faq.md                  # Frequently asked questions
│   └── erd.md                  # Entity relationship diagram
├── public/                     # Web server document root (publicly accessible)
│   ├── build/                  # Compiled assets (generated by Vite)
│   │   ├── assets/             # Versioned CSS and JS files
│   │   └── manifest.json       # Asset manifest for cache busting
│   ├── favicon.ico             # Browser favicon (ICO format)
│   ├── favicon.svg             # Modern SVG favicon
│   ├── apple-touch-icon.png    # iOS home screen icon
│   ├── robots.txt              # Search engine crawler instructions
│   └── index.php               # Laravel application entry point
├── resources/                  # Application resources (not publicly accessible)
│   ├── css/                    # Stylesheet source files
│   │   ├── app.css             # Main stylesheet with Tailwind CSS configuration
│   │   └── animated-grid.css   # Custom CSS animations for UI components
│   ├── js/                     # JavaScript source files
│   │   └── app.js              # Main JavaScript entry point (Alpine.js, etc.)
│   └── views/                  # Blade template files
│       ├── components/         # Reusable Blade components
│       │   ├── admin/          # Admin-specific UI components
│       │   │   ├── inventory/  # Inventory management components
│       │   │   │   ├── ics/    # ICS (Inventory Custodian Slip) components
│       │   │   │   │   ├── card.blade.php # ICS item display card
│       │   │   │   │   └── table-row.blade.php # ICS table row component
│       │   │   │   ├── par/    # PAR (Property Acknowledgment Receipt) components
│       │   │   │   │   ├── card.blade.php # PAR item display card
│       │   │   │   │   └── table-row.blade.php # PAR table row component
│       │   │   │   └── placeholder-layout.blade.php # Empty state layout
│       │   │   ├── layout.blade.php # Admin section wrapper
│       │   │   ├── permissions-manager.blade.php # Permission management UI
│       │   │   └── section.blade.php # Admin content section wrapper
│       │   ├── dashboard/      # Dashboard UI components
│       │   │   ├── action-card.blade.php # Clickable action cards
│       │   │   └── stat-card.blade.php # Statistics display cards
│       │   ├── inventory-manager/ # Inventory manager specific components
│       │   │   └── layout.blade.php # Inventory manager section wrapper
│       │   ├── layouts/        # Main application layouts
│       │   │   ├── app/        # Authenticated app layout components
│       │   │   │   ├── header.blade.php # Top navigation bar
│       │   │   │   └── sidebar.blade.php # Main navigation sidebar
│       │   │   ├── auth/       # Authentication layout variants
│       │   │   │   ├── card.blade.php # Card-style auth layout
│       │   │   │   ├── simple.blade.php # Minimal auth layout
│       │   │   │   └── split.blade.php # Split-screen auth layout
│       │   │   ├── app.blade.php # Main authenticated layout
│       │   │   └── auth.blade.php # Authentication pages layout
│       │   ├── settings/       # User settings components
│       │   │   └── layout.blade.php # Settings page wrapper
│       │   ├── tree/           # Tree view components for hierarchical data
│       │   │   ├── index.blade.php # Tree container component
│       │   │   └── item.blade.php # Individual tree item
│       │   ├── app-logo.blade.php # Full application logo
│       │   ├── app-logo-icon.blade.php # Icon-only logo
│       │   ├── action-message.blade.php # Success/error message display
│       │   ├── auth-header.blade.php # Authentication page header
│       │   ├── confirmation-modal.blade.php # Confirmation dialog
│       │   ├── input-error.blade.php # Form validation error display
│       │   ├── input-label.blade.php # Form input labels
│       │   ├── primary-button.blade.php # Primary action buttons
│       │   └── secondary-button.blade.php # Secondary action buttons
│       ├── flux/               # Flux UI component customizations
│       │   ├── icon/           # Custom SVG icon components
│       │   │   ├── arrows-right-left.blade.php # Transfer/exchange icon
│       │   │   ├── arrows-trending-up.blade.php # Growth/increase icon
│       │   │   ├── book-open-text.blade.php # Documentation/manual icon
│       │   │   ├── building-office.blade.php # Organization/division icon
│       │   │   ├── chart-bar.blade.php # Statistics/reports icon
│       │   │   ├── clipboard-document-list.blade.php # Inventory list icon
│       │   │   ├── cog-6-tooth.blade.php # Settings/configuration icon
│       │   │   ├── cube.blade.php # Item/product icon
│       │   │   ├── document-text.blade.php # Document/form icon
│       │   │   ├── home.blade.php # Dashboard/home icon
│       │   │   ├── identification.blade.php # Employee/user icon
│       │   │   ├── truck.blade.php # Delivery/logistics icon
│       │   │   ├── users.blade.php # Multiple users icon
│       │   │   └── [+30 more icons] # Additional custom icons
│       │   ├── navlist/         # Navigation list components
│       │   │   └── group.blade.php # Navigation group wrapper
│       │   └── toast.blade.php  # Notification toast component
│       ├── livewire/           # Livewire Volt single-file components
│       │   ├── admin/          # Admin panel Volt components
│       │   │   ├── data/       # Master data management components
│       │   │   │   ├── employees-and-divisions/ # Organizational structure management
│       │   │   │   │   ├── divisions/ # Division/department management
│       │   │   │   │   │   ├── create.blade.php # Create new division form
│       │   │   │   │   │   ├── edit.blade.php # Edit division details
│       │   │   │   │   │   └── index.blade.php # Division listing and search
│       │   │   │   │   ├── employees/ # Employee record management
│       │   │   │   │   │   ├── create.blade.php # Add new employee form
│       │   │   │   │   │   ├── edit.blade.php # Edit employee information
│       │   │   │   │   │   └── index.blade.php # Employee directory and search
│       │   │   │   │   ├── positions/ # Job position management
│       │   │   │   │   │   ├── create.blade.php # Create new position form
│       │   │   │   │   │   ├── edit.blade.php # Edit position details
│       │   │   │   │   │   └── index.blade.php # Position listing
│       │   │   │   │   ├── index.blade.php # Organizational overview dashboard
│       │   │   │   │   └── tree-view.blade.php # Hierarchical org chart view
│       │   │   │   ├── items-and-categories/ # Inventory catalog management
│       │   │   │   │   ├── items-catalog/ # Master item catalog
│       │   │   │   │   │   ├── create.blade.php # Add new catalog item
│       │   │   │   │   │   ├── edit.blade.php # Edit item details
│       │   │   │   │   │   └── index.blade.php # Item catalog browser
│       │   │   │   │   ├── primary-categories/ # Main item categories
│       │   │   │   │   │   ├── create.blade.php # Create primary category
│       │   │   │   │   │   ├── edit.blade.php # Edit category details
│       │   │   │   │   │   └── index.blade.php # Category management
│       │   │   │   │   ├── secondary-categories/ # Item sub-categories
│       │   │   │   │   │   ├── create.blade.php # Create sub-category
│       │   │   │   │   │   ├── edit.blade.php # Edit sub-category
│       │   │   │   │   │   └── index.blade.php # Sub-category management
│       │   │   │   │   ├── index.blade.php # Catalog overview dashboard
│       │   │   │   │   └── tree-view.blade.php # Category hierarchy view
│       │   │   │   └── suppliers-and-contracts/ # Procurement management
│       │   │   │       ├── contracts/ # Contract management
│       │   │   │       │   ├── create.blade.php # Create new contract
│       │   │   │       │   ├── edit.blade.php # Edit contract details
│       │   │   │       │   └── index.blade.php # Contract listing
│       │   │   │       ├── suppliers/ # Vendor/supplier management
│       │   │   │       │   ├── create.blade.php # Add new supplier
│       │   │   │       │   ├── edit.blade.php # Edit supplier info
│       │   │   │       │   └── index.blade.php # Supplier directory
│       │   │   │       ├── index.blade.php # Procurement overview
│       │   │   │       └── tree-view.blade.php # Supplier-contract relationships
│       │   │   ├── inventory/    # Core inventory tracking systems
│       │   │   │   ├── consumables/ # Consumable items management
│       │   │   │   │   ├── details.blade.php # Item detail view
│       │   │   │   │   ├── edit.blade.php # Edit consumable quantities
│       │   │   │   │   └── index.blade.php # Consumable inventory listing
│       │   │   │   ├── ics/         # Inventory Custodian Slip system
│       │   │   │   │   ├── create.blade.php # Create new ICS record
│       │   │   │   │   ├── edit.blade.php # Edit ICS details
│       │   │   │   │   └── index.blade.php # ICS records listing
│       │   │   │   ├── idr/         # Inventory Delivery Receipt system
│       │   │   │   │   ├── create.blade.php # Create new IDR record
│       │   │   │   │   ├── edit.blade.php # Edit IDR details
│       │   │   │   │   └── index.blade.php # IDR records listing
│       │   │   │   └── par/         # Property Acknowledgment Receipt system
│       │   │   │       ├── create.blade.php # Create new PAR record
│       │   │   │       ├── edit.blade.php # Edit PAR details
│       │   │   │       └── index.blade.php # PAR records listing
│       │   │   ├── main/        # Main admin functions
│       │   │   │   ├── dashboard.blade.php # Admin dashboard overview
│       │   │   │   └── reports/     # Report generation system
│       │   │   │       ├── formats/ # Different report formats
│       │   │   │       │   ├── ics/ # ICS report formats
│       │   │   │       │   │   ├── by-employee.blade.php # Employee-based ICS report
│       │   │   │       │   │   └── by-number.blade.php # Number-based ICS report
│       │   │   │       │   ├── idr/ # IDR report formats
│       │   │   │       │   │   ├── batch-combined.blade.php # Combined batch report
│       │   │   │       │   │   ├── batch-detailed.blade.php # Detailed batch report
│       │   │   │       │   │   └── by-employee.blade.php # Employee-based IDR report
│       │   │   │       │   ├── par/ # PAR report formats
│       │   │   │       │   │   ├── by-employee.blade.php # Employee-based PAR report
│       │   │   │       │   │   └── by-number.blade.php # Number-based PAR report
│       │   │   │       │   └── unavailable.blade.php # No data available message
│       │   │   │       └── index.blade.php # Report selection interface
│       │   │   └── system/      # System administration
│       │   │       ├── audit-logs/ # System audit trail
│       │   │       │   └── index.blade.php # Audit log viewer
│       │   │       └── users/   # User management
│       │   │           ├── create.blade.php # Create new user account
│       │   │           ├── edit.blade.php # Edit user details
│       │   │           └── index.blade.php # User management interface
│       │   ├── auth/            # Authentication Volt components
│       │   │   ├── confirm-password.blade.php # Password confirmation form
│       │   │   ├── forgot-password.blade.php # Password reset request
│       │   │   ├── login.blade.php # User login form
│       │   │   ├── register.blade.php # New user registration
│       │   │   ├── reset-password.blade.php # Password reset form
│       │   │   └── verify-email.blade.php # Email verification page
│       │   ├── inventory-manager/ # Division inventory manager components
│       │   │   ├── consumables/ # Division consumable management
│       │   │   │   ├── create.blade.php # Add consumable stock
│       │   │   │   ├── edit.blade.php # Update stock levels
│       │   │   │   └── index.blade.php # Division consumable inventory
│       │   │   ├── items/       # Division item management
│       │   │   │   └── index.blade.php # Division item overview
│       │   │   ├── reports/     # Division-specific reports
│       │   │   │   └── index.blade.php # Division report dashboard
│       │   │   ├── transfers/   # Item transfer management
│       │   │   │   ├── create.blade.php # Create transfer request
│       │   │   │   └── index.blade.php # Transfer history
│       │   │   └── dashboard.blade.php # Inventory manager dashboard
│       │   └── settings/        # User settings components
│       │       ├── appearance.blade.php # Theme and display preferences
│       │       ├── delete-user-form.blade.php # Account deletion form
│       │       ├── password.blade.php # Change password form
│       │       └── profile.blade.php # Edit profile information
│       ├── partials/           # Partial view components
│       │   ├── navigation/     # Navigation menu partials
│       │   │   ├── admin.blade.php # Admin navigation menu
│       │   │   └── inventory-manager.blade.php # Manager navigation menu
│       │   ├── head.blade.php  # HTML head section with meta tags
│       │   └── settings-heading.blade.php # Settings page header
│       ├── vendor/             # Third-party package views
│       │   └── livewire/       # Livewire pagination views
│       │       ├── simple-tailwind.blade.php # Simple pagination styling
│       │       └── tailwind.blade.php # Full Tailwind pagination
│       └── welcome.blade.php   # Application welcome/landing page
├── routes/                     # HTTP route definitions
│   ├── web.php                 # Main web routes (public pages)
│   ├── auth.php                # Authentication routes (login, register, etc.)
│   ├── admin.php               # Admin panel routes (protected by admin middleware)
│   └── console.php             # Artisan command definitions
├── storage/                    # File storage and application data
│   ├── app/                    # Application file storage
│   │   ├── public/             # Publicly accessible files (uploads, etc.)
│   │   └── private/            # Private application files (documents, etc.)
│   ├── framework/              # Laravel framework storage
│   │   ├── cache/              # Application cache files
│   │   ├── sessions/           # Session data files
│   │   ├── testing/            # Testing environment cache
│   │   └── views/              # Compiled Blade template cache
│   └── logs/                   # Application log files
│       └── laravel.log         # Main application log
└── tests/                      # Automated test suites
    ├── Feature/                # Feature tests (full application features)
    │   ├── Admin/              # Admin functionality tests
    │   │   ├── Data/           # Master data management tests
    │   │   │   ├── ContractManagementTest.php # Contract CRUD operations
    │   │   │   ├── DivisionManagementTest.php # Division management tests
    │   │   │   ├── EmployeeManagementTest.php # Employee management tests
    │   │   │   ├── ItemManagementTest.php # Item catalog tests
    │   │   │   ├── PositionManagementTest.php # Position management tests
    │   │   │   ├── PrimaryCategoryTest.php # Primary category tests
    │   │   │   ├── SecondaryCategoryTest.php # Secondary category tests
    │   │   │   └── SupplierManagementTest.php # Supplier management tests
    │   │   └── Inventory/      # Inventory system tests
    │   │       ├── ConsumablesDivisionViewTest.php # Division consumables view
    │   │       ├── ConsumablesPageTest.php # Consumables management
    │   │       ├── IcsCreationTest.php # ICS creation functionality
    │   │       ├── IdrCreationTest.php # IDR creation functionality
    │   │       └── ParCreationTest.php # PAR creation functionality
    │   ├── Auth/               # Authentication system tests
    │   │   ├── AuthenticationTest.php # Login/logout functionality
    │   │   ├── EmailVerificationTest.php # Email verification process
    │   │   ├── InventoryManagerAuthTest.php # Manager authentication
    │   │   ├── PasswordConfirmationTest.php # Password confirmation
    │   │   ├── PasswordResetTest.php # Password reset functionality
    │   │   └── RegistrationTest.php # User registration process
    │   ├── Settings/           # User settings tests
    │   │   ├── PasswordUpdateTest.php # Password change functionality
    │   │   └── ProfileUpdateTest.php # Profile update functionality
    │   ├── AdminMiddlewareTest.php # Admin access control tests
    │   ├── AdminUserManagementTest.php # Admin user management
    │   └── DashboardTest.php   # Dashboard functionality tests
    ├── Unit/                   # Unit tests (individual classes/methods)
    │   ├── Admin/              # Admin-specific unit tests
    │   │   └── Inventory/      # Inventory logic unit tests
    │   ├── ExampleTest.php     # Example unit test
    │   └── PermissionServiceTest.php # Permission service logic tests
    ├── TestCase.php            # Base test class with common functionality
    └── Pest.php                # Pest testing framework configuration
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

## database-schema

The system's database architecture is built around three core inventory management systems with comprehensive relationship mapping and audit capabilities. The schema supports:

- **Multi-System Integration**: ICS, PAR, and IDR systems with shared procurement and cataloging infrastructure
- **Organizational Hierarchy**: Complete mapping of divisions, positions, and employee relationships
- **Procurement Traceability**: Full chain from suppliers through contracts to individual item assignments
- **Component-Level Tracking**: Detailed tracking of complex equipment components and serial numbers
- **Transfer Management**: Complete audit trail of property transfers between employees
- **Soft Delete Protection**: Non-destructive deletion preserving historical data integrity

For a detailed view of all database entities, relationships, and field explanations, see the [Entity-Relationship Diagram](erd.md) which provides comprehensive documentation of the database structure including:

- **25+ interconnected entities** covering all aspects of inventory management
- **Detailed field explanations** for every database column
- **Relationship mapping** showing how data flows through the system
- **Business rule documentation** explaining the purpose of each entity
- **Data integrity constraints** ensuring system reliability

The ERD serves as the definitive reference for understanding how D'Agriventory stores and relates inventory data across all organizational levels and inventory systems.
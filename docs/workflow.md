# Development Workflow

This document outlines the recommended development workflow for the D'Agriventory project.

## Core Development Cycle

1.  **Get the latest code:**
    ```bash
    git pull origin main
    ```
2.  **Create a feature branch:**
    ```bash
    git checkout -b feature/your-feature-name
    ```
3.  **Write your code and tests.**
4.  **Run tests and format your code** using the commands below.
5.  **Commit and push** your changes.
6.  **Create a Pull Request** on GitHub. CI checks for linting and testing will run automatically.

---

## Essential Commands

### Running the Application

For the best development experience with hot-reloading, run:
```bash
composer run dev
```

### Testing

The project uses a dedicated MySQL database for all automated tests to ensure consistency with the production environment. This is configured in the `phpunit.xml` file.

**Important:** Before running tests, you must create an empty MySQL database named `d_agriventory_testing`. The test runner will use the same credentials from your `.env` file to connect to it.

#### Backend Tests (Pest)

```bash
# Run all backend tests
composer test

# Run a specific file
./vendor/bin/pest tests/Feature/YourTestFile.php
```

#### Browser Tests (Dusk)

```bash
# Run all browser tests
php artisan dusk

# Run a specific file
php artisan dusk tests/Browser/YourBrowserTest.php
```

### Code Formatting

Use Laravel Pint to format your code before committing.
```bash
# Format all files
./vendor/bin/pint

# Format only changed files (recommended)
./vendor/bin/pint --dirty
```

---

## Creating New Files

Use Artisan `make` commands to generate new classes:

- **Feature Test:** `php artisan make:test YourFeatureTest`
- **Unit Test:** `php artisan make:test YourUnitTest --unit`
- **Dusk Test:** `php artisan dusk:make YourBrowserTest` 
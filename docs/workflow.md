# D'Agriventory Development Workflow

This document outlines the recommended development workflow for the D'Agriventory project, including testing, code quality checks, and GitHub integration.

## Local Development Workflow

### 1. Development Cycle

For efficient development, follow this workflow:

1. **Pull latest changes** from the repository
   ```bash
   git pull origin main
   ```

2. **Install/update dependencies** if necessary
   ```bash
   composer install
   npm install
   ```

3. **Create a feature branch**
   ```bash
   git checkout -b feature/your-feature-name
   ```

4. **Write your code** following the project's standards and patterns

5. **Test and lint your code locally** (detailed below)

6. **Commit and push your changes**
   ```bash
   git add .
   git commit -m "Description of changes"
   git push origin feature/your-feature-name
   ```

7. **Create a pull request** to merge your feature branch back into the main branch

### 2. Testing Your Code

The project uses Pest for testing, which is built on top of PHPUnit. 

#### Running Tests

You have several options to run tests:

1. **Using Pest directly** (preferred during development for fastest feedback)
   ```bash
   ./vendor/bin/pest
   ```

2. **Using the Laravel Artisan command** (more verbose output)
   ```bash
   php artisan test
   ```

3. **Using the Composer script** (clears config cache first)
   ```bash
   composer test
   ```

#### Testing Specific Parts

- Run a specific test file:
  ```bash
  ./vendor/bin/pest tests/Feature/YourTestFile.php
  ```

- Run a specific test group:
  ```bash
  php artisan test --group=auth
  ```

- Stop on first failure:
  ```bash
  ./vendor/bin/pest --stop-on-failure
  ```

#### Creating New Tests

Use the Laravel Artisan command to generate test files:

- Feature test (default):
  ```bash
  php artisan make:test YourFeatureTest
  ```

- Unit test:
  ```bash
  php artisan make:test YourUnitTest --unit
  ```

### 3. Code Style and Quality

The project uses Laravel Pint for code styling, which is an opinionated PHP code style fixer built on top of PHP CS Fixer.

#### Formatting Your Code

- Format all code:
  ```bash
  ./vendor/bin/pint
  ```

- Format specific files or directories:
  ```bash
  ./vendor/bin/pint app/Models
  ./vendor/bin/pint app/Models/User.php
  ```

- Check for style issues without fixing them:
  ```bash
  ./vendor/bin/pint --test
  ```

- Format only modified files:
  ```bash
  ./vendor/bin/pint --dirty
  ```

## GitHub Integration

The project has two GitHub Actions workflows configured:

### 1. Linting Workflow

Located in `.github/workflows/lint.yml`, this workflow runs Laravel Pint to ensure code style consistency. It runs automatically on pushes to the `main` and `develop` branches as well as pull requests targeting these branches.

### 2. Testing Workflow

Located in `.github/workflows/tests.yml`, this workflow runs the application's test suite. Like the linting workflow, it runs automatically on pushes to the `main` and `develop` branches as well as pull requests targeting these branches.

## GitHub Workflow Permissions

If you're having trouble with GitHub Actions, ensure you have configured proper permissions:

1. Go to your repository's **Settings** tab
2. Click on **Actions** in the left sidebar
3. Scroll down to **Workflow permissions**
4. Enable **Read and write permissions**
5. Save your changes

## Best Practices

1. **Always run tests locally** before pushing to GitHub to catch issues early
2. **Format your code** using Pint before committing
3. **Write tests** for new features and bug fixes
4. **Follow the TALL stack principles** outlined in the project documentation
5. **Review CI results** on GitHub for any issues with your code

## Recommended Workflow Summary

For the most efficient workflow:

1. **During development**: Use `./vendor/bin/pest` for frequent test runs
2. **Before committing**: Run `./vendor/bin/pint` to format your code
3. **Before pushing**: Run `composer test` as a final verification step 
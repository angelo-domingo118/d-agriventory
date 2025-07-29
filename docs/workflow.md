# Development Workflow

Daily development commands, git procedures, and quality assurance processes for D'Agriventory contributors.

## daily-commands

### development-environment

```bash
# Start full development stack (recommended)
composer run dev

# Individual services (if needed separately)
php artisan serve          # Laravel server only
php artisan queue:listen   # Background job processing
npm run dev               # Vite with hot reload
```

### database-operations

```bash
# Fresh database setup
php artisan migrate:fresh --seed

# Create new migration
php artisan make:migration create_table_name

# Rollback last migration
php artisan migrate:rollback

# Check migration status
php artisan migrate:status
```

### cache-management

```bash
# Clear all caches (common during development)
php artisan optimize:clear

# Individual cache clearing
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear
```

### testing-workflow

```bash
# Run all tests with output
composer test

# Parallel testing (faster execution)
php artisan test --parallel

# Run specific test files
php artisan test --filter=UserTest

# Code formatting
./vendor/bin/pint
```

## git-flow-process

```
┌─────────────────┐
│   main branch   │ ← Production-ready releases
└─────────────────┘
         ↑
┌─────────────────┐
│ develop branch  │ ← Integration branch for features
└─────────────────┘
         ↑
┌─────────────────┐
│ feature/branch  │ ← Your working branch
└─────────────────┘
```

### branch-naming

```bash
# Feature development
git checkout -b feature/inventory-management
git checkout -b feature/user-roles

# Bug fixes
git checkout -b fix/login-validation
git checkout -b fix/report-generation

# Documentation
git checkout -b docs/api-documentation
git checkout -b docs/deployment-guide
```

### commit-standards

```bash
# Clear, descriptive commit messages
git commit -m "feat: add inventory item validation"
git commit -m "fix: resolve division assignment bug"
git commit -m "docs: update installation guide"
git commit -m "test: add user authentication tests"
```

## pull-request-checklist

### before-submitting

- [ ] **Tests pass**: `composer test` executes without failures
- [ ] **Code formatted**: `./vendor/bin/pint` applied without changes
- [ ] **Database migrations**: New migrations tested and reversible
- [ ] **No debugging code**: Remove `dd()`, `dump()`, and `console.log()`
- [ ] **Environment variables**: Document any new `.env` requirements

### pr-description-template

```markdown
## Changes Made
Brief description of what this PR accomplishes.

## Testing
- [ ] Unit tests added/updated
- [ ] Feature tests verified
- [ ] Manual testing completed

## Database Changes
- [ ] Migrations included
- [ ] Seeders updated (if applicable)
- [ ] No breaking schema changes

## Documentation
- [ ] Code comments added where needed
- [ ] README updated (if applicable)
```

### review-process

1. **Automated checks**: Ensure CI passes all tests and formatting
2. **Peer review**: At least one team member approval required
3. **Manual testing**: Verify functionality in development environment
4. **Merge strategy**: Use "Squash and merge" for clean commit history

## deployment-checklist

### production-preparation

```bash
# Optimise for production
npm run build
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Verify production environment
php artisan config:show
php artisan about
```

### post-deployment

```bash
# Run migrations
php artisan migrate --force

# Clear caches
php artisan optimize:clear

# Verify application health
php artisan health:check
```

## troubleshooting-commands

```bash
# Permission issues
chmod -R 775 storage bootstrap/cache

# Composer issues
composer dump-autoload

# npm issues
rm -rf node_modules package-lock.json
npm install

# Database connection test
php artisan tinker
>>> DB::connection()->getPdo();
``` 
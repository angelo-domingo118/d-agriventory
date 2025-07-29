# Frequently Asked Questions

Common questions and solutions for D'Agriventory development and deployment.

## development-environment

### q1-why-blank-page-after-setup

**Q: Why am I seeing a blank page after installation?**

**A:** Usually caused by missing application key or incorrect permissions.

```bash
# Generate application key
php artisan key:generate

# Fix storage permissions
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache

# Clear all caches
php artisan optimize:clear
```

### q2-npm-vite-not-working

**Q: Vite development server won't start or assets aren't loading?**

**A:** Common Node.js and dependency issues:

```bash
# Clear npm cache and reinstall
rm -rf node_modules package-lock.json
npm cache clean --force
npm install

# Check Node.js version (requires 18+)
node --version

# Restart Vite development server
npm run dev
```

### q3-database-connection-failed

**Q: Getting "could not find driver" or database connection errors?**

**A:** PHP MySQL extensions or configuration issues:

```bash
# Install PHP MySQL extension (Ubuntu/Debian)
sudo apt-get install php8.2-mysql

# Verify database credentials in .env
php artisan tinker
>>> DB::connection()->getPdo();

# Test database connection
mysql -h127.0.0.1 -u[username] -p[password] [database_name]
```

## testing-issues

### q4-tests-failing-database

**Q: Tests failing with database errors?**

**A:** Ensure test database exists and configuration is correct:

```bash
# Create test database
mysql -u root -p
> CREATE DATABASE `d-agriventory-testing`;

# Verify test environment
php artisan config:show database --env=testing

# Run migrations in test environment
php artisan migrate --env=testing
```



## cache-and-performance

### q6-changes-not-reflecting

**Q: Code changes aren't appearing in the browser?**

**A:** Cache clearing is the most common solution:

```bash
# Clear all Laravel caches
php artisan optimize:clear

# Individual cache clearing
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Clear browser cache (Ctrl+F5 or Cmd+Shift+R)
# For persistent issues, restart the development server
```

### q7-slow-livewire-components

**Q: Livewire components are slow to respond?**

**A:** Performance optimisation strategies:

```bash
# Enable query caching in development
php artisan config:cache

# Use Livewire lazy loading for heavy components
wire:init="loadData"

# Implement pagination for large datasets
use WithPagination;
```

## deployment-and-production

### q8-sail-vs-local-development

**Q: Should I use Laravel Sail or local development environment?**

**A:** Depends on your system and team preferences:

**Laravel Sail (Docker) - Choose when:**
- Consistent environment across team members
- Working on multiple PHP projects with different versions
- Windows development (avoids configuration complexity)

```bash
# Start Sail environment
sail up -d
sail artisan migrate
sail npm run dev
```

**Local Development - Choose when:**
- Already have PHP/MySQL/Node.js installed locally
- Prefer faster performance (no Docker overhead)
- Simpler debugging and IDE integration

```bash
# Local development
composer run dev
```

### q9-production-deployment-checklist

**Q: What should I check before deploying to production?**

**A:** Essential production preparation steps:

```bash
# Build assets
npm run build

# Cache configuration
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Run migrations
php artisan migrate --force

# Verify environment
php artisan about
php artisan config:show app.env  # Should show "production"
```

### q10-user-permissions-not-working

**Q: User roles and permissions aren't working correctly?**

**A:** Common authorization issues:

```bash
# Clear policy cache
php artisan optimize:clear

# Verify user role assignment
php artisan tinker
>>> User::find(1)->role;

# Check middleware configuration in routes
# Ensure IsAdmin or HasAdminPermission middleware is applied

# Re-seed users if needed
php artisan db:seed --class=AdminUserSeeder
```

## getting-additional-help

- **GitHub Issues**: Report bugs and feature requests
- **Documentation**: Check other files in `docs/` directory  
- **Laravel Docs**: [Official Laravel documentation](https://laravel.com/docs)
- **Livewire Docs**: [Livewire framework guide](https://livewire.laravel.com/docs)
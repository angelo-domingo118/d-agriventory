# Production Deployment Guide

This guide walks you through preparing and deploying the D'Agriventory Laravel application to a production server. Whether you're new to Laravel or deployment in general, this document provides step-by-step instructions with explanations of what each step does and why it's important.

## Table of Contents

1. [Understanding the Deployment Process](#understanding-the-deployment-process)
2. [Pre-Deployment Checklist](#pre-deployment-checklist)
3. [Local Production Testing](#local-production-testing)
4. [Server Requirements](#server-requirements)
5. [Deployment Steps](#deployment-steps)
6. [Post-Deployment Verification](#post-deployment-verification)
7. [Maintenance and Updates](#maintenance-and-updates)
8. [Troubleshooting](#troubleshooting)

## Understanding the Deployment Process

### What is Deployment?
Deployment is the process of moving your application from your local development environment (like Laragon) to a live server where real users can access it. This involves several key differences:

- **Environment**: Moving from your local machine to a remote server
- **Configuration**: Different database credentials, URLs, and security settings
- **Performance**: Optimized code and cached configurations for speed
- **Security**: Debug mode disabled, error logging instead of display

### Laravel's Role in Deployment
Laravel provides built-in tools to make deployment easier:
- **Artisan commands** for caching and optimization
- **Environment files** (.env) for configuration management
- **Asset compilation** through Vite for frontend resources
- **Database migrations** for consistent schema deployment

## Pre-Deployment Checklist

Before deploying, ensure you have:

- [ ] All code committed to version control (Git)
- [ ] Production server access credentials
- [ ] Production database created and accessible
- [ ] Domain name configured (if applicable)
- [ ] SSL certificate ready (for HTTPS)
- [ ] Backup of current production data (if updating)

## Local Production Testing

Before deploying to your live server, test your application locally in "production mode" to catch issues early.

### Step 1: Stop Development Server
If you're running `npm run dev`, stop it with `Ctrl + C`. In production, there's no Vite development server.

### Step 2: Build Frontend Assets
```bash
npm run build
```

**What this does**: Compiles and optimizes your CSS and JavaScript files into static files in the `public/build` directory. These files are minified (smaller) and versioned (for cache busting).

### Step 3: Optimize Laravel Application
```bash
php artisan optimize
```

**What this does**: Runs multiple optimization commands:
- `config:cache` - Caches all configuration files into a single file
- `route:cache` - Caches route definitions for faster lookup
- `view:cache` - Pre-compiles Blade templates

**Why it matters**: These optimizations can improve response times by 2-3x in production.

### Step 4: Test in Production Mode
Temporarily modify your `.env` file:
```env
APP_ENV=production
APP_DEBUG=false
```

**Important**: Change these back after testing!

Test your application at `http://d-agriventory.test/` to ensure everything works.

### Step 5: Return to Development Mode
```bash
php artisan optimize:clear
```
Then change your `.env` back to:
```env
APP_ENV=local
APP_DEBUG=true
```

## Server Requirements

Your production server needs:

### PHP Requirements
- **PHP 8.2 or higher**
- **Extensions**: BCMath, Ctype, Fileinfo, JSON, Mbstring, OpenSSL, PDO, Tokenizer, XML

### Web Server
- **Apache 2.4+** or **Nginx 1.18+**
- **Document root** must point to the `public` directory (security requirement)

### Database
- **MySQL 8.0+** (Required - PostgreSQL and other databases are not supported)
- This application uses MySQL-specific features for data retrieval
- Separate database for production (never use development database)

### System Tools
- **Composer** (PHP dependency manager)
- **Node.js & npm** (for asset compilation)
- **Git** (for code deployment)

## Deployment Steps

### Step 1: Prepare Your Code Repository

Ensure your code is ready for deployment:

```bash
# Commit all changes
git add .
git commit -m "Prepare for production deployment"
git push origin main
```

### Step 2: Server Setup

On your production server, clone your repository:

```bash
# Navigate to web directory
cd /var/www

# Clone your repository
git clone https://github.com/angelo-domingo118/d-agriventory.git
cd d-agriventory
```

### Step 3: Install PHP Dependencies

```bash
composer install --optimize-autoloader --no-dev
```

**Flags explained**:
- `--optimize-autoloader`: Creates an optimized class map for faster class loading
- `--no-dev`: Skips development dependencies (testing tools, debuggers) to save space and improve security

### Step 4: Configure Environment

```bash
# Copy environment template
cp .env.example .env

# Edit with production settings
nano .env
```

**Critical production settings**:
```env
APP_NAME="D'Agriventory"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com

# Database settings
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=d-agriventory
DB_USERNAME=your_db_user
DB_PASSWORD=your_secure_password

# Mail settings (for notifications)
MAIL_MAILER=smtp
MAIL_HOST=your-smtp-server
MAIL_PORT=587
MAIL_USERNAME=your-email
MAIL_PASSWORD=your-email-password
MAIL_ENCRYPTION=tls

# Cache settings (for better performance)
CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis
```

### Step 5: Generate Application Key

```bash
php artisan key:generate
```

**What this does**: Creates a unique encryption key for your application. This key is used to encrypt sessions, cookies, and other sensitive data.

### Step 6: Set File Permissions

```bash
# Make storage and cache directories writable
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
```

**Why this matters**: Laravel needs to write log files, cache files, and session data. Without proper permissions, your application will crash.

### Step 7: Database Setup

```bash
# Run database migrations
php artisan migrate --force

# Seed initial data (if needed)
php artisan db:seed --class=AdminUserSeeder
php artisan db:seed --class=DivisionSeeder
```

**The `--force` flag**: Required in production environment as a safety measure.

### Step 8: Install and Build Frontend Assets

```bash
# Install Node.js dependencies
npm install

# Build production assets
npm run build
```

**What happens**: Creates optimized CSS and JavaScript files in `public/build/` directory.

### Step 9: Optimize Application

```bash
# Cache everything for maximum performance
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize
```

### Step 10: Create Storage Link

```bash
php artisan storage:link
```

**What this does**: Creates a symbolic link from `public/storage` to `storage/app/public`, allowing public access to uploaded files.

### Step 11: Configure Web Server

#### For Apache (.htaccess)
Laravel includes an `.htaccess` file in the `public` directory. Ensure your Apache virtual host points to the `public` directory:

```apache
<VirtualHost *:80>
    ServerName your-domain.com
    DocumentRoot /var/www/d-agriventory/public
    
    <Directory /var/www/d-agriventory/public>
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

#### For Nginx
```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /var/www/d-agriventory/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

**Security note**: The document root MUST point to the `public` directory, never the project root.

### Step 12: Set Up SSL (HTTPS)

```bash
# Using Certbot for free SSL certificates
sudo apt install certbot python3-certbot-apache
sudo certbot --apache -d your-domain.com
```

## Post-Deployment Verification

### Test Critical Functions

1. **Homepage**: Visit your domain and verify the homepage loads
2. **Login**: Test admin and manager login functionality
3. **Database**: Check that data displays correctly
4. **File Uploads**: Test any file upload features
5. **Email**: Verify email notifications work

### Monitor Logs

```bash
# Check Laravel logs
tail -f storage/logs/laravel.log

# Check web server logs
sudo tail -f /var/log/apache2/error.log
# or for Nginx
sudo tail -f /var/log/nginx/error.log
```

### Performance Check

Use tools like:
- **Google PageSpeed Insights**
- **GTmetrix**
- **Laravel Telescope** (if installed)

## Maintenance and Updates

### Regular Tasks

#### Weekly
```bash
# Clear old log files
php artisan log:clear

# Clear expired cache
php artisan cache:clear
```

#### For Updates
```bash
# Pull latest code
git pull origin main

# Update dependencies
composer install --optimize-autoloader --no-dev

# Rebuild assets
npm run build

# Update database
php artisan migrate --force

# Recache everything
php artisan optimize
```

### Backup Strategy

**Database Backup**:
```bash
# Create daily database backup
mysqldump -u username -p database_name > backup_$(date +%Y%m%d).sql
```

**File Backup**:
```bash
# Backup uploaded files
tar -czf storage_backup_$(date +%Y%m%d).tar.gz storage/app/public
```

## Troubleshooting

### Common Issues

#### "500 Internal Server Error"
1. Check file permissions on `storage` and `bootstrap/cache`
2. Verify `.env` file exists and has correct database credentials
3. Check web server error logs
4. Ensure document root points to `public` directory

#### "Key not found" Error
```bash
php artisan key:generate
```

#### Assets Not Loading
1. Verify `npm run build` completed successfully
2. Check that `public/build` directory exists
3. Clear browser cache

#### Database Connection Failed
1. Verify database credentials in `.env`
2. Test database connection manually
3. Check firewall settings

### Log Locations

- **Laravel Logs**: `storage/logs/laravel.log`
- **Apache Logs**: `/var/log/apache2/error.log`
- **Nginx Logs**: `/var/log/nginx/error.log`
- **PHP Logs**: `/var/log/php8.2-fpm.log`

### Getting Help

1. **Laravel Documentation**: [Official Laravel 12.x Docs](https://laravel.com/docs/12.x)
2. **Deployment Guide**: [Laravel Deployment Documentation](https://laravel.com/docs/12.x/deployment)
3. **Server Configuration**: [Laravel Server Requirements](https://laravel.com/docs/12.x/installation#server-requirements)
4. **Vite Asset Bundling**: [Laravel Vite Documentation](https://laravel.com/docs/12.x/vite)
5. **Database**: [Laravel Database Documentation](https://laravel.com/docs/12.x/database)
6. **Review application logs** for specific error messages
7. **Use Laravel's debug mode** temporarily (set `APP_DEBUG=true`) to see detailed errors
8. **Consult the project's GitHub issues** or create a new one

## Security Considerations

### Essential Security Measures

1. **Never commit `.env` files** to version control
2. **Use HTTPS** for all production traffic
3. **Keep software updated**: PHP, Laravel, and server software
4. **Use strong passwords** for database and admin accounts
5. **Implement proper backup procedures**
6. **Monitor logs** for suspicious activity
7. **Use fail2ban** or similar tools to prevent brute force attacks

### Regular Security Tasks

- Update Laravel and dependencies monthly
- Review user access permissions quarterly
- Rotate database passwords annually
- Monitor security advisories for used packages

This deployment guide ensures your D'Agriventory application runs securely and efficiently in production. Remember that deployment is an iterative process – you'll refine your approach as you gain experience with your specific server environment and requirements. 
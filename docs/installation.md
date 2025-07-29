# Prerequisites Installation Guide

This guide provides step-by-step instructions for installing all required software to run D'Agriventory locally. Follow the instructions for your operating system.

> **Reference**: Based on [Laravel 12.x Installation Documentation](https://laravel.com/docs/12.x/installation)

## System Requirements

Before starting, ensure your system meets these requirements:

- **PHP 8.2 or higher** with required extensions
- **Composer** (PHP dependency manager) 
- **Node.js 18.0 or higher** with npm
- **MySQL 8.0 or higher** (Recommended - see database compatibility notes)
- **Git** (for version control)

> **⚠️ Database Compatibility**: This application is optimized for MySQL 8.0+ and uses some MySQL-specific features. While partial SQLite support is available for development, some functionality may not work correctly with SQLite. **MySQL is strongly recommended for production use.**

## Quick Install (Recommended)

### Using Laravel's Official Installer

Laravel provides automated installation scripts for different operating systems:

#### macOS
```bash
/bin/bash -c "$(curl -fsSL https://php.new/install/mac/8.4)"
```

#### Windows (PowerShell as Administrator)
```powershell
Set-ExecutionPolicy Bypass -Scope Process -Force; [System.Net.ServicePointManager]::SecurityProtocol = [System.Net.ServicePointManager]::SecurityProtocol -bor 3072; iex ((New-Object System.Net.WebClient).DownloadString('https://php.new/install/windows/8.4'))
```

#### Linux (Ubuntu/Debian)
```bash
/bin/bash -c "$(curl -fsSL https://php.new/install/linux/8.4)"
```

These scripts automatically install PHP, Composer, and the Laravel installer. You'll still need to install Node.js and MySQL separately (see manual installation below).

## Manual Installation

### 1. PHP 8.2+ Installation

#### Windows
1. **Download PHP**: Visit [php.net/downloads](https://www.php.net/downloads.php)
2. **Extract**: Unzip to `C:\php`
3. **Configure**: 
   - Copy `php.ini-development` to `php.ini`
   - Enable required extensions by uncommenting these lines:
     ```ini
     extension=bcmath
     extension=ctype
     extension=fileinfo
     extension=json
     extension=mbstring
     extension=openssl
     extension=pdo
     extension=pdo_mysql
     extension=tokenizer
     extension=xml
     ```
4. **Add to PATH**: Add `C:\php` to your system PATH

#### macOS
```bash
# Using Homebrew (recommended)
brew install php@8.2

# Add to PATH
echo 'export PATH="/usr/local/opt/php@8.2/bin:$PATH"' >> ~/.zshrc
source ~/.zshrc
```

#### Linux (Ubuntu/Debian)
```bash
# Add repository
sudo add-apt-repository ppa:ondrej/php
sudo apt update

# Install PHP and extensions
sudo apt install php8.2 php8.2-cli php8.2-common php8.2-mysql \
  php8.2-zip php8.2-gd php8.2-mbstring php8.2-curl \
  php8.2-xml php8.2-bcmath php8.2-tokenizer
```

**Verify Installation**:
```bash
php --version
# Should show PHP 8.2.x or higher
```

### 2. Composer Installation

Composer is the PHP dependency manager used by Laravel.

#### All Platforms
1. **Download**: Visit [getcomposer.org](https://getcomposer.org/download/)
2. **Install**: Follow the platform-specific instructions
3. **Global Installation** (recommended):

**Windows**:
```bash
# Download and run the installer
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
php composer-setup.php --install-dir=bin --filename=composer
```

**macOS/Linux**:
```bash
# Download installer
curl -sS https://getcomposer.org/installer | php

# Move to global location
sudo mv composer.phar /usr/local/bin/composer
sudo chmod +x /usr/local/bin/composer
```

**Verify Installation**:
```bash
composer --version
# Should show Composer version 2.x
```

### 3. Node.js 18+ Installation

Node.js is required for building frontend assets with Vite.

#### Windows
1. **Download**: Visit [nodejs.org](https://nodejs.org/en/download/)
2. **Install**: Run the MSI installer (choose LTS version 18.x or higher)
3. **Verify**: Open Command Prompt and run `node --version`

#### macOS
```bash
# Using Homebrew
brew install node@18

# Or download from nodejs.org
```

#### Linux (Ubuntu/Debian)
```bash
# Using NodeSource repository (recommended)
curl -fsSL https://deb.nodesource.com/setup_18.x | sudo -E bash -
sudo apt-get install -y nodejs

# Alternative: Using snap
sudo snap install node --classic
```

**Verify Installation**:
```bash
node --version
# Should show v18.x.x or higher

npm --version
# Should show npm version
```

### 4. MySQL 8.0+ Installation

#### Windows
1. **Download**: Visit [MySQL Community Downloads](https://dev.mysql.com/downloads/mysql/)
2. **Install**: Run the MySQL Installer
3. **Configure**: 
   - Choose "Development Computer" setup
   - Set root password (remember this!)
   - Create a new user for the application (recommended)

#### macOS
```bash
# Using Homebrew
brew install mysql

# Start MySQL service
brew services start mysql

# Secure installation
mysql_secure_installation
```

#### Linux (Ubuntu/Debian)
```bash
# Install MySQL server
sudo apt update
sudo apt install mysql-server

# Secure installation
sudo mysql_secure_installation

# Start MySQL service
sudo systemctl start mysql
sudo systemctl enable mysql
```

**Verify Installation**:
```bash
mysql --version
# Should show MySQL 8.0.x or higher
```

**Create Database and User**:
```sql
# Connect to MySQL as root
mysql -u root -p

# Create database
CREATE DATABASE `d-agriventory` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

# Create user (replace 'your_password' with a secure password)
CREATE USER 'dagri_user'@'localhost' IDENTIFIED BY 'your_password';

# Grant privileges
GRANT ALL PRIVILEGES ON `d-agriventory`.* TO 'dagri_user'@'localhost';
FLUSH PRIVILEGES;

# Exit MySQL
EXIT;
```

### 5. Git Installation

#### Windows
1. **Download**: Visit [git-scm.com](https://git-scm.com/downloads)
2. **Install**: Run the installer with default options

#### macOS
```bash
# Git is included with Xcode Command Line Tools
xcode-select --install

# Or using Homebrew
brew install git
```

#### Linux (Ubuntu/Debian)
```bash
sudo apt update
sudo apt install git
```

**Verify Installation**:
```bash
git --version
# Should show git version
```

## Laravel Herd (Alternative)

For a streamlined development experience, consider [Laravel Herd](https://herd.laravel.com/):

- **macOS/Windows**: All-in-one development environment
- **Includes**: PHP, Nginx, MySQL, Redis, Node.js
- **Features**: Automatic SSL, easy domain management
- **Perfect for**: Laravel development

Visit [herd.laravel.com](https://herd.laravel.com/) for installation instructions.

## Platform-Specific Guides

### Windows with Laragon
For Windows users, we recommend using Laragon as a complete development environment. See our [Laragon Setup Guide](laragon.md) for detailed instructions.

### Docker Alternative
If you prefer containerized development:

```bash
# Using Laravel Sail
curl -s https://laravel.build/d-agriventory | bash
cd d-agriventory
./vendor/bin/sail up
```

## Verification Checklist

After installation, verify all components:

```bash
# Check PHP version and extensions
php --version
php -m | grep -E "(bcmath|ctype|fileinfo|json|mbstring|openssl|pdo|tokenizer|xml)"

# Check Composer
composer --version

# Check Node.js and npm
node --version
npm --version

# Check MySQL
mysql --version

# Check Git
git --version
```

## Next Steps

Once all prerequisites are installed:

1. **Clone the repository**:
   ```bash
   git clone https://github.com/angelo-domingo118/d-agriventory.git
   cd d-agriventory
   ```

2. **Follow the Quick Setup** in the [README.md](../README.md#quick-setup)

3. **Reference the Laravel Documentation**: [Laravel 12.x Installation](https://laravel.com/docs/12.x/installation)

## Troubleshooting

### Common Issues

**PHP Extensions Missing**:
```bash
# Check installed extensions
php -m

# Install missing extensions (Linux)
sudo apt install php8.2-[extension-name]
```

**Composer Memory Issues**:
```bash
# Increase memory limit
php -d memory_limit=-1 /usr/local/bin/composer install
```

**MySQL Connection Issues**:
- Verify MySQL service is running
- Check database credentials in `.env`
- Test connection: `mysql -u username -p database_name`

**Node.js Version Issues**:
```bash
# Using nvm to manage Node.js versions
nvm install 18
nvm use 18
```

## Additional Resources

- **Laravel Installation**: [Official Laravel 12.x Installation Guide](https://laravel.com/docs/12.x/installation)
- **Server Requirements**: [Laravel Server Requirements](https://laravel.com/docs/12.x/installation#server-requirements)
- **Deployment**: [Laravel Deployment Documentation](https://laravel.com/docs/12.x/deployment)
- **Database Configuration**: [Laravel Database Configuration](https://laravel.com/docs/12.x/database#configuration)
- **Frontend Assets**: [Laravel Vite Documentation](https://laravel.com/docs/12.x/vite)

For additional help, consult our [FAQ](faq.md) or create an issue in the project repository. 
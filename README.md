# D'Agriventory

<p align="center">
  <img src="public/favicon.svg" alt="D'Agriventory Logo" width="100">
</p>

## About D'Agriventory

D'Agriventory is a comprehensive inventory management system designed specifically for agricultural purposes. The application streamlines the tracking of agricultural inventory, procurement, and asset management processes.

## Features

- **User & Role Management** - Manage users with various roles (admin, division inventory managers, employees)
- **Organization Management** - Track divisions, suppliers, and positions
- **Procurement System** - Manage contracts and items procurement
- **Inventory Cataloging** - Organize items by primary and secondary categories
- **Asset Tracking** - Track assets through ICS, PAR, and IDR systems
- **Audit Logging** - Keep detailed logs of all system changes

## Built With

D'Agriventory is built on the TALL stack:

- **[PHP 8.2](https://www.php.net)** - Programming language
- **[Laravel 12](https://laravel.com)** - PHP framework
- **[Livewire](https://livewire.laravel.com)** - Dynamic frontend with **[Volt](https://livewire.laravel.com/docs/volt)** for single-file components
- **[Tailwind CSS 4](https://tailwindcss.com)** - Utility-first CSS framework
- **[Flux UI](https://flux-ui.com)** - Component library
- **[Vite](https://vitejs.dev)** - Asset bundling
- **[Pest](https://pestphp.com)** - Testing framework

## Getting Started

### Prerequisites

- PHP 8.2 or higher
- Composer
- Node.js (with npm)
- Database (MySQL, PostgreSQL, SQLite)

### Installation

1. Clone the repository
   ```bash
   git clone https://github.com/yourusername/d-agriventory.git
   cd d-agriventory
   ```

2. Install PHP dependencies
   ```bash
   composer install
   ```

3. Install JavaScript dependencies
   ```bash
   npm install
   ```

4. Create and configure your environment file
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

5. Configure your database in the `.env` file
   ```
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=d_agriventory
   DB_USERNAME=root
   DB_PASSWORD=
   ```

6. Run migrations
   ```bash
   php artisan migrate
   ```

7. Seed the database (optional)
   ```bash
   php artisan db:seed
   ```

### Running the Application

For development:

```bash
composer run dev
```

This command will concurrently run:
- Laravel development server
- Queue listener
- Vite development server

## Project Structure

D'Agriventory follows a standard Laravel structure with some key conventions:

- **Business Logic**: Primary located within `app/Livewire` components
- **Controllers**: Standard controllers in `app/Http/Controllers`
- **Models**: Eloquent models in `app/Models`
- **Views**: Blade templates in `resources/views`, organized by feature

## Testing

Run tests with:

```bash
composer test
```

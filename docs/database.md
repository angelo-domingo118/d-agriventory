# Database Management

Database migration patterns, seeding procedures, and backup strategies for D'Agriventory's MySQL-based architecture.

> **⚠️ Database Compatibility**: This application is optimized for MySQL 8.0+ and uses MySQL-specific features for enhanced performance and functionality. While partial SQLite support is included for development purposes, some features may not work correctly:
> 
> **MySQL-specific features used:**
> - `CAST(...AS UNSIGNED)` for numeric sorting of ICS/PAR numbers
> - `CONCAT_WS()` and `DATE_FORMAT()` functions (with SQLite alternatives provided)
> - Advanced indexing and performance optimizations
> 
> **SQLite limitations:**
> - Numeric sorting may not work correctly for ICS/PAR numbers
> - Some advanced queries may fail or perform poorly
> 
> **Recommendation:** Use MySQL 8.0+ for production deployments.

## migration-rules

### naming-conventions

```bash
# Table creation
php artisan make:migration create_inventory_items_table

# Adding columns
php artisan make:migration add_serial_number_to_inventory_items_table

# Foreign key relationships
php artisan make:migration add_division_id_to_users_table
```

**Why these patterns?** Laravel's migration naming enables automatic table detection and provides clear intent for future developers.

### schema-patterns

```php
// ✅ Good - Complete column definition
Schema::create('inventory_items', function (Blueprint $table) {
    $table->id();
    $table->string('name', 100)->index();
    $table->text('description')->nullable();
    $table->foreignId('category_id')->constrained('primary_categories')->onDelete('cascade');
    $table->decimal('cost', 10, 2)->default(0.00);
    $table->enum('status', ['active', 'inactive', 'disposed'])->default('active');
    $table->timestamps();
});

// ❌ Bad - Missing constraints and indexes
Schema::create('inventory_items', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->text('description');
    $table->integer('category_id');
    $table->timestamps();
});
```

### foreign-key-standards

```php
// ✅ Good - Explicit cascade behaviour
$table->foreignId('user_id')
    ->constrained('users')
    ->onDelete('cascade')
    ->onUpdate('cascade');

// ✅ Good - Preserve data on deletion
$table->foreignId('division_id')
    ->constrained('divisions')
    ->onDelete('set null')
    ->nullable();
```

**Why explicit cascades?** Prevents orphaned records and makes deletion behaviour predictable for data integrity.

## database-compatibility

### Current Status

The D'Agriventory application includes database driver detection code that attempts to support both MySQL and SQLite, but the implementation is **incomplete**. While some effort was made to provide cross-database compatibility, certain MySQL-specific features are used without SQLite alternatives.

### MySQL-Specific Features

The following MySQL-specific features are used throughout the application:

1. **`CAST(...AS UNSIGNED)` for Numeric Sorting**
   ```php
   // Found in: resources/views/livewire/admin/inventory/ics/index.blade.php:240
   $query->orderBy(DB::raw('CAST(ics_number.ics_number AS UNSIGNED)'), $this->sortDirection);
   ```
   - **Purpose**: Sorts ICS numbers numerically instead of alphabetically
   - **SQLite Alternative**: Would need `CAST(...AS INTEGER)`
   - **Impact**: Sorting of ICS/PAR numbers will be incorrect in SQLite

2. **String Concatenation Functions** ✅ *Has SQLite support*
   ```php
   // MySQL: CONCAT_WS('-', ics_number.ics_type, ics_number.ics_number, DATE_FORMAT(...))
   // SQLite: ics_number.ics_type || '-' || ics_number.ics_number || '-' || STRFTIME(...)
   ```

3. **Date Formatting Functions** ✅ *Has SQLite support*
   ```php
   // MySQL: DATE_FORMAT(ics_number.date_accepted, '%m-%Y')
   // SQLite: STRFTIME('%m-%Y', ics_number.date_accepted)
   ```

### For Developers: Fixing SQLite Compatibility

If you want to complete the SQLite support, you would need to:

1. **Fix the CAST issue** by adding database driver detection:
   ```php
   $dbDriver = DB::connection()->getDriverName();
   $castExpression = $dbDriver === 'sqlite' 
       ? 'CAST(ics_number.ics_number AS INTEGER)'
       : 'CAST(ics_number.ics_number AS UNSIGNED)';
   $query->orderBy(DB::raw($castExpression), $this->sortDirection);
   ```

2. **Update all similar instances** in:
   - `resources/views/livewire/admin/inventory/ics/create.blade.php`
   - Any other files using `CAST(...AS UNSIGNED)`

3. **Test thoroughly** with SQLite to ensure all functionality works

### Production Recommendations

- **Use MySQL 8.0+** for all production deployments
- **SQLite is acceptable** for local development and testing only
- **Consider PostgreSQL support** if needed (would require additional work)

### Configuration Notes

The default database configuration in `config/database.php` is set to SQLite:
```php
'default' => env('DB_CONNECTION', 'sqlite'),
```

This suggests the application was intended to support SQLite, but the implementation was never completed.

## seeding-order

### dependency-hierarchy

```bash
# Core reference data (no dependencies)
php artisan db:seed --class=DivisionSeeder
php artisan db:seed --class=PositionSeeder
php artisan db:seed --class=SupplierSeeder

# Category structure
php artisan db:seed --class=PrimaryCategorySeeder
php artisan db:seed --class=SecondaryCategorySeeder

# User accounts (requires divisions and positions)
php artisan db:seed --class=AdminUserSeeder
php artisan db:seed --class=UserSeeder

# Inventory data (requires categories and users)
php artisan db:seed --class=ItemsCatalogSeeder
php artisan db:seed --class=InventoryItemSeeder

# Transactional data (requires all above)
php artisan db:seed --class=IcsDataSeeder
php artisan db:seed --class=ParDataSeeder
```

### seeder-best-practices

```php
// ✅ Good - Idempotent seeding with upsert
public function run(): void
{
    Division::upsert([
        ['name' => 'Regional Office', 'code' => 'RO'],
        ['name' => 'Finance Division', 'code' => 'FIN'],
    ], ['code'], ['name']);
}

// ❌ Bad - Creates duplicates on re-run
public function run(): void
{
    Division::create(['name' => 'Regional Office', 'code' => 'RO']);
    Division::create(['name' => 'Finance Division', 'code' => 'FIN']);
}
```

## backup-procedures

### automated-backup-script

```bash
#!/bin/bash
# save as: scripts/backup-database.sh

DATE=$(date +%Y%m%d_%H%M%S)
DB_NAME="d-agriventory"
BACKUP_DIR="/var/backups/mysql"
BACKUP_FILE="${BACKUP_DIR}/${DB_NAME}_${DATE}.sql"

# Create backup directory
mkdir -p $BACKUP_DIR

# Perform backup with compression
mysqldump --single-transaction \
          --routines \
          --triggers \
          --lock-tables=false \
          --user=$DB_USERNAME \
          --password=$DB_PASSWORD \
          $DB_NAME | gzip > "${BACKUP_FILE}.gz"

# Verify backup
if [ $? -eq 0 ]; then
    echo "Backup successful: ${BACKUP_FILE}.gz"
    
    # Keep only last 7 days of backups
    find $BACKUP_DIR -name "${DB_NAME}_*.sql.gz" -mtime +7 -delete
else
    echo "Backup failed!"
    exit 1
fi
```

### restore-procedure

```bash
#!/bin/bash
# Restore from backup file

BACKUP_FILE="$1"

if [ -z "$BACKUP_FILE" ]; then
    echo "Usage: ./restore-database.sh /path/to/backup.sql.gz"
    exit 1
fi

# Confirm restoration
read -p "This will replace the current database. Continue? (y/N): " confirm
if [[ $confirm != [yY] ]]; then
    echo "Restoration cancelled."
    exit 0
fi

# Stop application services
php artisan down

# Restore database
gunzip -c "$BACKUP_FILE" | mysql \
    --user=$DB_USERNAME \
    --password=$DB_PASSWORD \
    $DB_NAME

# Clear caches and restart
php artisan migrate:status
php artisan optimize:clear
php artisan up

echo "Database restored from: $BACKUP_FILE"
```

## maintenance-tasks

### weekly-maintenance

```bash
# Check database size and performance
mysql -e "SELECT table_name, ROUND(((data_length + index_length) / 1024 / 1024), 2) AS 'Size (MB)' FROM information_schema.TABLES WHERE table_schema = 'd-agriventory' ORDER BY (data_length + index_length) DESC;"

# Optimise tables
php artisan db:optimize

# Audit log cleanup (keep 90 days)
php artisan audit:clean --days=90
```

### monitoring-queries

```sql
-- Check for long-running queries
SELECT id, user, host, db, command, time, info
FROM information_schema.processlist
WHERE time > 30;

-- Monitor table sizes
SELECT table_name, 
       ROUND(((data_length + index_length) / 1024 / 1024), 2) AS 'Size (MB)'
FROM information_schema.tables
WHERE table_schema = 'd-agriventory'
ORDER BY (data_length + index_length) DESC;
```

## documentation-links

### Database and Migration Documentation

- **Laravel Migrations**: [Database Migrations Guide](https://laravel.com/docs/12.x/migrations) - Schema management
- **Laravel Eloquent**: [Eloquent ORM Documentation](https://laravel.com/docs/12.x/eloquent) - Database modeling
- **Database Seeding**: [Laravel Seeding Guide](https://laravel.com/docs/12.x/seeding) - Populating databases
- **Query Builder**: [Laravel Query Builder](https://laravel.com/docs/12.x/queries) - Database queries

### MySQL-Specific Resources

- **MySQL 8.0**: [Official MySQL 8.0 Documentation](https://dev.mysql.com/doc/refman/8.0/en/) - Complete MySQL reference
- **MySQL Performance**: [MySQL Performance Tuning](https://dev.mysql.com/doc/refman/8.0/en/optimization.html) - Optimization guide
- **MySQL JSON Functions**: [JSON Function Reference](https://dev.mysql.com/doc/refman/8.0/en/json-functions.html) - JSON data handling
- **MySQL Backup**: [MySQL Backup and Recovery](https://dev.mysql.com/doc/refman/8.0/en/backup-and-recovery.html) - Data protection

### Database Administration

- **Laravel Database Configuration**: [Database Configuration](https://laravel.com/docs/12.x/database#configuration) - Connection setup
- **Database Transactions**: [Laravel Transactions](https://laravel.com/docs/12.x/database#database-transactions) - ACID operations
- **Database Events**: [Eloquent Events](https://laravel.com/docs/12.x/eloquent#events) - Model lifecycle hooks
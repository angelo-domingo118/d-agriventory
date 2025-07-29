# Testing Guide

Comprehensive testing strategy using Pest for unit/feature tests in D'Agriventory.

## testing-philosophy

All tests run against a dedicated MySQL database (`d-agriventory-testing`) to ensure consistency with production environments. Tests cover business logic, user interactions, and integration points to maintain system reliability and prevent regressions during development.

## pest-framework

### unit-tests

```php
// tests/Unit/PermissionServiceTest.php

test('admin user has all permissions', function () {
    $admin = User::factory()->create(['role' => Role::Admin]);
    
    expect(PermissionService::hasPermission($admin, 'manage_users'))
        ->toBeTrue()
        ->and(PermissionService::hasPermission($admin, 'view_reports'))
        ->toBeTrue();
});

test('inventory manager has limited permissions', function () {
    $manager = User::factory()->create(['role' => Role::InventoryManager]);
    
    expect(PermissionService::hasPermission($manager, 'manage_inventory'))
        ->toBeTrue()
        ->and(PermissionService::hasPermission($manager, 'manage_users'))
        ->toBeFalse();
});

test('calculates item total cost correctly', function () {
    $item = InventoryItem::factory()->create(['cost' => 1500.00, 'quantity' => 3]);
    
    expect($item->totalCost())->toBe(4500.00);
});
```

### feature-tests

```php
// tests/Feature/Admin/InventoryManagementTest.php

test('admin can create inventory item', function () {
    $admin = User::factory()->admin()->create();
    $category = PrimaryCategory::factory()->create();
    
    $this->actingAs($admin)
        ->post('/admin/inventory/items', [
            'name' => 'Test Equipment',
            'description' => 'Testing equipment for lab',
            'category_id' => $category->id,
            'cost' => 2500.00,
        ])
        ->assertRedirect()
        ->assertSessionHas('success');
    
    $this->assertDatabaseHas('inventory_items', [
        'name' => 'Test Equipment',
        'cost' => 2500.00,
    ]);
});

test('inventory manager cannot access user management', function () {
    $manager = User::factory()->inventoryManager()->create();
    
    $this->actingAs($manager)
        ->get('/admin/users')
        ->assertForbidden();
});
```

### running-pest-tests

```bash
# Run all unit and feature tests
composer test

# Run specific test file
./vendor/bin/pest tests/Feature/InventoryManagementTest.php

# Run tests with coverage (requires Xdebug)
./vendor/bin/pest --coverage

# Run tests in parallel (faster execution)
php artisan test --parallel

# Filter tests by name
./vendor/bin/pest --filter="admin can create"

# Watch mode for continuous testing
./vendor/bin/pest --watch
```



## parallel-testing

### configuration

```php
// phpunit.xml - Parallel testing configuration
<phpunit>
    <testsuites>
        <testsuite name="Feature">
            <directory>tests/Feature</directory>
        </testsuite>
        <testsuite name="Unit">
            <directory>tests/Unit</directory>
        </testsuite>
    </testsuites>
</phpunit>
```

### execution-commands

```bash
# Run tests in parallel (automatically determines process count)
php artisan test --parallel

# Specify number of processes
php artisan test --parallel --processes=4

# Parallel with coverage
php artisan test --parallel --coverage

# Combine with filtering
php artisan test --parallel --filter="InventoryTest"
```

## test-data-management

### factories-and-seeders

```php
// database/factories/InventoryItemFactory.php
class InventoryItemFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => $this->faker->words(3, true),
            'description' => $this->faker->sentence(),
            'cost' => $this->faker->randomFloat(2, 100, 50000),
            'category_id' => PrimaryCategory::factory(),
            'division_id' => Division::factory(),
        ];
    }
    
    public function expensive(): static
    {
        return $this->state(['cost' => $this->faker->randomFloat(2, 10000, 100000)]);
    }
}
```

### database-transactions

Tests automatically wrap each test in a database transaction and rollback changes, ensuring test isolation without affecting the database state between tests.

## documentation-links

### Testing Framework Documentation

- **Pest PHP**: [Official Pest Documentation](https://pestphp.com/docs) - Modern PHP testing framework
- **Laravel Testing**: [Laravel Testing Guide](https://laravel.com/docs/12.x/testing) - Laravel's built-in testing features
- **PHPUnit**: [PHPUnit Documentation](https://phpunit.de/documentation.html) - Underlying testing framework

### Database Testing

- **Database Testing**: [Laravel Database Testing](https://laravel.com/docs/12.x/database-testing) - Testing with databases
- **Model Factories**: [Eloquent Factories](https://laravel.com/docs/12.x/eloquent-factories) - Generating test data
- **Database Seeding**: [Database Seeding Guide](https://laravel.com/docs/12.x/seeding) - Populating test databases

### Advanced Testing Topics

- **HTTP Testing**: [Laravel HTTP Tests](https://laravel.com/docs/12.x/http-tests) - Testing API endpoints
- **Console Testing**: [Artisan Command Testing](https://laravel.com/docs/12.x/console-tests) - Testing CLI commands
- **Mocking**: [Laravel Mocking Guide](https://laravel.com/docs/12.x/mocking) - Test doubles and mocks
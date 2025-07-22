# Coding Standards

This document establishes code quality standards for D'Agriventory development, ensuring maintainability and team consistency.

## baseline-standards

**PSR-12**: All PHP code must conform to [PSR-12](https://www.php-fig.org/psr/psr-12/) for consistent formatting and structure. Laravel Pint enforces these automatically.

**Why PSR-12?** Reduces cognitive load during code review, prevents formatting debates, and ensures compatibility with standard PHP tooling.

## naming-conventions

### classes-and-interfaces

**Rule**: Use `PascalCase` for classes, interfaces, and enums.

```php
// ✅ Good
class AdminUserController
interface InventoryServiceInterface
enum UserRole

// ❌ Bad
class admin_user_controller
class adminUserController
```

**Why?** Follows PHP community standards and Laravel framework conventions for immediate recognition.

### methods-and-variables

**Rule**: Use `camelCase` for methods, properties, and variables.

```php
// ✅ Good
public function createInventoryItem()
private $itemsCount = 0;
$currentUser = auth()->user();

// ❌ Bad
public function create_inventory_item()
private $items_count = 0;
$current_user = auth()->user();
```

**Why?** Consistent with Laravel's Eloquent methods and modern PHP practices, improving code readability.

### constants-and-database

**Rule**: Use `SCREAMING_SNAKE_CASE` for constants, `snake_case` for database columns.

```php
// ✅ Good - Constants
public const MAX_ITEMS_PER_BATCH = 100;
public const DEFAULT_DIVISION_NAME = 'Regional Office';

// ✅ Good - Database columns
$table->string('first_name');
$table->timestamp('created_at');

// ❌ Bad
public const maxItemsPerBatch = 100;
$table->string('firstName');
```

**Why?** Database conventions ensure compatibility with Laravel migrations and maintain SQL readability.

## laravel-specific-rules

### model-conventions

**Rule**: Models are singular, tables are plural, relationships follow Laravel conventions.

```php
// ✅ Good
class InventoryItem extends Model // Model: singular
{
    protected $table = 'inventory_items'; // Table: plural
    
    public function division() // belongsTo: singular
    {
        return $this->belongsTo(Division::class);
    }
    
    public function specifications() // hasMany: plural
    {
        return $this->hasMany(ItemSpecification::class);
    }
}

// ❌ Bad
class InventoryItems extends Model
{
    public function getDivision() // Avoid 'get' prefix
    {
        return $this->belongsTo(Division::class);
    }
}
```

**Why?** Leverages Laravel's automatic relationship resolution and follows framework expectations for cleaner code.

### livewire-components

**Rule**: Component classes use `PascalCase`, views use `kebab-case`, methods use `camelCase`.

```php
// ✅ Good
class CreateInventoryItem extends Component // PascalCase class
{
    public function createItem() // camelCase method
    {
        // Implementation
    }
    
    public function render()
    {
        return view('livewire.admin.create-inventory-item'); // kebab-case view
    }
}

// ❌ Bad
class create_inventory_item extends Component
{
    public function create_item() // snake_case not appropriate here
    {
        // Implementation
    }
}
```

**Why?** Maintains consistency with Livewire conventions and ensures proper component registration.

## documentation-standards

### method-documentation

**Rule**: Document public methods with clear purpose, parameters, and return types.

```php
// ✅ Good
/**
 * Create a new inventory item with validation and audit logging.
 *
 * @param array $itemData The validated item attributes
 * @param User $assignedUser The user responsible for the item
 * @return InventoryItem The created item instance
 * @throws InvalidRoleException When user lacks creation permissions
 */
public function createInventoryItem(array $itemData, User $assignedUser): InventoryItem

// ❌ Bad
// Creates item
public function createInventoryItem($data, $user)
```

**Why?** Enables IDE autocompletion, clarifies intent for future developers, and documents exception handling.

## security-patterns

### mass-assignment-protection

**Rule**: Always define `$fillable` or `$guarded` properties on models.

```php
// ✅ Good
class InventoryItem extends Model
{
    protected $fillable = [
        'name',
        'description',
        'category_id',
        'assigned_user_id',
    ];
}

// ❌ Bad
class InventoryItem extends Model
{
    // No mass assignment protection
}
```

**Why?** Prevents mass assignment vulnerabilities and makes intentions explicit for security audits.

## further-reading

- [Larasoft SOP – Naming](https://sops.larasoft.io/)
- [Alexey Mezenin Laravel Best Practices](https://github.com/alexeymezenin/laravel-best-practices)
- [Laravel Documentation – Eloquent](https://laravel.com/docs/eloquent)
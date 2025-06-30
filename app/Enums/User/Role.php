<?php

namespace App\Enums\User;

enum Role: string
{
    case ADMIN = 'admin';
    case INVENTORY_MANAGER = 'inventory_manager';
    case REGULAR = 'regular';

    /**
     * Get all role values as an array.
     *
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Check if a given string is a valid role.
     *
     * @param string $value
     * @return bool
     */
    public static function isValid(string $value): bool
    {
        return in_array($value, self::values());
    }

    /**
     * Get enum instance from a string value.
     *
     * @param string $value
     * @return self|null
     */
    public static function fromValue(string $value): ?self
    {
        return self::tryFrom($value);
    }

    /**
     * Get the user-friendly display name for the role.
     *
     * @return string
     */
    public function getDisplayName(): string
    {
        return match ($this) {
            self::ADMIN => 'Admin',
            self::INVENTORY_MANAGER => 'Inventory Manager',
            self::REGULAR => 'Regular',
        };
    }
} 
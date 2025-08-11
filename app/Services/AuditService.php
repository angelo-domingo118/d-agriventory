<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * AuditService provides standardized methods for creating audit log entries
 * for all CRUD operations throughout the application.
 */
class AuditService
{
    /**
     * Log a CREATE operation
     */
    public static function logCreate(Model $model, ?string $description = null): void
    {
        self::createAuditLog(
            model: $model,
            actionType: 'CREATE',
            newValues: $model->toArray(),
            description: $description ?? "Created new {$model->getTable()} record"
        );
    }

    /**
     * Log an UPDATE operation
     */
    public static function logUpdate(Model $model, array $originalValues, ?string $description = null): void
    {
        $changes = $model->getChanges();
        
        // Only log if there are actual changes
        if (empty($changes)) {
            return;
        }

        self::createAuditLog(
            model: $model,
            actionType: 'UPDATE',
            oldValues: $originalValues,
            newValues: $model->toArray(),
            description: $description ?? "Updated {$model->getTable()} record"
        );
    }

    /**
     * Log a DELETE operation
     */
    public static function logDelete(Model $model, ?string $description = null): void
    {
        self::createAuditLog(
            model: $model,
            actionType: 'DELETE',
            oldValues: $model->toArray(),
            description: $description ?? "Deleted {$model->getTable()} record"
        );
    }

    /**
     * Log a RESTORE operation (for soft deletes)
     */
    public static function logRestore(Model $model, ?string $description = null): void
    {
        self::createAuditLog(
            model: $model,
            actionType: 'RESTORE',
            newValues: $model->toArray(),
            description: $description ?? "Restored {$model->getTable()} record"
        );
    }

    /**
     * Log a TRANSFER operation (for inventory transfers)
     */
    public static function logTransfer(Model $model, array $transferData, ?string $description = null): void
    {
        self::createAuditLog(
            model: $model,
            actionType: 'TRANSFER',
            newValues: $transferData,
            description: $description ?? "Transferred {$model->getTable()} record"
        );
    }

    /**
     * Log a custom operation
     */
    public static function logCustom(
        Model $model,
        string $actionType,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?string $description = null
    ): void {
        self::createAuditLog(
            model: $model,
            actionType: $actionType,
            oldValues: $oldValues,
            newValues: $newValues,
            description: $description
        );
    }

    /**
     * Create the actual audit log record
     */
    private static function createAuditLog(
        Model $model,
        string $actionType,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?string $description = null
    ): void {
        try {
            AuditLog::create([
                'user_id' => Auth::id(),
                'table_name' => $model->getTable(),
                'record_id' => $model->getKey() ?? 0, // Use 0 for failed creates
                'action_type' => $actionType,
                'old_values' => $oldValues ? self::sanitizeValues($oldValues) : null,
                'new_values' => $newValues ? self::sanitizeValues($newValues) : null,
                'description' => $description,
            ]);
        } catch (\Exception $e) {
            // Log audit failures but don't break the main operation
            Log::error('Failed to create audit log', [
                'model' => get_class($model),
                'action' => $actionType,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Sanitize sensitive data from audit values
     */
    private static function sanitizeValues(array $values): array
    {
        $sensitiveFields = [
            'password',
            'password_confirmation',
            'remember_token',
            'email_verified_at',
        ];

        foreach ($sensitiveFields as $field) {
            if (isset($values[$field])) {
                $values[$field] = '[REDACTED]';
            }
        }

        return $values;
    }

    /**
     * Get human-readable model name from table name
     */
    public static function getModelName(string $tableName): string
    {
        return match ($tableName) {
            'users' => 'User',
            'admin_users' => 'Admin User',
            'employees' => 'Employee',
            'divisions' => 'Division',
            'suppliers' => 'Supplier',
            'contracts' => 'Contract',
            'contract_items' => 'Contract Item',
            'primary_categories' => 'Primary Category',
            'secondary_categories' => 'Secondary Category',
            'items_catalog' => 'Item',
            'item_specifications' => 'Item Specification',
            'ics_number' => 'ICS Record',
            'par_number' => 'PAR Record',
            'idr_number' => 'IDR Record',
            'ics_item_batches' => 'ICS Batch',
            'par_item_batches' => 'PAR Batch',
            'idr_item_batches' => 'IDR Batch',
            'consumable_records' => 'Consumable Record',
            'consumable_items' => 'Consumable Item',
            'ics_transfers' => 'ICS Transfer',
            'par_transfers' => 'PAR Transfer',
            'acknowledgement_receipts' => 'Acknowledgement Receipt',
            default => ucwords(str_replace('_', ' ', $tableName)),
        };
    }
}

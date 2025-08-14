<?php

namespace App\Observers;

use App\Services\AuditService;
use Illuminate\Database\Eloquent\Model;
use WeakMap;

/**
 * Universal observer that handles audit logging for all models
 */
class AuditLogObserver
{
    /**
     * Registry to store original values for models being updated.
     * Uses WeakMap to automatically clean up when models are garbage collected.
     *
     * @var WeakMap<Model, array>
     */
    private static WeakMap $originalValuesRegistry;

    /**
     * Initialize the registry if not already done
     */
    private static function initializeRegistry(): void
    {
        if (! isset(self::$originalValuesRegistry)) {
            self::$originalValuesRegistry = new WeakMap;
        }
    }

    /**
     * Store original values before update
     */
    public function updating(Model $model): void
    {
        // Skip audit logging for AuditLog itself to prevent infinite loops
        if ($model instanceof \App\Models\AuditLog) {
            return;
        }

        self::initializeRegistry();
        // Store original values in the registry using the model object as key
        self::$originalValuesRegistry[$model] = $model->getOriginal();
    }

    /**
     * Handle the model "created" event.
     */
    public function created(Model $model): void
    {
        // Skip audit logging for AuditLog itself to prevent infinite loops
        if ($model instanceof \App\Models\AuditLog) {
            return;
        }

        AuditService::logCreate($model);
    }

    /**
     * Handle the model "updated" event.
     */
    public function updated(Model $model): void
    {
        // Skip audit logging for AuditLog itself to prevent infinite loops
        if ($model instanceof \App\Models\AuditLog) {
            return;
        }

        self::initializeRegistry();

        // Retrieve original values from registry, fallback to getOriginal() if not found
        $originalValues = self::$originalValuesRegistry[$model] ?? $model->getOriginal();

        AuditService::logUpdate($model, $originalValues);

        // Clean up the registry entry to prevent memory leaks
        // (WeakMap handles this automatically, but explicit cleanup is good practice)
        if (isset(self::$originalValuesRegistry[$model])) {
            unset(self::$originalValuesRegistry[$model]);
        }
    }

    /**
     * Handle the model "deleted" event.
     */
    public function deleted(Model $model): void
    {
        // Skip audit logging for AuditLog itself to prevent infinite loops
        if ($model instanceof \App\Models\AuditLog) {
            return;
        }

        AuditService::logDelete($model);
    }

    /**
     * Handle the model "restored" event.
     */
    public function restored(Model $model): void
    {
        // Skip audit logging for AuditLog itself to prevent infinite loops
        if ($model instanceof \App\Models\AuditLog) {
            return;
        }

        AuditService::logRestore($model);
    }
}

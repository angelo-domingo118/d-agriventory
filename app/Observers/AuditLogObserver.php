<?php

namespace App\Observers;

use App\Services\AuditService;
use Illuminate\Database\Eloquent\Model;

/**
 * Universal observer that handles audit logging for all models
 */
class AuditLogObserver
{
    /**
     * Store original values before update
     */
    public function updating(Model $model): void
    {
        // Store original values so we can log them in the updated event
        $model->_original_for_audit = $model->getOriginal();
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

        $originalValues = $model->_original_for_audit ?? $model->getOriginal();
        AuditService::logUpdate($model, $originalValues);
        
        // Clean up the temporary property
        unset($model->_original_for_audit);
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

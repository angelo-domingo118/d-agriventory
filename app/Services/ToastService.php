<?php

declare(strict_types=1);

namespace App\Services;

use Livewire\Component;

/**
 * ToastService provides standardized methods for dispatching toast notifications
 * with consistent styling and messaging patterns throughout the application.
 */
class ToastService
{
    /**
     * Default duration for toast notifications in milliseconds
     */
    private const DEFAULT_DURATION = 5000;

    /**
     * Duration for quick notifications in milliseconds
     */
    private const QUICK_DURATION = 3000;

    /**
     * Duration for persistent notifications in milliseconds
     */
    private const PERSISTENT_DURATION = 8000;

    /**
     * Dispatch a success toast notification
     */
    public static function success(
        Component $component,
        string $text,
        ?string $heading = null,
        int $duration = self::DEFAULT_DURATION
    ): void {
        self::dispatch($component, [
            'id' => uniqid(),
            'heading' => $heading ?? 'Success!',
            'text' => $text,
            'variant' => 'success',
            'duration' => $duration,
        ]);
    }

    /**
     * Dispatch an error/danger toast notification
     */
    public static function error(
        Component $component,
        string $text,
        ?string $heading = null,
        int $duration = self::PERSISTENT_DURATION
    ): void {
        self::dispatch($component, [
            'id' => uniqid(),
            'heading' => $heading ?? 'Error',
            'text' => $text,
            'variant' => 'danger',
            'duration' => $duration,
        ]);
    }

    /**
     * Dispatch a warning toast notification
     */
    public static function warning(
        Component $component,
        string $text,
        ?string $heading = null,
        int $duration = self::DEFAULT_DURATION
    ): void {
        self::dispatch($component, [
            'id' => uniqid(),
            'heading' => $heading ?? 'Warning',
            'text' => $text,
            'variant' => 'warning',
            'duration' => $duration,
        ]);
    }

    /**
     * Dispatch an info toast notification
     */
    public static function info(
        Component $component,
        string $text,
        ?string $heading = null,
        int $duration = self::DEFAULT_DURATION
    ): void {
        self::dispatch($component, [
            'id' => uniqid(),
            'heading' => $heading,
            'text' => $text,
            'variant' => 'info',
            'duration' => $duration,
        ]);
    }

    /**
     * Dispatch a default toast notification
     */
    public static function message(
        Component $component,
        string $text,
        ?string $heading = null,
        int $duration = self::DEFAULT_DURATION
    ): void {
        self::dispatch($component, [
            'id' => uniqid(),
            'heading' => $heading,
            'text' => $text,
            'variant' => 'default',
            'duration' => $duration,
        ]);
    }

    /**
     * Quick success notification (shorter duration)
     */
    public static function quickSuccess(Component $component, string $text): void
    {
        self::success($component, $text, duration: self::QUICK_DURATION);
    }

    /**
     * Quick info notification (shorter duration)
     */
    public static function quickInfo(Component $component, string $text): void
    {
        self::info($component, $text, duration: self::QUICK_DURATION);
    }

    /**
     * Persistent notification (stays longer, for important information)
     */
    public static function persistent(
        Component $component,
        string $text,
        string $variant = 'info',
        ?string $heading = null
    ): void {
        self::dispatch($component, [
            'id' => uniqid(),
            'heading' => $heading,
            'text' => $text,
            'variant' => $variant,
            'duration' => self::PERSISTENT_DURATION,
        ]);
    }

    /**
     * Permanent notification (never auto-dismisses)
     */
    public static function permanent(
        Component $component,
        string $text,
        string $variant = 'warning',
        ?string $heading = null
    ): void {
        self::dispatch($component, [
            'id' => uniqid(),
            'heading' => $heading,
            'text' => $text,
            'variant' => $variant,
            'duration' => 0, // 0 means permanent
        ]);
    }

    /**
     * Common success messages
     */
    public static function created(Component $component, string $itemType): void
    {
        self::success($component, "{$itemType} created successfully.");
    }

    public static function updated(Component $component, string $itemType): void
    {
        self::success($component, "{$itemType} updated successfully.");
    }

    public static function deleted(Component $component, string $itemType): void
    {
        self::success($component, "{$itemType} deleted successfully.");
    }

    public static function restored(Component $component, string $itemType): void
    {
        self::success($component, "{$itemType} restored successfully.");
    }

    public static function transferred(Component $component, string $itemType): void
    {
        self::success($component, "{$itemType} transferred successfully.");
    }

    /**
     * Common info messages
     */
    public static function noChanges(Component $component): void
    {
        self::info($component, 'Nothing to save.', 'No changes');
    }

    public static function formReset(Component $component): void
    {
        self::success($component, 'Form has been reset to original values.');
    }

    /**
     * Common error messages
     */
    public static function validationError(Component $component, string $message = 'Please check your input and try again.'): void
    {
        self::error($component, $message, 'Validation Error');
    }

    public static function permissionDenied(Component $component): void
    {
        self::error($component, 'You do not have permission to perform this action.', 'Permission Denied');
    }

    public static function relationshipError(Component $component): void
    {
        self::error($component, 'Cannot delete this record because it is referenced by other records.', 'Cannot Delete');
    }

    public static function unexpectedError(Component $component, ?string $details = null): void
    {
        $message = 'An unexpected error occurred.';
        if ($details) {
            $message .= ' '.$details;
        }
        self::error($component, $message, 'Unexpected Error');
    }

    /**
     * Private method to dispatch the toast event
     */
    private static function dispatch(Component $component, array $data): void
    {
        $component->dispatch('notify', ...$data);
    }
}

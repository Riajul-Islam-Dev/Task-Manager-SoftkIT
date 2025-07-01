<?php

declare(strict_types=1);

/**
 * Task Priority Enum with PHP 8.1+ backed enum
 */
enum Priority: string
{
    case HIGH = 'High';
    case MEDIUM = 'Medium';
    case LOW = 'Low';
    
    /**
     * Get all priority values as array
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
    
    /**
     * Get priority color for UI
     */
    public function getColor(): string
    {
        return match($this) {
            self::HIGH => '#dc3545',
            self::MEDIUM => '#ffc107',
            self::LOW => '#28a745'
        };
    }
    
    /**
     * Get Bootstrap badge class
     */
    public function getBadgeClass(): string
    {
        return match($this) {
            self::HIGH => 'bg-danger',
            self::MEDIUM => 'bg-warning',
            self::LOW => 'bg-success'
        };
    }
    
    /**
     * Get priority icon
     */
    public function getIcon(): string
    {
        return match($this) {
            self::HIGH => '🔴',
            self::MEDIUM => '🟡',
            self::LOW => '🟢'
        };
    }
    
    /**
     * Get sort order for database queries
     */
    public function getSortOrder(): int
    {
        return match($this) {
            self::HIGH => 1,
            self::MEDIUM => 2,
            self::LOW => 3
        };
    }
    
    /**
     * Create from string value with validation
     */
    public static function fromString(string $value): self
    {
        return self::tryFrom($value) ?? throw new InvalidArgumentException("Invalid priority: $value");
    }
}

/**
 * Calendar Event Type Enum
 */
enum EventType: string
{
    case EVENT = 'event';
    case TASK = 'task';
    case MEETING = 'meeting';
    case REMINDER = 'reminder';
    
    /**
     * Get all event type values as array
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
    
    /**
     * Get event type color for calendar
     */
    public function getColor(): string
    {
        return match($this) {
            self::EVENT => '#3fb950',
            self::TASK => '#388bfd',
            self::MEETING => '#d29922',
            self::REMINDER => '#a5a5f0'
        };
    }
    
    /**
     * Get event type label
     */
    public function getLabel(): string
    {
        return match($this) {
            self::EVENT => 'General Event',
            self::TASK => 'Task Deadline',
            self::MEETING => 'Meeting',
            self::REMINDER => 'Reminder'
        };
    }
    
    /**
     * Create from string value with validation
     */
    public static function fromString(string $value): self
    {
        return self::tryFrom($value) ?? throw new InvalidArgumentException("Invalid event type: $value");
    }
}

/**
 * Alert Message Type Enum
 */
enum AlertType: string
{
    case SUCCESS = 'success';
    case ERROR = 'error';
    case WARNING = 'warning';
    case INFO = 'info';
    
    /**
     * Get Bootstrap alert class
     */
    public function getAlertClass(): string
    {
        return match($this) {
            self::SUCCESS => 'alert-success',
            self::ERROR => 'alert-danger',
            self::WARNING => 'alert-warning',
            self::INFO => 'alert-info'
        };
    }
    
    /**
     * Get Font Awesome icon class
     */
    public function getIconClass(): string
    {
        return match($this) {
            self::SUCCESS => 'fas fa-check-circle',
            self::ERROR => 'fas fa-exclamation-circle',
            self::WARNING => 'fas fa-exclamation-triangle',
            self::INFO => 'fas fa-info-circle'
        };
    }
}
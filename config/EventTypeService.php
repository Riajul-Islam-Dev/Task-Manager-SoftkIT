<?php

declare(strict_types=1);

require_once 'Database.php';

/**
 * Service class for managing dynamic event types
 */
class EventTypeService
{
    /**
     * Get all active event types from database
     * @return array Array of event types with id, code, name, color
     */
    public static function getActiveEventTypes(): array
    {
        try {
            $sql = "SELECT event_type_id, type_code, type_name, type_color 
                    FROM tbl_event_types 
                    WHERE is_active = 1 
                    ORDER BY sort_order ASC, type_name ASC";
            
            return Database::fetchAll($sql);
        } catch (Exception $e) {
            error_log('Error fetching event types: ' . $e->getMessage());
            // Fallback to default types if database fails
            return self::getDefaultEventTypes();
        }
    }
    
    /**
     * Get event type by code
     * @param string $typeCode The event type code
     * @return array|null Event type data or null if not found
     */
    public static function getEventTypeByCode(string $typeCode): ?array
    {
        try {
            $sql = "SELECT event_type_id, type_code, type_name, type_color 
                    FROM tbl_event_types 
                    WHERE type_code = ? AND is_active = 1";
            
            $result = Database::fetchOne($sql, [$typeCode]);
            return $result === false ? null : $result;
        } catch (Exception $e) {
            error_log('Error fetching event type by code: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Validate if event type code exists and is active
     * @param string $typeCode The event type code to validate
     * @return bool True if valid, false otherwise
     */
    public static function isValidEventType(string $typeCode): bool
    {
        return self::getEventTypeByCode($typeCode) !== null;
    }
    
    /**
     * Get all valid event type codes
     * @return array Array of valid type codes
     */
    public static function getValidEventTypeCodes(): array
    {
        $eventTypes = self::getActiveEventTypes();
        return array_column($eventTypes, 'type_code');
    }
    
    /**
     * Get event type color by code
     * @param string $typeCode The event type code
     * @return string Color hex code or default blue
     */
    public static function getEventTypeColor(string $typeCode): string
    {
        $eventType = self::getEventTypeByCode($typeCode);
        return $eventType['type_color'] ?? '#007bff';
    }
    
    /**
     * Get event type name by code
     * @param string $typeCode The event type code
     * @return string Type name or the code itself if not found
     */
    public static function getEventTypeName(string $typeCode): string
    {
        $eventType = self::getEventTypeByCode($typeCode);
        return $eventType['type_name'] ?? ucfirst($typeCode);
    }
    
    /**
     * Add new event type
     * @param string $typeCode Unique code for the event type
     * @param string $typeName Display name for the event type
     * @param string $typeColor Hex color code
     * @param int $sortOrder Sort order (optional)
     * @return bool True if successful, false otherwise
     */
    public static function addEventType(string $typeCode, string $typeName, string $typeColor = '#007bff', int $sortOrder = 0): bool
    {
        try {
            $sql = "INSERT INTO tbl_event_types (type_code, type_name, type_color, sort_order) 
                    VALUES (?, ?, ?, ?)";
            
            $stmt = Database::execute($sql, [$typeCode, $typeName, $typeColor, $sortOrder]);
            return $stmt->rowCount() > 0;
        } catch (Exception $e) {
            error_log('Error adding event type: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Update existing event type
     * @param int $eventTypeId The event type ID
     * @param string $typeName Display name
     * @param string $typeColor Hex color code
     * @param int $sortOrder Sort order
     * @param bool $isActive Active status
     * @return bool True if successful, false otherwise
     */
    public static function updateEventType(int $eventTypeId, string $typeName, string $typeColor, int $sortOrder, bool $isActive = true): bool
    {
        try {
            $sql = "UPDATE tbl_event_types 
                    SET type_name = ?, type_color = ?, sort_order = ?, is_active = ? 
                    WHERE event_type_id = ?";
            
            $stmt = Database::execute($sql, [$typeName, $typeColor, $sortOrder, $isActive, $eventTypeId]);
            return $stmt->rowCount() > 0;
        } catch (Exception $e) {
            error_log('Error updating event type: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Deactivate event type (soft delete)
     * @param int $eventTypeId The event type ID
     * @return bool True if successful, false otherwise
     */
    public static function deactivateEventType(int $eventTypeId): bool
    {
        try {
            $sql = "UPDATE tbl_event_types SET is_active = 0 WHERE event_type_id = ?";
            $stmt = Database::execute($sql, [$eventTypeId]);
            return $stmt->rowCount() > 0;
        } catch (Exception $e) {
            error_log('Error deactivating event type: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Fallback default event types (in case database is unavailable)
     * @return array Default event types
     */
    private static function getDefaultEventTypes(): array
    {
        return [
            ['event_type_id' => 1, 'type_code' => 'event', 'type_name' => 'General Event', 'type_color' => '#3fb950'],
            ['event_type_id' => 2, 'type_code' => 'task', 'type_name' => 'Task Deadline', 'type_color' => '#388bfd'],
            ['event_type_id' => 3, 'type_code' => 'meeting', 'type_name' => 'Meeting', 'type_color' => '#d29922'],
            ['event_type_id' => 4, 'type_code' => 'reminder', 'type_name' => 'Reminder', 'type_color' => '#a5a5f0']
        ];
    }
}
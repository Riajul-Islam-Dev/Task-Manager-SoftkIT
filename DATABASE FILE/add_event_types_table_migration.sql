-- Migration to create event types table for dynamic event type management
-- This allows administrators to add, edit, and remove event types without code changes

USE task_manager;

-- Create event types table
CREATE TABLE IF NOT EXISTS `tbl_event_types` (
    `event_type_id` INT AUTO_INCREMENT PRIMARY KEY,
    `type_code` VARCHAR(50) NOT NULL UNIQUE,
    `type_name` VARCHAR(100) NOT NULL,
    `type_color` VARCHAR(7) NOT NULL DEFAULT '#007bff',
    `is_active` BOOLEAN NOT NULL DEFAULT TRUE,
    `sort_order` INT NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert default event types (migrating from enum)
INSERT INTO `tbl_event_types` (`type_code`, `type_name`, `type_color`, `sort_order`) VALUES
('event', 'General Event', '#3fb950', 1),
('task', 'Task Deadline', '#388bfd', 2),
('meeting', 'Meeting', '#d29922', 3),
('reminder', 'Reminder', '#a5a5f0', 4);

-- First, modify the event_type column to VARCHAR to match type_code
ALTER TABLE `tbl_calendar_events`
MODIFY COLUMN `event_type` VARCHAR(50) DEFAULT 'event';

-- Add foreign key constraint to calendar events table
ALTER TABLE `tbl_calendar_events`
ADD CONSTRAINT `fk_calendar_events_event_type`
FOREIGN KEY (`event_type`) REFERENCES `tbl_event_types`(`type_code`)
ON DELETE RESTRICT ON UPDATE CASCADE;

-- Create index for better performance
CREATE INDEX `idx_event_types_active_sort` ON `tbl_event_types` (`is_active`, `sort_order`);
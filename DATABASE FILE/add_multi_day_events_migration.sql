-- Migration to add multi-day event support to tbl_calendar_events
-- This adds an end_date field to support events spanning multiple days

USE task_manager;

-- Add end_date column for multi-day events
ALTER TABLE `tbl_calendar_events` 
ADD COLUMN `end_date` DATE DEFAULT NULL AFTER `event_date`;

-- Add index for date range queries
ALTER TABLE `tbl_calendar_events` 
ADD INDEX `idx_date_range` (`event_date`, `end_date`);

-- Show the updated table structure
DESCRIBE `tbl_calendar_events`;

-- Example: Update existing single-day events to have end_date same as event_date if needed
-- UPDATE `tbl_calendar_events` SET `end_date` = `event_date` WHERE `end_date` IS NULL;
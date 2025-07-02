-- Migration to add start_time and end_time fields to tbl_calendar_events
-- This replaces the single event_time field with start_time and end_time

USE task_manager;

-- Add new columns for start and end times
ALTER TABLE `tbl_calendar_events` 
ADD COLUMN `start_time` TIME DEFAULT NULL AFTER `event_date`,
ADD COLUMN `end_time` TIME DEFAULT NULL AFTER `start_time`;

-- Migrate existing data: copy event_time to start_time
UPDATE `tbl_calendar_events` 
SET `start_time` = `event_time` 
WHERE `event_time` IS NOT NULL;

-- Drop the old event_time column
ALTER TABLE `tbl_calendar_events` 
DROP COLUMN `event_time`;

-- Add index for time-based queries
ALTER TABLE `tbl_calendar_events` 
ADD INDEX `idx_start_time` (`start_time`),
ADD INDEX `idx_end_time` (`end_time`);

-- Show the updated table structure
DESCRIBE `tbl_calendar_events`;
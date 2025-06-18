-- Calendar Events Table for Task Manager (No Sample Data)
-- Add this table to your existing task_manager database

-- Table structure for table `tbl_calendar_events`
CREATE TABLE `tbl_calendar_events` (
  `event_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `event_title` varchar(200) NOT NULL,
  `event_description` text DEFAULT NULL,
  `event_date` date NOT NULL,
  `event_time` time DEFAULT NULL,
  `event_type` enum('event','task','meeting','reminder') DEFAULT 'event',
  `task_id` int(10) UNSIGNED DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`event_id`),
  KEY `idx_event_date` (`event_date`),
  KEY `idx_task_id` (`task_id`),
  FOREIGN KEY (`task_id`) REFERENCES `tbl_tasks`(`task_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- No sample data included to avoid foreign key constraint issues
-- You can add events through the calendar interface after creating the table

-- Instructions:
-- 1. Run this SQL in your phpMyAdmin or MySQL client
-- 2. Make sure you're connected to the 'task_manager' database
-- 3. The table will be created empty and ready to use
-- 4. Add events through the web interface at calendar.php

-- Table Columns Explanation:
-- event_id: Primary key, auto-increment
-- event_title: Title of the calendar event (required)
-- event_description: Optional description of the event
-- event_date: Date of the event (required)
-- event_time: Time of the event (optional, defaults to all-day)
-- event_type: Type of event (event, task, meeting, reminder)
-- task_id: Optional link to existing task in tbl_tasks
-- created_at: Timestamp when event was created
-- updated_at: Timestamp when event was last updated
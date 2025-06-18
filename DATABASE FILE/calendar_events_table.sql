-- Calendar Events Table for Task Manager
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

-- Sample data for testing (optional)
-- Note: Only use task_id values that exist in tbl_tasks table
INSERT INTO `tbl_calendar_events` (`event_title`, `event_description`, `event_date`, `event_time`, `event_type`, `task_id`) VALUES
('Team Meeting', 'Weekly team sync meeting', '2024-01-15', '10:00:00', 'meeting', NULL),
('Logo Design Deadline', 'Final submission deadline for logo', '2024-01-20', '23:59:00', 'task', 2),
('Client Presentation', 'Present project progress to client', '2024-01-18', '14:00:00', 'meeting', NULL),
('Code Review', 'Review and merge pull requests', '2024-01-16', '16:00:00', 'reminder', NULL);

-- Alternative: Create table without sample data to avoid foreign key issues
-- Just comment out the INSERT statements above if you prefer to start with empty calendar

-- Instructions:
-- 1. Run this SQL in your phpMyAdmin or MySQL client
-- 2. Make sure you're connected to the 'task_manager' database
-- 3. The foreign key constraint will link calendar events to existing tasks
-- 4. You can delete the sample data if you don't want it

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
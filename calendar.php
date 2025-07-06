<?php

declare(strict_types=1);

// Include modern configuration and classes
require_once 'config/constants.php';
require_once 'config/Database.php';
require_once 'config/Session.php';
require_once 'config/Enums.php';
require_once 'config/EventTypeService.php';

// Start session
Session::start();

// Handle AJAX requests for calendar events
if (isset($_GET['action'])) {
    header('Content-Type: application/json');

    if ($_GET['action'] === 'get_events') {
        try {
            $start = $_GET['start'] ?? '';
            $end = $_GET['end'] ?? '';

            // Validate and parse dates
            if (empty($start) || empty($end)) {
                throw new InvalidArgumentException('Start and end dates are required');
            }

            $startDate = date('Y-m-d', strtotime($start));
            $endDate = date('Y-m-d', strtotime($end));

            if (!$startDate || !$endDate) {
                throw new InvalidArgumentException('Invalid date format');
            }

            $sql = "SELECT ce.*, t.task_name, t.task_description, t.priority, l.list_name 
                    FROM tbl_calendar_events ce 
                    LEFT JOIN tbl_tasks t ON ce.task_id = t.task_id
                LEFT JOIN tbl_lists l ON t.list_id = l.list_id 
                    WHERE ce.event_date BETWEEN ? AND ?";

            $events_data = Database::fetchAll($sql, [$startDate, $endDate]);
            $events = [];

            foreach ($events_data as $row) {
                // Get color from event type, fallback to priority-based color
                $eventTypeColor = EventTypeService::getEventTypeColor($row['event_type'] ?? '');
                $priorityColor = match ($row['priority'] ?? null) {
                    'High' => '#dc3545',
                    'Medium' => '#ffc107',
                    'Low' => '#28a745',
                    default => '#007bff'
                };
                
                // Use event type color if available, otherwise use priority color
                $color = $eventTypeColor !== '#007bff' ? $eventTypeColor : $priorityColor;

                $startTime = $row['start_time'] ?? '00:00:00';
                $endTime = $row['end_time'] ?? null;
                $endDate = $row['end_date'] ?? null;

                $eventStart = $row['event_date'] . 'T' . $startTime;
                
                // Handle multi-day events
                if ($endDate && $endDate !== $row['event_date']) {
                    // Multi-day event: end on the end_date
                    $eventEnd = $endTime ? $endDate . 'T' . $endTime : $endDate;
                } else {
                    // Single-day event: end on same day if end_time is provided
                    $eventEnd = $endTime ? $row['event_date'] . 'T' . $endTime : null;
                }

                $events[] = [
                    'id' => $row['event_id'],
                    'title' => $row['task_name'] ?: $row['event_title'],
                    'start' => $eventStart,
                    'end' => $eventEnd,
                    'backgroundColor' => $color,
                    'borderColor' => $color,
                    'classNames' => [
                        'event-type-' . strtolower($row['event_type'] ?? 'event'),
                        $row['priority'] ? 'priority-' . strtolower($row['priority']) : '',
                        ($startTime && $startTime !== '00:00:00') ? 'has-time' : ''
                    ],
                    'extendedProps' => [
                        'description' => $row['task_description'] ?: $row['event_description'],
                        'priority' => $row['priority'],
                        'list_name' => $row['list_name'],
                        'task_id' => $row['task_id'],
                        'event_type' => $row['event_type'],
                        'start_time' => $startTime,
                        'end_time' => $endTime,
                        'end_date' => $endDate
                    ]
                ];
            }

            echo json_encode($events);
            exit;
        } catch (Exception $e) {
            error_log('Calendar events error: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
            exit;
        }
    }

    if ($_GET['action'] === 'get_event_details') {
        try {
            $eventId = (int)($_GET['event_id'] ?? 0);

            if ($eventId <= 0) {
                throw new InvalidArgumentException('Invalid event ID');
            }

            $sql = "SELECT event_id, event_title, event_description, event_date, end_date, start_time, end_time, event_type, task_id FROM tbl_calendar_events WHERE event_id = ?";
            $event = Database::fetchOne($sql, [$eventId]);

            if (!$event) {
                throw new InvalidArgumentException('Event not found');
            }

            echo json_encode($event);
            exit;
        } catch (Exception $e) {
            error_log('Get event details error: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Failed to fetch event details: ' . $e->getMessage()]);
            exit;
        }
    }
}

/**
 * Validates calendar event input data
 */
function validateEventInput(array $data): array
{
    $errors = [];
    $cleaned = [];

    // Validate event title
    $eventTitle = trim($data['event_title'] ?? '');
    if (empty($eventTitle)) {
        $errors[] = 'Event title is required.';
    } elseif (strlen($eventTitle) > 200) {
        $errors[] = 'Event title must be 200 characters or less.';
    } else {
        $cleaned['event_title'] = $eventTitle;
    }

    // Validate event description (optional)
    $eventDescription = trim($data['event_description'] ?? '');
    if (strlen($eventDescription) > 1000) {
        $errors[] = 'Event description must be 1000 characters or less.';
    } else {
        $cleaned['event_description'] = $eventDescription;
    }

    // Validate event start date
    $eventDate = trim($data['event_date'] ?? '');
    if (empty($eventDate)) {
        $errors[] = 'Event start date is required.';
    } else {
        $dateObj = DateTime::createFromFormat('Y-m-d', $eventDate);
        if (!$dateObj || $dateObj->format('Y-m-d') !== $eventDate) {
            $errors[] = 'Please enter a valid start date.';
        } else {
            $cleaned['event_date'] = $eventDate;
        }
    }

    // Validate event end date (optional for multi-day events)
    $endDate = trim($data['end_date'] ?? '');
    if (!empty($endDate)) {
        $endDateObj = DateTime::createFromFormat('Y-m-d', $endDate);
        if (!$endDateObj || $endDateObj->format('Y-m-d') !== $endDate) {
            $errors[] = 'Please enter a valid end date.';
        } else {
            $cleaned['end_date'] = $endDate;
        }
    } else {
        $cleaned['end_date'] = null;
    }

    // Validate that end date is not before start date
    if (!empty($cleaned['event_date']) && !empty($cleaned['end_date'])) {
        $startDateObj = DateTime::createFromFormat('Y-m-d', $cleaned['event_date']);
        $endDateObj = DateTime::createFromFormat('Y-m-d', $cleaned['end_date']);
        
        if ($endDateObj < $startDateObj) {
            $errors[] = 'End date cannot be before start date.';
        }
    }

    // Validate start time (optional)
    $startTime = trim($data['start_time'] ?? '');
    if (!empty($startTime)) {
        $timeObj = DateTime::createFromFormat('H:i', $startTime);
        if (!$timeObj || $timeObj->format('H:i') !== $startTime) {
            $errors[] = 'Please enter a valid start time in HH:MM format.';
        } else {
            $cleaned['start_time'] = $startTime . ':00'; // Add seconds
        }
    } else {
        $cleaned['start_time'] = null;
    }

    // Validate end time (optional)
    $endTime = trim($data['end_time'] ?? '');
    if (!empty($endTime)) {
        $timeObj = DateTime::createFromFormat('H:i', $endTime);
        if (!$timeObj || $timeObj->format('H:i') !== $endTime) {
            $errors[] = 'Please enter a valid end time in HH:MM format.';
        } else {
            $cleaned['end_time'] = $endTime . ':00'; // Add seconds
        }
    } else {
        $cleaned['end_time'] = null;
    }

    // Validate that end time is after start time if both are provided
    if (!empty($cleaned['start_time']) && !empty($cleaned['end_time'])) {
        $startDateTime = DateTime::createFromFormat('H:i:s', $cleaned['start_time']);
        $endDateTime = DateTime::createFromFormat('H:i:s', $cleaned['end_time']);
        
        if ($endDateTime <= $startDateTime) {
            $errors[] = 'End time must be after start time.';
        }
    }

    // Validate event type using dynamic service
    $eventType = trim($data['event_type'] ?? '');
    if (empty($eventType)) {
        $errors[] = 'Please select an event type.';
    } elseif (!EventTypeService::isValidEventType($eventType)) {
        $errors[] = 'Please select a valid event type.';
    } else {
        $cleaned['event_type'] = $eventType;
    }

    // Validate task_id (optional)
    $taskId = (int)($data['task_id'] ?? 0);
    $cleaned['task_id'] = $taskId > 0 ? $taskId : null;

    return [
        'errors' => $errors,
        'data' => $cleaned
    ];
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Verify CSRF token
        if (!Session::verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            Session::setError('Invalid security token. Please try again.');
            header('Location: calendar.php');
            exit;
        }

        if (isset($_POST['add_event'])) {
            $validation = validateEventInput($_POST);
            $validationErrors = $validation['errors'];

            if (!empty($validationErrors)) {
                Session::setError(implode(' ', $validationErrors));
            } else {
                $data = $validation['data'];
                
                // If task_id is provided, verify it exists
                if ($data['task_id']) {
                    $taskExists = Database::fetchOne(
                        "SELECT task_id FROM tbl_tasks WHERE task_id = ?",
                        [$data['task_id']]
                    );

                    if (!$taskExists) {
                        Session::setError('Selected task does not exist.');
                        header('Location: calendar.php');
                        exit;
                    }
                }

                $result = Database::execute(
                    "INSERT INTO tbl_calendar_events (event_title, event_description, event_date, end_date, start_time, end_time, event_type, task_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
                    [$data['event_title'], $data['event_description'], $data['event_date'], $data['end_date'], $data['start_time'], $data['end_time'], $data['event_type'], $data['task_id']]
                );

                if ($result) {
                    Session::setSuccess('Calendar event added successfully!');
                } else {
                    Session::setError('Failed to add calendar event.');
                }
            }

            header('Location: calendar.php');
            exit;
        }

        if (isset($_POST['update_event'])) {
            $eventId = (int)($_POST['event_id'] ?? 0);
            $validation = validateEventInput($_POST);
            $validationErrors = $validation['errors'];

            if ($eventId <= 0) {
                Session::setError('Invalid event ID.');
            } elseif (!empty($validationErrors)) {
                Session::setError(implode(' ', $validationErrors));
            } else {
                // Check if event exists
                $eventExists = Database::fetchOne(
                    "SELECT event_id FROM tbl_calendar_events WHERE event_id = ?",
                    [$eventId]
                );

                if (!$eventExists) {
                    Session::setError('Event not found.');
                } else {
                    $data = $validation['data'];

                    // If task_id is provided, verify it exists
                    if ($data['task_id']) {
                        $taskExists = Database::fetchOne(
                            "SELECT task_id FROM tbl_tasks WHERE task_id = ?",
                            [$data['task_id']]
                        );

                        if (!$taskExists) {
                            Session::setError('Selected task does not exist.');
                            header('Location: calendar.php');
                            exit;
                        }
                    }

                    $result = Database::execute(
                        "UPDATE tbl_calendar_events SET event_title = ?, event_description = ?, event_date = ?, end_date = ?, start_time = ?, end_time = ?, event_type = ?, task_id = ? WHERE event_id = ?",
                        [$data['event_title'], $data['event_description'], $data['event_date'], $data['end_date'], $data['start_time'], $data['end_time'], $data['event_type'], $data['task_id'], $eventId]
                    );

                    if ($result) {
                        Session::setSuccess('Event updated successfully!');
                    } else {
                        Session::setError('Failed to update event.');
                    }
                }
            }

            header('Location: calendar.php');
            exit;
        }

        if (isset($_POST['delete_event'])) {
            $eventId = (int)($_POST['event_id'] ?? 0);

            if ($eventId <= 0) {
                Session::setError('Invalid event ID.');
            } else {
                // Check if event exists
                $eventExists = Database::fetchOne(
                    "SELECT event_id FROM tbl_calendar_events WHERE event_id = ?",
                    [$eventId]
                );

                if (!$eventExists) {
                    Session::setError('Event not found.');
                } else {
                    $result = Database::execute(
                        "DELETE FROM tbl_calendar_events WHERE event_id = ?",
                        [$eventId]
                    );

                    if ($result) {
                        Session::setSuccess('Event deleted successfully!');
                    } else {
                        Session::setError('Failed to delete event.');
                    }
                }
            }

            header('Location: calendar.php');
            exit;
        }
    } catch (Exception $e) {
        error_log('Calendar form error: ' . $e->getMessage());
        Session::setError('An error occurred. Please try again.');
        header('Location: calendar.php');
        exit;
    }
}

// Get all tasks for dropdown
try {
    $tasks = Database::fetchAll(
        "SELECT t.task_id, t.task_name, l.list_name FROM tbl_tasks t LEFT JOIN tbl_lists l ON t.list_id = l.list_id ORDER BY t.task_name"
    );
} catch (Exception $e) {
    error_log('Error fetching tasks: ' . $e->getMessage());
    $tasks = [];
}

// Get all active event types for dropdown
$eventTypes = EventTypeService::getActiveEventTypes();

// Get all lists for navigation
try {
    $lists = Database::fetchAll("SELECT * FROM tbl_lists ORDER BY list_id ASC");
} catch (Exception $e) {
    error_log('Error fetching lists: ' . $e->getMessage());
    $lists = [];
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Calendar - Task Manager</title>
    <link rel="icon" type="image/png" href="assets/img/favicon.png">

    <!-- Bootstrap 5 CSS -->
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="css/all.min.css" rel="stylesheet">
    <!-- SweetAlert2 CSS -->
    <link href="css/sweetalert2.min.css" rel="stylesheet">
    <style>
        /* GitHub-inspired theme */
        body {
            background-color: #0d1117;
            color: #e6edf3;
        }

        .navbar-brand {
            font-weight: 600;
            color: #f0f6fc !important;
        }

        .card {
            background-color: #161b22;
            border: 1px solid #30363d;
            border-radius: 6px;
        }

        .card-header {
            background-color: #21262d;
            border-bottom: 1px solid #30363d;
            color: #f0f6fc;
        }

        .modal-content {
            background-color: #161b22;
            border: 1px solid #30363d;
        }

        .modal-header {
            background-color: #21262d;
            border-bottom: 1px solid #30363d;
            color: #f0f6fc;
        }

        .modal-body {
            background-color: #161b22;
            color: #e6edf3;
        }

        .form-label {
            color: #f0f6fc;
            font-weight: 500;
        }

        .form-control,
        .form-select {
            background-color: #0d1117;
            border: 1px solid #30363d;
            color: #e6edf3;
        }

        .form-control:focus,
        .form-select:focus {
            background-color: #0d1117;
            border-color: #388bfd;
            color: #e6edf3;
            box-shadow: 0 0 0 0.25rem rgba(56, 139, 253, 0.25);
        }

        .form-control::placeholder {
            color: #7d8590;
            opacity: 1;
        }

        .btn-primary {
            background-color: #238636;
            border-color: #238636;
        }

        .btn-primary:hover {
            background-color: #2ea043;
            border-color: #2ea043;
        }

        .btn-secondary {
            background-color: #21262d;
            border-color: #30363d;
            color: #f0f6fc;
        }

        .btn-secondary:hover {
            background-color: #30363d;
            border-color: #484f58;
            color: #f0f6fc;
        }

        .calendar-container {
            background: #161b22;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.3);
            padding: 20px;
            border: 1px solid #30363d;
        }

        .fc-toolbar {
            background-color: #21262d;
            padding: 1rem;
            border-radius: 6px;
            margin-bottom: 1rem;
            border: 1px solid #30363d;
        }

        .fc-toolbar-title {
            color: #f0f6fc !important;
        }

        .fc-button {
            background-color: #238636 !important;
            border-color: #238636 !important;
            color: #fff !important;
        }

        .fc-button:hover {
            background-color: #2ea043 !important;
            border-color: #2ea043 !important;
        }

        .fc-daygrid-day {
            background-color: #0d1117;
        }

        .fc-col-header-cell {
            background-color: #21262d;
            color: #f0f6fc;
        }

        .fc-event {
            cursor: pointer;
            border: none !important;
            border-radius: 6px !important;
            padding: 2px 6px !important;
            margin: 1px 0 !important;
            font-size: 12px !important;
            font-weight: 500 !important;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.3) !important;
            transition: all 0.2s ease !important;
            position: relative !important;
            overflow: hidden !important;
        }

        .fc-event:hover {
            transform: translateY(-1px) !important;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.4) !important;
            z-index: 10 !important;
        }

        .fc-event-title {
            font-weight: 600 !important;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.3) !important;
        }

        .fc-event-time {
            font-size: 10px !important;
            opacity: 0.9 !important;
            font-weight: 400 !important;
        }

        /* Event type specific styling */
        .fc-event[data-event-type="TASK"] {
            background: linear-gradient(135deg, #388bfd 0%, #1f6feb 100%) !important;
            border-left: 4px solid #58a6ff !important;
        }

        .fc-event[data-event-type="MEETING"] {
            background: linear-gradient(135deg, #d29922 0%, #b8860b 100%) !important;
            border-left: 4px solid #f2cc60 !important;
        }

        .fc-event[data-event-type="REMINDER"] {
            background: linear-gradient(135deg, #a5a5f0 0%, #8b5cf6 100%) !important;
            border-left: 4px solid #c4b5fd !important;
        }

        .fc-event[data-event-type="EVENT"] {
            background: linear-gradient(135deg, #3fb950 0%, #2ea043 100%) !important;
            border-left: 4px solid #56d364 !important;
        }

        /* Priority indicators */
        .fc-event[data-priority="High"]::before {
            content: "●";
            color: #ff6b6b;
            font-size: 8px;
            position: absolute;
            top: 2px;
            right: 4px;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.5);
        }

        .fc-event[data-priority="Medium"]::before {
            content: "●";
            color: #ffd93d;
            font-size: 8px;
            position: absolute;
            top: 2px;
            right: 4px;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.5);
        }

        .fc-event[data-priority="Low"]::before {
            content: "●";
            color: #6bcf7f;
            font-size: 8px;
            position: absolute;
            top: 2px;
            right: 4px;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.5);
        }

        /* Multi-day event styling */
        .fc-event.fc-event-start {
            border-top-left-radius: 6px !important;
            border-bottom-left-radius: 6px !important;
            border-top-right-radius: 2px !important;
            border-bottom-right-radius: 2px !important;
        }

        .fc-event.fc-event-end {
            border-top-left-radius: 2px !important;
            border-bottom-left-radius: 2px !important;
            border-top-right-radius: 6px !important;
            border-bottom-right-radius: 6px !important;
        }

        .fc-event.fc-event-middle {
            border-radius: 2px !important;
        }

        /* Event with time indicator */
        .fc-event.has-time::after {
            content: "🕐";
            position: absolute;
            bottom: 1px;
            left: 2px;
            font-size: 8px;
            opacity: 0.7;
        }

        .alert-success {
            background-color: #0f2419;
            border-color: #1a7f37;
            color: #3fb950;
        }

        .alert-danger {
            background-color: #2d1117;
            border-color: #da3633;
            color: #f85149;
        }
        
        /* Enhanced calendar styling */
        .fc-daygrid-day:hover {
            background-color: rgba(88, 166, 255, 0.05);
        }
        
        .fc-day-today {
            background-color: rgba(88, 166, 255, 0.1) !important;
        }
        
        .fc-day-sat, .fc-day-sun {
            background-color: rgba(255, 255, 255, 0.02);
        }
        
        .fc-toolbar {
            background: linear-gradient(135deg, #21262d, #30363d) !important;
            border-radius: 8px !important;
            padding: 15px !important;
            margin-bottom: 20px !important;
            border: 1px solid #30363d !important;
        }
        
        .fc-toolbar-title {
            color: #58a6ff !important;
            font-weight: 600 !important;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.3) !important;
        }
        
        .fc-scrollgrid {
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
        }
        
        .fc-daygrid-day-number {
            color: #c9d1d9;
            font-weight: 500;
            padding: 4px 8px;
            border-radius: 4px;
            transition: all 0.2s ease;
        }
        
        .fc-daygrid-day-number:hover {
            background-color: rgba(88, 166, 255, 0.2);
            color: #58a6ff;
        }
        
        /* Event details modal enhancements */
        .event-details-header {
            transition: all 0.3s ease;
        }
        
        .event-details-body .row > div {
            transition: all 0.2s ease;
        }
        
        .event-details-body .row > div:hover {
            transform: translateX(2px);
        }
    </style>
</head>

<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark" style="background-color: #21262d; border-bottom: 1px solid #30363d;">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-tasks me-2"></i>Task Manager
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">
                            <i class="fas fa-home me-1"></i>Home
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="add-task.php">
                            <i class="fas fa-plus me-1"></i>Add Task
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="manage-list.php">
                            <i class="fas fa-list me-1"></i>Manage Lists
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="calendar.php">
                            <i class="fas fa-calendar me-1"></i>Calendar
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="manage-event-types.php">
                            <i class="fas fa-tags me-1"></i>Event Types
                        </a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <?php if (!empty($lists)): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                                <i class="fas fa-filter me-1"></i>Filter by List
                            </a>
                            <ul class="dropdown-menu">
                                <?php foreach ($lists as $list): ?>
                                    <li><a class="dropdown-item" href="list-task.php?list_id=<?= $list['list_id'] ?>"><?= htmlspecialchars($list['list_name']) ?></a></li>
                                <?php endforeach; ?>
                            </ul>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <!-- Page Header -->
        <div class="row mb-4">
            <div class="col-md-8">
                <h2 class="fw-semibold"><i class="fas fa-calendar me-2"></i>Task Calendar</h2>
                <p>Schedule and view your tasks on the calendar</p>
            </div>
            <div class="col-md-4 text-end">
                <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addEventModal">
                    <i class="fas fa-plus me-1"></i>Add Event
                </button>
            </div>
        </div>

        <!-- Session Messages -->
        <?php
        $flashMessages = Session::getFlashMessages();
        foreach ($flashMessages as $message):
            $alertClass = match ($message['type']) {
                AlertType::SUCCESS => 'alert-success',
                AlertType::ERROR => 'alert-danger',
                AlertType::WARNING => 'alert-warning',
                AlertType::INFO => 'alert-info'
            };
            $icon = match ($message['type']) {
                AlertType::SUCCESS => 'fas fa-check-circle',
                AlertType::ERROR => 'fas fa-exclamation-circle',
                AlertType::WARNING => 'fas fa-exclamation-triangle',
                AlertType::INFO => 'fas fa-info-circle'
            };
        ?>
            <div class="alert <?= $alertClass ?> alert-dismissible fade show" role="alert">
                <i class="<?= $icon ?> me-2"></i><?= htmlspecialchars($message['message']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endforeach; ?>

        <!-- Calendar -->
        <div class="calendar-container">
            <div id="calendar"></div>
        </div>
    </div>

    <!-- Add Event Modal -->
    <div class="modal fade" id="addEventModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-plus me-2"></i>Add Calendar Event</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="addEventForm">
                    <input type="hidden" name="csrf_token" value="<?= Session::getCsrfToken() ?>">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="event_title" class="form-label">Event Title *</label>
                            <input type="text" class="form-control" id="event_title" name="event_title" required maxlength="255">
                            <div class="text-info">Maximum 255 characters</div>
                        </div>
                        <div class="mb-3">
                            <label for="event_description" class="form-label">Description</label>
                            <textarea class="form-control" id="event_description" name="event_description" rows="3" maxlength="1000"></textarea>
                            <div class="text-info">Maximum 1000 characters</div>
                        </div>
                        <div class="row">
                             <div class="col-md-6">
                                 <div class="mb-3">
                                     <label for="event_date" class="form-label">Start Date *</label>
                                     <input type="date" class="form-control" id="event_date" name="event_date" required>
                                 </div>
                             </div>
                             <div class="col-md-6">
                                 <div class="mb-3">
                                     <label for="end_date" class="form-label">End Date</label>
                                     <input type="date" class="form-control" id="end_date" name="end_date">
                                     <div class="text-info">Leave empty for single-day events</div>
                                 </div>
                             </div>
                         </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="start_time" class="form-label">Start Time</label>
                                    <input type="time" class="form-control" id="start_time" name="start_time">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="end_time" class="form-label">End Time</label>
                                    <input type="time" class="form-control" id="end_time" name="end_time">
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="event_type" class="form-label">Event Type</label>
                            <select class="form-select" id="event_type" name="event_type" required>
                                <option value="">-- Select Event Type --</option>
                                <?php foreach ($eventTypes as $eventType): ?>
                                    <option value="<?= htmlspecialchars($eventType['type_code']) ?>">
                                        <?= htmlspecialchars($eventType['type_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="task_id" class="form-label">Link to Task (Optional)</label>
                            <select class="form-select" id="task_id" name="task_id">
                                <option value="">-- Select Task --</option>
                                <?php foreach ($tasks as $task): ?>
                                    <option value="<?= $task['task_id'] ?>">
                                        <?= htmlspecialchars($task['task_name']) ?>
                                        <?php if ($task['list_name']): ?>
                                            (<?= htmlspecialchars($task['list_name']) ?>)
                                        <?php endif; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="add_event" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i>Add Event
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Event Details Modal -->
    <div class="modal fade" id="eventDetailsModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-info-circle me-2"></i>Event Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="eventDetailsContent">
                    <!-- Event details will be loaded here -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" id="editEventBtn" style="display: none;">
                        <i class="fas fa-edit me-1"></i>Edit Event
                    </button>
                    <button type="button" class="btn btn-danger" id="deleteEventBtn" style="display: none;">
                        <i class="fas fa-trash me-1"></i>Delete Event
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Event Modal -->
    <div class="modal fade" id="editEventModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-edit me-2"></i>Edit Calendar Event</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="editEventForm">
                    <input type="hidden" name="csrf_token" value="<?= Session::getCsrfToken() ?>">
                    <input type="hidden" name="event_id" id="edit_event_id">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="edit_event_title" class="form-label">Event Title *</label>
                            <input type="text" class="form-control" id="edit_event_title" name="event_title" required maxlength="255">
                            <div class="text-info">Maximum 255 characters</div>
                        </div>
                        <div class="mb-3">
                            <label for="edit_event_description" class="form-label">Description</label>
                            <textarea class="form-control" id="edit_event_description" name="event_description" rows="3" maxlength="1000"></textarea>
                            <div class="text-info">Maximum 1000 characters</div>
                        </div>
                        <div class="row">
                             <div class="col-md-6">
                                 <div class="mb-3">
                                     <label for="edit_event_date" class="form-label">Start Date *</label>
                                     <input type="date" class="form-control" id="edit_event_date" name="event_date" required>
                                 </div>
                             </div>
                             <div class="col-md-6">
                                 <div class="mb-3">
                                     <label for="edit_end_date" class="form-label">End Date</label>
                                     <input type="date" class="form-control" id="edit_end_date" name="end_date">
                                     <div class="text-info">Leave empty for single-day events</div>
                                 </div>
                             </div>
                         </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="edit_start_time" class="form-label">Start Time</label>
                                    <input type="time" class="form-control" id="edit_start_time" name="start_time">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="edit_end_time" class="form-label">End Time</label>
                                    <input type="time" class="form-control" id="edit_end_time" name="end_time">
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="edit_event_type" class="form-label">Event Type</label>
                            <select class="form-select" id="edit_event_type" name="event_type" required>
                                <option value="">-- Select Event Type --</option>
                                <?php foreach ($eventTypes as $eventType): ?>
                                    <option value="<?= htmlspecialchars($eventType['type_code']) ?>">
                                        <?= htmlspecialchars($eventType['type_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="edit_task_id" class="form-label">Link to Task (Optional)</label>
                            <select class="form-select" id="edit_task_id" name="task_id">
                                <option value="">-- Select Task --</option>
                                <?php foreach ($tasks as $task): ?>
                                    <option value="<?= $task['task_id'] ?>">
                                        <?= htmlspecialchars($task['task_name']) ?>
                                        <?php if ($task['list_name']): ?>
                                            (<?= htmlspecialchars($task['list_name']) ?>)
                                        <?php endif; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="update_event" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i>Update Event
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS -->
    <script src="js/bootstrap.bundle.min.js"></script>
    <!-- FullCalendar JS -->
    <script src="js/index.global.min.js"></script>
    <!-- SweetAlert2 JS -->
    <script src="js/sweetalert2.all.min.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var calendarEl = document.getElementById('calendar');
            var calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,timeGridDay'
                },
                events: {
                    url: 'calendar.php?action=get_events',
                    failure: function() {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            text: 'There was an error while fetching events!',
                            confirmButtonColor: '#dc3545'
                        });
                    }
                },
                eventDidMount: function(info) {
                    // Add data attributes for CSS styling
                    if (info.event.extendedProps.event_type) {
                        info.el.setAttribute('data-event-type', info.event.extendedProps.event_type.toUpperCase());
                    }
                    if (info.event.extendedProps.priority) {
                        info.el.setAttribute('data-priority', info.event.extendedProps.priority);
                    }
                    
                    // Add time indicator class
                    if (info.event.extendedProps.start_time && info.event.extendedProps.start_time !== '00:00:00') {
                        info.el.classList.add('has-time');
                    }
                    
                    // Add tooltip with event details
                    const tooltipContent = [
                        info.event.title,
                        info.event.extendedProps.description ? info.event.extendedProps.description.substring(0, 100) + '...' : '',
                        info.event.extendedProps.priority ? `Priority: ${info.event.extendedProps.priority}` : '',
                        info.event.extendedProps.list_name ? `List: ${info.event.extendedProps.list_name}` : ''
                    ].filter(Boolean).join('\n');
                    
                    info.el.setAttribute('title', tooltipContent);
                },
                eventClick: function(info) {
                    showEventDetails(info.event);
                },
                dateClick: function(info) {
                    // Set the clicked date in the add event modal
                    document.getElementById('event_date').value = info.dateStr;
                    var addEventModal = new bootstrap.Modal(document.getElementById('addEventModal'));
                    addEventModal.show();
                }
            });

            calendar.render();

            function showEventDetails(event) {
                const eventTypeIcon = {
                    'TASK': 'fas fa-tasks',
                    'MEETING': 'fas fa-users',
                    'REMINDER': 'fas fa-bell',
                    'EVENT': 'fas fa-calendar-day'
                };
                
                const priorityIcon = {
                    'High': 'fas fa-exclamation-circle text-danger',
                    'Medium': 'fas fa-exclamation-triangle text-warning',
                    'Low': 'fas fa-info-circle text-success'
                };
                
                var content = `
                    <div class="event-details-header mb-3 p-3 rounded" style="background: linear-gradient(135deg, ${event.backgroundColor}20, ${event.backgroundColor}10); border-left: 4px solid ${event.backgroundColor};">
                        <h5 class="mb-2 d-flex align-items-center">
                            <i class="${eventTypeIcon[event.extendedProps.event_type] || 'fas fa-calendar'} me-2" style="color: ${event.backgroundColor};"></i>
                            <strong>${event.title}</strong>
                            ${event.extendedProps.priority ? `<span class="ms-auto"><i class="${priorityIcon[event.extendedProps.priority]} me-1"></i><span class="badge bg-${getPriorityColor(event.extendedProps.priority)}">${event.extendedProps.priority}</span></span>` : ''}
                        </h5>
                        ${event.extendedProps.event_type ? `<small class="text-muted"><i class="fas fa-tag me-1"></i>${event.extendedProps.event_type}</small>` : ''}
                    </div>
                    
                    <div class="event-details-body">
                        <div class="row mb-3">
                            <div class="col-sm-6">
                                <div class="d-flex align-items-center mb-2">
                                    <i class="fas fa-calendar-alt me-2 text-primary"></i>
                                    <strong>Date:</strong>
                                </div>
                                <div class="ms-4 text-muted">${getDateDisplay(event.start, event.extendedProps.end_date)}</div>
                            </div>
                            ${event.extendedProps.start_time || event.extendedProps.end_time ? `
                            <div class="col-sm-6">
                                <div class="d-flex align-items-center mb-2">
                                    <i class="fas fa-clock me-2 text-info"></i>
                                    <strong>Time:</strong>
                                </div>
                                <div class="ms-4 text-muted">${getTimeDisplay(event.extendedProps.start_time, event.extendedProps.end_time)}</div>
                            </div>
                            ` : ''}
                        </div>
                        
                        ${event.extendedProps.description ? `
                        <div class="mb-3">
                            <div class="d-flex align-items-center mb-2">
                                <i class="fas fa-align-left me-2 text-secondary"></i>
                                <strong>Description:</strong>
                            </div>
                            <div class="ms-4 p-2 rounded" style="background-color: #21262d; border: 1px solid #30363d;">
                                ${event.extendedProps.description}
                            </div>
                        </div>
                        ` : ''}
                        
                        ${event.extendedProps.list_name ? `
                        <div class="mb-3">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-list me-2 text-success"></i>
                                <strong>List:</strong>
                                <span class="ms-2 badge" style="background-color: #238636;">${event.extendedProps.list_name}</span>
                            </div>
                        </div>
                        ` : ''}
                    </div>
                `;

                document.getElementById('eventDetailsContent').innerHTML = content;

                // Show edit and delete buttons and set up functionality
                var editBtn = document.getElementById('editEventBtn');
                var deleteBtn = document.getElementById('deleteEventBtn');
                
                editBtn.style.display = 'inline-block';
                deleteBtn.style.display = 'inline-block';
                
                editBtn.onclick = function() {
                    populateEditModal(event);
                    var editModal = new bootstrap.Modal(document.getElementById('editEventModal'));
                    var detailsModal = bootstrap.Modal.getInstance(document.getElementById('eventDetailsModal'));
                    detailsModal.hide();
                    editModal.show();
                };
                
                deleteBtn.onclick = function() {
                    Swal.fire({
                        title: 'Are you sure?',
                        text: 'You won\'t be able to revert this!',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#dc3545',
                        cancelButtonColor: '#6c757d',
                        confirmButtonText: 'Yes, delete it!'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            deleteEvent(event.id);
                        }
                    });
                };

                var eventModal = new bootstrap.Modal(document.getElementById('eventDetailsModal'));
                eventModal.show();
            }

            function getPriorityColor(priority) {
                switch (priority) {
                    case 'High':
                        return 'danger';
                    case 'Medium':
                        return 'warning';
                    case 'Low':
                        return 'success';
                    default:
                        return 'primary';
                }
            }

            function getTimeDisplay(startTime, endTime) {
                 let timeDisplay = '';
                 
                 if (startTime) {
                     const start = new Date('1970-01-01T' + startTime).toLocaleTimeString('en-US', {
                         hour: '2-digit',
                         minute: '2-digit',
                         hour12: true
                     });
                     timeDisplay += start;
                 }
                 
                 if (endTime) {
                     const end = new Date('1970-01-01T' + endTime).toLocaleTimeString('en-US', {
                         hour: '2-digit',
                         minute: '2-digit',
                         hour12: true
                     });
                     timeDisplay += timeDisplay ? ` - ${end}` : end;
                 }
                 
                 return timeDisplay;
             }

             function getDateDisplay(startDate, endDate) {
                 const startDateStr = startDate.toLocaleDateString();
                 
                 if (endDate && endDate !== startDate.toISOString().split('T')[0]) {
                     const endDateObj = new Date(endDate);
                     return `${startDateStr} - ${endDateObj.toLocaleDateString()}`;
                 }
                 
                 return startDateStr;
             }

            function populateEditModal(event) {
                // Get event details from server to populate edit form
                fetch(`calendar.php?action=get_event_details&event_id=${event.id}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.error) {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error!',
                                text: data.error,
                                confirmButtonColor: '#dc3545'
                            });
                            return;
                        }
                        
                        // Populate edit form fields
                        document.getElementById('edit_event_id').value = data.event_id;
                        document.getElementById('edit_event_title').value = data.event_title || '';
                        document.getElementById('edit_event_description').value = data.event_description || '';
                         document.getElementById('edit_event_date').value = data.event_date || '';
                         document.getElementById('edit_end_date').value = data.end_date || '';
                        document.getElementById('edit_start_time').value = data.start_time ? data.start_time.substring(0, 5) : '';
                        document.getElementById('edit_end_time').value = data.end_time ? data.end_time.substring(0, 5) : '';
                        document.getElementById('edit_event_type').value = data.event_type || 'event';
                        document.getElementById('edit_task_id').value = data.task_id || '';
                    })
                    .catch(error => {
                        console.error('Error fetching event details:', error);
                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            text: 'Failed to load event details',
                            confirmButtonColor: '#dc3545'
                        });
                    });
            }

            function deleteEvent(eventId) {
                var form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="csrf_token" value="<?= Session::getCsrfToken() ?>">
                    <input type="hidden" name="delete_event" value="1">
                    <input type="hidden" name="event_id" value="${eventId}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        });
    </script>
</body>

</html>
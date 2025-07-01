<?php

declare(strict_types=1);

// Include modern configuration and classes
require_once 'config/constants.php';
require_once 'config/Database.php';
require_once 'config/Session.php';
require_once 'config/Enums.php';

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
                $color = match ($row['priority'] ?? null) {
                    'High' => '#dc3545',
                    'Medium' => '#ffc107',
                    'Low' => '#28a745',
                    default => '#007bff'
                };

                $eventTime = $row['event_time'] ?? '00:00:00';

                $events[] = [
                    'id' => $row['event_id'],
                    'title' => $row['task_name'] ?: $row['event_title'],
                    'start' => $row['event_date'] . 'T' . $eventTime,
                    'backgroundColor' => $color,
                    'borderColor' => $color,
                    'extendedProps' => [
                        'description' => $row['task_description'] ?: $row['event_description'],
                        'priority' => $row['priority'],
                        'list_name' => $row['list_name'],
                        'task_id' => $row['task_id'],
                        'event_type' => $row['event_type']
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
}

/**
 * Validates calendar event input data
 */
function validateEventInput(array $data): array
{
    $errors = [];

    // Validate event title
    if (empty(trim($data['event_title'] ?? ''))) {
        $errors[] = 'Event title is required.';
    } elseif (strlen(trim($data['event_title'])) > 255) {
        $errors[] = 'Event title must be less than 255 characters.';
    }

    // Validate event date
    if (empty($data['event_date'] ?? '')) {
        $errors[] = 'Event date is required.';
    } elseif (!DateTime::createFromFormat('Y-m-d', $data['event_date'])) {
        $errors[] = 'Invalid date format.';
    }

    // Validate event time (optional)
    if (!empty($data['event_time']) && !DateTime::createFromFormat('H:i', $data['event_time'])) {
        $errors[] = 'Invalid time format.';
    }

    // Validate event type
    $validTypes = ['event', 'task', 'meeting', 'reminder'];
    if (!empty($data['event_type']) && !in_array($data['event_type'], $validTypes, true)) {
        $errors[] = 'Invalid event type.';
    }

    // Validate task_id (optional)
    if (!empty($data['task_id']) && !is_numeric($data['task_id'])) {
        $errors[] = 'Invalid task ID.';
    }

    return $errors;
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
            $validationErrors = validateEventInput($_POST);

            if (!empty($validationErrors)) {
                Session::setError(implode(' ', $validationErrors));
            } else {
                $eventTitle = trim($_POST['event_title']);
                $eventDescription = trim($_POST['event_description'] ?? '');
                $eventDate = $_POST['event_date'];
                $eventTime = !empty($_POST['event_time']) ? $_POST['event_time'] : null;
                $eventType = $_POST['event_type'] ?? 'event';
                $taskId = !empty($_POST['task_id']) ? (int)$_POST['task_id'] : null;

                // If task_id is provided, verify it exists
                if ($taskId) {
                    $taskExists = Database::fetchOne(
                        "SELECT task_id FROM tbl_tasks WHERE task_id = ?",
                        [$taskId]
                    );

                    if (!$taskExists) {
                        Session::setError('Selected task does not exist.');
                        header('Location: calendar.php');
                        exit;
                    }
                }

                $result = Database::execute(
                    "INSERT INTO tbl_calendar_events (event_title, event_description, event_date, event_time, event_type, task_id) VALUES (?, ?, ?, ?, ?, ?)",
                    [$eventTitle, $eventDescription, $eventDate, $eventTime, $eventType, $taskId]
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
            border: none;
            border-radius: 4px;
        }

        .fc-event-task {
            background-color: #388bfd;
            color: white;
        }

        .fc-event-event {
            background-color: #3fb950;
            color: white;
        }

        .fc-event-meeting {
            background-color: #d29922;
            color: white;
        }

        .fc-event-reminder {
            background-color: #a5a5f0;
            color: white;
        }

        .priority-high {
            border-left: 4px solid #dc3545 !important;
        }

        .priority-medium {
            border-left: 4px solid #ffc107 !important;
        }

        .priority-low {
            border-left: 4px solid #28a745 !important;
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
                            <div class="form-text">Maximum 255 characters</div>
                        </div>
                        <div class="mb-3">
                            <label for="event_description" class="form-label">Description</label>
                            <textarea class="form-control" id="event_description" name="event_description" rows="3" maxlength="1000"></textarea>
                            <div class="form-text">Maximum 1000 characters</div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="event_date" class="form-label">Date *</label>
                                    <input type="date" class="form-control" id="event_date" name="event_date" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="event_time" class="form-label">Time</label>
                                    <input type="time" class="form-control" id="event_time" name="event_time">
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="event_type" class="form-label">Event Type</label>
                            <select class="form-select" id="event_type" name="event_type">
                                <option value="event">General Event</option>
                                <option value="task">Task Deadline</option>
                                <option value="meeting">Meeting</option>
                                <option value="reminder">Reminder</option>
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
                    <button type="button" class="btn btn-danger" id="deleteEventBtn" style="display: none;">
                        <i class="fas fa-trash me-1"></i>Delete Event
                    </button>
                </div>
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
                var content = `
                    <h6><strong>${event.title}</strong></h6>
                    <p><strong>Date:</strong> ${event.start.toLocaleDateString()}</p>
                    ${event.start.toTimeString() !== '00:00:00' ? `<p><strong>Time:</strong> ${event.start.toLocaleTimeString()}</p>` : ''}
                    ${event.extendedProps.description ? `<p><strong>Description:</strong> ${event.extendedProps.description}</p>` : ''}
                    ${event.extendedProps.priority ? `<p><strong>Priority:</strong> <span class="badge bg-${getPriorityColor(event.extendedProps.priority)}">${event.extendedProps.priority}</span></p>` : ''}
                    ${event.extendedProps.list_name ? `<p><strong>List:</strong> ${event.extendedProps.list_name}</p>` : ''}
                    ${event.extendedProps.event_type ? `<p><strong>Type:</strong> ${event.extendedProps.event_type}</p>` : ''}
                `;

                document.getElementById('eventDetailsContent').innerHTML = content;

                // Show delete button and set up delete functionality
                var deleteBtn = document.getElementById('deleteEventBtn');
                deleteBtn.style.display = 'inline-block';
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
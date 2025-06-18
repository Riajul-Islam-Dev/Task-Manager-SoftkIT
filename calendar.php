<?php
require_once('config/constants.php');

// Create connection
$conn = new mysqli(LOCALHOST, DB_USERNAME, DB_PASSWORD, DB_NAME);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle AJAX requests for calendar events
if (isset($_GET['action'])) {
    header('Content-Type: application/json');
    
    if ($_GET['action'] == 'get_events') {
        $start = $_GET['start'] ?? '';
        $end = $_GET['end'] ?? '';
        
        $sql = "SELECT ce.*, t.task_name, t.task_description, t.priority, l.list_name 
                FROM tbl_calendar_events ce 
                LEFT JOIN tbl_tasks t ON ce.task_id = t.task_id 
                LEFT JOIN tbl_lists l ON t.list_id = l.list_id 
                WHERE ce.event_date BETWEEN ? AND ?";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ss', $start, $end);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $events = [];
        while ($row = $result->fetch_assoc()) {
            $color = '#007bff'; // Default blue
            if ($row['priority'] == 'High') $color = '#dc3545'; // Red
            elseif ($row['priority'] == 'Medium') $color = '#ffc107'; // Yellow
            elseif ($row['priority'] == 'Low') $color = '#28a745'; // Green
            
            $events[] = [
                'id' => $row['event_id'],
                'title' => $row['task_name'] ?: $row['event_title'],
                'start' => $row['event_date'] . 'T' . $row['event_time'],
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
    }
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        if (isset($_POST['add_event'])) {
            $event_title = trim($_POST['event_title']);
            $event_description = trim($_POST['event_description']);
            $event_date = $_POST['event_date'];
            $event_time = $_POST['event_time'];
            $event_type = $_POST['event_type'];
            $task_id = !empty($_POST['task_id']) ? $_POST['task_id'] : null;
            
            if (empty($event_title) || empty($event_date)) {
                $_SESSION['error'] = 'Event title and date are required.';
            } else {
                $sql = "INSERT INTO tbl_calendar_events (event_title, event_description, event_date, event_time, event_type, task_id) VALUES (?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param('sssssi', $event_title, $event_description, $event_date, $event_time, $event_type, $task_id);
                
                if ($stmt->execute()) {
                    $_SESSION['success'] = 'Calendar event added successfully!';
                } else {
                    $_SESSION['error'] = 'Failed to add calendar event.';
                }
            }
            
            header('Location: calendar.php');
            exit;
        }
        
        if (isset($_POST['delete_event'])) {
            $event_id = $_POST['event_id'];
            
            $sql = "DELETE FROM tbl_calendar_events WHERE event_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('i', $event_id);
            
            if ($stmt->execute()) {
                $_SESSION['success'] = 'Event deleted successfully!';
            } else {
                $_SESSION['error'] = 'Failed to delete event.';
            }
            
            header('Location: calendar.php');
            exit;
        }
    } catch (Exception $e) {
        $_SESSION['error'] = 'An error occurred: ' . $e->getMessage();
        header('Location: calendar.php');
        exit;
    }
}

// Get all tasks for dropdown
$tasks_sql = "SELECT t.task_id, t.task_name, l.list_name FROM tbl_tasks t LEFT JOIN tbl_lists l ON t.list_id = l.list_id ORDER BY t.task_name";
$tasks_result = $conn->query($tasks_sql);

// Get all lists for navigation
$lists_sql = "SELECT * FROM tbl_lists ORDER BY list_name";
$lists_result = $conn->query($lists_sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Calendar - Task Manager</title>
    <link rel="icon" type="image/png" href="assets/img/favicon.png">
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- FullCalendar CSS -->
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.css" rel="stylesheet">
    <!-- SweetAlert2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11.10.1/dist/sweetalert2.min.css" rel="stylesheet">
    
    <style>
        .navbar-brand {
            font-weight: 600;
        }
        .calendar-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            padding: 20px;
        }
        .fc-event {
            cursor: pointer;
        }
        .priority-high { border-left: 4px solid #dc3545 !important; }
        .priority-medium { border-left: 4px solid #ffc107 !important; }
        .priority-low { border-left: 4px solid #28a745 !important; }
    </style>
</head>
<body class="bg-light">
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
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
                    <?php if ($lists_result && $lists_result->num_rows > 0): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                                <i class="fas fa-filter me-1"></i>Filter by List
                            </a>
                            <ul class="dropdown-menu">
                                <?php while ($list = $lists_result->fetch_assoc()): ?>
                                    <li><a class="dropdown-item" href="list-task.php?list_id=<?= $list['list_id'] ?>"><?= htmlspecialchars($list['list_name']) ?></a></li>
                                <?php endwhile; ?>
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
                <h2><i class="fas fa-calendar text-primary me-2"></i>Task Calendar</h2>
                <p class="text-muted">Schedule and view your tasks on the calendar</p>
            </div>
            <div class="col-md-4 text-end">
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addEventModal">
                    <i class="fas fa-plus me-1"></i>Add Event
                </button>
            </div>
        </div>

        <!-- Session Messages -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i><?= $_SESSION['success'] ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i><?= $_SESSION['error'] ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

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
                <form method="POST">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="event_title" class="form-label">Event Title *</label>
                            <input type="text" class="form-control" id="event_title" name="event_title" required>
                        </div>
                        <div class="mb-3">
                            <label for="event_description" class="form-label">Description</label>
                            <textarea class="form-control" id="event_description" name="event_description" rows="3"></textarea>
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
                                <?php if ($tasks_result && $tasks_result->num_rows > 0): ?>
                                    <?php while ($task = $tasks_result->fetch_assoc()): ?>
                                        <option value="<?= $task['task_id'] ?>">
                                            <?= htmlspecialchars($task['task_name']) ?> 
                                            <?php if ($task['list_name']): ?>
                                                (<?= htmlspecialchars($task['list_name']) ?>)
                                            <?php endif; ?>
                                        </option>
                                    <?php endwhile; ?>
                                <?php endif; ?>
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
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- FullCalendar JS -->
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js"></script>
    <!-- SweetAlert2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.10.1/dist/sweetalert2.all.min.js"></script>
    
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
                switch(priority) {
                    case 'High': return 'danger';
                    case 'Medium': return 'warning';
                    case 'Low': return 'success';
                    default: return 'primary';
                }
            }
            
            function deleteEvent(eventId) {
                var form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
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
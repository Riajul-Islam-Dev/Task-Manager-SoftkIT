<?php

declare(strict_types=1);

// Include modern configuration and classes
require_once 'config/constants.php';
require_once 'config/Database.php';
require_once 'config/Session.php';
require_once 'config/Enums.php';

// Start session to handle flash messages
Session::start();

/**
 * Validate and sanitize input data
 */
function validateTaskInput(array $data): array
{
    $errors = [];
    $cleaned = [];
    
    // Validate task name
    $taskName = trim($data['task_name'] ?? '');
    if (empty($taskName)) {
        $errors[] = 'Task name is required';
    } elseif (strlen($taskName) > 255) {
        $errors[] = 'Task name must be less than 255 characters';
    } else {
        $cleaned['task_name'] = $taskName;
    }
    
    // Validate task description
    $taskDescription = trim($data['task_description'] ?? '');
    if (strlen($taskDescription) > 1000) {
        $errors[] = 'Task description must be less than 1000 characters';
    }
    $cleaned['task_description'] = $taskDescription;
    
    // Validate list ID
    $listId = filter_var($data['list_id'] ?? 0, FILTER_VALIDATE_INT);
    if ($listId === false || $listId <= 0) {
        $errors[] = 'Please select a valid list';
    } else {
        $cleaned['list_id'] = $listId;
    }
    
    // Validate priority
    try {
        $priority = Priority::fromString($data['priority'] ?? '');
        $cleaned['priority'] = $priority->value;
    } catch (InvalidArgumentException) {
        $errors[] = 'Please select a valid priority';
    }
    
    // Validate deadline
    $deadline = $data['deadline'] ?? '';
    if (empty($deadline)) {
        $errors[] = 'Deadline is required';
    } else {
        $deadlineTime = strtotime($deadline);
        if ($deadlineTime === false) {
            $errors[] = 'Please enter a valid deadline date';
        } elseif ($deadlineTime < strtotime('today')) {
            $errors[] = 'Deadline cannot be in the past';
        } else {
            $cleaned['deadline'] = date('Y-m-d', $deadlineTime);
        }
    }
    
    return ['errors' => $errors, 'data' => $cleaned];
}

// Check whether the submit button is clicked
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit'])) {
    try {
        // Verify CSRF token
        if (!Session::verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            throw new Exception('Invalid security token. Please try again.');
        }
        
        // Validate input
        $validation = validateTaskInput($_POST);
        
        if (!empty($validation['errors'])) {
            Session::setError('Please fix the following errors: ' . implode(', ', $validation['errors']));
        } else {
            $data = $validation['data'];
            
            // Check if list exists
            $listExists = Database::fetchOne(
                "SELECT list_id FROM tbl_lists WHERE list_id = ?",
                [$data['list_id']]
            );
            
            if (!$listExists) {
                throw new Exception('Selected list does not exist.');
            }
            
            // Insert task into database
            Database::execute(
                "INSERT INTO tbl_tasks (task_name, task_description, list_id, priority, deadline) 
                 VALUES (?, ?, ?, ?, ?)",
                [
                    $data['task_name'],
                    $data['task_description'],
                    $data['list_id'],
                    $data['priority'],
                    $data['deadline']
                ]
            );
            
            Session::setSuccess('Task "' . $data['task_name'] . '" added successfully!');
            header('Location: ' . SITEURL . 'index.php');
            exit;
        }
    } catch (Exception $e) {
        error_log('Error adding task: ' . $e->getMessage());
        Session::setError('Failed to add task: ' . $e->getMessage());
    }
    
    // Redirect back to form on error
    header('Location: ' . SITEURL . 'add-task.php');
    exit;
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Task - Task Manager - SoftkIT</title>

    <link href="assets/img/favicon.png" rel="icon">
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="css/all.min.css" rel="stylesheet">
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
    <nav class="navbar navbar-expand-lg navbar-dark mb-4" style="background-color: #21262d; border-bottom: 1px solid #30363d;">
        <div class="container">
            <a class="navbar-brand" href="<?php echo SITEURL; ?>">
                <i class="fas fa-tasks me-2"></i>Task Manager - SoftkIT
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="<?php echo SITEURL; ?>">
                    <i class="fas fa-home me-1"></i>Home
                </a>
                <a class="nav-link" href="manage-list.php">
                    <i class="fas fa-list me-1"></i>Manage Lists
                </a>
                <a class="nav-link" href="calendar.php">
                    <i class="fas fa-calendar me-1"></i>Calendar
                </a>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title mb-0 fw-semibold">
                            <i class="fas fa-plus-circle me-2"></i>Add New Task
                        </h4>
                    </div>
                    <div class="card-body">

                        <?php
                        // Display flash messages using modern Session class
                        if (Session::hasFlashMessages()) {
                            $messages = Session::getFlashMessages();
                            foreach ($messages as $message) {
                                $alertClass = $message['type']->getAlertClass();
                                $iconClass = $message['type']->getIconClass();
                                $messageText = htmlspecialchars($message['message'], ENT_QUOTES, 'UTF-8');
                                
                                echo "<div class='alert {$alertClass} alert-dismissible fade show' role='alert'>";
                                echo "<i class='{$iconClass} me-2'></i>{$messageText}";
                                echo '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
                                echo '</div>';
                            }
                        }
                        ?>

                        <form method="POST" action="" class="needs-validation" novalidate>
                            <input type="hidden" name="csrf_token" value="<?php echo Session::getCsrfToken(); ?>">
                            <div class="mb-3">
                                <label for="task_name" class="form-label">
                                    <i class="fas fa-tasks me-1"></i>Task Name *
                                </label>
                                <input type="text" name="task_name" id="task_name" class="form-control"
                                    placeholder="Enter task name" required>
                                <div class="invalid-feedback">
                                    Please provide a task name.
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="task_description" class="form-label">
                                    <i class="fas fa-align-left me-1"></i>Task Description
                                </label>
                                <textarea name="task_description" id="task_description" class="form-control" rows="3"
                                    placeholder="Enter task description (optional)"></textarea>
                            </div>

                            <div class="mb-3">
                                <label for="list_id" class="form-label">
                                    <i class="fas fa-list me-1"></i>Select List
                                </label>
                                <select name="list_id" id="list_id" class="form-select">
                                    <option value="0">No specific list</option>
                                    <?php
                                        try {
                                            // Query to get all lists using modern Database class
                                            $lists = Database::fetchAll(
                                                "SELECT list_id, list_name FROM tbl_lists ORDER BY list_name ASC"
                                            );
                                            
                                            foreach ($lists as $list) {
                                                $listId = (int) $list['list_id'];
                                                $listName = htmlspecialchars($list['list_name'], ENT_QUOTES, 'UTF-8');
                                                $selected = (isset($_POST['list_id']) && (int)$_POST['list_id'] === $listId) ? 'selected' : '';
                                                echo "<option value='{$listId}' {$selected}>{$listName}</option>";
                                            }
                                        } catch (Exception $e) {
                                            error_log('Error fetching lists: ' . $e->getMessage());
                                            echo '<option value="" disabled>Error loading lists</option>';
                                        }
                                    ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="priority" class="form-label">
                                    <i class="fas fa-exclamation-triangle me-1"></i>Priority *
                                </label>
                                <select name="priority" id="priority" class="form-select" required>
                                    <option value="">Select priority</option>
                                    <?php
                                        foreach (Priority::cases() as $priority) {
                                            $selected = (isset($_POST['priority']) && $_POST['priority'] === $priority->value) ? 'selected' : '';
                                            echo "<option value='{$priority->value}' {$selected}>{$priority->getIcon()} {$priority->value}</option>";
                                        }
                                    ?>
                                </select>
                                <div class="invalid-feedback">
                                    Please select a priority level.
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="deadline" class="form-label">
                                    <i class="fas fa-calendar-alt me-1"></i>Deadline *
                                </label>
                                <input type="date" name="deadline" id="deadline" class="form-control"
                                    min="<?php echo date('Y-m-d'); ?>" required>
                                <div class="invalid-feedback">
                                    Please select a deadline.
                                </div>
                            </div>

                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <a href="<?php echo SITEURL; ?>" class="btn btn-outline-secondary me-md-2">
                                    <i class="fas fa-times me-1"></i>Cancel
                                </a>
                                <button type="submit" name="submit" class="btn btn-primary">
                                    <i class="fas fa-plus me-1"></i>Add Task
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="js/bootstrap.bundle.min.js"></script>
    <script>
        // Bootstrap form validation
        (function() {
            'use strict';
            window.addEventListener('load', function() {
                var forms = document.getElementsByClassName('needs-validation');
                var validation = Array.prototype.filter.call(forms, function(form) {
                    form.addEventListener('submit', function(event) {
                        if (form.checkValidity() === false) {
                            event.preventDefault();
                            event.stopPropagation();
                        }
                        form.classList.add('was-validated');
                    }, false);
                });
            }, false);
        })();
    </script>
</body>

</html>
<?php

declare(strict_types=1);

// Include modern configuration and classes
require_once 'config/constants.php';
require_once 'config/Database.php';
require_once 'config/Session.php';
require_once 'config/Enums.php';

/**
 * Validate and sanitize task input data
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
    } elseif (preg_match('/[<>"\'\/]/', $taskName)) {
        $errors[] = 'Task name contains invalid characters';
    } else {
        $cleaned['task_name'] = htmlspecialchars($taskName, ENT_QUOTES, 'UTF-8');
    }
    
    // Validate task description (optional)
    $taskDescription = trim($data['task_description'] ?? '');
    if (strlen($taskDescription) > 1000) {
        $errors[] = 'Task description must be less than 1000 characters';
    } else {
        $cleaned['task_description'] = htmlspecialchars($taskDescription, ENT_QUOTES, 'UTF-8');
    }
    
    // Validate list ID
    $listId = (int) ($data['list_id'] ?? 0);
    if ($listId <= 0) {
        $errors[] = 'Please select a valid list';
    } else {
        $cleaned['list_id'] = $listId;
    }
    
    // Validate priority
    $priority = trim($data['priority'] ?? '');
    try {
        $priorityEnum = Priority::fromString($priority);
        $cleaned['priority'] = $priorityEnum->value;
    } catch (InvalidArgumentException $e) {
        $errors[] = 'Please select a valid priority';
    }
    
    // Validate deadline (optional)
    $deadline = trim($data['deadline'] ?? '');
    if (!empty($deadline)) {
        $deadlineDate = DateTime::createFromFormat('Y-m-d', $deadline);
        if (!$deadlineDate || $deadlineDate->format('Y-m-d') !== $deadline) {
            $errors[] = 'Please enter a valid deadline date';
        } elseif ($deadlineDate < new DateTime('today')) {
            $errors[] = 'Deadline cannot be in the past';
        } else {
            $cleaned['deadline'] = $deadline;
        }
    } else {
        $cleaned['deadline'] = null;
    }
    
    return ['errors' => $errors, 'data' => $cleaned];
}

// Start session
Session::start();

// Initialize variables
$taskId = 0;
$taskName = '';
$taskDescription = '';
$listId = 0;
$priority = Priority::MEDIUM->value;
$deadline = '';
$taskData = null;
$lists = [];

// Check the Task ID in URL
if (isset($_GET['task_id']) && is_numeric($_GET['task_id'])) {
    $taskId = (int) $_GET['task_id'];
    
    try {
        // Get the task details from database
        $taskData = Database::fetchOne(
            "SELECT task_id, task_name, task_description, list_id, priority, deadline FROM tbl_tasks WHERE task_id = ?",
            [$taskId]
        );
        
        if ($taskData) {
            $taskName = $taskData['task_name'];
            $taskDescription = $taskData['task_description'] ?? '';
            $listId = (int) $taskData['list_id'];
            $priority = $taskData['priority'];
            $deadline = $taskData['deadline'] ?? '';
        } else {
            Session::setError('Task not found.');
            header('Location: ' . SITEURL);
            exit;
        }
        
        // Get all available lists for the dropdown
        $lists = Database::fetchAll("SELECT list_id, list_name FROM tbl_lists ORDER BY list_name ASC");
        
    } catch (Exception $e) {
        error_log('Error fetching task data: ' . $e->getMessage());
        Session::setError('Error loading task data.');
        header('Location: ' . SITEURL);
        exit;
    }
} else {
    Session::setError('Invalid task ID.');
    header('Location: ' . SITEURL);
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit'])) {
    try {
        // Verify CSRF token
        if (!Session::verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            throw new InvalidArgumentException('Invalid security token. Please try again.');
        }
        
        // Validate input
        $validation = validateTaskInput($_POST);
        
        if (!empty($validation['errors'])) {
            Session::setError(implode('. ', $validation['errors']));
        } else {
            $data = $validation['data'];
            
            // Verify that the selected list exists
            $listExists = Database::fetchOne(
                "SELECT list_id FROM tbl_lists WHERE list_id = ?",
                [$data['list_id']]
            );
            
            if (!$listExists) {
                Session::setError('Selected list does not exist.');
            } else {
                // Update the task
                $sql = "UPDATE tbl_tasks SET task_name = ?, task_description = ?, list_id = ?, priority = ?, deadline = ? WHERE task_id = ?";
                $params = [
                    $data['task_name'],
                    $data['task_description'],
                    $data['list_id'],
                    $data['priority'],
                    $data['deadline'],
                    $taskId
                ];
                
                if (Database::execute($sql, $params)) {
                    Session::setSuccess('Task updated successfully!');
                    header('Location: ' . SITEURL);
                    exit;
                } else {
                    Session::setError('Failed to update task. Please try again.');
                }
            }
        }
    } catch (Exception $e) {
        error_log('Error updating task: ' . $e->getMessage());
        Session::setError('An error occurred while updating the task. Please try again.');
    }
    
    // Redirect to prevent form resubmission
    header('Location: ' . SITEURL . 'update-task.php?task_id=' . $taskId);
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Task - Task Manager - SoftkIT</title>

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
                            <i class="fas fa-edit me-2"></i>Update Task
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
                                    placeholder="Enter task name" value="<?php echo htmlspecialchars($taskName, ENT_QUOTES, 'UTF-8'); ?>" required>
                                <div class="invalid-feedback">
                                    Please provide a task name.
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="task_description" class="form-label">
                                    <i class="fas fa-align-left me-1"></i>Task Description
                                </label>
                                <textarea name="task_description" id="task_description" class="form-control" rows="3"
                                    placeholder="Enter task description (optional)"><?php echo htmlspecialchars($taskDescription, ENT_QUOTES, 'UTF-8'); ?></textarea>
                            </div>

                            <div class="mb-3">
                                <label for="list_id" class="form-label">
                                    <i class="fas fa-list me-1"></i>Select List
                                </label>
                                <select name="list_id" id="list_id" class="form-select">
                                    <option value="0">No specific list</option>
                                    <?php if (!empty($lists)): ?>
                                        <?php foreach ($lists as $list): ?>
                                            <option value="<?php echo $list['list_id']; ?>" 
                                                    <?php echo ($list['list_id'] == $listId) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($list['list_name'], ENT_QUOTES, 'UTF-8'); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <option value="" disabled>No lists available</option>
                                    <?php endif; ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="priority" class="form-label">
                                    <i class="fas fa-exclamation-triangle me-1"></i>Priority *
                                </label>
                                <select name="priority" id="priority" class="form-select" required>
                                    <option value="">Select priority</option>
                                    <?php foreach (Priority::cases() as $priorityCase): ?>
                                        <option value="<?php echo $priorityCase->value; ?>" 
                                                <?php echo ($priorityCase->value === $priority) ? 'selected' : ''; ?>>
                                            <?php echo $priorityCase->getIcon(); ?> <?php echo $priorityCase->value; ?>
                                        </option>
                                    <?php endforeach; ?>
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
                                    min="<?php echo date('Y-m-d'); ?>" value="<?php echo htmlspecialchars($deadline, ENT_QUOTES, 'UTF-8'); ?>" required>
                                <div class="invalid-feedback">
                                    Please select a deadline.
                                </div>
                            </div>



                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <a href="<?php echo SITEURL; ?>" class="btn btn-outline-secondary me-md-2">
                                    <i class="fas fa-times me-1"></i>Cancel
                                </a>
                                <button type="submit" name="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-1"></i>Update Task
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
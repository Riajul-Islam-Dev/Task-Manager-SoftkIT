<?php

declare(strict_types=1);

// Include modern configuration and classes
require_once 'config/constants.php';
require_once 'config/Database.php';
require_once 'config/Session.php';
require_once 'config/Enums.php';

// Start session to handle flash messages
Session::start();

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Task Manager - SoftkIT</title>

    <link href="assets/img/favicon.png" rel="icon">
    <link href="css/bootstrap.min.css" rel="stylesheet">
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

        .table-dark {
            background-color: #21262d;
            border-color: #30363d;
        }

        .table-hover tbody tr:hover {
            background-color: #262c36;
        }

        .priority-high {
            color: #f85149;
            font-weight: 600;
        }

        .priority-medium {
            color: #d29922;
            font-weight: 600;
        }

        .priority-low {
            color: #3fb950;
            font-weight: 600;
        }

        .btn-primary {
            background-color: #238636;
            border-color: #238636;
        }

        .btn-primary:hover {
            background-color: #2ea043;
            border-color: #2ea043;
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
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="<?php echo SITEURL; ?>">Home</a>
                    </li>


                    <?php
                    try {
                        // Query to get all lists using modern Database class
                        $lists = Database::fetchAll(
                            "SELECT list_id, list_name FROM tbl_lists ORDER BY list_id ASC"
                        );
                        
                        if (!empty($lists)) {
                            foreach ($lists as $list) {
                                $listId = (int) $list['list_id'];
                                $listName = htmlspecialchars($list['list_name'], ENT_QUOTES, 'UTF-8');
                                echo "<li class='nav-item'>";
                                echo "<a class='nav-link' href='" . SITEURL . "list-task.php?list_id={$listId}'>{$listName}</a>";
                                echo "</li>";
                            }
                        }
                    } catch (Exception $e) {
                        error_log("Error fetching lists: " . $e->getMessage());
                    }
                    ?>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo SITEURL; ?>manage-list.php">
                            <i class="fas fa-cog me-1"></i>Manage Lists
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="calendar.php">
                            <i class="fas fa-calendar me-1"></i>Calendar
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="manage-event-types.php">
                            <i class="fas fa-tags me-1"></i>Event Types
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container">
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

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0 fw-semibold">
                            <i class="fas fa-list me-2"></i>All Tasks
                        </h5>
                        <a href="<?php echo SITEURL; ?>add-task.php" class="btn btn-primary btn-sm">
                            <i class="fas fa-plus me-1"></i>Add Task
                        </a>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover table-dark mb-0">

                                <thead class="table-dark">
                                    <tr>
                                        <th scope="col">#</th>
                                        <th scope="col">Task Name</th>
                                        <th scope="col">Priority</th>
                                        <th scope="col">Deadline</th>
                                        <th scope="col">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    try {
                                        // Query to get all tasks using modern Database class
                                        $sql = "
                                            SELECT task_id, task_name, task_description, priority, deadline 
                                            FROM tbl_tasks 
                                            ORDER BY 
                                                CASE priority 
                                                    WHEN 'High' THEN 1 
                                                    WHEN 'Medium' THEN 2 
                                                    WHEN 'Low' THEN 3 
                                                END, deadline ASC
                                        ";
                                        
                                        $tasks = Database::fetchAll($sql);
                                        
                                        if (!empty($tasks)) {
                                            $sn = 1;
                                            foreach ($tasks as $task) {
                                                $taskId = (int) $task['task_id'];
                                                $taskName = htmlspecialchars($task['task_name'], ENT_QUOTES, 'UTF-8');
                                                $taskDescription = htmlspecialchars($task['task_description'] ?? '', ENT_QUOTES, 'UTF-8');
                                                $deadline = htmlspecialchars($task['deadline'], ENT_QUOTES, 'UTF-8');
                                                $deadlineFormatted = date('M d, Y', strtotime($deadline));
                                                
                                                // Use Priority enum for better type safety
                                                try {
                                                    $priority = Priority::fromString($task['priority']);
                                                    $priorityBadge = "<span class='badge {$priority->getBadgeClass()}'>{$priority->value}</span>";
                                                } catch (InvalidArgumentException) {
                                                    $priorityBadge = "<span class='badge bg-secondary'>{$task['priority']}</span>";
                                                }
                                    ?>

                                                <tr>
                                                    <td><?php echo $sn++; ?></td>
                                                    <td>
                                                        <strong><?php echo $taskName; ?></strong>
                                                        <?php if (!empty($taskDescription)): ?>
                                                            <br><small><?php echo $taskDescription; ?></small>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td><?php echo $priorityBadge; ?></td>
                                                    <td><?php echo $deadlineFormatted; ?></td>
                                                    <td>
                                                        <div class="btn-group" role="group">
                                                            <a href="<?php echo SITEURL; ?>update-task.php?task_id=<?php echo $taskId; ?>"
                                                                class="btn btn-outline-primary btn-sm" title="Edit Task">
                                                                <i class="fas fa-edit"></i>
                                                            </a>
                                                            <a href="#"
                                                                class="btn btn-outline-danger btn-sm" title="Delete Task"
                                                                onclick="confirmDelete(<?php echo $taskId; ?>)">
                                                                <i class="fas fa-trash"></i>
                                                            </a>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php
                                            }
                                        } else {
                                            ?>
                                            <tr>
                                                <td colspan="5" class="text-center py-4">
                                                    <i class="fas fa-inbox fa-3x mb-3"></i>
                                                    <p>No tasks added yet. <a href="<?php echo SITEURL; ?>add-task.php">Add your first task</a>!</p>
                                                </td>
                                            </tr>
                                    <?php
                                        }
                                    } catch (Exception $e) {
                                        error_log("Error fetching tasks: " . $e->getMessage());
                                        echo '<tr><td colspan="5" class="text-center text-danger">Error loading tasks. Please try again.</td></tr>';
                                    }
                                    ?>



                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="js/bootstrap.bundle.min.js"></script>
    <!-- SweetAlert2 JS -->
    <script src="js/sweetalert2.all.min.js"></script>

    <script>
        function confirmDelete(taskId) {
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
                    window.location.href = '<?php echo SITEURL; ?>delete-task.php?task_id=' + taskId;
                }
            });
        }
    </script>
</body>

</html>
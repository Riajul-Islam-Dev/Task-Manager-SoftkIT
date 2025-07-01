<?php

declare(strict_types=1);

// Include modern configuration and classes
require_once 'config/constants.php';
require_once 'config/Database.php';
require_once 'config/Session.php';
require_once 'config/Enums.php';

// Start session
Session::start();

// Get list ID from URL
$listId = isset($_GET['list_id']) && is_numeric($_GET['list_id']) ? (int)$_GET['list_id'] : 0;
$listName = '';

// Get list name for display
if ($listId > 0) {
    try {
        $listData = Database::fetchOne(
            "SELECT list_name FROM tbl_lists WHERE list_id = ?",
            [$listId]
        );
        
        if ($listData) {
            $listName = htmlspecialchars($listData['list_name']);
        }
    } catch (Exception $e) {
        error_log('Error fetching list: ' . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $list_name ? $list_name . ' - ' : ''; ?>Tasks - Task Manager - SoftkIT</title>

    <link href="assets/img/favicon.png" rel="icon">
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="css/all.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
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
                        <a class="nav-link" href="<?php echo SITEURL; ?>">Home</a>
                    </li>

                    <?php
                    // Display Lists From Database in Menu
                    try {
                        $lists = Database::fetchAll("SELECT * FROM tbl_lists ORDER BY list_id ASC");
                        
                        foreach ($lists as $list) {
                            $navListId = (int)$list['list_id'];
                            $navListName = htmlspecialchars($list['list_name']);
                            $activeClass = ($navListId === $listId) ? ' active' : '';
                            echo '<li class="nav-item">';
                            echo '<a class="nav-link' . $activeClass . '" href="' . SITEURL . 'list-task.php?list_id=' . $navListId . '">' . $navListName . '</a>';
                            echo '</li>';
                        }
                    } catch (Exception $e) {
                        error_log('Error fetching navigation lists: ' . $e->getMessage());
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
                </ul>
            </div>
        </div>
    </nav>

    <div class="container">
        <!-- Session Messages -->
        <?php
        $flashMessages = Session::getFlashMessages();
        foreach ($flashMessages as $message):
            $alertClass = match($message['type']) {
                AlertType::SUCCESS => 'alert-success',
                AlertType::ERROR => 'alert-danger',
                AlertType::WARNING => 'alert-warning',
                AlertType::INFO => 'alert-info'
            };
            $icon = match($message['type']) {
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

        <?php if ($listName): ?>
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="fw-semibold"><i class="fas fa-list-ul me-2"></i><?php echo $listName; ?> Tasks</h2>
                <a href="<?php echo SITEURL; ?>add-task.php?list_id=<?= $listId ?>" class="btn btn-primary btn-sm">
                    <i class="fas fa-plus me-1"></i>Add Task
                </a>
            </div>
        <?php else: ?>
            <div class="alert alert-warning" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i>Invalid list selected. <a href="<?php echo SITEURL; ?>">Go back to home</a>.
            </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover table-dark mb-0">

                                <thead class="table-dark">
                                    <tr>
                                        <th scope="col">#</th>
                                        <th scope="col">Task</th>
                                        <th scope="col">Priority</th>
                                        <th scope="col">Deadline</th>
                                        <th scope="col">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    if ($listId > 0) {
                                        try {
                                            $tasks = Database::fetchAll(
                                                "SELECT * FROM tbl_tasks WHERE list_id = ? ORDER BY 
                                                 CASE priority 
                                                     WHEN 'High' THEN 1 
                                                     WHEN 'Medium' THEN 2 
                                                     WHEN 'Low' THEN 3 
                                                 END, deadline ASC",
                                                [$listId]
                                            );

                                            if (!empty($tasks)) {
                                                $sn = 1;
                                                foreach ($tasks as $row) {
                                                    $taskId = (int)$row['task_id'];
                                                    $taskName = htmlspecialchars($row['task_name']);
                                                    $taskDescription = htmlspecialchars($row['task_description'] ?? '');
                                                    $priority = htmlspecialchars($row['priority']);
                                                    $deadline = $row['deadline'];

                                                    // Format deadline using modern approach
                                                    [$formattedDeadline, $deadlineClass] = match (true) {
                                                        empty($deadline) || $deadline === '0000-00-00' => ['<small>No deadline</small>', ''],
                                                        default => (function() use ($deadline) {
                                                            $deadlineDate = new DateTime($deadline);
                                                            $today = new DateTime();
                                                            $diff = $today->diff($deadlineDate);

                                                            return match (true) {
                                                                $deadlineDate < $today => [
                                                                    $deadlineDate->format('M j, Y') . ' <small>(Overdue)</small>',
                                                                    'text-danger'
                                                                ],
                                                                $diff->days <= 3 => [
                                                                    $deadlineDate->format('M j, Y') . ' <small>(Due soon)</small>',
                                                                    'text-warning'
                                                                ],
                                                                default => [$deadlineDate->format('M j, Y'), '']
                                                            };
                                                        })()
                                                    };

                                                    // Priority styling using match expression
                                                    [$priorityClass, $priorityIcon] = match (strtolower($priority)) {
                                                        'high' => ['bg-danger', 'fas fa-exclamation-circle'],
                                                        'medium' => ['bg-warning', 'fas fa-minus-circle'],
                                                        'low' => ['bg-success', 'fas fa-check-circle'],
                                                        default => ['bg-secondary', 'fas fa-circle']
                                                    };
                                    ?>

                                                    <tr class="priority-<?= strtolower($priority) ?>">
                                                        <td><?= $sn++ ?></td>
                                                        <td>
                                                            <div>
                                                                <strong><?= $taskName ?></strong>
                                                                <?php if ($taskDescription): ?>
                                                                    <br><small><?= $taskDescription ?></small>
                                                                <?php endif; ?>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <span class="badge <?= $priorityClass ?>">
                                                                <i class="<?= $priorityIcon ?> me-1"></i><?= $priority ?>
                                                            </span>
                                                        </td>
                                                        <td class="<?= $deadlineClass ?>">
                                                            <?= $formattedDeadline ?>
                                                        </td>
                                                        <td>
                                                            <div class="btn-group" role="group">
                                                                <a href="<?= SITEURL ?>update-task.php?task_id=<?= $taskId ?>"
                                                                    class="btn btn-outline-primary btn-sm" title="Edit Task">
                                                                    <i class="fas fa-edit"></i>
                                                                </a>
                                                                <a href="#"
                                                                    class="btn btn-outline-danger btn-sm" title="Delete Task"
                                                                    onclick="confirmDelete(<?= $taskId ?>)">
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
                                                        <i class="fas fa-tasks fa-3x mb-3"></i>
                                                        <p>No tasks in this list yet. <a href="<?= SITEURL ?>add-task.php?list_id=<?= $listId ?>">Add your first task</a>!</p>
                                                    </td>
                                                </tr>
                                    <?php
                                            }
                                        } catch (Exception $e) {
                                            error_log('Error loading tasks: ' . $e->getMessage());
                                            echo '<tr><td colspan="5" class="text-center text-danger">Error loading tasks. Please try again.</td></tr>';
                                        }
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
                    window.location.href = '<?= SITEURL ?>delete-task.php?task_id=' + taskId;
                }
            });
        }
    </script>
</body>

</html>
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
    <title>Manage Lists - Task Manager - SoftkIT</title>

    <link href="assets/img/favicon.png" rel="icon">
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="css/all.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
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
                        try {
                            // Query to get all lists using modern Database class
                            $lists = Database::fetchAll(
                                "SELECT list_id, list_name FROM tbl_lists ORDER BY list_name ASC"
                            );
                            
                            foreach ($lists as $list) {
                                $listId = (int) $list['list_id'];
                                $listName = htmlspecialchars($list['list_name'], ENT_QUOTES, 'UTF-8');
                                echo "<li class='nav-item'>";
                                echo "<a class='nav-link' href='" . SITEURL . "list-task.php?list_id={$listId}'>{$listName}</a>";
                                echo "</li>";
                            }
                        } catch (Exception $e) {
                            error_log("Error fetching lists for navigation: " . $e->getMessage());
                        }
                    ?>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link active" href="<?php echo SITEURL; ?>manage-list.php">
                            <i class="fas fa-cog me-1"></i>Manage Lists
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo SITEURL; ?>calendar.php">
                            <i class="fas fa-calendar me-1"></i>Calendar
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
                            <i class="fas fa-list-ul me-2"></i>Manage Lists
                        </h5>
                        <a href="<?php echo SITEURL; ?>add-list.php" class="btn btn-primary btn-sm">
                            <i class="fas fa-plus me-1"></i>Add List
                        </a>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover table-dark mb-0">
                                <thead class="table-dark">
                                    <tr>
                                        <th scope="col">#</th>
                                        <th scope="col">List Name</th>
                                        <th scope="col">Task Count</th>
                                        <th scope="col">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                        try {
                                            // Get lists with task count using modern Database class
                                            $sql = "
                                                SELECT l.list_id, l.list_name, COUNT(t.task_id) as task_count 
                                                FROM tbl_lists l 
                                                LEFT JOIN tbl_tasks t ON l.list_id = t.list_id 
                                                GROUP BY l.list_id, l.list_name 
                                                ORDER BY l.list_name ASC
                                            ";
                                            
                                            $lists = Database::fetchAll($sql);
                                            
                                            if (!empty($lists)) {
                                                $sn = 1;
                                                foreach ($lists as $list) {
                                                    $listId = (int) $list['list_id'];
                                                    $listName = htmlspecialchars($list['list_name'], ENT_QUOTES, 'UTF-8');
                                                    $taskCount = (int) $list['task_count'];

                                                    
                                                    $badgeClass = match (true) {
                                                        $taskCount === 0 => 'bg-secondary',
                                                        $taskCount <= 5 => 'bg-primary',
                                                        $taskCount <= 10 => 'bg-warning',
                                                        default => 'bg-danger'
                                                    };
                                                    
                                                    $taskText = $taskCount === 1 ? 'task' : 'tasks';
                                    ?>
                                                    <tr>
                                                        <td><?php echo $sn++; ?></td>
                                                        <td>
                                                            <div class="d-flex flex-column">
                                                                <strong class="mb-1"><?php echo $listName; ?></strong>

                                                                <small>
                                                                    <a href="<?php echo SITEURL; ?>list-task.php?list_id=<?php echo $listId; ?>" 
                                                                       class="text-decoration-none text-primary">
                                                                        <i class="fas fa-eye me-1"></i>View tasks in this list
                                                                    </a>
                                                                </small>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <span class="badge <?php echo $badgeClass; ?> fs-6">
                                                                <i class="fas fa-tasks me-1"></i><?php echo $taskCount; ?> <?php echo $taskText; ?>
                                                            </span>
                                                        </td>
                                                        <td>
                                                            <div class="btn-group" role="group" aria-label="List actions">
                                                                <a href="<?php echo SITEURL; ?>update-list.php?list_id=<?php echo $listId; ?>"
                                                                   class="btn btn-outline-primary btn-sm" 
                                                                   title="Edit List"
                                                                   data-bs-toggle="tooltip">
                                                                    <i class="fas fa-edit"></i>
                                                                </a>
                                                                <button type="button"
                                                                        class="btn btn-outline-danger btn-sm" 
                                                                        title="Delete List"
                                                                        data-bs-toggle="tooltip"
                                                                        onclick="confirmDeleteList(<?php echo $listId; ?>, '<?php echo addslashes($listName); ?>', <?php echo $taskCount; ?>)">
                                                                    <i class="fas fa-trash"></i>
                                                                </button>
                                                            </div>
                                                        </td>
                                                    </tr>
                                    <?php
                                                }
                                            } else {
                                    ?>
                                                <tr>
                                                    <td colspan="4" class="text-center py-5">
                                                        <div class="d-flex flex-column align-items-center">
                                                            <i class="fas fa-folder-open fa-4x mb-3"></i>
                    <h5 class="mb-2">No lists created yet</h5>
                    <p class="mb-3">Get started by creating your first task list!</p>
                                                            <a href="<?php echo SITEURL; ?>add-list.php" class="btn btn-primary">
                                                                <i class="fas fa-plus me-2"></i>Create your first list
                                                            </a>
                                                        </div>
                                                    </td>
                                                </tr>
                                    <?php
                                            }
                                        } catch (Exception $e) {
                                            error_log("Error loading lists: " . $e->getMessage());
                                            echo '<tr><td colspan="4" class="text-center text-danger py-4">';
                                            echo '<i class="fas fa-exclamation-triangle fa-2x mb-2"></i><br>';
                                            echo 'Error loading lists. Please refresh the page or try again later.';
                                            echo '</td></tr>';
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
        // Initialize Bootstrap tooltips
        document.addEventListener('DOMContentLoaded', function() {
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
        });
        
        function confirmDeleteList(listId, listName, taskCount) {
            const taskText = taskCount === 1 ? 'task' : 'tasks';
            const warningText = taskCount > 0 
                ? `This list contains ${taskCount} ${taskText} that will also be deleted.` 
                : 'This action cannot be undone.';
            
            Swal.fire({
                title: `Delete "${listName}"?`,
                html: `<p class="mb-2">Are you sure you want to delete this list?</p><p class="text-warning"><i class="fas fa-exclamation-triangle me-1"></i>${warningText}</p>`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: '<i class="fas fa-trash me-1"></i>Yes, delete it!',
                cancelButtonText: '<i class="fas fa-times me-1"></i>Cancel',
                focusCancel: true,
                customClass: {
                    confirmButton: 'btn btn-danger',
                    cancelButton: 'btn btn-secondary'
                },
                buttonsStyling: false
            }).then((result) => {
                if (result.isConfirmed) {
                    // Show loading state
                    Swal.fire({
                        title: 'Deleting...',
                        text: 'Please wait while we delete the list.',
                        allowOutsideClick: false,
                        allowEscapeKey: false,
                        showConfirmButton: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });
                    
                    // Redirect to delete script
                    window.location.href = `<?php echo SITEURL; ?>delete-list.php?list_id=${listId}`;
                }
            });
        }
    </script>
</body>

</html>
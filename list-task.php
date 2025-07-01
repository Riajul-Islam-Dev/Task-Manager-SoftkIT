<?php
require_once('config/constants.php');

// Get list ID from URL
$list_id = isset($_GET['list_id']) ? (int)$_GET['list_id'] : 0;
$list_name = '';

// Get list name for display
if ($list_id > 0) {
    try {
        $conn_list = new mysqli(LOCALHOST, DB_USERNAME, DB_PASSWORD, DB_NAME);
        if (!$conn_list->connect_error) {
            $stmt = $conn_list->prepare("SELECT list_name FROM tbl_lists WHERE list_id = ?");
            $stmt->bind_param("i", $list_id);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($row = $result->fetch_assoc()) {
                $list_name = htmlspecialchars($row['list_name']);
            }
            $stmt->close();
            $conn_list->close();
        }
    } catch (Exception $e) {
        error_log($e->getMessage());
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
                        $conn2 = new mysqli(LOCALHOST, DB_USERNAME, DB_PASSWORD, DB_NAME);
                        if ($conn2->connect_error) {
                            throw new Exception("Connection failed: " . $conn2->connect_error);
                        }

                        $sql2 = "SELECT * FROM tbl_lists ORDER BY list_id ASC";
                        $res2 = $conn2->query($sql2);

                        if ($res2 && $res2->num_rows > 0) {
                            while ($row2 = $res2->fetch_assoc()) {
                                $nav_list_id = htmlspecialchars($row2['list_id']);
                                $nav_list_name = htmlspecialchars($row2['list_name']);
                                $active_class = ($nav_list_id == $list_id) ? ' active' : '';
                                echo '<li class="nav-item">';
                                echo '<a class="nav-link' . $active_class . '" href="' . SITEURL . 'list-task.php?list_id=' . $nav_list_id . '">' . $nav_list_name . '</a>';
                                echo '</li>';
                            }
                        }
                        $conn2->close();
                    } catch (Exception $e) {
                        error_log($e->getMessage());
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
        <?php if ($list_name): ?>
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="fw-semibold"><i class="fas fa-list-ul me-2"></i><?php echo $list_name; ?> Tasks</h2>
                <a href="<?php echo SITEURL; ?>add-task.php" class="btn btn-primary btn-sm">
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
                                    if ($list_id > 0) {
                                        try {
                                            $conn = new mysqli(LOCALHOST, DB_USERNAME, DB_PASSWORD, DB_NAME);
                                            if ($conn->connect_error) {
                                                throw new Exception("Connection failed: " . $conn->connect_error);
                                            }

                                            $stmt = $conn->prepare("SELECT * FROM tbl_tasks WHERE list_id = ? ORDER BY 
                                                              CASE priority 
                                                                  WHEN 'High' THEN 1 
                                                                  WHEN 'Medium' THEN 2 
                                                                  WHEN 'Low' THEN 3 
                                                              END, deadline ASC");
                                            $stmt->bind_param("i", $list_id);
                                            $stmt->execute();
                                            $res = $stmt->get_result();

                                            if ($res && $res->num_rows > 0) {
                                                $sn = 1;
                                                while ($row = $res->fetch_assoc()) {
                                                    $task_id = htmlspecialchars($row['task_id']);
                                                    $task_name = htmlspecialchars($row['task_name']);
                                                    $task_description = htmlspecialchars($row['task_description']);
                                                    $priority = htmlspecialchars($row['priority']);
                                                    $deadline = $row['deadline'];

                                                    // Format deadline
                                                    $formatted_deadline = '';
                                                    $deadline_class = '';
                                                    if ($deadline && $deadline !== '0000-00-00') {
                                                        $deadline_date = new DateTime($deadline);
                                                        $today = new DateTime();
                                                        $diff = $today->diff($deadline_date);

                                                        if ($deadline_date < $today) {
                                                            $deadline_class = 'text-danger';
                                                            $formatted_deadline = $deadline_date->format('M j, Y') . ' <small>(Overdue)</small>';
                                                        } elseif ($diff->days <= 3) {
                                                            $deadline_class = 'text-warning';
                                                            $formatted_deadline = $deadline_date->format('M j, Y') . ' <small>(Due soon)</small>';
                                                        } else {
                                                            $formatted_deadline = $deadline_date->format('M j, Y');
                                                        }
                                                    } else {
                                                        $formatted_deadline = '<small>No deadline</small>';
                                                    }

                                                    // Priority styling
                                                    $priority_class = '';
                                                    $priority_icon = '';
                                                    switch (strtolower($priority)) {
                                                        case 'high':
                                                            $priority_class = 'bg-danger';
                                                            $priority_icon = 'fas fa-exclamation-circle';
                                                            break;
                                                        case 'medium':
                                                            $priority_class = 'bg-warning';
                                                            $priority_icon = 'fas fa-minus-circle';
                                                            break;
                                                        case 'low':
                                                            $priority_class = 'bg-success';
                                                            $priority_icon = 'fas fa-check-circle';
                                                            break;
                                                    }
                                    ?>

                                                    <tr class="priority-<?php echo strtolower($priority); ?>">
                                                        <td><?php echo $sn++; ?></td>
                                                        <td>
                                                            <div>
                                                                <strong><?php echo $task_name; ?></strong>
                                                                <?php if ($task_description): ?>
                                                                    <br><small><?php echo $task_description; ?></small>
                                                                <?php endif; ?>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <span class="badge <?php echo $priority_class; ?>">
                                                                <i class="<?php echo $priority_icon; ?> me-1"></i><?php echo $priority; ?>
                                                            </span>
                                                        </td>
                                                        <td class="<?php echo $deadline_class; ?>">
                                                            <?php echo $formatted_deadline; ?>
                                                        </td>
                                                        <td>
                                                            <div class="btn-group" role="group">
                                                                <a href="<?php echo SITEURL; ?>update-task.php?task_id=<?php echo $task_id; ?>"
                                                                    class="btn btn-outline-primary btn-sm" title="Edit Task">
                                                                    <i class="fas fa-edit"></i>
                                                                </a>
                                                                <a href="#"
                                                                    class="btn btn-outline-danger btn-sm" title="Delete Task"
                                                                    onclick="confirmDelete(<?php echo $task_id; ?>)">
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
                                                        <p>No tasks in this list yet. <a href="<?php echo SITEURL; ?>add-task.php">Add your first task</a>!</p>
                                                    </td>
                                                </tr>
                                    <?php
                                            }
                                            $stmt->close();
                                            $conn->close();
                                        } catch (Exception $e) {
                                            error_log($e->getMessage());
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
                    window.location.href = '<?php echo SITEURL; ?>delete-task.php?task_id=' + taskId;
                }
            });
        }
    </script>
</body>

</html>
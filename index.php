<?php
require_once('config/constants.php');
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Task Manager - SoftkIT</title>

    <link href="assets/img/favicon.png" rel="icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- SweetAlert2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11.10.1/dist/sweetalert2.min.css" rel="stylesheet">
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
                                $list_id = htmlspecialchars($row2['list_id']);
                                $list_name = htmlspecialchars($row2['list_name']);
                                echo '<li class="nav-item">';
                                echo '<a class="nav-link" href="' . SITEURL . 'list-task.php?list_id=' . $list_id . '">' . $list_name . '</a>';
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
        <?php
        // Display session messages with Bootstrap alerts
        $messages = [
            'add' => ['class' => 'alert-success', 'icon' => 'fas fa-check-circle'],
            'delete' => ['class' => 'alert-success', 'icon' => 'fas fa-check-circle'],
            'update' => ['class' => 'alert-success', 'icon' => 'fas fa-check-circle'],
            'delete_fail' => ['class' => 'alert-danger', 'icon' => 'fas fa-exclamation-circle']
        ];

        foreach ($messages as $key => $config) {
            if (isset($_SESSION[$key])) {
                echo '<div class="alert ' . $config['class'] . ' alert-dismissible fade show" role="alert">';
                echo '<i class="' . $config['icon'] . ' me-2"></i>' . htmlspecialchars($_SESSION[$key]);
                echo '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
                echo '</div>';
                unset($_SESSION[$key]);
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
                                        $conn = new mysqli(LOCALHOST, DB_USERNAME, DB_PASSWORD, DB_NAME);
                                        if ($conn->connect_error) {
                                            throw new Exception("Connection failed: " . $conn->connect_error);
                                        }

                                        $sql = "SELECT * FROM tbl_tasks ORDER BY 
                                           CASE priority 
                                               WHEN 'High' THEN 1 
                                               WHEN 'Medium' THEN 2 
                                               WHEN 'Low' THEN 3 
                                           END, deadline ASC";

                                        $res = $conn->query($sql);

                                        if ($res && $res->num_rows > 0) {
                                            $sn = 1;
                                            while ($row = $res->fetch_assoc()) {
                                                $task_id = htmlspecialchars($row['task_id']);
                                                $task_name = htmlspecialchars($row['task_name']);
                                                $priority = htmlspecialchars($row['priority']);
                                                $deadline = htmlspecialchars($row['deadline']);

                                                $priority_class = 'priority-' . strtolower($priority);
                                                $deadline_formatted = date('M d, Y', strtotime($deadline));
                                    ?>

                                                <tr>
                                                    <td><?php echo $sn++; ?></td>
                                                    <td>
                                                        <strong><?php echo $task_name; ?></strong>
                                                        <?php if (!empty($row['task_description'])): ?>
                                                            <br><small><?php echo htmlspecialchars($row['task_description']); ?></small>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-<?php echo $priority === 'High' ? 'danger' : ($priority === 'Medium' ? 'warning' : 'success'); ?>">
                                                            <?php echo $priority; ?>
                                                        </span>
                                                    </td>
                                                    <td><?php echo $deadline_formatted; ?></td>
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
                                                    <i class="fas fa-inbox fa-3x mb-3"></i>
                            <p>No tasks added yet. <a href="<?php echo SITEURL; ?>add-task.php">Add your first task</a>!</p>
                                                </td>
                                            </tr>
                                    <?php
                                        }
                                        $conn->close();
                                    } catch (Exception $e) {
                                        error_log($e->getMessage());
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- SweetAlert2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.10.1/dist/sweetalert2.all.min.js"></script>
    
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
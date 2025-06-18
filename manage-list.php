<?php
require_once('config/constants.php');
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Lists - Task Manager - SoftkIT</title>

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

                        $sql2 = "SELECT * FROM tbl_lists ORDER BY list_name";
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
                                        $conn = new mysqli(LOCALHOST, DB_USERNAME, DB_PASSWORD, DB_NAME);
                                        if ($conn->connect_error) {
                                            throw new Exception("Connection failed: " . $conn->connect_error);
                                        }

                                        // Get lists with task count
                                        $sql = "SELECT l.*, COUNT(t.task_id) as task_count 
                                           FROM tbl_lists l 
                                           LEFT JOIN tbl_tasks t ON l.list_id = t.list_id 
                                           GROUP BY l.list_id 
                                           ORDER BY l.list_name";

                                        $res = $conn->query($sql);

                                        if ($res && $res->num_rows > 0) {
                                            $sn = 1;
                                            while ($row = $res->fetch_assoc()) {
                                                $list_id = htmlspecialchars($row['list_id']);
                                                $list_name = htmlspecialchars($row['list_name']);
                                                $task_count = (int)$row['task_count'];
                                    ?>

                                                <tr>
                                                    <td><?php echo $sn++; ?></td>
                                                    <td>
                                                        <strong><?php echo $list_name; ?></strong>
                                                        <br><small>
                                                            <a href="<?php echo SITEURL; ?>list-task.php?list_id=<?php echo $list_id; ?>" class="text-decoration-none">
                                                                View tasks in this list
                                                            </a>
                                                        </small>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-<?php echo $task_count > 0 ? 'primary' : 'secondary'; ?>">
                                                            <?php echo $task_count; ?> task<?php echo $task_count !== 1 ? 's' : ''; ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <div class="btn-group" role="group">
                                                            <a href="<?php echo SITEURL; ?>update-list.php?list_id=<?php echo $list_id; ?>"
                                                                class="btn btn-outline-primary btn-sm" title="Edit List">
                                                                <i class="fas fa-edit"></i>
                                                            </a>
                                                            <a href="#"
                                                class="btn btn-outline-danger btn-sm" title="Delete List"
                                                onclick="confirmDeleteList(<?php echo $list_id; ?>)">
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
                                                <td colspan="4" class="text-center py-4">
                                                    <i class="fas fa-folder-open fa-3x mb-3"></i>
                            <p>No lists created yet. <a href="<?php echo SITEURL; ?>add-list.php">Create your first list</a>!</p>
                                                </td>
                                            </tr>
                                    <?php
                                        }
                                        $conn->close();
                                    } catch (Exception $e) {
                                        error_log($e->getMessage());
                                        echo '<tr><td colspan="4" class="text-center text-danger">Error loading lists. Please try again.</td></tr>';
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
        function confirmDeleteList(listId) {
            Swal.fire({
                title: 'Are you sure?',
                text: 'This will delete the list and all tasks in it. You won\'t be able to revert this!',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = '<?php echo SITEURL; ?>delete-list.php?list_id=' + listId;
                }
            });
        }
    </script>
</body>

</html>
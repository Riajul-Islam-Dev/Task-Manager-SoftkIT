<?php
require_once('config/constants.php');

// Process form submission BEFORE any HTML output
if (isset($_POST['submit'])) {
    // Validate and sanitize input
    $task_name = trim($_POST['task_name']);
    $task_description = trim($_POST['task_description']);
    $list_id = (int)$_POST['list_id'];
    $priority = $_POST['priority'];
    $deadline = $_POST['deadline'];

    // Basic validation
    if (empty($task_name) || empty($priority) || empty($deadline)) {
        $_SESSION['add_fail'] = "Please fill in all required fields.";
        header('Location: ' . SITEURL . 'add-task.php');
        exit();
    }

    // Validate priority
    if (!in_array($priority, ['High', 'Medium', 'Low'])) {
        $_SESSION['add_fail'] = "Invalid priority selected.";
        header('Location: ' . SITEURL . 'add-task.php');
        exit();
    }

    // Validate deadline (must be today or future)
    if (strtotime($deadline) < strtotime(date('Y-m-d'))) {
        $_SESSION['add_fail'] = "Deadline cannot be in the past.";
        header('Location: ' . SITEURL . 'add-task.php');
        exit();
    }

    try {
        $conn = new mysqli(LOCALHOST, DB_USERNAME, DB_PASSWORD, DB_NAME);
        if ($conn->connect_error) {
            throw new Exception("Connection failed: " . $conn->connect_error);
        }

        // Use prepared statement to prevent SQL injection
        $stmt = $conn->prepare("INSERT INTO tbl_tasks (task_name, task_description, list_id, priority, deadline) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("ssiss", $task_name, $task_description, $list_id, $priority, $deadline);

        if ($stmt->execute()) {
            $_SESSION['add'] = "✅ Task '" . htmlspecialchars($task_name) . "' added successfully!";
            header('Location: ' . SITEURL);
            exit();
        } else {
            throw new Exception("Failed to add task: " . $stmt->error);
        }

        $stmt->close();
        $conn->close();
    } catch (Exception $e) {
        error_log($e->getMessage());
        $_SESSION['add_fail'] = "An error occurred while adding the task. Please try again.";
        header('Location: ' . SITEURL . 'add-task.php');
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Task - Task Manager - SoftkIT</title>

    <link href="assets/img/favicon.png" rel="icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
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

        .form-control, .form-select {
            background-color: #0d1117;
            border: 1px solid #30363d;
            color: #e6edf3;
        }

        .form-control:focus, .form-select:focus {
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
                        if (isset($_SESSION['add_fail'])) {
                            echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">';
                            echo '<i class="fas fa-exclamation-circle me-2"></i>' . htmlspecialchars($_SESSION['add_fail']);
                            echo '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
                            echo '</div>';
                            unset($_SESSION['add_fail']);
                        }
                        ?>

                        <form method="POST" action="" class="needs-validation" novalidate>
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
                                        $conn = new mysqli(LOCALHOST, DB_USERNAME, DB_PASSWORD, DB_NAME);
                                        if ($conn->connect_error) {
                                            throw new Exception("Connection failed: " . $conn->connect_error);
                                        }

                                        $sql = "SELECT * FROM tbl_lists ORDER BY list_id ASC";
                                        $res = $conn->query($sql);

                                        if ($res && $res->num_rows > 0) {
                                            while ($row = $res->fetch_assoc()) {
                                                $list_id = htmlspecialchars($row['list_id']);
                                                $list_name = htmlspecialchars($row['list_name']);
                                                echo '<option value="' . $list_id . '">' . $list_name . '</option>';
                                            }
                                        }
                                        $conn->close();
                                    } catch (Exception $e) {
                                        error_log($e->getMessage());
                                        echo '<option value="0">Error loading lists</option>';
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
                                    <option value="High">🔴 High Priority</option>
                                    <option value="Medium" selected>🟡 Medium Priority</option>
                                    <option value="Low">🟢 Low Priority</option>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
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
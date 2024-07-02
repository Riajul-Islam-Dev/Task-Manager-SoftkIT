<?php
include('config/constants.php');
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Task Manager</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo SITEURL; ?>css/style.css" />
</head>

<body>

    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">

                <h1 class="text-center">Task Manager Application</h1>
                <a class="btn btn-secondary" href="<?php echo SITEURL; ?>">Home</a>

                <h3 class="mt-4">Add Task Page</h3>

                <p>
                    <?php
                    if (isset($_SESSION['add_fail'])) {
                        echo $_SESSION['add_fail'];
                        unset($_SESSION['add_fail']);
                    }
                    ?>
                </p>

                <form method="POST" action="">
                    <div class="mb-3">
                        <label for="taskName" class="form-label">Task Name</label>
                        <input type="text" id="taskName" name="task_name" class="form-control" placeholder="Type your Task Name" required>
                    </div>

                    <div class="mb-3">
                        <label for="taskDescription" class="form-label">Task Description</label>
                        <textarea id="taskDescription" name="task_description" class="form-control" placeholder="Type Task Description"></textarea>
                    </div>

                    <div class="mb-3">
                        <label for="listSelect" class="form-label">Select List</label>
                        <select id="listSelect" name="list_id" class="form-select">
                            <?php
                            $conn = new mysqli(LOCALHOST, DB_USERNAME, DB_PASSWORD, DB_NAME);

                            if ($conn->connect_error) {
                                die("Connection failed: " . $conn->connect_error);
                            }

                            $sql = "SELECT * FROM tbl_lists";
                            $res = $conn->query($sql);

                            if ($res->num_rows > 0) {
                                while ($row = $res->fetch_assoc()) {
                                    $list_id = $row['list_id'];
                                    $list_name = $row['list_name'];
                            ?>
                                    <option value="<?php echo $list_id; ?>"><?php echo htmlspecialchars($list_name); ?></option>
                                <?php
                                }
                            } else {
                                ?>
                                <option value="0">None</option>
                            <?php
                            }
                            $conn->close();
                            ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="prioritySelect" class="form-label">Priority</label>
                        <select id="prioritySelect" name="priority" class="form-select">
                            <option value="High">High</option>
                            <option value="Medium">Medium</option>
                            <option value="Low">Low</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="deadline" class="form-label">Deadline</label>
                        <input type="date" id="deadline" name="deadline" class="form-control">
                    </div>

                    <button type="submit" class="btn btn-primary" name="submit">Add</button>
                </form>

            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>

<?php
if (isset($_POST['submit'])) {
    $task_name = $_POST['task_name'];
    $task_description = $_POST['task_description'];
    $list_id = $_POST['list_id'];
    $priority = $_POST['priority'];
    $deadline = $_POST['deadline'];

    $conn2 = new mysqli(LOCALHOST, DB_USERNAME, DB_PASSWORD, DB_NAME);

    if ($conn2->connect_error) {
        die("Connection failed: " . $conn2->connect_error);
    }

    $stmt2 = $conn2->prepare("INSERT INTO tbl_tasks (task_name, task_description, list_id, priority, deadline) VALUES (?, ?, ?, ?, ?)");
    $stmt2->bind_param("ssiis", $task_name, $task_description, $list_id, $priority, $deadline);

    if ($stmt2->execute()) {
        $_SESSION['add'] = "Task Added Successfully.";
        header('location:' . SITEURL);
        exit();
    } else {
        $_SESSION['add_fail'] = "Failed to Add Task";
        header('location:' . SITEURL . 'add-task.php');
        exit();
    }

    $stmt2->close();
    $conn2->close();
}
?>
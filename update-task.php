<?php
include('config/constants.php');

// Check the Task ID in URL
if (isset($_GET['task_id'])) {
    $task_id = $_GET['task_id'];

    // Connect to Database
    $conn = new mysqli(LOCALHOST, DB_USERNAME, DB_PASSWORD, DB_NAME);

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // SQL Query to Get the detail of selected task
    $stmt = $conn->prepare("SELECT * FROM tbl_tasks WHERE task_id = ?");
    $stmt->bind_param("i", $task_id);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows > 0) {
        $row = $res->fetch_assoc();
        $task_name = $row['task_name'];
        $task_description = $row['task_description'];
        $list_id = $row['list_id'];
        $priority = $row['priority'];
        $deadline = $row['deadline'];
    } else {
        header('location:' . SITEURL);
        exit();
    }

    $stmt->close();
    $conn->close();
} else {
    header('location:' . SITEURL);
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Task Manager</title>
    <link rel="stylesheet" href="<?php echo SITEURL; ?>css/style.css" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>

    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-6">
                <h1 class="text-center">Task Manager Application</h1>
                <a class="btn btn-secondary" href="<?php echo SITEURL; ?>">Home</a>
                <h3 class="mt-4">Update Task Page</h3>

                <p>
                    <?php
                    if (isset($_SESSION['update_fail'])) {
                        echo $_SESSION['update_fail'];
                        unset($_SESSION['update_fail']);
                    }
                    ?>
                </p>

                <form method="POST" action="">
                    <div class="mb-3">
                        <label for="taskName" class="form-label">Task Name:</label>
                        <input type="text" id="taskName" name="task_name" class="form-control" value="<?php echo htmlspecialchars($task_name); ?>" required>
                    </div>

                    <div class="mb-3">
                        <label for="taskDescription" class="form-label">Task Description</label>
                        <textarea id="taskDescription" name="task_description" class="form-control" rows="4"><?php echo htmlspecialchars($task_description); ?></textarea>
                    </div>

                    <div class="mb-3">
                        <label for="listSelect" class="form-label">Select List:</label>
                        <select id="listSelect" name="list_id" class="form-select">
                            <?php
                            $conn2 = new mysqli(LOCALHOST, DB_USERNAME, DB_PASSWORD, DB_NAME);

                            if ($conn2->connect_error) {
                                die("Connection failed: " . $conn2->connect_error);
                            }

                            $sql2 = "SELECT * FROM tbl_lists";
                            $res2 = $conn2->query($sql2);

                            if ($res2->num_rows > 0) {
                                while ($row2 = $res2->fetch_assoc()) {
                                    $list_id_db = $row2['list_id'];
                                    $list_name = $row2['list_name'];
                            ?>
                                    <option value="<?php echo $list_id_db; ?>" <?php if ($list_id_db == $list_id) echo "selected"; ?>><?php echo htmlspecialchars($list_name); ?></option>
                                <?php
                                }
                            } else {
                                ?>
                                <option value="0" <?php if ($list_id == 0) echo "selected"; ?>>None</option>
                            <?php
                            }
                            $conn2->close();
                            ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="prioritySelect" class="form-label">Priority</label>
                        <select id="prioritySelect" name="priority" class="form-select">
                            <option value="High" <?php if ($priority == "High") echo "selected"; ?>>High</option>
                            <option value="Medium" <?php if ($priority == "Medium") echo "selected"; ?>>Medium</option>
                            <option value="Low" <?php if ($priority == "Low") echo "selected"; ?>>Low</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="deadline" class="form-label">Deadline:</label>
                        <input type="date" id="deadline" name="deadline" class="form-control" value="<?php echo htmlspecialchars($deadline); ?>">
                    </div>

                    <button type="submit" class="btn btn-primary" name="submit">Make Changes</button>
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

    $conn3 = new mysqli(LOCALHOST, DB_USERNAME, DB_PASSWORD, DB_NAME);

    if ($conn3->connect_error) {
        die("Connection failed: " . $conn3->connect_error);
    }

    $stmt3 = $conn3->prepare("UPDATE tbl_tasks SET task_name = ?, task_description = ?, list_id = ?, priority = ?, deadline = ? WHERE task_id = ?");
    $stmt3->bind_param("ssiisi", $task_name, $task_description, $list_id, $priority, $deadline, $task_id);

    if ($stmt3->execute()) {
        $_SESSION['update'] = "Task Updated Successfully.";
        header('location:' . SITEURL);
        exit();
    } else {
        $_SESSION['update_fail'] = "Failed to Update Task";
        header('location:' . SITEURL . 'update-task.php?task_id=' . $task_id);
        exit();
    }

    $stmt3->close();
    $conn3->close();
}
?>
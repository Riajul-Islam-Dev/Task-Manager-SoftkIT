<?php
include('config/constants.php');

// Connect to the database
$conn = new mysqli(LOCALHOST, DB_USERNAME, DB_PASSWORD, DB_NAME);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch lists from database
$sql2 = "SELECT * FROM tbl_lists";
$res2 = $conn->query($sql2);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Task Manager</title>
    <link rel="stylesheet" href="<?php echo SITEURL; ?>css/style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>

    <div class="container">
        <h1 class="text-center my-4">Task Manager Application</h1>

        <!-- Menu Starts Here -->
        <nav>
            <div class="nav nav-tabs" id="nav-tab" role="tablist">
                <a href="<?php echo SITEURL; ?>" class="nav-link active" id="nav-home-tab" role="tab"><b>Home</b></a>

                <?php if ($res2->num_rows > 0) : ?>
                    <?php while ($row2 = $res2->fetch_assoc()) : ?>
                        <a href="<?php echo SITEURL; ?>list-task.php?list_id=<?php echo $row2['list_id']; ?>" class="nav-link" id="nav-home-tab" role="tab"><b><?php echo htmlspecialchars($row2['list_name']); ?></b></a>
                    <?php endwhile; ?>
                <?php endif; ?>

                <a href="<?php echo SITEURL; ?>manage-list.php" class="nav-link"><b>Manage Lists</b></a>
            </div>
        </nav>
        <!-- Menu Ends Here -->

        <!-- Tasks Starts Here -->
        <p>
            <?php
            if (isset($_SESSION['add'])) {
                echo $_SESSION['add'];
                unset($_SESSION['add']);
            }

            if (isset($_SESSION['delete'])) {
                echo $_SESSION['delete'];
                unset($_SESSION['delete']);
            }

            if (isset($_SESSION['update'])) {
                echo $_SESSION['update'];
                unset($_SESSION['update']);
            }

            if (isset($_SESSION['delete_fail'])) {
                echo $_SESSION['delete_fail'];
                unset($_SESSION['delete_fail']);
            }
            ?>
        </p>

        <div class="all-tasks">
            <a href="<?php echo SITEURL; ?>add-task.php" class="btn btn-dark mb-3">Add Task</a>

            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>S.N.</th>
                        <th>Task Name</th>
                        <th>Priority</th>
                        <th>Deadline</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Fetch tasks from database
                    $sql = "SELECT * FROM tbl_tasks";
                    $res = $conn->query($sql);
                    if ($res->num_rows > 0) :
                        $sn = 1;
                        while ($row = $res->fetch_assoc()) : ?>
                            <tr>
                                <td><?php echo $sn++; ?>.</td>
                                <td><?php echo htmlspecialchars($row['task_name']); ?></td>
                                <td><?php echo htmlspecialchars($row['priority']); ?></td>
                                <td><?php echo htmlspecialchars($row['deadline']); ?></td>
                                <td>
                                    <a href="<?php echo SITEURL; ?>update-task.php?task_id=<?php echo $row['task_id']; ?>" class="btn btn-success btn-sm">Update</a>
                                    <a href="<?php echo SITEURL; ?>delete-task.php?task_id=<?php echo $row['task_id']; ?>" class="btn btn-danger btn-sm">Remove</a>
                                </td>
                            </tr>
                        <?php endwhile;
                    else : ?>
                        <tr>
                            <td colspan="5" class="text-center">No Task Added Yet.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <!-- Tasks Ends Here -->
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>

<?php
// Close the database connection
$conn->close();
?>
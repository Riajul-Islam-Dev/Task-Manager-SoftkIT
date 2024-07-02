<?php
include('config/constants.php');

// Get the list ID from URL
$list_id_url = $_GET['list_id'] ?? null;
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

    <div class="wrapper">
        <h1 class="text-center">Task Manager Application</h1>

        <!-- Menu Starts Here -->
        <nav>
            <div class="nav nav-tabs" id="nav-tab" role="tablist">
                <a href="<?php echo SITEURL; ?>" class="nav-link active" id="nav-home-tab" data-bs-toggle="tab" aria-controls="nav-home" aria-selected="true"><b>Home</b></a>

                <?php
                // Displaying lists from database in the menu
                $conn2 = new mysqli(LOCALHOST, DB_USERNAME, DB_PASSWORD, DB_NAME);

                if ($conn2->connect_error) {
                    die("Connection failed: " . $conn2->connect_error);
                }

                $sql2 = "SELECT * FROM tbl_lists";
                $res2 = $conn2->query($sql2);

                if ($res2->num_rows > 0) {
                    while ($row2 = $res2->fetch_assoc()) {
                        $list_id = $row2['list_id'];
                        $list_name = $row2['list_name'];
                ?>
                        <a href="<?php echo SITEURL; ?>list-task.php?list_id=<?php echo $list_id; ?>" class="nav-link" id="nav-home-tab" data-bs-toggle="tab" aria-controls="nav-home" aria-selected="true"><b><?php echo $list_name; ?></b></a>
                <?php
                    }
                }
                $conn2->close();
                ?>

                <a href="<?php echo SITEURL; ?>manage-list.php" class="nav-link active" id="nav-home-tab" data-bs-toggle="tab" aria-controls="nav-home" aria-selected="true"><b>Manage Lists</b></a>
            </div>
        </nav>
        <!-- Menu Ends Here -->

        <div class="all-task">
            <a href="<?php echo SITEURL; ?>add-task.php"><button class="btn btn-dark">Add Task</button></a>

            <table class="tbl-full table table-condensed table-hover">
                <tr>
                    <th>S.N.</th>
                    <th>Task Name</th>
                    <th>Priority</th>
                    <th>Deadline</th>
                    <th>Actions</th>
                </tr>

                <?php
                $conn = new mysqli(LOCALHOST, DB_USERNAME, DB_PASSWORD, DB_NAME);

                if ($conn->connect_error) {
                    die("Connection failed: " . $conn->connect_error);
                }

                $sql = "SELECT * FROM tbl_tasks WHERE list_id=?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $list_id_url);
                $stmt->execute();
                $res = $stmt->get_result();

                if ($res->num_rows > 0) {
                    $sn = 1;
                    while ($row = $res->fetch_assoc()) {
                        $task_id = $row['task_id'];
                        $task_name = $row['task_name'];
                        $priority = $row['priority'];
                        $deadline = $row['deadline'];
                ?>
                        <tr>
                            <td><?php echo $sn++; ?>.</td>
                            <td><?php echo htmlspecialchars($task_name); ?></td>
                            <td><?php echo htmlspecialchars($priority); ?></td>
                            <td><?php echo htmlspecialchars($deadline); ?></td>
                            <td>
                                <a href="<?php echo SITEURL; ?>update-task.php?task_id=<?php echo $task_id; ?>" class="text-decoration-none">
                                    <button class="btn btn-success btn-sm">Update</button>
                                </a>
                                <a href="<?php echo SITEURL; ?>delete-task.php?task_id=<?php echo $task_id; ?>" class="text-decoration-none">
                                    <button class="btn btn-danger btn-sm">Delete</button>
                                </a>
                            </td>
                        </tr>
                    <?php
                    }
                } else {
                    ?>
                    <tr>
                        <td colspan="5">No tasks added to this list.</td>
                    </tr>
                <?php
                }
                $stmt->close();
                $conn->close();
                ?>
            </table>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
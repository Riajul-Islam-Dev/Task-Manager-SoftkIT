<?php
include('config/constants.php');
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
                        <a href="<?php echo SITEURL; ?>list-task.php?list_id=<?php echo $list_id; ?>" class="nav-link" id="nav-home-tab" data-bs-toggle="tab" aria-controls="nav-home" aria-selected="true"><b><?php echo htmlspecialchars($list_name); ?></b></a>
                <?php
                    }
                }
                $conn2->close();
                ?>

                <a href="<?php echo SITEURL; ?>manage-list.php" class="nav-link active" id="nav-home-tab" data-bs-toggle="tab" aria-controls="nav-home" aria-selected="true"><b>Manage Lists</b></a>
            </div>
        </nav>
        <!-- Menu Ends Here -->

        <p>
            <?php
            // Check if the session is set and display messages
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

        <!-- Table to display lists starts here -->
        <div class="all-lists">
            <a href="<?php echo SITEURL; ?>add-list.php"><button class="btn btn-dark">Add List</button></a>

            <table class="tbl-half table table-condensed table-hover">
                <tr>
                    <th>S.N.</th>
                    <th>List Name</th>
                    <th>Actions</th>
                </tr>

                <?php
                // Connect to the database
                $conn = new mysqli(LOCALHOST, DB_USERNAME, DB_PASSWORD, DB_NAME);

                if ($conn->connect_error) {
                    die("Connection failed: " . $conn->connect_error);
                }

                $sql = "SELECT * FROM tbl_lists";
                $res = $conn->query($sql);

                if ($res->num_rows > 0) {
                    $sn = 1;
                    while ($row = $res->fetch_assoc()) {
                        $list_id = $row['list_id'];
                        $list_name = $row['list_name'];
                ?>
                        <tr>
                            <td><?php echo $sn++; ?>.</td>
                            <td><?php echo htmlspecialchars($list_name); ?></td>
                            <td>
                                <a href="<?php echo SITEURL; ?>update-list.php?list_id=<?php echo $list_id; ?>" class="text-decoration-none">
                                    <button class="btn btn-success btn-sm">Update</button>
                                </a>
                                <a href="<?php echo SITEURL; ?>delete-list.php?list_id=<?php echo $list_id; ?>" class="text-decoration-none">
                                    <button class="btn btn-danger btn-sm">Delete</button>
                                </a>
                            </td>
                        </tr>
                    <?php
                    }
                } else {
                    ?>
                    <tr>
                        <td colspan="3">No List Added Yet.</td>
                    </tr>
                <?php
                }
                $conn->close();
                ?>
            </table>
        </div>
        <!-- Table to display lists ends here -->
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
<?php
include('config/constants.php');

// Get the Current Values of Selected List
if (isset($_GET['list_id'])) {
    $list_id = $_GET['list_id'];

    // Connect to Database
    $conn = new mysqli(LOCALHOST, DB_USERNAME, DB_PASSWORD, DB_NAME);

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // Query to Get the Values from Database
    $stmt = $conn->prepare("SELECT * FROM tbl_lists WHERE list_id = ?");
    $stmt->bind_param("i", $list_id);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows > 0) {
        $row = $res->fetch_assoc();
        $list_name = $row['list_name'];
        $list_description = $row['list_description'];
    } else {
        header('location:' . SITEURL . 'manage-list.php');
        exit();
    }

    $stmt->close();
    $conn->close();
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
                <a class="btn btn-secondary" href="<?php echo SITEURL; ?>manage-list.php">Manage Lists</a>

                <h3 class="mt-4">Update List Page</h3>

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
                        <label for="listName" class="form-label">List Name</label>
                        <input type="text" id="listName" name="list_name" class="form-control" value="<?php echo htmlspecialchars($list_name); ?>" required>
                    </div>

                    <div class="mb-3">
                        <label for="listDescription" class="form-label">List Description</label>
                        <textarea id="listDescription" name="list_description" class="form-control" rows="4"><?php echo htmlspecialchars($list_description); ?></textarea>
                    </div>

                    <button type="submit" name="submit" class="btn btn-primary">Make Changes</button>
                </form>

            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>

<?php
if (isset($_POST['submit'])) {
    $list_name = $_POST['list_name'];
    $list_description = $_POST['list_description'];

    $conn2 = new mysqli(LOCALHOST, DB_USERNAME, DB_PASSWORD, DB_NAME);

    if ($conn2->connect_error) {
        die("Connection failed: " . $conn2->connect_error);
    }

    $stmt2 = $conn2->prepare("UPDATE tbl_lists SET list_name = ?, list_description = ? WHERE list_id = ?");
    $stmt2->bind_param("ssi", $list_name, $list_description, $list_id);

    if ($stmt2->execute()) {
        $_SESSION['update'] = "List Updated Successfully";
        header('location:' . SITEURL . 'manage-list.php');
        exit();
    } else {
        $_SESSION['update_fail'] = "Failed to Update List";
        header('location:' . SITEURL . 'update-list.php?list_id=' . $list_id);
        exit();
    }

    $stmt2->close();
    $conn2->close();
}
?>
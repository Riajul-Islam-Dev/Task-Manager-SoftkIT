<?php

include('config/constants.php');




//Get the Current Values of Selected List
if (isset($_GET['list_id'])) {
    //Get the List ID value
    $list_id = $_GET['list_id'];

    //Connect to Database
    $conn = mysqli_connect(LOCALHOST, DB_USERNAME, DB_PASSWORD) or die(mysqli_error());

    //SElect DAtabase
    $db_select = mysqli_select_db($conn, DB_NAME) or die(mysqli_error());

    //Query to Get the Values from Database
    $sql = "SELECT * FROM tbl_lists WHERE list_id=$list_id";

    //Execute Query
    $res = mysqli_query($conn, $sql);

    //CHekc whether the query executed successfully or not
    if ($res == true) {
        //Get the Value from Database
        $row = mysqli_fetch_assoc($res); //Value is in array

        //printing $row array
        //print_r($row);

        //Create Individual Variable to save the data
        $list_name = $row['list_name'];
        $list_description = $row['list_description'];
    } else {
        //Go Back to Manage List Page
        header('location:' . SITEURL . 'manage-list.php');
    }
}

?>




<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update List - Task Manager - SoftkIT</title>

    <link href="assets/img/favicon.png" rel="icon">
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="css/all.min.css" rel="stylesheet">
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

        .form-control,
        .form-select {
            background-color: #0d1117;
            border: 1px solid #30363d;
            color: #e6edf3;
        }

        .form-control:focus,
        .form-select:focus {
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
            background-color: #0f5132;
            border-color: #0a3622;
            color: #75b798;
        }

        .alert-danger {
            background-color: #842029;
            border-color: #721c24;
            color: #ea868f;
        }

        .navbar {
            background-color: #21262d !important;
            border-bottom: 1px solid #30363d;
        }

        .navbar-nav .nav-link {
            color: #e6edf3 !important;
        }

        .navbar-nav .nav-link:hover {
            color: #58a6ff !important;
        }
    </style>
</head>

<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="<?php echo SITEURL; ?>">
                <i class="fas fa-tasks me-2"></i>Task Manager
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo SITEURL; ?>">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo SITEURL; ?>manage-list.php">Manage Lists</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo SITEURL; ?>add-list.php">Add List</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo SITEURL; ?>calendar.php">Calendar</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h4 class="mb-0">
                            <i class="fas fa-edit me-2"></i>Update List
                        </h4>
                    </div>
                    <div class="card-body">
                        <?php
                        //Check whether the session is set or not
                        if (isset($_SESSION['update_fail'])) {
                            echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">';
                            echo $_SESSION['update_fail'];
                            echo '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
                            echo '</div>';
                            unset($_SESSION['update_fail']);
                        }
                        ?>

                        <form method="POST" action="">
                            <div class="mb-3">
                                <label for="list_name" class="form-label">List Name</label>
                                <input type="text" name="list_name" id="list_name" class="form-control" value="<?php echo htmlspecialchars($list_name); ?>" required />
                            </div>

                            <div class="mb-3">
                                <label for="list_description" class="form-label">List Description</label>
                                <textarea name="list_description" id="list_description" class="form-control" rows="3"><?php echo htmlspecialchars($list_description); ?></textarea>
                            </div>

                            <div class="d-flex justify-content-end">
                                <button type="submit" name="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>Save Changes
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="js/bootstrap.bundle.min.js"></script>
</body>

</html>


<?php

//Check whether the Update is Clicked or Not
if (isset($_POST['submit'])) {
    //echo "Button Clicked";

    //Get the Updated Values from our Form
    $list_name = $_POST['list_name'];
    $list_description = $_POST['list_description'];

    //Connect Database
    $conn2 = mysqli_connect(LOCALHOST, DB_USERNAME, DB_PASSWORD) or die(mysqli_error());

    //SElect the Database
    $db_select2 = mysqli_select_db($conn2, DB_NAME);

    //QUERY to Update List
    $sql2 = "UPDATE tbl_lists SET 
            list_name = '$list_name',
            list_description = '$list_description' 
            WHERE list_id=$list_id
        ";

    //Execute the Query
    $res2 = mysqli_query($conn2, $sql2);

    //Check whether the query executed successfully or not
    if ($res2 == true) {
        //Update Successful
        //SEt the Message
        $_SESSION['update'] = "List Updated Successfully";

        //Redirect to Manage List PAge
        header('location:' . SITEURL . 'manage-list.php');
    } else {
        //FAiled to Update
        //SEt Session Message
        $_SESSION['update_fail'] = "Failed to Update List";
        //Redirect to the Update List PAge
        header('location:' . SITEURL . 'update-list.php?list_id=' . $list_id);
    }
}
?>
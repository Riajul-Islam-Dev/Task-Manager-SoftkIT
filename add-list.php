<?php
include('config/constants.php');

//Check whether the form is submitted or not
if (isset($_POST['submit'])) {
    //Get the values from form and save it in variables
    $list_name = $_POST['list_name'];
    $list_description = $_POST['list_description'];

    //Connect Database
    $conn = mysqli_connect(LOCALHOST, DB_USERNAME, DB_PASSWORD) or die(mysqli_error());

    //Select Database
    $db_select = mysqli_select_db($conn, DB_NAME);

    //Create SQL Query to Insert Data into Database
    $sql = "INSERT INTO tbl_lists SET 
            list_name = '$list_name',
            list_description = '$list_description'
        ";

    //Execute Query
    $res = mysqli_query($conn, $sql);

    //Check whether the data inserted or not
    if ($res == true) {
        //Data Inserted Successfully
        //Create a SESSION Variable to Display message
        $_SESSION['add'] = "List Added Successfully";

        //Redirect to Manage List Page
        header('location:' . SITEURL . 'manage-list.php');
        exit();
    } else {
        //Failed to insert data
        //Create Session to save message
        $_SESSION['add_fail'] = "Failed to Add List";

        //Redirect to Same Page
        header('location:' . SITEURL . 'add-list.php');
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add List - Task Manager - SoftkIT</title>

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
                <a class="nav-link" href="<?php echo SITEURL; ?>manage-list.php">
                    <i class="fas fa-list me-1"></i>Manage Lists
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
                            <i class="fas fa-plus-circle me-2"></i>Add New List
                        </h4>
                    </div>
                    <div class="card-body">

                        <?php
                        //Check whether the session is created or not
                        if (isset($_SESSION['add_fail'])) {
                            //display session message
                            echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">';
                            echo $_SESSION['add_fail'];
                            echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
                            echo '</div>';
                            //Remove the message after displaying once
                            unset($_SESSION['add_fail']);
                        }
                        ?>

                        <form method="POST">
                            <div class="mb-3">
                                <label for="list_name" class="form-label">List Name:</label>
                                <input type="text" id="list_name" name="list_name" class="form-control" placeholder="Type list name here" required="required" />
                            </div>

                            <div class="mb-3">
                                <label for="list_description" class="form-label">List Description</label>
                                <textarea id="list_description" name="list_description" class="form-control" rows="3" placeholder="Type List Description Here"></textarea>
                            </div>

                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <button type="submit" class="btn btn-primary" name="submit">
                                    <i class="fas fa-save me-1"></i>Add List
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
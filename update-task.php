<?php
include('config/constants.php');

//Check the Task ID in URL

if (isset($_GET['task_id'])) {
    //Get the Values from DAtabase
    $task_id = $_GET['task_id'];

    //Connect Database
    $conn = mysqli_connect(LOCALHOST, DB_USERNAME, DB_PASSWORD) or die(mysqli_error());

    //Select Database
    $db_select = mysqli_select_db($conn, DB_NAME) or die(mysqli_error());

    //SQL Query to Get the detail of selected task
    $sql = "SELECT * FROM tbl_tasks WHERE task_id=$task_id";

    //Execute Query
    $res = mysqli_query($conn, $sql);

    //Check if the query executed successfully or not
    if ($res == true) {
        //Query <br />Executed
        $row = mysqli_fetch_assoc($res);

        //Get the Individual Value
        $task_name = $row['task_name'];
        $task_description = $row['task_description'];
        $list_id = $row['list_id'];
        $priority = $row['priority'];
        $deadline = $row['deadline'];
    }
} else {
    //Redirect to Homepage
    header('location:' . SITEURL);
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Task - Task Manager - SoftkIT</title>

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
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title mb-0 fw-semibold">
                            <i class="fas fa-edit me-2"></i>Update Task
                        </h4>
                    </div>
                    <div class="card-body">
                        <?php
                        if (isset($_SESSION['update_fail'])) {
                            echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">';
                            echo $_SESSION['update_fail'];
                            echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
                            echo '</div>';
                            unset($_SESSION['update_fail']);
                        }
                        ?>

                        <form method="POST" action="">
                            <div class="mb-3">
                                <label for="task_name" class="form-label">Task Name:</label>
                                <input type="text" id="task_name" name="task_name" class="form-control" value="<?php echo $task_name; ?>" required="required" />
                            </div>

                            <div class="mb-3">
                                <label for="task_description" class="form-label">Task Description</label>
                                <textarea id="task_description" name="task_description" class="form-control" rows="3"><?php echo trim($task_description); ?></textarea>
                            </div>

                            <div class="mb-3">
                                <label for="list_id" class="form-label">Select List:</label>
                                <select id="list_id" name="list_id" class="form-select">
                                    <?php
                                    //Connect Database
                                    $conn2 = mysqli_connect(LOCALHOST, DB_USERNAME, DB_PASSWORD) or die(mysqli_error());

                                    //SElect Database
                                    $db_select2 = mysqli_select_db($conn2, DB_NAME) or die(mysqli_error());

                                    //SQL Query to GET Lists
                                    $sql2 = "SELECT * FROM tbl_lists";

                                    //Execute Query
                                    $res2 = mysqli_query($conn2, $sql2);

                                    //Check if executed successfully or not
                                    if ($res2 == true) {
                                        //Display the Lists
                                        //Count Rows
                                        $count_rows2 = mysqli_num_rows($res2);

                                        //Check whether list is added or not
                                        if ($count_rows2 > 0) {
                                            //Lists are Added
                                            while ($row2 = mysqli_fetch_assoc($res2)) {
                                                //Get individual value
                                                $list_id_db = $row2['list_id'];
                                                $list_name = $row2['list_name'];
                                    ?>

                                                <option <?php if ($list_id_db == $list_id) {
                                                            echo "selected='selected'";
                                                        } ?> value="<?php echo $list_id_db; ?>"><?php echo $list_name; ?></option>

                                            <?php
                                            }
                                        } else {
                                            //No List Added
                                            //Display None as option
                                            ?>
                                            <option <?php if ($list_id = 0) {
                                                        echo "selected='selected'";
                                                    } ?> value="0">None</option>p
                                    <?php
                                        }
                                    }
                                    ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="priority" class="form-label">Priority</label>
                                <select id="priority" name="priority" class="form-select">
                                    <option <?php if ($priority == "High") {
                                                echo "selected='selected'";
                                            } ?> value="High">High</option>
                                    <option <?php if ($priority == "Medium") {
                                                echo "selected='selected'";
                                            } ?> value="Medium">Medium</option>
                                    <option <?php if ($priority == "Low") {
                                                echo "selected='selected'";
                                            } ?> value="Low">Low</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="deadline" class="form-label">Deadline:</label>
                                <input type="date" id="deadline" name="deadline" class="form-control" value="<?php echo $deadline; ?>" />
                            </div>

                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <button type="submit" class="btn btn-primary" name="submit">
                                    <i class="fas fa-save me-1"></i>Update Task
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

//Check if the button is clicked
if (isset($_POST['submit'])) {
    //echo "Clicked";

    //Get the CAlues from Form
    $task_name = $_POST['task_name'];
    $task_description = $_POST['task_description'];
    $list_id = $_POST['list_id'];
    $priority = $_POST['priority'];
    $deadline = $_POST['deadline'];

    //Connect Database
    $conn3 = mysqli_connect(LOCALHOST, DB_USERNAME, DB_PASSWORD) or die(mysqli_error());

    //SElect Database
    $db_select3 = mysqli_select_db($conn3, DB_NAME) or die(mysqli_error());

    //CREATE SQL QUery to Update TAsk
    $sql3 = "UPDATE tbl_tasks SET 
        task_name = '$task_name',
        task_description = '$task_description',
        list_id = '$list_id',
        priority = '$priority',
        deadline = '$deadline'
        WHERE 
        task_id = $task_id
        ";

    //Execute Query
    $res3 = mysqli_query($conn3, $sql3);

    //CHeck whether the Query Executed of Not
    if ($res3 == true) {
        //Query Executed and Task Updated
        $_SESSION['update'] = "Task Updated Successfully.";

        //Redirect to Home Page
        header('location:' . SITEURL);
    } else {
        //FAiled to Update Task
        $_SESSION['update_fail'] = "Failed to Update Task";

        //Redirect to this Page
        header('location:' . SITEURL . 'update-task.php?task_id=' . $task_id);
    }
}

?>
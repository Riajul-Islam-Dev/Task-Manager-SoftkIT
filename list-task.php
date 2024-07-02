<?php 
    include('config/constants.php');
    //Get the listid from URL
    
    $list_id_url = $_GET['list_id'];
?>

<html>
    <head>
        <title>Task Manager</title>
        <link rel="stylesheet" href="<?php echo SITEURL; ?>css/style.css" />
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-eOJMYsd53ii+scO/bJGFsiCZc+5NDVN2yr8+0RDqr0Ql0h+rP48ckxlpbzKgwra6" crossorigin="anonymous">
    </head>
    
    <body>
        
        <div class="wrapper">
        
        <h1 class="text-center">Task Manager Application</h1>
        
        <!-- Menu Starts Here -->
    <nav>
        <div class="nav nav-tabs" id="nav-tab" role="tablist">
    
            <a href="<?php echo SITEURL; ?>" style="text-decoration: none;"><button class="nav-link active" id="nav-home-tab" data-bs-toggle="tab" data-bs-target="#nav-home" type="button" role="tab" aria-controls="nav-home" aria-selected="true"><b>Home</b></button></a>
            
            <?php 
                
                //Comment Displaying Lists From Database in ourMenu
                $conn2 = mysqli_connect(LOCALHOST, DB_USERNAME, DB_PASSWORD) or die(mysqli_error());
                
                //SELECT DATABASE
                $db_select2 = mysqli_select_db($conn2, DB_NAME) or die(mysqli_error());
                
                //Query to Get the Lists from database
                $sql2 = "SELECT * FROM tbl_lists";
                
                //Execute Query
                $res2 = mysqli_query($conn2, $sql2);
                
                //CHeck whether the query executed or not
                if($res2==true)
                {
                    //Display the lists in menu
                    while($row2=mysqli_fetch_assoc($res2))
                    {
                        $list_id = $row2['list_id'];
                        $list_name = $row2['list_name'];
                        ?>
                        
                        <a href="<?php echo SITEURL; ?>list-task.php?list_id=<?php echo $list_id; ?>" style="text-decoration: none;"><button class="nav-link active" id="nav-home-tab" data-bs-toggle="tab" data-bs-target="#nav-home" type="button" role="tab" aria-controls="nav-home" aria-selected="true"><b><?php echo $list_name; ?></b></button></a>
                        
                        <?php
                        
                    }
                }
                
            ?>
            
            
            
            <a href="<?php echo SITEURL; ?>manage-list.php" style="text-decoration: none;"><button class="nav-link active" id="nav-home-tab" data-bs-toggle="tab" data-bs-target="#nav-home" type="button" role="tab" aria-controls="nav-home" aria-selected="true"><b>Manage Lists</b></button></a>
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
                
                    $conn = mysqli_connect(LOCALHOST, DB_USERNAME, DB_PASSWORD) or die(mysqli_error());
                    
                    $db_select = mysqli_select_db($conn, DB_NAME) or die(mysqli_error());
                    
                    //SQL QUERY to display tasks by list selected
                    $sql = "SELECT * FROM tbl_tasks WHERE list_id=$list_id_url";
                    
                    //Execute Query
                    $res = mysqli_query($conn, $sql);
                    
                    if($res==true)
                    {
                        //Display the tasks based on list
                        //Count the Rows
                        $count_rows = mysqli_num_rows($res);
                        
                        if($count_rows>0)
                        {
                            //We have tasks on this list
                            while($row=mysqli_fetch_assoc($res))
                            {
                                $task_id = $row['task_id'];
                                $task_name = $row['task_name'];
                                $priority = $row['priority'];
                                $deadline = $row['deadline'];
                                ?>
                                
                                <tr>
                                    <td>1. </td>
                                    <td><?php echo $task_name; ?></td>
                                    <td><?php echo $priority; ?></td>
                                    <td><?php echo $deadline; ?></td>
                                    <td>
                                        <a href="<?php echo SITEURL; ?>update-task.php?task_id=<?php echo $task_id; ?>" style="text-decoration:none;"><div class="btn btn-success btn-sm">Update</div> </a>
                                    
                                    <a href="<?php echo SITEURL; ?>delete-task.php?task_id=<?php echo $task_id; ?>" style="text-decoration:none;"> <div class="btn btn-danger btn-sm">Delete</div> </a>
                                    </td>
                                </tr>
                                
                                <?php
                            }
                        }
                        else
                        {
                            //NO Tasks on this list
                            ?>
                            
                            <tr>
                                <td colspan="5">No Tasks added on this list.</td>
                            </tr>
                            
                            <?php
                        }
                    }
                ?>
                
                
            
            </table>
        
        </div>
        
        </div>
    </body>
    
</html>
































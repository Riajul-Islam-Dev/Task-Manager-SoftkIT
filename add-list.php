<?php 
    include('config/constants.php');
?>

<html>
    <head>
        <title>Task Manager</title>
        <link rel="stylesheet" href="<?php echo SITEURL; ?>css/style.css" />
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-eOJMYsd53ii+scO/bJGFsiCZc+5NDVN2yr8+0RDqr0Ql0h+rP48ckxlpbzKgwra6" crossorigin="anonymous">
    </head>
    
    <body>
        
        <div class="container">
            
            <div class="row">
                <div class="col-lg-3"></div>

                <div class="col-lg-6">
                <h1 class="text-center">Task Manager Application</h1>
        
                    <a class="btn-secondary" href="<?php echo SITEURL; ?>">Home</a>
                    <a class="btn-secondary" href="<?php echo SITEURL; ?>manage-list.php">Manage Lists</a>
                    
                    
                    <h3>Add List Page</h3>
                    
                    <p>
                    
                    <?php 
                    
                        //Check whether the session is created or not
                        if(isset($_SESSION['add_fail']))
                        {
                            //display session message
                            echo $_SESSION['add_fail'];
                            //Remove the message after displaying once
                            unset($_SESSION['add_fail']);
                        }
                    
                    ?>
                    
                    </p>

                    <form method="POST">
                        <div class="mb-3">
                          <label for="example" class="form-label">List Name:</label>
                          <input type="text" name="list_name" class="form-control" placeholder="Type list name here" required="required" />
                        </div>

                        <div class="mb-3">
                          <label for="example" class="form-label">List Description</label>
                          <textarea name="list_description" class="form-control" placeholder="Type List Description Here"></textarea>
                        </div>


                        <button type="submit" class="btn btn-primary" name="submit">Submit</button>
                      </form>

                </div>

                <div class="col-lg-3"></div>
            </div>
        </div>

        
    </body>
</html>


<?php 

    //Check whether the form is submitted or not
    if(isset($_POST['submit']))
    {
        //echo "Form Submitted";
        
        //Get the values from form and save it in variables
        $list_name = $_POST['list_name'];
        $list_description = $_POST['list_description'];
        
        //Connect Database
        $conn = mysqli_connect(LOCALHOST, DB_USERNAME, DB_PASSWORD) or die(mysqli_error());
        
        //Check whether the database connected or not
        /*
        if($conn==true)
        {
            echo "Database Connected";
        }
        */
        
        //SElect Database
        $db_select = mysqli_select_db($conn, DB_NAME);
        
        //Check whether database is connected or not
        /*
        if($db_select==true)
        {
            echo "Database SElected";
        }
        */
        //SQL Query to Insert data into database
        $sql = "INSERT INTO tbl_lists SET 
            list_name = '$list_name',
            list_description = '$list_description'
        ";
        
        //Execute Query and Insert into Database
        $res = mysqli_query($conn, $sql);
        
        //Check whether the query executed successfully or not
        if($res==true)
        {
            //Data inserted successfully
            //echo "Data Inserted";
            
            //Create a SESSION VAriable to Display message
            $_SESSION['add'] = "List Added Successfully";
            
            //Redirect to Manage List Page
            header('location:'.SITEURL.'manage-list.php');
            
            
        }
        else
        {
            //Failed to insert data
            //echo "Failed to Insert Data";
            
            //Create SEssion to save message
            $_SESSION['add_fail'] = "Failed to Add List";
            
            //REdirect to Same Page
            header('location:'.SITEURL.'add-list.php');
        }
        
    }

?>


































<?php
//Start Session
session_start();

//Create Constants to save Database Credentials
define('LOCALHOST', 'localhost');
// Local
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'task_manager');
// Live
// define('DB_USERNAME', 'softkiti_task_manager_user');
// define('DB_PASSWORD', 'Gv8#zPq9Xr!mL2');
// define('DB_NAME', 'softkiti_task_manager');

// define('SITEURL', 'http://127.0.0.1/Task-Manager-SoftkIT/');

if ($_SERVER['HTTP_HOST'] == 'localhost') {
    define("SITEURL", "http://localhost/Task-Manager-SoftkIT/");
} else {
    define("SITEURL", "https://tm.softkit.io/");
}

<?php
//Start Session
session_start();

//Create Constants to save Database Credentials
define('LOCALHOST', 'localhost');

// Check if we're on local or live server
if ($_SERVER['HTTP_HOST'] == 'localhost' || $_SERVER['HTTP_HOST'] == 'tm.softkit.io.test') {
    // Local development
    define('DB_USERNAME', 'root');
    define('DB_PASSWORD', '');
    define('DB_NAME', 'task_manager');
    define("SITEURL", "http://localhost/tm.softkit.io/");
} else {
    // Live server
    define('DB_USERNAME', 'softkiti_task_manager_user');
    define('DB_PASSWORD', 'Gv8#zPq9Xr!mL2');
    define('DB_NAME', 'softkiti_task_manager');
    define("SITEURL", "https://tm.softkit.io/");
}

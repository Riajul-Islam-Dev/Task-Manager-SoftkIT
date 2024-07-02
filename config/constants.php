<?php
// Start session
session_start();

// Create constants to save database credentials
define('LOCALHOST', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'task_manager');

define("SITEURL", "http://127.0.0.1/Task-Manager-SoftkIT/");
// Define site URL based on the environment
// if ($_SERVER['HTTP_HOST'] === 'localhost') {
//     define("SITEURL", "http://localhost/Task-Manager-SoftkIT/");
// } else {
//     define("SITEURL", "https://tm.softkit.xyz/");
// }

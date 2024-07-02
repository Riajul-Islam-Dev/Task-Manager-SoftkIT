<?php
//Start Session
session_start();

//Create Constants to save Database Credentials
define('LOCALHOST', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'task_manager');

// define('SITEURL', 'http://localhost/Task-Manager-SoftkIT/');


if ($_SERVER['HTTP_HOST'] == 'localhost') {
    define("SITEURL", "http://localhost/Task-Manager-SoftkIT/");
} else {
    define("SITEURL", "https://tm.softkit.xyz/");
}

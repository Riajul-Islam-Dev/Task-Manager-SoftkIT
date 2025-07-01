<?php

declare(strict_types=1);

// Include modern configuration and classes
require_once 'config/constants.php';
require_once 'config/Database.php';
require_once 'config/Session.php';
require_once 'config/Enums.php';

// Start session
Session::start();

// Check task_id in URL
if (isset($_GET['task_id']) && is_numeric($_GET['task_id'])) {
    $taskId = (int) $_GET['task_id'];
    
    try {
        // First, check if the task exists and get its details
        $taskData = Database::fetchOne(
            "SELECT task_id, task_name, list_id FROM tbl_tasks WHERE task_id = ?",
            [$taskId]
        );
        
        if (!$taskData) {
            Session::setError('Task not found.');
            header('Location: ' . SITEURL);
            exit;
        }
        
        // Begin transaction for safe deletion
        Database::beginTransaction();
        
        try {
            // Delete the task from database
            $deleteResult = Database::execute(
                "DELETE FROM tbl_tasks WHERE task_id = ?",
                [$taskId]
            );
            
            if ($deleteResult) {
                // Commit the transaction
                Database::commit();
                
                // Log the deletion event
                error_log("Task deleted successfully: ID {$taskId}, Name: {$taskData['task_name']}");
                
                Session::setSuccess("Task '{$taskData['task_name']}' deleted successfully!");
            } else {
                throw new Exception('Failed to delete task from database');
            }
        } catch (Exception $e) {
            // Rollback the transaction
            Database::rollback();
            throw $e;
        }
        
    } catch (Exception $e) {
        error_log('Error deleting task: ' . $e->getMessage());
        Session::setError('An error occurred while deleting the task. Please try again.');
    }
} else {
    Session::setError('Invalid task ID provided.');
}

// Redirect to Homepage
header('Location: ' . SITEURL);
exit;

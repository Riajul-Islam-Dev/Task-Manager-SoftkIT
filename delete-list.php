<?php

declare(strict_types=1);

// Include modern configuration and classes
require_once 'config/constants.php';
require_once 'config/Database.php';
require_once 'config/Session.php';
require_once 'config/Enums.php';

// Start session
Session::start();

// Check whether the list_id is assigned and valid
if (isset($_GET['list_id']) && is_numeric($_GET['list_id'])) {
    $listId = (int) $_GET['list_id'];
    
    try {
        // First, check if the list exists and get its details
        $listData = Database::fetchOne(
            "SELECT list_id, list_name, (SELECT COUNT(*) FROM tbl_tasks WHERE list_id = ?) as task_count FROM tbl_lists WHERE list_id = ?",
            [$listId, $listId]
        );
        
        if (!$listData) {
            Session::setError('List not found.');
            header('Location: ' . SITEURL . 'manage-list.php');
            exit;
        }
        
        // Check if list has tasks
        if ($listData['task_count'] > 0) {
            Session::setWarning("Cannot delete list '{$listData['list_name']}' because it contains {$listData['task_count']} task(s). Please delete all tasks first.");
            header('Location: ' . SITEURL . 'manage-list.php');
            exit;
        }
        
        // Begin transaction for safe deletion
        Database::beginTransaction();
        
        try {
            // Delete the list from database
            $deleteResult = Database::execute(
                "DELETE FROM tbl_lists WHERE list_id = ?",
                [$listId]
            );
            
            if ($deleteResult) {
                // Commit the transaction
                Database::commit();
                
                // Log the deletion event
                error_log("List deleted successfully: ID {$listId}, Name: {$listData['list_name']}");
                
                Session::setSuccess("List '{$listData['list_name']}' deleted successfully!");
            } else {
                throw new Exception('Failed to delete list from database');
            }
        } catch (Exception $e) {
            // Rollback the transaction
            Database::rollback();
            throw $e;
        }
        
    } catch (Exception $e) {
        error_log('Error deleting list: ' . $e->getMessage());
        Session::setError('An error occurred while deleting the list. Please try again.');
    }
} else {
    Session::setError('Invalid list ID provided.');
}

// Redirect to Manage List Page
header('Location: ' . SITEURL . 'manage-list.php');
exit;

<?php

declare(strict_types=1);

// Include modern configuration and classes
require_once 'config/constants.php';
require_once 'config/Database.php';
require_once 'config/Session.php';
require_once 'config/Enums.php';

/**
 * Validate and sanitize list input data
 */
function validateListInput(array $data): array
{
    $errors = [];
    $cleaned = [];
    
    // Validate list name
    $listName = trim($data['list_name'] ?? '');
    if (empty($listName)) {
        $errors[] = 'List name is required';
    } elseif (strlen($listName) > 100) {
        $errors[] = 'List name must be less than 100 characters';
    } elseif (preg_match('/[<>"\'\/]/', $listName)) {
        $errors[] = 'List name contains invalid characters';
    } else {
        $cleaned['list_name'] = htmlspecialchars($listName, ENT_QUOTES, 'UTF-8');
    }
    
    // Validate list description (optional)
    $listDescription = trim($data['list_description'] ?? '');
    if (strlen($listDescription) > 500) {
        $errors[] = 'List description must be less than 500 characters';
    } else {
        $cleaned['list_description'] = htmlspecialchars($listDescription, ENT_QUOTES, 'UTF-8');
    }
    
    return ['errors' => $errors, 'data' => $cleaned];
}

// Start session
Session::start();

// Check whether the form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit'])) {
    try {
        // Verify CSRF token
        if (!Session::verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            throw new InvalidArgumentException('Invalid security token. Please try again.');
        }
        
        // Validate input
        $validation = validateListInput($_POST);
        
        if (!empty($validation['errors'])) {
            Session::setError(implode('. ', $validation['errors']));
        } else {
            $data = $validation['data'];
            
            // Check if list name already exists
            $existingList = Database::fetchOne(
                "SELECT list_id FROM tbl_lists WHERE list_name = ?",
                [$data['list_name']]
            );
            
            if ($existingList) {
                Session::setError('A list with this name already exists. Please choose a different name.');
            } else {
                // Insert new list
                $sql = "INSERT INTO tbl_lists (list_name, list_description) VALUES (?, ?)";
                $params = [$data['list_name'], $data['list_description']];
                
                if (Database::execute($sql, $params)) {
                    Session::setSuccess('List added successfully!');
                    header('Location: ' . SITEURL . 'manage-list.php');
                    exit;
                } else {
                    Session::setError('Failed to add list. Please try again.');
                }
            }
        }
    } catch (Exception $e) {
        error_log('Error adding list: ' . $e->getMessage());
        Session::setError('An error occurred while adding the list. Please try again.');
    }
    
    // Redirect to prevent form resubmission
    header('Location: ' . SITEURL . 'add-list.php');
    exit;
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
                            // Display flash messages using modern Session class
                            if (Session::hasFlashMessages()) {
                                $messages = Session::getFlashMessages();
                                foreach ($messages as $message) {
                                    $alertClass = $message['type']->getAlertClass();
                                    $iconClass = $message['type']->getIconClass();
                                    $messageText = htmlspecialchars($message['message'], ENT_QUOTES, 'UTF-8');
                                    
                                    echo "<div class='alert {$alertClass} alert-dismissible fade show' role='alert'>";
                                    echo "<i class='{$iconClass} me-2'></i>{$messageText}";
                                    echo '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
                                    echo '</div>';
                                }
                            }
                        ?>

                        <form method="POST" novalidate>
                            <!-- CSRF Token -->
                            <input type="hidden" name="csrf_token" value="<?php echo Session::getCsrfToken(); ?>">
                            
                            <div class="mb-3">
                                <label for="list_name" class="form-label">
                                    <i class="fas fa-list me-1"></i>List Name <span class="text-danger">*</span>
                                </label>
                                <input type="text" 
                                       id="list_name" 
                                       name="list_name" 
                                       class="form-control" 
                                       placeholder="Enter a descriptive name for your list" 
                                       required 
                                       maxlength="100"
                                       value="<?php echo htmlspecialchars($_POST['list_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" />
                                <div class="form-text">
                                    <small><i class="fas fa-info-circle me-1"></i>Choose a unique name that describes your list's purpose</small>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label for="list_description" class="form-label">
                                    <i class="fas fa-align-left me-1"></i>List Description
                                </label>
                                <textarea id="list_description" 
                                          name="list_description" 
                                          class="form-control" 
                                          rows="4" 
                                          maxlength="500"
                                          placeholder="Provide additional details about this list (optional)"><?php echo htmlspecialchars($_POST['list_description'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
                                <div class="form-text">
                                    <small><i class="fas fa-info-circle me-1"></i>Optional: Add more context about what this list will contain</small>
                                </div>
                            </div>

                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <a href="<?php echo SITEURL; ?>manage-list.php" class="btn btn-secondary me-md-2">
                                    <i class="fas fa-times me-1"></i>Cancel
                                </a>
                                <button type="submit" class="btn btn-primary" name="submit">
                                    <i class="fas fa-plus me-1"></i>Create List
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Form validation and enhancement
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('form');
            const listNameInput = document.getElementById('list_name');
            const listDescInput = document.getElementById('list_description');
            
            // Real-time validation for list name
            listNameInput.addEventListener('input', function() {
                const value = this.value.trim();
                const invalidChars = /[<>"'\/]/;
                
                if (value.length > 100) {
                    this.setCustomValidity('List name must be less than 100 characters');
                } else if (invalidChars.test(value)) {
                    this.setCustomValidity('List name contains invalid characters');
                } else if (value.length === 0) {
                    this.setCustomValidity('List name is required');
                } else {
                    this.setCustomValidity('');
                }
            });
            
            // Character counter for description
            listDescInput.addEventListener('input', function() {
                const remaining = 500 - this.value.length;
                let counterEl = document.getElementById('desc-counter');
                
                if (!counterEl) {
                    counterEl = document.createElement('small');
                    counterEl.id = 'desc-counter';
                    counterEl.className = 'form-text';
                    this.parentNode.appendChild(counterEl);
                }
                
                counterEl.textContent = `${remaining} characters remaining`;
                counterEl.className = remaining < 50 ? 'form-text text-warning' : 'form-text';
                
                if (this.value.length > 500) {
                    this.setCustomValidity('Description must be less than 500 characters');
                } else {
                    this.setCustomValidity('');
                }
            });
            
            // Form submission handling
            form.addEventListener('submit', function(e) {
                const submitBtn = this.querySelector('button[type="submit"]');
                
                if (this.checkValidity()) {
                    // Show loading state
                    submitBtn.disabled = true;
                    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Creating...';
                }
            });
        });
    </script>


</body>

</html>
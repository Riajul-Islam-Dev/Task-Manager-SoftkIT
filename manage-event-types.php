<?php

declare(strict_types=1);

// Include configuration and classes
require_once 'config/constants.php';
require_once 'config/Database.php';
require_once 'config/Session.php';
require_once 'config/EventTypeService.php';

// Start session
Session::start();

/**
 * Validates event type input data
 */
function validateEventTypeInput(array $data, bool $isUpdate = false): array
{
    $errors = [];
    $cleaned = [];

    // Validate type code (only required for new event types)
    if (!$isUpdate) {
        $typeCode = trim($data['type_code'] ?? '');
        if (empty($typeCode)) {
            $errors[] = 'Type code is required.';
        } elseif (!preg_match('/^[a-z0-9_-]+$/', $typeCode)) {
            $errors[] = 'Type code can only contain lowercase letters, numbers, hyphens, and underscores.';
        } elseif (strlen($typeCode) > 50) {
            $errors[] = 'Type code must be 50 characters or less.';
        } else {
            $cleaned['type_code'] = $typeCode;
        }
    }

    // Validate type name
    $typeName = trim($data['type_name'] ?? '');
    if (empty($typeName)) {
        $errors[] = 'Type name is required.';
    } elseif (strlen($typeName) > 100) {
        $errors[] = 'Type name must be 100 characters or less.';
    } else {
        $cleaned['type_name'] = $typeName;
    }

    // Validate type color
    $typeColor = trim($data['type_color'] ?? '#007bff');
    if (!preg_match('/^#[0-9a-fA-F]{6}$/', $typeColor)) {
        $errors[] = 'Please enter a valid hex color code (e.g., #007bff).';
    } else {
        $cleaned['type_color'] = $typeColor;
    }

    // Validate sort order
    $sortOrder = (int)($data['sort_order'] ?? 0);
    $cleaned['sort_order'] = $sortOrder;

    // Validate active status
    $isActive = isset($data['is_active']) && $data['is_active'] === '1';
    $cleaned['is_active'] = $isActive;

    return [
        'errors' => $errors,
        'data' => $cleaned
    ];
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Verify CSRF token
        if (!Session::verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            Session::setError('Invalid security token. Please try again.');
            header('Location: manage-event-types.php');
            exit;
        }

        if (isset($_POST['add_event_type'])) {
            $validation = validateEventTypeInput($_POST);
            $validationErrors = $validation['errors'];

            if (!empty($validationErrors)) {
                Session::setError(implode(' ', $validationErrors));
            } else {
                $data = $validation['data'];

                // Check if type code already exists
                $existing = EventTypeService::getEventTypeByCode($data['type_code']);
                if ($existing) {
                    Session::setError('Event type code already exists.');
                } else {
                    $result = EventTypeService::addEventType(
                        $data['type_code'],
                        $data['type_name'],
                        $data['type_color'],
                        $data['sort_order']
                    );

                    if ($result) {
                        Session::setSuccess('Event type added successfully!');
                    } else {
                        Session::setError('Failed to add event type.');
                    }
                }
            }

            header('Location: manage-event-types.php');
            exit;
        }

        if (isset($_POST['update_event_type'])) {
            $eventTypeId = (int)($_POST['event_type_id'] ?? 0);
            $validation = validateEventTypeInput($_POST, true);
            $validationErrors = $validation['errors'];

            if ($eventTypeId <= 0) {
                Session::setError('Invalid event type ID.');
            } elseif (!empty($validationErrors)) {
                Session::setError(implode(' ', $validationErrors));
            } else {
                $data = $validation['data'];

                $result = EventTypeService::updateEventType(
                    $eventTypeId,
                    $data['type_name'],
                    $data['type_color'],
                    $data['sort_order'],
                    $data['is_active']
                );

                if ($result) {
                    Session::setSuccess('Event type updated successfully!');
                } else {
                    Session::setError('Failed to update event type.');
                }
            }

            header('Location: manage-event-types.php');
            exit;
        }

        if (isset($_POST['deactivate_event_type'])) {
            $eventTypeId = (int)($_POST['event_type_id'] ?? 0);

            if ($eventTypeId <= 0) {
                Session::setError('Invalid event type ID.');
            } else {
                $result = EventTypeService::deactivateEventType($eventTypeId);

                if ($result) {
                    Session::setSuccess('Event type deactivated successfully!');
                } else {
                    Session::setError('Failed to deactivate event type.');
                }
            }

            header('Location: manage-event-types.php');
            exit;
        }
    } catch (Exception $e) {
        error_log('Event type management error: ' . $e->getMessage());
        Session::setError('An error occurred. Please try again.');
        header('Location: manage-event-types.php');
        exit;
    }
}

// Get all event types (including inactive ones for management)
try {
    $allEventTypes = Database::fetchAll(
        "SELECT * FROM tbl_event_types ORDER BY sort_order ASC, type_name ASC"
    );
} catch (Exception $e) {
    error_log('Error fetching event types: ' . $e->getMessage());
    $allEventTypes = [];
}

// Get all lists for navigation
try {
    $lists = Database::fetchAll("SELECT * FROM tbl_lists ORDER BY list_id ASC");
} catch (Exception $e) {
    error_log('Error fetching lists: ' . $e->getMessage());
    $lists = [];
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Event Types - Task Manager - SoftkIT</title>
    <link href="assets/img/favicon.png" rel="icon">
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="css/all.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
    <!-- SweetAlert2 CSS -->
    <link href="css/sweetalert2.min.css" rel="stylesheet">
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

        .table-dark {
            background-color: #21262d;
            border-color: #30363d;
        }

        .table-hover tbody tr:hover {
            background-color: #262c36;
        }

        .btn-primary {
            background-color: #238636;
            border-color: #238636;
        }

        .btn-primary:hover {
            background-color: #2ea043;
            border-color: #2ea043;
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

        .alert-warning {
            background-color: #332b00;
            border-color: #d29922;
            color: #f2cc60;
        }

        .alert-info {
            background-color: #0c2d48;
            border-color: #1f6feb;
            color: #79c0ff;
        }

        .btn-outline-primary {
            color: #58a6ff;
            border-color: #30363d;
        }

        .btn-outline-primary:hover {
            background-color: #1f6feb;
            border-color: #1f6feb;
            color: #ffffff;
        }

        .btn-outline-warning {
            color: #d29922;
            border-color: #30363d;
        }

        .btn-outline-warning:hover {
            background-color: #d29922;
            border-color: #d29922;
            color: #000000;
        }

        .color-preview {
            display: inline-block;
            width: 20px;
            height: 20px;
            border-radius: 3px;
            border: 1px solid #30363d;
            vertical-align: middle;
        }

        .table-dark td,
        .table-dark th {
            border-color: #30363d;
        }

        .modal-content {
            background-color: #161b22;
            border: 1px solid #30363d;
        }

        .modal-header {
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

        .color-preview {
            width: 30px;
            height: 30px;
            border-radius: 4px;
            border: 1px solid #30363d;
            display: inline-block;
        }
    </style>
</head>

<body>
    <nav class="navbar navbar-expand-lg navbar-dark mb-4" style="background-color: #21262d; border-bottom: 1px solid #30363d;">
        <div class="container">
            <a class="navbar-brand" href="<?php echo SITEURL; ?>">
                <i class="fas fa-tasks me-2"></i>Task Manager - SoftkIT
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo SITEURL; ?>">Home</a>
                    </li>

                    <?php
                    try {
                        // Query to get all lists using modern Database class
                        $navLists = Database::fetchAll(
                            "SELECT list_id, list_name FROM tbl_lists ORDER BY list_name ASC"
                        );

                        foreach ($navLists as $list) {
                            $listId = (int) $list['list_id'];
                            $listName = htmlspecialchars($list['list_name'], ENT_QUOTES, 'UTF-8');
                            echo "<li class='nav-item'>";
                            echo "<a class='nav-link' href='" . SITEURL . "list-task.php?list_id={$listId}'>{$listName}</a>";
                            echo "</li>";
                        }
                    } catch (Exception $e) {
                        error_log("Error fetching lists for navigation: " . $e->getMessage());
                    }
                    ?>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo SITEURL; ?>manage-list.php">
                            <i class="fas fa-cog me-1"></i>Manage Lists
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo SITEURL; ?>calendar.php">
                            <i class="fas fa-calendar me-1"></i>Calendar
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="manage-event-types.php">
                            <i class="fas fa-tags me-1"></i>Event Types
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container">
        <!-- Flash Messages -->
        <?php if (Session::hasFlashMessages()): ?>
            <?php foreach (Session::getFlashMessages() as $flash): ?>
                <?php
                $alertClass = match ($flash['type']) {
                    AlertType::SUCCESS => 'alert-success',
                    AlertType::ERROR => 'alert-danger',
                    AlertType::WARNING => 'alert-warning',
                    AlertType::INFO => 'alert-info',
                    default => 'alert-info'
                };
                $icon = match ($flash['type']) {
                    AlertType::SUCCESS => 'fas fa-check-circle',
                    AlertType::ERROR => 'fas fa-exclamation-circle',
                    AlertType::WARNING => 'fas fa-exclamation-triangle',
                    AlertType::INFO => 'fas fa-info-circle',
                    default => 'fas fa-info-circle'
                };
                ?>
                <div class="alert <?= $alertClass ?> alert-dismissible fade show" role="alert">
                    <i class="<?= $icon ?> me-2"></i>
                    <?= htmlspecialchars($flash['message']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="fas fa-tags me-2"></i>Manage Event Types
                </h5>
                <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addEventTypeModal">
                    <i class="fas fa-plus me-1"></i>Add Event Type
                </button>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-dark table-hover mb-0">
                        <thead>
                            <tr>
                                <th class="border-0">Type Code</th>
                                <th class="border-0">Type Name</th>
                                <th class="border-0">Color</th>
                                <th class="border-0">Sort Order</th>
                                <th class="border-0">Status</th>
                                <th class="border-0 text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($allEventTypes)): ?>
                                <tr>
                                    <td colspan="6" class="text-center py-5">
                                        <div class="text-muted">
                                            <i class="fas fa-tags fa-3x mb-3 opacity-50"></i>
                                            <h6>No event types found</h6>
                                            <p class="mb-3">Get started by creating your first event type.</p>
                                            <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addEventTypeModal">
                                                <i class="fas fa-plus me-1"></i>Add Event Type
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($allEventTypes as $eventType): ?>
                                    <tr>
                                        <td>
                                            <code class="text-info"><?= htmlspecialchars($eventType['type_code']) ?></code>
                                        </td>
                                        <td>
                                            <strong><?= htmlspecialchars($eventType['type_name']) ?></strong>
                                        </td>
                                        <td>
                                            <span class="color-preview" style="background-color: <?= htmlspecialchars($eventType['type_color']) ?>"></span>
                                            <small class="ms-2"><?= htmlspecialchars($eventType['type_color']) ?></small>
                                        </td>
                                        <td><?= $eventType['sort_order'] ?></td>
                                        <td>
                                            <?php if ($eventType['is_active']): ?>
                                                <span class="badge bg-success"><i class="fas fa-check me-1"></i>Active</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary"><i class="fas fa-times me-1"></i>Inactive</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end">
                                            <div class="btn-group btn-group-sm" role="group">
                                                <button type="button" class="btn btn-outline-primary"
                                                    onclick="editEventType(<?= htmlspecialchars(json_encode($eventType)) ?>)"
                                                    data-bs-toggle="tooltip" title="Edit event type">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <?php if ($eventType['is_active']): ?>
                                                    <form method="POST" class="d-inline"
                                                        onsubmit="return confirm('Are you sure you want to deactivate this event type?')">
                                                        <input type="hidden" name="csrf_token" value="<?= Session::getCsrfToken() ?>">
                                                        <input type="hidden" name="event_type_id" value="<?= $eventType['event_type_id'] ?>">
                                                        <button type="submit" name="deactivate_event_type" class="btn btn-outline-warning"
                                                            data-bs-toggle="tooltip" title="Deactivate event type">
                                                            <i class="fas fa-ban"></i>
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Event Type Modal -->
    <div class="modal fade" id="addEventTypeModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-plus me-2"></i>Add Event Type</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?= Session::getCsrfToken() ?>">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="type_code" class="form-label">Type Code *</label>
                            <input type="text" class="form-control" id="type_code" name="type_code" required
                                pattern="[a-z0-9_-]+" maxlength="50">
                            <div>Lowercase letters, numbers, hyphens, and underscores only.</div>
                        </div>
                        <div class="mb-3">
                            <label for="type_name" class="form-label">Type Name *</label>
                            <input type="text" class="form-control" id="type_name" name="type_name" required maxlength="100">
                        </div>
                        <div class="mb-3">
                            <label for="type_color" class="form-label">Color</label>
                            <input type="color" class="form-control form-control-color" id="type_color" name="type_color" value="#007bff">
                        </div>
                        <div class="mb-3">
                            <label for="sort_order" class="form-label">Sort Order</label>
                            <input type="number" class="form-control" id="sort_order" name="sort_order" value="0" min="0">
                            <div>Lower numbers appear first in dropdowns.</div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="add_event_type" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i>Add Event Type
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Event Type Modal -->
    <div class="modal fade" id="editEventTypeModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-edit me-2"></i>Edit Event Type</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="editEventTypeForm">
                    <input type="hidden" name="csrf_token" value="<?= Session::getCsrfToken() ?>">
                    <input type="hidden" name="event_type_id" id="edit_event_type_id">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="edit_type_code" class="form-label">Type Code</label>
                            <input type="text" class="form-control" id="edit_type_code" readonly>
                            <div>Type code cannot be changed after creation.</div>
                        </div>
                        <div class="mb-3">
                            <label for="edit_type_name" class="form-label">Type Name *</label>
                            <input type="text" class="form-control" id="edit_type_name" name="type_name" required maxlength="100">
                        </div>
                        <div class="mb-3">
                            <label for="edit_type_color" class="form-label">Color</label>
                            <input type="color" class="form-control form-control-color" id="edit_type_color" name="type_color">
                        </div>
                        <div class="mb-3">
                            <label for="edit_sort_order" class="form-label">Sort Order</label>
                            <input type="number" class="form-control" id="edit_sort_order" name="sort_order" min="0">
                        </div>
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="edit_is_active" name="is_active" value="1">
                                <label class="form-check-label" for="edit_is_active">
                                    Active
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="update_event_type" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i>Update Event Type
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS -->
    <script src="js/bootstrap.bundle.min.js"></script>
    <!-- SweetAlert2 JS -->
    <script src="js/sweetalert2.all.min.js"></script>

    <script>
        // Initialize Bootstrap tooltips
        document.addEventListener('DOMContentLoaded', function() {
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            var tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
        });

        function editEventType(eventType) {
            document.getElementById('edit_event_type_id').value = eventType.event_type_id;
            document.getElementById('edit_type_code').value = eventType.type_code;
            document.getElementById('edit_type_name').value = eventType.type_name;
            document.getElementById('edit_type_color').value = eventType.type_color;
            document.getElementById('edit_sort_order').value = eventType.sort_order;
            document.getElementById('edit_is_active').checked = eventType.is_active == 1;

            var editModal = new bootstrap.Modal(document.getElementById('editEventTypeModal'));
            editModal.show();
        }
    </script>
</body>

</html>
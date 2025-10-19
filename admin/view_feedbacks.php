<?php
require_once '../includes/auth.php';
require_once '../includes/functions.php';
require_once '../config/database.php';

// Restrict access to admins only
redirectIfNotLoggedIn();
if (!isAdmin()) {
    header("Location: ../index.php");
    exit();
}

// Initialize CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("Invalid request");
    }

    $id = intval($_POST['id']);
    
    if (isset($_POST['mark_read'])) {
        $stmt = $conn->prepare("UPDATE contacts SET status = 'read' WHERE id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $_SESSION['message'] = "Feedback marked as read";
            $_SESSION['message_type'] = "success";
        }
    } 
    elseif (isset($_POST['mark_replied'])) {
        $stmt = $conn->prepare("UPDATE contacts SET status = 'replied' WHERE id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $_SESSION['message'] = "Feedback marked as replied";
            $_SESSION['message_type'] = "success";
        }
    }
    elseif (isset($_POST['delete'])) {
        $stmt = $conn->prepare("DELETE FROM contacts WHERE id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $_SESSION['message'] = "Feedback deleted successfully";
            $_SESSION['message_type'] = "success";
        }
    }
    
    header("Location: view_feedbacks.php");
    exit();
}

// Get all feedbacks from contacts table
$feedbacks = $conn->query("
    SELECT * FROM contacts 
    ORDER BY created_at DESC
")->fetch_all(MYSQLI_ASSOC);

$title = "Manage Feedbacks";
include_once '../includes/header.php';
?>

<div class="container">
    <h2 class="my-4">View Feedbacks</h2>
    
    <?php if (isset($_SESSION['message'])): ?>
        <div class="alert alert-<?php echo $_SESSION['message_type']; ?> alert-dismissible fade show">
            <?php echo $_SESSION['message']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['message'], $_SESSION['message_type']); ?>
    <?php endif; ?>
    
    <div class="card shadow-sm">
        <div class="card-body">
            <?php if (!empty($feedbacks)): ?>
                <style>
                    .message-container {
                        max-height: 120px;
                        overflow: hidden;
                        position: relative;
                    }
                    .message-container.expanded {
                        max-height: none;
                    }
                    .message-content {
                        white-space: pre-wrap;
                        word-break: break-word;
                        font-family: inherit;
                        margin-bottom: 0;
                    }
                    .expand-toggle {
                        position: absolute;
                        right: 0;
                        bottom: 0;
                        background: linear-gradient(to right, transparent, white 50%);
                        padding-left: 20px;
                        color: #0d6efd;
                        cursor: pointer;
                    }
                    .expand-toggle:hover {
                        text-decoration: underline;
                    }
                    tr.expanded .message-container {
                        max-height: none;
                    }
                    tr.expanded .expand-toggle {
                        display: none;
                    }
                </style>
                
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Subject</th>
                                <th style="width: 30%">Message</th>
                                <th>Date</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($feedbacks as $feedback): ?>
                                <tr id="row-<?php echo $feedback['id']; ?>">
                                    <td><?php echo $feedback['id']; ?></td>
                                    <td><?php echo htmlspecialchars($feedback['name']); ?></td>
                                    <td>
                                        <a href="mailto:<?php echo htmlspecialchars($feedback['email']); ?>">
                                            <?php echo htmlspecialchars($feedback['email']); ?>
                                        </a>
                                    </td>
                                    <td><?php echo htmlspecialchars($feedback['subject']); ?></td>
                                    <td>
                                        <div class="message-container" id="message-<?php echo $feedback['id']; ?>">
                                            <p class="message-content"><?php echo htmlspecialchars($feedback['message']); ?></p>
                                            <?php if (substr_count($feedback['message'], "\n") > 3 || strlen($feedback['message']) > 200): ?>
                                                <span class="expand-toggle" onclick="toggleExpand(<?php echo $feedback['id']; ?>)">
                                                    Show more
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($feedback['created_at'])); ?></td>
                                    <td>
                                        <?php 
                                        $badgeClass = [
                                            'new' => 'bg-primary',
                                            'read' => 'bg-info',
                                            'replied' => 'bg-success'
                                        ][$feedback['status']] ?? 'bg-secondary';
                                        ?>
                                        <span class="badge <?php echo $badgeClass; ?>">
                                            <?php echo ucfirst($feedback['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="d-flex gap-2">
                                            <?php if ($feedback['status'] !== 'read'): ?>
                                                <form method="POST">
                                                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                                    <input type="hidden" name="id" value="<?php echo $feedback['id']; ?>">
                                                    <button type="submit" name="mark_read" class="btn btn-sm btn-primary" title="Mark as read">
                                                        <i class="bi bi-check-circle"></i>
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                            
                                            <?php if ($feedback['status'] !== 'replied'): ?>
                                                <form method="POST">
                                                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                                    <input type="hidden" name="id" value="<?php echo $feedback['id']; ?>">
                                                    <button type="submit" name="mark_replied" class="btn btn-sm btn-success" title="Mark as replied">
                                                        <i class="bi bi-reply"></i>
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                            
                                            <form method="POST" onsubmit="return confirm('Are you sure you want to delete this feedback?');">
                                                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                                <input type="hidden" name="id" value="<?php echo $feedback['id']; ?>">
                                                <button type="submit" name="delete" class="btn btn-sm btn-danger" title="Delete">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <script>
                    function toggleExpand(id) {
                        const row = document.getElementById('row-' + id);
                        const messageContainer = document.getElementById('message-' + id);
                        
                        row.classList.toggle('expanded');
                        messageContainer.classList.toggle('expanded');
                        
                        const toggle = messageContainer.querySelector('.expand-toggle');
                        if (toggle) {
                            toggle.textContent = row.classList.contains('expanded') ? 'Show less' : 'Show more';
                        }
                    }
                </script>
            <?php else: ?>
                <div class="alert alert-info text-center py-4">
                    <i class="bi bi-chat-square-text fs-4"></i>
                    <p class="mt-2 mb-0">No feedbacks found in the system</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include_once '../includes/footer.php'; ?>
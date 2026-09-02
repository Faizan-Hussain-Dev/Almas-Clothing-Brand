<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['admin_id'])) {
    header('Location: admin_login.php');
    exit();
}

include '../includes/db_connect.php';

// Handle feedback approval/rejection
if (isset($_GET['action']) && isset($_GET['id'])) {
    $feedback_id = (int)$_GET['id'];
    $action = $_GET['action'];
    
    if ($action === 'approve') {
        $sql = "UPDATE feedback SET status = 'approved' WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $feedback_id);
        $stmt->execute();
        $message = "Feedback approved successfully!";
    } elseif ($action === 'disapprove') {
        $sql = "UPDATE feedback SET status = 'disapproved' WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $feedback_id);
        $stmt->execute();
        $message = "Feedback disapproved!";
    } elseif ($action === 'delete') {
        $sql = "DELETE FROM feedback WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $feedback_id);
        $stmt->execute();
        $message = "Feedback deleted successfully!";
    }
    
    header('Location: feedback.php?message=' . urlencode($message));
    exit();
}

// Get feedback with product names
$feedback = [];
$sql = "SELECT f.*, p.name as product_name FROM feedback f LEFT JOIN products p ON f.product_id = p.product_id ORDER BY f.created_at DESC";
$result = $conn->query($sql);
if ($result) {
    while ($item = $result->fetch_assoc()) {
        $feedback[] = $item;
    }
}

// Add status column if it doesn't exist
$conn->query("ALTER TABLE feedback ADD COLUMN IF NOT EXISTS status VARCHAR(20) DEFAULT 'pending'");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Feedback - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="fas fa-comments me-2"></i>Customer Feedback</h2>
            <a href="index.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
            </a>
        </div>
        
        <?php if (isset($_GET['message'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($_GET['message']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if (empty($feedback)): ?>
            <div class="text-center py-5">
                <i class="fas fa-comments" style="font-size: 3rem; color: #ccc;"></i>
                <h5 class="text-muted mt-3">No feedback found</h5>
                <p class="text-muted">Customer feedback will appear here when submitted.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>Customer Name</th>
                            <th>Email</th>
                            <th>Product</th>
                            <th>Rating</th>
                            <th>Message</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($feedback as $item): ?>
                            <tr>
                                <td><?php echo $item['id']; ?></td>
                                <td><?php echo htmlspecialchars($item['user_name']); ?></td>
                                <td><?php echo htmlspecialchars($item['email']); ?></td>
                                <td>
                                    <?php 
                                    if ($item['product_name']) {
                                        echo htmlspecialchars($item['product_name']);
                                    } else {
                                        echo '<span class="text-muted">General</span>';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <?php if ($item['rating']): ?>
                                        <span class="text-warning">
                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                <i class="fas fa-star <?php echo $i <= $item['rating'] ? '' : 'text-muted'; ?>"></i>
                                            <?php endfor; ?>
                                        </span>
                                        <small class="text-muted">(<?php echo $item['rating']; ?>/5)</small>
                                    <?php else: ?>
                                        <span class="text-muted">No rating</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span title="<?php echo htmlspecialchars($item['message']); ?>">
                                        <?php echo strlen($item['message']) > 50 ? 
                                            substr(htmlspecialchars($item['message']), 0, 50) . '...' : 
                                            htmlspecialchars($item['message']); ?>
                                    </span>
                                </td>
                                <td><?php echo date('M j, Y', strtotime($item['created_at'])); ?></td>
                                <td>
                                    <?php 
                                    $status = $item['status'] ?? 'pending';
                                    $badgeClass = $status === 'approved' ? 'bg-success' : 
                                                 ($status === 'disapproved' ? 'bg-danger' : 'bg-warning');
                                    echo '<span class="badge ' . $badgeClass . '">' . ucfirst($status) . '</span>';
                                    ?>
                                </td>
                                <td>
                                    <?php if ($status !== 'approved'): ?>
                                        <a href="?action=approve&id=<?php echo $item['id']; ?>" 
                                           class="btn btn-sm btn-success me-1" title="Approve">
                                            <i class="fas fa-check"></i>
                                        </a>
                                    <?php endif; ?>
                                    
                                    <?php if ($status !== 'disapproved'): ?>
                                        <a href="?action=disapprove&id=<?php echo $item['id']; ?>" 
                                           class="btn btn-sm btn-warning me-1" title="Disapprove">
                                            <i class="fas fa-times"></i>
                                        </a>
                                    <?php endif; ?>
                                    
                                    <a href="?action=delete&id=<?php echo $item['id']; ?>" 
                                       class="btn btn-sm btn-danger" 
                                       onclick="return confirm('Are you sure you want to delete this feedback?')"
                                       title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="mt-3">
                <small class="text-muted">
                    Total: <?php echo count($feedback); ?> feedback(s)
                </small>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

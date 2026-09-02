<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['admin_id'])) {
    header('Location: admin_login.php');
    exit();
}

include '../includes/db_connect.php';

// Handle order status update
$message = '';

if (isset($_POST['update_status'])) {
    $order_id = (int)$_POST['order_id'];
    $status = $_POST['status'];
    
    $sql = "UPDATE orders SET status=? WHERE order_id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $status, $order_id);
    
    if ($stmt->execute()) {
        $message = "Order status updated successfully!";
    } else {
        $message = "Failed to update order status.";
    }
}

// Delete order
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $order_id = (int)$_GET['delete'];
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Delete order items
        $sql = "DELETE FROM order_items WHERE order_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $order_id);
        $stmt->execute();
        
        // Delete order
        $sql = "DELETE FROM orders WHERE order_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $order_id);
        $stmt->execute();
        
        $conn->commit();
        $message = "Order deleted successfully!";
    } catch (Exception $e) {
        $conn->rollback();
        $message = "Failed to delete order.";
    }
}

// Get orders without filtering
$sql = "SELECT o.*, 
               COALESCE(o.customer_name, 'Guest Customer') as customer_name,
               COALESCE(o.customer_email, 'guest@example.com') as customer_email,
               COUNT(oi.id) as item_count
        FROM orders o 
        LEFT JOIN order_items oi ON o.order_id = oi.order_id
        GROUP BY o.order_id 
        ORDER BY o.created_at DESC";

$stmt = $conn->prepare($sql);
$stmt->execute();
$result = $stmt->get_result();

$orders = [];
while ($order = $result->fetch_assoc()) {
    $orders[] = $order;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Orders - Admin Panel</title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/style.css">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        .admin-wrapper {
            display: flex;
            min-height: 100vh;
        }
        
        .admin-sidebar {
            width: 250px;
            background: var(--primary-color);
            min-height: 100vh;
            position: fixed;
            left: 0;
            top: 0;
            z-index: 1000;
        }
        
        .admin-content {
            margin-left: 250px;
            flex: 1;
            padding: 20px;
        }
        
        @media (max-width: 768px) {
            .admin-sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s ease;
            }
            
            .admin-sidebar.show {
                transform: translateX(0);
            }
            
            .admin-content {
                margin-left: 0;
            }
        }
        
        /* Sidebar Navigation Hover Effects */
        .admin-sidebar .nav-link {
            color: rgba(255,255,255,0.8) !important;
            padding: 12px 20px !important;
            border-radius: 5px;
            margin: 2px 10px;
            transition: all 0.3s ease;
            text-decoration: none !important;
        }
        
        .admin-sidebar .nav-link:hover {
            background-color: #ffc107 !important;
            color: #000000 !important;
            transform: translateX(5px);
            text-decoration: none !important;
        }
        
        .admin-sidebar .nav-link.active {
            background-color: transparent !important;
            color: white !important;
            border-left: 3px solid #ffc107;
        }
        
        .admin-sidebar .nav-link.active:hover {
            background-color: #ffc107 !important;
            color: #000000 !important;
            border-left: none;
        }
    </style>
</head>
<body>
    <div class="admin-wrapper">
        <!-- Sidebar -->
        <div class="admin-sidebar">
            <div class="p-4 text-white">
                <h4 class="mb-4">
                    Admin Panel
                </h4>
                
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link text-white" href="index.php">
                            Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-white" href="manage_products.php">
                            Products
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-white" href="manage_categories.php">
                            Categories
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-white active" href="manage_orders.php">
                            Orders
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-white" href="manage_admin.php">
                            Admins
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-white" href="feedback.php">
                            Feedback
                        </a>
                    </li>
                    <li class="nav-item mt-4">
                        <a class="nav-link text-white" href="../index.php">
                            View Website
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-white" href="admin_logout.php">
                            Logout
                        </a>
                    </li>
                </ul>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="admin-content">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>Manage Orders</h2>
            </div>
            
            <?php if (!empty($message)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <!-- Orders Table -->
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Order ID</th>
                                    <th>Customer</th>
                                    <th>Email</th>
                                    <th>Items</th>
                                    <th>Total</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($orders as $order): ?>
                                    <tr>
                                        <td><strong>#<?php echo $order['order_id']; ?></strong></td>
                                        <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                                        <td><?php echo htmlspecialchars($order['customer_email']); ?></td>
                                        <td>
                                            <span class="badge bg-info"><?php echo $order['item_count']; ?> items</span>
                                        </td>
                                        <td><strong>PKR <?php echo number_format($order['total_amount'], 2); ?></strong></td>
                                        <td>
                                            <span class="badge bg-<?php echo $order['status'] == 'pending' ? 'warning' : ($order['status'] == 'shipped' ? 'info' : 'success'); ?>">
                                                <?php echo ucfirst($order['status']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('M j, Y', strtotime($order['created_at'])); ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary" onclick="showOrderDetails(<?php echo $order['order_id']; ?>)">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-secondary" onclick="showStatusModal(<?php echo $order['order_id']; ?>, '<?php echo $order['status']; ?>')">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <a href="?delete=<?php echo $order['order_id']; ?>" 
                                               class="btn btn-sm btn-outline-danger"
                                               onclick="return confirm('Are you sure you want to delete this order?')">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Order Details Modal -->
    <div class="modal fade" id="orderDetailsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Order Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="orderDetailsContent">
                    <!-- Content will be loaded via AJAX -->
                </div>
            </div>
        </div>
    </div>
    
    <!-- Update Status Modal -->
    <div class="modal fade" id="updateStatusModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Update Order Status</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="order_id" id="statusOrderId">
                        <div class="mb-3">
                            <label class="form-label">Order Status</label>
                            <select class="form-select" name="status" id="orderStatus">
                                <option value="pending">Pending</option>
                                <option value="shipped">Shipped</option>
                                <option value="delivered">Delivered</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="update_status" class="btn btn-primary">Update Status</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        function showOrderDetails(orderId) {
            fetch('get_order_details.php?id=' + orderId)
                .then(response => response.text())
                .then(html => {
                    document.getElementById('orderDetailsContent').innerHTML = html;
                    new bootstrap.Modal(document.getElementById('orderDetailsModal')).show();
                })
                .catch(error => {
                    console.error('Error:', error);
                });
        }
        
        function showStatusModal(orderId, currentStatus) {
            document.getElementById('statusOrderId').value = orderId;
            document.getElementById('orderStatus').value = currentStatus;
            new bootstrap.Modal(document.getElementById('updateStatusModal')).show();
        }
    </script>
</body>
</html>

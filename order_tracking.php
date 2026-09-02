<?php
include 'includes/header.php';

$order_details = null;
$error_message = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email'])) {
    $email = trim($_POST['email']);
    
    if (empty($email)) {
        $error_message = "Please enter your email address";
    } else {
        // Find orders by customer email
        $sql = "SELECT o.*, oi.product_id, oi.quantity, oi.price, p.name as product_name
                FROM orders o 
                LEFT JOIN order_items oi ON o.order_id = oi.order_id
                LEFT JOIN products p ON oi.product_id = p.product_id
                WHERE o.customer_email = ? 
                ORDER BY o.created_at DESC";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            // Group results by order
            $order_details = [];
            while ($row = $result->fetch_assoc()) {
                $order_id = $row['order_id'];
                if (!isset($order_details[$order_id])) {
                    $order_details[$order_id] = [
                        'order_id' => $row['order_id'],
                        'total_amount' => $row['total_amount'],
                        'status' => $row['status'],
                        'created_at' => $row['created_at'],
                        'customer_email' => $row['customer_email'],
                        'items' => []
                    ];
                }
                if ($row['product_id']) {
                    $order_details[$order_id]['items'][] = [
                        'product_name' => $row['product_name'],
                        'quantity' => $row['quantity'],
                        'price' => $row['price']
                    ];
                }
            }
        } else {
            $error_message = "No orders found for this email address.";
        }
    }
}
?>

<!-- Order Tracking Section -->
<section class="py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="text-center mb-5">
                    <h1 class="display-5 fw-bold">Track Your Order</h1>
                    <p class="lead text-muted">Enter your email address to view your order history and status</p>
                </div>
                
                <!-- Search Form -->
                <div class="card shadow-sm mb-4">
                    <div class="card-body">
                        <form method="POST" class="d-flex gap-2">
                            <input type="email" 
                                   class="form-control" 
                                   name="email" 
                                   placeholder="Enter your email address" 
                                   required>
                            <button type="submit" class="btn btn-primary">Track Order</button>
                        </form>
                    </div>
                </div>
                
                <?php if ($error_message): ?>
                    <div class="alert alert-danger" role="alert">
                        <?php echo htmlspecialchars($error_message); ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($order_details !== null && count($order_details) > 0): ?>
                    <h3 class="mb-4">Order History</h3>
                    
                    <?php foreach ($order_details as $order): ?>
                        <div class="card shadow-sm mb-4">
                            <div class="card-body">
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <h5 class="card-title">Order #<?php echo $order['order_id']; ?></h5>
                                        <p class="text-muted mb-1">Date: <?php echo date('F j, Y, g:i a', strtotime($order['created_at'])); ?></p>
                                        <p class="mb-0">Total Items: <?php echo count($order['items']); ?></p>
                                    </div>
                                    <div class="col-md-6 text-md-end">
                                        <p class="mb-2">
                                            Status: 
                                            <?php
                                            $status_class = '';
                                            switch($order['status']) {
                                                case 'pending':
                                                    $status_class = 'bg-warning';
                                                    break;
                                                case 'shipped':
                                                    $status_class = 'bg-info';
                                                    break;
                                                case 'delivered':
                                                    $status_class = 'bg-success';
                                                    break;
                                            }
                                            ?>
                                            <span class="badge <?php echo $status_class; ?>"><?php echo ucfirst($order['status']); ?></span>
                                        </p>
                                        <p class="fw-bold mb-0">Total: PKR <?php echo number_format($order['total_amount'], 2); ?></p>
                                    </div>
                                </div>
                                
                                <h6 class="mb-3">Order Items:</h6>
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>Product</th>
                                                <th>Quantity</th>
                                                <th>Price</th>
                                                <th>Subtotal</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($order['items'] as $item): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                                                    <td><?php echo $item['quantity']; ?></td>
                                                    <td>PKR <?php echo number_format($item['price'], 2); ?></td>
                                                    <td>PKR <?php echo number_format($item['quantity'] * $item['price'], 2); ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                
                <?php else: ?>
                    <div class="alert alert-info text-center">
                        <i class="fas fa-info-circle me-2"></i>
                        No orders found for this email address.
                    </div>
                <?php endif; ?>
                
                <div class="text-center mt-4">
                    <a href="index.php" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-2"></i> Back to Home
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

<style>
.order-progress {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 8px;
}

.progress-step {
    opacity: 0.4;
    transition: opacity 0.3s ease;
}

.progress-step.active {
    opacity: 1;
}

.progress-step i {
    font-size: 1.5rem;
    color: var(--accent-color);
    margin-bottom: 8px;
}
</style>

<?php include 'includes/footer.php'; ?>
<?php include('chatbot_embed.php'); ?>

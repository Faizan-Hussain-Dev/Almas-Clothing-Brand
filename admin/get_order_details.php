<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['admin_id'])) {
    exit('Unauthorized access');
}

include '../includes/db_connect.php';

$order_id = (int)$_GET['id'];

// Get order details
$sql = "SELECT o.*, 'Guest Customer' as customer_name, 'guest@example.com' as customer_email
        FROM orders o 
        WHERE o.order_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $order_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['error' => 'Order not found']);
    exit();
}

$order = $result->fetch_assoc();

// Get order items
$sql_items = "SELECT oi.*, p.name as product_name 
              FROM order_items oi 
              JOIN products p ON oi.product_id = p.product_id 
              WHERE oi.order_id = ?";
$stmt_items = $conn->prepare($sql_items);
$stmt_items->bind_param("i", $order_id);
$stmt_items->execute();
$items_result = $stmt_items->get_result();

$items = [];
while ($item = $items_result->fetch_assoc()) {
    $items[] = $item;
}
?>

<div class="order-details">
    <div class="row mb-3">
        <div class="col-md-6">
            <h6>Order Information</h6>
            <p><strong>Order ID:</strong> #<?php echo $order['order_id']; ?></p>
            <p><strong>Date:</strong> <?php echo date('F j, Y, g:i a', strtotime($order['created_at'])); ?></p>
            <p><strong>Status:</strong> 
                <span class="badge bg-<?php echo $order['status'] == 'pending' ? 'warning' : ($order['status'] == 'shipped' ? 'info' : 'success'); ?>">
                    <?php echo ucfirst($order['status']); ?>
                </span>
            </p>
        </div>
        <div class="col-md-6">
            <h6>Customer Information</h6>
            <p><strong>Name:</strong> <?php echo htmlspecialchars($order['customer_name']); ?></p>
            <p><strong>Email:</strong> <?php echo htmlspecialchars($order['customer_email']); ?></p>
        </div>
    </div>
    
    <h6>Order Items</h6>
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
                <?php foreach ($items as $item): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                        <td><?php echo $item['quantity']; ?></td>
                        <td>$<?php echo number_format($item['price'], 2); ?></td>
                        <td>$<?php echo number_format($item['quantity'] * $item['price'], 2); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <th colspan="3">Total:</th>
                    <th class="text-primary">$<?php echo number_format($order['total_amount'], 2); ?></th>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

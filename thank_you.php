<?php
include 'includes/header.php';

// Check if order exists in session
if (!isset($_SESSION['last_order'])) {
    header('Location: index.php');
    exit();
}

$order = $_SESSION['last_order'];

// Send order confirmation email (basic implementation)
$to = $order['customer_email'];
$subject = "Order Confirmation - Almas Clothing Brand";
$message = "
Dear {$order['customer_name']},

Thank you for your order from Almas Clothing Brand!

Order ID: #{$order['order_id']}
Total Amount: PKR {$order['total']}

Order Details:
";

foreach ($order['items'] as $item) {
    $message .= "- {$item['product']['name']} (Qty: {$item['quantity']}) - PKR {$item['subtotal']}\n";
}

$message .= "
We'll process your order and deliver it within 3-5 business days.

Thank you for shopping with us!

Best regards,
{$order['customer_name']}
Email: {$order['customer_email']}
Almas Clothing Brand
";

$headers = "From: noreply@almas.com\r\n";
$headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

// Send email (note: this requires proper mail server configuration)
@mail($to, $subject, $message, $headers);
?>

<!-- Thank You Section -->
<section class="py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="text-center mb-5">
                    <div class="success-icon mb-4">
                        <i class="fas fa-check-circle" style="font-size: 5rem; color: #28a745;"></i>
                    </div>
                    <h1 class="display-4 fw-bold mb-3">Order Confirmed!</h1>
                    <p class="lead text-muted">Thank you for your purchase. Your order has been successfully placed.</p>
                </div>
                
                <!-- Order Details Card -->
                <div class="card shadow-sm mb-4">
                    <div class="card-body">
                        <h4 class="card-title mb-4">Order Details</h4>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <p class="mb-2"><strong>Order ID:</strong> #<?php echo $order['order_id']; ?></p>
                                <p class="mb-2"><strong>Date:</strong> <?php echo date('F j, Y, g:i a'); ?></p>
                                <p class="mb-2"><strong>Status:</strong> <span class="badge bg-warning">Pending</span></p>
                            </div>
                            <div class="col-md-6">
                                <p class="mb-2"><strong>Customer:</strong> <?php echo htmlspecialchars($order['customer_name']); ?></p>
                                <p class="mb-2"><strong>Email:</strong> <?php echo htmlspecialchars($order['customer_email']); ?></p>
                                <p class="mb-2"><strong>Payment:</strong> Cash on Delivery</p>
                            </div>
                        </div>
                        
                        <hr>
                        
                        <h5 class="mb-3">Items Ordered</h5>
                        <div class="table-responsive">
                            <table class="table">
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
                                            <td><?php echo htmlspecialchars($item['product']['name']); ?></td>
                                            <td><?php echo $item['quantity']; ?></td>
                                            <td>PKR <?php echo number_format($item['product']['price'], 2); ?></td>
                                            <td>PKR <?php echo number_format($item['subtotal'], 2); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <th colspan="3">Total:</th>
                                        <th class="text-primary">PKR <?php echo number_format($order['total'], 2); ?></th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
                <!-- Action Buttons -->
                <div class="text-center">
                    <a href="index.php" class="btn btn-primary btn-lg me-3">
                        <i class="fas fa-home me-2"></i> Continue Shopping
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Order Tracking Section -->
<section class="py-5 bg-light">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="text-center mb-4">
                    <h3>Track Your Order</h3>
                    <p class="text-muted">Enter your email to check your order status</p>
                </div>
                
                <div class="row justify-content-center">
                    <div class="col-md-8">
                        <form method="POST" action="order_tracking.php" class="d-flex gap-2">
                            <input type="email" class="form-control" name="email" placeholder="Enter your email address" required>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search me-2"></i> Track Order
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php 
// Clear order from session after displaying
unset($_SESSION['last_order']);
include 'includes/footer.php'; 
?>

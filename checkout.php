<?php
include 'includes/header.php';

// Check if cart is empty
if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
    header('Location: cart.php');
    exit();
}

// Get cart items and total
$cart_items = [];
$total = 0;

$product_ids = array_keys($_SESSION['cart']);
$placeholders = str_repeat('?,', count($product_ids) - 1) . '?';

$sql = "SELECT * FROM products WHERE product_id IN ($placeholders)";
$stmt = $conn->prepare($sql);
$stmt->bind_param(str_repeat('i', count($product_ids)), ...$product_ids);
$stmt->execute();
$result = $stmt->get_result();

while ($product = $result->fetch_assoc()) {
    $quantity = $_SESSION['cart'][$product['product_id']];
    $subtotal = $product['price'] * $quantity;
    $total += $subtotal;
    
    $cart_items[] = [
        'product' => $product,
        'quantity' => $quantity,
        'subtotal' => $subtotal
    ];
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate form data
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    $city = trim($_POST['city']);
    $postal_code = trim($_POST['postal_code']);
    
    $errors = [];
    
    if (empty($name)) $errors[] = "Name is required";
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Valid email is required";
    if (empty($phone)) $errors[] = "Phone number is required";
    if (empty($address)) $errors[] = "Address is required";
    if (empty($city)) $errors[] = "City is required";
    if (empty($postal_code)) $errors[] = "Postal code is required";
    
    if (empty($errors)) {
        // Start transaction
        $conn->begin_transaction();
        
        try {
            // Insert order with customer name and email
            $sql = "INSERT INTO orders (user_id, customer_name, customer_email, total_amount, status) VALUES (NULL, ?, ?, ?, 'pending')";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssd", $name, $email, $total);
            $stmt->execute();
            $order_id = $conn->insert_id;
            
            // Insert order items
            foreach ($cart_items as $item) {
                $sql = "INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("iiid", $order_id, $item['product']['product_id'], $item['quantity'], $item['product']['price']);
                $stmt->execute();
                
                // Update product stock
                $new_stock = $item['product']['stock'] - $item['quantity'];
                $sql = "UPDATE products SET stock = ? WHERE product_id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ii", $new_stock, $item['product']['product_id']);
                $stmt->execute();
            }
            
            // Commit transaction
            $conn->commit();
            
            // Clear cart
            unset($_SESSION['cart']);
            
            // Store order details in session for thank you page
            $_SESSION['last_order'] = [
                'order_id' => $order_id,
                'total' => $total,
                'customer_name' => $name,
                'customer_email' => $email,
                'items' => $cart_items
            ];
            
            // Redirect to thank you page
            header('Location: thank_you.php');
            exit();
            
        } catch (Exception $e) {
            $conn->rollback();
            $error = "An error occurred while processing your order. Please try again.";
        }
    } else {
        $error = implode(", ", $errors);
    }
}
?>

<!-- Checkout Section -->
<section class="py-5">
    <div class="container">
        <h1 class="display-5 fw-bold mb-4">Checkout</h1>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-danger" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <div class="row">
            <!-- Billing Information -->
            <div class="col-lg-8">
                <div class="checkout-section">
                    <h4 class="mb-4">Billing Information</h4>
                    
                    <form method="POST" id="checkout-form">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="name" class="form-label">Full Name *</label>
                                <input type="text" class="form-control" id="name" name="name" required
                                       value="<?php echo isset($_SESSION['user_name']) ? htmlspecialchars($_SESSION['user_name']) : ''; ?>">
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="email" class="form-label">Email Address *</label>
                                <input type="email" class="form-control" id="email" name="email" required
                                       value="<?php echo isset($_SESSION['user_email']) ? htmlspecialchars($_SESSION['user_email']) : ''; ?>">
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="phone" class="form-label">Phone Number *</label>
                                <input type="tel" class="form-control" id="phone" name="phone" required>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="postal_code" class="form-label">Postal Code *</label>
                                <input type="text" class="form-control" id="postal_code" name="postal_code" required>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="address" class="form-label">Street Address *</label>
                            <input type="text" class="form-control" id="address" name="address" required>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="city" class="form-label">City *</label>
                                <input type="text" class="form-control" id="city" name="city" required>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="country" class="form-label">Country</label>
                                <select class="form-select" id="country" name="country">
                                    <option value="Pakistan" selected>Pakistan</option>
                                    <option value="United States">United States</option>
                                    <option value="United Kingdom">United Kingdom</option>
                                    <option value="Canada">Canada</option>
                                    <option value="Australia">Australia</option>
                                </select>
                            </div>
                        </div>
                        
                        <hr class="my-4">
                        
                        <h5 class="mb-3">Payment Method</h5>
                        
                        <div class="payment-methods">
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="radio" name="payment_method" id="cod" value="cod" checked required>
                                <label class="form-check-label" for="cod">
                                    <i class="fas fa-money-bill-wave me-2"></i>
                                    Cash on Delivery (COD)
                                </label>
                                <small class="d-block text-muted mt-1">Pay when you receive your order</small>
                            </div>
                        </div>
                        
                        <div class="mt-4">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-lock me-2"></i> Place Order
                            </button>
                            
                            <a href="cart.php" class="btn btn-outline-secondary ms-2">
                                <i class="fas fa-arrow-left me-2"></i> Back to Cart
                            </a>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Order Summary -->
            <div class="col-lg-4">
                <div class="order-summary sticky-top" style="top: 100px;">
                    <h4 class="mb-4">Order Summary</h4>
                    
                    <!-- Cart Items Summary -->
                    <div class="mb-4">
                        <?php foreach ($cart_items as $item): ?>
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <div>
                                    <h6 class="mb-0"><?php echo htmlspecialchars($item['product']['name']); ?></h6>
                                    <small class="text-muted">Qty: <?php echo $item['quantity']; ?></small>
                                </div>
                                <span>PKR <?php echo number_format($item['subtotal'], 2); ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <hr>
                    
                    <div class="summary-row">
                        <span>Subtotal:</span>
                        <span>PKR <?php echo number_format($total, 2); ?></span>
                    </div>
                    
                    <div class="summary-row">
                        <span>Shipping:</span>
                        <span class="text-success">Free</span>
                    </div>
                    
                    <div class="summary-row">
                        <span>Tax:</span>
                        <span>PKR 0.00</span>
                    </div>
                    
                    <div class="summary-row">
                        <span class="fw-bold">Total:</span>
                        <span class="fw-bold text-primary">PKR <?php echo number_format($total, 2); ?></span>
                    </div>
                    
                    <div class="alert alert-info mt-3">
                        <i class="fas fa-info-circle me-2"></i>
                        <small>Your order will be delivered within 3-5 business days.</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>
<?php include('chatbot_embed.php'); ?>

<script>
document.getElementById('checkout-form').addEventListener('submit', function(e) {
    // Show loading state
    const submitBtn = this.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Processing...';
    submitBtn.disabled = true;
    
    // Form will submit normally
});
</script>

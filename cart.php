<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include 'includes/db_connect.php';

// Handle AJAX requests first
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    // Initialize cart if not exists
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
    
    // Add to cart
    if (isset($_POST['product_id'])) {
        $product_id = (int)$_POST['product_id'];
        $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;
        
        // Get product details
        $sql = "SELECT * FROM products WHERE product_id = ? AND stock > 0";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $product = $result->fetch_assoc();
            
            // Check if enough stock
            $current_quantity = isset($_SESSION['cart'][$product_id]) ? $_SESSION['cart'][$product_id] : 0;
            if ($current_quantity + $quantity <= $product['stock']) {
                $_SESSION['cart'][$product_id] = $current_quantity + $quantity;
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Product added to cart',
                    'cart_count' => array_sum($_SESSION['cart'])
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Not enough stock available'
                ]);
            }
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Product not available'
            ]);
        }
        exit();
    }
    
    // Update cart quantity
    if (isset($_POST['update_cart']) && isset($_POST['item_id']) && isset($_POST['quantity'])) {
        $item_id = (int)$_POST['item_id'];
        $quantity = (int)$_POST['quantity'];
        
        if ($quantity > 0) {
            // Check stock
            $sql = "SELECT stock FROM products WHERE product_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $item_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $product = $result->fetch_assoc();
                if ($quantity <= $product['stock']) {
                    $_SESSION['cart'][$item_id] = $quantity;
                    echo json_encode(['success' => true]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Not enough stock']);
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'Product not found']);
            }
        } else {
            unset($_SESSION['cart'][$item_id]);
            echo json_encode(['success' => true]);
        }
        exit();
    }
    
    // Remove from cart
    if (isset($_POST['remove_item']) && isset($_POST['item_id'])) {
        $item_id = (int)$_POST['item_id'];
        unset($_SESSION['cart'][$item_id]);
        echo json_encode(['success' => true]);
        exit();
    }
    
    // Get cart count
    if (isset($_POST['get_cart_count'])) {
        echo json_encode([
            'success' => true,
            'cart_count' => isset($_SESSION['cart']) ? array_sum($_SESSION['cart']) : 0
        ]);
        exit();
    }
}

// Include header for normal page load
include 'includes/header.php';

// Initialize cart if not exists
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Get cart items
$cart_items = [];
$total = 0;

if (!empty($_SESSION['cart'])) {
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
}
?>

<!-- Cart Section -->
<section class="py-5">
    <div class="container">
        <h1 class="display-5 fw-bold mb-4">Shopping Cart</h1>
        
        <?php if (empty($cart_items)): ?>
            <div class="text-center py-5">
                <i class="fas fa-shopping-cart" style="font-size: 4rem; color: #ccc; margin-bottom: 20px;"></i>
                <h3 class="text-muted mb-3">Your cart is empty</h3>
                <p class="text-muted mb-4">Looks like you haven't added anything to your cart yet.</p>
                <a href="shop.php" class="btn btn-primary btn-lg">
                    <i class="fas fa-shopping-bag me-2"></i> Start Shopping
                </a>
            </div>
        <?php else: ?>
            <div class="row">
                <!-- Cart Items -->
                <div class="col-lg-8">
                    <div class="cart-table">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Product</th>
                                        <th>Price</th>
                                        <th>Quantity</th>
                                        <th>Subtotal</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($cart_items as $item): ?>
                                        <tr class="cart-item">
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <img src="assets/images/products/<?php echo htmlspecialchars($item['product']['product_image']); ?>" 
                                                         class="cart-image me-3" 
                                                         alt="<?php echo htmlspecialchars($item['product']['name']); ?>">
                                                    <div>
                                                        <h6 class="mb-0"><?php echo htmlspecialchars($item['product']['name']); ?></h6>
                                                        <small class="text-muted"><?php echo htmlspecialchars($item['product']['description']); ?></small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>PKR <?php echo number_format($item['product']['price'], 2); ?></td>
                                            <td>
                                                <input type="number" 
                                                       class="form-control quantity-input" 
                                                       value="<?php echo $item['quantity']; ?>" 
                                                       min="1" 
                                                       max="<?php echo $item['product']['stock']; ?>"
                                                       onchange="updateCartItem(<?php echo $item['product']['product_id']; ?>, this.value)">
                                            </td>
                                            <td class="fw-bold">PKR <?php echo number_format($item['subtotal'], 2); ?></td>
                                            <td>
                                                <button class="btn btn-sm btn-danger" 
                                                        onclick="removeFromCart(<?php echo $item['product']['product_id']; ?>)">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    <div class="mt-3">
                        <a href="shop.php" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left me-2"></i> Continue Shopping
                        </a>
                    </div>
                </div>
                
                <!-- Order Summary -->
                <div class="col-lg-4">
                    <div class="order-summary">
                        <h4 class="mb-4">Order Summary</h4>
                        
                        <div class="summary-row">
                            <span>Subtotal:</span>
                            <span>PKR <?php echo number_format($total, 2); ?></span>
                        </div>
                        
                        <div class="summary-row">
                            <span>Shipping:</span>
                            <span>Free</span>
                        </div>
                        
                        <div class="summary-row">
                            <span>Tax:</span>
                            <span>PKR 0.00</span>
                        </div>
                        
                        <div class="summary-row">
                            <span>Total:</span>
                            <span>PKR <?php echo number_format($total, 2); ?></span>
                        </div>
                        
                        <div class="d-grid gap-2 mt-4">
                            <a href="checkout.php" class="btn btn-primary btn-lg">
                                <i class="fas fa-credit-card me-2"></i> Proceed to Checkout
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php include 'includes/footer.php'; ?>

<script>
function removeFromCart(productId) {
    if (confirm('Are you sure you want to remove this item from cart?')) {
        const formData = new FormData();
        formData.append('remove_item', 'true');
        formData.append('item_id', productId);
        
        fetch('cart.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Remove the row from table
                const row = document.querySelector(`button[onclick="removeFromCart(${productId})"]`).closest('tr');
                row.remove();
                
                // Update cart count in header
                updateCartCount(0); // Will fetch actual count
                
                // Check if cart is empty and reload if needed
                const remainingItems = document.querySelectorAll('.cart-item');
                if (remainingItems.length === 0) {
                    location.reload();
                } else {
                    // Update totals
                    updateOrderSummary();
                }
            }
        })
        .catch(error => {
            console.error('Error:', error);
        });
    }
}

function updateCartItem(productId, quantity) {
    if (quantity < 1) {
        removeFromCart(productId);
        return;
    }
    
    const formData = new FormData();
    formData.append('update_cart', 'true');
    formData.append('item_id', productId);
    formData.append('quantity', quantity);
    
    fetch('cart.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update the row subtotal
            const row = document.querySelector(`button[onclick="removeFromCart(${productId})"]`).closest('tr');
            const priceCell = row.cells[1]; // Price column
            const price = parseFloat(priceCell.textContent.replace('PKR ', '').replace(',', ''));
            const subtotalCell = row.cells[3]; // Subtotal column
            subtotalCell.textContent = 'PKR ' + (price * quantity).toLocaleString('en-PK', {minimumFractionDigits: 2});
            
            // Update cart count in header
            updateCartCount(0); // Will fetch actual count
            
            // Update totals
            updateOrderSummary();
        } else {
            alert(data.message || 'Failed to update cart');
            location.reload();
        }
    })
        .catch(error => {
            console.error('Error:', error);
            location.reload();
        });
}

function updateCartCount() {
    // Update cart count in header without page reload
    const formData = new FormData();
    formData.append('get_cart_count', 'true');
    
    fetch('cart.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Use the same function as script.js
            const cartLink = document.querySelector('a[href="cart.php"]');
            if (cartLink) {
                let cartBadge = cartLink.querySelector('.badge');
                
                // If badge doesn't exist, create it
                if (!cartBadge) {
                    cartBadge = document.createElement('span');
                    cartBadge.className = 'badge bg-danger ms-1';
                    cartLink.appendChild(cartBadge);
                }
                
                if (data.cart_count > 0) {
                    cartBadge.textContent = data.cart_count;
                    cartBadge.style.display = 'inline-block';
                } else {
                    cartBadge.style.display = 'none';
                }
            }
        }
    })
    .catch(error => {
        console.error('Error:', error);
    });
}

function updateOrderSummary() {
    // Calculate new total from remaining items
    let total = 0;
    const rows = document.querySelectorAll('.cart-item');
    rows.forEach(row => {
        const subtotalCell = row.cells[3]; // Subtotal column
        const subtotal = parseFloat(subtotalCell.textContent.replace('PKR ', '').replace(',', ''));
        total += subtotal;
    });
    
    // Update total in order summary
    const totalElements = document.querySelectorAll('.summary-row span:last-child');
    if (totalElements.length >= 2) {
        totalElements[0].textContent = 'PKR ' + total.toLocaleString('en-PK', {minimumFractionDigits: 2}); // Subtotal
        totalElements[totalElements.length - 1].textContent = 'PKR ' + total.toLocaleString('en-PK', {minimumFractionDigits: 2}); // Total
    }
}
</script>
<?php include('chatbot_embed.php'); ?>
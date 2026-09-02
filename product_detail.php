<?php 
include 'includes/header.php';
include 'includes/db_connect.php';

// Get product ID from URL
$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($product_id <= 0) {
    header('Location: shop.php');
    exit();
}

// Get product details
$sql = "SELECT p.*, c.name as category_name 
        FROM products p 
        JOIN categories c ON p.category_id = c.id 
        WHERE p.product_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $product_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Location: shop.php');
    exit();
}

$product = $result->fetch_assoc();

// Handle review submission
$review_message = '';
$review_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $rating = (int)$_POST['rating'];
    $message = trim($_POST['message']);
    
    $errors = [];
    
    if (empty($name)) $errors[] = "Name is required";
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Valid email is required";
    if (empty($rating) || $rating < 1 || $rating > 5) $errors[] = "Valid rating is required";
    if (empty($message) || strlen($message) < 10) $errors[] = "Review must be at least 10 characters long";
    
    if (empty($errors)) {
        // Insert review into database
        $sql = "INSERT INTO feedback (product_id, user_name, email, rating, message) VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("issis", $product_id, $name, $email, $rating, $message);
        
        if ($stmt->execute()) {
            $review_message = "Thank you for your review! It has been submitted successfully.";
            $review_type = "success";
        } else {
            $review_message = "Sorry, there was an error submitting your review. Please try again.";
            $review_type = "error";
        }
    } else {
        $review_message = implode(", ", $errors);
        $review_type = "error";
    }
}

// Get existing reviews for this product
$reviews = [];
$sql_reviews = "SELECT * FROM feedback WHERE product_id = ? ORDER BY created_at DESC";
$stmt_reviews = $conn->prepare($sql_reviews);
$stmt_reviews->bind_param("i", $product_id);
$stmt_reviews->execute();
$reviews_result = $stmt_reviews->get_result();

if ($reviews_result) {
    while ($review = $reviews_result->fetch_assoc()) {
        $reviews[] = $review;
    }
}

// Calculate average rating
$avg_rating = 0;
$total_ratings = count($reviews);
if ($total_ratings > 0) {
    $rating_sum = array_sum(array_column($reviews, 'rating'));
    $avg_rating = $rating_sum / $total_ratings;
}
?>

<!-- Product Detail Section -->
<section class="py-5">
    <div class="container">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                <li class="breadcrumb-item"><a href="shop.php">Shop</a></li>
                <li class="breadcrumb-item"><a href="shop.php?category=<?php echo $product['category_id']; ?>"><?php echo htmlspecialchars($product['category_name']); ?></a></li>
                <li class="breadcrumb-item active"><?php echo htmlspecialchars($product['name']); ?></li>
            </ol>
        </nav>
        
        <div class="row">
            <!-- Product Image -->
            <div class="col-lg-6 mb-4">
                <div class="product-image-container">
                    <img src="assets/images/products/<?php echo htmlspecialchars($product['product_image']); ?>" 
                         class="img-fluid rounded shadow-sm" 
                         alt="<?php echo htmlspecialchars($product['name']); ?>"
                         id="main-product-image">
                </div>
            </div>
            
            <!-- Product Info -->
            <div class="col-lg-6">
                <div class="product-info">
                    <span class="badge bg-secondary mb-3"><?php echo htmlspecialchars($product['category_name']); ?></span>
                    <h1 class="display-5 fw-bold mb-3"><?php echo htmlspecialchars($product['name']); ?></h1>
                    
                    <div class="product-rating mb-3">
                        <?php if ($total_ratings > 0): ?>
                            <div class="d-flex align-items-center">
                                <div class="text-warning me-2">
                                    <?php 
                                    for ($i = 1; $i <= 5; $i++) {
                                        if ($i <= round($avg_rating)) {
                                            echo '<i class="fas fa-star"></i>';
                                        } else {
                                            echo '<i class="far fa-star"></i>';
                                        }
                                    }
                                    ?>
                                </div>
                                <span class="text-muted"><?php echo number_format($avg_rating, 1); ?> out of 5 (<?php echo $total_ratings; ?> reviews)</span>
                            </div>
                        <?php else: ?>
                            <div class="text-muted">
                                <i class="far fa-star"></i>
                                <i class="far fa-star"></i>
                                <i class="far fa-star"></i>
                                <i class="far fa-star"></i>
                                <i class="far fa-star"></i>
                                <span class="ms-2">No reviews yet</span>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="product-price mb-4">
                        <span class="display-6 fw-bold text-primary">PKR <?php echo number_format($product['price'], 2); ?></span>
                    </div>
                    
                    <div class="product-stock mb-4">
                        <?php if ($product['stock'] > 0): ?>
                            <span class="text-success"><i class="fas fa-check-circle"></i> In Stock (<?php echo $product['stock']; ?> available)</span>
                        <?php else: ?>
                            <span class="text-danger"><i class="fas fa-times-circle"></i> Out of Stock</span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="product-description mb-4">
                        <h5>Description</h5>
                        <p class="text-muted"><?php echo nl2br(htmlspecialchars($product['description'])); ?></p>
                    </div>
                    
                    <div class="product-actions mb-4">
                        <div class="row align-items-center">
                            <div class="col-auto">
                                <label for="quantity" class="form-label">Quantity:</label>
                            </div>
                            <div class="col-auto">
                                <input type="number" id="quantity" class="form-control" value="1" min="1" max="<?php echo $product['stock']; ?>" style="width: 80px;">
                            </div>
                            <div class="col-auto">
                                <button type="button" class="btn btn-outline-info" onclick="toggleSizeChart()">
                                    <i class="fas fa-ruler me-2"></i> Size Chart
                                </button>
                            </div>
                        </div>
                        
                        <!-- Size Chart Image (Hidden by default) -->
                        <div id="sizeChartContainer" class="mt-3" style="display: none;">
                            <div class="card">
                                <div class="card-body">
                                    <h6 class="card-title">Size Chart</h6>
                                    <img src="assets/images/products/size_chart.png" 
                                         class="img-fluid rounded" 
                                         alt="Size Chart">
                                </div>
                            </div>
                        </div>
                        
                        <div class="d-grid gap-2 mt-3">
                            <button onclick="addToCartWithQuantity(<?php echo $product['product_id']; ?>)" 
                                    class="btn btn-primary btn-lg" 
                                    <?php echo $product['stock'] <= 0 ? 'disabled' : ''; ?>>
                                <i class="fas fa-shopping-cart me-2"></i>
                                <?php echo $product['stock'] <= 0 ? 'Out of Stock' : 'Add to Cart'; ?>
                            </button>
                            
                            <a href="shop.php" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left me-2"></i> Continue Shopping
                            </a>
                        </div>
                    </div>
                    
                    <div class="product-features">
                        <h5>Features</h5>
                        <ul class="list-unstyled">
                            <li><i class="fas fa-check text-success me-2"></i> Premium Quality Material</li>
                            <li><i class="fas fa-check text-success me-2"></i> Comfortable Fit</li>
                            <li><i class="fas fa-check text-success me-2"></i> Easy to Wash</li>
                            <li><i class="fas fa-check text-success me-2"></i> Durable Design</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Product Reviews Section -->
<section class="py-5 bg-light">
    <div class="container">
        <div class="row">
            <div class="col-lg-8 mx-auto">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h3 class="mb-0">Customer Reviews</h3>
                    <div class="text-end">
                        <?php if ($total_ratings > 0): ?>
                            <div class="text-warning">
                                <?php 
                                for ($i = 1; $i <= 5; $i++) {
                                    if ($i <= round($avg_rating)) {
                                        echo '<i class="fas fa-star"></i>';
                                    } else {
                                        echo '<i class="far fa-star"></i>';
                                    }
                                }
                                ?>
                            </div>
                            <small class="text-muted"><?php echo number_format($avg_rating, 1); ?> average (<?php echo $total_ratings; ?> reviews)</small>
                        <?php else: ?>
                            <small class="text-muted">No reviews yet</small>
                        <?php endif; ?>
                    </div>
                </div>
                
                <?php if (!empty($review_message)): ?>
                    <div class="alert alert-<?= $review_type === 'success' ? 'success' : 'danger' ?> alert-dismissible fade show" role="alert">
                        <?= htmlspecialchars($review_message) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <!-- Review Form -->
                <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="card-title">Write a Review</h5>
                        <form method="POST" action="">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="name" class="form-label">Your Name *</label>
                                    <input type="text" class="form-control" id="name" name="name" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="email" class="form-label">Email Address *</label>
                                    <input type="email" class="form-control" id="email" name="email" required>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="rating" class="form-label">Rating *</label>
                                <div class="rating-input">
                                    <?php for ($i = 5; $i >= 1; $i--): ?>
                                        <input type="radio" id="star<?= $i ?>" name="rating" value="<?= $i ?>" required>
                                        <label for="star<?= $i ?>" class="star-label">
                                            <i class="fas fa-star"></i>
                                        </label>
                                    <?php endfor; ?>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="message" class="form-label">Your Review *</label>
                                <textarea class="form-control" id="message" name="message" rows="4" placeholder="Share your experience with this product..." required></textarea>
                                <div class="form-text">Minimum 10 characters</div>
                            </div>
                            <button type="submit" name="submit_review" class="btn btn-primary">
                                <i class="fas fa-paper-plane me-2"></i> Submit Review
                            </button>
                        </form>
                    </div>
                </div>
                
                <!-- Reviews List -->
                <?php if (empty($reviews)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-comments" style="font-size: 3rem; color: #ccc; margin-bottom: 20px;"></i>
                        <h5 class="text-muted">No reviews yet</h5>
                        <p class="text-muted">Be the first to review this product!</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($reviews as $review): ?>
                        <div class="card mb-3">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <div>
                                        <h6 class="mb-1"><?= htmlspecialchars($review['user_name']) ?></h6>
                                        <div class="text-warning">
                                            <?php 
                                            for ($i = 1; $i <= 5; $i++) {
                                                if ($i <= $review['rating']) {
                                                    echo '<i class="fas fa-star"></i>';
                                                } else {
                                                    echo '<i class="far fa-star"></i>';
                                                }
                                            }
                                            ?>
                                        </div>
                                    </div>
                                    <small class="text-muted">
                                        <i class="fas fa-clock me-1"></i>
                                        <?= date('M j, Y', strtotime($review['created_at'])) ?>
                                    </small>
                                </div>
                                <p class="card-text"><?= nl2br(htmlspecialchars($review['message'])) ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<style>
.rating-input {
    display: flex;
    flex-direction: row-reverse;
    gap: 5px;
}

.rating-input input[type="radio"] {
    display: none;
}

.rating-input .star-label {
    font-size: 1.5rem;
    color: #ddd;
    cursor: pointer;
    transition: color 0.2s;
}

.rating-input .star-label:hover,
.rating-input .star-label:hover ~ .star-label {
    color: #ffc107;
}

.rating-input input[type="radio"]:checked ~ .star-label {
    color: #ffc107;
}
</style>

<?php include 'includes/footer.php'; ?>

<script>
function toggleSizeChart() {
    const sizeChart = document.getElementById('sizeChartContainer');
    if (sizeChart.style.display === 'none') {
        sizeChart.style.display = 'block';
    } else {
        sizeChart.style.display = 'none';
    }
}

function addToCartWithQuantity(productId) {
    const quantity = document.getElementById('quantity').value;
    
    if (quantity <= 0) {
        showToast('Please enter a valid quantity', 'warning');
        return;
    }
    
    const formData = new FormData();
    formData.append('product_id', productId);
    formData.append('quantity', quantity);
    
    fetch('cart.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('Product added to cart successfully!', 'success');
            updateCartCount(data.cart_count);
        } else {
            showToast(data.message || 'Failed to add product to cart', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('An error occurred. Please try again.', 'error');
    });
}

// Review form submission
document.getElementById('review-form').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const name = document.getElementById('review-name').value;
    const rating = document.getElementById('review-rating').value;
    const message = document.getElementById('review-message').value;
    
    if (!name || !rating || !message) {
        showToast('Please fill in all fields', 'warning');
        return;
    }
    
    // Here you would normally submit to a backend
    showToast('Thank you for your review!', 'success');
    
    // Reset form
    this.reset();
});
</script>

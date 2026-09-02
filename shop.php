<?php 
include 'includes/header.php';
include 'includes/db_connect.php';

// Quick test to check database connection and products
$test_result = $conn->query("SELECT COUNT(*) as count FROM products");
$test_row = $test_result->fetch_assoc();
$total_products = $test_row['count'];
?>
<!-- Shop Header -->
<section class="py-5 bg-light">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-6">
                <h1 class="display-5 fw-bold">Shop</h1>
                <p class="lead text-muted">Browse our complete collection</p>
                <?php if (isset($_GET['debug'])): ?>
                    <div class="alert alert-info">
                        Total Products in Database: <?php echo $total_products; ?>
                    </div>
                <?php endif; ?>
            </div>
            <div class="col-md-6">
                <div class="d-flex gap-2">
                    <input type="text" id="search-input" class="form-control" placeholder="Search products...">
                    <select id="category-filter" class="form-select">
                        <option value="">All Categories</option>
                        <?php
                        $sql = "SELECT * FROM categories ORDER BY name";
                        $result = $conn->query($sql);
                        while($category = $result->fetch_assoc()) {
                            $selected = ($category_id == $category['id']) ? 'selected' : '';
                            echo '<option value="' . $category['id'] . '" ' . $selected . '>' . htmlspecialchars($category['name']) . '</option>';
                        }
                        ?>
                    </select>
                    <select id="sort-select" class="form-select">
                        <option value="name">Sort by Name</option>
                        <option value="price_low">Price: Low to High</option>
                        <option value="price_high">Price: High to Low</option>
                        <option value="newest">Newest First</option>
                    </select>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Products Grid -->
<section class="py-5">
    <div class="container">
        <div class="row" id="product-grid">
            <?php
            // Handle filtering and sorting
            $where_clause = "";
            $order_clause = "ORDER BY p.created_at DESC";
            
            // Category filter (handle both GET and POST)
            $category_id = '';
            if ((isset($_GET['category']) && !empty($_GET['category'])) || (isset($_POST['category']) && !empty($_POST['category']))) {
                $category_id = $conn->real_escape_string(isset($_GET['category']) ? $_GET['category'] : $_POST['category']);
                $where_clause .= " WHERE p.category_id = '$category_id'";
            }
            
            // Search filter
            if (isset($_POST['search']) && !empty($_POST['search'])) {
                $search_term = $conn->real_escape_string($_POST['search']);
                if (empty($where_clause)) {
                    $where_clause .= " WHERE p.name LIKE '%$search_term%' OR p.description LIKE '%$search_term%'";
                } else {
                    $where_clause .= " AND (p.name LIKE '%$search_term%' OR p.description LIKE '%$search_term%')";
                }
            }
            
            // Sorting
            if (isset($_POST['sort'])) {
                switch($_POST['sort']) {
                    case 'name':
                        $order_clause = "ORDER BY p.name ASC";
                        break;
                    case 'price_low':
                        $order_clause = "ORDER BY p.price ASC";
                        break;
                    case 'price_high':
                        $order_clause = "ORDER BY p.price DESC";
                        break;
                    case 'newest':
                        $order_clause = "ORDER BY p.created_at DESC";
                        break;
                }
            }
            
            $sql = "SELECT p.*, c.name as category_name 
                    FROM products p 
                    JOIN categories c ON p.category_id = c.id 
                    $where_clause 
                    $order_clause";
            $result = $conn->query($sql);
            
            // Debug: Show SQL query and result count
            if (isset($_GET['debug'])) {
                echo "<div class='alert alert-info'>";
                echo "SQL: " . $sql . "<br>";
                echo "Results: " . $result->num_rows . "<br>";
                echo "</div>";
            }
            
            if ($result->num_rows > 0) {
                while($product = $result->fetch_assoc()) {
                    echo '<div class="col-lg-4 col-md-6 mb-4">';
                    echo '<div class="product-card">';
                    echo '<div class="image-wrapper">';
                    echo '<img src="assets/images/products/' . htmlspecialchars($product['product_image']) . '" 
                             class="product-image" alt="' . htmlspecialchars($product['name']) . '">';
                    echo '</div>';
                    echo '<div class="product-body">';
                    echo '<span class="badge bg-secondary mb-2">' . htmlspecialchars($product['category_name']) . '</span>';
                    echo '<h5 class="product-title">' . htmlspecialchars($product['name']) . '</h5>';
                    echo '<p class="product-description">' . substr(htmlspecialchars($product['description']), 0, 100) . '...</p>';
                    echo '<div class="d-flex justify-content-between align-items-center mb-3">';
                    echo '<div class="product-price">PKR ' . number_format($product['price'], 2) . '</div>';
                    echo '<small class="text-muted">Stock: ' . $product['stock'] . '</small>';
                    echo '</div>';
                    echo '<div class="d-grid gap-2">';
                    echo '<a href="product_detail.php?id=' . $product['product_id'] . '" 
                           class="btn btn-outline-primary">View Details</a>';
                    echo '<button onclick="addToCart(' . $product['product_id'] . ')" 
                           class="btn btn-primary" ' . ($product['stock'] <= 0 ? 'disabled' : '') . '>'
                         . ($product['stock'] <= 0 ? 'Out of Stock' : 'Add to Cart') . '</button>';
                    echo '</div>';
                    echo '</div>';
                    echo '</div>';
                    echo '</div>';
                }
            } else {
                echo '<div class="col-12 text-center">';
                echo '<div class="py-5">';
                echo '<i class="fas fa-search" style="font-size: 4rem; color: #ccc; margin-bottom: 20px;"></i>';
                echo '<h4 class="text-muted">No products found</h4>';
                echo '<p class="text-muted">Try adjusting your filters or search terms</p>';
                echo '</div>';
                echo '</div>';
            }
            ?>
        </div>
        
        <!-- Pagination (if needed for larger datasets) -->
        <?php
        // For now, we'll show all products. In a real application, you'd implement pagination
        ?>
    </div>
</section>

<!-- Loading state for AJAX requests -->
<div id="loading" class="text-center py-5" style="display: none;">
    <div class="spinner"></div>
    <p class="mt-3">Loading products...</p>
</div>

<?php include 'includes/footer.php'; ?>

<script>
// Handle form submissions for filtering and sorting
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('search-input');
    const categoryFilter = document.getElementById('category-filter');
    const sortSelect = document.getElementById('sort-select');
    const productGrid = document.getElementById('product-grid');
    const loading = document.getElementById('loading');
    
    // Handle URL parameters for category filtering from homepage
    const urlParams = new URLSearchParams(window.location.search);
    const categoryFromUrl = urlParams.get('category');
    
    if (categoryFromUrl) {
        categoryFilter.value = categoryFromUrl;
        // Trigger filtering to show only the selected category products
        performSearch();
    }
    
    function performSearch() {
        loading.style.display = 'block';
        productGrid.style.opacity = '0.5';
        
        const formData = new FormData();
        formData.append('search', searchInput.value);
        formData.append('category', categoryFilter.value);
        formData.append('sort', sortSelect.value);
        
        fetch('shop.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.text())
        .then(html => {
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');
            const newGrid = doc.getElementById('product-grid');
            
            if (newGrid) {
                productGrid.innerHTML = newGrid.innerHTML;
            }
            
            loading.style.display = 'none';
            productGrid.style.opacity = '1';
        })
        .catch(error => {
            console.error('Error:', error);
            loading.style.display = 'none';
            productGrid.style.opacity = '1';
            showToast('An error occurred while searching', 'error');
        });
    }
    
    // Debounced search
    let searchTimeout;
    searchInput.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(performSearch, 500);
    });
    
    categoryFilter.addEventListener('change', performSearch);
    sortSelect.addEventListener('change', performSearch);
});
</script>
<?php include('chatbot_embed.php'); ?>

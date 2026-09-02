<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['admin_id'])) {
    header('Location: admin_login.php');
    exit();
}

include '../includes/db_connect.php';

// Handle product operations
$message = '';

// Check for success message from redirect
if (isset($_GET['success'])) {
    $message = $_GET['success'];
}

// Add product
if (isset($_POST['add_product'])) {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $category_id = (int)$_POST['category_id'];
    $price = (float)$_POST['price'];
    $stock = (int)$_POST['stock'];
    
    // Handle image upload
    $product_image = '';
    if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] == 0) {
        $upload_dir = '../assets/images/products/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file_name = time() . '_' . basename($_FILES['product_image']['name']);
        $target_file = $upload_dir . $file_name;
        
        if (move_uploaded_file($_FILES['product_image']['tmp_name'], $target_file)) {
            $product_image = $file_name;
        }
    }
    
    if (!empty($name) && !empty($description) && $category_id > 0 && $price > 0 && $stock >= 0) {
        $sql = "INSERT INTO products (name, description, category_id, price, stock, product_image) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssidis", $name, $description, $category_id, $price, $stock, $product_image);
        
        if ($stmt->execute()) {
            $message = "Product added successfully!";
            // Redirect to prevent form resubmission on refresh
            header("Location: manage_products.php?success=" . urlencode($message));
            exit();
        } else {
            $message = "Failed to add product.";
        }
    } else {
        $message = "Please fill all required fields.";
    }
}

// Update product
if (isset($_POST['update_product'])) {
    $product_id = (int)$_POST['product_id'];
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $category_id = (int)$_POST['category_id'];
    $price = (float)$_POST['price'];
    $stock = (int)$_POST['stock'];
    
    // Handle image upload
    $product_image = $_POST['existing_image'];
    if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] == 0) {
        $upload_dir = '../assets/images/products/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file_name = time() . '_' . basename($_FILES['product_image']['name']);
        $target_file = $upload_dir . $file_name;
        
        if (move_uploaded_file($_FILES['product_image']['tmp_name'], $target_file)) {
            $product_image = $file_name;
        }
    }
    
    $sql = "UPDATE products SET name=?, description=?, category_id=?, price=?, stock=?, product_image=? WHERE product_id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssidisi", $name, $description, $category_id, $price, $stock, $product_image, $product_id);
    
    if ($stmt->execute()) {
        $message = "Product updated successfully!";
        // Redirect to prevent form resubmission on refresh
        header("Location: manage_products.php?success=" . urlencode($message));
        exit();
    } else {
        $message = "Failed to update product.";
    }
}

// Delete product
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $product_id = (int)$_GET['delete'];
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Delete related feedback first
        $sql = "DELETE FROM feedback WHERE product_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        
        // Delete related order items
        $sql = "DELETE FROM order_items WHERE product_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        
        // Delete the product
        $sql = "DELETE FROM products WHERE product_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        
        $conn->commit();
        $message = "Product deleted successfully!";
        // Redirect to prevent form resubmission on refresh
        header("Location: manage_products.php?success=" . urlencode($message));
        exit();
    } catch (Exception $e) {
        $conn->rollback();
        $message = "Failed to delete product. Please check if the product is referenced in other records.";
    }
}

// Get products
$products = [];
$sql = "SELECT p.*, c.name as category_name FROM products p JOIN categories c ON p.category_id = c.id ORDER BY p.created_at DESC";
$result = $conn->query($sql);

// Debug: Check if query executed and count results
if ($result) {
    $product_count = $result->num_rows;
    while ($product = $result->fetch_assoc()) {
        $products[] = $product;
    }
} else {
    $product_count = 0;
    $error = $conn->error;
}

// Get categories
$categories = [];
$sql = "SELECT * FROM categories ORDER BY name";
$result = $conn->query($sql);
if ($result) {
    while ($category = $result->fetch_assoc()) {
        $categories[] = $category;
    }
}

// Get product for editing
$edit_product = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $product_id = (int)$_GET['edit'];
    $sql = "SELECT * FROM products WHERE product_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $edit_product = $result->fetch_assoc();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Products - Admin Panel</title>
    
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
                        <a class="nav-link text-white active" href="manage_products.php">
                            Products
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-white" href="manage_categories.php">
                            Categories
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-white" href="manage_orders.php">
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
                <h2>Manage Products</h2>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addProductModal">
                    <i class="fas fa-plus me-2"></i> Add Product
                </button>
            </div>
            
            <?php if (!empty($message)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <!-- Products Table -->
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Image</th>
                                    <th>Name</th>
                                    <th>Category</th>
                                    <th>Price</th>
                                    <th>Stock</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($products as $product): ?>
                                    <tr>
                                        <td>
                                            <?php if (!empty($product['product_image'])): ?>
                                                <img src="../assets/images/products/<?php echo htmlspecialchars($product['product_image']); ?>" 
                                                     alt="<?php echo htmlspecialchars($product['name']); ?>" 
                                                     style="width: 50px; height: 50px; object-fit: cover;">
                                            <?php else: ?>
                                                <div style="width: 50px; height: 50px; background: #f0f0f0; display: flex; align-items: center; justify-content: center;">
                                                    <i class="fas fa-image text-muted"></i>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($product['name']); ?></td>
                                        <td><?php echo htmlspecialchars($product['category_name']); ?></td>
                                        <td>PKR <?php echo number_format($product['price'], 2); ?></td>
                                        <td>
                                            <?php if ($product['stock'] < 10): ?>
                                                <span class="badge bg-danger"><?php echo $product['stock']; ?></span>
                                            <?php else: ?>
                                                <span class="badge bg-success"><?php echo $product['stock']; ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <a href="?edit=<?php echo $product['product_id']; ?>" class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="?delete=<?php echo $product['product_id']; ?>" 
                                               class="btn btn-sm btn-outline-danger"
                                               onclick="return confirm('Are you sure you want to delete this product?')">
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
    
    <!-- Add Product Modal -->
    <div class="modal fade" id="addProductModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Product</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" enctype="multipart/form-data">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Product Name</label>
                                <input type="text" class="form-control" name="name" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Category</label>
                                <select class="form-select" name="category_id" required>
                                    <option value="">Select Category</option>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?php echo $category['id']; ?>"><?php echo htmlspecialchars($category['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" name="description" rows="3" required></textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Price ($)</label>
                                <input type="number" class="form-control" name="price" step="0.01" min="0" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Stock</label>
                                <input type="number" class="form-control" name="stock" min="0" required>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Product Image</label>
                            <input type="file" class="form-control" name="product_image" accept="image/*">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="add_product" class="btn btn-primary">Add Product</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Edit Product Modal -->
    <?php if ($edit_product): ?>
    <div class="modal fade" id="editProductModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Product</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" enctype="multipart/form-data">
                    <div class="modal-body">
                        <input type="hidden" name="product_id" value="<?php echo $edit_product['product_id']; ?>">
                        <input type="hidden" name="existing_image" value="<?php echo htmlspecialchars($edit_product['product_image']); ?>">
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Product Name</label>
                                <input type="text" class="form-control" name="name" value="<?php echo htmlspecialchars($edit_product['name']); ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Category</label>
                                <select class="form-select" name="category_id" required>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?php echo $category['id']; ?>" <?php echo $category['id'] == $edit_product['category_id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($category['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" name="description" rows="3" required><?php echo htmlspecialchars($edit_product['description']); ?></textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Price ($)</label>
                                <input type="number" class="form-control" name="price" step="0.01" min="0" value="<?php echo $edit_product['price']; ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Stock</label>
                                <input type="number" class="form-control" name="stock" min="0" value="<?php echo $edit_product['stock']; ?>" required>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Product Image</label>
                            <input type="file" class="form-control" name="product_image" accept="image/*">
                            <?php if (!empty($edit_product['product_image'])): ?>
                                <small class="text-muted">Current: <?php echo htmlspecialchars($edit_product['product_image']); ?></small>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="update_product" class="btn btn-primary">Update Product</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const editModal = new bootstrap.Modal(document.getElementById('editProductModal'));
            editModal.show();
        });
    </script>
    <?php endif; ?>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

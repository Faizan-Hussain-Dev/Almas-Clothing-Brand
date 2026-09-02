<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['admin_id'])) {
    header('Location: admin_login.php');
    exit();
}

include '../includes/db_connect.php';

// Handle category operations
$message = '';

// Add category
if (isset($_POST['add_category'])) {
    $name = trim($_POST['name']);
    
    if (!empty($name)) {
        $sql = "INSERT INTO categories (name) VALUES (?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $name);
        
        if ($stmt->execute()) {
            $message = "Category added successfully!";
        } else {
            $message = "Failed to add category.";
        }
    } else {
        $message = "Category name is required.";
    }
}

// Update category
if (isset($_POST['update_category'])) {
    $category_id = (int)$_POST['category_id'];
    $name = trim($_POST['name']);
    
    if (!empty($name)) {
        $sql = "UPDATE categories SET name=? WHERE id=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $name, $category_id);
        
        if ($stmt->execute()) {
            $message = "Category updated successfully!";
        } else {
            $message = "Failed to update category.";
        }
    } else {
        $message = "Category name is required.";
    }
}

// Delete category
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $category_id = (int)$_GET['delete'];
    
    // Check if category has products
    $sql = "SELECT COUNT(*) as count FROM products WHERE category_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $category_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $count = $result->fetch_assoc()['count'];
    
    if ($count > 0) {
        $message = "Cannot delete category. It has associated products.";
    } else {
        $sql = "DELETE FROM categories WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $category_id);
        
        if ($stmt->execute()) {
            $message = "Category deleted successfully!";
        } else {
            $message = "Failed to delete category.";
        }
    }
}

// Get categories
$categories = [];
$sql = "SELECT c.*, COUNT(p.product_id) as product_count 
        FROM categories c 
        LEFT JOIN products p ON c.id = p.category_id 
        GROUP BY c.id 
        ORDER BY c.name";
$result = $conn->query($sql);
if ($result) {
    while ($category = $result->fetch_assoc()) {
        $categories[] = $category;
    }
}

// Get category for editing
$edit_category = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $category_id = (int)$_GET['edit'];
    $sql = "SELECT * FROM categories WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $category_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $edit_category = $result->fetch_assoc();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Categories - Admin Panel</title>
    
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
                        <a class="nav-link text-white active" href="manage_categories.php">
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
                <h2>Manage Categories</h2>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
                    <i class="fas fa-plus me-2"></i> Add Category
                </button>
            </div>
            
            <?php if (!empty($message)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <!-- Categories Table -->
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Category Name</th>
                                    <th>Products Count</th>
                                    <th>Created Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($categories as $category): ?>
                                    <tr>
                                        <td><?php echo $category['id']; ?></td>
                                        <td><?php echo htmlspecialchars($category['name']); ?></td>
                                        <td>
                                            <span class="badge bg-info"><?php echo $category['product_count']; ?></span>
                                        </td>
                                        <td><?php echo date('M j, Y', strtotime($category['created_at'])); ?></td>
                                        <td>
                                            <a href="?edit=<?php echo $category['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="?delete=<?php echo $category['id']; ?>" 
                                               class="btn btn-sm btn-outline-danger"
                                               onclick="return confirm('Are you sure you want to delete this category?')">
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
    
    <!-- Add Category Modal -->
    <div class="modal fade" id="addCategoryModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Category</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Category Name</label>
                            <input type="text" class="form-control" name="name" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="add_category" class="btn btn-primary">Add Category</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Edit Category Modal -->
    <?php if ($edit_category): ?>
    <div class="modal fade" id="editCategoryModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Category</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="category_id" value="<?php echo $edit_category['id']; ?>">
                        <div class="mb-3">
                            <label class="form-label">Category Name</label>
                            <input type="text" class="form-control" name="name" value="<?php echo htmlspecialchars($edit_category['name']); ?>" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="update_category" class="btn btn-primary">Update Category</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const editModal = new bootstrap.Modal(document.getElementById('editCategoryModal'));
            editModal.show();
        });
    </script>
    <?php endif; ?>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['admin_id'])) {
    header('Location: admin_login.php');
    exit();
}

include '../includes/db_connect.php';

// Get dashboard statistics
$total_products = 0;
$total_orders = 0;
$total_categories = 0;

$sql_products = "SELECT COUNT(*) as count FROM products";
$result_products = $conn->query($sql_products);
if ($result_products) {
    $total_products = $result_products->fetch_assoc()['count'];
}

$sql_orders = "SELECT COUNT(*) as count FROM orders";
$result_orders = $conn->query($sql_orders);
if ($result_orders) {
    $total_orders = $result_orders->fetch_assoc()['count'];
}

$sql_categories = "SELECT COUNT(*) as count FROM categories";
$result_categories = $conn->query($sql_categories);
if ($result_categories) {
    $total_categories = $result_categories->fetch_assoc()['count'];
}

// Get recent orders
$recent_orders = [];
$sql_recent = "SELECT o.*, 'Guest Customer' as customer_name 
               FROM orders o 
               ORDER BY o.created_at DESC 
               LIMIT 5";
$result_recent = $conn->query($sql_recent);
if ($result_recent) {
    while ($order = $result_recent->fetch_assoc()) {
        $recent_orders[] = $order;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Almas Clothing Brand</title>
    
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
        
        .admin-header {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: var(--shadow-sm);
            margin-bottom: 30px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
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
                        <a class="nav-link text-white active" href="index.php">
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
            <!-- Header -->
            <div class="admin-header">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h2 class="mb-0">Admin Dashboard</h2>
                        <p class="text-muted mb-0">Welcome back, <?php echo htmlspecialchars($_SESSION['admin_username']); ?>!</p>
                    </div>
                    <button class="btn btn-outline-secondary d-md-none" onclick="toggleSidebar()">
                        <i class="fas fa-bars"></i>
                    </button>
                </div>
            </div>
            
            <!-- Statistics Cards -->
            <div class="stats-grid">
                <div class="stats-card">
                    <i class="fas fa-box" style="font-size: 2rem; color: #28a745; margin-bottom: 15px;"></i>
                    <div class="stats-number"><?php echo $total_products; ?></div>
                    <div class="stats-label">Total Products</div>
                </div>
                
                <div class="stats-card">
                    <i class="fas fa-shopping-cart" style="font-size: 2rem; color: #ffc107; margin-bottom: 15px;"></i>
                    <div class="stats-number"><?php echo $total_orders; ?></div>
                    <div class="stats-label">Total Orders</div>
                </div>
                
                <div class="stats-card">
                    <i class="fas fa-tags" style="font-size: 2rem; color: #17a2b8; margin-bottom: 15px;"></i>
                    <div class="stats-number"><?php echo $total_categories; ?></div>
                    <div class="stats-label">Total Categories</div>
                </div>
            </div>
        </div>
    </div>
</div>    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        function toggleSidebar() {
            document.querySelector('.admin-sidebar').classList.toggle('show');
        }
    </script>
</body>
</html>

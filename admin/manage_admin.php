<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['admin_id'])) {
    header('Location: admin_login.php');
    exit();
}

include '../includes/db_connect.php';

// Get current admin info
$current_admin_id = $_SESSION['admin_id'];
$sql = "SELECT role FROM admins WHERE admin_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $current_admin_id);
$stmt->execute();
$result = $stmt->get_result();
$current_admin = $result->fetch_assoc();
$is_main_admin = $current_admin && $current_admin['role'] === 'main_admin';

// Handle admin operations
$message = '';

// Add admin (only main admin can add admins)
if (isset($_POST['add_admin']) && $is_main_admin) {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $role = $_POST['role'];
    
    $errors = [];
    
    if (empty($username)) $errors[] = "Username is required";
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Valid email is required";
    if (empty($password)) $errors[] = "Password is required";
    if (strlen($password) < 6) $errors[] = "Password must be at least 6 characters";
    
    // Check if username already exists
    if (empty($errors)) {
        $sql = "SELECT admin_id FROM admins WHERE username = ? OR email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $username, $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $errors[] = "Username or email already exists";
        }
    }
    
    if (empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        $sql = "INSERT INTO admins (username, email, password, role) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssss", $username, $email, $hashed_password, $role);
        
        if ($stmt->execute()) {
            $message = "Admin added successfully!";
        } else {
            $message = "Failed to add admin.";
        }
    } else {
        $message = implode(", ", $errors);
    }
}

// Update admin
if (isset($_POST['update_admin'])) {
    $admin_id = (int)$_POST['admin_id'];
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    
    $errors = [];
    
    if (empty($username)) $errors[] = "Username is required";
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Valid email is required";
    
    // Check if username/email exists for other admin
    if (empty($errors)) {
        $sql = "SELECT admin_id FROM admins WHERE (username = ? OR email = ?) AND admin_id != ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssi", $username, $email, $admin_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $errors[] = "Username or email already exists";
        }
    }
    
    if (empty($errors)) {
        if (!empty($password)) {
            // Update with new password
            if (strlen($password) < 6) {
                $message = "Password must be at least 6 characters";
            } else {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $sql = "UPDATE admins SET username=?, email=?, password=? WHERE admin_id=?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("sssi", $username, $email, $hashed_password, $admin_id);
                
                if ($stmt->execute()) {
                    $message = "Admin updated successfully!";
                } else {
                    $message = "Failed to update admin.";
                }
            }
        } else {
            // Update without changing password
            $sql = "UPDATE admins SET username=?, email=? WHERE admin_id=?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssi", $username, $email, $admin_id);
            
            if ($stmt->execute()) {
                $message = "Admin updated successfully!";
            } else {
                $message = "Failed to update admin.";
            }
        }
    } else {
        $message = implode(", ", $errors);
    }
}

// Delete admin
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $admin_id = (int)$_GET['delete'];
    
    // Prevent deleting current admin
    if ($admin_id == $_SESSION['admin_id']) {
        $message = "Cannot delete your own account.";
    } else {
        $sql = "DELETE FROM admins WHERE admin_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $admin_id);
        
        if ($stmt->execute()) {
            $message = "Admin deleted successfully!";
        } else {
            $message = "Failed to delete admin.";
        }
    }
}

// Get admins
$admins = [];
$sql = "SELECT * FROM admins ORDER BY created_at DESC";
$result = $conn->query($sql);
if ($result) {
    while ($admin = $result->fetch_assoc()) {
        $admins[] = $admin;
    }
}

// Get admin for editing
$edit_admin = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $admin_id = (int)$_GET['edit'];
    $sql = "SELECT * FROM admins WHERE admin_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $admin_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $edit_admin = $result->fetch_assoc();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Admins - Admin Panel</title>
    
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
                        <a class="nav-link text-white active" href="manage_admin.php">
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
                <h2>Manage Admins</h2>
                <?php if ($is_main_admin): ?>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addAdminModal">
                        <i class="fas fa-plus me-2"></i> Add Admin
                    </button>
                <?php else: ?>
                    <span class="text-muted">Only Main Admin can add new admins</span>
                <?php endif; ?>
            </div>
            
            <?php if (!empty($message)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <!-- Admins Table -->
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Username</th>
                                    <th>Email</th>
                                    <th>Role</th>
                                    <th>Created Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($admins as $admin): ?>
                                    <tr>
                                        <td><?php echo $admin['admin_id']; ?></td>
                                        <td><?php echo htmlspecialchars($admin['username']); ?></td>
                                        <td><?php echo htmlspecialchars($admin['email']); ?></td>
                                        <td>
                                            <?php 
                                            $role = $admin['role'] ?? 'sub_admin';
                                            $badgeClass = $role === 'main_admin' ? 'bg-danger' : 'bg-primary';
                                            $roleText = $role === 'main_admin' ? 'Main Admin' : 'Sub Admin';
                                            echo '<span class="badge ' . $badgeClass . '">' . $roleText . '</span>';
                                            ?>
                                        </td>
                                        <td><?php echo date('M j, Y', strtotime($admin['created_at'])); ?></td>
                                        <td>
                                            <?php if ($is_main_admin): ?>
                                                <?php if ($admin['role'] !== 'main_admin'): ?>
                                                    <a href="?edit=<?php echo $admin['admin_id']; ?>" 
                                                       class="btn btn-sm btn-outline-primary me-1">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <a href="?delete=<?php echo $admin['admin_id']; ?>" 
                                                       class="btn btn-sm btn-outline-danger"
                                                       onclick="return confirm('Are you sure you want to delete this admin?')">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
                                                <?php else: ?>
                                                    <span class="text-muted">Main Admin</span>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <span class="text-muted">No access</span>
                                            <?php endif; ?>
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
    
    <!-- Add Admin Modal -->
    <div class="modal fade" id="addAdminModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Admin</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="username" class="form-label">Username</label>
                            <input type="text" class="form-control" id="username" name="username" required>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password" required minlength="6">
                            <small class="form-text text-muted">Minimum 6 characters</small>
                        </div>
                        <div class="mb-3">
                            <label for="role" class="form-label">Role</label>
                            <select class="form-select" id="role" name="role" required>
                                <option value="">Select Role</option>
                                <option value="sub_admin">Sub Admin</option>
                                <option value="main_admin">Main Admin</option>
                            </select>
                            <small class="form-text text-warning">Main Admin has full control over other admins</small>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" name="add_admin" class="btn btn-primary">Add Admin</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Edit Admin Modal -->
    <?php if ($edit_admin): ?>
    <div class="modal fade" id="editAdminModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Admin</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="admin_id" value="<?php echo $edit_admin['admin_id']; ?>">
                        <div class="mb-3">
                            <label class="form-label">Username</label>
                            <input type="text" class="form-control" name="username" value="<?php echo htmlspecialchars($edit_admin['username']); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($edit_admin['email']); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">New Password (leave empty to keep current)</label>
                            <input type="password" class="form-control" name="password" minlength="6">
                            <small class="form-text text-muted">Minimum 6 characters</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="update_admin" class="btn btn-primary">Update Admin</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const editModal = new bootstrap.Modal(document.getElementById('editAdminModal'));
            editModal.show();
        });
    </script>
    <?php endif; ?>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include '../includes/db_connect.php';

// Handle admin login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    
    $errors = [];
    
    if (empty($username)) $errors[] = "Username is required";
    if (empty($password)) $errors[] = "Password is required";
    
    if (empty($errors)) {
        $sql = "SELECT * FROM admins WHERE username = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $admin = $result->fetch_assoc();
            
            // For demo purposes, simple password check
            if ($password === 'admin123' || password_verify($password, $admin['password'])) {
                $_SESSION['admin_id'] = $admin['admin_id'];
                $_SESSION['admin_username'] = $admin['username'];
                $_SESSION['admin_email'] = $admin['email'];
                
                header('Location: index.php');
                exit();
            } else {
                $error = "Invalid username or password";
            }
        } else {
            $error = "Invalid username or password";
        }
    } else {
        $error = implode(", ", $errors);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Almas Clothing Brand</title>
    
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
        body {
            background: linear-gradient(135deg, var(--primary-color) 0%, #343a40 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Poppins', sans-serif;
        }
        
        .admin-login-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
            overflow: hidden;
            max-width: 900px;
            width: 100%;
        }
        
        .admin-login-left {
            background: linear-gradient(135deg, var(--primary-color) 0%, #343a40 100%);
            color: white;
            padding: 60px 40px;
            text-align: center;
        }
        
        .admin-login-right {
            padding: 60px 40px;
        }
        
        .admin-logo {
            font-size: 3rem;
            color: var(--accent-color);
            margin-bottom: 20px;
        }
        
        .admin-title {
            font-family: 'Montserrat', sans-serif;
            font-weight: 700;
            margin-bottom: 20px;
        }
        
        .input-group-text {
            background: var(--secondary-color);
            border: 1px solid var(--border-color);
            color: var(--text-dark);
        }
        
        .btn-admin {
            background: var(--primary-color);
            border: none;
            padding: 12px 30px;
            font-weight: 500;
            border-radius: 5px;
            transition: all 0.3s ease;
            color: white !important;
        }
        
        .btn-admin:hover {
            background: var(--accent-color);
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
            color: white !important;
        }
    </style>
</head>
<body>
    <div class="admin-login-container">
        <div class="row g-0">
            <div class="col-lg-5">
                <div class="admin-login-left">
                    <div class="admin-logo">Admin</div>
                    <h2 class="admin-title">Admin Panel</h2>
                    <p>Almas Clothing Brand Management System</p>
                    <div class="mt-4">
                        <div style="font-size: 4rem; opacity: 0.3;">🔒</div>
                    </div>
                </div>
            </div>
            <div class="col-lg-7">
                <div class="admin-login-right">
                    <h3 class="mb-4">Admin Login</h3>
                    
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger" role="alert">
                            <?php echo htmlspecialchars($error); ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST">
                        <div class="mb-3">
                            <label for="username" class="form-label">Username</label>
                            <input type="text" class="form-control" id="username" name="username" required>
                        </div>
                        
                        <div class="mb-4">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        
                        <div class="d-grid">
                            <button type="submit" class="btn btn-admin btn-lg">
                                Login
                            </button>
                        </div>
                    </form>
                    
                    <div class="text-center mt-4">
                        <small class="text-muted">
                            Demo: Username: admin, Password: admin123
                        </small>
                    </div>
                    
                    <div class="text-center mt-3">
                        <a href="../index.php" class="text-decoration-none">
                            ← Back to Website
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

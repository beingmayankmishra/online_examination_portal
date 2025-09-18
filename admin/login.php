<?php
session_start();
require_once '../includes/db_connect.php';

// Check if already logged in
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header('Location: dashboard.php');
    exit();
}

// Process login
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (!empty($email) && !empty($password)) {
        // Check credentials against database
        $stmt = $pdo->prepare("SELECT * FROM admin_users WHERE email = ?");
        $stmt->execute([$email]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($admin) {
            // Check password using password_verify() only
            if (password_verify($password, $admin['password_hash'])) {
                // Login successful
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_id'] = $admin['id'];
                $_SESSION['admin_email'] = $admin['email'];
                
                header('Location: dashboard.php');
                exit();
            } else {
                $error = "Invalid credentials. Please try again.";
            }
        } else {
            $error = "Invalid credentials. Please try again.";
        }
    } else {
        $error = "Please enter both email and password.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Mind Power University</title>
    <link rel="icon" type="image/png"  href="../css/images/MPU_favicon.jpg">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>Mind Power University</h1>
            <h2>Admin Portal</h2>
        </header>
        
        <div class="login-form">
            <h3>Admin Login</h3>
            
            <?php if (!empty($error)): ?>
            <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" required autocomplete="off" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required autocomplete="off">
                </div>
                
                <button type="submit" class="btn">Login</button>
            </form>
        </div>
        
        <footer>
            <p>&copy; 2025 Mind Power University. All rights reserved.</p>
        </footer>
    </div>
</body>
</html>
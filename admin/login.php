<?php
session_start();

require_once '../includes/db_connect.php';

// Default admin credentials
$adminEmail = 'examination@mindpoweruniversity.ac.in';
$adminPassword = 'examadmin@123';

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
    
    if ($email === $adminEmail && $password === $adminPassword) {
        // Login successful
        $_SESSION['admin_logged_in'] = true;
        header('Location: dashboard.php');
        exit();
    } else {
        $error = "Invalid credentials. Please try again.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Mind Power University</title>
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
            <div class="error-message"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" value="examination@mindpoweruniversity.ac.in" required>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" value="examadmin@123" required>
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
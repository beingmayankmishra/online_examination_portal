<?php
// admin/index.php
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
     <style>
        body, html {
            margin: 0;
            padding: 0;
            height: 100%;
            font-family: 'Arial', sans-serif;
            background: #f4f6fb;
        }

        .login-page {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        /* Header */
        header {
            display: flex;
            align-items: center;
            padding: 15px 25px;
            background: white;
            color: #004aad;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        
        header img {
            height: 60px;
            margin-right: 12px;
        }
        
        header h1 {
            font-size: 20px;
            font-weight: bold;
        }

        /* Main layout */
        main {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        /* Combined container for both boxes */
        .login-container {
            display: flex;
            width: 900px;
            height: 500px;
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
        }

        /* Left box */
        .left-box {
            flex: 1;
            background: #004aad;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 40px;
            color: white;
            position: relative;
        }

        /* Angled edge for left box */
        .left-box::after {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 40px;
            height: 100%;
            background: #004aad;
            transform: skewX(-5deg);
            transform-origin: top right;
            z-index: 1;
        }

        .login-form {
            max-width: 320px;
            width: 100%;
            z-index: 2;
        }

        .login-form h3 {
            text-align: center;
            margin-bottom: 25px;
            font-size: 26px;
            font-weight: bold;
        }

        .form-group {
            margin-bottom: 18px;
        }
        
        .form-group label {
            display: block;
            font-size: 14px;
            margin-bottom: 5px;
        }
        
        .form-group input {
            width: 90%;
            padding: 12px 15px;
            border: none;
            border-radius: 25px;
            font-size: 14px;
            outline: none;
        }

        .form-group input:focus {
            border: 2px solid #ff8800;
        }

        .login-btn {
            width: 100%;
            background: #ff8800;
            border: none;
            color: white;
            padding: 12px;
            border-radius: 25px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: background 0.3s ease;
            margin-top: 10px;
        }
        
        .login-btn:hover {
            background: #e67300;
        }

        .back-link {
            margin-top: 15px;
            text-align: center;
            font-size: 14px;
        }
        
        .back-link a {
            color: #ffcc00;
            text-decoration: none;
            font-weight: bold;
        }
        
        .back-link a:hover {
            text-decoration: underline;
        }

        /* Right box */
        .right-box {
            flex: 1;
            background: white;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 20px;
            position: relative;
        }

        /* Angled edge for right box */
        .right-box::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 40px;
            height: 100%;
            background: white;
            transform: skewX(-5deg);
            transform-origin: top left;
            z-index: 1;
        }

        .right-box img {
            height: 140px;
            margin-bottom: 20px;
            z-index: 2;
        }
        
        .right-box h2 {
            font-size: 32px;
            color: #004aad;
            font-weight: bold;
            z-index: 2;
        }

        /* Error message */
        .error-message {
            background: #f8d7da;
            color: #721c24;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 15px;
            text-align: center;
            font-size: 14px;
        }

        /* Footer */
        footer {
            background: #004aad;
            color: white;
            text-align: center;
            padding: 12px;
            font-size: 14px;
            margin-top: 20px;
        }

        /* Responsive design */
        @media (max-width: 900px) {
            .login-container {
                flex-direction: column;
                width: 90%;
                height: auto;
            }
            
            .left-box::after,
            .right-box::before {
                display: none;
            }
            
            .left-box, .right-box {
                padding: 30px 20px;
            }
            
            .right-box {
                order: -1;
                padding-top: 40px;
            }
            
            .right-box img {
                height: 100px;
            }
            
            .right-box h2 {
                font-size: 24px;
            }
        }
    </style>
    
</head>
<body>
    <div class="login-page">
        <!-- Header -->
        <header>
            <img src="../css/images/MPU LOGO new.png" alt="University Logo">
            <h1>Mind Power University - Admin Portal</h1>
        </header>

        <!-- Main Content -->
        <main>
            <div class="login-container">
                <!-- Left box -->
                <div class="left-box">
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
                            
                            <button type="submit" class="login-btn">Login</button>
                        </form>

                        <div class="back-link">
                            <p>Back to <a href="../">Student Login</a></p>
                        </div>
                    </div>
                </div>

                <!-- Right box -->
                <div class="right-box">
                    <img src="../css/images/MPU LOGO new.png" alt="University Logo">
                    <h2>ADMIN PORTAL</h2>
                </div>
            </div>
        </main>

        <!-- Footer -->
        <footer>
            &copy; <?php echo date("Y"); ?> Mind Power University. All Rights Reserved.
        </footer>
    </div>
</body>
</html>
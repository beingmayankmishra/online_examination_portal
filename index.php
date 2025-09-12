<?php
session_start();
if (isset($_SESSION['student_id'])) {
    header('Location: dashboard.php');
    exit();
}

$error = '';
if (isset($_GET['error'])) {
    switch ($_GET['error']) {
        case 'invalid_credentials':
            $error = 'Invalid enrollment number or date of birth.';
            break;
        case 'already_attempted':
            $error = 'You have already attempted the exam.';
            break;
        case 'already_submitted':
            $error = 'You have already submitted the exam.';
            break;
        default:
            $error = 'An error occurred. Please try again.';
    }
}

$success = '';
if (isset($_GET['success'])) {
    if ($_GET['success'] === 'exam_submitted') {
        $success = 'Exam submitted successfully!';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mind Power University Online Examination Portal</title>
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

        .admin-link {
            margin-top: 15px;
            text-align: center;
            font-size: 14px;
        }
        
        .admin-link a {
            color: #ffcc00;
            text-decoration: none;
            font-weight: bold;
        }
        
        .admin-link a:hover {
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

        /* Error & Success */
        .error-message {
            background: #f8d7da;
            color: #721c24;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 15px;
            text-align: center;
            font-size: 14px;
        }
        
        .success-message {
            background: #d4edda;
            color: #155724;
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
            <img src="css/images/MPU LOGO new.png" alt="University Logo">
            <h1>Mind Power University - Online Examination Portal</h1>
        </header>

        <!-- Main Content -->
        <main>
            <div class="login-container">
                <!-- Left box -->
                <div class="left-box">
                    <div class="login-form">
                        <h3>Login</h3>

                        <?php if (!empty($error)): ?>
                        <div class="error-message"><?php echo $error; ?></div>
                        <?php endif; ?>

                        <?php if (!empty($success)): ?>
                        <div class="success-message"><?php echo $success; ?></div>
                        <?php endif; ?>

                        <form id="studentLoginForm" action="login.php" method="POST">
                            <div class="form-group">
                                <label for="enrollment">Enrollment Number</label>
                                <input type="text" id="enrollment" name="enrollment" placeholder="Enter Enrollment Number" required>
                            </div>

                            <div class="form-group">
                                <label for="dob">Date of Birth</label>
                                <input type="date" id="dob" name="dob" required>
                            </div>

                            <button type="submit" class="login-btn">Login</button>
                        </form>

                        <div class="admin-link">
                            <p>Are you an admin? <a href="admin/">Click here</a></p>
                        </div>
                    </div>
                </div>

                <!-- Right box -->
                <div class="right-box">
                    <img src="css/images/MPU LOGO new.png" alt="University Logo">
                    <h2>WELCOME!</h2>
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
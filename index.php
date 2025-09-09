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
    <title>Mind Power University - Online Examination Portal</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>Mind Power University</h1>
            <h2>Online Examination Portal</h2>
        </header>
        
        <div class="login-form">
            <h3>Student Login</h3>
            
            <?php if (!empty($error)): ?>
            <div class="error-message"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if (!empty($success)): ?>
            <div class="success-message"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <form id="studentLoginForm" action="login.php" method="POST">
                <div class="form-group">
                    <label for="enrollment">Enrollment Number</label>
                    <input type="text" id="enrollment" name="enrollment" required>
                </div>
                
                <div class="form-group">
                    <label for="dob">Date of Birth</label>
                    <input type="date" id="dob" name="dob" required>
                </div>
                
                <button type="submit" class="btn">Login</button>
            </form>
            
            <div class="admin-link">
                <p>Are you an admin? <a href="admin/">Click here</a></p>
            </div>
        </div>
        
        <footer>
            <p>&copy; 2025 Mind Power University. All rights reserved.</p>
        </footer>
    </div>
    
    <script src="js/script.js"></script>
</body>
</html>
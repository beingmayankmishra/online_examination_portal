<?php

require_once 'includes/auth.php';
require_once 'includes/db_connect.php';
redirectIfNotLoggedIn('student');

$studentId = $_SESSION['student_id'];

// Check if student has already seen instructions
if (isset($_SESSION['instructions_seen'])) {
    header('Location: dashboard.php');
    exit();
}

// Mark instructions as seen when proceeding to exam
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $_SESSION['instructions_seen'] = true;
    
    // Clear any previous exam session data to prevent timeout issues
    if (isset($_SESSION['exam_start_time'])) {
        unset($_SESSION['exam_start_time']);
    }
    if (isset($_SESSION['exam_duration'])) {
        unset($_SESSION['exam_duration']);
    }
    
    header('Location: dashboard.php');
    exit();
}

// Get exam time for display
$stmt = $pdo->query("SELECT COUNT(*) as count FROM questions WHERE is_active = TRUE");
$totalQuestions = $stmt->fetch()['count'];
$examTimeMinutes = $totalQuestions;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Exam Instructions - Mind Power University</title>
    <link rel="icon" type="image/png" href="css/images/MPU_favicon.jpg?v=2">
    <link rel="stylesheet" href="css/style.css">
    <style>
        .instructions-container {
            max-width: 800px;
            margin: 50px auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        
        .instructions-header {
            text-align: center;
            margin-bottom: 30px;
            color: #1a237e;
        }
        
        .instructions-list {
            margin: 20px 0;
            padding-left: 20px;
        }
        
        .instructions-list li {
            margin-bottom: 15px;
            line-height: 1.6;
        }
        
        .warning-box {
            background: #fff3e0;
            border-left: 4px solid #ff9800;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        
        .proceed-btn {
            display: block;
            width: 200px;
            margin: 30px auto 0;
            padding: 15px;
            background: linear-gradient(135deg, #311b92 0%, #4527a0 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .proceed-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(49, 27, 146, 0.4);
        }
        
        .fullscreen-permission {
            background: #e3f2fd;
            border-left: 4px solid #2196f3;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>Mind Power University</h1>
            <h2>Online Examination Instructions</h2>
        </header>
        
        <div class="instructions-container">
            <div class="instructions-header">
                <h2>Important Exam Instructions</h2>
                <p>Please read all instructions carefully before proceeding</p>
            </div>
            
            <div class="warning-box">
                <strong>⚠️ Important:</strong> Once you proceed, the exam will open in full-screen secure mode. 
                Do not attempt to exit or refresh the browser during the exam.
            </div>
            
            <div class="fullscreen-permission">
                <strong>🔒 Fullscreen Required:</strong> The exam requires full Screen mode. Please allow fullscreen 
                permission when prompted by your browser.
            </div>
            
            <h3>Exam Rules & Guidelines:</h3>
            <ol class="instructions-list">
                <li><strong>Full-Screen Lockdown:</strong> The exam will open in secure full-screen lockdown mode</li>
                <li><strong>No Browser Switching:</strong> Switching tabs/windows will result in automatic submission</li>
                <li><strong>Time Limit:</strong> You have <?php echo $examTimeMinutes; ?> minutes to complete the exam</li>
                <li><strong>Auto-Submit:</strong> Exam will auto-submit when time expires</li>
                <li><strong>Single Attempt:</strong> You can attempt this exam only once</li>
                <li><strong>No Navigation:</strong> Browser back/forward buttons are disabled</li>
                <li><strong>No Refresh:</strong> Page refresh is disabled and will auto-submit</li>
                <li><strong>Answer Saving:</strong> Answers are auto-saved as you select them</li>
            </ol>
            
            <h3>Prohibited Actions:</h3>
            <ul class="instructions-list">
                <li>Refreshing the page (auto-submit)</li>
                <li>Opening new tabs/windows (auto-submit)</li>
                <li>Using browser developer tools (auto-submit)</li>
                <li>Right-clicking or trying to inspect elements</li>
                <li>Using keyboard shortcuts to exit fullscreen</li>
            </ul>
            
            <form method="POST">
                <button type="submit" class="proceed-btn" onclick="requestFullscreenPermission()">
                    Proceed to Exam
                </button>
            </form>
        </div>
    </div>

    <script>
    function requestFullscreenPermission() {
        // Try to request fullscreen immediately to get user permission
        if (document.documentElement.requestFullscreen) {
            document.documentElement.requestFullscreen()
                .then(() => {
                    // Immediately exit fullscreen after getting permission
                    document.exitFullscreen();
                })
                .catch(err => {
                    console.log('Fullscreen preview error (expected):', err);
                });
        }
    }
    </script>
</body>
</html>
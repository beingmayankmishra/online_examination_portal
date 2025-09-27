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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <style>
        :root {
            --primary: #1a237e;
            --primary-light: #534bae;
            --primary-dark: #000051;
            --secondary: #fbc02d;
            --secondary-light: #fff263;
            --secondary-dark: #c49000;
            --text-on-primary: #ffffff;
            --text-on-secondary: #000000;
            --background: #f5f7ff;
            --surface: #ffffff;
            --error: #b00020;
            --success: #00c853;
            --warning: #ffab00;
            --info: #0091ea;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background: var(--background);
            color: #333;
            line-height: 1.6;
        }
        
        .header {
           
            color: var(--text-on-primary);
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            color: #004aad;
            top: 0;
            z-index: 100;
        }
        
        .logo-container {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .logo {
            height: 50px;
            width: auto;
        }
        
        .university-name {
            font-size: 1.5rem;
            font-weight: 700;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--secondary);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }
        
        .main-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        
        .page-title {
            text-align: center;
            margin-bottom: 2rem;
            color: var(--primary);
            position: relative;
            padding-bottom: 1rem;
        }
        
        .page-title:after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 100px;
            height: 4px;
            background: var(--secondary);
            border-radius: 2px;
        }
        
        .instructions-container {
            background: var(--surface);
            border-radius: 12px;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.08);
            overflow: hidden;
            margin-bottom: 2rem;
        }
        
        .instructions-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
            color: var(--text-on-primary);
            padding: 1.5rem 2rem;
            text-align: center;
        }
        
        .instructions-body {
            padding: 2rem;
        }
        
        .info-box {
            padding: 1.5rem;
            border-radius: 8px;
            margin-bottom: 2rem;
            display: flex;
            align-items: flex-start;
            gap: 1rem;
        }
        
        .info-icon {
            font-size: 1.5rem;
            flex-shrink: 0;
        }
        
        .warning-box {
            background: #fff8e1;
            border-left: 4px solid var(--warning);
        }
        
        .fullscreen-box {
            background: #e3f2fd;
            border-left: 4px solid var(--info);
        }
        
        .instructions-section {
            margin-bottom: 2rem;
        }
        
        .instructions-section h3 {
            color: var(--primary);
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid #eee;
        }
        
        .instructions-list {
            list-style-type: none;
        }
        
        .instructions-list li {
            margin-bottom: 1rem;
            padding-left: 1.5rem;
            position: relative;
        }
        
        .instructions-list li:before {
            content: '';
            position: absolute;
            left: 0;
            top: 0.5rem;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: var(--primary-light);
        }
        
        .prohibited-list li:before {
            background: var(--error);
        }
        
        .exam-details {
            background: #f5f5f5;
            padding: 1.5rem;
            border-radius: 8px;
            margin: 2rem 0;
        }
        
        .detail-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
            margin-top: 1rem;
        }
        
        .detail-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .detail-icon {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            background: var(--primary-light);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .proceed-section {
            text-align: center;
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 1px solid #eee;
        }
        
        .proceed-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.75rem;
            padding: 1rem 2.5rem;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(26, 35, 126, 0.2);
        }
        
        .proceed-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(26, 35, 126, 0.3);
        }
        
        .proceed-btn:active {
            transform: translateY(0);
        }
        
       footer {
    background: #004aad;
    color: white;
    text-align: center;
    padding: 12px;
    font-size: 14px;
    margin-top: 20px;
}
        
        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                padding: 1rem;
                gap: 1rem;
            }
            
            .logo {
                height: 40px;
            }
            
            .university-name {
                font-size: 1.2rem;
            }
            
            .instructions-body {
                padding: 1.5rem;
            }
            
            .detail-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="logo-container">
            <img src="css/images/MPU LOGO new.png" alt="Mind Power University Logo" class="logo">
            <div class="university-name">Mind Power University</div>
        </div>
        <div class="user-info">
            <div class="user-avatar">
                <?php 
                // Display user initial
                if (isset($_SESSION['student_name'])) {
                    echo strtoupper(substr($_SESSION['student_name'], 0, 1));
                } else {
                    echo 'S';
                }
                ?>
            </div>
            <div class="user-name">
                <?php 
                if (isset($_SESSION['student_name'])) {
                    echo htmlspecialchars($_SESSION['student_name']);
                } else {
                    echo 'Student';
                }
                ?>
            </div>
        </div>
    </header>
    
    <div class="main-container">
        <h1 class="page-title">Online Examination Instructions</h1>
        
        <div class="instructions-container">
            <div class="instructions-header">
                <h2>Important Exam Instructions</h2>
                <p>Please read all instructions carefully before proceeding to the exam</p>
            </div>
            
            <div class="instructions-body">
                <div class="info-box warning-box">
                    <div class="info-icon"><i class="fas fa-exclamation-triangle"></i></div>
                    <div>
                        <strong>Important:</strong> Once you proceed, the exam will open in full-screen secure mode. 
                        Do not attempt to exit or refresh the browser during the exam as it may result in automatic submission.
                    </div>
                </div>
                
                <div class="info-box fullscreen-box">
                    <div class="info-icon"><i class="fas fa-desktop"></i></div>
                    <div>
                        <strong>Fullscreen Required:</strong> The exam requires full screen mode. Please allow fullscreen 
                        permission when prompted by your browser for optimal experience.
                    </div>
                </div>
                
                <div class="exam-details">
                    <h3>Exam Overview</h3>
                    <div class="detail-grid">
                        <div class="detail-item">
                            <div class="detail-icon">
                                <i class="fas fa-question-circle"></i>
                            </div>
                            <div>
                                <div class="detail-label">Total Questions</div>
                                <div class="detail-value"><?php echo $totalQuestions; ?></div>
                            </div>
                        </div>
                        
                        <div class="detail-item">
                            <div class="detail-icon">
                                <i class="fas fa-clock"></i>
                            </div>
                            <div>
                                <div class="detail-label">Time Allotted</div>
                                <div class="detail-value"><?php echo $examTimeMinutes; ?> minutes</div>
                            </div>
                        </div>
                        
                        <div class="detail-item">
                            <div class="detail-icon">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <div>
                                <div class="detail-label">Question Type</div>
                                <div class="detail-value">Multiple Choice</div>
                            </div>
                        </div>
                        
                        <div class="detail-item">
                            <div class="detail-icon">
                                <i class="fas fa-redo"></i>
                            </div>
                            <div>
                                <div class="detail-label">Navigation</div>
                                <div class="detail-value">One-way (cannot go back)</div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="instructions-section">
                    <h3><i class="fas fa-list-alt"></i> Exam Rules & Guidelines</h3>
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
                </div>
                
                <div class="instructions-section">
                    <h3><i class="fas fa-ban"></i> Prohibited Actions</h3>
                    <ul class="instructions-list prohibited-list">
                        <li>Refreshing the page (will result in auto-submission)</li>
                        <li>Opening new tabs or browser windows (will result in auto-submission)</li>
                        <li>Using browser developer tools (will result in auto-submission)</li>
                        <li>Right-clicking or trying to inspect elements</li>
                        <li>Using keyboard shortcuts to exit fullscreen mode</li>
                        <li>Copying or taking screenshots of exam content</li>
                    </ul>
                </div>
                
                <div class="proceed-section">
                    <form method="POST">
                        <button type="submit" class="proceed-btn" onclick="requestFullscreenPermission()">
                            <i class="fas fa-play-circle"></i> Proceed to Exam
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <footer class="footer">
        <p>© <?php echo date('Y'); ?> Mind Power University. All Rights Reserved.</p>
       
    </footer>

    <script>
    function requestFullscreenPermission() {
        // Try to request fullscreen immediately to get user permission
        if (document.documentElement.requestFullscreen) {
            document.documentElement.requestFullscreen()
                .then(() => {
                    // Immediately exit fullscreen after getting permission
                    if (document.exitFullscreen) {
                        document.exitFullscreen();
                    }
                })
                .catch(err => {
                    console.log('Fullscreen preview error (expected):', err);
                });
        }
    }
    </script>
</body>
</html>
<?php
session_start();

// Check if we need to clear sessionStorage from previous student
if (isset($_SESSION['clear_session_storage']) && $_SESSION['clear_session_storage']) {
    unset($_SESSION['clear_session_storage']);
    echo '<script>
        // Clear all exam-related sessionStorage data
        Object.keys(sessionStorage).forEach(key => {
            if (key.startsWith("examTimeLeft") || 
                key.startsWith("examStartTime") || 
                key.startsWith("tabChangeCount") || 
                key.startsWith("examInitialized") ||
                key.startsWith("answer_") ||
                key === "currentStudentId") {
                sessionStorage.removeItem(key);
            }
        });
    </script>';
}

require_once 'includes/auth.php';
require_once 'includes/db_connect.php';
redirectIfNotLoggedIn('student');

// Check if student has seen instructions
if (!isset($_SESSION['instructions_seen'])) {
    header('Location: instructions.php');
    exit();
}

// Check if student has already attempted exam
$studentId = $_SESSION['student_id'];
$checkAttempt = $pdo->prepare("SELECT exam_attempted FROM students WHERE id = ?");
$checkAttempt->execute([$studentId]);
$student = $checkAttempt->fetch();

if ($student && $student['exam_attempted']) {
    header('Location: index.php?error=already_attempted');
    exit();
}

// Get all active questions
$stmt = $pdo->query("SELECT * FROM questions WHERE is_active = TRUE ORDER BY RAND()");
$questions = $stmt->fetchAll(PDO::FETCH_ASSOC);
$totalQuestions = count($questions);

// Calculate exam time (1 minute per question)
$examTimeMinutes = $totalQuestions;
$examTimeSeconds = $examTimeMinutes * 60;

// Initialize response records for this student
foreach ($questions as $question) {
    $checkStmt = $pdo->prepare("SELECT id FROM exam_responses WHERE student_id = ? AND question_id = ?");
    $checkStmt->execute([$studentId, $question['id']]);
    
    if (!$checkStmt->fetch()) {
        $insertStmt = $pdo->prepare("INSERT INTO exam_responses (student_id, question_id) VALUES (?, ?)");
        $insertStmt->execute([$studentId, $question['id']]);
    }
}

// CRITICAL FIX: Reset exam start time when starting fresh exam
$_SESSION['exam_start_time'] = time();
$_SESSION['exam_duration'] = $examTimeSeconds;
$remainingTime = $examTimeSeconds; // Always start with full time

// Set flag to enable fullscreen mode
$_SESSION['enable_fullscreen'] = true;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Exam Dashboard - Mind Power University</title>
    <link rel="icon" type="image/png"  href="css/images/MPU_favicon.jpg?v=2">
    <link rel="stylesheet" href="css/style.css">
    <style>
        /* Security warning banner */
        .security-banner {
            background: linear-gradient(135deg, #ff5252 0%, #d32f2f 100%);
            color: white;
            padding: 10px;
            text-align: center;
            font-weight: bold;
            position: sticky;
            top: 0;
            z-index: 10000;
            box-shadow: 0 2px 10px rgba(0,0,0,0.3);
        }
        
        /* Lockdown mode - hide everything except exam content */
        body.lockdown-mode {
            overflow: hidden !important;
            margin: 0 !important;
            padding: 0 !important;
            width: 100vw !important;
            height: 100vh !important;
        }
        
        .lockdown-mode .container {
            max-width: 100% !important;
            margin: 0 !important;
            padding: 20px !important;
            height: 100vh !important;
            overflow-y: auto !important;
        }
        
        /* Fullscreen styles */
        :fullscreen {
            width: 100% !important;
            height: 100% !important;
        }
        
        :-ms-fullscreen {
            width: 100% !important;
            height: 100% !important;
        }
        
        :-webkit-full-screen {
            width: 100% !important;
            height: 100% !important;
        }
        
        :-moz-full-screen {
            width: 100% !important;
            height: 100% !important;
        }
        
        :fullscreen .container {
            padding: 30px !important;
            max-width: 100% !important;
        }
        
        :fullscreen .dashboard-header {
            margin-bottom: 25px !important;
        }
        
        /* Prevent scrolling in fullscreen */
        :fullscreen body {
            overflow: hidden !important;
        }
        
        /* Hide browser UI elements */
        .lockdown-mode ::-webkit-scrollbar {
            display: none !important;
        }
        
        .lockdown-mode {
            -ms-overflow-style: none !important;
            scrollbar-width: none !important;
        }
        
        /* Fullscreen permission prompt */
        .fullscreen-permission-prompt {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.9);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 99999;
            color: white;
            flex-direction: column;
            text-align: center;
        }
        
        .fullscreen-permission-content {
            max-width: 600px;
            padding: 30px;
            background: #1a237e;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.5);
        }
        
        .fullscreen-permission-content h2 {
            margin-top: 0;
            color: #fff;
        }
        
        .fullscreen-permission-content p {
            margin-bottom: 20px;
            line-height: 1.6;
        }
        
        .enable-fullscreen-btn {
            background: linear-gradient(135deg, #4CAF50 0%, #2E7D32 100%);
            color: white;
            border: none;
            padding: 15px 30px;
            font-size: 18px;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .enable-fullscreen-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
        }
        
        /* Disable text selection */
        .lockdown-mode {
            -webkit-user-select: none;
            -moz-user-select: none;
            -ms-user-select: none;
            user-select: none;
        }
    </style>
</head>
<body data-student-id="<?php echo $_SESSION['student_id']; ?>">
    <!-- Fullscreen Permission Prompt -->
    <div class="fullscreen-permission-prompt" id="fullscreenPrompt">
        <div class="fullscreen-permission-content">
            <h2>🔒 Secure Exam Mode Required</h2>
            <p>This exam must be taken in fullscreen mode to ensure academic integrity and prevent unauthorized activities.</p>
            <p>Please click the button below to enable fullscreen mode. You will not be able to take the exam without enabling fullscreen.</p>
            <button class="enable-fullscreen-btn" id="enableFullscreen">Enable Fullscreen Mode</button>
            <p style="margin-top: 20px; font-size: 14px; opacity: 0.8;">
                If you accidentally exit fullscreen, the exam will be automatically submitted.
            </p>
        </div>
    </div>
    
    <!-- Security Warning Banner -->
    <div class="security-banner" id="securityBanner">
        ⚠️ SECURE EXAM MODE ACTIVE - Do not attempt to exit or refresh
    </div>
    
    <div class="container">
        <div class="dashboard-header">
            <div class="student-info">
                Enrollment: <strong><?php echo $_SESSION['enrollment_number']; ?></strong> | 
                Name: <strong><?php echo $_SESSION['student_name']; ?></strong>
            </div>
            <div class="timer" id="examTimer" data-time="<?php echo $remainingTime; ?>">
                <?php echo sprintf('%02d:%02d', floor($remainingTime / 60), $remainingTime % 60); ?>
            </div>
        </div>
        
        <form id="examForm" action="submit_exam.php" method="POST">
            <?php
            // Split questions into pages (10 questions per page)
            $questionsPerPage = 10;
            $totalPages = ceil($totalQuestions / $questionsPerPage);
            
            for ($page = 1; $page <= $totalPages; $page++):
                $startIndex = ($page - 1) * $questionsPerPage;
                $endIndex = min($startIndex + $questionsPerPage, $totalQuestions);
            ?>
            <div class="question-page" id="page-<?php echo $page; ?>" style="<?php echo $page > 1 ? 'display: none;' : ''; ?>">
                <?php for ($i = $startIndex; $i < $endIndex; $i++): 
                    $question = $questions[$i];
                    $questionNum = $i + 1;
                ?>
                <div class="question-container">
                    <div class="question-text"><?php echo $questionNum . '. ' . htmlspecialchars($question['question_text']); ?></div>
                    <div class="options-container">
                        <label class="option-label">
                            <input type="radio" name="question_<?php echo $question['id']; ?>" value="A">
                            <?php echo htmlspecialchars($question['option_a']); ?>
                        </label>
                        <label class="option-label">
                            <input type="radio" name="question_<?php echo $question['id']; ?>" value="B">
                            <?php echo htmlspecialchars($question['option_b']); ?>
                        </label>
                        <label class="option-label">
                            <input type="radio" name="question_<?php echo $question['id']; ?>" value="C">
                            <?php echo htmlspecialchars($question['option_c']); ?>
                        </label>
                        <label class="option-label">
                            <input type="radio" name="question_<?php echo $question['id']; ?>" value="D">
                            <?php echo htmlspecialchars($question['option_d']); ?>
                        </label>
                    </div>
                </div>
                <?php endfor; ?>
            </div>
            <?php endfor; ?>
            
            <?php if ($totalPages > 1): ?>
            <div class="pagination">
                <?php for ($page = 1; $page <= $totalPages; $page++): ?>
                <button type="button" class="page-btn <?php echo $page === 1 ? 'active' : ''; ?>" data-page="<?php echo $page; ?>">
                    <?php echo $page; ?>
                </button>
                <?php endfor; ?>
            </div>
            <?php endif; ?>
            
            <button type="submit" class="submit-btn">Submit Exam</button>
        </form>
        
        <div class="warning-modal" id="warningModal">
            <div class="modal-content">
                <h3>⚠️ Security Violation Detected</h3>
                <p>You have attempted to exit the secure exam environment.</p>
                <p>Your exam will be submitted automatically.</p>
                <div class="modal-buttons">
                    <button onclick="forceSubmit()">OK</button>
                </div>
            </div>
        </div>
    </div>

    <!-- <script src="js/script.js"></script> -->
    <script>
    // Enhanced Secure Exam Browser Functions
    document.addEventListener('DOMContentLoaded', function() {
        console.log('DOM loaded - Initializing secure lockdown mode');
        
        // Show fullscreen permission prompt
        document.getElementById('fullscreenPrompt').style.display = 'flex';
        
        // Setup event listener for fullscreen button
        document.getElementById('enableFullscreen').addEventListener('click', function() {
            enableLockdownMode();
        });
    });

    // Global variables
    let examTimerInterval;
    let examTimeLeft = <?php echo $examTimeSeconds; ?>;
    let tabChangeCount = 0;
    let isExamStarted = false;
    let isSubmitting = false;

    function enableLockdownMode() {
        console.log('Enabling complete lockdown mode...');
        
        // Hide permission prompt
        document.getElementById('fullscreenPrompt').style.display = 'none';
        
        // Enable fullscreen first
        enterFullscreenLockdown();
        
        // Setup security features
        setupExamSecurity();
        
        // Add lockdown mode class to body
        document.body.classList.add('lockdown-mode');
        
        // Start the timer only after fullscreen is enabled
        startExamTimer();
        
        // Set flag that exam has started
        isExamStarted = true;
        
        // Hide browser address bar (for mobile)
        setTimeout(function() {
            window.scrollTo(0, 1);
        }, 100);
    }
    
    function enterFullscreenLockdown() {
        console.log('Entering fullscreen lockdown mode...');
        
        const element = document.documentElement;
        
        // Try all fullscreen methods
        const requestFullscreen = element.requestFullscreen || 
                                element.webkitRequestFullscreen || 
                                element.mozRequestFullScreen || 
                                element.msRequestFullscreen;
        
        if (requestFullscreen) {
            requestFullscreen.call(element)
                .then(() => {
                    console.log('Fullscreen lockdown enabled successfully');
                    document.body.classList.add('lockdown-mode');
                    
                    // Continuous fullscreen maintenance
                    setInterval(() => {
                        if (!document.fullscreenElement && 
                            !document.webkitFullscreenElement && 
                            !document.mozFullScreenElement && 
                            !document.msFullscreenElement) {
                            requestFullscreen.call(element).catch(e => {
                                console.log('Fullscreen maintenance failed:', e);
                                forceSubmit();
                            });
                        }
                    }, 1000);
                })
                .catch(err => {
                    console.log('Fullscreen error:', err);
                    // Even if fullscreen fails, still enable lockdown features
                    document.body.classList.add('lockdown-mode');
                    showSecurityWarning('Fullscreen not available - Strict monitoring enabled');
                });
        } else {
            console.log('Fullscreen API not supported');
            document.body.classList.add('lockdown-mode');
            showSecurityWarning('Browser not supported - Strict monitoring enabled');
        }
    }

    function startExamTimer() {
        // Update timer display immediately
        updateTimerDisplay();
        
        // Start the timer interval
        examTimerInterval = setInterval(function() {
            examTimeLeft--;
            
            updateTimerDisplay();
            
            if (examTimeLeft <= 0) {
                clearInterval(examTimerInterval);
                submitExam();
            }
        }, 1000);
    }
    
    function updateTimerDisplay() {
        const minutes = Math.floor(examTimeLeft / 60);
        const seconds = examTimeLeft % 60;
        document.getElementById('examTimer').textContent = `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
    }

    function setupExamSecurity() {
        console.log('Setting up advanced exam security features...');
        
        // 1. Disable right-click (completely prevent, not just show warning)
        document.addEventListener('contextmenu', function(e) {
            e.preventDefault();
            return false;
        });
        
        // 2. Disable developer tools
        disableDeveloperTools();
        
        // 3. Prevent leaving page
        setupExitPrevention();
        
        // 4. Disable text selection
        disableTextSelection();
        
        // 5. Prevent keyboard shortcuts
        disableKeyboardShortcuts();
        
        // 6. Prevent screenshots (as much as possible)
        preventScreenshots();
    }
    
    function disableDeveloperTools() {
        // Prevent F12, Ctrl+Shift+I, Ctrl+Shift+C, etc.
        document.addEventListener('keydown', function(e) {
            // Developer tools shortcuts
            if (e.key === 'F12' || 
                (e.ctrlKey && e.shiftKey && e.key === 'I') ||
                (e.ctrlKey && e.shiftKey && e.key === 'C') ||
                (e.ctrlKey && e.key === 'U') ||
                (e.ctrlKey && e.key === 'R')) {
                e.preventDefault();
                return false;
            }
            
            // Navigation shortcuts
            if (e.ctrlKey && (e.key === 'r' || e.key === 'R')) {
                e.preventDefault();
                return false;
            }
            
            // Tab/window switching
            if (e.altKey || (e.ctrlKey && e.key === 'Tab')) {
                e.preventDefault();
                return false;
            }
            
            // Print screen
            if (e.key === 'PrintScreen') {
                e.preventDefault();
                return false;
            }
            
            // Escape key
            if (e.key === 'Escape') {
                e.preventDefault();
                return false;
            }
        });
    }
    
    function setupExitPrevention() {
        // Detect tab/window switching
        document.addEventListener('visibilitychange', function() {
            if (document.hidden && isExamStarted) {
                tabChangeCount++;
                
                if (tabChangeCount >= 3) {
                    forceSubmit();
                } else {
                    showViolationWarning(`Tab switch detected (${tabChangeCount}/3)`);
                }
            }
        });
        
        // Detect window resize (attempt to exit fullscreen)
        window.addEventListener('resize', function() {
            if (!document.fullscreenElement && 
                !document.webkitFullscreenElement && 
                !document.mozFullScreenElement && 
                !document.msFullscreenElement &&
                isExamStarted) {
                showViolationWarning('Fullscreen exit detected');
                setTimeout(enterFullscreenLockdown, 500);
            }
        });
        
        // Prevent page refresh - modified to not show browser dialog when submitting
        window.addEventListener('beforeunload', function(e) {
            if (isExamStarted && !isSubmitting) {
                e.preventDefault();
                e.returnValue = 'Leaving this page will automatically submit your exam. Are you sure?';
                return e.returnValue;
            }
            // When isSubmitting is true, no dialog will be shown
        });
        
        // Prevent back/forward navigation
        history.pushState(null, null, window.location.href);
        window.addEventListener('popstate', function() {
            history.pushState(null, null, window.location.href);
            if (isExamStarted) {
                showViolationWarning('Navigation prevented');
            }
        });
    }
    
    function disableTextSelection() {
        document.addEventListener('selectstart', function(e) {
            e.preventDefault();
            return false;
        });
        
        document.addEventListener('dragstart', function(e) {
            e.preventDefault();
            return false;
        });
        
        // Apply CSS to prevent text selection
        document.body.style.userSelect = 'none';
        document.body.style.webkitUserSelect = 'none';
        document.body.style.mozUserSelect = 'none';
        document.body.style.msUserSelect = 'none';
    }
    
    function disableKeyboardShortcuts() {
        // Block additional shortcuts
        document.addEventListener('keydown', function(e) {
            // Function keys
            if (e.key.startsWith('F') && e.key.length > 1) {
                e.preventDefault();
                return false;
            }
        });
    }
    
    function preventScreenshots() {
        // As much as possible in a browser environment
        document.addEventListener('keydown', function(e) {
            // Windows + PrintScreen, etc.
            if (e.key === 'PrintScreen' || (e.ctrlKey && e.key === 'p')) {
                e.preventDefault();
                return false;
            }
        });
    }
    
    function showViolationWarning(message) {
        console.log('Security violation:', message);
        const warningModal = document.getElementById('warningModal');
        if (warningModal) {
            warningModal.style.display = 'flex';
        }
    }
    
    function showSecurityWarning(message) {
        const banner = document.getElementById('securityBanner');
        if (banner) {
            banner.textContent = '⚠️ ' + message;
        }
    }
    
    function submitExam() {
        if (isSubmitting) return; // Prevent multiple submissions
        
        isSubmitting = true;
        console.log('Submitting exam normally');
        clearInterval(examTimerInterval);
        
        // Submit the form without showing browser warnings
        document.getElementById('examForm').submit();
    }
    
    function forceSubmit() {
        if (isSubmitting) return; // Prevent multiple submissions
        
        isSubmitting = true;
        console.log('Force submitting exam due to security violation');
        clearInterval(examTimerInterval);
        
        // Clear all storage
        sessionStorage.clear();
        localStorage.clear();
        
        // Submit the form without showing browser warnings
        document.getElementById('examForm').submit();
    }
    
    // Fullscreen change detection
    const fullscreenEvents = [
        'fullscreenchange', 
        'webkitfullscreenchange', 
        'mozfullscreenchange', 
        'MSFullscreenChange'
    ];
    
    fullscreenEvents.forEach(event => {
        document.addEventListener(event, function() {
            const isFullscreen = document.fullscreenElement || 
                               document.webkitFullscreenElement || 
                               document.mozFullScreenElement || 
                               document.msFullscreenElement;
            
            if (!isFullscreen && isExamStarted) {
                showViolationWarning('Fullscreen mode exited');
                setTimeout(enterFullscreenLockdown, 100);
            }
        });
    });
    
    // Continuous security monitoring
    setInterval(() => {
        // Check if still in fullscreen
        const isFullscreen = document.fullscreenElement || 
                           document.webkitFullscreenElement || 
                           document.mozFullScreenElement || 
                           document.msFullscreenElement;
        
        if (!isFullscreen && isExamStarted) {
            enterFullscreenLockdown();
        }
    }, 2000);
    </script>
</body>     
</html>
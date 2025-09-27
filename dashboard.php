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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
        
        /* Summary page styles */
        .summary-container {
            display: none;
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            margin: 20px 0;
            text-align: center;
        }
        
        .summary-title {
            color: #311b92;
            margin-bottom: 25px;
            font-size: 24px;
            border-bottom: 2px solid #eee;
            padding-bottom: 15px;
        }
        
        .summary-stats {
            display: flex;
            justify-content: space-around;
            flex-wrap: wrap;
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-box {
            flex: 1;
            min-width: 150px;
            padding: 20px;
            border-radius: 10px;
            color: white;
            font-weight: bold;
        }
        
        .total-questions {
            background: linear-gradient(135deg, #2196F3 0%, #1976D2 100%);
        }
        
        .attempted {
            background: linear-gradient(135deg, #4CAF50 0%, #2E7D32 100%);
        }
        
        .not-attempted {
            background: linear-gradient(135deg, #F44336 0%, #D32F2F 100%);
        }
        
        .stat-number {
            font-size: 32px;
            margin-bottom: 5px;
        }
        
        .stat-label {
            font-size: 16px;
        }
        
        .summary-actions {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-top: 20px;
        }
        
        .back-to-exam {
            background: #FF9800;
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: bold;
            transition: all 0.3s;
        }
        
        .confirm-submit {
            background: #F44336;
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: bold;
            transition: all 0.3s;
        }
        
        .back-to-exam:hover, .confirm-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        
        .question-status-container {
            margin: 20px 0;
            padding: 20px;
            background: #f9f9f9;
            border-radius: 10px;
        }
        
        .question-status-title {
            font-size: 18px;
            margin-bottom: 15px;
            color: #311b92;
            font-weight: bold;
        }
        
        .question-status-buttons {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            justify-content: center;
        }
        
        .question-status-btn {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            cursor: pointer;
            border: 2px solid #ddd;
            background: white;
            transition: all 0.2s;
        }
        
        .question-status-btn.attempted {
            border-color: #4CAF50;
            background: #E8F5E9;
            color: #2E7D32;
        }
        
        .question-status-btn.current {
            border-color: #2196F3;
            background: #E3F2FD;
            color: #1976D2;
            transform: scale(1.1);
        }
        
        .question-status-btn:hover {
            transform: scale(1.1);
        }
        
        /* Warning modal */
        .warning-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.8);
            z-index: 999999;
            justify-content: center;
            align-items: center;
        }
        
        .modal-content {
            background: white;
            padding: 30px;
            border-radius: 15px;
            text-align: center;
            max-width: 500px;
            width: 90%;
        }
        
        .modal-content h3 {
            color: #D32F2F;
            margin-top: 0;
        }
        
        .modal-buttons {
            margin-top: 20px;
        }
        
        .modal-buttons button {
            background: #2196F3;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
        }
        
        /* Pagination */
        .pagination {
            display: flex;
            justify-content: center;
            margin: 20px 0;
            gap: 10px;
        }
        
        .page-btn {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            border: 2px solid #ddd;
            background: white;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .page-btn.active {
            border-color: #2196F3;
            background: #E3F2FD;
            color: #1976D2;
        }
        
        .page-btn:hover {
            transform: scale(1.1);
        }
        
        /* Submit button */
        .submit-btn {
            background: linear-gradient(135deg, #4CAF50 0%, #2E7D32 100%);
            color: white;
            border: none;
            padding: 15px 30px;
            font-size: 18px;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s;
            margin: 20px auto;
            display: block;
        }
        
        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
        }
    </style>
</head>
<body data-student-id="<?php echo $_SESSION['student_id']; ?>" data-total-questions="<?php echo $totalQuestions; ?>">
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
        
        <!-- Exam Form -->
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
                <div class="question-container" data-question-id="<?php echo $question['id']; ?>" data-question-number="<?php echo $questionNum; ?>">
                    <div class="question-text"><?php echo $questionNum . '. ' . htmlspecialchars($question['question_text']); ?></div>
                    <div class="options-container">
                        <label class="option-label">
                            <input type="radio" name="question_<?php echo $question['id']; ?>" value="A" data-question-id="<?php echo $question['id']; ?>">
                            <?php echo htmlspecialchars($question['option_a']); ?>
                        </label>
                        <label class="option-label">
                            <input type="radio" name="question_<?php echo $question['id']; ?>" value="B" data-question-id="<?php echo $question['id']; ?>">
                            <?php echo htmlspecialchars($question['option_b']); ?>
                        </label>
                        <label class="option-label">
                            <input type="radio" name="question_<?php echo $question['id']; ?>" value="C" data-question-id="<?php echo $question['id']; ?>">
                            <?php echo htmlspecialchars($question['option_c']); ?>
                        </label>
                        <label class="option-label">
                            <input type="radio" name="question_<?php echo $question['id']; ?>" value="D" data-question-id="<?php echo $question['id']; ?>">
                            <?php echo htmlspecialchars($question['option_d']); ?>
                        </label>
                    </div>
                </div>
                <?php endfor; ?>
                
                <?php if ($page == $totalPages): ?>
                <div class="btn-container">
                    <button type="button" class="submit-btn" id="reviewExamBtn">Review Exam</button>
                </div>
                <?php endif; ?>
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
        </form>
        
        <!-- Summary Page -->
        <div class="summary-container" id="summaryPage">
            <h2 class="summary-title">Exam Summary</h2>
            
            <div class="summary-stats">
                <div class="stat-box total-questions">
                    <div class="stat-number" id="totalQuestions">0</div>
                    <div class="stat-label">Total Questions</div>
                </div>
                
                <div class="stat-box attempted">
                    <div class="stat-number" id="attemptedQuestions">0</div>
                    <div class="stat-label">Attempted</div>
                </div>
                
                <div class="stat-box not-attempted">
                    <div class="stat-number" id="notAttemptedQuestions">0</div>
                    <div class="stat-label">Not Attempted</div>
                </div>
            </div>
            
            <div class="question-status-container">
                <div class="question-status-title">Question Status</div>
                <div class="question-status-buttons" id="questionStatusButtons">
                    <!-- Question status buttons will be generated here by JavaScript -->
                </div>
            </div>
            
            <div class="summary-actions">
                <button type="button" class="back-to-exam" id="backToExamBtn">
                    <i class="fas fa-arrow-left"></i> Back to Exam
                </button>
                <button type="button" class="confirm-submit" id="confirmSubmitBtn">
                    <i class="fas fa-paper-plane"></i> Confirm Submission
                </button>
            </div>
        </div>
        
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
        
        // Setup event listeners for new buttons
        document.getElementById('reviewExamBtn').addEventListener('click', showSummaryPage);
        document.getElementById('backToExamBtn').addEventListener('click', hideSummaryPage);
        document.getElementById('confirmSubmitBtn').addEventListener('click', submitExam);
        
        // Initialize question status tracking
        initializeQuestionStatus();
        
        // Setup pagination
        setupPagination();
    });

    // Global variables
    let examTimerInterval;
    let examTimeLeft = <?php echo $examTimeSeconds; ?>;
    let tabChangeCount = 0;
    let isExamStarted = false;
    let isSubmitting = false;
    let currentPage = 1;
    const totalQuestions = <?php echo $totalQuestions; ?>;
    const totalPages = Math.ceil(totalQuestions / 10);
    let answeredQuestions = new Set();

    function setupPagination() {
        document.querySelectorAll('.page-btn').forEach(button => {
            button.addEventListener('click', function() {
                const pageNum = parseInt(this.dataset.page);
                showPage(pageNum);
            });
        });
    }

    function initializeQuestionStatus() {
        // Load any previously answered questions from sessionStorage
        const savedAnswers = sessionStorage.getItem('answeredQuestions');
        if (savedAnswers) {
            answeredQuestions = new Set(JSON.parse(savedAnswers));
        }
        
        // Set up change listeners for all radio buttons
        document.querySelectorAll('input[type="radio"]').forEach(radio => {
            radio.addEventListener('change', function() {
                const questionId = this.getAttribute('data-question-id');
                answeredQuestions.add(questionId);
                
                // Save to sessionStorage
                sessionStorage.setItem('answeredQuestions', JSON.stringify(Array.from(answeredQuestions)));
                
                // Update the question status display if we're on the summary page
                if (document.getElementById('summaryPage').style.display === 'block') {
                    updateQuestionStatusButtons();
                    updateSummaryStats();
                }
            });
        });
        
        // Check initially which questions are answered
        document.querySelectorAll('input[type="radio"]:checked').forEach(radio => {
            const questionId = radio.getAttribute('data-question-id');
            answeredQuestions.add(questionId);
        });
        
        // Save to sessionStorage
        sessionStorage.setItem('answeredQuestions', JSON.stringify(Array.from(answeredQuestions)));
        
        // Generate question status buttons
        generateQuestionStatusButtons();
    }

    function generateQuestionStatusButtons() {
        const container = document.getElementById('questionStatusButtons');
        container.innerHTML = '';
        
        for (let i = 1; i <= totalQuestions; i++) {
            const btn = document.createElement('div');
            btn.className = 'question-status-btn';
            btn.textContent = i;
            btn.setAttribute('data-question', i);
            
            // Check if this question is answered
            const questionId = getQuestionIdByNumber(i);
            if (answeredQuestions.has(questionId)) {
                btn.classList.add('attempted');
            }
            
            // Add click event to navigate to the question
            btn.addEventListener('click', function() {
                goToQuestion(i);
            });
            
            container.appendChild(btn);
        }
    }

    function updateQuestionStatusButtons() {
        document.querySelectorAll('.question-status-btn').forEach(btn => {
            const questionNum = parseInt(btn.getAttribute('data-question'));
            const questionId = getQuestionIdByNumber(questionNum);
            
            // Update classes based on answered status
            if (answeredQuestions.has(questionId)) {
                btn.classList.add('attempted');
            } else {
                btn.classList.remove('attempted');
            }
        });
    }

    function getQuestionIdByNumber(questionNumber) {
        // Find the question element by its number
        const questionElements = document.querySelectorAll('.question-container');
        for (let i = 0; i < questionElements.length; i++) {
            const qNum = parseInt(questionElements[i].getAttribute('data-question-number'));
            if (qNum === questionNumber) {
                return questionElements[i].getAttribute('data-question-id');
            }
        }
        return questionNumber.toString(); // Fallback
    }

    function goToQuestion(questionNumber) {
        // Calculate which page this question is on
        const targetPage = Math.ceil(questionNumber / 10);
        
        // Show that page
        showPage(targetPage);
        
        // Hide summary page
        hideSummaryPage();
        
        // Scroll to the question
        const questionElements = document.querySelectorAll('.question-container');
        if (questionNumber <= questionElements.length) {
            questionElements[questionNumber - 1].scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
        
        // Highlight the current question in the summary
        document.querySelectorAll('.question-status-btn').forEach(btn => {
            btn.classList.remove('current');
        });
        const statusBtn = document.querySelector(`.question-status-btn[data-question="${questionNumber}"]`);
        if (statusBtn) {
            statusBtn.classList.add('current');
        }
    }

    function showSummaryPage() {
        // Hide all question pages
        document.querySelectorAll('.question-page').forEach(page => {
            page.style.display = 'none';
        });
        
        // Hide pagination
        if (document.querySelector('.pagination')) {
            document.querySelector('.pagination').style.display = 'none';
        }
        
        // Show summary page
        document.getElementById('summaryPage').style.display = 'block';
        
        // Update summary stats
        updateSummaryStats();
        
        // Update question status buttons
        updateQuestionStatusButtons();
    }

    function hideSummaryPage() {
        // Hide summary page
        document.getElementById('summaryPage').style.display = 'none';
        
        // Show pagination
        if (document.querySelector('.pagination')) {
            document.querySelector('.pagination').style.display = 'flex';
        }
        
        // Show the current page
        showPage(currentPage);
    }

    function updateSummaryStats() {
        document.getElementById('totalQuestions').textContent = totalQuestions;
        document.getElementById('attemptedQuestions').textContent = answeredQuestions.size;
        document.getElementById('notAttemptedQuestions').textContent = totalQuestions - answeredQuestions.size;
    }

    function showPage(pageNum) {
        // Hide all pages
        document.querySelectorAll('.question-page').forEach(page => {
            page.style.display = 'none';
        });
        
        // Show selected page
        const currentPageElement = document.getElementById(`page-${pageNum}`);
        if (currentPageElement) {
            currentPageElement.style.display = 'block';
        }
        
        // Update active page button
        document.querySelectorAll('.page-btn').forEach(button => {
            if (parseInt(button.dataset.page) === pageNum) {
                button.classList.add('active');
            } else {
                button.classList.remove('active');
            }
        });
        
        // Update current page
        currentPage = pageNum;
    }

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
        if (isSubmitting) return; 
        
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
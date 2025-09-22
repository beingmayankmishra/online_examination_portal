<?php
// submit_exam.php - COMPLETE FIXED VERSION
require_once 'includes/auth.php';
require_once 'includes/db_connect.php';

// Check if this is a timeout submission
$isTimeout = isset($_GET['timeout']) && $_GET['timeout'] === 'true';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if student is logged in (unless it's a timeout)
if (!isStudentLoggedIn() && !$isTimeout) {
    header('Location: index.php');
    exit();
}

$studentId = $_SESSION['student_id'] ?? null;

// For timeout case, we need to get student ID from session
if ($isTimeout && empty($studentId)) {
    // Try to get student ID from session data if available
    $studentId = $_SESSION['student_id'] ?? null;
}

// If we still don't have a student ID, redirect to login
if (!$studentId) {
    header('Location: index.php');
    exit();
}

// Check if student has already submitted (unless it's a timeout)
if (!$isTimeout) {
    $checkStmt = $pdo->prepare("SELECT * FROM exam_results WHERE student_id = ?");
    $checkStmt->execute([$studentId]);

    if ($checkStmt->fetch()) {
        // Already submitted
        header('Location: index.php?error=already_submitted');
        exit();
    }
}

// Process exam submission for POST requests or timeout
if ($_SERVER['REQUEST_METHOD'] === 'POST' || $isTimeout) {
    // Get all active questions
    $stmt = $pdo->query("SELECT * FROM questions WHERE is_active = TRUE");
    $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $totalQuestions = count($questions);
    
    $attempted = 0;
    $correctAnswers = 0;
    $wrongAnswers = 0;
    
    // Process each question
    foreach ($questions as $question) {
        $questionId = $question['id'];
        
        if ($isTimeout) {
            // For timeout, try to get answers from session or database
            $selectedOption = '';
            
            // First try to get from session
            $answerKey = "answer_{$studentId}_{$questionId}";
            if (isset($_SESSION[$answerKey])) {
                $selectedOption = $_SESSION[$answerKey];
            } else {
                // If not in session, check database
                $responseStmt = $pdo->prepare("SELECT selected_option FROM exam_responses WHERE student_id = ? AND question_id = ?");
                $responseStmt->execute([$studentId, $questionId]);
                $response = $responseStmt->fetch();
                
                if ($response && !empty($response['selected_option'])) {
                    $selectedOption = $response['selected_option'];
                }
            }
        } else {
            // For normal submission, get from POST
            $selectedOption = $_POST["question_$questionId"] ?? '';
        }
        
        if (!empty($selectedOption)) {
            $attempted++;
            
            // Check if answer is correct
            $isCorrect = ($selectedOption === $question['correct_option']);
            
            if ($isCorrect) {
                $correctAnswers++;
            } else {
                $wrongAnswers++;
            }
            
            // Update response in database
            $updateStmt = $pdo->prepare("UPDATE exam_responses SET selected_option = ?, is_correct = ? WHERE student_id = ? AND question_id = ?");
            $updateStmt->execute([$selectedOption, $isCorrect, $studentId, $questionId]);
        }
    }
    
    $unattempted = $totalQuestions - $attempted;
    $marks = $correctAnswers; // Assuming each correct answer gives 1 mark
    
    // Save exam results
    $resultStmt = $pdo->prepare("INSERT INTO exam_results (student_id, total_questions, attempted, correct_answers, wrong_answers, marks, submitted_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
    $resultStmt->execute([$studentId, $totalQuestions, $attempted, $correctAnswers, $wrongAnswers, $marks]);
    
    // Mark student as attempted
    $updateStudentStmt = $pdo->prepare("UPDATE students SET exam_attempted = TRUE WHERE id = ?");
    $updateStudentStmt->execute([$studentId]);
    
    // Output JavaScript to clear sessionStorage BEFORE session destruction
    echo '<script>
        // Clear all exam-related sessionStorage data
        Object.keys(sessionStorage).forEach(key => {
            if (key.startsWith("examTimeLeft") || 
                key.startsWith("examStartTime") || 
                key.startsWith("tabChangeCount") || 
                key.startsWith("examInitialized") ||
                key.startsWith("answer_") ||
                key === "currentStudentId" ||
                key === "kioskModeAgreed") {
                sessionStorage.removeItem(key);
            }
        });
    </script>';
    
    // Clear PHP session data but keep it active for redirect
    $_SESSION = array();
    
    // Redirect with appropriate message
    if ($isTimeout) {
        header('Location: index.php?error=timeout');
    } else {
        header('Location: index.php?success=exam_submitted');
    }
    exit();
}

// If not a POST request or timeout, redirect to index
header('Location: index.php');
exit();
?>
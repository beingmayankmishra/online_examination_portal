<?php

require_once 'includes/auth.php';
require_once 'includes/db_connect.php';

if (!isStudentLoggedIn()) {
    header('Location: index.php');
    exit();
}

$studentId = $_SESSION['student_id'];

// Check if student has already submitted
$checkStmt = $pdo->prepare("SELECT * FROM exam_results WHERE student_id = ?");
$checkStmt->execute([$studentId]);

if ($checkStmt->fetch()) {
    // Already submitted
    header('Location: index.php?error=already_submitted');
    exit();
}

// Process exam submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get all questions
    $stmt = $pdo->query("SELECT * FROM questions WHERE is_active = TRUE");
    $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $totalQuestions = count($questions);
    
    $attempted = 0;
    $correctAnswers = 0;
    $wrongAnswers = 0;
    
    // Process each question
    foreach ($questions as $question) {
        $questionId = $question['id'];
        $selectedOption = $_POST["question_$questionId"] ?? '';
        
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
    
    // Clear session and redirect
    session_destroy();
    header('Location: index.php?success=exam_submitted');
    exit();
}
?>
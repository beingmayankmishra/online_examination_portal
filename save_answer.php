<?php
require_once 'includes/db_connect.php';
require_once 'includes/auth.php';

if (!isStudentLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $questionId = $_POST['question_id'] ?? '';
    $selectedOption = $_POST['selected_option'] ?? ''; 
    $studentId = $_SESSION['student_id'];
    
    if (empty($questionId)) {
        echo json_encode(['success' => false, 'message' => 'Question ID required']);
        exit();
    }
    
    try {
        // Check if response already exists
        $checkStmt = $pdo->prepare("SELECT id FROM exam_responses WHERE student_id = ? AND question_id = ?");
        $checkStmt->execute([$studentId, $questionId]);
        
        if ($checkStmt->fetch()) {
            // Update existing response
            $updateStmt = $pdo->prepare("UPDATE exam_responses SET selected_option = ? WHERE student_id = ? AND question_id = ?");
            $updateStmt->execute([$selectedOption, $studentId, $questionId]);
        } else {
            // Insert new response
            $insertStmt = $pdo->prepare("INSERT INTO exam_responses (student_id, question_id, selected_option) VALUES (?, ?, ?)");
            $insertStmt->execute([$studentId, $questionId, $selectedOption]);
        }
        
        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>
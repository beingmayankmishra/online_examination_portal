<?php
require_once '../includes/auth.php';
require_once '../includes/db_connect.php';
redirectIfNotLoggedIn('admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $questionId = $_POST['question_id'] ?? '';
    
    if (!empty($questionId)) {
        try {
            // Begin transaction
            $pdo->beginTransaction();
            
            // Delete exam responses for this question
            $deleteResponses = $pdo->prepare("DELETE FROM exam_responses WHERE question_id = ?");
            $deleteResponses->execute([$questionId]);
            
            // Delete question
            $deleteQuestion = $pdo->prepare("DELETE FROM questions WHERE id = ?");
            $deleteQuestion->execute([$questionId]);
            
            // Commit transaction
            $pdo->commit();
            
            $_SESSION['message'] = "Question deleted successfully.";
        } catch (PDOException $e) {
            $pdo->rollBack();
            $_SESSION['error'] = "Error deleting question: " . $e->getMessage();
        }
    } else {
        $_SESSION['error'] = "Question ID required.";
    }
}

header('Location: upload_questions.php');
exit();
?>
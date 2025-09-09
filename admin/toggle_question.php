<?php
require_once '../includes/auth.php';
require_once '../includes/db_connect.php';
redirectIfNotLoggedIn('admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $questionId = $_POST['question_id'] ?? '';
    $currentStatus = $_POST['current_status'] ?? 0;
    
    if (!empty($questionId)) {
        $newStatus = $currentStatus ? 0 : 1;
        
        try {
            $stmt = $pdo->prepare("UPDATE questions SET is_active = ? WHERE id = ?");
            $stmt->execute([$newStatus, $questionId]);
            
            $_SESSION['message'] = "Question status updated successfully.";
        } catch (PDOException $e) {
            $_SESSION['error'] = "Error updating question status: " . $e->getMessage();
        }
    } else {
        $_SESSION['error'] = "Question ID required.";
    }
}

header('Location: upload_questions.php');
exit();
?>
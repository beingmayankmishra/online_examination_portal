<?php
require_once '../includes/auth.php';
require_once '../includes/db_connect.php';
redirectIfNotLoggedIn('admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $studentId = $_POST['student_id'] ?? '';
    
    if (!empty($studentId)) {
        try {
            // Begin transaction
            $pdo->beginTransaction();
            
            // Delete exam responses
            $deleteResponses = $pdo->prepare("DELETE FROM exam_responses WHERE student_id = ?");
            $deleteResponses->execute([$studentId]);
            
            // Delete exam results
            $deleteResults = $pdo->prepare("DELETE FROM exam_results WHERE student_id = ?");
            $deleteResults->execute([$studentId]);
            
            // Delete student
            $deleteStudent = $pdo->prepare("DELETE FROM students WHERE id = ?");
            $deleteStudent->execute([$studentId]);
            
            // Commit transaction
            $pdo->commit();
            
            $_SESSION['message'] = "Student deleted successfully.";
        } catch (PDOException $e) {
            $pdo->rollBack();
            $_SESSION['error'] = "Error deleting student: " . $e->getMessage();
        }
    } else {
        $_SESSION['error'] = "Student ID required.";
    }
}

header('Location: upload_students.php');
exit();
?>
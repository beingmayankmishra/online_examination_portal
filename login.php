<?php

require_once 'includes/db_connect.php';
require_once 'includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $enrollment = $_POST['enrollment'];
    $dob = $_POST['dob'];
    
    // Check if student exists and hasn't attempted the exam
    $stmt = $pdo->prepare("SELECT * FROM students WHERE enrollment_number = ? AND dob = ?");
    $stmt->execute([$enrollment, $dob]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($student) {
        if ($student['exam_attempted']) {
            // Student has already attempted the exam
            header('Location: index.php?error=already_attempted');
            exit();
        }
        
        // Start session and redirect to exam
        session_start();
        $_SESSION['student_id'] = $student['id'];
        $_SESSION['enrollment_number'] = $student['enrollment_number'];
        $_SESSION['student_name'] = $student['name'];
        
        // Set flag to clear session storage on next page load
        $_SESSION['clear_session_storage'] = true;
        
        header('Location: dashboard.php');
        exit();
    } else {
        // Invalid credentials
        header('Location: index.php?error=invalid_credentials');
        exit();
    }
}
?>
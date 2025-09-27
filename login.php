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
        header('Location: index.php?error=already_attempted');
        exit();
    }
    
    // Start session and set student data
    session_start();
    $_SESSION['student_id'] = $student['id'];
    $_SESSION['enrollment_number'] = $student['enrollment_number'];
    $_SESSION['student_name'] = $student['name'];
    
    // Redirect to instructions instead of directly to exam
        header('Location: instructions.php');
        exit();
    } else {
        // Invalid credentials - redirect back to login with error
        header('Location: index.php?error=invalid_credentials');
        exit();
    }
}

// If not POST request or any other issue, redirect to login
header('Location: index.php');
exit();
?>

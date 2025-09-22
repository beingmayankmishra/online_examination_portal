<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function isStudentLoggedIn() {
    return isset($_SESSION['student_id']) && !empty($_SESSION['student_id']);
}


function isAdminLoggedIn() {
    return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
}

function redirectIfNotLoggedIn($type = 'student') {
    if ($type === 'student' && !isStudentLoggedIn()) {
        header('Location: ../index.php');
        exit();
    } elseif ($type === 'admin' && !isAdminLoggedIn()) {
        header('Location: index.php');
        exit();
    }
}
?>
<?php

require_once 'includes/auth.php'; 


unset($_SESSION['exam_start_time']);
unset($_SESSION['exam_duration']);
unset($_SESSION['exam_initialized']);
unset($_SESSION['clear_session_storage']);


unset($_SESSION['student_id']);
unset($_SESSION['enrollment_number']);
unset($_SESSION['student_name']);


$_SESSION = array();

// If it's desired to kill the session, also delete the session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destroy the session
session_destroy();

// Clear browser sessionStorage via JavaScript before redirecting
echo '<script>
    // Clear all exam-related sessionStorage data
    Object.keys(sessionStorage).forEach(key => {
        if (key.startsWith("examTimeLeft_") || 
            key.startsWith("examStartTime_") || 
            key.startsWith("tabChangeCount_") || 
            key.startsWith("examInitialized_") ||
            key.startsWith("answer_") ||
            key === "currentStudentId") {
            sessionStorage.removeItem(key);
        }
    });
    
    // Redirect after clearing storage
    setTimeout(function() {
        window.location.href = "index.php";
    }, 100);
</script>';

// Exit the script
exit();
?>
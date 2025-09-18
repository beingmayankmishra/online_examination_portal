<?php

session_start();

// Check if we need to clear sessionStorage from previous student
if (isset($_SESSION['clear_session_storage']) && $_SESSION['clear_session_storage']) {
    unset($_SESSION['clear_session_storage']);
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
    </script>';
}


require_once 'includes/auth.php';
require_once 'includes/db_connect.php';
redirectIfNotLoggedIn('student');

// Get all active questions
$stmt = $pdo->query("SELECT * FROM questions WHERE is_active = TRUE ORDER BY RAND()");
$questions = $stmt->fetchAll(PDO::FETCH_ASSOC);
$totalQuestions = count($questions);

// Calculate exam time (1 minute per question)
$examTimeMinutes = $totalQuestions;
$examTimeSeconds = $examTimeMinutes * 60;



// Initialize response records for this student
$studentId = $_SESSION['student_id'];
foreach ($questions as $question) {
    $checkStmt = $pdo->prepare("SELECT id FROM exam_responses WHERE student_id = ? AND question_id = ?");
    $checkStmt->execute([$studentId, $question['id']]);
    
    if (!$checkStmt->fetch()) {
        $insertStmt = $pdo->prepare("INSERT INTO exam_responses (student_id, question_id) VALUES (?, ?)");
        $insertStmt->execute([$studentId, $question['id']]);
    }
}

// Get remaining time from session or set initial time
if (!isset($_SESSION['exam_start_time'])) {
    $_SESSION['exam_start_time'] = time();
    $_SESSION['exam_duration'] = $examTimeSeconds;
}



$elapsedTime = time() - $_SESSION['exam_start_time'];
$remainingTime = max(0, $_SESSION['exam_duration'] - $elapsedTime);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Exam Dashboard - Mind Power University</title>
    <link rel="icon" type="image/png"  href="css/images/MPU_favicon.jpg?v=2">
    <link rel="stylesheet" href="css/style.css">
</head>
<body data-student-id="<?php echo $_SESSION['student_id']; ?>">
    <div class="container">
        <div class="dashboard-header">
            <div class="student-info">
                Enrollment: <strong><?php echo $_SESSION['enrollment_number']; ?></strong> | 
                Name: <strong><?php echo $_SESSION['student_name']; ?></strong>
            </div>
            <div class="timer" id="examTimer" data-time="<?php echo $remainingTime; ?>">
                <?php echo sprintf('%02d:%02d', floor($remainingTime / 60), $remainingTime % 60); ?>
            </div>
        </div>
        
        <form id="examForm" action="submit_exam.php" method="POST">
            <?php
            // Split questions into pages (10 questions per page)
            $questionsPerPage = 10;
            $totalPages = ceil($totalQuestions / $questionsPerPage);
            
            for ($page = 1; $page <= $totalPages; $page++):
                $startIndex = ($page - 1) * $questionsPerPage;
                $endIndex = min($startIndex + $questionsPerPage, $totalQuestions);
            ?>
            <div class="question-page" id="page-<?php echo $page; ?>" style="<?php echo $page > 1 ? 'display: none;' : ''; ?>">
                <?php for ($i = $startIndex; $i < $endIndex; $i++): 
                    $question = $questions[$i];
                    $questionNum = $i + 1;
                ?>
                <div class="question-container">
                    <div class="question-text"><?php echo $questionNum . '. ' . $question['question_text']; ?></div>
                    <div class="options-container">
                        <label class="option-label">
                            <input type="radio" name="question_<?php echo $question['id']; ?>" value="A">
                            <?php echo $question['option_a']; ?>
                        </label>
                        <label class="option-label">
                            <input type="radio" name="question_<?php echo $question['id']; ?>" value="B">
                            <?php echo $question['option_b']; ?>
                        </label>
                        <label class="option-label">
                            <input type="radio" name="question_<?php echo $question['id']; ?>" value="C">
                            <?php echo $question['option_c']; ?>
                        </label>
                        <label class="option-label">
                            <input type="radio" name="question_<?php echo $question['id']; ?>" value="D">
                            <?php echo $question['option_d']; ?>
                        </label>
                    </div>
                </div>
                <?php endfor; ?>
            </div>
            <?php endfor; ?>
            
            <?php if ($totalPages > 1): ?>
            <div class="pagination">
                <?php for ($page = 1; $page <= $totalPages; $page++): ?>
                <button type="button" class="page-btn <?php echo $page === 1 ? 'active' : ''; ?>" data-page="<?php echo $page; ?>">
                    <?php echo $page; ?>
                </button>
                <?php endfor; ?>
            </div>
            <?php endif; ?>
            
            <button type="submit" class="submit-btn">Submit Exam</button>
        </form>
        
        <div class="warning-modal" id="warningModal">
            <div class="modal-content">
                <h3>Warning: Tab Change Detected</h3>
                <p>You have changed tabs <span id="warningCount">1</span> time(s).</p>
                <p>Multiple tab changes may result in automatic submission of your exam.</p>
                <div class="modal-buttons">
                    <button id="closeWarning">I Understand</button>
                </div>
            </div>
        </div>
    </div>
    
    <script src="js/script.js"></script>
</body>
</html>
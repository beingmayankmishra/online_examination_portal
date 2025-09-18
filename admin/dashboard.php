<?php
require_once '../includes/auth.php';
require_once '../includes/db_connect.php';
redirectIfNotLoggedIn('admin');

require_once '../includes/db_connect.php';

// Get statistics
$studentsCount = $pdo->query("SELECT COUNT(*) FROM students")->fetchColumn();
$questionsCount = $pdo->query("SELECT COUNT(*) FROM questions WHERE is_active = TRUE")->fetchColumn();
$examAttempts = $pdo->query("SELECT COUNT(*) FROM exam_results")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>    
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard  Mind Power University</title>
    <link rel="icon" type="image/png"  href="../css/images/MPU_favicon.jpg">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <div class="admin-container">
        <div class="admin-header">
            <h1>Admin Dashboard</h1>
            <a href="logout.php" style="color: white;">Logout</a>
        </div>
        
        <div class="admin-nav">
            <ul>
                <li><a href="dashboard.php" class="active">Dashboard</a></li>
                <li><a href="upload_students.php">Upload Students</a></li>
                <li><a href="upload_questions.php">Upload Questions</a></li>
                <li><a href="export_reports.php">Export Reports</a></li>
            </ul>
        </div>
        
        <div class="admin-content">
            <h2>Overview</h2>
            
            <div class="stats-container">
                <div class="stat-card">
                    <h3>Total Students</h3>
                    <p><?php echo $studentsCount; ?></p>
                </div>
                
                <div class="stat-card">
                    <h3>Active Questions</h3>
                    <p><?php echo $questionsCount; ?></p>
                </div>
                
                <div class="stat-card">
                    <h3>Exam Attempts</h3>
                    <p><?php echo $examAttempts; ?></p>
                </div>
            </div>
            
            <h2>Recent Exam Results</h2>
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Enrollment Number</th>
                            <th>Name</th>
                            <th>Total Questions</th>
                            <th>Attempted</th>
                            <th>Correct</th>
                            <th>Marks</th>
                            <th>Submitted At</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $stmt = $pdo->query("
                            SELECT s.enrollment_number, s.name, er.total_questions, er.attempted, 
                                   er.correct_answers, er.marks, er.submitted_at
                            FROM exam_results er
                            JOIN students s ON er.student_id = s.id
                            ORDER BY er.submitted_at DESC
                            LIMIT 10
                        ");
                        
                        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)):
                        ?>
                        <tr>
                            <td><?php echo $row['enrollment_number']; ?></td>
                            <td><?php echo $row['name']; ?></td>
                            <td><?php echo $row['total_questions']; ?></td>
                            <td><?php echo $row['attempted']; ?></td>
                            <td><?php echo $row['correct_answers']; ?></td>
                            <td><?php echo $row['marks']; ?></td>
                            <td><?php echo $row['submitted_at']; ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>
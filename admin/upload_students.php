<?php
require_once '../includes/auth.php';
require_once '../includes/db_connect.php';
redirectIfNotLoggedIn('admin');

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['student_file'])) {
    $file = $_FILES['student_file'];
    
    if ($file['error'] === UPLOAD_ERR_OK) {
        $fileType = pathinfo($file['name'], PATHINFO_EXTENSION);
        
        if ($fileType === 'csv') {
            $handle = fopen($file['tmp_name'], 'r');
            
            // Skip header row
            fgetcsv($handle);
            
            $successCount = 0;
            $errorCount = 0;
            
            while (($data = fgetcsv($handle)) !== FALSE) {
                if (count($data) >= 3) {
                    $enrollment = trim($data[0]);
                    $name = trim($data[1]);
                    $dob = trim($data[2]);
                    
                    // Validate and format date
                    $dateObj = DateTime::createFromFormat('Y-m-d', $dob);
                    if (!$dateObj) {
                        $dateObj = DateTime::createFromFormat('d/m/Y', $dob);
                        if ($dateObj) {
                            $dob = $dateObj->format('Y-m-d');
                        } else {
                            $errorCount++;
                            continue;
                        }
                    } else {
                        $dob = $dateObj->format('Y-m-d');
                    }
                    
                    try {
                        // Check if student already exists
                        $checkStmt = $pdo->prepare("SELECT id FROM students WHERE enrollment_number = ?");
                        $checkStmt->execute([$enrollment]);
                        
                        if ($checkStmt->fetch()) {
                            // Update existing student
                            $updateStmt = $pdo->prepare("UPDATE students SET name = ?, dob = ? WHERE enrollment_number = ?");
                            $updateStmt->execute([$name, $dob, $enrollment]);
                        } else {
                            // Insert new student
                            $insertStmt = $pdo->prepare("INSERT INTO students (enrollment_number, name, dob) VALUES (?, ?, ?)");
                            $insertStmt->execute([$enrollment, $name, $dob]);
                        }
                        
                        $successCount++;
                    } catch (PDOException $e) {
                        $errorCount++;
                    }
                } else {
                    $errorCount++;
                }
            }
            
            fclose($handle);
            
            $message = "Upload completed: $successCount students processed successfully, $errorCount errors.";
        } else {
            $message = "Please upload a valid CSV file.";
        }
    } else {
        $message = "Error uploading file.";
    }
}

// Get all students
$students = $pdo->query("SELECT * FROM students ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Students - Admin Panel</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <div class="admin-container">
        <div class="admin-header">
            <h1>Upload Students</h1>
            <a href="dashboard.php" style="color: white;">Back to Dashboard</a>
        </div>
        
        <div class="admin-nav">
            <ul>
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="upload_students.php" class="active">Upload Students</a></li>
                <li><a href="upload_questions.php">Upload Questions</a></li>
                <li><a href="export_reports.php">Export Reports</a></li>
            </ul>
        </div>
        
        <div class="admin-content">
            <h2>Upload Student Data</h2>
            
            <?php if (!empty($message)): ?>
            <div class="message"><?php echo $message; ?></div>
            <?php endif; ?>
            
            <div class="upload-form">
                <form method="POST" enctype="multipart/form-data">
                    <p><strong>Format:</strong> Upload CSV file with columns: Enrollment Number, Name, Date of Birth (YYYY-MM-DD)</p>
                    <input type="file" name="student_file" accept=".csv" required>
                    <button type="submit" class="btn">Upload Students</button>
                </form>
            </div>
            
            <h2>Student List</h2>
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Enrollment Number</th>
                            <th>Name</th>
                            <th>Date of Birth</th>
                            <th>Exam Attempted</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($students as $student): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($student['enrollment_number']); ?></td>
                            <td><?php echo htmlspecialchars($student['name']); ?></td>
                            <td><?php echo htmlspecialchars($student['dob']); ?></td>
                            <td><?php echo $student['exam_attempted'] ? 'Yes' : 'No'; ?></td>
                            <td>
                                <form method="POST" action="delete_student.php" style="display: inline;">
                                    <input type="hidden" name="student_id" value="<?php echo $student['id']; ?>">
                                    <button type="submit" class="btn-danger" onclick="return confirm('Are you sure you want to delete this student?')">Delete</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <?php if (empty($students)): ?>
            <p>No students found.</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
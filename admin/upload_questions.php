<?php
require_once '../includes/auth.php';
require_once '../includes/db_connect.php';
redirectIfNotLoggedIn('admin');

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_FILES['question_file'])) {
        // File upload handling
        $file = $_FILES['question_file'];
        
        if ($file['error'] === UPLOAD_ERR_OK) {
            $fileType = pathinfo($file['name'], PATHINFO_EXTENSION);
            
            if ($fileType === 'csv') {
                $handle = fopen($file['tmp_name'], 'r');
                
                // Skip header row
                fgetcsv($handle);
                
                $successCount = 0;
                $errorCount = 0;
                
                while (($data = fgetcsv($handle)) !== FALSE) {
                    if (count($data) >= 6) {
                        $questionText = trim($data[0]);
                        $optionA = trim($data[1]);
                        $optionB = trim($data[2]);
                        $optionC = trim($data[3]);
                        $optionD = trim($data[4]);
                        $correctOption = strtoupper(trim($data[5]));
                        
                        // Validate correct option
                        if (!in_array($correctOption, ['A', 'B', 'C', 'D'])) {
                            $errorCount++;
                            continue;
                        }
                        
                        try {
                            // Check if question already exists
                            $checkStmt = $pdo->prepare("SELECT id FROM questions WHERE question_text = ?");
                            $checkStmt->execute([$questionText]);
                            
                            if ($checkStmt->fetch()) {
                                // Update existing question
                                $updateStmt = $pdo->prepare("UPDATE questions SET option_a = ?, option_b = ?, option_c = ?, option_d = ?, correct_option = ? WHERE question_text = ?");
                                $updateStmt->execute([$optionA, $optionB, $optionC, $optionD, $correctOption, $questionText]);
                            } else {
                                // Insert new question
                                $insertStmt = $pdo->prepare("INSERT INTO questions (question_text, option_a, option_b, option_c, option_d, correct_option) VALUES (?, ?, ?, ?, ?, ?)");
                                $insertStmt->execute([$questionText, $optionA, $optionB, $optionC, $optionD, $correctOption]);
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
                
                $message = "Upload completed: $successCount questions processed successfully, $errorCount errors.";
            } else {
                $message = "Please upload a valid CSV file.";
            }
        } else {
            $message = "Error uploading file.";
        }
    } elseif (isset($_POST['add_question'])) {
        // Manual question addition
        $questionText = $_POST['question_text'];
        $optionA = $_POST['option_a'];
        $optionB = $_POST['option_b'];
        $optionC = $_POST['option_c'];
        $optionD = $_POST['option_d'];
        $correctOption = $_POST['correct_option'];
        
        try {
            $stmt = $pdo->prepare("INSERT INTO questions (question_text, option_a, option_b, option_c, option_d, correct_option) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$questionText, $optionA, $optionB, $optionC, $optionD, $correctOption]);
            
            $message = "Question added successfully.";
        } catch (PDOException $e) {
            $message = "Error adding question: " . $e->getMessage();
        }
    }
}

// Get all questions
$questions = $pdo->query("SELECT * FROM questions ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Questions - Admin Panel</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <div class="admin-container">
        <div class="admin-header">
            <h1>Upload Questions</h1>
            <a href="dashboard.php" style="color: white;">Back to Dashboard</a>
        </div>
        
        <div class="admin-nav">
            <ul>
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="upload_students.php">Upload Students</a></li>
                <li><a href="upload_questions.php" class="active">Upload Questions</a></li>
                <li><a href="export_reports.php">Export Reports</a></li>
            </ul>
        </div>
        
        <div class="admin-content">
            <h2>Upload Questions</h2>
            
            <?php if (!empty($message)): ?>
            <div class="message"><?php echo $message; ?></div>
            <?php endif; ?>
            
            <div class="upload-form">
                <h3>Upload via CSV</h3>
                <p><strong>Format:</strong> Upload CSV file with columns: Question, Option A, Option B, Option C, Option D, Correct Answer (A/B/C/D)</p>
                <form method="POST" enctype="multipart/form-data">
                    <input type="file" name="question_file" accept=".csv" required>
                    <button type="submit" class="btn">Upload Questions</button>
                </form>
            </div>
            
            <div class="manual-form">
                <h3>Add Question Manually</h3>
                <form method="POST">
                    <div class="form-group">
                        <label for="question_text">Question</label>
                        <textarea id="question_text" name="question_text" rows="3" required></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="option_a">Option A</label>
                        <input type="text" id="option_a" name="option_a" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="option_b">Option B</label>
                        <input type="text" id="option_b" name="option_b" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="option_c">Option C</label>
                        <input type="text" id="option_c" name="option_c" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="option_d">Option D</label>
                        <input type="text" id="option_d" name="option_d" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="correct_option">Correct Option</label>
                        <select id="correct_option" name="correct_option" required>
                            <option value="">Select Correct Option</option>
                            <option value="A">A</option>
                            <option value="B">B</option>
                            <option value="C">C</option>
                            <option value="D">D</option>
                        </select>
                    </div>
                    
                    <button type="submit" name="add_question" class="btn">Add Question</button>
                </form>
            </div>
            
            <h2>Question Bank</h2>
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Question</th>
                            <th>Option A</th>
                            <th>Option B</th>
                            <th>Option C</th>
                            <th>Option D</th>
                            <th>Correct Answer</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($questions as $question): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($question['question_text']); ?></td>
                            <td><?php echo htmlspecialchars($question['option_a']); ?></td>
                            <td><?php echo htmlspecialchars($question['option_b']); ?></td>
                            <td><?php echo htmlspecialchars($question['option_c']); ?></td>
                            <td><?php echo htmlspecialchars($question['option_d']); ?></td>
                            <td><?php echo htmlspecialchars($question['correct_option']); ?></td>
                            <td><?php echo $question['is_active'] ? 'Active' : 'Inactive'; ?></td>
                            <td>
                                <form method="POST" action="delete_question.php" style="display: inline;">
                                    <input type="hidden" name="question_id" value="<?php echo $question['id']; ?>">
                                    <button type="submit" class="btn-danger" onclick="return confirm('Are you sure you want to delete this question?')">Delete</button>
                                </form>
                                
                                <form method="POST" action="toggle_question.php" style="display: inline;">
                                    <input type="hidden" name="question_id" value="<?php echo $question['id']; ?>">
                                    <input type="hidden" name="current_status" value="<?php echo $question['is_active'] ? 1 : 0; ?>">
                                    <button type="submit" class="btn-warning">
                                        <?php echo $question['is_active'] ? 'Deactivate' : 'Activate'; ?>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <?php if (empty($questions)): ?>
            <p>No questions found.</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
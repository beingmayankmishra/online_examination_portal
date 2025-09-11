<?php
require_once '../includes/auth.php';
require_once '../includes/db_connect.php';
redirectIfNotLoggedIn('admin');

// Include TCPDF library
require_once '../tcpdf/tcpdf.php';

// Get all exam results
$results = $pdo->query("
    SELECT s.enrollment_number, s.name, s.dob, 
           er.total_questions, er.attempted, er.correct_answers, 
           er.wrong_answers, er.marks, er.submitted_at
    FROM exam_results er
    JOIN students s ON er.student_id = s.id
    ORDER BY er.submitted_at DESC
")->fetchAll(PDO::FETCH_ASSOC);

// Handle export requests
if (isset($_GET['export'])) {
    $format = $_GET['format'] ?? 'csv';
    
    if ($format === 'csv') {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=exam_results_' . date('Y-m-d') . '.csv');
        
        $output = fopen('php://output', 'w');
        
        // Add CSV header
        fputcsv($output, ['Enrollment Number', 'Name', 'DOB', 'Total Questions', 'Attempted', 'Unattempted', 'Correct', 'Wrong', 'Marks', 'Submitted At']);
        
        // Add data rows
        foreach ($results as $row) {
            $unattempted = $row['total_questions'] - $row['attempted'];
            fputcsv($output, [
                $row['enrollment_number'],
                $row['name'],
                $row['dob'],
                $row['total_questions'],
                $row['attempted'],
                $unattempted,
                $row['correct_answers'],
                $row['wrong_answers'],
                $row['marks'],
                $row['submitted_at']
            ]);
        }
        
        fclose($output);
        exit();
    } elseif ($format === 'pdf') {
        // Create new PDF document
        $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        
        // Set document information
        $pdf->SetCreator('Mind Power University');
        $pdf->SetAuthor('Examination System');
        $pdf->SetTitle('Exam Results Report');
        $pdf->SetSubject('Exam Results');
        
        // Set default header data
        $pdf->SetHeaderData('', 0, 'Mind Power University', 'Exam Results Report');
        
        // Set header and footer fonts
        $pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
        $pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));
        
        // Set default monospaced font
        $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
        
        // Set margins
        $pdf->SetMargins(15, 25, 15);
        $pdf->SetHeaderMargin(10);
        $pdf->SetFooterMargin(10);
        
        // Set auto page breaks
        $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
        
        // Set image scale factor
        $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
        
        // Add a page
        $pdf->AddPage();
        
        // Set font
        $pdf->SetFont('helvetica', 'B', 16);
        $pdf->Cell(0, 10, 'Mind Power University - Exam Results', 0, 1, 'C');
        $pdf->SetFont('helvetica', '', 12);
        $pdf->Cell(0, 10, 'Generated on: ' . date('Y-m-d H:i:s'), 0, 1, 'C');
        $pdf->Ln(10);
        
        // Create table header
        $html = '<table border="1" cellpadding="5" style="border-collapse: collapse; width: 100%;">';
        $html .= '<tr style="background-color: #f2f2f2; font-weight: bold;">';
        $html .= '<th width="15%">Enrollment No.</th>';
        $html .= '<th width="20%">Name</th>';
        $html .= '<th width="10%">Total Qs</th>';
        $html .= '<th width="10%">Attempted</th>';
        $html .= '<th width="10%">Correct</th>';
        $html .= '<th width="10%">Wrong</th>';
        $html .= '<th width="10%">Marks</th>';
        $html .= '<th width="15%">Submitted At</th>';
        $html .= '</tr>';
        
        // Add table rows
        foreach ($results as $row) {
            $unattempted = $row['total_questions'] - $row['attempted'];
            $html .= '<tr>';
            $html .= '<td>' . htmlspecialchars($row['enrollment_number']) . '</td>';
            $html .= '<td>' . htmlspecialchars($row['name']) . '</td>';
            $html .= '<td>' . $row['total_questions'] . '</td>';
            $html .= '<td>' . $row['attempted'] . '</td>';
            $html .= '<td>' . $row['correct_answers'] . '</td>';
            $html .= '<td>' . $row['wrong_answers'] . '</td>';
            $html .= '<td>' . $row['marks'] . '</td>';
            $html .= '<td>' . $row['submitted_at'] . '</td>';
            $html .= '</tr>';
        }
        
        $html .= '</table>';
        
        // Output HTML content
        $pdf->writeHTML($html, true, false, true, false, '');
        
        // Close and output PDF
        $pdf->Output('exam_results_' . date('Y-m-d') . '.pdf', 'D');
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Export Reports - Admin Panel</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <div class="admin-container">
        <div class="admin-header">
            <h1>Export Reports</h1>
            <a href="dashboard.php" style="color: white;">Back to Dashboard</a>
        </div>
        
        <div class="admin-nav">
            <ul>
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="upload_students.php">Upload Students</a></li>
                <li><a href="upload_questions.php">Upload Questions</a></li>
                <li><a href="export_reports.php" class="active">Export Reports</a></li>
            </ul>
        </div>
        
        <div class="admin-content">
            <h2>Exam Results</h2>
            
            <div class="export-options">
                <h3>Export Options</h3>
                <a href="?export=true&format=csv" class="export-btn">Export as CSV</a>
                <a href="?export=true&format=pdf" class="export-btn">Export as PDF</a>
            </div>
            
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Enrollment Number</th>
                            <th>Name</th>
                            <th>DOB</th>
                            <th>Total Questions</th>
                            <th>Attempted</th>
                            <th>Unattempted</th>
                            <th>Correct</th>
                            <th>Wrong</th>
                            <th>Marks</th>
                            <th>Submitted At</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($results as $result): 
                            $unattempted = $result['total_questions'] - $result['attempted'];
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($result['enrollment_number']); ?></td>
                            <td><?php echo htmlspecialchars($result['name']); ?></td>
                            <td><?php echo htmlspecialchars($result['dob']); ?></td>
                            <td><?php echo $result['total_questions']; ?></td>
                            <td><?php echo $result['attempted']; ?></td>
                            <td><?php echo $unattempted; ?></td>
                            <td><?php echo $result['correct_answers']; ?></td>
                            <td><?php echo $result['wrong_answers']; ?></td>
                            <td><?php echo $result['marks']; ?></td>
                            <td><?php echo $result['submitted_at']; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <?php if (empty($results)): ?>
            <p>No exam results found.</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
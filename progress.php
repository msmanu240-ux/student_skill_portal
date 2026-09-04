<?php
session_start();
require_once 'config/database.php';

// Authentication Check
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'student') {
    header("Location: login.php");
    exit;
}

$student_id = $_SESSION['user_id'];
$student_name = $_SESSION['user_name'];

try {
    // Get enrollment details, attempts and certificate status
    $stmt = $pdo->prepare("
        SELECT e.*, c.course_name, c.id as course_id,
               (SELECT COUNT(*) FROM quiz_attempts qa WHERE qa.student_id = e.student_id AND qa.course_id = e.course_id) as attempts_count,
               (SELECT MAX(score) FROM quiz_attempts qa WHERE qa.student_id = e.student_id AND qa.course_id = e.course_id) as best_score,
               (SELECT result FROM quiz_attempts qa WHERE qa.student_id = e.student_id AND qa.course_id = e.course_id ORDER BY attempted_at DESC LIMIT 1) as latest_result,
               cert.certificate_id
        FROM enrollments e
        JOIN courses c ON e.course_id = c.id
        LEFT JOIN certificates cert ON e.student_id = cert.student_id AND e.course_id = cert.course_id
        WHERE e.student_id = ?
        ORDER BY e.enrolled_at DESC
    ");
    $stmt->execute([$student_id]);
    $progress_records = $stmt->fetchAll();

} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Learning Progress</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

    <div class="wrapper">
        <!-- Sidebar -->
        <nav id="sidebar">
            <div class="sidebar-header">
                <h3><i class="fa-solid fa-graduation-cap me-2"></i>Skills Portal</h3>
            </div>

            <ul class="list-unstyled components">
                <li>
                    <a href="dashboard.php"><i class="fa-solid fa-gauge me-2"></i>Dashboard</a>
                </li>
                <li>
                    <a href="skills.php"><i class="fa-solid fa-lightbulb me-2"></i>Browse Skills</a>
                </li>
                <li>
                    <a href="courses.php"><i class="fa-solid fa-book-open me-2"></i>All Courses</a>
                </li>
                <li>
                    <a href="student/my-courses.php"><i class="fa-solid fa-graduation-cap me-2"></i>My Enrollments</a>
                </li>
                <li class="active">
                    <a href="progress.php"><i class="fa-solid fa-chart-line me-2"></i>My Progress</a>
                </li>
                <li>
                    <a href="student/certificates.php"><i class="fa-solid fa-award me-2"></i>My Certificates</a>
                </li>
                <li>
                    <a href="logout.php"><i class="fa-solid fa-right-from-bracket me-2"></i>Logout</a>
                </li>
            </ul>
        </nav>

        <!-- Page Content -->
        <div id="content">
            <!-- Navbar -->
            <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm rounded mb-4">
                <div class="container-fluid">
                    <span class="navbar-text fs-5 fw-bold text-dark">
                        Learning & Skills Progress Tracker
                    </span>
                    <div class="ms-auto">
                        <span class="badge bg-primary px-3 py-2"><i class="fa-solid fa-user me-1"></i> Student Role</span>
                    </div>
                </div>
            </nav>

            <div class="container-fluid">
                <div class="card border-0 shadow-sm p-4">
                    <h4 class="fw-bold mb-4 text-dark"><i class="fa-solid fa-chart-line me-2 text-info"></i>Detailed Enrollment Report</h4>
                    
                    <?php if (count($progress_records) === 0): ?>
                        <div class="text-center py-5">
                            <p class="text-muted mb-3">No enrollments tracked. Join a course to view details.</p>
                            <a href="courses.php" class="btn btn-primary"><i class="fa-solid fa-magnifying-glass me-1"></i> Find Courses</a>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Course Name</th>
                                        <th>Enrollment Status</th>
                                        <th>Video Status</th>
                                        <th>Quiz Score</th>
                                        <th>Attempts</th>
                                        <th>Pass/Fail Status</th>
                                        <th>Certificate Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($progress_records as $pr): ?>
                                        <?php 
                                            // Determine Video Status
                                            $video_status = 'Not Started';
                                            if ($pr['progress'] >= 50) {
                                                $video_status = 'Completed';
                                            }
                                        ?>
                                        <tr>
                                            <td><strong><?php echo htmlspecialchars($pr['course_name']); ?></strong></td>
                                            <td>
                                                <?php if ($pr['status'] === 'Completed'): ?>
                                                    <span class="badge bg-success">Completed</span>
                                                <?php elseif ($pr['status'] === 'In Progress'): ?>
                                                    <span class="badge bg-warning text-dark">In Progress</span>
                                                <?php else: ?>
                                                    <span class="badge bg-info text-dark">Enrolled</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($video_status === 'Completed'): ?>
                                                    <span class="text-success"><i class="fa-solid fa-circle-check me-1"></i> Completed</span>
                                                <?php else: ?>
                                                    <span class="text-muted"><i class="fa-regular fa-circle me-1"></i> Not Started</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php 
                                                    if ($pr['best_score'] !== NULL && $pr['best_score'] >= 0) {
                                                        echo $pr['best_score'] . '/10';
                                                    } else {
                                                        echo 'N/A';
                                                    }
                                                ?>
                                            </td>
                                            <td>
                                                <?php echo $pr['attempts_count']; ?>
                                            </td>
                                            <td>
                                                <?php if ($pr['latest_result'] === 'Pass' || $pr['status'] === 'Completed'): ?>
                                                    <span class="badge bg-success">Passed</span>
                                                <?php elseif ($pr['latest_result'] === 'Fail'): ?>
                                                    <span class="badge bg-danger">Failed</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">No Attempt</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if (!empty($pr['certificate_id'])): ?>
                                                    <span class="badge bg-success"><i class="fa-solid fa-award me-1"></i> Available</span>
                                                <?php else: ?>
                                                    <span class="badge bg-light text-dark border">Locked</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <a href="learning.php?course_id=<?php echo $pr['course_id']; ?>" class="btn btn-sm btn-outline-primary mb-1">
                                                    <i class="fa-solid fa-graduation-cap"></i> Study
                                                </a>
                                                <?php if (!empty($pr['certificate_id'])): ?>
                                                    <a href="certificate.php?course_id=<?php echo $pr['course_id']; ?>" class="btn btn-sm btn-success mb-1">
                                                        <i class="fa-solid fa-award"></i> Certificate
                                                    </a>
                                                <?php else: ?>
                                                    <a href="quiz.php?course_id=<?php echo $pr['course_id']; ?>" class="btn btn-sm btn-warning mb-1">
                                                        <i class="fa-solid fa-square-poll-horizontal"></i> Quiz
                                                    </a>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

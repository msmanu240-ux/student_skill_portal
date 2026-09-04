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
$course_id = isset($_POST['course_id']) ? intval($_POST['course_id']) : 0;
$submitted_answers = $_POST['answers'] ?? [];

if ($course_id <= 0 || empty($submitted_answers)) {
    header("Location: courses.php");
    exit;
}

try {
    // 1. Fetch Course details
    $stmt = $pdo->prepare("SELECT course_name FROM courses WHERE id = ?");
    $stmt->execute([$course_id]);
    $course_name = $stmt->fetch()['course_name'] ?? 'Course';

    // 2. Fetch all correct answers for the course
    $stmt = $pdo->prepare("SELECT id, correct_answer FROM quiz_questions WHERE course_id = ?");
    $stmt->execute([$course_id]);
    $questions = $stmt->fetchAll();

    $total_questions = 10; // Forced total
    $correct_count = 0;

    foreach ($questions as $q) {
        $q_id = $q['id'];
        $correct_ans = $q['correct_answer'];
        $student_ans = $submitted_answers[$q_id] ?? '';

        if (strtoupper(trim($student_ans)) === strtoupper(trim($correct_ans))) {
            $correct_count++;
        }
    }

    $wrong_count = $total_questions - $correct_count;
    $percentage = ($correct_count / $total_questions) * 100;
    $result = ($correct_count >= 7) ? 'Pass' : 'Fail';

    // 3. Determine Attempt Number
    $stmt = $pdo->prepare("SELECT COALESCE(MAX(attempt_number), 0) as last_attempt FROM quiz_attempts WHERE student_id = ? AND course_id = ?");
    $stmt->execute([$student_id, $course_id]);
    $attempt_number = $stmt->fetch()['last_attempt'] + 1;

    // 4. Save attempt in MySQL
    $stmt = $pdo->prepare("INSERT INTO quiz_attempts (student_id, course_id, score, total_questions, percentage, result, attempt_number) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$student_id, $course_id, $correct_count, $total_questions, $percentage, $result, $attempt_number]);

    $cert_generated = false;
    $certificate_id = '';

    if ($result === 'Pass') {
        // 5. Update Enrollment Status
        $stmt = $pdo->prepare("UPDATE enrollments SET status = 'Completed', progress = 100 WHERE student_id = ? AND course_id = ?");
        $stmt->execute([$student_id, $course_id]);

        // 6. Generate Certificate (if not already issued for this student & course)
        $stmt = $pdo->prepare("SELECT certificate_id FROM certificates WHERE student_id = ? AND course_id = ?");
        $stmt->execute([$student_id, $course_id]);
        $existing_cert = $stmt->fetch();

        if (!$existing_cert) {
            $current_year = date('Y');
            // Get next serial number
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM certificates");
            $next_num = $stmt->fetch()['count'] + 1;
            $certificate_id = sprintf("CERT-%s-%05d", $current_year, $next_num);

            $stmt = $pdo->prepare("INSERT INTO certificates (certificate_id, student_id, course_id, score) VALUES (?, ?, ?, ?)");
            $stmt->execute([$certificate_id, $student_id, $course_id, $correct_count]);
            $cert_generated = true;
        } else {
            $certificate_id = $existing_cert['certificate_id'];
            $cert_generated = true;
        }
    }

} catch (PDOException $e) {
    die("Database transaction failed: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quiz Results</title>
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
                <li class="active">
                    <a href="courses.php"><i class="fa-solid fa-book-open me-2"></i>All Courses</a>
                </li>
                <li>
                    <a href="student/my-courses.php"><i class="fa-solid fa-graduation-cap me-2"></i>My Enrollments</a>
                </li>
                <li>
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
            <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm rounded mb-4">
                <div class="container-fluid">
                    <span class="navbar-text fs-5 fw-bold text-dark">
                        Quiz Results Summary
                    </span>
                </div>
            </nav>

            <div class="container-fluid">
                <div class="row">
                    <div class="col-lg-8 mx-auto">
                        <div class="card border-0 shadow-sm p-4 text-center mb-4">
                            <?php if ($result === 'Pass'): ?>
                                <div class="text-success mb-3">
                                    <i class="fa-solid fa-circle-check fa-4x"></i>
                                </div>
                                <h1 class="fw-bold text-success">🎉 Congratulations!</h1>
                                <h3 class="fw-semibold text-dark">You passed the quiz!</h3>
                                <p class="lead text-muted">Excellent work! You successfully demonstrated your skills in this subject.</p>
                            <?php else: ?>
                                <div class="text-danger mb-3">
                                    <i class="fa-solid fa-circle-xmark fa-4x"></i>
                                </div>
                                <h1 class="fw-bold text-danger">❌ Try Again</h1>
                                <p class="lead text-muted">You scored <?php echo $correct_count; ?>/10. You need at least 7/10 to pass.</p>
                            <?php endif; ?>
                        </div>

                        <!-- Details card -->
                        <div class="card border-0 shadow-sm p-4 mb-4">
                            <h5 class="fw-bold text-dark border-bottom pb-2 mb-3">Quiz Performance Card</h5>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <p class="mb-1 text-muted">Student Name</p>
                                    <h6 class="fw-bold text-dark"><?php echo htmlspecialchars($student_name); ?></h6>
                                </div>
                                <div class="col-md-6">
                                    <p class="mb-1 text-muted">Course Name</p>
                                    <h6 class="fw-bold text-dark"><?php echo htmlspecialchars($course_name); ?></h6>
                                </div>
                                <div class="col-md-4 border-end">
                                    <p class="mb-1 text-muted">Total Questions</p>
                                    <h6 class="fw-bold text-dark"><?php echo $total_questions; ?></h6>
                                </div>
                                <div class="col-md-4 border-end text-center">
                                    <p class="mb-1 text-muted">Correct / Wrong</p>
                                    <h6 class="fw-bold"><span class="text-success"><?php echo $correct_count; ?></span> / <span class="text-danger"><?php echo $wrong_count; ?></span></h6>
                                </div>
                                <div class="col-md-4 text-end">
                                    <p class="mb-1 text-muted">Percentage / Result</p>
                                    <h6 class="fw-bold <?php echo $result === 'Pass' ? 'text-success' : 'text-danger'; ?>"><?php echo $percentage; ?>% - <?php echo $result; ?></h6>
                                </div>
                            </div>
                        </div>

                        <?php if ($result === 'Pass' && $cert_generated): ?>
                            <!-- Certificate Card -->
                            <div class="card border-0 shadow-sm p-4 text-center bg-light mb-4">
                                <div class="text-warning mb-2">
                                    <i class="fa-solid fa-trophy fa-3x"></i>
                                </div>
                                <h4 class="fw-bold">🏆 Certificate of Completion</h4>
                                <p class="text-muted small">A unique certificate has been generated for you with ID: <strong><?php echo $certificate_id; ?></strong></p>
                                <div class="mt-3">
                                    <a href="certificate.php?course_id=<?php echo $course_id; ?>" class="btn btn-primary px-4 fw-bold">
                                        <i class="fa-solid fa-award me-1"></i> View Certificate
                                    </a>
                                </div>
                            </div>
                            <div class="text-center">
                                <a href="feedback.php?course_id=<?php echo $course_id; ?>" class="btn btn-outline-success px-4 py-2 me-2">
                                    <i class="fa-solid fa-comment-dots me-1"></i> Submit Feedback & Rating
                                </a>
                                <a href="dashboard.php" class="btn btn-outline-secondary px-4 py-2">
                                    <i class="fa-solid fa-gauge me-1"></i> Back to Dashboard
                                </a>
                            </div>
                        <?php else: ?>
                            <!-- Try Again Actions -->
                            <div class="text-center">
                                <a href="quiz.php?course_id=<?php echo $course_id; ?>" class="btn btn-warning text-dark px-5 py-2 fw-bold me-2">
                                    <i class="fa-solid fa-rotate-right me-1"></i> Try Quiz Again
                                </a>
                                <a href="learning.php?course_id=<?php echo $course_id; ?>" class="btn btn-outline-secondary px-4 py-2">
                                    <i class="fa-solid fa-video me-1"></i> Review Learning Video
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

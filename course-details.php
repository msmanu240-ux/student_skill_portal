<?php
session_start();
require_once 'config/database.php';

// Authentication Check
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'student') {
    header("Location: login.php");
    exit;
}

$student_id = $_SESSION['user_id'];
$course_id = isset($_GET['course_id']) ? intval($_GET['course_id']) : 0;

if ($course_id <= 0) {
    header("Location: courses.php");
    exit;
}

// Enroll Trigger
if (isset($_POST['action']) && $_POST['action'] === 'enroll') {
    try {
        $stmt = $pdo->prepare("SELECT id FROM enrollments WHERE student_id = ? AND course_id = ?");
        $stmt->execute([$student_id, $course_id]);
        if (!$stmt->fetch()) {
            $stmt = $pdo->prepare("INSERT INTO enrollments (student_id, course_id, status, progress) VALUES (?, ?, 'Enrolled', 0)");
            $stmt->execute([$student_id, $course_id]);
        }
    } catch (PDOException $e) {
        $error = "Enrollment failed: " . $e->getMessage();
    }
}

try {
    // Fetch course details, skill details, and enrollment status
    $stmt = $pdo->prepare("
        SELECT c.*, s.skill_name, s.level as skill_level, s.description as skill_description,
               e.status as enrollment_status, e.progress
        FROM courses c
        LEFT JOIN skills s ON c.skill_id = s.id
        LEFT JOIN enrollments e ON c.id = e.course_id AND e.student_id = ?
        WHERE c.id = ?
    ");
    $stmt->execute([$student_id, $course_id]);
    $course = $stmt->fetch();

    if (!$course) {
        header("Location: courses.php");
        exit;
    }

    // Get feedback metrics
    $stmt = $pdo->prepare("SELECT COALESCE(AVG(rating), 0) as avg_rating, COUNT(*) as cnt FROM feedback WHERE course_id = ?");
    $stmt->execute([$course_id]);
    $feedback = $stmt->fetch();
    $avg_rating = round($feedback['avg_rating'], 1);
    $rating_count = $feedback['cnt'];

    // Get list of feedbacks
    $stmt = $pdo->prepare("
        SELECT f.*, u.name as student_name 
        FROM feedback f 
        JOIN users u ON f.student_id = u.id 
        WHERE f.course_id = ? 
        ORDER BY f.created_at DESC 
        LIMIT 5
    ");
    $stmt->execute([$course_id]);
    $feedbacks_list = $stmt->fetchAll();

} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($course['course_name']); ?> - Details</title>
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
            <!-- Navbar -->
            <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm rounded mb-4">
                <div class="container-fluid">
                    <span class="navbar-text fs-5 fw-bold text-dark">
                        Course Detail Overview
                    </span>
                    <div class="ms-auto">
                        <a href="courses.php" class="btn btn-outline-secondary btn-sm"><i class="fa-solid fa-arrow-left me-1"></i> Back to Courses</a>
                    </div>
                </div>
            </nav>

            <div class="container-fluid">
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>

                <div class="row g-4">
                    <!-- Left Sidebar / Info Panel -->
                    <div class="col-lg-8">
                        <div class="card border-0 shadow-sm p-4 mb-4">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <span class="badge bg-secondary fs-6"><?php echo htmlspecialchars($course['skill_name']); ?></span>
                                <span class="badge bg-light text-dark border"><?php echo htmlspecialchars($course['skill_level']); ?></span>
                            </div>
                            <h2 class="fw-bold text-primary mb-3"><?php echo htmlspecialchars($course['course_name']); ?></h2>
                            
                            <!-- Average Stars -->
                            <div class="mb-3 d-flex align-items-center">
                                <div class="text-warning me-2 fs-5">
                                    <?php 
                                        for ($i = 1; $i <= 5; $i++) {
                                            if ($i <= $avg_rating) {
                                                echo '<i class="fa-solid fa-star"></i>';
                                            } elseif ($i - 0.5 <= $avg_rating) {
                                                echo '<i class="fa-solid fa-star-half-stroke"></i>';
                                            } else {
                                                echo '<i class="fa-regular fa-star"></i>';
                                            }
                                        }
                                    ?>
                                </div>
                                <span class="fw-bold text-dark fs-6"><?php echo $avg_rating; ?> / 5</span>
                                <span class="text-muted ms-2">(<?php echo $rating_count; ?> reviews)</span>
                            </div>

                            <h5 class="fw-bold text-dark mt-4">Description</h5>
                            <p class="text-muted fs-6" style="line-height: 1.6;">
                                <?php echo nl2br(htmlspecialchars($course['description'])); ?>
                            </p>

                            <h5 class="fw-bold text-dark mt-4">Skill Pathway Description</h5>
                            <p class="text-muted small">
                                <?php echo htmlspecialchars($course['skill_description']); ?>
                            </p>
                        </div>

                        <!-- Feedback and reviews section -->
                        <div class="card border-0 shadow-sm p-4">
                            <h5 class="fw-bold text-dark mb-3"><i class="fa-solid fa-comments me-2 text-info"></i>Student Reviews</h5>
                            <?php if (count($feedbacks_list) === 0): ?>
                                <p class="text-muted mb-0">No reviews yet for this course. Be the first to complete the quiz and leave your review!</p>
                            <?php else: ?>
                                <div class="list-group list-group-flush">
                                    <?php foreach ($feedbacks_list as $f): ?>
                                        <div class="list-group-item px-0 py-3">
                                            <div class="d-flex justify-content-between align-items-center mb-1">
                                                <h6 class="fw-bold mb-0"><?php echo htmlspecialchars($f['student_name']); ?></h6>
                                                <small class="text-muted"><?php echo date('Y-M-d', strtotime($f['created_at'])); ?></small>
                                            </div>
                                            <div class="text-warning mb-2 small">
                                                <?php 
                                                    for ($i = 1; $i <= 5; $i++) {
                                                        echo $i <= $f['rating'] ? '<i class="fa-solid fa-star"></i>' : '<i class="fa-regular fa-star"></i>';
                                                    }
                                                ?>
                                            </div>
                                            <p class="mb-0 text-muted small"><?php echo nl2br(htmlspecialchars($f['comments'])); ?></p>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Right Column: Enrollment Actions & Details -->
                    <div class="col-lg-4">
                        <div class="card border-0 shadow-sm p-4 mb-4">
                            <h5 class="fw-bold mb-4">Course Information</h5>
                            
                            <ul class="list-unstyled mb-4">
                                <li class="d-flex justify-content-between mb-3 border-bottom pb-2">
                                    <span class="text-muted"><i class="fa-solid fa-user-tie me-2"></i>Instructor</span>
                                    <span class="fw-semibold text-dark"><?php echo htmlspecialchars($course['instructor']); ?></span>
                                </li>
                                <li class="d-flex justify-content-between mb-3 border-bottom pb-2">
                                    <span class="text-muted"><i class="fa-solid fa-clock me-2"></i>Duration</span>
                                    <span class="fw-semibold text-dark"><?php echo htmlspecialchars($course['duration']); ?></span>
                                </li>
                                <li class="d-flex justify-content-between mb-3 border-bottom pb-2">
                                    <span class="text-muted"><i class="fa-solid fa-circle-play me-2"></i>Format</span>
                                    <span class="fw-semibold text-dark">YouTube Video</span>
                                </li>
                                <li class="d-flex justify-content-between mb-3">
                                    <span class="text-muted"><i class="fa-solid fa-gauge-high me-2"></i>Quiz Limit</span>
                                    <span class="fw-semibold text-dark">10 Questions</span>
                                </li>
                            </ul>

                            <?php if ($course['enrollment_status'] === NULL): ?>
                                <form method="POST" action="course-details.php?course_id=<?php echo $course_id; ?>">
                                    <input type="hidden" name="action" value="enroll">
                                    <button type="submit" class="btn btn-primary btn-lg w-100 fw-bold">
                                        <i class="fa-solid fa-graduation-cap me-2"></i> Enroll Now
                                    </button>
                                </form>
                            <?php else: ?>
                                <div class="bg-light p-3 rounded mb-3 text-center">
                                    <span class="text-muted small d-block">Status:</span>
                                    <strong class="text-primary"><?php echo $course['enrollment_status']; ?></strong>
                                    <span class="text-muted small d-block mt-2">Progress:</span>
                                    <div class="progress mt-1" style="height: 8px;">
                                        <div class="progress-bar bg-success" style="width: <?php echo $course['progress']; ?>%"></div>
                                    </div>
                                    <span class="small text-dark fw-bold"><?php echo $course['progress']; ?>% Done</span>
                                </div>

                                <a href="learning.php?course_id=<?php echo $course_id; ?>" class="btn btn-warning text-dark btn-lg w-100 fw-bold mb-2">
                                    <i class="fa-solid fa-circle-play me-2"></i> Go to Learning Video
                                </a>

                                <?php if ($course['enrollment_status'] === 'Completed'): ?>
                                    <a href="certificate.php?course_id=<?php echo $course_id; ?>" class="btn btn-success btn-lg w-100 fw-bold mb-2">
                                        <i class="fa-solid fa-award me-2"></i> View Certificate
                                    </a>
                                    <a href="feedback.php?course_id=<?php echo $course_id; ?>" class="btn btn-outline-secondary w-100 fw-semibold">
                                        <i class="fa-solid fa-comment-dots me-2"></i> Review Course
                                    </a>
                                <?php else: ?>
                                    <a href="quiz.php?course_id=<?php echo $course_id; ?>" class="btn btn-outline-primary btn-lg w-100 fw-bold">
                                        <i class="fa-solid fa-circle-question me-2"></i> Take Course Quiz
                                    </a>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

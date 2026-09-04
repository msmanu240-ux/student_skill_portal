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

try {
    // Check enrollment
    $stmt = $pdo->prepare("SELECT status, progress FROM enrollments WHERE student_id = ? AND course_id = ?");
    $stmt->execute([$student_id, $course_id]);
    $enrollment = $stmt->fetch();

    if (!$enrollment) {
        // Automatically enroll student if they hit learning directly? Better to redirect to details
        header("Location: course-details.php?course_id=" . $course_id);
        exit;
    }

    // Update status to 'In Progress' and progress to 50 if currently 'Enrolled'
    if ($enrollment['status'] === 'Enrolled') {
        $upd = $pdo->prepare("UPDATE enrollments SET status = 'In Progress', progress = 50 WHERE student_id = ? AND course_id = ?");
        $upd->execute([$student_id, $course_id]);
    }

    // Fetch course details
    $stmt = $pdo->prepare("SELECT * FROM courses WHERE id = ?");
    $stmt->execute([$course_id]);
    $course = $stmt->fetch();

    // Normalize/Extract Youtube Video ID
    $youtube_url = $course['youtube_url'] ?? '';
    $embed_url = '';
    
    // Parse Video ID from various YouTube URL formats
    if (preg_match('/embed\/([a-zA-Z0-9_-]+)/', $youtube_url, $matches)) {
        $embed_url = "https://www.youtube.com/embed/" . $matches[1];
    } elseif (preg_match('/watch\?v=([a-zA-Z0-9_-]+)/', $youtube_url, $matches)) {
        $embed_url = "https://www.youtube.com/embed/" . $matches[1];
    } elseif (preg_match('/youtu\.be\/([a-zA-Z0-9_-]+)/', $youtube_url, $matches)) {
        $embed_url = "https://www.youtube.com/embed/" . $matches[1];
    } else {
        // Fallback or leave empty
        $embed_url = htmlspecialchars($youtube_url);
    }

} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Learning: <?php echo htmlspecialchars($course['course_name']); ?></title>
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
                        <i class="fa-solid fa-circle-play text-danger me-2"></i> Course Lecture Video
                    </span>
                    <div class="ms-auto">
                        <a href="course-details.php?course_id=<?php echo $course_id; ?>" class="btn btn-outline-secondary btn-sm"><i class="fa-solid fa-arrow-left me-1"></i> Back to Details</a>
                    </div>
                </div>
            </nav>

            <div class="container-fluid">
                <div class="row g-4">
                    <div class="col-lg-9 mx-auto">
                        <!-- Video Title & Instructor -->
                        <div class="mb-4">
                            <h2 class="fw-bold text-dark mb-1"><?php echo htmlspecialchars($course['course_name']); ?></h2>
                            <p class="text-muted"><i class="fa-solid fa-chalkboard-user me-1"></i> Instructor: <?php echo htmlspecialchars($course['instructor']); ?> | <i class="fa-solid fa-clock me-1"></i> Duration: <?php echo htmlspecialchars($course['duration']); ?></p>
                        </div>

                        <!-- Responsive Iframe Wrapper -->
                        <div class="card border-0 shadow-sm overflow-hidden mb-4">
                            <div class="ratio ratio-16x9">
                                <?php if (!empty($embed_url)): ?>
                                    <iframe src="<?php echo $embed_url; ?>" title="YouTube video player" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen></iframe>
                                <?php else: ?>
                                    <div class="bg-dark text-white d-flex align-items-center justify-content-center">
                                        <div class="text-center">
                                            <i class="fa-solid fa-video-slash fa-3x mb-3 text-muted"></i>
                                            <p class="lead">No video URL available for this course.</p>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Course Description and Actions -->
                        <div class="card border-0 shadow-sm p-4 mb-4">
                            <h5 class="fw-bold mb-3">About This Lecture</h5>
                            <p class="text-muted" style="line-height: 1.6;">
                                <?php echo nl2br(htmlspecialchars($course['description'])); ?>
                            </p>

                            <hr class="my-4">

                            <!-- Call to Action -->
                            <div class="text-center">
                                <h4 class="fw-bold mb-3">Finished watching the lecture?</h4>
                                <p class="text-muted">Test your knowledge! Take the 10-question course quiz to earn your Certificate of Completion.</p>
                                <?php if (isset($enrollment['status']) && $enrollment['status'] === 'Completed'): ?>
                                    <div class="alert alert-success d-inline-block">
                                        <i class="fa-solid fa-circle-check me-2"></i> You have already passed this course quiz and completed this training.
                                    </div>
                                    <div>
                                        <a href="certificate.php?course_id=<?php echo $course_id; ?>" class="btn btn-success btn-lg px-5 fw-bold">
                                            <i class="fa-solid fa-award me-2"></i> View Certificate
                                        </a>
                                    </div>
                                <?php else: ?>
                                    <a href="quiz.php?course_id=<?php echo $course_id; ?>" class="btn btn-warning text-dark btn-lg px-5 fw-bold">
                                        <i class="fa-solid fa-square-poll-horizontal me-2"></i> Start Quiz
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

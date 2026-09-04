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
    // 1. Total Enrolled Courses
    $stmt = $pdo->prepare("SELECT COUNT(*) as cnt FROM enrollments WHERE student_id = ?");
    $stmt->execute([$student_id]);
    $total_enrolled = $stmt->fetch()['cnt'];

    // 2. Completed Courses
    $stmt = $pdo->prepare("SELECT COUNT(*) as cnt FROM enrollments WHERE student_id = ? AND status = 'Completed'");
    $stmt->execute([$student_id]);
    $completed_courses = $stmt->fetch()['cnt'];

    // 3. Courses in Progress
    $stmt = $pdo->prepare("SELECT COUNT(*) as cnt FROM enrollments WHERE student_id = ? AND status = 'In Progress'");
    $stmt->execute([$student_id]);
    $in_progress_courses = $stmt->fetch()['cnt'];

    // 4. Quiz Attempts
    $stmt = $pdo->prepare("SELECT COUNT(*) as cnt FROM quiz_attempts WHERE student_id = ?");
    $stmt->execute([$student_id]);
    $quiz_attempts = $stmt->fetch()['cnt'];

    // 5. Passed Quizzes
    $stmt = $pdo->prepare("SELECT COUNT(*) as cnt FROM quiz_attempts WHERE student_id = ? AND result = 'Pass'");
    $stmt->execute([$student_id]);
    $passed_quizzes = $stmt->fetch()['cnt'];

    // 6. Certificates Earned
    $stmt = $pdo->prepare("SELECT COUNT(*) as cnt FROM certificates WHERE student_id = ?");
    $stmt->execute([$student_id]);
    $certificates_earned = $stmt->fetch()['cnt'];

    // Fetch Enrolled Courses list with details
    $stmt = $pdo->prepare("
        SELECT e.id as enrollment_id, e.progress, e.status, c.id as course_id, c.course_name, c.instructor, c.duration, s.skill_name
        FROM enrollments e
        JOIN courses c ON e.course_id = c.id
        LEFT JOIN skills s ON c.skill_id = s.id
        WHERE e.student_id = ?
        ORDER BY e.enrolled_at DESC
    ");
    $stmt->execute([$student_id]);
    $my_courses = $stmt->fetchAll();

} catch (PDOException $e) {
    die("Error loading dashboard data: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard</title>
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
                <li class="active">
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
                        <?php 
                            $hour = date('H');
                            $greeting = ($hour < 12) ? "Good Morning" : (($hour < 17) ? "Good Afternoon" : "Good Evening");
                            echo $greeting . ", " . htmlspecialchars($student_name) . "!";
                        ?>
                    </span>
                    <div class="ms-auto">
                        <span class="badge bg-primary px-3 py-2"><i class="fa-solid fa-graduation-cap me-1"></i> Student Console</span>
                    </div>
                </div>
            </nav>

            <div class="container-fluid">
                <!-- Welcome Section with Modern Gradient -->
                <div class="p-4 rounded shadow-sm mb-4 text-white" style="background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%);">
                    <h2 class="fw-bold"><i class="fa-solid fa-rocket me-2"></i>Empower Your Skillset</h2>
                    <p class="mb-0">Watch video lectures, take assessments, earn certifications, and track your learning milestones below.</p>
                </div>

                <!-- Stats Overview Cards -->
                <div class="row g-3 mb-4">
                    <div class="col-md-3">
                        <div class="card border-0 shadow-sm p-3 bg-white text-dark h-100">
                            <div class="d-flex align-items-center">
                                <div class="p-3 bg-light-primary text-primary rounded me-3">
                                    <i class="fa-solid fa-book-bookmark fa-2x"></i>
                                </div>
                                <div>
                                    <h4 class="mb-0 fw-bold"><?php echo $total_enrolled; ?></h4>
                                    <small class="text-muted d-block">Enrolled</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card border-0 shadow-sm p-3 bg-white text-dark h-100">
                            <div class="d-flex align-items-center">
                                <div class="p-3 bg-light-success text-success rounded me-3">
                                    <i class="fa-solid fa-circle-check fa-2x"></i>
                                </div>
                                <div>
                                    <h4 class="mb-0 fw-bold"><?php echo $completed_courses; ?></h4>
                                    <small class="text-muted d-block">Completed</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card border-0 shadow-sm p-3 bg-white text-dark h-100">
                            <div class="d-flex align-items-center">
                                <div class="p-3 bg-light-warning text-warning rounded me-3">
                                    <i class="fa-solid fa-spinner fa-2x"></i>
                                </div>
                                <div>
                                    <h4 class="mb-0 fw-bold"><?php echo $in_progress_courses; ?></h4>
                                    <small class="text-muted d-block">In Progress</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card border-0 shadow-sm p-3 bg-white text-dark h-100">
                            <div class="d-flex align-items-center">
                                <div class="p-3 bg-light-danger text-danger rounded me-3">
                                    <i class="fa-solid fa-award fa-2x"></i>
                                </div>
                                <div>
                                    <h4 class="mb-0 fw-bold"><?php echo $certificates_earned; ?></h4>
                                    <small class="text-muted d-block">Certificates</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Unique Skill Badges Closet -->
                <?php
                    // Fetch completed courses to unlock badges
                    try {
                        $stmt = $pdo->prepare("SELECT course_id FROM enrollments WHERE student_id = ? AND status = 'Completed'");
                        $stmt->execute([$student_id]);
                        $completed_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);
                    } catch (PDOException $e) {
                        $completed_ids = [];
                    }

                    $badges = [
                        ['id' => 1, 'name' => 'Python Specialist', 'icon' => 'fa-brands fa-python', 'color' => '#f59e0b', 'bg' => '#fef3c7'],
                        ['id' => 2, 'name' => 'Web Architect', 'icon' => 'fa-solid fa-code', 'color' => '#3b82f6', 'bg' => '#dbeafe'],
                        ['id' => 3, 'name' => 'CSS Designer', 'icon' => 'fa-solid fa-compass-drafting', 'color' => '#10b981', 'bg' => '#d1fae5'],
                        ['id' => 4, 'name' => 'Data Analyst', 'icon' => 'fa-solid fa-chart-pie', 'color' => '#8b5cf6', 'bg' => '#f3e8ff'],
                        ['id' => 5, 'name' => 'AI Engineer', 'icon' => 'fa-solid fa-brain', 'color' => '#ec4899', 'bg' => '#fce7f3'],
                        ['id' => 6, 'name' => 'Growth Marketer', 'icon' => 'fa-solid fa-bullhorn', 'color' => '#f43f5e', 'bg' => '#ffe4e6'],
                        ['id' => 7, 'name' => 'Orator Master', 'icon' => 'fa-solid fa-comments', 'color' => '#06b6d4', 'bg' => '#cffafe'],
                        ['id' => 8, 'name' => 'Database Admin', 'icon' => 'fa-solid fa-database', 'color' => '#14b8a6', 'bg' => '#ccfbf1'],
                    ];
                ?>
                <div class="card border-0 shadow-sm p-4 mb-4">
                    <h5 class="fw-bold mb-3"><i class="fa-solid fa-ribbon me-2 text-warning"></i>My Skill Achievement Badges</h5>
                    <div class="row g-3">
                        <?php foreach ($badges as $badge): ?>
                            <?php 
                                $unlocked = in_array($badge['id'], $completed_ids); 
                                $opacity = $unlocked ? '1.0' : '0.25';
                                $filter = $unlocked ? 'none' : 'grayscale(100%)';
                            ?>
                            <div class="col-6 col-sm-4 col-md-3 col-lg-3 text-center">
                                <div class="p-3 border rounded h-100 bg-light transition-all" style="opacity: <?php echo $opacity; ?>; filter: <?php echo $filter; ?>; border-color: <?php echo $unlocked ? $badge['color'] : '#e2e8f0'; ?> !important;">
                                    <div class="d-inline-flex p-3 rounded-circle mb-2" style="background-color: <?php echo $badge['bg']; ?>; color: <?php echo $badge['color']; ?>;">
                                        <i class="<?php echo $badge['icon']; ?> fa-2x"></i>
                                    </div>
                                    <h6 class="fw-bold mb-1 small text-dark"><?php echo htmlspecialchars($badge['name']); ?></h6>
                                    <span class="badge <?php echo $unlocked ? 'bg-success' : 'bg-secondary'; ?> px-2 py-1" style="font-size: 9px;">
                                        <?php echo $unlocked ? 'Unlocked' : 'Locked'; ?>
                                    </span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- My Enrolled Courses Progress -->
                <div class="card border-0 shadow-sm p-4 mb-4">
                    <h5 class="fw-bold mb-4 text-dark"><i class="fa-solid fa-graduation-cap me-2 text-primary"></i>My Enrolled Courses & Study Desk</h5>
                    
                    <?php if (count($my_courses) === 0): ?>
                        <div class="text-center py-4">
                            <i class="fa-solid fa-folder-open fa-3x text-muted mb-2"></i>
                            <p class="text-muted">You are not enrolled in any courses yet.</p>
                            <a href="courses.php" class="btn btn-primary"><i class="fa-solid fa-magnifying-glass me-1"></i> Browse & Enroll in Courses</a>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Course Name</th>
                                        <th>Skill Pathway</th>
                                        <th>Details</th>
                                        <th>Status</th>
                                        <th style="min-width: 150px;">Learning Progress</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($my_courses as $mc): ?>
                                        <tr>
                                            <td><strong><?php echo htmlspecialchars($mc['course_name']); ?></strong></td>
                                            <td><span class="badge bg-secondary"><?php echo htmlspecialchars($mc['skill_name'] ?? 'General'); ?></span></td>
                                            <td>
                                                <small class="text-muted d-block">Instructor: <?php echo htmlspecialchars($mc['instructor']); ?></small>
                                                <small class="text-muted d-block">Duration: <?php echo htmlspecialchars($mc['duration']); ?></small>
                                            </td>
                                            <td>
                                                <?php if ($mc['status'] === 'Completed'): ?>
                                                    <span class="badge bg-success"><i class="fa-solid fa-circle-check me-1"></i> Completed</span>
                                                <?php elseif ($mc['status'] === 'In Progress'): ?>
                                                    <span class="badge bg-warning text-dark"><i class="fa-solid fa-spinner me-1 animate-spin"></i> In Progress</span>
                                                <?php else: ?>
                                                    <span class="badge bg-info"><i class="fa-solid fa-circle-play me-1"></i> Enrolled</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="progress" style="height: 10px;">
                                                    <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo $mc['progress']; ?>%" aria-valuenow="<?php echo $mc['progress']; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                                </div>
                                                <small class="text-muted mt-1 d-block font-monospace"><?php echo $mc['progress']; ?>% Done</small>
                                            </td>
                                            <td>
                                                <a href="learning.php?course_id=<?php echo $mc['course_id']; ?>" class="btn btn-sm btn-primary">
                                                    <i class="fa-solid fa-circle-play me-1"></i> Study
                                                </a>
                                                <?php if ($mc['status'] === 'Completed'): ?>
                                                    <a href="certificate.php?course_id=<?php echo $mc['course_id']; ?>" class="btn btn-sm btn-outline-success ms-1">
                                                        <i class="fa-solid fa-award me-1"></i> Certificate
                                                    </a>
                                                    <a href="feedback.php?course_id=<?php echo $mc['course_id']; ?>" class="btn btn-sm btn-outline-secondary ms-1">
                                                        <i class="fa-solid fa-comment-dots me-1"></i> Review
                                                    </a>
                                                <?php else: ?>
                                                    <a href="quiz.php?course_id=<?php echo $mc['course_id']; ?>" class="btn btn-sm btn-warning ms-1 text-dark fw-bold">
                                                        <i class="fa-solid fa-circle-question me-1"></i> Quiz
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

    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

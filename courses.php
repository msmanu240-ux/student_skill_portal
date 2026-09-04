<?php
session_start();
require_once 'config/database.php';

// Authentication Check
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'student') {
    header("Location: login.php");
    exit;
}

$student_id = $_SESSION['user_id'];
$skill_filter = isset($_GET['skill_id']) ? intval($_GET['skill_id']) : 0;

// Enroll logic
if (isset($_POST['enroll_course_id'])) {
    $course_to_enroll = intval($_POST['enroll_course_id']);
    try {
        // Double check if already enrolled
        $stmt = $pdo->prepare("SELECT id FROM enrollments WHERE student_id = ? AND course_id = ?");
        $stmt->execute([$student_id, $course_to_enroll]);
        if (!$stmt->fetch()) {
            $stmt = $pdo->prepare("INSERT INTO enrollments (student_id, course_id, status, progress) VALUES (?, ?, 'Enrolled', 0)");
            $stmt->execute([$student_id, $course_to_enroll]);
        }
        header("Location: course-details.php?course_id=" . $course_to_enroll);
        exit;
    } catch (PDOException $e) {
        $enroll_error = "Enrollment failed: " . $e->getMessage();
    }
}

try {
    // Fetch filter skills
    $skills_stmt = $pdo->query("SELECT id, skill_name FROM skills ORDER BY skill_name");
    $all_skills = $skills_stmt->fetchAll();

    // Fetch courses with rating and enrollment status
    $sql = "
        SELECT c.*, s.skill_name, s.level as skill_level,
               COALESCE(AVG(f.rating), 0) as avg_rating, 
               COUNT(f.id) as feedback_count,
               e.status as enrollment_status
        FROM courses c
        LEFT JOIN skills s ON c.skill_id = s.id
        LEFT JOIN feedback f ON c.id = f.course_id
        LEFT JOIN enrollments e ON c.id = e.course_id AND e.student_id = :student_id
    ";

    if ($skill_filter > 0) {
        $sql .= " WHERE c.skill_id = :skill_id ";
    }

    $sql .= " GROUP BY c.id ORDER BY c.course_name ASC";

    $stmt = $pdo->prepare($sql);
    $params = ['student_id' => $student_id];
    if ($skill_filter > 0) {
        $params['skill_id'] = $skill_filter;
    }
    $stmt->execute($params);
    $courses = $stmt->fetchAll();

} catch (PDOException $e) {
    die("Error loading courses: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Browse Courses</title>
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
                        Browse Courses
                    </span>
                    <div class="ms-auto">
                        <span class="badge bg-primary px-3 py-2"><i class="fa-solid fa-user me-1"></i> Student Role</span>
                    </div>
                </div>
            </nav>

            <div class="container-fluid">
                <!-- Filters Section -->
                <div class="card border-0 shadow-sm p-3 mb-4">
                    <form method="GET" action="courses.php" class="row align-items-center g-3">
                        <div class="col-md-4">
                            <label for="skill_id" class="form-label fw-bold small text-uppercase">Filter By Skill</label>
                            <select class="form-select" id="skill_id" name="skill_id" onchange="this.form.submit()">
                                <option value="0">All Skills</option>
                                <?php foreach ($all_skills as $s): ?>
                                    <option value="<?php echo $s['id']; ?>" <?php echo $skill_filter === intval($s['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($s['skill_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <?php if ($skill_filter > 0): ?>
                            <div class="col-md-2">
                                <label class="form-label d-block">&nbsp;</label>
                                <a href="courses.php" class="btn btn-outline-secondary w-100"><i class="fa-solid fa-times me-1"></i> Clear Filter</a>
                            </div>
                        <?php endif; ?>
                    </form>
                </div>

                <?php if (isset($enroll_error)): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($enroll_error); ?></div>
                <?php endif; ?>

                <!-- Courses Cards Grid -->
                <div class="row g-4">
                    <?php if (count($courses) === 0): ?>
                        <div class="col-12 text-center py-5">
                            <img src="https://illustrations.popsy.co/blue/page-not-found.svg" alt="no-courses" style="max-height: 200px;" class="mb-3">
                            <h4 class="text-muted">No courses found matching the selected filter.</h4>
                        </div>
                    <?php else: ?>
                        <?php foreach ($courses as $c): ?>
                            <div class="col-md-6 col-lg-4">
                                <div class="card border-0 shadow-sm h-100 card-hover">
                                    <div class="card-body d-flex flex-column">
                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <span class="badge bg-secondary"><?php echo htmlspecialchars($c['skill_name'] ?? 'General'); ?></span>
                                            <span class="badge bg-light text-dark border"><i class="fa-solid fa-clock me-1"></i> <?php echo htmlspecialchars($c['duration']); ?></span>
                                        </div>
                                        <h5 class="card-title fw-bold text-dark mb-2"><?php echo htmlspecialchars($c['course_name']); ?></h5>
                                        <p class="small text-muted mb-3"><i class="fa-solid fa-user-tie me-1"></i> Instructor: <?php echo htmlspecialchars($c['instructor']); ?></p>
                                        
                                        <!-- Star rating displaying average rating -->
                                        <div class="mb-3 d-flex align-items-center">
                                            <div class="text-warning me-2">
                                                <?php 
                                                    $rating = round($c['avg_rating'], 1);
                                                    for ($i = 1; $i <= 5; $i++) {
                                                        if ($i <= $rating) {
                                                            echo '<i class="fa-solid fa-star"></i>';
                                                        } elseif ($i - 0.5 <= $rating) {
                                                            echo '<i class="fa-solid fa-star-half-stroke"></i>';
                                                        } else {
                                                            echo '<i class="fa-regular fa-star"></i>';
                                                        }
                                                    }
                                                ?>
                                            </div>
                                            <small class="text-muted fw-semibold"><?php echo $rating; ?>/5 (<?php echo $c['feedback_count']; ?>)</small>
                                        </div>

                                        <p class="card-text text-muted flex-grow-1 text-truncate-3" style="font-size: 0.95rem;">
                                            <?php echo htmlspecialchars($c['description']); ?>
                                        </p>

                                        <hr class="my-3">

                                        <div class="mt-auto">
                                            <?php if ($c['enrollment_status'] === 'Completed'): ?>
                                                <a href="course-details.php?course_id=<?php echo $c['id']; ?>" class="btn btn-success w-100 fw-semibold">
                                                    <i class="fa-solid fa-circle-check me-1"></i> Completed - View Details
                                                </a>
                                            <?php elseif ($c['enrollment_status'] === 'In Progress' || $c['enrollment_status'] === 'Enrolled'): ?>
                                                <a href="course-details.php?course_id=<?php echo $c['id']; ?>" class="btn btn-warning w-100 fw-semibold text-dark">
                                                    <i class="fa-solid fa-spinner me-1"></i> Resume Course
                                                </a>
                                            <?php else: ?>
                                                <form method="POST" action="courses.php?skill_id=<?php echo $skill_filter; ?>">
                                                    <input type="hidden" name="enroll_course_id" value="<?php echo $c['id']; ?>">
                                                    <button type="submit" class="btn btn-primary w-100 fw-semibold">
                                                        <i class="fa-solid fa-graduation-cap me-1"></i> Enroll Now
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
session_start();
require_once 'config/database.php';

// Authentication Check
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'student') {
    header("Location: login.php");
    exit;
}

$student_name = $_SESSION['user_name'];

try {
    // Fetch all skills
    $stmt = $pdo->query("SELECT * FROM skills ORDER BY category, skill_name ASC");
    $skills = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Error loading skills: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Browse Skills</title>
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
                <li class="active">
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
                        Browse Development Skills
                    </span>
                    <div class="ms-auto">
                        <span class="badge bg-primary px-3 py-2"><i class="fa-solid fa-user me-1"></i> Student Role</span>
                    </div>
                </div>
            </nav>

            <div class="container-fluid">
                <div class="row g-4">
                    <?php if (count($skills) === 0): ?>
                        <div class="col-12 text-center py-5">
                            <p class="text-muted">No skills found in the database. Please contact an administrator.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($skills as $skill): ?>
                            <div class="col-md-6 col-lg-4">
                                <div class="card border-0 shadow-sm h-100 card-hover">
                                    <div class="card-body d-flex flex-column">
                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <span class="badge bg-info text-dark"><?php echo htmlspecialchars($skill['category']); ?></span>
                                            <span class="badge bg-secondary"><?php echo htmlspecialchars($skill['level']); ?></span>
                                        </div>
                                        <h5 class="card-title fw-bold text-primary mb-2">
                                            <i class="fa-solid fa-gears me-1"></i> <?php echo htmlspecialchars($skill['skill_name']); ?>
                                        </h5>
                                        <p class="card-text text-muted flex-grow-1">
                                            <?php echo htmlspecialchars($skill['description']); ?>
                                        </p>
                                        <div class="mt-3">
                                            <a href="courses.php?skill_id=<?php echo $skill['id']; ?>" class="btn btn-outline-primary w-100 fw-semibold">
                                                <i class="fa-solid fa-list me-1"></i> View Related Courses
                                            </a>
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

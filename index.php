<?php
session_start();
require_once 'config/database.php';

// Fetch some quick stats
try {
    $stmt1 = $pdo->query("SELECT COUNT(*) as count FROM skills");
    $total_skills = $stmt1->fetch()['count'];

    $stmt2 = $pdo->query("SELECT COUNT(*) as count FROM courses");
    $total_courses = $stmt2->fetch()['count'];
    
    $stmt3 = $pdo->query("SELECT COUNT(*) as count FROM users WHERE role = 'student'");
    $total_students = $stmt3->fetch()['count'];
} catch (PDOException $e) {
    $total_skills = $total_courses = $total_students = 0;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Skills Development Portal</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome Icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

    <!-- Header Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fa-solid fa-graduation-cap me-2"></i>Skills Portal
            </a>
            <button class="navbar-expand-lg navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo $_SESSION['user_role'] === 'admin' ? 'admin/dashboard.php' : 'dashboard.php'; ?>">
                                <i class="fa-solid fa-desktop me-1"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-warning" href="logout.php">
                                <i class="fa-solid fa-right-from-bracket me-1"></i> Logout
                            </a>
                        </li>
                    <?php else: ?>
                        <li class="nav-item"><a class="nav-link" href="login.php"><i class="fa-solid fa-right-to-bracket me-1"></i> Student Login</a></li>
                        <li class="nav-item"><a class="nav-link" href="register.php"><i class="fa-solid fa-user-plus me-1"></i> Register</a></li>
                        <li class="nav-item"><a class="nav-link text-info" href="admin/login.php"><i class="fa-solid fa-user-shield me-1"></i> Admin Portal</a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <header class="bg-primary text-white text-center py-5">
        <div class="container">
            <h1 class="display-4 fw-bold">Develop Your Core Professional & Technical Skills</h1>
            <p class="lead">Enroll in high-quality courses, watch learning videos, test your knowledge with interactive quizzes, and earn printable certificates!</p>
            <?php if (!isset($_SESSION['user_id'])): ?>
                <a href="register.php" class="btn btn-warning btn-lg me-2 fw-semibold"><i class="fa-solid fa-user-plus"></i> Get Started</a>
                <a href="login.php" class="btn btn-outline-light btn-lg"><i class="fa-solid fa-right-to-bracket"></i> Login Now</a>
            <?php else: ?>
                <a href="dashboard.php" class="btn btn-warning btn-lg fw-semibold"><i class="fa-solid fa-desktop"></i> Go to Dashboard</a>
            <?php endif; ?>
        </div>
    </header>

    <!-- Stats Cards -->
    <section class="py-5">
        <div class="container">
            <div class="row g-4 text-center">
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm p-4 card-hover">
                        <div class="text-primary mb-3">
                            <i class="fa-solid fa-award fa-3x"></i>
                        </div>
                        <h3><?php echo $total_skills; ?></h3>
                        <p class="text-muted">In-Demand Skills</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm p-4 card-hover">
                        <div class="text-success mb-3">
                            <i class="fa-solid fa-book-open fa-3x"></i>
                        </div>
                        <h3><?php echo $total_courses; ?></h3>
                        <p class="text-muted">Quality Courses</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm p-4 card-hover">
                        <div class="text-warning mb-3">
                            <i class="fa-solid fa-users fa-3x"></i>
                        </div>
                        <h3><?php echo $total_students; ?></h3>
                        <p class="text-muted">Registered Students</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- How It Works -->
    <section class="bg-light py-5">
        <div class="container">
            <h2 class="text-center fw-bold mb-5">How It Works</h2>
            <div class="row g-4 text-center">
                <div class="col-md-3">
                    <div class="p-3">
                        <div class="badge rounded-pill bg-primary p-3 fs-4 mb-3">1</div>
                        <h5>Register / Login</h5>
                        <p class="text-muted">Create a secure student account in seconds to begin your training journey.</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="p-3">
                        <div class="badge rounded-pill bg-primary p-3 fs-4 mb-3">2</div>
                        <h5>Select a Skill</h5>
                        <p class="text-muted">Browse hot skills like Web Dev, Python, Java, or Communication Skills.</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="p-3">
                        <div class="badge rounded-pill bg-primary p-3 fs-4 mb-3">3</div>
                        <h5>Watch & Learn</h5>
                        <p class="text-muted">Enroll and watch selected high-quality YouTube lectures inside the portal.</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="p-3">
                        <div class="badge rounded-pill bg-primary p-3 fs-4 mb-3">4</div>
                        <h5>Quiz & Certificate</h5>
                        <p class="text-muted">Score 7/10 or more in the course quiz to instantly unlock your Certificate of Completion!</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-dark text-white text-center py-4">
        <div class="container">
            <p class="mb-0">&copy; 2026 Student Skills Development Portal. All rights reserved.</p>
        </div>
    </footer>

    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

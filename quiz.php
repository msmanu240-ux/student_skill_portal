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
    // Check if enrolled
    $stmt = $pdo->prepare("SELECT status FROM enrollments WHERE student_id = ? AND course_id = ?");
    $stmt->execute([$student_id, $course_id]);
    $enrollment = $stmt->fetch();

    if (!$enrollment) {
        header("Location: course-details.php?course_id=" . $course_id);
        exit;
    }

    // Fetch Course Name
    $stmt = $pdo->prepare("SELECT course_name FROM courses WHERE id = ?");
    $stmt->execute([$course_id]);
    $course_name = $stmt->fetch()['course_name'] ?? 'Course';

    // Fetch exactly 10 questions
    $stmt = $pdo->prepare("SELECT * FROM quiz_questions WHERE course_id = ? LIMIT 10");
    $stmt->execute([$course_id]);
    $questions = $stmt->fetchAll();

} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quiz: <?php echo htmlspecialchars($course_name); ?></title>
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
                        <i class="fa-solid fa-circle-question text-warning me-2"></i> Course Assessment Quiz
                    </span>
                    <div class="ms-auto">
                        <a href="learning.php?course_id=<?php echo $course_id; ?>" class="btn btn-outline-secondary btn-sm"><i class="fa-solid fa-arrow-left me-1"></i> Back to Lecture</a>
                    </div>
                </div>
            </nav>

            <div class="container-fluid">
                <div class="row">
                    <div class="col-lg-9 mx-auto">
                        <div class="card border-0 shadow-sm p-4 mb-4">
                            <h3 class="fw-bold text-dark mb-1"><?php echo htmlspecialchars($course_name); ?> - Course Quiz</h3>
                            <p class="text-muted">Answer all 10 questions to pass. Passing score is <strong class="text-success">7/10 or higher</strong>.</p>
                            <div class="alert alert-info py-2">
                                <i class="fa-solid fa-info-circle me-2"></i> All questions are multiple choice. Double check before submitting.
                            </div>
                        </div>

                        <?php if (count($questions) < 10): ?>
                            <div class="alert alert-danger p-4 text-center">
                                <i class="fa-solid fa-triangle-exclamation fa-3x mb-3"></i>
                                <h4 class="fw-bold">Quiz Not Ready!</h4>
                                <p class="mb-0">This course does not have exactly 10 questions set up in the database. Current questions: <?php echo count($questions); ?>/10. Please contact the administrator.</p>
                            </div>
                        <?php else: ?>
                            <form action="quiz-result.php" method="POST">
                                <input type="hidden" name="course_id" value="<?php echo $course_id; ?>">
                                
                                <?php $counter = 1; foreach ($questions as $q): ?>
                                    <div class="card border-0 shadow-sm p-4 mb-4">
                                        <h5 class="fw-bold mb-3 text-dark">
                                            Question <?php echo $counter; ?>: <span class="fw-normal"><?php echo htmlspecialchars($q['question']); ?></span>
                                        </h5>
                                        
                                        <div class="form-check mb-2">
                                            <input class="form-check-input" type="radio" name="answers[<?php echo $q['id']; ?>]" id="q_<?php echo $q['id']; ?>_a" value="A" required>
                                            <label class="form-check-label w-100 p-2 border rounded" for="q_<?php echo $q['id']; ?>_a">
                                                <strong>A.</strong> <?php echo htmlspecialchars($q['option_a']); ?>
                                            </label>
                                        </div>
                                        <div class="form-check mb-2">
                                            <input class="form-check-input" type="radio" name="answers[<?php echo $q['id']; ?>]" id="q_<?php echo $q['id']; ?>_b" value="B" required>
                                            <label class="form-check-label w-100 p-2 border rounded" for="q_<?php echo $q['id']; ?>_b">
                                                <strong>B.</strong> <?php echo htmlspecialchars($q['option_b']); ?>
                                            </label>
                                        </div>
                                        <div class="form-check mb-2">
                                            <input class="form-check-input" type="radio" name="answers[<?php echo $q['id']; ?>]" id="q_<?php echo $q['id']; ?>_c" value="C" required>
                                            <label class="form-check-label w-100 p-2 border rounded" for="q_<?php echo $q['id']; ?>_c">
                                                <strong>C.</strong> <?php echo htmlspecialchars($q['option_c']); ?>
                                            </label>
                                        </div>
                                        <div class="form-check mb-3">
                                            <input class="form-check-input" type="radio" name="answers[<?php echo $q['id']; ?>]" id="q_<?php echo $q['id']; ?>_d" value="D" required>
                                            <label class="form-check-label w-100 p-2 border rounded" for="q_<?php echo $q['id']; ?>_d">
                                                <strong>D.</strong> <?php echo htmlspecialchars($q['option_d']); ?>
                                            </label>
                                        </div>
                                    </div>
                                <?php $counter++; endforeach; ?>

                                <div class="text-center mb-5">
                                    <button type="submit" class="btn btn-warning btn-lg px-5 fw-bold text-dark">
                                        <i class="fa-solid fa-paper-plane me-2"></i> Submit Quiz Answers
                                    </button>
                                </div>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

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
    header("Location: dashboard.php");
    exit;
}

try {
    // Check if enrolled and completed
    $stmt = $pdo->prepare("SELECT status FROM enrollments WHERE student_id = ? AND course_id = ?");
    $stmt->execute([$student_id, $course_id]);
    $enrollment = $stmt->fetch();

    if (!$enrollment || $enrollment['status'] !== 'Completed') {
        // Must complete the course before submitting feedback
        header("Location: course-details.php?course_id=" . $course_id);
        exit;
    }

    // Fetch Course Name
    $stmt = $pdo->prepare("SELECT course_name FROM courses WHERE id = ?");
    $stmt->execute([$course_id]);
    $course_name = $stmt->fetch()['course_name'] ?? 'Course';

    // Check if feedback already submitted
    $stmt = $pdo->prepare("SELECT * FROM feedback WHERE student_id = ? AND course_id = ?");
    $stmt->execute([$student_id, $course_id]);
    $existing_feedback = $stmt->fetch();

} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rating = isset($_POST['rating']) ? intval($_POST['rating']) : 0;
    $comments = trim($_POST['comments'] ?? '');

    if ($rating < 1 || $rating > 5) {
        $error = "Please select a star rating between 1 and 5.";
    } else {
        try {
            if ($existing_feedback) {
                // Update feedback
                $stmt = $pdo->prepare("UPDATE feedback SET rating = ?, comments = ? WHERE student_id = ? AND course_id = ?");
                $stmt->execute([$rating, $comments, $student_id, $course_id]);
            } else {
                // Insert feedback
                $stmt = $pdo->prepare("INSERT INTO feedback (student_id, course_id, rating, comments) VALUES (?, ?, ?, ?)");
                $stmt->execute([$student_id, $course_id, $rating, $comments]);
            }
            $success = "Feedback submitted successfully! Thank you for your review.";
            // Reload page to reflect updates
            header("Refresh: 2; URL=dashboard.php");
        } catch (PDOException $e) {
            $error = "Failed to submit feedback: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submit Feedback: <?php echo htmlspecialchars($course_name); ?></title>
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
                        Course Feedback & Review
                    </span>
                    <div class="ms-auto">
                        <a href="dashboard.php" class="btn btn-outline-secondary btn-sm"><i class="fa-solid fa-gauge me-1"></i> Dashboard</a>
                    </div>
                </div>
            </nav>

            <div class="container-fluid">
                <div class="row">
                    <div class="col-lg-6 mx-auto">
                        <div class="card border-0 shadow-sm p-4">
                            <h3 class="fw-bold text-dark mb-1">Course Review Form</h3>
                            <p class="text-muted">Tell us about your learning experience in <strong class="text-primary"><?php echo htmlspecialchars($course_name); ?></strong></p>
                            
                            <hr class="mb-4">

                            <?php if (!empty($error)): ?>
                                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                            <?php endif; ?>

                            <?php if (!empty($success)): ?>
                                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                            <?php endif; ?>

                            <form method="POST" action="feedback.php?course_id=<?php echo $course_id; ?>">
                                <!-- Star Rating Picker -->
                                <div class="mb-4 text-center">
                                    <label class="form-label fw-bold d-block mb-3">Rate the Course Content</label>
                                    <div class="rating-stars">
                                        <input type="radio" name="rating" id="star5" value="5" <?php echo ($existing_feedback && intval($existing_feedback['rating']) === 5) ? 'checked' : ''; ?>>
                                        <label for="star5" title="5 stars"><i class="fa-solid fa-star"></i></label>
                                        
                                        <input type="radio" name="rating" id="star4" value="4" <?php echo ($existing_feedback && intval($existing_feedback['rating']) === 4) ? 'checked' : ''; ?>>
                                        <label for="star4" title="4 stars"><i class="fa-solid fa-star"></i></label>
                                        
                                        <input type="radio" name="rating" id="star3" value="3" <?php echo ($existing_feedback && intval($existing_feedback['rating']) === 3) ? 'checked' : ''; ?>>
                                        <label for="star3" title="3 stars"><i class="fa-solid fa-star"></i></label>
                                        
                                        <input type="radio" name="rating" id="star2" value="2" <?php echo ($existing_feedback && intval($existing_feedback['rating']) === 2) ? 'checked' : ''; ?>>
                                        <label for="star2" title="2 stars"><i class="fa-solid fa-star"></i></label>
                                        
                                        <input type="radio" name="rating" id="star1" value="1" <?php echo ($existing_feedback && intval($existing_feedback['rating']) === 1) ? 'checked' : ''; ?>>
                                        <label for="star1" title="1 star"><i class="fa-solid fa-star"></i></label>
                                    </div>
                                </div>

                                <!-- Review Comments -->
                                <div class="mb-4">
                                    <label for="comments" class="form-label fw-bold">Comments / Suggestions</label>
                                    <textarea class="form-control" id="comments" name="comments" rows="5" required placeholder="Write details about the course quality, instructor, quiz clarity, etc..."><?php echo $existing_feedback ? htmlspecialchars($existing_feedback['comments']) : ''; ?></textarea>
                                </div>

                                <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold">
                                    <i class="fa-solid fa-paper-plane me-1"></i> Submit Review
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

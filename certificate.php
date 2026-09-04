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
    // Fetch certificate record
    $stmt = $pdo->prepare("
        SELECT cert.*, u.name as student_name, c.course_name, c.instructor
        FROM certificates cert
        JOIN users u ON cert.student_id = u.id
        JOIN courses c ON cert.course_id = c.id
        WHERE cert.student_id = ? AND cert.course_id = ?
    ");
    $stmt->execute([$student_id, $course_id]);
    $certificate = $stmt->fetch();

    // Safety check: Only students who pass can view their certificates
    if (!$certificate) {
        // Redirect to dashboard with message
        header("Location: dashboard.php");
        exit;
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
    <title>Certificate - <?php echo htmlspecialchars($certificate['certificate_id']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Google Fonts for Professional Certificates -->
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@600;700;800&family=Great+Vibes&family=Montserrat:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="bg-light">

    <!-- Action panel - hidden in print mode -->
    <div class="container py-3 text-center no-print">
        <a href="dashboard.php" class="btn btn-outline-secondary me-2">
            <i class="fa-solid fa-arrow-left me-1"></i> Back to Dashboard
        </a>
        <button id="btnPrintCertificate" class="btn btn-success me-2">
            <i class="fa-solid fa-print me-1"></i> Print Certificate
        </button>
        <button onclick="window.print()" class="btn btn-primary">
            <i class="fa-solid fa-download me-1"></i> Download PDF
        </button>
    </div>

    <!-- Printable Certificate Layout -->
    <div class="certificate-container">
        <!-- Red and Gold Left Diagonal Shapes -->
        <div class="cert-shape-gold-bg"></div>
        <div class="cert-shape-red-bg"></div>
        <div class="cert-shape-gold-accent"></div>
        <div class="cert-shape-red-accent"></div>

        <!-- Diamond Award Seal on Left Panel -->
        <div class="cert-seal-badge">
            <div class="cert-seal-badge-inner">
                <div class="cert-gold-seal-circle">
                    <div class="text-center">
                        <span style="font-size: 8px; font-weight: 800; display: block; margin-bottom: -2px; line-height: 1;">2026</span>
                        <span style="font-size: 9px; font-weight: 900; display: block; border-top: 1px solid #161617; border-bottom: 1px solid #161617; padding: 1px 0; margin-top: 2px; line-height: 1;">AWARD</span>
                    </div>
                </div>
                <div class="cert-gold-ribbon-tails">
                    <div class="cert-ribbon-tail-l"></div>
                    <div class="cert-ribbon-tail-r"></div>
                </div>
            </div>
        </div>

        <!-- Content Content on Right Side -->
        <div class="certificate-content-right">
            <div>
                <h1 class="cert-main-title">CERTIFICATE</h1>
                <h4 class="cert-sub-title">OF ACHIEVEMENT</h4>
            </div>

            <div>
                <p class="cert-presented-to">PROUDLY PRESENTED TO</p>
                <h2 class="cert-recipient-name"><?php echo htmlspecialchars($certificate['student_name']); ?></h2>
                
                <p class="cert-desc-text">
                    for successfully demonstrating mastery and completing the course assessment for the training program in
                </p>
                <h3 class="cert-course-title"><?php echo htmlspecialchars($certificate['course_name']); ?></h3>
            </div>

            <!-- Signature and Date Row -->
            <div class="cert-signature-row">
                <div class="cert-sign-block">
                    <div class="cert-sign-line" style="font-family: inherit; font-size: 14px; padding-bottom: 0px; color: #fff;">
                        <?php echo date('F d, Y', strtotime($certificate['issue_date'])); ?>
                    </div>
                    <div class="cert-sign-label">Date Issued</div>
                </div>

                <div class="cert-sign-block">
                    <div class="cert-sign-line" style="font-family: inherit; font-size: 13px; font-weight: bold; padding-bottom: 0px; color: #fff;">
                        Score: <?php echo $certificate['score']; ?> / 10
                    </div>
                    <div class="cert-sign-label">Passing Grade</div>
                </div>

                <div class="cert-sign-block">
                    <div class="cert-sign-line"><?php echo htmlspecialchars($certificate['instructor']); ?></div>
                    <div class="cert-sign-label">Course Instructor</div>
                </div>
            </div>
        </div>
    </div>

    <script src="js/script.js"></script>
</body>
</html>

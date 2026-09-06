<?php

require_once "includes/auth.php";
require_once "config/database.php";

function e($value)
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

$userId = (int) $_SESSION['user_id'];

/* Students and institutions land here. Industry and academician users
   have their own dashboards. */
if (in_array($_SESSION['role'] ?? '', ['industry', 'academician'], true)) {
    header("Location: " . ($_SESSION['role'] === 'industry'
        ? "industry-dashboard.php"
        : "academician-dashboard.php"));
    exit();
}

$userName = $_SESSION['full_name'] ?? $_SESSION['name'] ?? "Student";


/* Latest skill assessment */

$latestScore = null;
$latestTotal = null;

$stmt = $conn->prepare(
    "SELECT score, total_questions
     FROM assessments
     WHERE user_id = ?
     ORDER BY completed_at DESC
     LIMIT 1"
);
$stmt->bind_param("i", $userId);
$stmt->execute();
$assessmentResult = $stmt->get_result();

if ($assessmentResult->num_rows > 0) {
    $row = $assessmentResult->fetch_assoc();
    $latestScore = (int) $row['score'];
    $latestTotal = (int) $row['total_questions'];
}
$stmt->close();

$assessmentPercent = ($latestTotal > 0)
    ? round(($latestScore / $latestTotal) * 100)
    : null;


/* Skills added */

$stmt = $conn->prepare("SELECT COUNT(*) AS total FROM skills WHERE user_id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$skillCount = (int) ($stmt->get_result()->fetch_assoc()['total'] ?? 0);
$stmt->close();


/* Applications, grouped by status */

$applicationCounts = [];
$totalApplications = 0;

$stmt = $conn->prepare(
    "SELECT status, COUNT(*) AS total
     FROM applications
     WHERE student_id = ?
     GROUP BY status"
);
$stmt->bind_param("i", $userId);
$stmt->execute();
$appResult = $stmt->get_result();

while ($row = $appResult->fetch_assoc()) {
    $applicationCounts[$row['status']] = (int) $row['total'];
    $totalApplications += (int) $row['total'];
}
$stmt->close();


/* A few recent opportunities to surface on the dashboard */

$recentOpportunities = [];

$result = $conn->query(
    "SELECT id, title, type, location, deadline
     FROM opportunities
     ORDER BY created_at DESC
     LIMIT 3"
);

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $recentOpportunities[] = $row;
    }
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | SkillBridge</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>

<div class="app">

    <!-- SIDEBAR -->

    <aside class="sidebar">

        <div class="side-logo">
            Skill<span>Bridge</span>
        </div>

        <div class="side-title">
            Student Portal
        </div>

        <a href="dashboard.php" class="side-link active">
            <span>▣</span>
            Dashboard
        </a>

        <a href="skill-assessment.php" class="side-link">
            <span>✓</span>
            Skill Assessment
        </a>

        <a href="skill-profile.php" class="side-link">
            <span>◎</span>
            Skill Profile
        </a>

        <a href="skill-gap.php" class="side-link">
            <span>◈</span>
            Skill Gap Analysis
        </a>

        <a href="ai-recommendations.php" class="side-link">
            <span>✦</span>
            AI Recommendations
        </a>

        <a href="opportunities.php" class="side-link">
            <span>▤</span>
            Opportunities
        </a>

        <a href="applications.php" class="side-link">
            <span>▥</span>
            My Applications
        </a>

        <a href="portfolio.php" class="side-link">
            <span>◆</span>
            Digital Portfolio
        </a>

        <div class="side-title">
            Account
        </div>

        <a href="logout.php" class="side-link">
            <span>↩</span>
            Logout
        </a>

    </aside>


    <!-- MAIN CONTENT -->

    <main class="main">

        <div class="topbar">
            <div>
                <h1>Welcome back, <?= e($userName) ?> 👋</h1>
                <p>Here's where you left off.</p>
            </div>
        </div>


        <!-- STATS -->

        <section class="stats-grid">

            <div class="stat-card">
                <div class="stat-icon">✓</div>
                <div>
                    <p>Latest Assessment</p>
                    <h2><?= $assessmentPercent !== null ? $assessmentPercent . "%" : "—" ?></h2>
                    <span>
                        <?= $assessmentPercent !== null
                            ? "Score {$latestScore}/{$latestTotal}"
                            : "Not taken yet" ?>
                    </span>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">◎</div>
                <div>
                    <p>Skills Added</p>
                    <h2><?= $skillCount ?></h2>
                    <span>In your skill profile</span>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">▥</div>
                <div>
                    <p>Applications</p>
                    <h2><?= $totalApplications ?></h2>
                    <span>
                        <?= isset($applicationCounts['Selected'])
                            ? $applicationCounts['Selected'] . " selected"
                            : "Total submitted" ?>
                    </span>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">◈</div>
                <div>
                    <p>Shortlisted</p>
                    <h2><?= $applicationCounts['Shortlisted'] ?? 0 ?></h2>
                    <span>Awaiting next steps</span>
                </div>
            </div>

        </section>


        <!-- QUICK ACTIONS -->

        <section class="dashboard-card">
            <div class="card-header">
                <div>
                    <h2>Quick Actions</h2>
                    <p>Pick up where the blueprint leaves off.</p>
                </div>
            </div>

            <div class="quick-actions">

                <?php if ($assessmentPercent === null): ?>
                <a href="skill-assessment.php" class="quick-action">
                    <div class="quick-icon">✓</div>
                    <div>
                        <h3>Take Your Skill Assessment</h3>
                        <p>Start here — this feeds your skill profile and gap analysis.</p>
                    </div>
                </a>
                <?php else: ?>
                <a href="skill-gap.php" class="quick-action">
                    <div class="quick-icon">◈</div>
                    <div>
                        <h3>Review Your Skill Gap</h3>
                        <p>See where you stand against your target role.</p>
                    </div>
                </a>
                <?php endif; ?>

                <a href="ai-recommendations.php" class="quick-action">
                    <div class="quick-icon">✦</div>
                    <div>
                        <h3>AI Recommendations</h3>
                        <p>Courses, internships and jobs picked for you.</p>
                    </div>
                </a>

                <a href="opportunities.php" class="quick-action">
                    <div class="quick-icon">▤</div>
                    <div>
                        <h3>Browse Opportunities</h3>
                        <p>Internships, jobs and projects from industry partners.</p>
                    </div>
                </a>

                <a href="portfolio.php" class="quick-action">
                    <div class="quick-icon">◆</div>
                    <div>
                        <h3>Build Your Portfolio</h3>
                        <p>Showcase projects and certifications to recruiters.</p>
                    </div>
                </a>

            </div>
        </section>


        <!-- RECENT OPPORTUNITIES -->

        <section class="dashboard-card">
            <div class="card-header">
                <div>
                    <h2>Latest Opportunities</h2>
                    <p>Newly posted by industry partners.</p>
                </div>
                <a href="opportunities.php" class="secondary-btn">View All</a>
            </div>

            <?php if (empty($recentOpportunities)): ?>

                <div class="empty-state">
                    <div class="empty-icon">▤</div>
                    <h3>No opportunities posted yet</h3>
                    <p>Check back soon — new internships and jobs show up here.</p>
                </div>

            <?php else: ?>

                <?php foreach ($recentOpportunities as $op): ?>
                    <div class="op-card">
                        <h3><?= e($op['title']) ?></h3>
                        <p>
                            <?= e($op['type']) ?>
                            <?= $op['location'] ? " • " . e($op['location']) : "" ?>
                            <?= $op['deadline'] ? " • Deadline " . e($op['deadline']) : "" ?>
                        </p>
                        <a href="opportunities.php" class="secondary-btn">View</a>
                    </div>
                <?php endforeach; ?>

            <?php endif; ?>

        </section>

    </main>

</div>

<script src="script.js"></script>

</body>
</html>

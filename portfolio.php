<?php

require_once "includes/auth.php";
require_once "config/database.php";

function e($value)
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

$userId = (int) $_SESSION['user_id'];
$message = "";

/* =========================
   HANDLE FORM SUBMISSIONS
========================= */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $action = $_POST['action'] ?? '';

    if ($action === 'update_headline') {

        $headline  = trim($_POST['headline'] ?? '');
        $about     = trim($_POST['about'] ?? '');
        $resumeUrl = trim($_POST['resume_url'] ?? '');

        $stmt = $conn->prepare(
            "INSERT INTO portfolios (user_id, headline, about, resume_url)
             VALUES (?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE
                headline = VALUES(headline),
                about = VALUES(about),
                resume_url = VALUES(resume_url)"
        );
        $stmt->bind_param("isss", $userId, $headline, $about, $resumeUrl);
        $stmt->execute();
        $stmt->close();

        $message = "Portfolio details saved.";

    } elseif ($action === 'add_project') {

        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $technologies = trim($_POST['technologies'] ?? '');
        $projectUrl = trim($_POST['project_url'] ?? '');

        if ($title === '') {
            $message = "Project title is required.";
        } else {
            $stmt = $conn->prepare(
                "INSERT INTO projects (user_id, title, description, technologies, project_url)
                 VALUES (?, ?, ?, ?, ?)"
            );
            $stmt->bind_param("issss", $userId, $title, $description, $technologies, $projectUrl);
            $stmt->execute();
            $stmt->close();
            $message = "Project added.";
        }

    } elseif ($action === 'delete_project') {

        $projectId = (int) ($_POST['project_id'] ?? 0);
        $stmt = $conn->prepare("DELETE FROM projects WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $projectId, $userId);
        $stmt->execute();
        $stmt->close();
        $message = "Project removed.";

    } elseif ($action === 'add_certification') {

        $name = trim($_POST['name'] ?? '');
        $organization = trim($_POST['organization'] ?? '');
        $issueDate = $_POST['issue_date'] ?? null;
        $certificateUrl = trim($_POST['certificate_url'] ?? '');

        if ($name === '') {
            $message = "Certification name is required.";
        } else {
            $stmt = $conn->prepare(
                "INSERT INTO certifications (user_id, name, organization, issue_date, certificate_url)
                 VALUES (?, ?, ?, ?, ?)"
            );
            $stmt->bind_param("issss", $userId, $name, $organization, $issueDate, $certificateUrl);
            $stmt->execute();
            $stmt->close();
            $message = "Certification added.";
        }

    } elseif ($action === 'delete_certification') {

        $certId = (int) ($_POST['certification_id'] ?? 0);
        $stmt = $conn->prepare("DELETE FROM certifications WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $certId, $userId);
        $stmt->execute();
        $stmt->close();
        $message = "Certification removed.";
    }
}


/* =========================
   LOAD CURRENT PORTFOLIO
========================= */

$headline = "";
$about = "";
$resumeUrl = "";

$stmt = $conn->prepare("SELECT headline, about, resume_url FROM portfolios WHERE user_id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$portfolioResult = $stmt->get_result();

if ($portfolioResult->num_rows > 0) {
    $row = $portfolioResult->fetch_assoc();
    $headline = $row['headline'];
    $about = $row['about'];
    $resumeUrl = $row['resume_url'];
}
$stmt->close();


$projects = [];
$stmt = $conn->prepare("SELECT id, title, description, technologies, project_url FROM projects WHERE user_id = ? ORDER BY created_at DESC");
$stmt->bind_param("i", $userId);
$stmt->execute();
$projectResult = $stmt->get_result();
while ($row = $projectResult->fetch_assoc()) {
    $projects[] = $row;
}
$stmt->close();


$certifications = [];
$stmt = $conn->prepare("SELECT id, name, organization, issue_date, certificate_url, verified FROM certifications WHERE user_id = ? ORDER BY created_at DESC");
$stmt->bind_param("i", $userId);
$stmt->execute();
$certResult = $stmt->get_result();
while ($row = $certResult->fetch_assoc()) {
    $certifications[] = $row;
}
$stmt->close();

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Digital Portfolio | SkillBridge</title>
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

        <a href="dashboard.php" class="side-link">
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

        <a href="portfolio.php" class="side-link active">
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
                <h1>Digital Portfolio</h1>
                <p>The last step of your journey — showcase your work to recruiters.</p>
            </div>
        </div>

        <?php if ($message !== ""): ?>
            <div style="background:#f0fdf4;color:#166534;padding:15px;border-radius:10px;
                        margin-bottom:20px;border:1px solid #bbf7d0;">
                <?= e($message) ?>
            </div>
        <?php endif; ?>


        <!-- HEADLINE / ABOUT -->

        <section class="dashboard-card">
            <div class="card-header">
                <div>
                    <h2>Profile Summary</h2>
                    <p>What recruiters see first.</p>
                </div>
            </div>

            <form method="POST">
                <input type="hidden" name="action" value="update_headline">

                <label>Headline</label>
                <input type="text" name="headline" class="form-control"
                       placeholder="e.g. BSc Computer Science student, aspiring Data Analyst"
                       value="<?= e($headline) ?>">

                <label>About</label>
                <textarea name="about" class="form-control" rows="4"
                          placeholder="A short summary of who you are and what you're looking for."><?= e($about) ?></textarea>

                <label>Resume Link</label>
                <input type="url" name="resume_url" class="form-control"
                       placeholder="Link to your resume (Google Drive, Dropbox, etc.)"
                       value="<?= e($resumeUrl) ?>">

                <div class="submit-area">
                    <button type="submit" class="btn btn-primary">Save</button>
                </div>
            </form>
        </section>


        <!-- PROJECTS -->

        <section class="dashboard-card">
            <div class="card-header">
                <div>
                    <h2>Projects</h2>
                    <p>Things you've built.</p>
                </div>
            </div>

            <?php if (empty($projects)): ?>
                <div class="empty-state">
                    <div class="empty-icon">◆</div>
                    <h3>No projects added yet</h3>
                    <p>Add a project below to show recruiters what you've built.</p>
                </div>
            <?php else: ?>
                <?php foreach ($projects as $project): ?>
                    <div class="op-card">
                        <h3><?= e($project['title']) ?></h3>
                        <p><?= e($project['description']) ?></p>
                        <?php if (!empty($project['technologies'])): ?>
                            <p><strong>Tech:</strong> <?= e($project['technologies']) ?></p>
                        <?php endif; ?>
                        <?php if (!empty($project['project_url'])): ?>
                            <a href="<?= e($project['project_url']) ?>" target="_blank" rel="noopener" class="secondary-btn">View Project</a>
                        <?php endif; ?>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="action" value="delete_project">
                            <input type="hidden" name="project_id" value="<?= (int) $project['id'] ?>">
                            <button type="submit" class="btn btn-outline-secondary">Remove</button>
                        </form>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>

            <form method="POST" class="mt-4">
                <input type="hidden" name="action" value="add_project">

                <label>Project Title</label>
                <input type="text" name="title" class="form-control" required>

                <label>Description</label>
                <textarea name="description" class="form-control" rows="3"></textarea>

                <label>Technologies Used</label>
                <input type="text" name="technologies" class="form-control" placeholder="e.g. Python, React, MySQL">

                <label>Project Link</label>
                <input type="url" name="project_url" class="form-control" placeholder="GitHub / live demo link">

                <div class="submit-area">
                    <button type="submit" class="btn btn-primary">Add Project</button>
                </div>
            </form>
        </section>


        <!-- CERTIFICATIONS -->

        <section class="dashboard-card">
            <div class="card-header">
                <div>
                    <h2>Certifications</h2>
                    <p>Courses and credentials you've earned.</p>
                </div>
            </div>

            <?php if (empty($certifications)): ?>
                <div class="empty-state">
                    <div class="empty-icon">✓</div>
                    <h3>No certifications added yet</h3>
                    <p>Add one below to strengthen your profile.</p>
                </div>
            <?php else: ?>
                <?php foreach ($certifications as $cert): ?>
                    <div class="op-card">
                        <h3><?= e($cert['name']) ?></h3>
                        <p>
                            <?= e($cert['organization']) ?>
                            <?= $cert['issue_date'] ? " • " . e($cert['issue_date']) : "" ?>
                            <?= $cert['verified'] ? " • Verified" : "" ?>
                        </p>
                        <?php if (!empty($cert['certificate_url'])): ?>
                            <a href="<?= e($cert['certificate_url']) ?>" target="_blank" rel="noopener" class="secondary-btn">View Certificate</a>
                        <?php endif; ?>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="action" value="delete_certification">
                            <input type="hidden" name="certification_id" value="<?= (int) $cert['id'] ?>">
                            <button type="submit" class="btn btn-outline-secondary">Remove</button>
                        </form>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>

            <form method="POST" class="mt-4">
                <input type="hidden" name="action" value="add_certification">

                <label>Certification Name</label>
                <input type="text" name="name" class="form-control" required>

                <label>Issuing Organization</label>
                <input type="text" name="organization" class="form-control">

                <label>Issue Date</label>
                <input type="date" name="issue_date" class="form-control">

                <label>Certificate Link</label>
                <input type="url" name="certificate_url" class="form-control">

                <div class="submit-area">
                    <button type="submit" class="btn btn-primary">Add Certification</button>
                </div>
            </form>
        </section>

    </main>

</div>

<script src="script.js"></script>

</body>
</html>

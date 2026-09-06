<?php

require_once "includes/auth.php";
require_once "config/database.php";

$userId = (int)$_SESSION['user_id'];

function e($value)
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

/* GET CURRENT USER */

$userName = "Academician";
$userInitial = "A";

$stmt = $conn->prepare("SELECT name, email, role FROM users WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();

$userResult = $stmt->get_result();

if ($userResult->num_rows > 0) {

    $user = $userResult->fetch_assoc();

    $userName = $user['name'];

    $nameParts = preg_split('/\s+/', trim($userName));

    $userInitial = strtoupper(substr($nameParts[0], 0, 1));

    if (count($nameParts) > 1) {
        $userInitial .= strtoupper(substr($nameParts[1], 0, 1));
    }
}

$stmt->close();


/* ONLY ACADEMICIAN CAN ACCESS */

/* NOTE: this checked $_SESSION['user_role'], a key nothing in the app
   ever sets (login.php sets $_SESSION['role']) — so this gate always
   failed and academicians were bounced out of their own page. Using
   the role just fetched from the DB above is also more reliable than
   trusting the session. */

if (($user['role'] ?? '') !== 'academician') {

    header("Location: login.php");
    exit();

}


/* SUCCESS MESSAGE */

$successMessage = $_SESSION['research_success'] ?? '';

unset($_SESSION['research_success']);

$errorMessage = "";


/* CREATE NEW PROJECT */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $technologies = trim($_POST['technologies'] ?? '');
    $status = trim($_POST['status'] ?? 'Planning');
    $projectType = trim($_POST['project_type'] ?? 'Research');
    $duration = trim($_POST['duration'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $progress = (int)($_POST['progress'] ?? 0);
    $members = (int)($_POST['members'] ?? 0);
    $industryPartner = trim($_POST['industry_partner'] ?? '');

    if ($title === '') {

        $errorMessage = "Project title is required.";

    } elseif ($progress < 0 || $progress > 100) {

        $errorMessage = "Progress must be between 0 and 100.";

    } elseif ($members < 0) {

        $errorMessage = "Members cannot be negative.";

    } else {

        $stmt = $conn->prepare("
            INSERT INTO research_projects
            (
                user_id,
                title,
                description,
                technologies,
                status,
                project_type,
                duration,
                category,
                progress,
                members,
                industry_partner
            )
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->bind_param(
            "isssssssiis",
            $userId,
            $title,
            $description,
            $technologies,
            $status,
            $projectType,
            $duration,
            $category,
            $progress,
            $members,
            $industryPartner
        );

        if ($stmt->execute()) {

            $_SESSION['research_success'] =
                "Research project created successfully.";

            header("Location: research-projects.php");
            exit();

        } else {

            $errorMessage =
                "Unable to create project. Please try again.";

        }

        $stmt->close();
    }
}


/* PROJECT STATISTICS */

$totalProjects = 0;
$activeProjects = 0;
$totalStudents = 0;
$industryPartners = 0;


/* TOTAL PROJECTS */

$stmt = $conn->prepare("
    SELECT COUNT(*) AS total
    FROM research_projects
    WHERE user_id = ?
");

$stmt->bind_param("i", $userId);
$stmt->execute();

$result = $stmt->get_result();
$row = $result->fetch_assoc();

$totalProjects = (int)$row['total'];

$stmt->close();


/* ACTIVE PROJECTS */

$stmt = $conn->prepare("
    SELECT COUNT(*) AS total
    FROM research_projects
    WHERE user_id = ?
    AND LOWER(status) = 'active'
");

$stmt->bind_param("i", $userId);
$stmt->execute();

$result = $stmt->get_result();
$row = $result->fetch_assoc();

$activeProjects = (int)$row['total'];

$stmt->close();


/* TOTAL STUDENTS */

$stmt = $conn->prepare("
    SELECT COALESCE(SUM(members),0) AS total
    FROM research_projects
    WHERE user_id = ?
");

$stmt->bind_param("i", $userId);
$stmt->execute();

$result = $stmt->get_result();
$row = $result->fetch_assoc();

$totalStudents = (int)$row['total'];

$stmt->close();


/* INDUSTRY PARTNERS */

$stmt = $conn->prepare("
    SELECT COUNT(DISTINCT industry_partner) AS total
    FROM research_projects
    WHERE user_id = ?
    AND industry_partner IS NOT NULL
    AND TRIM(industry_partner) <> ''
");

$stmt->bind_param("i", $userId);
$stmt->execute();

$result = $stmt->get_result();
$row = $result->fetch_assoc();

$industryPartners = (int)$row['total'];

$stmt->close();


/* FETCH PROJECTS */

$projects = [];

$stmt = $conn->prepare("
    SELECT
        id,
        title,
        description,
        technologies,
        status,
        project_type,
        duration,
        category,
        progress,
        members,
        industry_partner,
        created_at
    FROM research_projects
    WHERE user_id = ?
    ORDER BY created_at DESC
");

$stmt->bind_param("i", $userId);
$stmt->execute();

$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {

    $projects[] = $row;

}

$stmt->close();


/* STATUS CLASS */

function statusClass($status)
{
    $status = strtolower(trim($status));

    if ($status === "active") {
        return "status-active";
    }

    if ($status === "completed") {
        return "status-completed";
    }

    return "status-planning";
}


/* CATEGORY CLASS */

function categoryClass($category)
{
    $category = strtolower(trim($category));

    if (strpos($category, "artificial") !== false ||
        strpos($category, "ai") !== false) {

        return "ai";
    }

    if (strpos($category, "data") !== false) {

        return "data";
    }

    if (strpos($category, "web") !== false) {

        return "web";
    }

    if (strpos($category, "iot") !== false) {

        return "iot";
    }

    return "other";
}


/* PROJECT ICON */

function projectIcon($category)
{
    $category = strtolower(trim($category));

    if (strpos($category, "artificial") !== false ||
        strpos($category, "ai") !== false) {

        return "🤖";
    }

    if (strpos($category, "data") !== false) {

        return "📊";
    }

    if (strpos($category, "web") !== false) {

        return "🌐";
    }

    if (strpos($category, "iot") !== false) {

        return "📡";
    }

    return "🔬";
}

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Research & Projects | SkillBridge</title>

    <link
        rel="stylesheet"
        href="style.css"
    >

    <link
        rel="preconnect"
        href="https://fonts.googleapis.com"
    >

    <link
        rel="preconnect"
        href="https://fonts.gstatic.com"
        crossorigin
    >

    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap"
        rel="stylesheet"
    >

    <style>

        body {
            font-family: "Inter", sans-serif;
        }

        .research-page {
            padding-bottom: 50px;
        }

        .research-topbar {
            margin-bottom: 25px;
        }

        .research-topbar h2 {
            margin-bottom: 5px;
        }

        .research-topbar p {
            color: #85859a;
            font-size: 12px;
        }

        .research-hero {
            background: linear-gradient(135deg,#6259ee,#7955e8);
            color: white;
            padding: 30px 34px;
            border-radius: 18px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 25px;
            margin-bottom: 22px;
        }

        .research-hero-badge {
            display: inline-block;
            background: rgba(255,255,255,.16);
            padding: 7px 12px;
            border-radius: 20px;
            font-size: 10px;
            margin-bottom: 12px;
        }

        .research-hero h1 {
            font-size: 27px;
            margin-bottom: 8px;
        }

        .research-hero p {
            max-width: 650px;
            font-size: 12px;
            line-height: 1.7;
            opacity: .92;
        }

        .research-hero-icon {
            width: 82px;
            height: 82px;
            border-radius: 20px;
            background: rgba(255,255,255,.15);
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 38px;
            flex-shrink: 0;
        }

        .research-stats {
            display: grid;
            grid-template-columns: repeat(4,1fr);
            gap: 16px;
            margin-bottom: 24px;
        }

        .research-stat {
            background: white;
            border: 1px solid #e7e6f0;
            border-radius: 15px;
            padding: 19px;
            display: flex;
            align-items: center;
            gap: 13px;
        }

        .research-stat-icon {
            width: 45px;
            height: 45px;
            border-radius: 12px;
            background: #f0efff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 19px;
        }

        .research-stat small {
            display: block;
            color: #85859a;
            font-size: 10px;
            margin-bottom: 4px;
        }

        .research-stat strong {
            color: #202034;
            font-size: 22px;
        }

        .research-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 17px;
            gap: 15px;
            flex-wrap: wrap;
        }

        .research-actions-left h2 {
            font-size: 19px;
            color: #202034;
            margin-bottom: 4px;
        }

        .research-actions-left p {
            font-size: 10px;
            color: #85859a;
        }

        .new-project-btn {
            border: none;
            background: #635bff;
            color: white;
            padding: 11px 17px;
            border-radius: 9px;
            font-size: 11px;
            font-weight: 600;
            cursor: pointer;
        }

        .research-filter {
            background: white;
            border: 1px solid #e7e6f0;
            padding: 15px;
            border-radius: 14px;
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .research-search {
            flex: 1;
            min-width: 220px;
            position: relative;
        }

        .research-search span {
            position: absolute;
            left: 13px;
            top: 10px;
            color: #85859a;
        }

        .research-search input {
            width: 100%;
            box-sizing: border-box;
            padding: 11px 12px 11px 37px;
            border: 1px solid #dedee9;
            border-radius: 8px;
            outline: none;
            font-family: inherit;
            font-size: 11px;
        }

        .research-filter select {
            border: 1px solid #dedee9;
            border-radius: 8px;
            background: white;
            padding: 11px 13px;
            min-width: 145px;
            color: #55556b;
            font-family: inherit;
            font-size: 10px;
            outline: none;
        }

        .project-grid {
            display: grid;
            grid-template-columns: repeat(2,1fr);
            gap: 18px;
        }

        .project-card {
            background: white;
            border: 1px solid #e7e6f0;
            border-radius: 17px;
            padding: 21px;
            transition: .25s;
        }

        .project-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(36,35,70,.07);
        }

        .project-card-top {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 10px;
            margin-bottom: 15px;
        }

        .project-icon {
            width: 46px;
            height: 46px;
            border-radius: 12px;
            background: #f0efff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
        }

        .project-status {
            padding: 6px 9px;
            border-radius: 20px;
            font-size: 8px;
            font-weight: 700;
        }

        .status-active {
            background: #eaf8ef;
            color: #16a34a;
        }

        .status-completed {
            background: #eeeefe;
            color: #635bff;
        }

        .status-planning {
            background: #fff4e8;
            color: #ea580c;
        }

        .project-card h3 {
            font-size: 14px;
            color: #202034;
            line-height: 1.5;
            margin-bottom: 7px;
        }

        .project-description {
            color: #85859a;
            font-size: 10px;
            line-height: 1.6;
            margin-bottom: 15px;
        }

        .project-meta {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px;
            margin-bottom: 15px;
        }

        .project-meta-item {
            background: #fafaff;
            border-radius: 8px;
            padding: 9px;
        }

        .project-meta-item span {
            display: block;
            color: #9999aa;
            font-size: 8px;
            margin-bottom: 3px;
        }

        .project-meta-item strong {
            font-size: 9px;
            color: #45455b;
        }

        .project-label {
            font-size: 9px;
            color: #55556b;
            font-weight: 700;
            margin-bottom: 7px;
        }

        .project-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 5px;
            margin-bottom: 17px;
        }

        .project-tag {
            padding: 5px 8px;
            border-radius: 20px;
            background: #f0efff;
            color: #6259ee;
            font-size: 8px;
            font-weight: 600;
        }

        .project-progress {
            margin-bottom: 16px;
        }

        .project-progress-top {
            display: flex;
            justify-content: space-between;
            color: #85859a;
            font-size: 8px;
            margin-bottom: 6px;
        }

        .progress-bar {
            height: 6px;
            background: #ededf4;
            border-radius: 20px;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg,#635bff,#8658ed);
            border-radius: 20px;
        }

        .project-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
        }

        .project-members {
            display: flex;
            align-items: center;
        }

        .member {
            width: 27px;
            height: 27px;
            border-radius: 50%;
            background: #efefff;
            color: #635bff;
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 8px;
            font-weight: 700;
            border: 2px solid white;
            margin-left: -5px;
        }

        .member:first-child {
            margin-left: 0;
        }

        .project-members span {
            font-size: 8px;
            color: #85859a;
            margin-left: 7px;
        }

        .project-view-btn {
            background: white;
            border: 1px solid #dcdafc;
            color: #635bff;
            border-radius: 8px;
            padding: 8px 12px;
            font-size: 9px;
            font-weight: 600;
            cursor: pointer;
        }

        .research-highlight {
            margin-top: 23px;
            background: linear-gradient(135deg,#f4f2ff,#fbfaff);
            border: 1px solid #ddd9ff;
            border-radius: 16px;
            padding: 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
        }

        .highlight-content h3 {
            color: #29293d;
            font-size: 14px;
            margin-bottom: 6px;
        }

        .highlight-content p {
            color: #77778c;
            font-size: 10px;
            line-height: 1.6;
            max-width: 600px;
        }

        .highlight-btn {
            border: none;
            background: #635bff;
            color: white;
            padding: 11px 17px;
            border-radius: 8px;
            font-size: 10px;
            font-weight: 600;
            cursor: pointer;
            white-space: nowrap;
        }

        .empty-projects {
            grid-column: 1/-1;
            text-align: center;
            padding: 60px 20px;
            background: white;
            border: 1px solid #e7e6f0;
            border-radius: 17px;
            color: #85859a;
        }

        .empty-projects div {
            font-size: 40px;
            margin-bottom: 12px;
        }

        @media(max-width:1050px) {

            .research-stats {
                grid-template-columns: repeat(2,1fr);
            }

            .project-grid {
                grid-template-columns: 1fr;
            }

        }

        @media(max-width:700px) {

            .research-hero {
                padding: 25px;
            }

            .research-hero h1 {
                font-size: 23px;
            }

            .research-hero-icon {
                display: none;
            }

            .research-filter {
                flex-direction: column;
            }

            .research-search {
                width: 100%;
            }

            .research-filter select {
                width: 100%;
            }

            .research-highlight {
                flex-direction: column;
                align-items: flex-start;
            }

            .highlight-btn {
                width: 100%;
            }

        }

        @media(max-width:500px) {

            .research-stats {
                grid-template-columns: 1fr;
            }

            .project-meta {
                grid-template-columns: 1fr;
            }

            .project-footer {
                flex-direction: column;
                align-items: flex-start;
            }

        }

    </style>

</head>


<body>

<div class="app">


    <!-- SIDEBAR -->

    <aside class="sidebar">

        <div class="sidebar-logo">

            <div class="logo-icon">
                S
            </div>

            <h2>
                SkillBridge
            </h2>

        </div>


        <div class="sidebar-title">
            ACADEMICIAN
        </div>


        <nav>

            <a
                href="academician-dashboard.php"
                class="side-link"
            >

                <span>🏠</span>

                Dashboard

            </a>


            <a
                href="research-projects.php"
                class="side-link active"
            >

                <span>🔬</span>

                Research & Projects

            </a>


            <a
                href="collaboration.php"
                class="side-link"
            >

                <span>🤝</span>

                Collaboration

            </a>


            <a href="#" class="side-link">

                <span>🎓</span>

                Student Projects

            </a>


            <a href="#" class="side-link">

                <span>📚</span>

                FDP & Training

            </a>


            <a href="#" class="side-link">

                <span>📊</span>

                Reports

            </a>

        </nav>


        <div class="sidebar-bottom">

            <a
                href="logout.php"
                class="side-link logout-link"
            >

                <span>🚪</span>

                Logout

            </a>

        </div>

    </aside>



    <!-- MAIN -->

    <main class="main research-page">


        <!-- TOPBAR -->

        <div class="topbar research-topbar">

            <div>

                <h2>
                    Research & Projects
                </h2>

                <p>
                    Manage research activities, academic projects and industry-linked initiatives.
                </p>

            </div>

        </div>



        <!-- HERO -->

        <section class="research-hero">

            <div>

                <span class="research-hero-badge">

                    🔬 Academic Research Hub

                </span>

                <h1>
                    Research That Creates Impact
                </h1>

                <p>
                    Connect academic research with real-world industry challenges,
                    involve students in meaningful projects and build collaborative
                    innovation opportunities.
                </p>

            </div>


            <div class="research-hero-icon">

                🔬

            </div>

        </section>



        <!-- STATS -->

        <section class="research-stats">


            <div class="research-stat">

                <div class="research-stat-icon">
                    🔬
                </div>

                <div>

                    <small>
                        Total Projects
                    </small>

                    <strong>
                        <?= $totalProjects ?>
                    </strong>

                </div>

            </div>



            <div class="research-stat">

                <div class="research-stat-icon">
                    🚀
                </div>

                <div>

                    <small>
                        Active Research
                    </small>

                    <strong>
                        <?= $activeProjects ?>
                    </strong>

                </div>

            </div>



            <div class="research-stat">

                <div class="research-stat-icon">
                    👨‍🎓
                </div>

                <div>

                    <small>
                        Students Involved
                    </small>

                    <strong>
                        <?= $totalStudents ?>
                    </strong>

                </div>

            </div>



            <div class="research-stat">

                <div class="research-stat-icon">
                    🤝
                </div>

                <div>

                    <small>
                        Industry Partners
                    </small>

                    <strong>
                        <?= $industryPartners ?>
                    </strong>

                </div>

            </div>

        </section>



        <!-- ACTION -->

        <div class="research-actions">

            <div class="research-actions-left">

                <h2>
                    Research Projects
                </h2>

                <p>
                    Track and manage your ongoing academic and industry projects.
                </p>

            </div>


            <button
                class="new-project-btn"
                onclick="openProjectForm()"
            >

                + New Project

            </button>

        </div>



        <!-- FILTER -->

        <section class="research-filter">

            <div class="research-search">

                <span>
                    🔍
                </span>

                <input
                    type="text"
                    id="projectSearch"
                    placeholder="Search research projects..."
                >

            </div>


            <select id="projectStatus">

                <option value="">
                    All Status
                </option>

                <option value="active">
                    Active
                </option>

                <option value="completed">
                    Completed
                </option>

                <option value="planning">
                    Planning
                </option>

            </select>


            <select id="projectCategory">

                <option value="">
                    All Categories
                </option>

                <option value="ai">
                    Artificial Intelligence
                </option>

                <option value="data">
                    Data Science
                </option>

                <option value="web">
                    Web Technology
                </option>

                <option value="iot">
                    IoT
                </option>

            </select>

        </section>



        <!-- PROJECT GRID -->

        <section
            class="project-grid"
            id="projectGrid"
        >

            <?php if (empty($projects)): ?>

                <div class="empty-projects">

                    <div>🔬</div>

                    <h3>
                        No Research Projects Yet
                    </h3>

                    <p>
                        Create your first research project using the
                        <strong>+ New Project</strong> button.
                    </p>

                </div>

            <?php endif; ?>


            <?php foreach ($projects as $project): ?>

                <?php

                $status = strtolower(trim($project['status']));

                $category = categoryClass($project['category']);

                $icon = projectIcon($project['category']);

                $progress = max(
                    0,
                    min(100, (int)$project['progress'])
                );

                $technologies = [];

                if (!empty($project['technologies'])) {

                    $technologies = array_filter(
                        array_map(
                            'trim',
                            explode(',', $project['technologies'])
                        )
                    );

                }

                ?>

                <div
                    class="project-card"
                    data-status="<?= e($status) ?>"
                    data-category="<?= e($category) ?>"
                >

                    <div class="project-card-top">

                        <div class="project-icon">

                            <?= $icon ?>

                        </div>


                        <span
                            class="project-status <?= statusClass($project['status']) ?>"
                        >

                            <?= e(strtoupper($project['status'])) ?>

                        </span>

                    </div>


                    <h3>

                        <?= e($project['title']) ?>

                    </h3>


                    <p class="project-description">

                        <?= e($project['description']) ?>

                    </p>


                    <div class="project-meta">

                        <div class="project-meta-item">

                            <span>
                                Project Type
                            </span>

                            <strong>

                                <?= e($project['project_type']) ?>

                            </strong>

                        </div>


                        <div class="project-meta-item">

                            <span>
                                Duration
                            </span>

                            <strong>

                                <?= e($project['duration'] ?: 'Not specified') ?>

                            </strong>

                        </div>

                    </div>


                    <div class="project-label">

                        Category

                    </div>

                    <div class="project-tags">

                        <?php if (!empty($project['category'])): ?>

                            <span class="project-tag">

                                <?= e($project['category']) ?>

                            </span>

                        <?php endif; ?>


                        <?php foreach ($technologies as $technology): ?>

                            <span class="project-tag">

                                <?= e($technology) ?>

                            </span>

                        <?php endforeach; ?>

                    </div>


                    <div class="project-progress">

                        <div class="project-progress-top">

                            <span>
                                Project Progress
                            </span>

                            <span>

                                <?= $progress ?>%

                            </span>

                        </div>


                        <div class="progress-bar">

                            <div
                                class="progress-fill"
                                style="width:<?= $progress ?>%;"
                            ></div>

                        </div>

                    </div>


                    <div class="project-footer">

                        <div class="project-members">

                            <?php

                            $memberCount = (int)$project['members'];

                            $memberInitials = [
                                $userInitial
                            ];

                            if ($memberCount > 1) {
                                $memberInitials[] = "ST";
                            }

                            if ($memberCount > 2) {
                                $memberInitials[] = "TM";
                            }

                            ?>

                            <?php foreach ($memberInitials as $initial): ?>

                                <div class="member">

                                    <?= e($initial) ?>

                                </div>

                            <?php endforeach; ?>


                            <span>

                                <?= $memberCount ?> members

                            </span>

                        </div>


                        <button
                            class="project-view-btn"
                            onclick="viewProject(<?= htmlspecialchars(json_encode($project['title']), ENT_QUOTES, 'UTF-8') ?>)"
                        >

                            View Project

                        </button>

                    </div>

                </div>

            <?php endforeach; ?>

        </section>



        <!-- HIGHLIGHT -->

        <section class="research-highlight">

            <div class="highlight-content">

                <h3>
                    🤝 Turn Research Into Industry Collaboration
                </h3>

                <p>
                    Connect your research projects with companies looking for
                    academic expertise, student talent and innovative solutions.
                </p>

            </div>


            <button
                class="highlight-btn"
                onclick="window.location.href='collaboration.php'"
            >

                Explore Collaborations

            </button>

        </section>


    </main>

</div>



<!-- NEW PROJECT MODAL -->

<div
    id="projectModal"
    style="
        display:none;
        position:fixed;
        inset:0;
        background:rgba(20,20,40,.55);
        z-index:9999;
        align-items:center;
        justify-content:center;
        padding:20px;
    "
>

    <div
        style="
            width:100%;
            max-width:600px;
            max-height:90vh;
            overflow:auto;
            background:white;
            border-radius:18px;
            padding:25px;
        "
    >

        <div
            style="
                display:flex;
                justify-content:space-between;
                align-items:center;
                margin-bottom:20px;
            "
        >

            <div>

                <h2 style="font-size:20px;">
                    Create Research Project
                </h2>

                <p
                    style="
                        font-size:11px;
                        color:#85859a;
                        margin-top:4px;
                    "
                >
                    Add your research or academic project details.
                </p>

            </div>


            <button
                type="button"
                onclick="closeProjectForm()"
                style="
                    border:none;
                    background:#f2f2f8;
                    width:32px;
                    height:32px;
                    border-radius:50%;
                    cursor:pointer;
                "
            >
                ✕
            </button>

        </div>


        <form
            method="POST"
            action="research-projects.php"
        >

            <div
                style="
                    display:grid;
                    grid-template-columns:1fr 1fr;
                    gap:12px;
                "
            >

                <div style="grid-column:1/-1;">

                    <label>
                        Project Title
                    </label>

                    <input
                        type="text"
                        name="title"
                        required
                        placeholder="Enter project title"
                        style="
                            width:100%;
                            padding:11px;
                            border:1px solid #dedee9;
                            border-radius:8px;
                            margin-top:5px;
                        "
                    >

                </div>


                <div>

                    <label>
                        Project Type
                    </label>

                    <select
                        name="project_type"
                        required
                        style="
                            width:100%;
                            padding:11px;
                            border:1px solid #dedee9;
                            border-radius:8px;
                            margin-top:5px;
                        "
                    >

                        <option value="Research">
                            Research
                        </option>

                        <option value="Industry Project">
                            Industry Project
                        </option>

                        <option value="Student Project">
                            Student Project
                        </option>

                    </select>

                </div>


                <div>

                    <label>
                        Category
                    </label>

                    <select
                        name="category"
                        required
                        style="
                            width:100%;
                            padding:11px;
                            border:1px solid #dedee9;
                            border-radius:8px;
                            margin-top:5px;
                        "
                    >

                        <option value="">
                            Select Category
                        </option>

                        <option value="Artificial Intelligence">
                            Artificial Intelligence
                        </option>

                        <option value="Data Science">
                            Data Science
                        </option>

                        <option value="Web Technology">
                            Web Technology
                        </option>

                        <option value="IoT">
                            IoT
                        </option>

                        <option value="Cyber Security">
                            Cyber Security
                        </option>

                        <option value="Cloud Computing">
                            Cloud Computing
                        </option>

                        <option value="Other">
                            Other
                        </option>

                    </select>

                </div>


                <div>

                    <label>
                        Status
                    </label>

                    <select
                        name="status"
                        style="
                            width:100%;
                            padding:11px;
                            border:1px solid #dedee9;
                            border-radius:8px;
                            margin-top:5px;
                        "
                    >

                        <option value="Planning">
                            Planning
                        </option>

                        <option value="Active">
                            Active
                        </option>

                        <option value="Completed">
                            Completed
                        </option>

                    </select>

                </div>


                <div>

                    <label>
                        Duration
                    </label>

                    <input
                        type="text"
                        name="duration"
                        placeholder="e.g. 6 Months"
                        style="
                            width:100%;
                            padding:11px;
                            border:1px solid #dedee9;
                            border-radius:8px;
                            margin-top:5px;
                        "
                    >

                </div>


                <div>

                    <label>
                        Progress (%)
                    </label>

                    <input
                        type="number"
                        name="progress"
                        min="0"
                        max="100"
                        value="0"
                        style="
                            width:100%;
                            padding:11px;
                            border:1px solid #dedee9;
                            border-radius:8px;
                            margin-top:5px;
                        "
                    >

                </div>


                <div>

                    <label>
                        Students / Members
                    </label>

                    <input
                        type="number"
                        name="members"
                        min="0"
                        value="0"
                        style="
                            width:100%;
                            padding:11px;
                            border:1px solid #dedee9;
                            border-radius:8px;
                            margin-top:5px;
                        "
                    >

                </div>


                <div style="grid-column:1/-1;">

                    <label>
                        Technologies
                    </label>

                    <input
                        type="text"
                        name="technologies"
                        placeholder="Python, Machine Learning, SQL"
                        style="
                            width:100%;
                            padding:11px;
                            border:1px solid #dedee9;
                            border-radius:8px;
                            margin-top:5px;
                        "
                    >

                </div>


                <div style="grid-column:1/-1;">

                    <label>
                        Industry Partner
                    </label>

                    <input
                        type="text"
                        name="industry_partner"
                        placeholder="Optional industry partner"
                        style="
                            width:100%;
                            padding:11px;
                            border:1px solid #dedee9;
                            border-radius:8px;
                            margin-top:5px;
                        "
                    >

                </div>


                <div style="grid-column:1/-1;">

                    <label>
                        Description
                    </label>

                    <textarea
                        name="description"
                        rows="4"
                        required
                        placeholder="Describe the research project..."
                        style="
                            width:100%;
                            padding:11px;
                            border:1px solid #dedee9;
                            border-radius:8px;
                            margin-top:5px;
                            resize:vertical;
                        "
                    ></textarea>

                </div>

            </div>


            <div
                style="
                    display:flex;
                    justify-content:flex-end;
                    gap:10px;
                    margin-top:20px;
                "
            >

                <button
                    type="button"
                    onclick="closeProjectForm()"
                    style="
                        padding:10px 17px;
                        border:1px solid #dedee9;
                        background:white;
                        border-radius:8px;
                        cursor:pointer;
                    "
                >

                    Cancel

                </button>


                <button
                    type="submit"
                    style="
                        padding:10px 17px;
                        border:none;
                        background:#635bff;
                        color:white;
                        border-radius:8px;
                        cursor:pointer;
                        font-weight:600;
                    "
                >

                    Create Project

                </button>

            </div>

        </form>

    </div>

</div>



<script src="script.js"></script>


<script>

/* OPEN PROJECT FORM */

function openProjectForm()
{
    document.getElementById("projectModal").style.display = "flex";
}


/* CLOSE PROJECT FORM */

function closeProjectForm()
{
    document.getElementById("projectModal").style.display = "none";
}


/* VIEW PROJECT */

function viewProject(projectName)
{
    alert(
        "Project: " +
        projectName +
        "\n\nProject details are available in the research database."
    );
}


/* CLOSE MODAL WHEN CLICKING OUTSIDE */

document.getElementById("projectModal").addEventListener(
    "click",
    function(event)
    {

        if(event.target === this)
        {
            closeProjectForm();
        }

    }
);


/* SEARCH */

const projectSearch =
    document.getElementById("projectSearch");

const projectStatus =
    document.getElementById("projectStatus");

const projectCategory =
    document.getElementById("projectCategory");


function filterProjects()
{

    const search =
        projectSearch.value.toLowerCase().trim();

    const status =
        projectStatus.value.toLowerCase();

    const category =
        projectCategory.value.toLowerCase();


    document
        .querySelectorAll(".project-card")
        .forEach(function(card)
        {

            const text =
                card.textContent.toLowerCase();

            const cardStatus =
                card.dataset.status.toLowerCase();

            const cardCategory =
                card.dataset.category.toLowerCase();


            let show = true;


            if(
                search &&
                !text.includes(search)
            )
            {
                show = false;
            }


            if(
                status &&
                cardStatus !== status
            )
            {
                show = false;
            }


            if(
                category &&
                cardCategory !== category
            )
            {
                show = false;
            }


            card.style.display =
                show ? "" : "none";

        });

}


projectSearch.addEventListener(
    "input",
    filterProjects
);


projectStatus.addEventListener(
    "change",
    filterProjects
);


projectCategory.addEventListener(
    "change",
    filterProjects
);


/* SUCCESS MESSAGE */

<?php if (!empty($successMessage)): ?>

alert(
    <?= json_encode($successMessage) ?>
);

<?php endif; ?>


/* ERROR MESSAGE */

<?php if (!empty($errorMessage)): ?>

alert(
    <?= json_encode($errorMessage) ?>
);

<?php endif; ?>

</script>


</body>

</html>
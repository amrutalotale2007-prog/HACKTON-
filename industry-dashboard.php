<?php
session_start();
require_once "config/database.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION["user_id"];

/* USER INFORMATION */
$stmt = $conn->prepare("SELECT name, email, role FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();

$userResult = $stmt->get_result();
$user = $userResult->fetch_assoc();

$userName = $_SESSION['full_name'] ?? $_SESSION['name'] ?? ($user["name"] ?? "Industry Partner");
$userRole = $user["role"] ?? "industry";

$avatar = strtoupper(substr($userName, 0, 1));


/* ACTIVE OPPORTUNITIES */
$stmt = $conn->prepare("
    SELECT COUNT(*) AS total
    FROM opportunities
    WHERE user_id = ?
    AND (deadline IS NULL OR deadline >= CURDATE())
");

$stmt->bind_param("i", $user_id);
$stmt->execute();

$activeResult = $stmt->get_result()->fetch_assoc();
$activeOpportunities = $activeResult["total"] ?? 0;


/* TOTAL APPLICATIONS */
$stmt = $conn->prepare("
    SELECT COUNT(*) AS total
    FROM applications a
    INNER JOIN opportunities o
    ON a.opportunity_id = o.id
    WHERE o.user_id = ?
");

$stmt->bind_param("i", $user_id);
$stmt->execute();

$appResult = $stmt->get_result()->fetch_assoc();
$totalApplications = $appResult["total"] ?? 0;


/* SELECTED STUDENTS */
$stmt = $conn->prepare("
    SELECT COUNT(*) AS total
    FROM applications a
    INNER JOIN opportunities o
    ON a.opportunity_id = o.id
    WHERE o.user_id = ?
    AND a.status = 'Selected'
");

$stmt->bind_param("i", $user_id);
$stmt->execute();

$selectedResult = $stmt->get_result()->fetch_assoc();
$selectedStudents = $selectedResult["total"] ?? 0;


/* SHORTLISTED STUDENTS */
$stmt = $conn->prepare("
    SELECT COUNT(*) AS total
    FROM applications a
    INNER JOIN opportunities o
    ON a.opportunity_id = o.id
    WHERE o.user_id = ?
    AND a.status = 'Shortlisted'
");

$stmt->bind_param("i", $user_id);
$stmt->execute();

$shortlistedResult = $stmt->get_result()->fetch_assoc();
$shortlistedStudents = $shortlistedResult["total"] ?? 0;


/* RECENT APPLICATIONS */
$stmt = $conn->prepare("
    SELECT
        a.id,
        a.status,
        a.applied_at,
        u.name AS student_name,
        o.title AS opportunity_title
    FROM applications a

    INNER JOIN opportunities o
    ON a.opportunity_id = o.id

    INNER JOIN users u
    ON a.student_id = u.id

    WHERE o.user_id = ?

    ORDER BY a.applied_at DESC
    LIMIT 5
");

$stmt->bind_param("i", $user_id);
$stmt->execute();

$recentApplications = $stmt->get_result();


/* MY OPPORTUNITIES */
$stmt = $conn->prepare("
    SELECT
        o.id,
        o.title,
        o.type,
        o.location,
        o.stipend,
        o.deadline,

        COUNT(a.id) AS applications,

        SUM(
            CASE
                WHEN a.status = 'Shortlisted'
                THEN 1
                ELSE 0
            END
        ) AS shortlisted

    FROM opportunities o

    LEFT JOIN applications a
    ON o.id = a.opportunity_id

    WHERE o.user_id = ?

    GROUP BY
        o.id,
        o.title,
        o.type,
        o.location,
        o.stipend,
        o.deadline

    ORDER BY o.created_at DESC
");

$stmt->bind_param("i", $user_id);
$stmt->execute();

$opportunities = $stmt->get_result();

?>

<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport"
      content="width=device-width, initial-scale=1.0">

<title>Industry Dashboard - SkillBridge</title>

<link rel="stylesheet"
      href="style.css">

<style>

.empty-state {
    text-align: center;
    padding: 35px;
    color: #77778a;
}

.empty-state strong {
    display: block;
    color: #29293d;
    font-size: 16px;
    margin-bottom: 5px;
}

.application-count {
    font-size: 12px;
    color: #77778a;
}

</style>

</head>


<body>


<div class="app">


<!-- SIDEBAR -->

<aside class="sidebar">

    <div class="logo">
        <span>SB</span> SkillBridge
    </div>


    <div class="side-title">
        INDUSTRY
    </div>


    <a href="industry-dashboard.php"
       class="side-link active">

        🏠 Dashboard

    </a>


    <a href="post-opportunity.php"
       class="side-link">

        ➕ Post Opportunity

    </a>


    <a href="candidate-matching.php"
       class="side-link">

        👥 Candidate Matching

    </a>


    <a href="applications.php"
       class="side-link">

        📋 Applications

    </a>


    <a href="opportunities.php"
       class="side-link">

        💼 My Opportunities

    </a>


    <a href="messages.php"
       class="side-link">

        💬 Messages

    </a>


    <div class="side-title">
        COLLABORATION
    </div>


    <a href="collaboration.php"
       class="side-link">

        🤝 Collaboration

    </a>


    <a href="workshops.php"
       class="side-link">

        🎓 Workshops

    </a>


    <a href="projects.php"
       class="side-link">

        🚀 Projects

    </a>


    <div class="side-title">
        ACCOUNT
    </div>


    <a href="logout.php"
       class="side-link">

        🚪 Logout

    </a>

</aside>



<!-- MAIN -->

<main class="main">


<!-- TOPBAR -->

<header class="topbar">

    <div>

        <h2>
            Industry Dashboard
        </h2>

        <p>
            Manage opportunities, discover talent and collaborate with academia.
        </p>

    </div>


    <div class="user-box">

        <div class="avatar">

            <?php echo htmlspecialchars($avatar); ?>

        </div>


        <div>

            <strong>
                <?php echo htmlspecialchars($userName); ?>
            </strong>

            <small>
                Industry Partner
            </small>

        </div>

    </div>

</header>



<!-- CONTENT -->

<section class="page-content">


<!-- WELCOME -->

<div class="industry-welcome">

    <div>

        <span class="hero-label">
            INDUSTRY PORTAL
        </span>


        <h1>

            Build Your
            <span>Future Workforce</span>

        </h1>


        <p>

            Connect with skilled students, post opportunities,
            discover talented candidates and build meaningful
            academic collaborations.

        </p>

    </div>


    <div class="industry-icon">
        🏢
    </div>

</div>



<!-- QUICK ACTIONS -->

<div class="quick-actions">


<a href="post-opportunity.php"
   class="quick-action">

    <div class="quick-icon purple">
        ➕
    </div>

    <div>

        <strong>
            Post Opportunity
        </strong>

        <span>
            Create internship, job or project
        </span>

    </div>

</a>



<a href="candidate-matching.php"
   class="quick-action">

    <div class="quick-icon blue">
        👥
    </div>

    <div>

        <strong>
            Find Candidates
        </strong>

        <span>
            Discover skill-matched students
        </span>

    </div>

</a>



<a href="collaboration.php"
   class="quick-action">

    <div class="quick-icon green">
        🤝
    </div>

    <div>

        <strong>
            Collaborate
        </strong>

        <span>
            Connect with academicians
        </span>

    </div>

</a>


</div>



<!-- KPIs -->

<div class="kpis">


<div class="kpi">

    <div class="kpi-icon">
        💼
    </div>

    <div>

        <strong>
            <?php echo $activeOpportunities; ?>
        </strong>

        <span>
            Active Opportunities
        </span>

    </div>

</div>



<div class="kpi">

    <div class="kpi-icon">
        👥
    </div>

    <div>

        <strong>
            <?php echo $totalApplications; ?>
        </strong>

        <span>
            Applications
        </span>

    </div>

</div>



<div class="kpi">

    <div class="kpi-icon">
        🎯
    </div>

    <div>

        <strong>
            <?php echo $shortlistedStudents; ?>
        </strong>

        <span>
            Shortlisted
        </span>

    </div>

</div>



<div class="kpi">

    <div class="kpi-icon">
        🎓
    </div>

    <div>

        <strong>
            <?php echo $selectedStudents; ?>
        </strong>

        <span>
            Hired Students
        </span>

    </div>

</div>


</div>



<!-- TWO COLUMN -->

<div class="dashboard-two-column">


<!-- RECENT APPLICATIONS -->

<div class="table-card">


<div class="card-heading">

    <div>

        <h2>
            Recent Applications
        </h2>

        <p>
            Latest candidates applying to your opportunities.
        </p>

    </div>


    <a href="applications.php"
       class="btn btn-outline">

        View All

    </a>

</div>



<div class="candidate-list">


<?php if ($recentApplications->num_rows > 0): ?>


<?php while ($application = $recentApplications->fetch_assoc()): ?>


<div class="candidate-row">


    <div class="candidate-avatar">

        <?php
        echo strtoupper(
            substr(
                $application["student_name"],
                0,
                1
            )
        );
        ?>

    </div>


    <div class="candidate-info">

        <strong>

            <?php
            echo htmlspecialchars(
                $application["student_name"]
            );
            ?>

        </strong>


        <span>

            <?php
            echo htmlspecialchars(
                $application["opportunity_title"]
            );
            ?>

        </span>

    </div>


    <div class="candidate-match">

        <small>
            Applied
        </small>

    </div>


    <?php

    $status = $application["status"];

    $statusClass = "status-review";

    if ($status == "Selected") {
        $statusClass = "status-selected";
    }

    if ($status == "Shortlisted") {
        $statusClass = "status-shortlisted";
    }

    ?>

    <span class="status <?php echo $statusClass; ?>">

        <?php echo htmlspecialchars($status); ?>

    </span>


</div>


<?php endwhile; ?>


<?php else: ?>


<div class="empty-state">

    <strong>
        No applications yet
    </strong>

    Applications will appear here when students apply to your opportunities.

</div>


<?php endif; ?>


</div>

</div>



<!-- ANALYTICS -->

<div class="analytics-card">

    <div class="card-heading">

        <div>

            <h2>
                Hiring Analytics
            </h2>

            <p>
                Current application activity.
            </p>

        </div>

    </div>


    <div class="analytics-number">

        <?php echo $totalApplications; ?>

        <span>
            Applications
        </span>

    </div>


    <div class="fake-chart">

        <div class="chart-bar"
             style="height:35%;">

            <span>
                Week 1
            </span>

        </div>


        <div class="chart-bar"
             style="height:50%;">

            <span>
                Week 2
            </span>

        </div>


        <div class="chart-bar"
             style="height:65%;">

            <span>
                Week 3
            </span>

        </div>


        <div class="chart-bar"
             style="height:80%;">

            <span>
                Week 4
            </span>

        </div>

    </div>


    <div class="analytics-footer">

        <span>
            Total Applications
        </span>

        <strong>
            <?php echo $totalApplications; ?>
        </strong>

    </div>

</div>


</div>



<!-- OPPORTUNITIES -->

<div class="section-heading">

    <div>

        <h2>
            Your Active Opportunities
        </h2>

        <p>
            Manage your currently published opportunities.
        </p>

    </div>


    <a href="post-opportunity.php"
       class="btn btn-primary">

        + Post New

    </a>

</div>



<div class="industry-op-grid">


<?php if ($opportunities->num_rows > 0): ?>


<?php while ($op = $opportunities->fetch_assoc()): ?>


<div class="industry-op-card">


<div class="industry-op-top">


<div class="opportunity-logo">

<?php

$title = $op["title"];

$words = explode(" ", $title);

$logo = "";

foreach ($words as $word) {

    $logo .= strtoupper(
        substr($word, 0, 1)
    );

    if (strlen($logo) >= 2) {
        break;
    }
}

echo htmlspecialchars($logo);

?>

</div>



<span class="status status-selected">

    <?php

    if (
        !empty($op["deadline"]) &&
        $op["deadline"] < date("Y-m-d")
    ) {
        echo "Closed";
    } else {
        echo "Active";
    }

    ?>

</span>


</div>



<h3>

<?php
echo htmlspecialchars(
    $op["title"]
);
?>

</h3>



<p>

<?php
echo htmlspecialchars(
    $op["type"] ?? "Opportunity"
);
?>

<?php if (!empty($op["location"])): ?>

    • <?php echo htmlspecialchars($op["location"]); ?>

<?php endif; ?>

</p>



<div class="opportunity-stats">


<div>

    <strong>
        <?php echo $op["applications"] ?? 0; ?>
    </strong>

    <span>
        Applications
    </span>

</div>


<div>

    <strong>
        <?php echo $op["shortlisted"] ?? 0; ?>
    </strong>

    <span>
        Shortlisted
    </span>

</div>


<div>

    <strong>
        <?php
        echo !empty($op["stipend"])
            ? htmlspecialchars($op["stipend"])
            : "—";
        ?>
    </strong>

    <span>
        Stipend
    </span>

</div>


</div>



<div class="opportunity-actions">


<a href="opportunity-details.php?id=<?php echo $op["id"]; ?>"
   class="btn btn-outline">

    Manage

</a>


<a href="candidate-matching.php?opportunity_id=<?php echo $op["id"]; ?>"
   class="btn btn-primary">

    Candidates

</a>


</div>


</div>


<?php endwhile; ?>


<?php else: ?>


<div class="empty-state"
     style="grid-column:1/-1;">

    <strong>
        No opportunities posted yet
    </strong>

    <p>
        Create your first internship, job or project opportunity.
    </p>

    <a href="post-opportunity.php"
       class="btn btn-primary">

        + Post Opportunity

    </a>

</div>


<?php endif; ?>


</div>



<!-- COLLABORATION BANNER -->

<div class="collaboration-banner">


<div class="collab-icon">
    🤝
</div>


<div>

    <h2>
        Build Strong Academic Partnerships
    </h2>

    <p>

        Connect with colleges, academicians and researchers
        for internships, workshops, live projects and research.

    </p>

</div>


<a href="collaboration.php"
   class="btn btn-primary">

    Explore Collaboration

</a>


</div>


</section>

</main>

</div>


<script src="script.js"></script>

</body>

</html>
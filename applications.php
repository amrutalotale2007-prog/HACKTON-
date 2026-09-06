<?php

session_start();

require_once "config/database.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION["user_id"];

/* Get logged-in student */
$userQuery = $conn->prepare("
    SELECT name
    FROM users
    WHERE id = ?
");

$userQuery->bind_param("i", $user_id);
$userQuery->execute();

$userResult = $userQuery->get_result();
$user = $userResult->fetch_assoc();

$userName = $user["name"] ?? "Student";


/* Get applications */
$applications = [];

$query = $conn->prepare("
    SELECT 
        a.id,
        a.status,
        a.applied_at,
        o.title,
        o.type,
        o.category,
        o.skills,
        o.location,
        o.stipend
    FROM applications a
    INNER JOIN opportunities o
        ON a.opportunity_id = o.id
    WHERE a.student_id = ?
    ORDER BY a.applied_at DESC
");

$query->bind_param("i", $user_id);
$query->execute();

$result = $query->get_result();

while ($row = $result->fetch_assoc()) {
    $applications[] = $row;
}


/* Application statistics */
$totalApplications = count($applications);

$underReview = 0;
$shortlisted = 0;
$selected = 0;
$rejected = 0;

foreach ($applications as $application) {

    switch ($application["status"]) {

        case "Under Review":
            $underReview++;
            break;

        case "Shortlisted":
            $shortlisted++;
            break;

        case "Selected":
            $selected++;
            break;

        case "Rejected":
            $rejected++;
            break;
    }
}

?>

<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport"
      content="width=device-width, initial-scale=1.0">

<title>My Applications - SkillBridge</title>

<link rel="stylesheet" href="style.css">

<style>

.application-hero {

    background: linear-gradient(
        135deg,
        #635bff,
        #7c3aed
    );

    color: white;

    border-radius: 18px;

    padding: 30px;

    display: flex;

    justify-content: space-between;

    align-items: center;

    margin-bottom: 22px;
}

.hero-label {

    font-size: 11px;

    font-weight: 700;

    letter-spacing: 1.5px;

    opacity: .8;
}

.application-hero h1 {

    font-size: 30px;

    margin: 10px 0;
}

.application-hero h1 span {

    color: #ddd9ff;
}

.application-hero p {

    font-size: 13px;

    max-width: 600px;

    line-height: 1.6;

    opacity: .9;
}

.hero-icon {

    font-size: 60px;
}


/* KPI */

.kpis {

    display: grid;

    grid-template-columns:
        repeat(4, 1fr);

    gap: 16px;

    margin-bottom: 22px;
}

.kpi {

    background: white;

    border: 1px solid #e7e6f0;

    border-radius: 16px;

    padding: 20px;

    display: flex;

    align-items: center;

    gap: 15px;
}

.kpi-icon {

    width: 48px;

    height: 48px;

    border-radius: 13px;

    background: #f0efff;

    display: flex;

    align-items: center;

    justify-content: center;

    font-size: 22px;
}

.kpi strong {

    display: block;

    font-size: 25px;

    color: #17172b;
}

.kpi span {

    font-size: 11px;

    color: #85859a;
}


/* FILTER */

.application-filter {

    background: white;

    border: 1px solid #e7e6f0;

    border-radius: 18px;

    padding: 22px;

    margin-bottom: 15px;

    display: flex;

    justify-content: space-between;

    align-items: center;

    gap: 20px;
}

.application-filter h2 {

    font-size: 19px;

    color: #17172b;

    margin-bottom: 5px;
}

.application-filter p {

    font-size: 12px;

    color: #85859a;
}

.application-filter select {

    padding: 10px 14px;

    border: 1px solid #ddddeb;

    border-radius: 9px;

    background: white;

    color: #333;

    cursor: pointer;
}


/* TABLE */

.table-card {

    background: white;

    border: 1px solid #e7e6f0;

    border-radius: 18px;

    overflow: hidden;

    margin-bottom: 22px;
}

.table-responsive {

    overflow-x: auto;
}

.table {

    width: 100%;

    border-collapse: collapse;

    min-width: 950px;
}

.table th {

    background: #fafaff;

    padding: 15px;

    text-align: left;

    font-size: 11px;

    color: #77778c;

    text-transform: uppercase;

    letter-spacing: .5px;

    border-bottom: 1px solid #eeeeF5;
}

.table td {

    padding: 16px 15px;

    border-bottom: 1px solid #eeeeF5;

    font-size: 12px;

    color: #44445a;
}

.table tr:last-child td {

    border-bottom: none;
}


/* APPLICATION NAME */

.application-name {

    display: flex;

    align-items: center;

    gap: 11px;
}

.company-small {

    width: 38px;

    height: 38px;

    border-radius: 10px;

    background: #f0efff;

    color: #635bff;

    display: flex;

    align-items: center;

    justify-content: center;

    font-weight: 700;
}

.application-name strong {

    display: block;

    font-size: 13px;

    color: #29293d;

    margin-bottom: 3px;
}

.application-name small {

    color: #8b8b9d;

    font-size: 10px;
}


/* MATCH */

.match-badge {

    background: #ecfdf3;

    color: #16a34a;

    padding: 5px 9px;

    border-radius: 15px;

    font-size: 11px;

    font-weight: 700;
}


/* STATUS */

.status {

    padding: 6px 10px;

    border-radius: 15px;

    font-size: 10px;

    font-weight: 600;

    white-space: nowrap;
}

.status-shortlisted {

    background: #f0efff;

    color: #635bff;
}

.status-review {

    background: #eff6ff;

    color: #2563eb;
}

.status-selected {

    background: #ecfdf3;

    color: #16a34a;
}

.status-rejected {

    background: #fef2f2;

    color: #dc2626;
}


/* VIEW BUTTON */

.view-btn {

    border: 1px solid #ddd9ff;

    background: #f8f7ff;

    color: #635bff;

    padding: 7px 12px;

    border-radius: 7px;

    cursor: pointer;

    font-size: 11px;

    font-weight: 600;
}

.view-btn:hover {

    background: #635bff;

    color: white;
}


/* EMPTY */

.empty-applications {

    text-align: center;

    padding: 60px 20px;

    color: #77778c;
}

.empty-applications .empty-icon {

    font-size: 45px;

    margin-bottom: 15px;
}

.empty-applications h3 {

    color: #29293d;

    margin-bottom: 7px;
}


/* TIP */

.career-tip {

    background: #ffffff;

    border: 1px solid #e7e6f0;

    border-radius: 18px;

    padding: 22px;

    display: flex;

    align-items: center;

    gap: 15px;
}

.tip-icon {

    width: 45px;

    height: 45px;

    border-radius: 12px;

    background: #fff7e6;

    display: flex;

    align-items: center;

    justify-content: center;

    font-size: 22px;

    flex-shrink: 0;
}

.career-tip h3 {

    font-size: 14px;

    color: #29293d;

    margin-bottom: 5px;
}

.career-tip p {

    font-size: 11px;

    color: #85859a;

    line-height: 1.5;
}

.career-tip .btn {

    margin-left: auto;

    white-space: nowrap;

    text-decoration: none;
}


/* RESPONSIVE */

@media(max-width:1100px) {

    .kpis {

        grid-template-columns:
            repeat(2, 1fr);
    }
}

@media(max-width:700px) {

    .application-hero {

        flex-direction: column;

        align-items: flex-start;
    }

    .hero-icon {

        display: none;
    }

    .application-filter {

        flex-direction: column;

        align-items: flex-start;
    }

    .application-filter select {

        width: 100%;
    }

    .career-tip {

        flex-direction: column;

        align-items: flex-start;
    }

    .career-tip .btn {

        margin-left: 0;
    }
}

@media(max-width:500px) {

    .kpis {

        grid-template-columns: 1fr;
    }

}

</style>

</head>


<body>

<div class="app">


<!-- SIDEBAR -->

<aside class="sidebar">

    <div class="logo">

        <span>SB</span>

        SkillBridge

    </div>


    <div class="side-title">
        STUDENT
    </div>


    <a href="dashboard.php"
       class="side-link">

        🏠 Dashboard

    </a>


    <a href="skill-assessment.php"
       class="side-link">

        📝 Skill Assessment

    </a>


    <a href="skill-profile.php"
       class="side-link">

        👤 Skill Profile

    </a>


    <a href="skill-gap.php"
       class="side-link">

        📊 Skill Gap

    </a>


    <a href="ai-recommendations.php"
       class="side-link">

        🤖 AI Recommendations

    </a>


    <a href="opportunities.php"
       class="side-link">

        💼 Opportunities

    </a>


    <a href="applications.php"
       class="side-link active">

        📋 My Applications

    </a>


    <a href="portfolio.php"
       class="side-link">

        ⭐ My Portfolio

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


<header class="topbar">

    <div>

        <h2>
            My Applications
        </h2>

        <p>
            Track and manage your internship,
            job and project applications.
        </p>

    </div>


    <div class="user-box">

        <div class="avatar">

            <?= strtoupper(substr($userName, 0, 1)) ?>

        </div>

        <div>

            <strong>
                <?= htmlspecialchars($userName) ?>
            </strong>

            <small>
                Student
            </small>

        </div>

    </div>

</header>



<section class="page-content">


<!-- HERO -->

<div class="application-hero">

    <div>

        <span class="hero-label">
            APPLICATION TRACKER
        </span>

        <h1>

            Track Your
            <span>Applications</span>

        </h1>

        <p>

            Keep track of all your applications
            and monitor your career opportunities
            from one place.

        </p>

    </div>


    <div class="hero-icon">
        📋
    </div>

</div>



<!-- STATS -->

<div class="kpis">


<div class="kpi">

    <div class="kpi-icon">
        📋
    </div>

    <div>

        <strong>
            <?= $totalApplications ?>
        </strong>

        <span>
            Total Applications
        </span>

    </div>

</div>


<div class="kpi">

    <div class="kpi-icon">
        🔵
    </div>

    <div>

        <strong>
            <?= $underReview ?>
        </strong>

        <span>
            Under Review
        </span>

    </div>

</div>


<div class="kpi">

    <div class="kpi-icon">
        ⭐
    </div>

    <div>

        <strong>
            <?= $shortlisted ?>
        </strong>

        <span>
            Shortlisted
        </span>

    </div>

</div>


<div class="kpi">

    <div class="kpi-icon">
        🎉
    </div>

    <div>

        <strong>
            <?= $selected ?>
        </strong>

        <span>
            Selected
        </span>

    </div>

</div>


</div>



<!-- FILTER -->

<div class="application-filter">

    <div>

        <h2>
            Application History
        </h2>

        <p>
            View the current status of your applications.
        </p>

    </div>


    <select id="statusFilter"
            onchange="filterApplications()">

        <option value="all">
            All Applications
        </option>

        <option value="Under Review">
            Under Review
        </option>

        <option value="Shortlisted">
            Shortlisted
        </option>

        <option value="Selected">
            Selected
        </option>

        <option value="Rejected">
            Rejected
        </option>

    </select>

</div>



<!-- TABLE -->

<div class="table-card">

<div class="table-responsive">

<table class="table">

<thead>

<tr>

<th>
    Opportunity
</th>

<th>
    Type
</th>

<th>
    Category
</th>

<th>
    Applied On
</th>

<th>
    Status
</th>

<th>
    Action
</th>

</tr>

</thead>


<tbody id="applicationTable">


<?php if (count($applications) > 0): ?>


<?php foreach ($applications as $application): ?>

<?php

$title = $application["title"];

$firstLetter =
    strtoupper(substr($title, 0, 1));

$status =
    $application["status"];

$statusClass = "status-review";

if ($status === "Shortlisted") {
    $statusClass = "status-shortlisted";
}

if ($status === "Selected") {
    $statusClass = "status-selected";
}

if ($status === "Rejected") {
    $statusClass = "status-rejected";
}

$skills =
    $application["skills"] ?? "";

?>

<tr data-status="<?= htmlspecialchars($status) ?>">


<td>

<div class="application-name">

    <div class="company-small">

        <?= htmlspecialchars($firstLetter) ?>

    </div>


    <div>

        <strong>

            <?= htmlspecialchars($title) ?>

        </strong>

        <small>

            <?= htmlspecialchars($skills) ?>

        </small>

    </div>

</div>

</td>


<td>

<?= htmlspecialchars(
    $application["type"] ?? "-"
) ?>

</td>


<td>

<?= htmlspecialchars(
    $application["category"] ?? "-"
) ?>

</td>


<td>

<?= date(
    "d M Y",
    strtotime($application["applied_at"])
) ?>

</td>


<td>

<span class="status <?= $statusClass ?>">

    <?= htmlspecialchars($status) ?>

</span>

</td>


<td>

<button
    class="view-btn"
    onclick="viewApplication(
        <?= (int)$application['id'] ?>
    )">

    View

</button>

</td>


</tr>


<?php endforeach; ?>


<?php else: ?>


<tr>

<td colspan="6">

<div class="empty-applications">

    <div class="empty-icon">
        📋
    </div>

    <h3>
        No Applications Yet
    </h3>

    <p>
        You haven't applied to any opportunities yet.
    </p>

    <br>

    <a href="opportunities.php"
       class="btn btn-primary">

        Explore Opportunities →

    </a>

</div>

</td>

</tr>


<?php endif; ?>


</tbody>

</table>

</div>

</div>



<!-- TIP -->

<div class="career-tip">

    <div class="tip-icon">
        💡
    </div>

    <div>

        <h3>
            Improve Your Chances
        </h3>

        <p>

            Complete your skill assessment,
            improve your skill gaps and apply
            to opportunities that match your profile.

        </p>

    </div>


    <a href="ai-recommendations.php"
       class="btn btn-primary">

        View Recommendations

    </a>

</div>


</section>

</main>

</div>



<script src="script.js"></script>


<script>

function filterApplications() {

    const filter =
        document.getElementById(
            "statusFilter"
        ).value;

    const rows =
        document.querySelectorAll(
            "#applicationTable tr[data-status]"
        );

    rows.forEach(function(row) {

        const status =
            row.getAttribute("data-status");

        if (
            filter === "all" ||
            status === filter
        ) {

            row.style.display = "";

        } else {

            row.style.display = "none";

        }

    });

}


function viewApplication(id) {

    window.location.href =
        "application-details.php?id=" + id;

}

</script>


</body>

</html>
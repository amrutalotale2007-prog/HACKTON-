<?php
session_start();

require_once "config/database.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

/* USER DATA */
$userQuery = $conn->prepare("SELECT name, role FROM users WHERE id = ?");
$userQuery->bind_param("i", $user_id);
$userQuery->execute();
$userResult = $userQuery->get_result();
$user = $userResult->fetch_assoc();

$userName = $user['name'] ?? "User";
$userRole = $user['role'] ?? "academician";

/* COLLABORATION DATA */
$collabQuery = $conn->prepare("
    SELECT 
        c.id,
        c.title,
        c.message,
        c.status,
        c.created_at,
        u.name AS partner_name,
        u.role AS partner_role
    FROM collaborations c
    JOIN users u ON c.receiver_id = u.id
    WHERE c.sender_id = ?
    ORDER BY c.created_at DESC
");

$collabQuery->bind_param("i", $user_id);
$collabQuery->execute();
$collabResult = $collabQuery->get_result();

/* STATS */
$totalCollaborations = 0;
$activeCollaborations = 0;
$completedCollaborations = 0;
$pendingCollaborations = 0;

$collaborations = [];

while ($row = $collabResult->fetch_assoc()) {

    $collaborations[] = $row;

    $totalCollaborations++;

    if ($row['status'] === 'Accepted') {
        $activeCollaborations++;
    }

    if ($row['status'] === 'Completed') {
        $completedCollaborations++;
    }

    if ($row['status'] === 'Pending') {
        $pendingCollaborations++;
    }
}

/* COLLABORATION REQUESTS */
$requestQuery = $conn->prepare("
    SELECT 
        c.id,
        c.title,
        c.message,
        c.status,
        c.created_at,
        u.id AS sender_id,
        u.name AS sender_name,
        u.role AS sender_role
    FROM collaborations c
    JOIN users u ON c.sender_id = u.id
    WHERE c.receiver_id = ?
    AND c.status = 'Pending'
    ORDER BY c.created_at DESC
");

$requestQuery->bind_param("i", $user_id);
$requestQuery->execute();
$requestResult = $requestQuery->get_result();

$requests = [];

while ($row = $requestResult->fetch_assoc()) {
    $requests[] = $row;
}

$requestCount = count($requests);

/* PARTNERS */
$partnerQuery = $conn->prepare("
    SELECT COUNT(DISTINCT 
        CASE 
            WHEN sender_id = ? THEN receiver_id
            ELSE sender_id
        END
    ) AS total
    FROM collaborations
    WHERE sender_id = ? OR receiver_id = ?
");

$partnerQuery->bind_param(
    "iii",
    $user_id,
    $user_id,
    $user_id
);

$partnerQuery->execute();
$partnerResult = $partnerQuery->get_result();
$partnerData = $partnerResult->fetch_assoc();

$industryPartners = $partnerData['total'] ?? 0;

/* SUCCESS RATE */
$successRate = 0;

if ($totalCollaborations > 0) {
    $successRate = round(
        (($activeCollaborations + $completedCollaborations) /
        $totalCollaborations) * 100
    );
}
?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport"
      content="width=device-width, initial-scale=1.0">

<title>Collaboration | SkillBridge</title>

<style>

*{
    box-sizing:border-box;
}

html,
body{
    margin:0;
    padding:0;
    width:100%;
    min-height:100%;
    overflow-x:hidden;
    font-family:Arial, Helvetica, sans-serif;
    background:#f7f7fc;
    color:#29293d;
}

.dashboard-layout{
    width:100%;
    min-height:100vh;
}

/* SIDEBAR */

.sidebar{
    position:fixed;
    left:0;
    top:0;
    bottom:0;

    width:240px;
    height:100vh;

    background:#ffffff;
    border-right:1px solid #e6e6f0;

    padding:26px 18px;

    overflow-y:auto;
    overflow-x:hidden;

    z-index:1000;
}

.logo-area{
    display:flex;
    align-items:center;
    gap:12px;

    margin-bottom:30px;
}

.logo-icon{
    width:42px;
    height:42px;

    background:linear-gradient(
        135deg,
        #635bff,
        #7c3aed
    );

    color:#ffffff;

    border-radius:12px;

    display:flex;
    align-items:center;
    justify-content:center;

    font-size:21px;
    font-weight:800;

    flex-shrink:0;
}

.logo-area h2{
    margin:0;
    font-size:21px;
    color:#202034;
}

.logo-area span{
    display:block;
    margin-top:3px;
    font-size:11px;
    color:#85859a;
}

.side-label{
    font-size:10px;
    font-weight:700;
    letter-spacing:1px;
    color:#9999aa;
    margin:20px 5px 9px;
}

.sidebar-menu{
    display:flex;
    flex-direction:column;
    gap:5px;
}

.sidebar-menu a{
    display:flex;
    align-items:center;
    gap:10px;

    width:100%;

    padding:11px 12px;

    border-radius:9px;

    text-decoration:none;

    color:#555568;

    font-size:13px;

    transition:0.2s;
}

.sidebar-menu a:hover{
    background:#f2f1ff;
    color:#635bff;
}

.sidebar-menu a.active{
    background:#eeeefe;
    color:#635bff;
    font-weight:700;
}

/* MAIN */

.main-content{
    width:calc(100% - 240px);
    min-height:100vh;
    margin-left:240px;
    overflow:hidden;
}

.collab-page{
    width:100%;
    max-width:1400px;
    margin:0 auto;
    padding:28px;
    min-height:100vh;
}

/* HERO */

.collab-hero{
    width:100%;

    background:linear-gradient(
        135deg,
        #635bff,
        #7c3aed
    );

    border-radius:20px;

    padding:30px;

    color:white;

    display:flex;
    justify-content:space-between;
    align-items:center;

    gap:25px;

    margin-bottom:25px;
}

.collab-hero h1{
    margin:0 0 10px;
    font-size:30px;
}

.collab-hero p{
    margin:0;
    max-width:700px;
    line-height:1.6;
    font-size:14px;
    opacity:.93;
}

.hero-btn{
    flex-shrink:0;

    border:0;

    background:white;
    color:#635bff;

    padding:13px 20px;

    border-radius:10px;

    font-size:13px;
    font-weight:700;

    cursor:pointer;
}

.hero-btn:hover{
    transform:translateY(-2px);
}

/* STATS */

.stats-grid{
    width:100%;

    display:grid;

    grid-template-columns:
        repeat(4,minmax(0,1fr));

    gap:16px;

    margin-bottom:30px;
}

.stat-card{
    background:white;

    border:1px solid #e7e7f0;

    border-radius:16px;

    padding:20px;

    min-width:0;
}

.stat-icon{
    width:42px;
    height:42px;

    border-radius:11px;

    background:#f0efff;

    display:flex;
    align-items:center;
    justify-content:center;

    font-size:20px;

    margin-bottom:14px;
}

.stat-card h2{
    margin:0;
    color:#17172b;
    font-size:26px;
}

.stat-card p{
    margin:6px 0 0;
    color:#77778a;
    font-size:13px;
}

/* SECTION */

.section-header{
    margin-bottom:17px;
}

.section-header h2{
    margin:0;
    font-size:21px;
    color:#202034;
}

.section-header p{
    margin:6px 0 0;
    font-size:13px;
    color:#77778a;
}

/* TYPES */

.collab-types{
    display:grid;

    grid-template-columns:
        repeat(4,minmax(0,1fr));

    gap:16px;

    margin-bottom:30px;
}

.type-card{
    background:white;

    border:1px solid #e7e7f0;

    border-radius:16px;

    padding:20px;

    cursor:pointer;

    transition:.25s;
}

.type-card:hover{
    transform:translateY(-3px);

    border-color:#635bff;

    box-shadow:
        0 10px 25px rgba(70,60,140,.08);
}

.type-icon{
    width:48px;
    height:48px;

    background:#f0efff;

    border-radius:12px;

    display:flex;
    align-items:center;
    justify-content:center;

    font-size:24px;

    margin-bottom:14px;
}

.type-card h3{
    margin:0 0 7px;
    font-size:16px;
}

.type-card p{
    margin:0;
    color:#77778a;
    font-size:12px;
    line-height:1.55;
}

/* GRID */

.main-grid{
    display:grid;

    grid-template-columns:
        minmax(0,1.5fr)
        minmax(300px,1fr);

    gap:20px;

    width:100%;
}

.panel{
    background:white;

    border:1px solid #e7e7f0;

    border-radius:17px;

    padding:22px;

    min-width:0;

    overflow:hidden;
}

.panel-title{
    display:flex;

    align-items:center;

    justify-content:space-between;

    gap:10px;

    margin-bottom:18px;
}

.panel-title h3{
    margin:0;
    font-size:17px;
}

.view-all{
    color:#635bff;
    font-size:12px;
    font-weight:700;
}

/* FILTER */

.filters{
    display:flex;
    gap:10px;
    margin-bottom:18px;
}

.search-box{
    flex:1;

    min-width:0;

    padding:11px 13px;

    border:1px solid #dedee8;

    border-radius:9px;

    outline:none;

    font-size:13px;
}

.filter-select{
    width:135px;

    padding:11px;

    border:1px solid #dedee8;

    border-radius:9px;

    background:white;

    color:#555568;

    outline:none;

    font-size:12px;
}

/* COLLAB CARD */

.collab-card{
    border:1px solid #ececf4;

    border-radius:13px;

    padding:17px;

    margin-bottom:12px;
}

.collab-card:last-child{
    margin-bottom:0;
}

.collab-top{
    display:flex;

    justify-content:space-between;

    align-items:flex-start;

    gap:12px;
}

.collab-info{
    display:flex;

    gap:11px;

    min-width:0;
}

.company-icon{
    width:42px;
    height:42px;

    min-width:42px;

    background:#f0efff;

    color:#635bff;

    border-radius:10px;

    display:flex;

    align-items:center;
    justify-content:center;

    font-weight:800;
}

.collab-info h4{
    margin:0 0 4px;

    font-size:14px;
}

.collab-info p{
    margin:0;

    color:#77778a;

    font-size:11px;

    line-height:1.4;
}

.status{
    padding:5px 9px;

    border-radius:20px;

    font-size:10px;

    font-weight:700;
}

.status.active{
    background:#eaf8ef;
    color:#16a34a;
}

.status.completed{
    background:#eeeefe;
    color:#635bff;
}

.status.pending{
    background:#fff5df;
    color:#d97706;
}

.collab-description{
    margin:13px 0;

    color:#666679;

    font-size:12px;

    line-height:1.55;
}

.tags{
    display:flex;

    flex-wrap:wrap;

    gap:6px;
}

.tag{
    background:#f4f4fa;

    color:#5d5d72;

    padding:5px 8px;

    border-radius:6px;

    font-size:10px;
}

.collab-footer{
    display:flex;

    justify-content:space-between;

    align-items:center;

    gap:10px;

    border-top:1px solid #eeeeF4;

    margin-top:13px;

    padding-top:11px;

    color:#77778a;

    font-size:10px;
}

.small-btn{
    border:0;

    background:#635bff;

    color:white;

    padding:7px 12px;

    border-radius:7px;

    font-size:10px;

    cursor:pointer;
}

/* REQUEST */

.request-item{
    padding:14px 0;

    border-bottom:1px solid #eeeeF4;
}

.request-item:last-child{
    border-bottom:0;
}

.request-user{
    display:flex;

    align-items:center;

    gap:10px;
}

.avatar{
    width:38px;
    height:38px;

    min-width:38px;

    border-radius:50%;

    background:#eeeefe;

    color:#635bff;

    display:flex;

    align-items:center;
    justify-content:center;

    font-weight:700;
}

.request-user h4{
    margin:0 0 3px;

    font-size:13px;
}

.request-user p{
    margin:0;

    font-size:10px;

    color:#77778a;
}

.request-actions{
    display:flex;

    gap:7px;

    margin-top:10px;
}

.accept-btn,
.decline-btn{
    padding:7px 11px;

    border-radius:7px;

    font-size:10px;

    cursor:pointer;

    font-weight:600;
}

.accept-btn{
    background:#635bff;
    color:white;
    border:1px solid #635bff;
}

.decline-btn{
    background:white;
    color:#77778a;
    border:1px solid #dddde8;
}

/* MODAL */

.modal{
    position:fixed;

    inset:0;

    background:rgba(20,20,40,.55);

    display:none;

    align-items:center;

    justify-content:center;

    padding:20px;

    z-index:9999;
}

.modal.show{
    display:flex;
}

.modal-box{
    width:100%;

    max-width:600px;

    max-height:90vh;

    overflow-y:auto;

    background:white;

    border-radius:18px;

    padding:25px;
}

.modal-header{
    display:flex;

    justify-content:space-between;

    align-items:center;

    margin-bottom:20px;
}

.modal-header h2{
    margin:0;

    font-size:20px;
}

.close-btn{
    width:34px;
    height:34px;

    border:0;

    border-radius:50%;

    background:#f1f1f6;

    cursor:pointer;

    font-size:18px;
}

.form-group{
    margin-bottom:15px;
}

.form-group label{
    display:block;

    margin-bottom:6px;

    color:#404054;

    font-size:12px;

    font-weight:700;
}

.form-group input,
.form-group textarea,
.form-group select{
    width:100%;

    padding:11px 12px;

    border:1px solid #dddde8;

    border-radius:8px;

    outline:none;

    font-family:Arial,sans-serif;

    font-size:13px;
}

.form-group textarea{
    min-height:100px;

    resize:vertical;
}

.submit-btn{
    width:100%;

    border:0;

    background:#635bff;

    color:white;

    padding:13px;

    border-radius:9px;

    font-weight:700;

    cursor:pointer;
}

/* EMPTY */

.empty{
    text-align:center;

    padding:35px 10px;

    color:#77778a;

    font-size:13px;
}

/* RESPONSIVE */

@media(max-width:1100px){

    .sidebar{
        width:210px;
    }

    .main-content{
        width:calc(100% - 210px);
        margin-left:210px;
    }

    .stats-grid{
        grid-template-columns:repeat(2,minmax(0,1fr));
    }

    .collab-types{
        grid-template-columns:repeat(2,minmax(0,1fr));
    }

    .main-grid{
        grid-template-columns:1fr;
    }
}

@media(max-width:600px){

    .sidebar{
        position:relative;

        width:100%;
        height:auto;

        border-right:0;

        border-bottom:1px solid #e6e6f0;
    }

    .main-content{
        width:100%;
        margin-left:0;
    }

    .collab-page{
        padding:16px;
    }

    .stats-grid,
    .collab-types{
        grid-template-columns:1fr;
    }

    .collab-hero{
        flex-direction:column;
        align-items:flex-start;
    }

    .hero-btn{
        width:100%;
    }

    .filters{
        flex-direction:column;
    }

    .filter-select{
        width:100%;
    }
}

</style>

</head>

<body>

<div class="dashboard-layout">

<!-- SIDEBAR -->

<aside class="sidebar">

    <div class="logo-area">

        <div class="logo-icon">
            S
        </div>

        <div>

            <h2>SkillBridge</h2>

            <span>
                Academia × Industry
            </span>

        </div>

    </div>

    <div class="side-label">
        ACADEMICIAN
    </div>

    <nav class="sidebar-menu">

        <a href="academician-dashboard.php">
            🏠 Dashboard
        </a>

        <a href="research-projects.php">
            🔬 Research & Projects
        </a>

        <a href="collaboration.php"
           class="active">
            🤝 Collaboration
        </a>

        <a href="portfolio.php">
            📁 Portfolio
        </a>

    </nav>

    <div class="side-label">
        PLATFORM
    </div>

    <nav class="sidebar-menu">

        <a href="opportunities.php">
            💼 Opportunities
        </a>

        <a href="index.php">
            🌐 Home
        </a>

        <a href="logout.php">
            🚪 Logout
        </a>

    </nav>

</aside>


<!-- MAIN -->

<main class="main-content">

<div class="collab-page">


<section class="collab-hero">

    <div class="collab-hero-content">

        <h1>
            Build Meaningful Collaborations
        </h1>

        <p>
            Connect academicians, researchers and industry
            experts to create impactful projects, research
            opportunities, workshops and real-world learning
            experiences.
        </p>

    </div>

    <button
        class="hero-btn"
        id="openModal">

        + New Collaboration

    </button>

</section>


<!-- STATS -->

<div class="stats-grid">

    <div class="stat-card">

        <div class="stat-icon">
            🤝
        </div>

        <h2>
            <?= $activeCollaborations ?>
        </h2>

        <p>
            Active Collaborations
        </p>

    </div>


    <div class="stat-card">

        <div class="stat-icon">
            🏢
        </div>

        <h2>
            <?= $industryPartners ?>
        </h2>

        <p>
            Industry Partners
        </p>

    </div>


    <div class="stat-card">

        <div class="stat-icon">
            🎓
        </div>

        <h2>
            <?= $totalCollaborations ?>
        </h2>

        <p>
            Collaborations
        </p>

    </div>


    <div class="stat-card">

        <div class="stat-icon">
            📊
        </div>

        <h2>
            <?= $successRate ?>%
        </h2>

        <p>
            Success Rate
        </p>

    </div>

</div>


<div class="section-header">

    <h2>
        Collaboration Opportunities
    </h2>

    <p>
        Choose a collaboration model that matches your goals.
    </p>

</div>


<!-- TYPES -->

<div class="collab-types">

    <div class="type-card"
         onclick="selectType('Joint Research')">

        <div class="type-icon">
            🔬
        </div>

        <h3>
            Joint Research
        </h3>

        <p>
            Work together on innovative research projects
            and publish industry-relevant findings.
        </p>

    </div>


    <div class="type-card"
         onclick="selectType('Live Project')">

        <div class="type-icon">
            💻
        </div>

        <h3>
            Live Projects
        </h3>

        <p>
            Give students practical experience through
            real-world industry projects.
        </p>

    </div>


    <div class="type-card"
         onclick="selectType('Industry Workshop')">

        <div class="type-icon">
            🎤
        </div>

        <h3>
            Workshops
        </h3>

        <p>
            Conduct technical workshops, seminars and
            industry training sessions.
        </p>

    </div>


    <div class="type-card"
         onclick="selectType('Guest Lecture')">

        <div class="type-icon">
            👨‍🏫
        </div>

        <h3>
            Guest Lectures
        </h3>

        <p>
            Connect industry professionals with students
            through expert sessions.
        </p>

    </div>

</div>


<!-- MAIN GRID -->

<div class="main-grid">


<section class="panel">

    <div class="panel-title">

        <h3>
            Active Collaborations
        </h3>

        <span class="view-all">
            <?= $totalCollaborations ?> Total
        </span>

    </div>


    <div class="filters">

        <input
            type="text"
            id="searchCollab"
            class="search-box"
            placeholder="Search collaborations..."
        >

        <select
            id="collabFilter"
            class="filter-select">

            <option value="all">
                All Types
            </option>

            <option value="research">
                Research
            </option>

            <option value="project">
                Live Project
            </option>

            <option value="workshop">
                Workshop
            </option>

        </select>

    </div>


    <div id="collabList">

<?php if (count($collaborations) > 0): ?>

<?php foreach ($collaborations as $collab): ?>

<?php

$title = htmlspecialchars($collab['title'] ?? 'Untitled Collaboration');

$message = htmlspecialchars(
    $collab['message'] ?? 'No description available.'
);

$partner = htmlspecialchars(
    $collab['partner_name'] ?? 'Organization'
);

$status = $collab['status'] ?? 'Pending';

$type = strtolower($title);

if (strpos($type, 'research') !== false) {
    $dataType = 'research';
}
elseif (
    strpos($type, 'project') !== false ||
    strpos($type, 'development') !== false
) {
    $dataType = 'project';
}
elseif (
    strpos($type, 'workshop') !== false ||
    strpos($type, 'lecture') !== false
) {
    $dataType = 'workshop';
}
else {
    $dataType = 'project';
}

$statusClass = strtolower($status);

?>

<div class="collab-card"
     data-type="<?= $dataType ?>">

    <div class="collab-top">

        <div class="collab-info">

            <div class="company-icon">
                <?= strtoupper(substr($partner,0,1)) ?>
            </div>

            <div>

                <h4>
                    <?= $title ?>
                </h4>

                <p>
                    <?= $partner ?>
                </p>

            </div>

        </div>


        <span class="status <?= $statusClass ?>">

            <?= htmlspecialchars($status) ?>

        </span>

    </div>


    <p class="collab-description">

        <?= $message ?>

    </p>


    <div class="tags">

        <span class="tag">
            Collaboration
        </span>

        <span class="tag">
            <?= ucfirst($dataType) ?>
        </span>

    </div>


    <div class="collab-footer">

        <span>

            📅
            <?= date(
                "d M Y",
                strtotime($collab['created_at'])
            ) ?>

        </span>

        <button
            class="small-btn"
            onclick="viewCollaboration(
                <?= (int)$collab['id'] ?>
            )">

            View

        </button>

    </div>

</div>

<?php endforeach; ?>

<?php else: ?>

<div class="empty">

    🤝 No collaborations yet.<br><br>

    Click <b>+ New Collaboration</b>
    to create your first collaboration.

</div>

<?php endif; ?>

    </div>

</section>


<!-- REQUESTS -->

<section class="panel">

    <div class="panel-title">

        <h3>
            Collaboration Requests
        </h3>

        <span class="view-all">
            <?= $requestCount ?> New
        </span>

    </div>


<?php if ($requestCount > 0): ?>

<?php foreach ($requests as $request): ?>

<?php

$senderName =
    htmlspecialchars($request['sender_name']);

$senderRole =
    htmlspecialchars($request['sender_role']);

$requestTitle =
    htmlspecialchars($request['title']);

$senderInitial =
    strtoupper(
        substr($request['sender_name'],0,1)
    );

?>

<div class="request-item">

    <div class="request-user">

        <div class="avatar">

            <?= $senderInitial ?>

        </div>

        <div>

            <h4>
                <?= $senderName ?>
            </h4>

            <p>
                <?= $requestTitle ?>
                •
                <?= $senderRole ?>
            </p>

        </div>

    </div>


    <div class="request-actions">

        <button
            class="accept-btn"
            onclick="updateRequest(
                <?= (int)$request['id'] ?>,
                'Accepted',
                this
            )">

            Accept

        </button>


        <button
            class="decline-btn"
            onclick="updateRequest(
                <?= (int)$request['id'] ?>,
                'Rejected',
                this
            )">

            Decline

        </button>

    </div>

</div>

<?php endforeach; ?>

<?php else: ?>

<div class="empty">

    📭 No new collaboration requests.

</div>

<?php endif; ?>

</section>

</div>

</div>

</main>

</div>


<!-- MODAL -->

<div
    class="modal"
    id="collabModal">

    <div class="modal-box">

        <div class="modal-header">

            <h2>
                New Collaboration Request
            </h2>

            <button
                class="close-btn"
                id="closeModal">

                ×

            </button>

        </div>


        <form
            id="collabForm"
            method="POST"
            action="create-collaboration.php">


            <div class="form-group">

                <label>
                    Collaboration Type
                </label>

                <select
                    name="type"
                    id="collabType"
                    required>

                    <option value="">
                        Select type
                    </option>

                    <option value="Joint Research">
                        Joint Research
                    </option>

                    <option value="Live Project">
                        Live Project
                    </option>

                    <option value="Industry Workshop">
                        Industry Workshop
                    </option>

                    <option value="Guest Lecture">
                        Guest Lecture
                    </option>

                    <option value="Consultancy">
                        Consultancy
                    </option>

                    <option value="Internship Program">
                        Internship Program
                    </option>

                </select>

            </div>


            <div class="form-group">

                <label>
                    Receiver User ID
                </label>

                <input
                    type="number"
                    name="receiver_id"
                    placeholder="Enter user ID"
                    min="1"
                    required>

            </div>


            <div class="form-group">

                <label>
                    Project / Collaboration Title
                </label>

                <input
                    type="text"
                    name="title"
                    placeholder="Enter collaboration title"
                    required>

            </div>


            <div class="form-group">

                <label>
                    Description
                </label>

                <textarea
                    name="message"
                    placeholder="Describe collaboration objectives..."
                    required></textarea>

            </div>


            <button
                type="submit"
                class="submit-btn">

                Send Collaboration Request

            </button>

        </form>

    </div>

</div>


<script>

/* MODAL */

const modal =
    document.getElementById("collabModal");

const openModal =
    document.getElementById("openModal");

const closeModal =
    document.getElementById("closeModal");


openModal.addEventListener(
    "click",
    function(){

        modal.classList.add("show");

    }
);


closeModal.addEventListener(
    "click",
    function(){

        modal.classList.remove("show");

    }
);


window.addEventListener(
    "click",
    function(e){

        if(e.target === modal){

            modal.classList.remove("show");

        }

    }
);


/* SELECT TYPE */

function selectType(type){

    modal.classList.add("show");

    document.getElementById(
        "collabType"
    ).value = type;

}


/* VIEW */

function viewCollaboration(id){

    window.location.href =
        "collaboration-details.php?id=" + id;

}


/* ACCEPT / DECLINE */

function updateRequest(id,status,button){

    if(status === "Rejected"){

        if(!confirm(
            "Are you sure you want to decline this request?"
        )){
            return;
        }

    }

    fetch("update-collaboration.php",{

        method:"POST",

        headers:{
            "Content-Type":
                "application/x-www-form-urlencoded"
        },

        body:
            "id=" +
            encodeURIComponent(id) +
            "&status=" +
            encodeURIComponent(status)

    })
    .then(response => response.text())
    .then(data => {

        if(data.trim() === "success"){

            const item =
                button.closest(".request-item");

            item.style.opacity = "0.5";

            button.innerText =
                status === "Accepted"
                ? "Accepted"
                : "Declined";

            button.disabled = true;

        }
        else{

            alert(data);

        }

    })
    .catch(error => {

        alert(
            "Something went wrong."
        );

    });

}


/* SEARCH */

const searchInput =
    document.getElementById(
        "searchCollab"
    );

const filter =
    document.getElementById(
        "collabFilter"
    );


function filterCollaborations(){

    const search =
        searchInput.value
        .toLowerCase()
        .trim();

    const selectedType =
        filter.value;


    document
    .querySelectorAll(".collab-card")
    .forEach(function(card){

        const text =
            card.innerText.toLowerCase();

        const type =
            card.dataset.type;

        const searchMatch =
            text.includes(search);

        const typeMatch =
            selectedType === "all" ||
            type === selectedType;


        card.style.display =
            searchMatch && typeMatch
            ? "block"
            : "none";

    });

}


searchInput.addEventListener(
    "input",
    filterCollaborations
);

filter.addEventListener(
    "change",
    filterCollaborations
);

</script>

</body>
</html>
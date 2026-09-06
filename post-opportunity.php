<?php

session_start();

require_once "config/database.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

$message = "";
$message_type = "";


// =========================
// GET CURRENT USER
// =========================

$stmt = $conn->prepare("
    SELECT name, email, role
    FROM users
    WHERE id = ?
");

$stmt->bind_param("i", $user_id);
$stmt->execute();

$user = $stmt->get_result()->fetch_assoc();

if (!$user) {
    session_destroy();
    header("Location: login.php");
    exit();
}

$user_name = $user['name'];
$user_role = $user['role'];

$initial = strtoupper(substr($user_name, 0, 1));


// =========================
// FORM SUBMISSION
// =========================

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $opportunity_type = trim($_POST['opportunity_type'] ?? "");
    $title = trim($_POST['title'] ?? "");
    $category = trim($_POST['category'] ?? "");
    $work_mode = trim($_POST['work_mode'] ?? "");
    $description = trim($_POST['description'] ?? "");
    $skills = trim($_POST['skills'] ?? "");
    $location = trim($_POST['location'] ?? "");
    $openings = intval($_POST['openings'] ?? 1);
    $duration = trim($_POST['duration'] ?? "");
    $deadline = trim($_POST['deadline'] ?? "");
    $stipend = trim($_POST['stipend'] ?? "");
    $experience = trim($_POST['experience'] ?? "");
    $eligibility = trim($_POST['eligibility'] ?? "");

    $benefits_array = $_POST['benefits'] ?? [];

    if (is_array($benefits_array)) {
        $benefits = implode(", ", $benefits_array);
    } else {
        $benefits = "";
    }


    // =========================
    // VALIDATION
    // =========================

    if (
        empty($opportunity_type) ||
        empty($title) ||
        empty($category) ||
        empty($work_mode) ||
        empty($description) ||
        empty($skills) ||
        empty($location) ||
        empty($deadline) ||
        $openings < 1
    ) {

        $message = "Please fill all required fields.";
        $message_type = "error";

    } elseif (strtotime($deadline) < strtotime(date("Y-m-d"))) {

        $message = "Application deadline cannot be in the past.";
        $message_type = "error";

    } else {

        // =========================
        // INSERT OPPORTUNITY
        // =========================

        $stmt = $conn->prepare("
            INSERT INTO opportunities
            (
                user_id,
                title,
                type,
                category,
                work_mode,
                description,
                skills,
                location,
                openings,
                duration,
                stipend,
                experience,
                eligibility,
                benefits,
                deadline
            )
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->bind_param(
            "issssssisssssss",
            $user_id,
            $title,
            $opportunity_type,
            $category,
            $work_mode,
            $description,
            $skills,
            $location,
            $openings,
            $duration,
            $stipend,
            $experience,
            $eligibility,
            $benefits,
            $deadline
        );


        if ($stmt->execute()) {

            $message = "Opportunity published successfully!";
            $message_type = "success";

            // Clear submitted values
            $_POST = [];

        } else {

            $message = "Failed to publish opportunity. Please try again.";
            $message_type = "error";
        }

    }
}

?>

<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport"
      content="width=device-width, initial-scale=1.0">

<title>Post Opportunity | SkillBridge</title>

<link rel="stylesheet" href="style.css">

<link rel="preconnect"
      href="https://fonts.googleapis.com">

<link rel="preconnect"
      href="https://fonts.gstatic.com"
      crossorigin>

<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap"
      rel="stylesheet">


<style>

* {
    box-sizing: border-box;
}

body {
    margin: 0;
    font-family: Inter, Arial, sans-serif;
    background: #f7f7fc;
    color: #29293d;
}


/* =========================
   LAYOUT
========================= */

.app {
    display: flex;
    min-height: 100vh;
}


/* =========================
   SIDEBAR
========================= */

.sidebar {
    position: fixed;
    left: 0;
    top: 0;
    bottom: 0;

    width: 250px;

    background: white;
    border-right: 1px solid #e8e8f2;

    padding: 24px 18px;

    overflow-y: auto;
    z-index: 1000;
}

.sidebar-logo {
    display: flex;
    align-items: center;
    gap: 12px;

    margin-bottom: 30px;
}

.sidebar-logo h2 {
    margin: 0;
    font-size: 20px;
}

.logo-icon {
    width: 42px;
    height: 42px;

    background: linear-gradient(
        135deg,
        #635bff,
        #7c3aed
    );

    color: white;

    border-radius: 11px;

    display: flex;
    align-items: center;
    justify-content: center;

    font-weight: 700;
    font-size: 20px;
}


.sidebar-user {
    display: flex;
    align-items: center;
    gap: 10px;

    padding: 12px;

    background: #f7f7fc;
    border-radius: 12px;

    margin-bottom: 22px;
}

.company-avatar {
    width: 40px;
    height: 40px;

    background: linear-gradient(
        135deg,
        #635bff,
        #7c3aed
    );

    color: white;

    border-radius: 10px;

    display: flex;
    align-items: center;
    justify-content: center;

    font-weight: 700;
}

.sidebar-user h4 {
    margin: 0 0 3px;
    font-size: 12px;
}

.sidebar-user p {
    margin: 0;
    color: #888899;
    font-size: 10px;
}


.sidebar-menu {
    display: flex;
    flex-direction: column;
    gap: 5px;
}

.side-link {
    display: flex;
    align-items: center;
    gap: 10px;

    padding: 11px 12px;

    text-decoration: none;

    color: #55556a;

    border-radius: 9px;

    font-size: 13px;
}

.side-link:hover {
    background: #f3f2ff;
    color: #635bff;
}

.side-link.active {
    background: #eeeefe;
    color: #635bff;
    font-weight: 700;
}

.sidebar-bottom {
    margin-top: 30px;
}

.logout-link {
    color: #dc2626;
}


/* =========================
   MAIN
========================= */

.main {
    margin-left: 250px;

    width: calc(100% - 250px);

    padding: 28px;
}


/* =========================
   TOPBAR
========================= */

.topbar {
    display: flex;
    justify-content: space-between;
    align-items: center;

    margin-bottom: 25px;
}

.topbar h2 {
    margin: 0 0 5px;
    font-size: 22px;
}

.topbar p {
    margin: 0;

    color: #77778a;
    font-size: 13px;
}

.topbar-right {
    display: flex;
    align-items: center;
    gap: 15px;
}

.notification-btn {
    position: relative;

    width: 40px;
    height: 40px;

    border: 1px solid #e8e8f2;

    background: white;

    border-radius: 10px;

    cursor: pointer;
}

.notification-dot {
    position: absolute;

    top: 8px;
    right: 8px;

    width: 7px;
    height: 7px;

    border-radius: 50%;

    background: #dc2626;
}

.top-profile {
    display: flex;
    align-items: center;
    gap: 9px;
}

.profile-circle {
    width: 40px;
    height: 40px;

    border-radius: 50%;

    background: linear-gradient(
        135deg,
        #635bff,
        #7c3aed
    );

    color: white;

    display: flex;
    align-items: center;
    justify-content: center;

    font-weight: 700;
}

.top-profile strong {
    display: block;
    font-size: 12px;
}

.top-profile small {
    color: #888899;
    font-size: 10px;
}


/* =========================
   HEADER
========================= */

.post-header {
    background: linear-gradient(
        135deg,
        #635bff,
        #7c3aed
    );

    border-radius: 20px;

    padding: 28px;

    color: white;

    display: flex;
    justify-content: space-between;
    align-items: center;

    margin-bottom: 24px;
}

.page-badge {
    display: inline-block;

    padding: 6px 10px;

    background: rgba(255,255,255,.15);

    border-radius: 7px;

    font-size: 10px;
    font-weight: 700;
}

.post-header h1 {
    margin: 10px 0 8px;

    font-size: 28px;
}

.post-header p {
    margin: 0;

    max-width: 650px;

    line-height: 1.6;

    font-size: 13px;

    opacity: .92;
}

.post-header-icon {
    font-size: 60px;
}


/* =========================
   LAYOUT
========================= */

.post-layout {
    display: grid;

    grid-template-columns:
        minmax(0, 1fr)
        320px;

    gap: 22px;
}


/* =========================
   FORM CARD
========================= */

.post-form-card {
    background: white;

    border: 1px solid #e8e8f2;

    border-radius: 18px;

    padding: 25px;
}

.form-heading {
    margin-bottom: 25px;
}

.form-heading h2 {
    margin: 0 0 5px;

    font-size: 20px;
}

.form-heading p {
    margin: 0;

    color: #77778a;

    font-size: 12px;
}


/* =========================
   FORM
========================= */

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;

    margin-bottom: 8px;

    font-size: 12px;

    font-weight: 600;

    color: #29293d;
}

.form-row {
    display: grid;

    grid-template-columns: 1fr 1fr;

    gap: 16px;
}

input,
select,
textarea {
    width: 100%;

    padding: 11px 12px;

    border: 1px solid #ddddE8;

    border-radius: 9px;

    background: white;

    color: #29293d;

    font-family: inherit;

    font-size: 12px;

    outline: none;
}

input:focus,
select:focus,
textarea:focus {
    border-color: #635bff;

    box-shadow:
        0 0 0 3px rgba(99,91,255,.08);
}

textarea {
    resize: vertical;
}

.character-count {
    text-align: right;

    margin-top: 5px;

    color: #9999aa;

    font-size: 10px;
}


/* =========================
   OPPORTUNITY TYPES
========================= */

.opportunity-type-grid {
    display: grid;

    grid-template-columns:
        repeat(3, 1fr);

    gap: 12px;
}

.type-option {
    display: flex;

    align-items: center;

    gap: 10px;

    padding: 14px;

    border: 1px solid #e5e5ef;

    border-radius: 12px;

    cursor: pointer;

    transition: .2s;
}

.type-option:hover {
    border-color: #635bff;
}

.type-option.active-type {
    background: #f4f2ff;

    border-color: #635bff;
}

.type-option input {
    width: auto;
}

.type-icon {
    font-size: 23px;
}

.type-option strong {
    display: block;

    font-size: 12px;
}

.type-option small {
    display: block;

    margin-top: 3px;

    color: #888899;

    font-size: 9px;
}


/* =========================
   SKILLS
========================= */

.skill-input-box {
    display: flex;

    gap: 8px;
}

.add-skill-btn {
    padding: 10px 15px;

    border: none;

    background: #635bff;

    color: white;

    border-radius: 8px;

    cursor: pointer;

    font-weight: 600;
}

.selected-skills {
    display: flex;

    flex-wrap: wrap;

    gap: 7px;

    margin-top: 10px;
}

.selected-skill {
    display: inline-flex;

    align-items: center;

    gap: 6px;

    padding: 6px 9px;

    background: #f0efff;

    color: #635bff;

    border-radius: 7px;

    font-size: 10px;

    font-weight: 600;
}

.selected-skill button {
    border: none;

    background: transparent;

    color: #635bff;

    cursor: pointer;

    font-size: 14px;

    padding: 0;
}


/* =========================
   CHECKBOX
========================= */

.checkbox-grid {
    display: grid;

    grid-template-columns:
        repeat(2, 1fr);

    gap: 10px;
}

.checkbox-grid label {
    display: flex;

    align-items: center;

    gap: 7px;

    padding: 10px;

    background: #fafaff;

    border: 1px solid #eeeeF4;

    border-radius: 8px;

    font-size: 11px;

    cursor: pointer;
}

.checkbox-grid input {
    width: auto;
}


/* =========================
   BUTTONS
========================= */

.form-actions {
    display: flex;

    justify-content: flex-end;

    gap: 10px;

    padding-top: 15px;

    border-top: 1px solid #eeeeF4;
}

.draft-btn,
.publish-btn {
    padding: 11px 18px;

    border-radius: 8px;

    cursor: pointer;

    font-weight: 700;

    font-size: 12px;
}

.draft-btn {
    background: white;

    color: #55556a;

    border: 1px solid #ddddE8;
}

.publish-btn {
    background: #635bff;

    color: white;

    border: none;
}

.publish-btn:hover {
    background: #5048e5;
}


/* =========================
   MESSAGE
========================= */

.alert {
    padding: 13px 15px;

    border-radius: 10px;

    margin-bottom: 18px;

    font-size: 12px;

    font-weight: 600;
}

.alert-success {
    background: #eaf8ef;

    color: #15803d;

    border: 1px solid #bbf7d0;
}

.alert-error {
    background: #fef2f2;

    color: #dc2626;

    border: 1px solid #fecaca;
}


/* =========================
   SIDE CARDS
========================= */

.post-side-column {
    display: flex;

    flex-direction: column;

    gap: 18px;
}

.side-info-card,
.matching-info-card {
    background: white;

    border: 1px solid #e8e8f2;

    border-radius: 16px;

    padding: 20px;
}

.side-card-icon {
    width: 40px;
    height: 40px;

    display: flex;
    align-items: center;
    justify-content: center;

    background: #eaf8ef;

    color: #16a34a;

    border-radius: 10px;

    font-weight: 700;

    margin-bottom: 12px;
}

.side-info-card h3,
.matching-info-card h3 {
    margin: 0 0 7px;

    font-size: 15px;
}

.side-info-card > p,
.matching-info-card > p {
    margin: 0;

    color: #77778a;

    font-size: 11px;

    line-height: 1.5;
}

.completion-bar {
    margin-top: 18px;
}

.completion-top {
    display: flex;

    justify-content: space-between;

    margin-bottom: 7px;

    font-size: 10px;
}

.completion-top strong {
    color: #635bff;
}

.progress-track {
    width: 100%;

    height: 7px;

    background: #eeeeF5;

    border-radius: 20px;

    overflow: hidden;
}

.progress-fill {
    height: 100%;

    background: linear-gradient(
        90deg,
        #635bff,
        #7c3aed
    );

    border-radius: 20px;
}

.tips-title {
    font-size: 14px;

    font-weight: 700;

    margin-bottom: 15px;
}

.posting-tip {
    display: flex;

    gap: 10px;

    margin-bottom: 13px;
}

.posting-tip > span {
    width: 22px;
    height: 22px;

    min-width: 22px;

    border-radius: 50%;

    background: #f0efff;

    color: #635bff;

    display: flex;

    align-items: center;
    justify-content: center;

    font-size: 10px;

    font-weight: 700;
}

.posting-tip p {
    margin: 0;

    color: #77778a;

    font-size: 10px;

    line-height: 1.5;
}

.matching-info-card {
    background: linear-gradient(
        135deg,
        #f0efff,
        #faf8ff
    );

    border-color: #ddd8ff;
}

.matching-icon {
    font-size: 25px;

    margin-bottom: 8px;
}

.matching-feature {
    margin-top: 10px;

    font-size: 10px;

    font-weight: 600;

    color: #635bff;
}


/* =========================
   RESPONSIVE
========================= */

@media (max-width: 1050px) {

    .post-layout {
        grid-template-columns: 1fr;
    }

    .post-side-column {
        display: grid;

        grid-template-columns:
            repeat(3, 1fr);
    }

}

@media (max-width: 800px) {

    .sidebar {
        width: 210px;
    }

    .main {
        margin-left: 210px;

        width: calc(100% - 210px);

        padding: 18px;
    }

    .opportunity-type-grid {
        grid-template-columns: 1fr;
    }

    .post-side-column {
        grid-template-columns: 1fr;
    }

    .post-header {
        align-items: flex-start;
    }

    .post-header-icon {
        display: none;
    }
}

@media (max-width: 600px) {

    .sidebar {
        width: 180px;
    }

    .main {
        margin-left: 180px;

        width: calc(100% - 180px);

        padding: 12px;
    }

    .form-row {
        grid-template-columns: 1fr;
    }

    .checkbox-grid {
        grid-template-columns: 1fr;
    }

    .topbar-right {
        display: none;
    }

    .post-header h1 {
        font-size: 22px;
    }

    .post-form-card {
        padding: 17px;
    }
}

</style>

</head>


<body>


<div class="app">


<!-- =========================
     SIDEBAR
========================= -->

<aside class="sidebar">

    <div class="sidebar-logo">

        <div class="logo-icon">
            S
        </div>

        <h2>
            SkillBridge
        </h2>

    </div>


    <div class="sidebar-user">

        <div class="company-avatar">
            <?= htmlspecialchars($initial) ?>
        </div>

        <div>

            <h4>
                <?= htmlspecialchars($user_name) ?>
            </h4>

            <p>
                <?= htmlspecialchars(ucfirst($user_role)) ?>
            </p>

        </div>

    </div>


    <nav class="sidebar-menu">

        <a
            href="industry-dashboard.php"
            class="side-link">

            <span>⌂</span>

            Dashboard

        </a>


        <a
            href="post-opportunity.php"
            class="side-link active">

            <span>＋</span>

            Post Opportunity

        </a>


        <a
            href="candidate-matching.php"
            class="side-link">

            <span>◎</span>

            Candidate Matching

        </a>


        <a
            href="applications.php"
            class="side-link">

            <span>▤</span>

            Applications

        </a>


        <a
            href="collaboration.php"
            class="side-link">

            <span>◇</span>

            Collaboration

        </a>


        <a
            href="company-profile.php"
            class="side-link">

            <span>⚙</span>

            Company Profile

        </a>

    </nav>


    <div class="sidebar-bottom">

        <a
            href="logout.php"
            class="side-link logout-link">

            <span>↪</span>

            Logout

        </a>

    </div>

</aside>


<!-- =========================
     MAIN
========================= -->

<main class="main">


<!-- TOPBAR -->

<div class="topbar">

    <div>

        <h2>
            Post Opportunity
        </h2>

        <p>
            Create a new opportunity and connect with talented students.
        </p>

    </div>


    <div class="topbar-right">

        <button
            class="notification-btn">

            🔔

            <span class="notification-dot"></span>

        </button>


        <div class="top-profile">

            <div class="profile-circle">

                <?= htmlspecialchars($initial) ?>

            </div>

            <div>

                <strong>
                    <?= htmlspecialchars($user_name) ?>
                </strong>

                <small>
                    <?= htmlspecialchars(ucfirst($user_role)) ?>
                </small>

            </div>

        </div>

    </div>

</div>


<?php if (!empty($message)): ?>

    <div class="alert
        <?= $message_type === 'success'
            ? 'alert-success'
            : 'alert-error'
        ?>">

        <?= htmlspecialchars($message) ?>

    </div>

<?php endif; ?>


<!-- HEADER -->

<section class="post-header">

    <div>

        <span class="page-badge">
            New Opportunity
        </span>

        <h1>
            Find the Right Talent
        </h1>

        <p>
            Create internships, jobs or live projects and connect
            with students whose skills match your requirements.
        </p>

    </div>

    <div class="post-header-icon">
        💼
    </div>

</section>


<!-- CONTENT -->

<div class="post-layout">


<!-- =========================
     FORM
========================= -->

<section class="post-form-card">


<div class="form-heading">

    <h2>
        Opportunity Details
    </h2>

    <p>
        Enter complete details about your opportunity.
    </p>

</div>


<form method="POST"
      action="post-opportunity.php"
      id="opportunityForm">


<!-- TYPE -->

<div class="form-group">

<label>
    Opportunity Type *
</label>


<div class="opportunity-type-grid">


<label class="type-option active-type">

<input
    type="radio"
    name="opportunity_type"
    value="Internship"
    checked>

<div class="type-icon">
    🎓
</div>

<div>

<strong>
    Internship
</strong>

<small>
    Offer industry experience
</small>

</div>

</label>


<label class="type-option">

<input
    type="radio"
    name="opportunity_type"
    value="Job">

<div class="type-icon">
    💼
</div>

<div>

<strong>
    Job
</strong>

<small>
    Hire full-time employees
</small>

</div>

</label>


<label class="type-option">

<input
    type="radio"
    name="opportunity_type"
    value="Live Project">

<div class="type-icon">
    🚀
</div>

<div>

<strong>
    Live Project
</strong>

<small>
    Work with student teams
</small>

</div>

</label>


</div>

</div>


<!-- TITLE -->

<div class="form-group">

<label for="title">
    Opportunity Title *
</label>

<input
    type="text"
    name="title"
    id="title"
    maxlength="200"
    placeholder="e.g. Frontend Developer Intern"
    value="<?= htmlspecialchars($_POST['title'] ?? '') ?>"
    required>

</div>


<!-- CATEGORY + MODE -->

<div class="form-row">


<div class="form-group">

<label for="category">
    Category *
</label>

<select
    name="category"
    id="category"
    required>

<option value="">
    Select category
</option>

<?php

$categories = [
    "Software Development",
    "Web Development",
    "Data Science",
    "Artificial Intelligence",
    "Cyber Security",
    "Cloud Computing",
    "UI / UX Design",
    "Digital Marketing",
    "Business Analytics"
];

foreach ($categories as $cat):

?>

<option
    value="<?= htmlspecialchars($cat) ?>"
    <?= (($_POST['category'] ?? '') === $cat)
        ? 'selected'
        : ''
    ?>>

    <?= htmlspecialchars($cat) ?>

</option>

<?php endforeach; ?>

</select>

</div>


<div class="form-group">

<label for="work_mode">
    Work Mode *
</label>

<select
    name="work_mode"
    id="work_mode"
    required>

<option value="">
    Select work mode
</option>

<?php

$modes = [
    "Remote",
    "On-site",
    "Hybrid"
];

foreach ($modes as $mode):

?>

<option
    value="<?= htmlspecialchars($mode) ?>"
    <?= (($_POST['work_mode'] ?? '') === $mode)
        ? 'selected'
        : ''
    ?>>

    <?= htmlspecialchars($mode) ?>

</option>

<?php endforeach; ?>

</select>

</div>

</div>


<!-- DESCRIPTION -->

<div class="form-group">

<label for="description">
    Opportunity Description *
</label>

<textarea
    name="description"
    id="description"
    rows="6"
    maxlength="1000"
    placeholder="Describe the role, responsibilities and what the candidate will work on..."
    required><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>

<div class="character-count">

<span id="charCount">
    <?= strlen($_POST['description'] ?? '') ?>
</span>/1000 characters

</div>

</div>


<!-- SKILLS -->

<div class="form-group">

<label>
    Required Skills *
</label>


<div class="skill-input-box">

<input
    type="text"
    id="skillInput"
    placeholder="Enter skill e.g. Python">

<button
    type="button"
    class="add-skill-btn"
    onclick="addSkill()">

    + Add

</button>

</div>


<div
    class="selected-skills"
    id="skillList">

<?php

$old_skills = $_POST['skills'] ?? "";

if (!empty($old_skills)):

    $skill_array = explode(",", $old_skills);

    foreach ($skill_array as $skill):

        $skill = trim($skill);

        if (!empty($skill)):

?>

<span class="selected-skill">

    <?= htmlspecialchars($skill) ?>

    <button
        type="button"
        onclick="removeSkill(this)">

        ×

    </button>

</span>

<?php

        endif;

    endforeach;

endif;

?>

</div>


<input
    type="hidden"
    name="skills"
    id="skillsHidden"
    value="<?= htmlspecialchars($old_skills) ?>"
    required>

</div>


<!-- LOCATION + OPENINGS -->

<div class="form-row">


<div class="form-group">

<label for="location">
    Location *
</label>

<input
    type="text"
    name="location"
    id="location"
    placeholder="e.g. Mumbai, Maharashtra"
    value="<?= htmlspecialchars($_POST['location'] ?? '') ?>"
    required>

</div>


<div class="form-group">

<label for="openings">
    Number of Openings *
</label>

<input
    type="number"
    name="openings"
    id="openings"
    min="1"
    value="<?= htmlspecialchars($_POST['openings'] ?? '') ?>"
    placeholder="e.g. 5"
    required>

</div>

</div>


<!-- DURATION + DEADLINE -->

<div class="form-row">


<div class="form-group">

<label for="duration">
    Duration
</label>

<select
    name="duration"
    id="duration">

<option value="">
    Select duration
</option>

<?php

$durations = [
    "1 Month",
    "2 Months",
    "3 Months",
    "6 Months",
    "12 Months",
    "Permanent"
];

foreach ($durations as $duration):

?>

<option
    value="<?= htmlspecialchars($duration) ?>"
    <?= (($_POST['duration'] ?? '') === $duration)
        ? 'selected'
        : ''
    ?>>

    <?= htmlspecialchars($duration) ?>

</option>

<?php endforeach; ?>

</select>

</div>


<div class="form-group">

<label for="deadline">
    Application Deadline *
</label>

<input
    type="date"
    name="deadline"
    id="deadline"
    min="<?= date('Y-m-d') ?>"
    value="<?= htmlspecialchars($_POST['deadline'] ?? '') ?>"
    required>

</div>

</div>


<!-- STIPEND + EXPERIENCE -->

<div class="form-row">


<div class="form-group">

<label for="stipend">
    Stipend / Salary
</label>

<input
    type="text"
    name="stipend"
    id="stipend"
    placeholder="e.g. ₹15,000/month"
    value="<?= htmlspecialchars($_POST['stipend'] ?? '') ?>">

</div>


<div class="form-group">

<label for="experience">
    Experience Required
</label>

<select
    name="experience"
    id="experience">

<?php

$experiences = [
    "Fresher",
    "0 - 1 Year",
    "1 - 2 Years",
    "2 - 3 Years",
    "3+ Years"
];

foreach ($experiences as $experience):

?>

<option
    value="<?= htmlspecialchars($experience) ?>"
    <?= (($_POST['experience'] ?? 'Fresher') === $experience)
        ? 'selected'
        : ''
    ?>>

    <?= htmlspecialchars($experience) ?>

</option>

<?php endforeach; ?>

</select>

</div>

</div>


<!-- ELIGIBILITY -->

<div class="form-group">

<label for="eligibility">
    Eligibility Criteria
</label>

<textarea
    name="eligibility"
    id="eligibility"
    rows="4"
    placeholder="Mention eligibility criteria..."><?= htmlspecialchars($_POST['eligibility'] ?? '') ?></textarea>

</div>


<!-- BENEFITS -->

<div class="form-group">

<label>
    Benefits
</label>


<div class="checkbox-grid">

<?php

$benefit_options = [
    "Certificate",
    "Letter of Recommendation",
    "Flexible Hours",
    "Pre-placement Offer",
    "Mentorship",
    "Training"
];

$selected_benefits = $_POST['benefits'] ?? [];

foreach ($benefit_options as $benefit):

?>

<label>

<input
    type="checkbox"
    name="benefits[]"
    value="<?= htmlspecialchars($benefit) ?>"
    <?= in_array($benefit, $selected_benefits)
        ? 'checked'
        : ''
    ?>>

<?= htmlspecialchars($benefit) ?>

</label>

<?php endforeach; ?>

</div>

</div>


<!-- ACTIONS -->

<div class="form-actions">

<button
    type="reset"
    class="draft-btn">

    Clear Form

</button>


<button
    type="submit"
    class="publish-btn">

    Publish Opportunity

</button>

</div>


</form>

</section>


<!-- =========================
     RIGHT COLUMN
========================= -->

<aside class="post-side-column">


<div class="side-info-card">

<div class="side-card-icon">
    ✓
</div>

<h3>
    Complete Your Posting
</h3>

<p>
    Detailed opportunity information helps us
    recommend the best candidates.
</p>

<div class="completion-bar">

<div class="completion-top">

<span>
    Posting quality
</span>

<strong>
    100%
</strong>

</div>

<div class="progress-track">

<div
    class="progress-fill"
    style="width:100%;">
</div>

</div>

</div>

</div>


<div class="side-info-card">

<div class="tips-title">
    💡 Posting Tips
</div>


<div class="posting-tip">

<span>1</span>

<p>
Use a clear and specific opportunity title.
</p>

</div>


<div class="posting-tip">

<span>2</span>

<p>
Mention only skills actually required for the role.
</p>

</div>


<div class="posting-tip">

<span>3</span>

<p>
Clearly explain responsibilities and expected outcomes.
</p>

</div>


<div class="posting-tip">

<span>4</span>

<p>
Adding stipend and benefits attracts more candidates.
</p>

</div>

</div>


<div class="matching-info-card">

<div class="matching-icon">
    ✨
</div>

<h3>
    AI Candidate Matching
</h3>

<p>
    Once published, SkillBridge can compare required
    skills with student profiles.
</p>


<div class="matching-feature">
    ✓ Skill Match Score
</div>

<div class="matching-feature">
    ✓ Candidate Ranking
</div>

<div class="matching-feature">
    ✓ Recommended Students
</div>

</div>


</aside>


</div>

</main>

</div>


<script src="script.js"></script>


<script>


// =========================
// DESCRIPTION COUNTER
// =========================

const description =
    document.getElementById("description");

const charCount =
    document.getElementById("charCount");

if (description) {

    description.addEventListener(
        "input",
        function () {

            charCount.textContent =
                description.value.length;

        }
    );

}


// =========================
// OPPORTUNITY TYPE
// =========================

document
.querySelectorAll(".type-option")
.forEach(option => {

    option.addEventListener(
        "click",
        function () {

            document
            .querySelectorAll(".type-option")
            .forEach(item => {

                item.classList.remove(
                    "active-type"
                );

            });

            option.classList.add(
                "active-type"
            );

        }
    );

});


// =========================
// ADD SKILL
// =========================

function addSkill() {

    const input =
        document.getElementById("skillInput");

    const skill =
        input.value.trim();

    if (skill === "") {

        alert("Please enter a skill.");

        return;
    }


    const hidden =
        document.getElementById("skillsHidden");

    let skills =
        hidden.value
        ? hidden.value
            .split(",")
            .map(s => s.trim())
            .filter(Boolean)
        : [];


    const exists =
        skills.some(
            s => s.toLowerCase() === skill.toLowerCase()
        );


    if (exists) {

        alert("This skill is already added.");

        input.value = "";

        return;
    }


    skills.push(skill);

    hidden.value =
        skills.join(", ");


    const skillList =
        document.getElementById("skillList");


    const span =
        document.createElement("span");

    span.className =
        "selected-skill";


    span.innerHTML =
        `${escapeHtml(skill)}
        <button
            type="button"
            onclick="removeSkill(this)">
            ×
        </button>`;


    skillList.appendChild(span);

    input.value = "";

}


// =========================
// REMOVE SKILL
// =========================

function removeSkill(button) {

    const skill =
        button.parentElement
        .firstChild
        .textContent
        .trim();


    button.parentElement.remove();


    const hidden =
        document.getElementById("skillsHidden");


    let skills =
        hidden.value
        .split(",")
        .map(s => s.trim())
        .filter(Boolean);


    skills =
        skills.filter(
            s => s.toLowerCase() !== skill.toLowerCase()
        );


    hidden.value =
        skills.join(", ");

}


// =========================
// ENTER KEY
// =========================

document
.getElementById("skillInput")
.addEventListener(
    "keypress",
    function(event) {

        if (event.key === "Enter") {

            event.preventDefault();

            addSkill();

        }

    }
);


// =========================
// HTML ESCAPE
// =========================

function escapeHtml(text) {

    const div =
        document.createElement("div");

    div.textContent = text;

    return div.innerHTML;

}


// =========================
// FORM VALIDATION
// =========================

document
.getElementById("opportunityForm")
.addEventListener(
    "submit",
    function(event) {

        const skills =
            document
            .getElementById("skillsHidden")
            .value
            .trim();


        if (skills === "") {

            event.preventDefault();

            alert(
                "Please add at least one required skill."
            );

            return;
        }

    }
);

</script>


</body>

</html>
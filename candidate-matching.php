<?php
session_start();

require_once "config/database.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$industry_id = $_SESSION['user_id'];

/* =========================
   SHORTLIST / REJECT ACTION
========================= */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $student_id = intval($_POST['student_id'] ?? 0);
    $action = $_POST['action'] ?? '';

    if ($student_id > 0) {

        if ($action === "shortlist") {

            $stmt = $conn->prepare("
                INSERT INTO candidate_actions
                (industry_id, student_id, action)
                VALUES (?, ?, 'Shortlisted')
                ON DUPLICATE KEY UPDATE action='Shortlisted'
            ");

            $stmt->bind_param("ii", $industry_id, $student_id);
            $stmt->execute();
            $stmt->close();
        }

        if ($action === "reject") {

            $stmt = $conn->prepare("
                INSERT INTO candidate_actions
                (industry_id, student_id, action)
                VALUES (?, ?, 'Rejected')
                ON DUPLICATE KEY UPDATE action='Rejected'
            ");

            $stmt->bind_param("ii", $industry_id, $student_id);
            $stmt->execute();
            $stmt->close();
        }

        header("Location: candidate-matching.php");
        exit();
    }
}


/* =========================
   GET INDUSTRY NAME
========================= */

$industry_name = "Industry";

$stmt = $conn->prepare("
    SELECT name
    FROM users
    WHERE id = ?
");

$stmt->bind_param("i", $industry_id);
$stmt->execute();

$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    $industry_name = $row['name'];
}

$stmt->close();


/* =========================
   GET CANDIDATES
========================= */

$candidates = [];

$sql = "
    SELECT
        u.id,
        u.name,
        u.email,
        sp.college,
        sp.course,
        sp.year,
        sp.location,
        sp.bio,
        GROUP_CONCAT(
            CONCAT(s.skill_name, '|', COALESCE(s.skill_level, ''))
            SEPARATOR ','
        ) AS skills
    FROM users u

    LEFT JOIN student_profiles sp
        ON u.id = sp.user_id

    LEFT JOIN skills s
        ON u.id = s.user_id

    WHERE u.role = 'student'

    GROUP BY
        u.id,
        u.name,
        u.email,
        sp.college,
        sp.course,
        sp.year,
        sp.location,
        sp.bio

    ORDER BY u.id DESC
";

$result = $conn->query($sql);

if ($result) {

    while ($row = $result->fetch_assoc()) {

        $skill_list = [];

        if (!empty($row['skills'])) {

            $skill_rows = explode(",", $row['skills']);

            foreach ($skill_rows as $skill_row) {

                $parts = explode("|", $skill_row);

                $skill_name = trim($parts[0]);

                if ($skill_name !== "") {
                    $skill_list[] = $skill_name;
                }
            }
        }

        /*
         * Basic AI-style matching score.
         * Later this can be connected to actual
         * opportunity requirements.
         */

        $important_skills = [
            "python",
            "sql",
            "java",
            "javascript",
            "machine learning",
            "html",
            "css"
        ];

        $matched = 0;

        foreach ($skill_list as $skill) {

            if (
                in_array(
                    strtolower(trim($skill)),
                    $important_skills
                )
            ) {
                $matched++;
            }
        }

        $skill_count = count($skill_list);

        if ($skill_count > 0) {
            $match_score = round(
                min(
                    98,
                    55 + ($matched * 8) + min($skill_count, 5)
                )
            );
        } else {
            $match_score = 50;
        }

        $candidates[] = [
            "id" => $row['id'],
            "name" => $row['name'],
            "email" => $row['email'],
            "college" => $row['college'] ?: "Not Added",
            "course" => $row['course'] ?: "Not Added",
            "year" => $row['year'] ?: "Not Added",
            "location" => $row['location'] ?: "Not Added",
            "bio" => $row['bio'] ?: "",
            "skills" => $skill_list,
            "match" => $match_score
        ];
    }
}


/* =========================
   KPI DATA
========================= */

$total_candidates = count($candidates);

$strong_matches = 0;
$total_match = 0;

foreach ($candidates as $candidate) {

    $total_match += $candidate['match'];

    if ($candidate['match'] >= 80) {
        $strong_matches++;
    }
}

$average_match = $total_candidates > 0
    ? round($total_match / $total_candidates)
    : 0;


/* =========================
   SHORTLIST COUNT
========================= */

$shortlisted = 0;

$stmt = $conn->prepare("
    SELECT COUNT(*)
    AS total
    FROM candidate_actions
    WHERE industry_id = ?
    AND action = 'Shortlisted'
");

$stmt->bind_param("i", $industry_id);
$stmt->execute();

$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    $shortlisted = $row['total'];
}

$stmt->close();


/* =========================
   ESCAPE FUNCTION
========================= */

function e($value)
{
    return htmlspecialchars(
        $value ?? "",
        ENT_QUOTES,
        "UTF-8"
    );
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

<title>Candidate Matching | SkillBridge</title>

<link rel="stylesheet" href="style.css">

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

.matching-page {
    padding-bottom: 50px;
}

.matching-topbar {
    margin-bottom: 25px;
}

.matching-hero {
    background: linear-gradient(
        135deg,
        #6259ee,
        #7955e8
    );

    border-radius: 18px;
    padding: 30px 34px;
    color: white;

    display: flex;
    justify-content: space-between;
    align-items: center;

    gap: 25px;
    margin-bottom: 23px;
}

.matching-hero-badge {
    display: inline-block;
    background: rgba(255,255,255,.16);
    padding: 7px 12px;
    border-radius: 20px;
    font-size: 11px;
    margin-bottom: 12px;
}

.matching-hero h1 {
    font-size: 28px;
    margin-bottom: 8px;
}

.matching-hero p {
    max-width: 650px;
    font-size: 13px;
    line-height: 1.7;
    opacity: .9;
}

.ai-icon-box {
    width: 82px;
    height: 82px;
    border-radius: 20px;
    background: rgba(255,255,255,.15);

    display: flex;
    align-items: center;
    justify-content: center;

    font-size: 38px;
    flex-shrink: 0;
}

.matching-kpis {
    display: grid;
    grid-template-columns: repeat(4,1fr);
    gap: 16px;
    margin-bottom: 22px;
}

.matching-kpi {
    background: white;
    border: 1px solid #e7e6f0;
    border-radius: 15px;
    padding: 19px;

    display: flex;
    align-items: center;
    gap: 13px;
}

.matching-kpi-icon {
    width: 46px;
    height: 46px;
    border-radius: 12px;
    background: #f0efff;

    display: flex;
    justify-content: center;
    align-items: center;

    font-size: 20px;
}

.matching-kpi small {
    color: #85859a;
    font-size: 10px;
    display: block;
    margin-bottom: 4px;
}

.matching-kpi strong {
    font-size: 23px;
    color: #202034;
}

.matching-filter {
    background: white;
    border: 1px solid #e7e6f0;
    border-radius: 15px;
    padding: 18px;

    display: flex;
    gap: 12px;
    align-items: center;

    margin-bottom: 22px;
    flex-wrap: wrap;
}

.candidate-search {
    flex: 1;
    min-width: 220px;
    position: relative;
}

.candidate-search input {
    width: 100%;
    box-sizing: border-box;

    border: 1px solid #dedee9;
    border-radius: 9px;

    padding: 12px 14px 12px 40px;

    outline: none;
    font-family: inherit;
}

.search-symbol {
    position: absolute;
    left: 14px;
    top: 11px;
    color: #85859a;
}

.matching-filter select {
    border: 1px solid #dedee9;
    border-radius: 9px;

    padding: 12px 14px;
    min-width: 145px;

    background: white;
    color: #45455b;

    outline: none;
    font-family: inherit;
}

.matching-filter button {
    border: none;
    background: #635bff;
    color: white;

    border-radius: 9px;
    padding: 12px 18px;

    cursor: pointer;
    font-weight: 600;
}

.candidate-heading {
    display: flex;
    justify-content: space-between;
    align-items: center;

    margin-bottom: 15px;
}

.candidate-heading h2 {
    font-size: 19px;
    color: #202034;
}

.candidate-heading p {
    color: #85859a;
    font-size: 11px;
    margin-top: 4px;
}

.sort-select {
    border: 1px solid #dedee9;
    background: white;

    border-radius: 8px;
    padding: 9px 12px;

    font-size: 11px;
    color: #55556b;
}

.candidate-grid {
    display: grid;
    grid-template-columns: repeat(2,1fr);
    gap: 18px;
}

.candidate-card {
    background: white;
    border: 1px solid #e7e6f0;

    border-radius: 17px;
    padding: 22px;

    transition: .25s;
}

.candidate-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 30px rgba(36,35,70,.07);
}

.candidate-top {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;

    margin-bottom: 18px;
}

.candidate-person {
    display: flex;
    align-items: center;
    gap: 12px;
}

.candidate-avatar {
    width: 50px;
    height: 50px;

    border-radius: 14px;
    background: #efefff;
    color: #5f57e8;

    display: flex;
    justify-content: center;
    align-items: center;

    font-size: 18px;
    font-weight: 700;
}

.candidate-person h3 {
    color: #202034;
    font-size: 14px;
    margin-bottom: 4px;
}

.candidate-person p {
    color: #85859a;
    font-size: 10px;
}

.match-score {
    text-align: center;
    min-width: 67px;
}

.match-score strong {
    display: block;
    font-size: 20px;
    color: #16a34a;
}

.match-score span {
    color: #85859a;
    font-size: 9px;
}

.candidate-info {
    display: grid;
    grid-template-columns: 1fr 1fr;

    gap: 9px;
    margin-bottom: 16px;
}

.candidate-info-item {
    background: #fafaff;
    padding: 9px 11px;
    border-radius: 8px;
}

.candidate-info-item span {
    display: block;
    font-size: 9px;
    color: #9999aa;
    margin-bottom: 3px;
}

.candidate-info-item strong {
    font-size: 10px;
    color: #45455b;
}

.candidate-skills-title {
    font-size: 10px;
    font-weight: 700;
    color: #55556b;
    margin-bottom: 8px;
}

.candidate-skills {
    display: flex;
    flex-wrap: wrap;

    gap: 6px;
    margin-bottom: 17px;
}

.candidate-skill {
    background: #f0efff;
    color: #5e56e5;

    padding: 6px 9px;
    border-radius: 20px;

    font-size: 9px;
    font-weight: 600;
}

.match-progress {
    margin-bottom: 17px;
}

.match-progress-top {
    display: flex;
    justify-content: space-between;

    font-size: 9px;
    color: #85859a;

    margin-bottom: 6px;
}

.match-progress-bar {
    height: 7px;
    background: #ededf4;

    border-radius: 20px;
    overflow: hidden;
}

.match-progress-fill {
    height: 100%;

    background: linear-gradient(
        90deg,
        #635bff,
        #8658ed
    );

    border-radius: 20px;
}

.candidate-actions {
    display: flex;
    gap: 8px;
}

.candidate-actions button {
    flex: 1;

    padding: 10px 9px;

    border-radius: 8px;

    font-family: inherit;
    font-size: 10px;
    font-weight: 600;

    cursor: pointer;
}

.view-profile-btn {
    background: white;
    color: #635bff;
    border: 1px solid #d9d7ff;
}

.shortlist-btn {
    background: #635bff;
    color: white;
    border: 1px solid #635bff;
}

.reject-btn {
    background: white;
    color: #dc2626;
    border: 1px solid #f0caca;

    max-width: 70px;
}

.ai-matching-banner {
    margin-top: 22px;

    padding: 25px 28px;

    border-radius: 16px;
    border: 1px solid #ddd9ff;

    background: linear-gradient(
        135deg,
        #f4f2ff,
        #fbfaff
    );

    display: flex;
    align-items: center;
    justify-content: space-between;

    gap: 20px;
}

.ai-banner-left {
    display: flex;
    align-items: center;
    gap: 14px;
}

.ai-banner-icon {
    width: 48px;
    height: 48px;

    border-radius: 12px;
    background: #635bff;
    color: white;

    display: flex;
    align-items: center;
    justify-content: center;

    font-size: 21px;
}

.ai-banner-left h3 {
    font-size: 14px;
    color: #29293d;
    margin-bottom: 4px;
}

.ai-banner-left p {
    color: #77778c;
    font-size: 10px;
}

.ai-banner-btn {
    border: none;
    background: #635bff;
    color: white;

    padding: 11px 17px;

    border-radius: 8px;

    font-size: 10px;
    font-weight: 600;

    cursor: pointer;
}

.no-candidates {
    background: white;
    border: 1px solid #e7e6f0;

    border-radius: 15px;

    padding: 45px;
    text-align: center;

    color: #85859a;
}

@media(max-width:1050px) {

    .matching-kpis {
        grid-template-columns: repeat(2,1fr);
    }

    .candidate-grid {
        grid-template-columns: 1fr;
    }
}

@media(max-width:700px) {

    .matching-hero {
        padding: 25px;
    }

    .matching-hero h1 {
        font-size: 23px;
    }

    .ai-icon-box {
        display: none;
    }

    .matching-filter {
        flex-direction: column;
        align-items: stretch;
    }

    .candidate-search {
        width: 100%;
    }

    .matching-filter select,
    .matching-filter button {
        width: 100%;
    }

    .candidate-heading {
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
    }

    .ai-matching-banner {
        flex-direction: column;
        align-items: flex-start;
    }

    .ai-banner-btn {
        width: 100%;
    }
}

@media(max-width:500px) {

    .matching-kpis {
        grid-template-columns: 1fr;
    }

    .candidate-info {
        grid-template-columns: 1fr;
    }

    .candidate-actions {
        flex-wrap: wrap;
    }

    .candidate-actions button {
        min-width: 45%;
    }

    .reject-btn {
        max-width: none;
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
INDUSTRY
</div>


<nav>

<a href="industry-dashboard.php" class="side-link">
<span>🏠</span>
Dashboard
</a>

<a href="post-opportunity.php" class="side-link">
<span>＋</span>
Post Opportunity
</a>

<a href="candidate-matching.php" class="side-link active">
<span>◎</span>
Candidate Matching
</a>

<a href="#" class="side-link">
<span>📋</span>
Applications
</a>

<a href="collaboration.php" class="side-link">
<span>◇</span>
Collaboration
</a>

<a href="#" class="side-link">
<span>⚙</span>
Company Profile
</a>

</nav>


<div class="sidebar-bottom">

<a href="logout.php" class="side-link logout-link">
<span>🚪</span>
Logout
</a>

</div>

</aside>


<!-- MAIN -->

<main class="main matching-page">


<!-- TOPBAR -->

<div class="topbar matching-topbar">

<div>

<h2>
Candidate Matching
</h2>

<p>
Welcome, <?= e($industry_name) ?>. Find students who best match your requirements.
</p>

</div>

</div>


<!-- HERO -->

<section class="matching-hero">

<div>

<span class="matching-hero-badge">
✨ AI Powered Matching
</span>

<h1>
Find Your Best Candidates
</h1>

<p>
Our AI analyzes student skills, education, projects and experience
to identify candidates who are the best fit for your opportunities.
</p>

</div>

<div class="ai-icon-box">
🤖
</div>

</section>


<!-- KPI -->

<section class="matching-kpis">


<div class="matching-kpi">

<div class="matching-kpi-icon">
👥
</div>

<div>

<small>
Total Candidates
</small>

<strong>
<?= $total_candidates ?>
</strong>

</div>

</div>


<div class="matching-kpi">

<div class="matching-kpi-icon">
🎯
</div>

<div>

<small>
Strong Matches
</small>

<strong>
<?= $strong_matches ?>
</strong>

</div>

</div>


<div class="matching-kpi">

<div class="matching-kpi-icon">
⭐
</div>

<div>

<small>
Shortlisted
</small>

<strong>
<?= $shortlisted ?>
</strong>

</div>

</div>


<div class="matching-kpi">

<div class="matching-kpi-icon">
📈
</div>

<div>

<small>
Average Match
</small>

<strong>
<?= $average_match ?>%
</strong>

</div>

</div>

</section>


<!-- FILTER -->

<section class="matching-filter">

<div class="candidate-search">

<span class="search-symbol">
🔍
</span>

<input
type="text"
id="candidateSearch"
placeholder="Search candidates by name, skill or college..."
>

</div>


<select id="matchFilter">

<option value="">
All Match Scores
</option>

<option value="90">
90%+ Match
</option>

<option value="80">
80%+ Match
</option>

<option value="70">
70%+ Match
</option>

</select>


<select id="skillFilter">

<option value="">
All Skills
</option>

<option value="python">
Python
</option>

<option value="java">
Java
</option>

<option value="sql">
SQL
</option>

<option value="machine learning">
Machine Learning
</option>

<option value="javascript">
JavaScript
</option>

</select>


<button onclick="filterCandidates()">
Apply Filters
</button>

</section>


<!-- CANDIDATES -->

<div class="candidate-heading">

<div>

<h2>
Recommended Candidates
</h2>

<p>
Candidates ranked according to their skills and profile data.
</p>

</div>

<select class="sort-select" id="sortCandidates">

<option>
Highest Match
</option>

<option>
Most Recent
</option>

</select>

</div>


<section class="candidate-grid" id="candidateGrid">


<?php if (count($candidates) === 0): ?>

<div class="no-candidates">

<h3>
No student candidates available
</h3>

<p>
Students will appear here after they create their SkillBridge profile and add skills.
</p>

</div>

<?php else: ?>


<?php foreach ($candidates as $candidate): ?>

<?php

$name = $candidate['name'];

$initials = "";

$name_parts = preg_split(
    '/\s+/',
    trim($name)
);

foreach ($name_parts as $part) {

    if ($part !== "") {
        $initials .= strtoupper(
            substr($part,0,1)
        );
    }

    if (strlen($initials) >= 2) {
        break;
    }
}

?>

<div
class="candidate-card"
data-match="<?= $candidate['match'] ?>"
data-skills="<?= e(strtolower(implode(" ",$candidate['skills']))) ?>"
>


<div class="candidate-top">


<div class="candidate-person">

<div class="candidate-avatar">
<?= e($initials) ?>
</div>

<div>

<h3>
<?= e($candidate['name']) ?>
</h3>

<p>
<?= e($candidate['course']) ?>
</p>

</div>

</div>


<div class="match-score">

<strong>
<?= $candidate['match'] ?>%
</strong>

<span>
AI Match
</span>

</div>

</div>


<div class="candidate-info">

<div class="candidate-info-item">

<span>
College
</span>

<strong>
<?= e($candidate['college']) ?>
</strong>

</div>


<div class="candidate-info-item">

<span>
Year
</span>

<strong>
<?= e($candidate['year']) ?>
</strong>

</div>

</div>


<div class="candidate-skills-title">
Student Skills
</div>


<div class="candidate-skills">

<?php if (count($candidate['skills']) > 0): ?>

<?php foreach ($candidate['skills'] as $skill): ?>

<span class="candidate-skill">
<?= e($skill) ?>
</span>

<?php endforeach; ?>

<?php else: ?>

<span class="candidate-skill">
No skills added
</span>

<?php endif; ?>

</div>


<div class="match-progress">

<div class="match-progress-top">

<span>
Profile Match
</span>

<span>
<?= $candidate['match'] ?>%
</span>

</div>


<div class="match-progress-bar">

<div
class="match-progress-fill"
style="width:<?= $candidate['match'] ?>%;">
</div>

</div>

</div>


<div class="candidate-actions">


<a
href="candidate-profile.php?id=<?= $candidate['id'] ?>"
class="view-profile-btn"
style="text-align:center;text-decoration:none;padding:10px 9px;border-radius:8px;font-size:10px;font-weight:600;flex:1;"
>
View Profile
</a>


<form method="POST" style="flex:1;">

<input
type="hidden"
name="student_id"
value="<?= $candidate['id'] ?>"
>

<input
type="hidden"
name="action"
value="shortlist"
>

<button
type="submit"
class="shortlist-btn"
style="width:100%;"
>
Shortlist
</button>

</form>


<form method="POST">

<input
type="hidden"
name="student_id"
value="<?= $candidate['id'] ?>"
>

<input
type="hidden"
name="action"
value="reject"
>

<button
type="submit"
class="reject-btn"
>
Reject
</button>

</form>


</div>


</div>

<?php endforeach; ?>

<?php endif; ?>


</section>


<!-- AI BANNER -->

<section class="ai-matching-banner">

<div class="ai-banner-left">

<div class="ai-banner-icon">
✨
</div>

<div>

<h3>
AI Matching is continuously learning
</h3>

<p>
Candidate rankings improve as more students complete assessments
and update their profiles.
</p>

</div>

</div>


<button
class="ai-banner-btn"
onclick="location.reload()"
>
Refresh AI Analysis
</button>

</section>


</main>

</div>


<script src="script.js"></script>


<script>

function filterCandidates() {

    const search =
        document
        .getElementById("candidateSearch")
        .value
        .toLowerCase();

    const matchFilter =
        document
        .getElementById("matchFilter")
        .value;

    const skillFilter =
        document
        .getElementById("skillFilter")
        .value
        .toLowerCase();

    const cards =
        document.querySelectorAll(
            ".candidate-card"
        );

    cards.forEach(function(card) {

        const text =
            card.textContent.toLowerCase();

        const match =
            parseInt(card.dataset.match);

        const skills =
            card.dataset.skills.toLowerCase();

        let show = true;

        if (
            search &&
            !text.includes(search)
        ) {
            show = false;
        }

        if (
            matchFilter &&
            match < parseInt(matchFilter)
        ) {
            show = false;
        }

        if (
            skillFilter &&
            !skills.includes(skillFilter)
        ) {
            show = false;
        }

        card.style.display =
            show ? "" : "none";

    });
}


document
.getElementById("candidateSearch")
.addEventListener(
    "input",
    filterCandidates
);


document
.getElementById("matchFilter")
.addEventListener(
    "change",
    filterCandidates
);


document
.getElementById("skillFilter")
.addEventListener(
    "change",
    filterCandidates
);


document
.getElementById("sortCandidates")
.addEventListener(
    "change",
    function() {

        const grid =
            document.getElementById(
                "candidateGrid"
            );

        const cards =
            Array.from(
                grid.querySelectorAll(
                    ".candidate-card"
                )
            );

        if (
            this.value ===
            "Highest Match"
        ) {

            cards.sort(
                function(a,b) {

                    return (
                        parseInt(
                            b.dataset.match
                        )
                        -
                        parseInt(
                            a.dataset.match
                        )
                    );

                }
            );

        }

        cards.forEach(
            function(card) {
                grid.appendChild(card);
            }
        );

    }
);

</script>

</body>
</html>
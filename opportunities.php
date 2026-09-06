<?php
session_start();
require_once "config/database.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION["user_id"];


/* USER DETAILS */

$stmt = $conn->prepare("
    SELECT name, role
    FROM users
    WHERE id = ?
");

$stmt->bind_param("i", $user_id);
$stmt->execute();

$userResult = $stmt->get_result();
$user = $userResult->fetch_assoc();

$userName = $user["name"] ?? "Student";

$avatar = strtoupper(
    substr($userName, 0, 1)
);


/* APPLY FOR OPPORTUNITY */

if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST["apply"])
) {

    $opportunity_id = intval($_POST["opportunity_id"]);

    /* Check whether already applied */

    $check = $conn->prepare("
        SELECT id
        FROM applications
        WHERE student_id = ?
        AND opportunity_id = ?
    ");

    $check->bind_param(
        "ii",
        $user_id,
        $opportunity_id
    );

    $check->execute();

    $checkResult = $check->get_result();


    if ($checkResult->num_rows > 0) {

        $applyMessage =
            "You have already applied for this opportunity.";

        $applyType = "error";

    } else {

        /* Check opportunity */

        $opCheck = $conn->prepare("
            SELECT id
            FROM opportunities
            WHERE id = ?
            AND (deadline IS NULL OR deadline >= CURDATE())
        ");

        $opCheck->bind_param(
            "i",
            $opportunity_id
        );

        $opCheck->execute();

        $opResult = $opCheck->get_result();


        if ($opResult->num_rows === 0) {

            $applyMessage =
                "This opportunity is no longer available.";

            $applyType = "error";

        } else {

            /* Insert Application */

            $insert = $conn->prepare("
                INSERT INTO applications
                (student_id, opportunity_id, status)
                VALUES (?, ?, 'Applied')
            ");

            $insert->bind_param(
                "ii",
                $user_id,
                $opportunity_id
            );


            if ($insert->execute()) {

                $applyMessage =
                    "Application submitted successfully!";

                $applyType = "success";

            } else {

                $applyMessage =
                    "Unable to submit application.";

                $applyType = "error";
            }

            $insert->close();
        }

        $opCheck->close();
    }

    $check->close();
}


/* GET FILTER VALUES */

$search = trim($_GET["search"] ?? "");
$type = $_GET["type"] ?? "all";
$location = $_GET["location"] ?? "all";


/* LOAD OPPORTUNITIES */

$sql = "
    SELECT
        o.id,
        o.title,
        o.type,
        o.category,
        o.description,
        o.skills,
        o.location,
        o.openings,
        o.stipend,
        o.deadline,

        u.name AS company_name

    FROM opportunities o

    INNER JOIN users u
    ON o.user_id = u.id

    WHERE
        o.deadline IS NULL
        OR o.deadline >= CURDATE()
";


$params = [];
$types = "";


/* SEARCH */

if ($search !== "") {

    $sql .= "
        AND (
            o.title LIKE ?
            OR o.skills LIKE ?
            OR o.category LIKE ?
            OR o.description LIKE ?
            OR u.name LIKE ?
        )
    ";

    $searchValue = "%" . $search . "%";

    $params[] = $searchValue;
    $params[] = $searchValue;
    $params[] = $searchValue;
    $params[] = $searchValue;
    $params[] = $searchValue;

    $types .= "sssss";
}


/* TYPE FILTER */

if ($type !== "all") {

    $sql .= " AND o.type = ? ";

    $params[] = $type;

    $types .= "s";
}


/* LOCATION FILTER */

if ($location !== "all") {

    $sql .= " AND o.location = ? ";

    $params[] = $location;

    $types .= "s";
}


$sql .= "
    ORDER BY o.created_at DESC
";


$stmt = $conn->prepare($sql);


if (!empty($params)) {

    $stmt->bind_param(
        $types,
        ...$params
    );
}


$stmt->execute();

$opportunities = $stmt->get_result();


/* TOTAL OPPORTUNITIES */

$totalQuery = $conn->query("
    SELECT COUNT(*) AS total
    FROM opportunities
    WHERE deadline IS NULL
    OR deadline >= CURDATE()
");

$totalOpportunities =
    $totalQuery->fetch_assoc()["total"] ?? 0;


/* STUDENT APPLICATION COUNT */

$appQuery = $conn->prepare("
    SELECT COUNT(*) AS total
    FROM applications
    WHERE student_id = ?
");

$appQuery->bind_param(
    "i",
    $user_id
);

$appQuery->execute();

$applications =
    $appQuery->get_result()
             ->fetch_assoc()["total"] ?? 0;


/* BEST MATCH */

$bestMatch = 0;

if ($opportunities->num_rows > 0) {

    $tempResult = $opportunities;

    while ($temp = $tempResult->fetch_assoc()) {

        /*
         * Basic demo matching.
         * Later this can be connected with skill assessment.
         */

        $match = 70;

        if (!empty($temp["skills"])) {
            $match += 5;
        }

        if ($match > 100) {
            $match = 100;
        }

        if ($match > $bestMatch) {
            $bestMatch = $match;
        }
    }

    /* Re-run query for displaying cards */

    $stmt->execute();
    $opportunities = $stmt->get_result();
}


/* BEST MATCH COUNT */

$bestMatches = 0;

if ($opportunities->num_rows > 0) {

    $tempResult = $opportunities;

    while ($temp = $tempResult->fetch_assoc()) {

        $match = !empty($temp["skills"]) ? 75 : 70;

        if ($match >= 75) {
            $bestMatches++;
        }
    }

    $stmt->execute();
    $opportunities = $stmt->get_result();
}

?>


<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport"
      content="width=device-width, initial-scale=1.0">

<title>Opportunities - SkillBridge</title>

<link rel="stylesheet"
      href="style.css">


<style>

.page-message {

    padding: 15px 20px;

    border-radius: 10px;

    margin-bottom: 20px;

    font-weight: 600;

}

.page-message.success {

    background: #dcfce7;

    color: #166534;

}

.page-message.error {

    background: #fee2e2;

    color: #991b1b;

}

.no-results {

    display: <?php
        echo ($opportunities->num_rows === 0)
            ? "block"
            : "none";
    ?>;

}

.application-form {

    margin: 0;

}

.application-form button {

    border: none;

    cursor: pointer;

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
   class="side-link active">

    💼 Opportunities

</a>


<a href="applications.php"
   class="side-link">

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


<!-- TOPBAR -->

<header class="topbar">


<div>

    <h2>
        Opportunities
    </h2>

    <p>
        Find opportunities that match your skills and career goals.
    </p>

</div>



<div class="user-box">


<div class="avatar">

    <?php
    echo htmlspecialchars($avatar);
    ?>

</div>


<div>

    <strong>

        <?php
        echo htmlspecialchars($userName);
        ?>

    </strong>


    <small>
        Student
    </small>

</div>


</div>


</header>



<!-- CONTENT -->

<section class="page-content">


<!-- MESSAGE -->

<?php if (!empty($applyMessage)): ?>

<div class="page-message
    <?php echo $applyType; ?>">

    <?php
    echo htmlspecialchars($applyMessage);
    ?>

</div>

<?php endif; ?>



<!-- HERO -->

<div class="opportunity-hero">


<div>


<span class="hero-label">
    CAREER OPPORTUNITIES
</span>


<h1>

    Discover Your Next
    <span>Opportunity</span>

</h1>


<p>

    Explore internships, jobs, projects and training
    opportunities personalized according to your skills.

</p>


</div>


<div class="hero-icon">
    💼
</div>


</div>



<!-- SEARCH -->

<form method="GET"
      class="search-box">


<div class="search-input">

    🔎

    <input
        type="text"
        name="search"
        value="<?php
            echo htmlspecialchars($search);
        ?>"
        placeholder="Search opportunities, skills or companies..."
    >

</div>


<select name="type">


<option value="all">
    All Types
</option>


<option value="Internship"
    <?php
    echo ($type === "Internship")
        ? "selected"
        : "";
    ?>>

    Internship

</option>


<option value="Job"
    <?php
    echo ($type === "Job")
        ? "selected"
        : "";
    ?>>

    Job

</option>


<option value="Project"
    <?php
    echo ($type === "Project")
        ? "selected"
        : "";
    ?>>

    Live Project

</option>


<option value="Training"
    <?php
    echo ($type === "Training")
        ? "selected"
        : "";
    ?>>

    Training

</option>


</select>



<select name="location">


<option value="all">
    All Locations
</option>


<option value="Mumbai"
    <?php
    echo ($location === "Mumbai")
        ? "selected"
        : "";
    ?>>

    Mumbai

</option>


<option value="Pune"
    <?php
    echo ($location === "Pune")
        ? "selected"
        : "";
    ?>>

    Pune

</option>


<option value="Bangalore"
    <?php
    echo ($location === "Bangalore")
        ? "selected"
        : "";
    ?>>

    Bangalore

</option>


<option value="Remote"
    <?php
    echo ($location === "Remote")
        ? "selected"
        : "";
    ?>>

    Remote

</option>


</select>


<button type="submit"
        class="btn btn-primary">

    Search

</button>


</form>



<!-- STATS -->

<div class="kpis">


<div class="kpi">

    <div class="kpi-icon">
        💼
    </div>


    <div>

        <strong>
            <?php
            echo $totalOpportunities;
            ?>
        </strong>

        <span>
            Opportunities
        </span>

    </div>

</div>



<div class="kpi">

    <div class="kpi-icon">
        🎯
    </div>


    <div>

        <strong>
            <?php
            echo $bestMatches;
            ?>
        </strong>

        <span>
            Best Matches
        </span>

    </div>

</div>



<div class="kpi">

    <div class="kpi-icon">
        ⭐
    </div>


    <div>

        <strong>

            <?php
            echo $bestMatch > 0
                ? $bestMatch . "%"
                : "—";
            ?>

        </strong>

        <span>
            Highest Match
        </span>

    </div>

</div>



<div class="kpi">

    <div class="kpi-icon">
        📋
    </div>


    <div>

        <strong>
            <?php
            echo $applications;
            ?>
        </strong>

        <span>
            Applications
        </span>

    </div>

</div>


</div>



<!-- SECTION HEADER -->

<div class="section-heading">


<div>

    <h2>
        Recommended Opportunities
    </h2>

    <p>
        Based on available opportunities and your profile.
    </p>

</div>


<a href="opportunities.php"
   class="btn btn-outline">

    Reset Filters

</a>


</div>



<!-- OPPORTUNITIES -->

<div class="op-grid">


<?php if ($opportunities->num_rows > 0): ?>


<?php while ($op = $opportunities->fetch_assoc()): ?>


<?php

$title =
    $op["title"] ?? "Opportunity";

$company =
    $op["company_name"] ?? "Industry Partner";

$match =
    !empty($op["skills"])
    ? 75
    : 70;


/* LOGO */

$logo = strtoupper(
    substr($company, 0, 1)
);


/* CHECK APPLICATION */

$alreadyApplied = false;

$check = $conn->prepare("
    SELECT id
    FROM applications
    WHERE student_id = ?
    AND opportunity_id = ?
");

$check->bind_param(
    "ii",
    $user_id,
    $op["id"]
);

$check->execute();

$checkResult =
    $check->get_result();

if ($checkResult->num_rows > 0) {
    $alreadyApplied = true;
}

$check->close();

?>


<div class="op-card">


<div class="op-top">


<div class="company-logo">

    <?php
    echo htmlspecialchars($logo);
    ?>

</div>


<span class="match high">

    <?php
    echo $match;
    ?>% Match

</span>


</div>



<h3>

    <?php
    echo htmlspecialchars($title);
    ?>

</h3>


<p class="company">

    <?php
    echo htmlspecialchars($company);
    ?>

</p>



<!-- SKILLS -->

<div class="tags">


<?php

if (!empty($op["skills"])):

    $skills =
        explode(",", $op["skills"]);

    foreach (
        array_slice($skills, 0, 4)
        as $skill
    ):

?>

<span>

    <?php
    echo htmlspecialchars(
        trim($skill)
    );
    ?>

</span>

<?php

    endforeach;

else:

?>

<span>
    Skills not specified
</span>

<?php endif; ?>


</div>



<!-- INFO -->

<div class="op-info">


<span>

    📍

    <?php
    echo !empty($op["location"])
        ? htmlspecialchars($op["location"])
        : "Location not specified";
    ?>

</span>


<span>

    💼

    <?php
    echo !empty($op["type"])
        ? htmlspecialchars($op["type"])
        : "Opportunity";
    ?>

</span>


</div>



<!-- FOOTER -->

<div class="op-footer">


<div>

    <small>
        Deadline
    </small>


    <strong>

        <?php

        if (!empty($op["deadline"])) {

            echo date(
                "d M Y",
                strtotime($op["deadline"])
            );

        } else {

            echo "Open";

        }

        ?>

    </strong>

</div>



<?php if ($alreadyApplied): ?>


<button class="btn btn-outline"
        disabled>

    Applied

</button>


<?php else: ?>


<form method="POST"
      class="application-form">


<input type="hidden"
       name="opportunity_id"
       value="<?php
       echo $op["id"];
       ?>">


<button type="submit"
        name="apply"
        class="btn btn-primary">

    Apply Now

</button>


</form>


<?php endif; ?>


</div>


</div>


<?php endwhile; ?>


<?php else: ?>


<div class="no-results"
     style="grid-column:1/-1; text-align:center; padding:50px;">

    <div style="font-size:40px;">
        🔍
    </div>

    <h3>
        No opportunities found
    </h3>

    <p>
        Try changing your search or filters.
    </p>

</div>


<?php endif; ?>


</div>


</section>

</main>

</div>


<script src="script.js"></script>


</body>

</html>
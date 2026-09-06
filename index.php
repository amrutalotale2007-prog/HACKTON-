<?php
/**
 * SkillBridge Landing Page
 */

session_start();

/*
 * If the user is already logged in,
 * redirect them to the correct dashboard.
 */
if (isset($_SESSION['user_id'])) {

    $role = $_SESSION['role'] ?? '';

    switch (strtolower($role)) {

        case 'academician':
            header("Location: academician-dashboard.php");
            exit();

        case 'industry':
            header("Location: industry-dashboard.php");
            exit();

        case 'institution':
        case 'student':
            header("Location: dashboard.php");
            exit();

        default:
            // Unknown role — stay on landing page.
            break;
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>SkillBridge — Connecting Talent with Opportunities</title>

    <!-- Bootstrap -->
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <!-- Bootstrap Icons -->
    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
    >

    <!-- Custom CSS -->
    <link rel="stylesheet" href="style.css">

</head>

<body>

<!-- =========================
     NAVBAR
========================= -->

<nav class="navbar navbar-expand-lg bg-white border-bottom">

    <div class="container">

        <a
            class="navbar-brand fw-bold"
            href="index.php"
        >
            Skill<span style="color:#635bff;">Bridge</span>
        </a>

        <div class="d-flex gap-2">

            <a
                href="login.php"
                class="btn btn-outline-secondary"
            >
                Login
            </a>

            <a
                href="signup.php"
                class="btn"
                style="background:#635bff;color:#fff;"
            >
                Create Account
            </a>

        </div>

    </div>

</nav>


<!-- =========================
     HERO SECTION
========================= -->

<header
    class="py-5 border-bottom"
    style="background:#faf9ff;"
>

    <div class="container text-center py-5">

        <span
            class="badge rounded-pill mb-3"
            style="background:#ece9fe;color:#635bff;"
        >
            STUDENTS • ACADEMICIANS • INDUSTRY
        </span>

        <h1 class="display-5 fw-bold mb-3">

            Connecting Talent
            <br class="d-none d-md-block">

            with
            <span style="color:#635bff;">
                Opportunities
            </span>

        </h1>

        <p class="lead text-muted mb-4">

            Build skills, discover internships and jobs,
            and connect students, academicians and industry
            on one platform.

        </p>

        <div class="d-flex justify-content-center gap-3 flex-wrap">

            <a
                href="signup.php"
                class="btn btn-lg"
                style="background:#635bff;color:#fff;"
            >
                Get Started
                <i class="bi bi-arrow-right"></i>
            </a>

            <a
                href="login.php"
                class="btn btn-lg btn-outline-secondary"
            >
                I already have an account
            </a>

        </div>

    </div>

</header>


<!-- =========================
     FEATURES
========================= -->

<section class="container py-5">

    <div class="row g-4 text-center">

        <!-- STUDENTS -->

        <div class="col-md-4">

            <div class="p-4 h-100 border rounded-4">

                <i
                    class="bi bi-mortarboard-fill fs-1 mb-3"
                    style="color:#635bff;"
                ></i>

                <h3 class="h5">
                    For Students
                </h3>

                <p class="text-muted mb-0">

                    Take a skill assessment,
                    build your profile,
                    close skill gaps,
                    and apply to internships
                    and jobs matched to you.

                </p>

            </div>

        </div>


        <!-- INDUSTRY -->

        <div class="col-md-4">

            <div class="p-4 h-100 border rounded-4">

                <i
                    class="bi bi-briefcase-fill fs-1 mb-3"
                    style="color:#635bff;"
                ></i>

                <h3 class="h5">
                    For Industry
                </h3>

                <p class="text-muted mb-0">

                    Post opportunities,
                    find candidates that match
                    your requirements,
                    and manage applications
                    in one place.

                </p>

            </div>

        </div>


        <!-- ACADEMICIANS -->

        <div class="col-md-4">

            <div class="p-4 h-100 border rounded-4">

                <i
                    class="bi bi-mortarboard fs-1 mb-3"
                    style="color:#635bff;"
                ></i>

                <h3 class="h5">
                    For Academicians
                </h3>

                <p class="text-muted mb-0">

                    Manage research projects
                    and build industry collaborations —
                    mentorship, guest lectures,
                    workshops and live projects.

                </p>

            </div>

        </div>

    </div>

</section>


<!-- =========================
     FOOTER
========================= -->

<footer class="text-center text-muted py-4 border-top">

    &copy;
    <?= date("Y") ?>
    SkillBridge

</footer>


<!-- JavaScript -->

<script src="script.js"></script>

</body>

</html>
<?php
require_once "includes/auth.php";
require_once "config/database.php";

/* NOTE: this page previously had no login check at all (anyone could
   open the URL directly), and read $_SESSION['academician_name'], a
   key nothing in the app ever sets — so the welcome message was
   always blank. Fixed to require login and to require the
   "academician" role specifically, and to read the name the same
   way research-projects.php does. */

if (($_SESSION['role'] ?? '') !== 'academician') {
    header("Location: login.php");
    exit();
}

$academicianName = $_SESSION['full_name'] ?? $_SESSION['name'] ?? "";
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Academician Dashboard | SkillBridge</title>

    <link rel="stylesheet" href="style.css">

</head>

<body>

<div class="dashboard-layout">

    <!-- SIDEBAR -->

    <aside class="sidebar">

        <div class="logo-area">

            <h2>SkillBridge</h2>

        </div>


        <nav class="sidebar-menu">

            <a href="academician-dashboard.php"
               class="active">

                🏠 Dashboard

            </a>


            <a href="research-projects.php">

                🔬 Research & Projects

            </a>


            <a href="collaboration.php">

                🤝 Collaboration

            </a>


            <a href="logout.php">

                🚪 Logout

            </a>

        </nav>

    </aside>


    <!-- MAIN -->

    <main class="main-content">

        <!-- HEADER -->

        <header class="dashboard-topbar">

            <div>

                <h1>Academician Dashboard</h1>

                <p>
                    Connect research, education and industry.
                </p>

            </div>

            <div class="notification">
                🔔
            </div>

        </header>


        <!-- HERO -->

        <section class="dashboard-hero">

            <div>

                <span class="hero-badge">
                    ACADEMICIAN PORTAL
                </span>

                <h1>

                    <?php if ($academicianName != ""): ?>

                        Welcome,
                        <?= htmlspecialchars($academicianName) ?> 👋

                    <?php else: ?>

                        Research & Collaboration Hub 🔬

                    <?php endif; ?>

                </h1>

                <p>
                    Manage research projects, industry
                    collaborations and student engagement.
                </p>

            </div>


            <a href="research-projects.php"
               class="primary-btn">

                View Research

            </a>

        </section>


        <!-- STATS -->

        <section class="stats-grid">

            <div class="stat-card">

                <div class="stat-icon">
                    🔬
                </div>

                <div>

                    <p>Total Projects</p>

                    <h2>0</h2>

                    <span>Your research projects</span>

                </div>

            </div>


            <div class="stat-card">

                <div class="stat-icon">
                    🚀
                </div>

                <div>

                    <p>Active Research</p>

                    <h2>0</h2>

                    <span>Currently active</span>

                </div>

            </div>


            <div class="stat-card">

                <div class="stat-icon">
                    👨‍🎓
                </div>

                <div>

                    <p>Students Involved</p>

                    <h2>0</h2>

                    <span>Research participants</span>

                </div>

            </div>


            <div class="stat-card">

                <div class="stat-icon">
                    🤝
                </div>

                <div>

                    <p>Industry Partners</p>

                    <h2>0</h2>

                    <span>Active collaborations</span>

                </div>

            </div>

        </section>


        <!-- QUICK ACTIONS -->

        <section class="dashboard-card">

            <div class="card-header">

                <div>

                    <h2>Quick Actions</h2>

                    <p>
                        Manage your academic and research activities.
                    </p>

                </div>

            </div>


            <div class="quick-actions">

                <a href="research-projects.php"
                   class="quick-action">

                    <div class="quick-icon">
                        🔬
                    </div>

                    <div>

                        <h3>Research Projects</h3>

                        <p>
                            Manage your research projects.
                        </p>

                    </div>

                </a>


                <a href="collaboration.php"
                   class="quick-action">

                    <div class="quick-icon">
                        🤝
                    </div>

                    <div>

                        <h3>Industry Collaboration</h3>

                        <p>
                            Connect with industry partners.
                        </p>

                    </div>

                </a>


                <a href="collaboration.php"
                   class="quick-action">

                    <div class="quick-icon">
                        🎤
                    </div>

                    <div>

                        <h3>Guest Lectures</h3>

                        <p>
                            Organize industry sessions.
                        </p>

                    </div>

                </a>


                <a href="collaboration.php"
                   class="quick-action">

                    <div class="quick-icon">
                        💡
                    </div>

                    <div>

                        <h3>Live Projects</h3>

                        <p>
                            Create industry-based projects.
                        </p>

                    </div>

                </a>

            </div>

        </section>


        <!-- RESEARCH -->

        <section class="dashboard-card">

            <div class="card-header">

                <div>

                    <h2>Research Projects</h2>

                    <p>
                        Your latest research activities.
                    </p>

                </div>

                <a href="research-projects.php"
                   class="secondary-btn">

                    View All

                </a>

            </div>


            <div class="empty-state">

                <div class="empty-icon">
                    🔬
                </div>

                <h3>
                    No research projects added
                </h3>

                <p>
                    Add your research projects to
                    collaborate with students and industry.
                </p>

                <a href="research-projects.php"
                   class="primary-btn">

                    Add Research Project

                </a>

            </div>

        </section>


        <!-- COLLABORATION -->

        <section class="dashboard-card recommendation-card">

            <div>

                <span class="hero-badge">
                    INDUSTRY • ACADEMIA
                </span>

                <h2>
                    Build Stronger Industry Connections
                </h2>

                <p>
                    Collaborate with companies on research,
                    workshops, guest lectures, internships
                    and live projects.
                </p>

                <a href="collaboration.php"
                   class="primary-btn">

                    Explore Collaboration

                </a>

            </div>

            <div class="recommendation-icon">
                🤝
            </div>

        </section>

    </main>

</div>

<script src="script.js"></script>

</body>

</html>
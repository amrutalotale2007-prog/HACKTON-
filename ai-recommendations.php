<?php
require_once "includes/auth.php";

/* NOTE: previously read $_SESSION['student_name'], a key nothing in
   the app ever sets, so the welcome message was always blank. Also
   had no login check at all. */

$studentName = $_SESSION['full_name'] ?? $_SESSION['name'] ?? "";
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>AI Recommendations | SkillBridge</title>

    <link rel="stylesheet" href="style.css">

    <style>

        .recommendation-page {
            padding: 30px;
        }

        .ai-hero {
            background: linear-gradient(135deg, #635bff, #7c3aed);
            color: white;
            border-radius: 22px;
            padding: 35px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 25px;
            margin-bottom: 25px;
        }

        .ai-hero h1 {
            margin: 10px 0;
            font-size: 32px;
        }

        .ai-hero p {
            margin: 0;
            opacity: 0.9;
            max-width: 650px;
            line-height: 1.6;
        }

        .ai-icon {
            width: 100px;
            height: 100px;
            border-radius: 25px;
            background: rgba(255,255,255,0.18);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 50px;
            flex-shrink: 0;
        }

        .ai-badge {
            display: inline-block;
            padding: 7px 14px;
            border-radius: 20px;
            background: rgba(255,255,255,0.18);
            font-size: 13px;
            font-weight: 600;
        }

        .section-title {
            margin: 30px 0 15px;
        }

        .section-title h2 {
            margin-bottom: 5px;
        }

        .section-title p {
            color: #77778a;
        }

        .recommendation-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
        }

        .recommendation-card {
            background: white;
            border: 1px solid #e8e8f2;
            border-radius: 18px;
            padding: 22px;
            transition: 0.25s;
        }

        .recommendation-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 30px rgba(40,40,80,0.08);
        }

        .recommendation-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .recommendation-icon-box {
            width: 50px;
            height: 50px;
            border-radius: 14px;
            background: #f1efff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 25px;
        }

        .match-score {
            font-size: 14px;
            font-weight: 700;
            color: #635bff;
        }

        .recommendation-card h3 {
            margin: 10px 0;
        }

        .recommendation-card p {
            color: #77778a;
            line-height: 1.5;
            font-size: 14px;
        }

        .skill-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 7px;
            margin: 15px 0;
        }

        .skill-tag {
            background: #f5f5fa;
            padding: 6px 10px;
            border-radius: 8px;
            font-size: 12px;
        }

        .view-btn {
            display: block;
            width: 100%;
            border: none;
            background: #635bff;
            color: white;
            padding: 11px;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 600;
        }

        .career-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
        }

        .career-card {
            background: white;
            border: 1px solid #e8e8f2;
            border-radius: 18px;
            padding: 25px;
        }

        .career-header {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 15px;
        }

        .career-icon {
            width: 55px;
            height: 55px;
            border-radius: 15px;
            background: #f1efff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 27px;
        }

        .career-card h3 {
            margin: 0;
        }

        .career-card p {
            color: #77778a;
            line-height: 1.6;
        }

        .progress-section {
            margin-top: 18px;
        }

        .progress-info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 7px;
            font-size: 13px;
        }

        .progress-bar {
            height: 8px;
            background: #eeeeF5;
            border-radius: 10px;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            background: #635bff;
            border-radius: 10px;
        }

        .learning-list {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
        }

        .learning-card {
            background: white;
            border: 1px solid #e8e8f2;
            border-radius: 18px;
            padding: 22px;
        }

        .learning-card .number {
            font-size: 28px;
            font-weight: 800;
            color: #635bff;
        }

        .learning-card h3 {
            margin: 10px 0;
        }

        .learning-card p {
            color: #77778a;
            font-size: 14px;
            line-height: 1.5;
        }

        .empty-recommendation {
            background: white;
            border: 1px dashed #cfcfe0;
            border-radius: 18px;
            text-align: center;
            padding: 45px 20px;
            margin-top: 20px;
        }

        .empty-recommendation .icon {
            font-size: 45px;
            margin-bottom: 10px;
        }

        .empty-recommendation h3 {
            margin-bottom: 8px;
        }

        .empty-recommendation p {
            color: #77778a;
            max-width: 550px;
            margin: auto;
            line-height: 1.6;
        }

        @media(max-width:1000px) {

            .recommendation-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .learning-list {
                grid-template-columns: repeat(2, 1fr);
            }

        }

        @media(max-width:700px) {

            .recommendation-page {
                padding: 20px;
            }

            .ai-hero {
                flex-direction: column;
                align-items: flex-start;
            }

            .recommendation-grid,
            .career-grid,
            .learning-list {
                grid-template-columns: 1fr;
            }

            .ai-hero h1 {
                font-size: 25px;
            }

        }

    </style>

</head>


<body>

<div class="dashboard-layout">

    <!-- ================= SIDEBAR ================= -->

    <aside class="sidebar">

        <div class="logo-area">

            <h2>SkillBridge</h2>

        </div>


        <nav class="sidebar-menu">

            <a href="dashboard.php">
                🏠 Dashboard
            </a>

            <a href="skill-assessment.php">
                📝 Skill Assessment
            </a>

            <a href="skill-profile.php">
                👤 Skill Profile
            </a>

            <a href="skill-gap.php">
                📊 Skill Gap
            </a>

            <a href="ai-recommendations.php"
               class="active">

                🤖 AI Recommendations

            </a>

            <a href="opportunities.php">
                💼 Opportunities
            </a>

            <a href="applications.php">
                📋 My Applications
            </a>

            <a href="portfolio.php">
                🎓 Portfolio
            </a>

            <a href="logout.php">
                🚪 Logout
            </a>

        </nav>

    </aside>


    <!-- ================= MAIN CONTENT ================= -->

    <main class="main-content">

        <div class="recommendation-page">


            <!-- ================= HERO ================= -->

            <section class="ai-hero">

                <div>

                    <span class="ai-badge">
                        ✨ AI POWERED
                    </span>

                    <h1>
                        AI Career Recommendations
                    </h1>

                    <p>

                        <?php if ($studentName != ""): ?>

                            Personalized recommendations
                            for
                            <?= htmlspecialchars($studentName) ?>.

                        <?php else: ?>

                            Complete your profile and skill
                            assessment to receive personalized
                            career recommendations.

                        <?php endif; ?>

                    </p>

                </div>


                <div class="ai-icon">
                    🤖
                </div>

            </section>


            <!-- ================= RECOMMENDED CAREERS ================= -->

            <div class="section-title">

                <h2>
                    Recommended Career Paths
                </h2>

                <p>
                    Based on your skills and assessment results.
                </p>

            </div>


            <div class="career-grid">


                <!-- CAREER 1 -->

                <div class="career-card">

                    <div class="career-header">

                        <div class="career-icon">
                            💻
                        </div>

                        <div>

                            <h3>
                                Software Developer
                            </h3>

                            <span>
                                High demand career
                            </span>

                        </div>

                    </div>


                    <p>

                        Build applications and software
                        solutions using programming,
                        databases and development tools.

                    </p>


                    <div class="skill-tags">

                        <span class="skill-tag">
                            Programming
                        </span>

                        <span class="skill-tag">
                            Database
                        </span>

                        <span class="skill-tag">
                            Problem Solving
                        </span>

                    </div>


                    <div class="progress-section">

                        <div class="progress-info">

                            <span>
                                Career Match
                            </span>

                            <strong>
                                85%
                            </strong>

                        </div>

                        <div class="progress-bar">

                            <div class="progress-fill"
                                 style="width:85%">

                            </div>

                        </div>

                    </div>

                </div>


                <!-- CAREER 2 -->

                <div class="career-card">

                    <div class="career-header">

                        <div class="career-icon">
                            📊
                        </div>

                        <div>

                            <h3>
                                Data Analyst
                            </h3>

                            <span>
                                Growing career
                            </span>

                        </div>

                    </div>


                    <p>

                        Analyze datasets, create visualizations
                        and generate useful insights for
                        business decisions.

                    </p>


                    <div class="skill-tags">

                        <span class="skill-tag">
                            Python
                        </span>

                        <span class="skill-tag">
                            SQL
                        </span>

                        <span class="skill-tag">
                            Statistics
                        </span>

                    </div>


                    <div class="progress-section">

                        <div class="progress-info">

                            <span>
                                Career Match
                            </span>

                            <strong>
                                78%
                            </strong>

                        </div>

                        <div class="progress-bar">

                            <div class="progress-fill"
                                 style="width:78%">

                            </div>

                        </div>

                    </div>

                </div>


                <!-- CAREER 3 -->

                <div class="career-card">

                    <div class="career-header">

                        <div class="career-icon">
                            🤖
                        </div>

                        <div>

                            <h3>
                                AI / ML Engineer
                            </h3>

                            <span>
                                Emerging career
                            </span>

                        </div>

                    </div>


                    <p>

                        Develop intelligent systems using
                        machine learning, artificial intelligence
                        and data-driven techniques.

                    </p>


                    <div class="skill-tags">

                        <span class="skill-tag">
                            Python
                        </span>

                        <span class="skill-tag">
                            Machine Learning
                        </span>

                        <span class="skill-tag">
                            Mathematics
                        </span>

                    </div>


                    <div class="progress-section">

                        <div class="progress-info">

                            <span>
                                Career Match
                            </span>

                            <strong>
                                72%
                            </strong>

                        </div>

                        <div class="progress-bar">

                            <div class="progress-fill"
                                 style="width:72%">

                            </div>

                        </div>

                    </div>

                </div>


                <!-- CAREER 4 -->

                <div class="career-card">

                    <div class="career-header">

                        <div class="career-icon">
                            🌐
                        </div>

                        <div>

                            <h3>
                                Web Developer
                            </h3>

                            <span>
                                Popular career
                            </span>

                        </div>

                    </div>


                    <p>

                        Create responsive websites and
                        web applications using modern
                        frontend and backend technologies.

                    </p>


                    <div class="skill-tags">

                        <span class="skill-tag">
                            HTML
                        </span>

                        <span class="skill-tag">
                            CSS
                        </span>

                        <span class="skill-tag">
                            JavaScript
                        </span>

                    </div>


                    <div class="progress-section">

                        <div class="progress-info">

                            <span>
                                Career Match
                            </span>

                            <strong>
                                68%
                            </strong>

                        </div>

                        <div class="progress-bar">

                            <div class="progress-fill"
                                 style="width:68%">

                            </div>

                        </div>

                    </div>

                </div>

            </div>


            <!-- ================= SKILL BASED RECOMMENDATIONS ================= -->

            <div class="section-title">

                <h2>
                    Recommended Opportunities
                </h2>

                <p>
                    Opportunities that can help you improve your career profile.
                </p>

            </div>


            <div class="recommendation-grid">


                <div class="recommendation-card">

                    <div class="recommendation-top">

                        <div class="recommendation-icon-box">
                            💼
                        </div>

                        <span class="match-score">
                            92% Match
                        </span>

                    </div>

                    <h3>
                        Software Development Internship
                    </h3>

                    <p>
                        Gain practical experience by working
                        on real-world software projects.
                    </p>

                    <div class="skill-tags">

                        <span class="skill-tag">
                            Java
                        </span>

                        <span class="skill-tag">
                            SQL
                        </span>

                        <span class="skill-tag">
                            Git
                        </span>

                    </div>

                    <button class="view-btn"
                            onclick="viewOpportunity('Software Development Internship')">

                        View Opportunity

                    </button>

                </div>


                <div class="recommendation-card">

                    <div class="recommendation-top">

                        <div class="recommendation-icon-box">
                            📊
                        </div>

                        <span class="match-score">
                            86% Match
                        </span>

                    </div>

                    <h3>
                        Data Analytics Internship
                    </h3>

                    <p>
                        Work with real datasets and learn
                        practical data analysis techniques.
                    </p>

                    <div class="skill-tags">

                        <span class="skill-tag">
                            Python
                        </span>

                        <span class="skill-tag">
                            SQL
                        </span>

                        <span class="skill-tag">
                            Excel
                        </span>

                    </div>

                    <button class="view-btn"
                            onclick="viewOpportunity('Data Analytics Internship')">

                        View Opportunity

                    </button>

                </div>


                <div class="recommendation-card">

                    <div class="recommendation-top">

                        <div class="recommendation-icon-box">
                            🤖
                        </div>

                        <span class="match-score">
                            80% Match
                        </span>

                    </div>

                    <h3>
                        AI Project
                    </h3>

                    <p>
                        Work on an industry project involving
                        artificial intelligence and machine learning.
                    </p>

                    <div class="skill-tags">

                        <span class="skill-tag">
                            Python
                        </span>

                        <span class="skill-tag">
                            AI
                        </span>

                        <span class="skill-tag">
                            ML
                        </span>

                    </div>

                    <button class="view-btn"
                            onclick="viewOpportunity('AI Project')">

                        View Opportunity

                    </button>

                </div>

            </div>


            <!-- ================= LEARNING RECOMMENDATIONS ================= -->

            <div class="section-title">

                <h2>
                    Recommended Learning
                </h2>

                <p>
                    Improve your skill gaps with these learning paths.
                </p>

            </div>


            <div class="learning-list">


                <div class="learning-card">

                    <div class="number">
                        01
                    </div>

                    <h3>
                        Advanced Python
                    </h3>

                    <p>
                        Improve Python programming,
                        functions, OOP and data handling.
                    </p>

                    <button class="view-btn"
                            onclick="startLearning('Advanced Python')">

                        Start Learning

                    </button>

                </div>


                <div class="learning-card">

                    <div class="number">
                        02
                    </div>

                    <h3>
                        SQL & Database
                    </h3>

                    <p>
                        Learn queries, joins, normalization
                        and database management.
                    </p>

                    <button class="view-btn"
                            onclick="startLearning('SQL & Database')">

                        Start Learning

                    </button>

                </div>


                <div class="learning-card">

                    <div class="number">
                        03
                    </div>

                    <h3>
                        Web Development
                    </h3>

                    <p>
                        Learn frontend and backend
                        development for modern applications.
                    </p>

                    <button class="view-btn"
                            onclick="startLearning('Web Development')">

                        Start Learning

                    </button>

                </div>

            </div>


            <!-- ================= EMPTY / PROFILE MESSAGE ================= -->

            <div class="empty-recommendation">

                <div class="icon">
                    🎯
                </div>

                <h3>
                    Want More Accurate Recommendations?
                </h3>

                <p>
                    Complete your Skill Profile and Skill Assessment.
                    Once your information is available, SkillBridge
                    can generate recommendations based on your
                    skills, interests and career goals.
                </p>

                <br>

                <a href="skill-profile.php"
                   class="primary-btn">

                    Complete Profile

                </a>

                <a href="skill-assessment.php"
                   class="secondary-btn">

                    Take Assessment

                </a>

            </div>


        </div>

    </main>

</div>


<script src="script.js"></script>


<script>

function viewOpportunity(name) {

    alert(
        "Opening recommendation: " + name
    );

}


function startLearning(name) {

    alert(
        "Learning path selected: " + name
    );

}

</script>

</body>

</html>
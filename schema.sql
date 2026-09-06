-- SkillBridge — actual schema, reverse-engineered from the code
--
-- IMPORTANT: the SQL dump you originally shared (and schema-fixes.sql,
-- which patched it) describe a table design your PHP code has since
-- moved on from. Almost every table your app actually queries is
-- FLATTER than the dump: instead of student_profiles.student_id /
-- companies.company_id / academicians.academician_id being used as
-- foreign keys elsewhere, every table below just stores users.id
-- directly (usually in a column literally called user_id — except
-- `applications`, which calls it student_id, but it still holds a
-- users.id value, not a student_profiles.student_id).
--
-- This file is the schema your code actually expects. Use this
-- instead of the original dump / schema-fixes.sql.

CREATE DATABASE IF NOT EXISTS skillbridge;
USE skillbridge;

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('student','industry','academician','institution') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Read by skill-profile.php / candidate-matching.php.
-- NOTE: nothing in the project currently INSERTs a row here — see
-- README-FIXES.md for why that matters.
CREATE TABLE IF NOT EXISTS student_profiles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    college VARCHAR(150),
    course VARCHAR(100),
    year VARCHAR(20),
    location VARCHAR(150),
    bio TEXT,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- One row per skill a student has added (skill-profile.php,
-- candidate-matching.php). This replaces the dump's separate
-- skills catalog + student_skills join table with one flat table.
CREATE TABLE IF NOT EXISTS skills (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    skill_name VARCHAR(100) NOT NULL,
    skill_level VARCHAR(30),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- skill-assessment.php
CREATE TABLE IF NOT EXISTS assessments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    score INT NOT NULL,
    total_questions INT NOT NULL,
    completed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- post-opportunity.php / opportunities.php / industry-dashboard.php
CREATE TABLE IF NOT EXISTS opportunities (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,          -- the industry user who posted it
    title VARCHAR(150) NOT NULL,
    type VARCHAR(50),
    category VARCHAR(100),
    work_mode VARCHAR(50),
    description TEXT,
    skills TEXT,                   -- comma-separated skill list (free text)
    location VARCHAR(150),
    openings INT DEFAULT 1,
    duration VARCHAR(100),
    stipend VARCHAR(100),
    experience VARCHAR(100),
    eligibility TEXT,
    benefits TEXT,
    deadline DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- opportunities.php (apply) / applications.php / industry-dashboard.php
-- NOTE: `student_id` here stores a users.id value directly — it does
-- NOT reference student_profiles.student_id. Kept the existing name
-- to avoid touching every file that already queries it.
CREATE TABLE IF NOT EXISTS applications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    opportunity_id INT NOT NULL,
    status ENUM('Applied','Under Review','Shortlisted','Interview','Selected','Rejected')
        DEFAULT 'Applied',
    applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (opportunity_id) REFERENCES opportunities(id) ON DELETE CASCADE
);

-- research-projects.php
CREATE TABLE IF NOT EXISTS research_projects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,          -- the academician who owns it
    title VARCHAR(200) NOT NULL,
    description TEXT,
    technologies VARCHAR(255),
    status VARCHAR(50),
    project_type VARCHAR(50),
    duration VARCHAR(100),
    category VARCHAR(100),
    progress INT DEFAULT 0,
    members INT DEFAULT 1,
    industry_partner VARCHAR(150),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- collaboration.php — direct messages between two users, not the
-- dump's company<->academician collaboration-project table.
CREATE TABLE IF NOT EXISTS collaborations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sender_id INT NOT NULL,
    receiver_id INT NOT NULL,
    title VARCHAR(200),
    message TEXT,
    status VARCHAR(50) DEFAULT 'Sent',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Added for portfolio.php (new file — nothing referenced these tables
-- before, so they're defined fresh here, following the same flat
-- user_id convention as everything above).
CREATE TABLE IF NOT EXISTS portfolios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    headline VARCHAR(200),
    about TEXT,
    resume_url VARCHAR(255),
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS projects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(150) NOT NULL,
    description TEXT,
    technologies VARCHAR(255),
    project_url VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS certifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    name VARCHAR(150) NOT NULL,
    organization VARCHAR(150),
    issue_date DATE,
    certificate_url VARCHAR(255),
    verified BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- If you already ran the old dump: drop and recreate is simplest for
-- a dev database with no real user data yet —
--   DROP DATABASE skillbridge;
-- then re-run this whole file. If you have real data you need to
-- keep, tell me and I'll write ALTER statements instead of DROP/CREATE.

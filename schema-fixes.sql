-- SkillBridge schema fix
--
-- The SQL dump you ran created the users table with `user_id` as the
-- primary key column name. But almost every PHP page in this project
-- (industry-dashboard.php, research-projects.php, skill-gap.php,
-- collaboration.php, applications.php, opportunities.php,
-- candidate-matching.php, post-opportunity.php, skill-profile.php)
-- queries it as `id`:
--
--     SELECT name, role FROM users WHERE id = ?
--
-- Only login.php/signup.php (now fixed) disagreed and referenced
-- `full_name`, which never existed at all. Since the majority of the
-- app expects `id`, run this against your existing database:

ALTER TABLE users CHANGE COLUMN user_id id INT AUTO_INCREMENT;

-- If you're setting up the database fresh instead of patching an
-- existing one, just create the table with `id` directly:
--
-- CREATE TABLE users (
--     id INT AUTO_INCREMENT PRIMARY KEY,
--     name VARCHAR(100) NOT NULL,
--     email VARCHAR(100) UNIQUE NOT NULL,
--     password VARCHAR(255) NOT NULL,
--     role ENUM('student','industry','academician','institution') NOT NULL,
--     created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
-- );

-- signup.php now stores `role` in lowercase ('student', 'industry',
-- 'academician', 'institution') to match this ENUM and to match every
-- page's role checks (e.g. research-projects.php checks
-- $user['role'] === 'academician'). If you already have rows with
-- capitalized roles from testing, normalize them:

UPDATE users SET role = LOWER(role);

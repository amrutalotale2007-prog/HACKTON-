<?php

session_start();

/* =========================
   DATABASE CONNECTION
========================= */

require_once "config/database.php";


/* =========================
   CREATE ACCOUNT (SIGN IN / REGISTER)
========================= */

$error_message = "";
$success_message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    /* =========================
       GET FORM DATA
    ========================= */

    $full_name = isset($_POST["full_name"])
        ? trim($_POST["full_name"])
        : "";

    $email = isset($_POST["email"])
        ? trim($_POST["email"])
        : "";

    $user_password = isset($_POST["password"])
        ? $_POST["password"]
        : "";

    $role = isset($_POST["role"])
        ? strtolower(trim($_POST["role"]))
        : "";


    /* =========================
       VALIDATE EMPTY FIELDS
    ========================= */

    if (
        $full_name === "" ||
        $email === "" ||
        $user_password === "" ||
        $role === ""
    ) {
        $error_message = "Please fill all fields.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        /* =========================
           VALIDATE EMAIL
        ========================= */
        $error_message = "Please enter a valid email address.";
    } elseif (strlen($user_password) < 6) {
        /* =========================
           VALIDATE PASSWORD
        ========================= */
        $error_message = "Password must be at least 6 characters.";
    } else {
        /* =========================
           VALIDATE ROLE
        ========================= */
        $allowed_roles = array(
            "student",
            "industry",
            "academician",
            "institution"
        );

        if (!in_array($role, $allowed_roles, true)) {
            $error_message = "Please select a valid role.";
        } else {
            /* =========================
               CHECK EMAIL ALREADY EXISTS (PREPARED STATEMENT)
            ========================= */
            $check = $conn->prepare(
                "SELECT id FROM users WHERE email = ? LIMIT 1"
            );

            if (!$check) {
                die("Database error: " . $conn->error);
            }

            $check->bind_param("s", $email);
            $check->execute();
            $check->store_result();

            if ($check->num_rows > 0) {
                $check->close();
                $error_message = "Email already registered. Please login.";
            } else {
                $check->close();

                /* =========================
                   HASH PASSWORD
                ========================= */
                $hashed_password = password_hash(
                    $user_password,
                    PASSWORD_DEFAULT
                );

                /* =========================
                   INSERT USER (PREPARED STATEMENT)
                   Dynamically handles schema whether name or full_name column is used
                ========================= */
                $columns = [];
                $colResult = $conn->query("SHOW COLUMNS FROM users");
                if ($colResult) {
                    while ($col = $colResult->fetch_assoc()) {
                        $columns[] = $col['Field'];
                    }
                }

                if (in_array('name', $columns, true) && in_array('full_name', $columns, true)) {
                    $stmt = $conn->prepare(
                        "INSERT INTO users (name, full_name, email, password, role) VALUES (?, ?, ?, ?, ?)"
                    );
                    $stmt->bind_param("sssss", $full_name, $full_name, $email, $hashed_password, $role);
                } elseif (in_array('full_name', $columns, true) && !in_array('name', $columns, true)) {
                    $stmt = $conn->prepare(
                        "INSERT INTO users (full_name, email, password, role) VALUES (?, ?, ?, ?)"
                    );
                    $stmt->bind_param("ssss", $full_name, $email, $hashed_password, $role);
                } else {
                    $stmt = $conn->prepare(
                        "INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)"
                    );
                    $stmt->bind_param("ssss", $full_name, $email, $hashed_password, $role);
                }

                if (!$stmt) {
                    die("Database error: " . $conn->error);
                }

                if ($stmt->execute()) {
                    $new_user_id = $stmt->insert_id;
                    $stmt->close();

                    /* Save session */
                    $_SESSION["user_id"]   = $new_user_id;
                    $_SESSION["full_name"] = $full_name;
                    $_SESSION["name"]      = $full_name;
                    $_SESSION["email"]     = $email;
                    $_SESSION["role"]      = $role;

                    echo "<script>
                            alert('Account created successfully! Please login.');
                            window.location.href = 'login.php';
                          </script>";
                    exit();
                } else {
                    $error_message = "Account creation failed: " . $stmt->error;
                    $stmt->close();
                }
            }
        }
    }

    if (!empty($error_message)) {
        echo "<script>
                alert('" . addslashes($error_message) . "');
              </script>";
    }
}

?>
<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Create Account - SkillBridge</title>

    <!-- Bootstrap -->
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <!-- Custom CSS -->
    <link
        rel="stylesheet"
        href="style.css">

</head>

<body class="auth-page">

<div class="auth-wrapper">

    <!-- =========================
         LEFT SIDE
    ========================== -->
    <div class="auth-brand">

        <div class="auth-logo">
            Skill<span>Bridge</span>
        </div>

        <h1>
            Start Your
            <br>
            <span>Career Journey</span>
        </h1>

        <p>
            Join students, academicians and
            industries on one platform.
        </p>

        <div class="auth-illustration">
            <i class="bi bi-person-plus-fill"></i>
        </div>

    </div>

    <!-- =========================
         RIGHT SIDE
    ========================== -->
    <div class="auth-form-area">

        <div class="auth-box">

            <h2>
                Create Account
            </h2>

            <p>
                Create your SkillBridge profile
            </p>

            <!-- =========================
                 SIGN IN / REGISTRATION FORM
            ========================== -->
            <form
                method="POST"
                action="signin.php">

                <!-- FULL NAME -->
                <label for="full_name">
                    Full Name
                </label>

                <input
                    type="text"
                    id="full_name"
                    name="full_name"
                    class="form-control"
                    placeholder="Enter full name"
                    value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>"
                    required>

                <!-- EMAIL -->
                <label for="email">
                    Email
                </label>

                <input
                    type="email"
                    id="email"
                    name="email"
                    class="form-control"
                    placeholder="Enter email"
                    value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                    required>

                <!-- PASSWORD -->
                <label for="password">
                    Password
                </label>

                <input
                    type="password"
                    id="password"
                    name="password"
                    class="form-control"
                    placeholder="Create password"
                    minlength="6"
                    required>

                <!-- ROLE -->
                <label for="role">
                    Select Your Role
                </label>

                <select
                    id="role"
                    name="role"
                    class="form-select"
                    required>

                    <option value="">
                        Choose role
                    </option>

                    <option value="Student" <?= (isset($_POST['role']) && strtolower($_POST['role']) === 'student') ? 'selected' : '' ?>>
                        Student
                    </option>

                    <option value="Industry" <?= (isset($_POST['role']) && strtolower($_POST['role']) === 'industry') ? 'selected' : '' ?>>
                        Industry
                    </option>

                    <option value="Academician" <?= (isset($_POST['role']) && strtolower($_POST['role']) === 'academician') ? 'selected' : '' ?>>
                        Academician
                    </option>

                    <option value="Institution" <?= (isset($_POST['role']) && strtolower($_POST['role']) === 'institution') ? 'selected' : '' ?>>
                        Institution
                    </option>

                </select>

                <!-- BUTTON -->
                <button
                    type="submit"
                    class="btn btn-purple w-100 mt-4">
                    Create Account
                </button>

            </form>

            <!-- LOGIN -->
            <p class="text-center mt-4">
                Already have an account?
                <a href="login.php">
                    Login
                </a>
            </p>

            <a href="index.php" class="back-link">
                ← Back to Home
            </a>

        </div>

    </div>

</div>

<!-- JavaScript -->
<script src="script.js"></script>

</body>

</html>

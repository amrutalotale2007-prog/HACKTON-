<?php

session_start();

require_once "config/database.php";


/* =========================
   HANDLE LOGIN
========================= */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email = trim($_POST["email"] ?? "");
    $user_password = $_POST["password"] ?? "";

    if (empty($email) || empty($user_password)) {

        echo "<script>
                alert('Please enter email and password.');
              </script>";

    } else {

        /* Prepared statement to find user by email */
        $stmt = $conn->prepare(
            "SELECT * FROM users WHERE email = ? LIMIT 1"
        );

        if (!$stmt) {
            die("Database error: " . $conn->error);
        }

        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {

            $user = $result->fetch_assoc();

            /* Verify hashed password */
            if (password_verify($user_password, $user["password"])) {

                $user_id   = $user["id"] ?? $user["user_id"] ?? 0;
                $full_name = $user["full_name"] ?? $user["name"] ?? "";
                $role      = strtolower($user["role"] ?? "student");

                /* Store user_id, full_name, email and role in PHP session (Requirement 5) */
                $_SESSION["user_id"]   = (int) $user_id;
                $_SESSION["full_name"] = $full_name;
                $_SESSION["name"]      = $full_name; // backwards-compatible alias
                $_SESSION["email"]     = $user["email"];
                $_SESSION["role"]      = $role;

                $stmt->close();

                /* Redirect user to correct dashboard (Requirement 6) */
                switch ($role) {

                    case "academician":
                        $target = "academician-dashboard.php";
                        break;

                    case "industry":
                        $target = "industry-dashboard.php";
                        break;

                    case "student":
                    case "institution":
                    default:
                        $target = "dashboard.php";
                        break;
                }

                header("Location: " . $target);
                echo "<script>window.location.href = '" . $target . "';</script>";
                exit();

            } else {

                echo "<script>
                        alert('Incorrect password.');
                      </script>";

            }

        } else {

            echo "<script>
                    alert('Email not found.');
                  </script>";

        }

        $stmt->close();
    }
}

?>
<!DOCTYPE html>
<html>

<head>

<meta charset="UTF-8">

<meta name="viewport"
      content="width=device-width, initial-scale=1.0">

<title>Login - SkillBridge</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
      rel="stylesheet">

<link rel="stylesheet"
      href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

<link rel="stylesheet"
      href="style.css">

</head>

<body class="auth-page">

<div class="auth-wrapper">

    <div class="auth-brand">

        <div class="auth-logo">
            Skill<span>Bridge</span>
        </div>

        <h1>
            Connecting Talent
            <br>
            with <span>Opportunities</span>
        </h1>

        <p>
            Build skills. Discover opportunities.
            Connect with industry.
        </p>

        <div class="auth-illustration">
            <i class="bi bi-people-fill"></i>
        </div>

    </div>


    <div class="auth-form-area">

        <div class="auth-box">

            <div class="text-center mb-4">

                <div class="auth-icon">
                    <i class="bi bi-person"></i>
                </div>

                <h2>Welcome Back!</h2>

                <p>
                    Login to your SkillBridge account
                </p>

            </div>


            <form method="POST" action="login.php">

                <label>Email Address</label>

                <input
                    type="email"
                    name="email"
                    class="form-control"
                    placeholder="Enter your email"
                    value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                    required>


                <label>Password</label>

                <input
                    type="password"
                    name="password"
                    class="form-control"
                    placeholder="Enter your password"
                    required>


                
                <button type="submit" class="btn btn-purple w-100 mt-4">
                    Login
                    <i class="bi bi-arrow-right"></i>
                </button>

            </form>


            <div class="divider">
                <span>OR</span>
            </div>


            <p class="text-center">

                Don't have an account?

                <a href="signin.php">
                    Create Account
                </a>

            </p>


            <a href="index.php"
               class="back-link">

                ← Back to Home

            </a>

        </div>

    </div>

</div>

<script src="script.js"></script>

</body>
</html>

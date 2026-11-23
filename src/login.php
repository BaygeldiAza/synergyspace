<?php
session_start();

require_once __DIR__ . "/config/database.php";

$errors = [];
$email = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // Get form data
    $email = mb_strtolower(trim($_POST["email"] ?? ""));
    $password = $_POST["password"] ?? "";

    // Validate email
    if ($email === "") {
        $errors[] = "Please enter your email.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Please enter a valid email address.";
    }

    // Validate password
    if ($password === "") {
        $errors[] = "Please enter your password.";
    }

    if (empty($errors)) {

        try {

            // Find user
            $sql = "SELECT id, email, password_hash, role
                    FROM users
                    WHERE email = ?
                    LIMIT 1";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([$email]);

            $user = $stmt->fetch();
            error_log("LOGIN DEBUG: User found: " . ($user ? "YES" : "NO"));
            
            if ($user) {
                error_log("LOGIN DEBUG: User ID: " . $user["id"]);
                error_log("LOGIN DEBUG: Hash length: " . strlen($user["password_hash"]));
                error_log("LOGIN DEBUG: Verification: " .
                    (password_verify($password, $user["password_hash"]) ? "PASS" : "FAIL")
                );
            }

            // Check password
            if ($user && password_verify($password, $user["password_hash"])) {

                // Create a new session ID
                session_regenerate_id(true);

                // Save user information
                $_SESSION["user_id"] = $user["id"];
                $_SESSION["email"] = $user["email"];
                $_SESSION["role"] = $user["role"];

                // Redirect based on role
                if ($user["role"] === "owner") {
                    header("Location: owner/dashboard.php");
                } else {
                    header("Location: tenant/dashboard.php");
                }

                exit;

            } else {

                $errors[] = "Invalid email or password.";

            }

        } catch (PDOException $e) {

            error_log("Login database error: " . $e->getMessage());

            $errors[] = "Something went wrong. Please try again.";
        }
    }
}
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Login | SynergySpace</title>

    <link rel="stylesheet" href="css/style.css">
</head>

<body>

<div class="split-container">

    <!-- Left side -->
    <section class="split left">

        <div class="brand-content">

            <h1>SynergySpace</h1>

            <h2>Welcome Back!</h2>

            <p>
                Connect with professionals, discover coworking spaces,
                and continue building something great together.
            </p>

        </div>

    </section>


    <!-- Right side -->
    <section class="split right">

        <div class="auth-card">

            <h1>Sign In</h1>

            <p class="auth-subtitle">
                Enter your account details to continue.
            </p>


            <!-- Display errors -->
            <?php if (!empty($errors)): ?>

                <div class="error-message">

                    <?php foreach ($errors as $error): ?>

                        <p><?= htmlspecialchars($error) ?></p>

                    <?php endforeach; ?>

                </div>

            <?php endif; ?>


            <!-- Login form -->
            <form action="login.php" method="POST">

                <div class="form-group">

                    <label for="email">Email Address</label>

                    <input
                        type="email"
                        id="email"
                        name="email"
                        placeholder="Enter your email"
                        value="<?= htmlspecialchars($email) ?>"
                        required
                    >

                </div>


                <div class="form-group">

                    <label for="password">Password</label>

                    <input
                        type="password"
                        id="password"
                        name="password"
                        placeholder="Enter your password"
                        required
                    >

                </div>


                <button type="submit" class="auth-btn">
                    Sign In
                </button>

            </form>


            <p class="auth-switch">

                Don't have an account?

                <a href="register.php">Create an account</a>

            </p>

        </div>

    </section>

</div>

</body>
</html>



<?php

$errors = [];
$success = "";

// Process the form when submitted
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    error_log("=== NEW REGISTRATION TEST ===");

    error_log("POST contains password: " .
        (array_key_exists("password", $_POST) ? "YES" : "NO")
    );

    error_log("Password length immediately after POST: " .
        strlen($_POST["password"] ?? "")
    );

    error_log("Confirm password length: " .
        strlen($_POST["confirm_password"] ?? "")
    );
    // 1. Get form data
    $full_name = trim($_POST["full_name"] ?? "");
    $email = mb_strtolower(trim($_POST["email"] ?? ""));
    $password = $_POST["password"] ?? "";
    $confirm_password = $_POST["confirm_password"] ?? "";
    $role = $_POST["role"] ?? "";

    // 2. Validate full name
    if ($full_name === "") {
        $errors[] = "Full name is required.";
    }

    // 3. Validate email
    if ($email === "") {
        $errors[] = "Email is required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Please enter a valid email address.";
    }

    // 4. Validate password
    if ($password === "") {
        $errors[] = "Password is required.";
    } elseif (strlen($password) < 8) {
        $errors[] = "Password must contain at least 8 characters.";
    }

    // 5. Confirm password
    if ($confirm_password === "") {
        $errors[] = "Please confirm your password.";
    } elseif ($password !== $confirm_password) {
        $errors[] = "Passwords do not match.";
    }

    // 6. Validate account type
    if (!in_array($role, ["tenant", "owner"], true)) {
        $errors[] = "Please select a valid account type.";
    }

    // 7. Register user if validation succeeds
    if (empty($errors)) {

        require_once __DIR__ . "/config/database.php";

        try {

            // Check whether email already exists
            $checkEmail = $pdo->prepare(
                "SELECT id FROM users WHERE email = ? LIMIT 1"
            );

            $checkEmail->execute([$email]);

            if ($checkEmail->fetch()) {

                $errors[] = "This email is already registered.";

            }else {
                error_log("Password length before hashing: " . strlen($password));

                // 1. Generate password hash
                $password_hash = password_hash($password, PASSWORD_DEFAULT);

                error_log("REGISTER TEST: Password length: " . strlen($password));
                error_log("REGISTER TEST: Hash verification: " .
                    (password_verify($password, $password_hash) ? "PASS" : "FAIL")
                );
                // 2. Start transaction
                $pdo->beginTransaction();

                // 3. Insert user
                $insertUser = $pdo->prepare(
                    "INSERT INTO users (email, password_hash, role)
                    VALUES (?, ?, ?)"
                );

                $insertUser->execute([
                    $email,
                    $password_hash,
                    $role
                ]);

                // 4. Get the newly inserted user ID
                $user_id = $pdo->lastInsertId();

                // 5. Retrieve the stored password hash
                $checkHash = $pdo->prepare(
                    "SELECT password_hash FROM users WHERE id = ?"
                );

                $checkHash->execute([$user_id]);

                $stored_hash = $checkHash->fetchColumn();

                // 6. Verify the stored hash
                if (
                    $stored_hash === false ||
                    !password_verify($password, $stored_hash)
                ) {
                    throw new PDOException(
                        "Stored password hash verification failed."
                    );
                }

                // 7. Insert user profile
                $insertProfile = $pdo->prepare(
                    "INSERT INTO profiles (user_id, full_name)
                    VALUES (?, ?)"
                );

                $insertProfile->execute([
                    $user_id,
                    $full_name
                ]);

                // 8. Commit transaction
                $pdo->commit();

                $success = "Account created successfully! You can now log in.";

                $_POST = [];
            }

        } catch (PDOException $e) {

            // Roll back if something fails
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            $errors[] = "Registration failed. Please try again.";

            // Log actual error
            error_log("Registration error: " . $e->getMessage());
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Register - SynergySpace</title>

    <link rel="stylesheet" href="./css/style.css">
</head>

<body>

    <!-- Left welcome section -->
    <div class="split left">

        <div class="brand-content">

            <h1>Welcome to SynergySpace</h1>

            <h2>Working Better Together</h2>

            <p>
                Find suitable workspaces, connect with professionals,
                and build meaningful collaborations.
            </p>

        </div>

    </div>


    <!-- Right registration section -->
    <div class="split right">

        <div class="auth-card">

            <h1>Create Account</h1>

            <p>Join SynergySpace and start connecting.</p>


            <!-- Success message -->
            <?php if (!empty($success)): ?>

                <div class="success-message">
                    <?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?>
                </div>

            <?php endif; ?>


            <!-- Error messages -->
            <?php if (!empty($errors)): ?>

                <div class="error-message">

                    <ul>

                        <?php foreach ($errors as $error): ?>

                            <li>
                                <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
                            </li>

                        <?php endforeach; ?>

                    </ul>

                </div>

            <?php endif; ?>


            <!-- Registration form -->
            <form action="register.php" method="POST">

                <div class="form-group">

                    <label for="full_name">Full Name</label>

                    <input
                        type="text"
                        id="full_name"
                        name="full_name"
                        placeholder="Enter your full name"
                        value="<?= htmlspecialchars($_POST['full_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                        required
                    >

                </div>


                <div class="form-group">

                    <label for="email">Email Address</label>

                    <input
                        type="email"
                        id="email"
                        name="email"
                        placeholder="Enter your email"
                        value="<?= htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                        required
                    >

                </div>


                <div class="form-group">

                    <label for="password">Password</label>

                    <input
                        type="password"
                        id="password"
                        name="password"
                        placeholder="Create a password"
                        required
                    >

                </div>


                <div class="form-group">

                    <label for="confirm_password">Confirm Password</label>

                    <input
                        type="password"
                        id="confirm_password"
                        name="confirm_password"
                        placeholder="Confirm your password"
                        required
                    >

                </div>


                <div class="form-group">

                    <label>Account Type</label>

                    <div class="role-options">

                        <label>
                            <input
                                type="radio"
                                name="role"
                                value="tenant"
                                <?= (($_POST['role'] ?? '') === 'tenant') ? 'checked' : '' ?>
                                required
                            >
                            Tenant
                        </label>


                        <label>
                            <input
                                type="radio"
                                name="role"
                                value="owner"
                                <?= (($_POST['role'] ?? '') === 'owner') ? 'checked' : '' ?>
                                required
                            >
                            Owner
                        </label>

                    </div>

                </div>


                <button type="submit" class="auth-button">
                    Create Account
                </button>


                <p class="auth-footer">

                    Already have an account?

                    <a href="login.php">Login</a>

                </p>

            </form>

        </div>

    </div>

</body>

</html>

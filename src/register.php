
<?php

$errors = [];
$success = "";

// Process the form when submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // 1. Get form data
    $full_name = trim($_POST["full_name"] ?? "");
    $email = mb_strtolower(trim($_POST["email"] ?? ""));
    $password = $_POST["password"] ?? "";
    $confirm_password = $_POST["confirm_password"] ?? "";
    $role = $_POST["role"] ?? "";

    // 2. Validate full name
    if (empty($full_name)) {
        $errors[] = "Full name is required.";
    }

    // 3. Validate email
    if (empty($email)) {
        $errors[] = "Email is required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Please enter a valid email address.";
    }

    // 4. Validate password
    if (empty($password)) {
        $errors[] = "Password is required.";
    } elseif (strlen($password) < 8) {
        $errors[] = "Password must contain at least 8 characters.";
    }

    // 5. Confirm password
    if (empty($confirm_password)) {
        $errors[] = "Please confirm your password.";
    } elseif ($password !== $confirm_password) {
        $errors[] = "Passwords do not match.";
    }

    // 6. Validate account type
    if (!in_array($role, ["tenant", "owner"], true)) {
        $errors[] = "Please select a valid account type.";
    }

    // 7. Insert data if validation succeeds
    if (empty($errors)) {

        require_once __DIR__ . "/config/database.php";

        try {

            // Check if email already exists
            $checkEmail = $pdo->prepare(
                "SELECT id FROM users WHERE email = ?"
            );

            $checkEmail->execute([$email]);

            if ($checkEmail->fetch()) {

                $errors[] = "This email is already registered.";

            } else {

                // Hash the password
                $password_hash = password_hash(
                    $password,
                    PASSWORD_DEFAULT
                );

                // Start transaction
                $pdo->beginTransaction();

                // Insert into users table
                $insertUser = $pdo->prepare(
                    "INSERT INTO users (email, password_hash, role)
                     VALUES (?, ?, ?)"
                );

                $insertUser->execute([
                    $email,
                    $password_hash,
                    $role
                ]);

                // Get the newly created user ID
                $user_id = $pdo->lastInsertId();

                // Insert into profiles table
                $insertProfile = $pdo->prepare(
                    "INSERT INTO profiles (user_id, full_name)
                     VALUES (?, ?)"
                );

                $insertProfile->execute([
                    $user_id,
                    $full_name
                ]);

                // Save both insertions
                $pdo->commit();

                $success = "Account created successfully! You can now log in.";

                $_POST = [];

            }

        } catch (PDOException $e) {

            // Undo changes if something fails
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            $errors[] = "Registration failed. Please try again.";

            // Log actual error for debugging
            error_log($e->getMessage());
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

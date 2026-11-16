<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - SynergySpace</title>
    <link rel="stylesheet" href="./css/style.css">
</head>
<body>
    <div class="auth-container">

        <div class="auth-card">

            <h1>Create Account</h1>
            <p>Join SynergySpace and start connecting.</p>

            <form action="register.php" method="POST">
                <div class="form-group">
                    <label for="full_name">Full Name</label>
                    <input type="text" id="full_name" name="full_name" placeholder="Enter your full name" required>

                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" placeholder="Enter your email" required>

                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" placeholder="Create a password" required>

                    <label for="confirm_password">Confirm Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm your password" required>

                    <label>Account Type</label>

                    <div class="role-options">
                        <label>
                            <input type="radio" name="role" value="tenant" required>
                            Tenat
                        </label>

                        <label>
                            <input type="radio" name="role" value="owner" required>
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
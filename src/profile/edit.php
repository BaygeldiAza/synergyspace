<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/profile_functions.php';

requireLogin();
require_once __DIR__ . '/../config/database.php';

$account = getCurrentUserAccount($pdo);
if ($account === null) {
    session_destroy();
    header('Location: ../login.php');
    exit;
}

$profile = getCurrentUserProfile($pdo);
$errors = [];
$fullName = (string) ($profile['full_name'] ?? '');
$bio = (string) ($profile['bio'] ?? '');
$location = (string) ($profile['location'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach (['full_name' => 'Full name', 'bio' => 'Biography', 'location' => 'Location'] as $field => $label) {
        if (array_key_exists($field, $_POST) && !is_string($_POST[$field])) {
            $errors[] = $label . ' must be plain text.';
        }
    }

    $fullName = is_string($_POST['full_name'] ?? null) ? trim($_POST['full_name']) : '';
    $bio = is_string($_POST['bio'] ?? null) ? trim($_POST['bio']) : '';
    $location = is_string($_POST['location'] ?? null) ? trim($_POST['location']) : '';

    if (!profileVerifyCsrf($_POST['csrf_token'] ?? null)) {
        $errors[] = 'Your security token is invalid or expired. Please try again.';
    }

    if ($fullName === '') {
        $errors[] = 'Full name is required.';
    } elseif (mb_strlen($fullName) > 150) {
        $errors[] = 'Full name must be 150 characters or fewer.';
    }

    if (mb_strlen($bio) > 1000) {
        $errors[] = 'Biography must be 1000 characters or fewer.';
    }

    if (mb_strlen($location) > 255) {
        $errors[] = 'Location must be 255 characters or fewer.';
    }

    if (!$errors) {
        try {
            updateCurrentUserProfile($pdo, [
                'full_name' => $fullName,
                'bio' => $bio,
                'location' => $location,
            ]);

            $_SESSION['profile_success'] = 'Your profile has been updated.';
            header('Location: ./index.php');
            exit;
        } catch (InvalidArgumentException $e) {
            $errors[] = $e->getMessage();
        } catch (PDOException $e) {
            error_log('Profile edit database error: ' . $e->getMessage());
            $errors[] = 'Your profile could not be saved. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile | SynergySpace</title>
    <link rel="stylesheet" href="../css/style.css">
    <script src="https://kit.fontawesome.com/90b5aa2433.js" crossorigin="anonymous"></script>
</head>
<body class="profile-page">
<?php
$activePage = 'profile';
require_once __DIR__ . '/../includes/header.php';
?>
    <div class="profile-content">
        <div class="profile-overview-heading profile-edit-heading">
            <div>
                <span class="profile-eyebrow">Your account</span>
                <h1>Edit your profile</h1>
                <p>Update the details other SynergySpace members see about you.</p>
            </div>
            <a class="profile-cancel-button profile-back-link" href="./index.php">
                <i class="fas fa-arrow-left" aria-hidden="true"></i>
                Back to profile
            </a>
        </div>

        <?php if ($errors): ?>
            <div class="profile-alert profile-alert-error" role="alert">
                <i class="fas fa-circle-exclamation" aria-hidden="true"></i>
                <div>
                    <strong>Please review the following:</strong>
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?= profileEscape($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        <?php endif; ?>

        <form action="./edit.php" method="POST" class="profile-edit-form">
            <input type="hidden" name="csrf_token" value="<?= profileEscape(profileCsrfToken()) ?>">

            <section class="profile-edit-section" aria-labelledby="personalDetailsHeading">
                <div class="profile-section-heading">
                    <span><i class="fas fa-user-pen" aria-hidden="true"></i></span>
                    <div>
                        <h2 id="personalDetailsHeading">Personal details</h2>
                        <p>Your name, location, and biography help people get to know you.</p>
                    </div>
                </div>

                <div class="profile-form-grid">
                    <div class="profile-field profile-field-wide">
                        <label for="full_name">Full name <span aria-hidden="true">*</span></label>
                        <input
                            id="full_name"
                            name="full_name"
                            type="text"
                            maxlength="150"
                            autocomplete="name"
                            required
                            value="<?= profileEscape($fullName) ?>"
                            aria-invalid="<?= in_array('Full name is required.', $errors, true) || in_array('Full name must be 150 characters or fewer.', $errors, true) ? 'true' : 'false' ?>"
                        >
                        <small class="profile-input-hint">Up to 150 characters</small>
                    </div>

                    <div class="profile-field profile-field-wide">
                        <label for="location">Location</label>
                        <input
                            id="location"
                            name="location"
                            type="text"
                            maxlength="255"
                            autocomplete="address-level2"
                            placeholder="City, Country"
                            value="<?= profileEscape($location) ?>"
                        >
                        <small class="profile-input-hint">Optional · Up to 255 characters</small>
                    </div>

                    <div class="profile-field profile-field-wide">
                        <label for="bio">Biography</label>
                        <textarea
                            id="bio"
                            name="bio"
                            rows="7"
                            maxlength="1000"
                            placeholder="Share a little about your professional background and what you enjoy working on."
                        ><?= profileEscape($bio) ?></textarea>
                        <div class="profile-field-meta">
                            <span>A concise introduction works best.</span>
                            <span><b id="bioCount"><?= mb_strlen($bio) ?></b>/1000</span>
                        </div>
                    </div>
                </div>
            </section>

            <aside class="profile-readonly-card">
                <span class="profile-save-icon"><i class="fas fa-lock" aria-hidden="true"></i></span>
                <div>
                    <h2>Account details stay secure</h2>
                    <p>Email address and account role cannot be changed here.</p>
                </div>
                <dl>
                    <div>
                        <dt>Email</dt>
                        <dd><?= profileEscape($account['email']) ?></dd>
                    </div>
                    <div>
                        <dt>Role</dt>
                        <dd><?= profileEscape(ucfirst($account['role'])) ?></dd>
                    </div>
                </dl>
            </aside>

            <div class="profile-form-actions">
                <a class="profile-cancel-button" href="./index.php">Cancel</a>
                <button type="submit" class="profile-save-button">
                    <i class="fas fa-check" aria-hidden="true"></i>
                    Save Changes
                </button>
            </div>
        </form>
    </div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
<script src="../js/main.js"></script>
</body>
</html>

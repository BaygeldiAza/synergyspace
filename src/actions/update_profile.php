<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/profile_functions.php';

requireLogin();
require_once __DIR__ . '/../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../profile/index.php');
    exit;
}

$userId = (int) $_SESSION['user_id'];
$token = $_POST['csrf_token'] ?? '';

if (!profileVerifyCsrf($token)) {
    $_SESSION['profile_error'] = 'Your session expired. Please try again.';
    header('Location: ../profile/index.php?edit=1');
    exit;
}

$fullName = trim((string) ($_POST['full_name'] ?? ''));
$bio = trim((string) ($_POST['bio'] ?? ''));
$location = trim((string) ($_POST['location'] ?? ''));
$skillIds = isset($_POST['skills']) && is_array($_POST['skills'])
    ? array_values(array_unique(array_map('intval', $_POST['skills'])))
    : [];
$interestIds = isset($_POST['interests']) && is_array($_POST['interests'])
    ? array_values(array_unique(array_map('intval', $_POST['interests'])))
    : [];

if ($fullName === '') {
    $_SESSION['profile_error'] = 'Full name is required.';
    header('Location: ../profile/index.php?edit=1');
    exit;
}

if (mb_strlen($fullName) > 150) {
    $_SESSION['profile_error'] = 'Full name must be 150 characters or fewer.';
    header('Location: ../profile/index.php?edit=1');
    exit;
}

if (mb_strlen($bio) > 1000) {
    $_SESSION['profile_error'] = 'Professional bio must be 1000 characters or fewer.';
    header('Location: ../profile/index.php?edit=1');
    exit;
}

if (mb_strlen($location) > 255) {
    $_SESSION['profile_error'] = 'Location must be 255 characters or fewer.';
    header('Location: ../profile/index.php?edit=1');
    exit;
}

$profileImagePath = null;
$oldImagePath = null;

try {
    $oldStmt = $pdo->prepare('SELECT profile_image FROM profiles WHERE user_id = ? LIMIT 1');
    $oldStmt->execute([$userId]);
    $oldImagePath = $oldStmt->fetchColumn();

    if (array_key_exists('profile_image', $_FILES)) {
        $imageUpload = $_FILES['profile_image'];
        if (!is_array($imageUpload) || !is_int($imageUpload['error'] ?? null)) {
            throw new InvalidArgumentException('The image upload is invalid.');
        }
        if ($imageUpload['error'] !== UPLOAD_ERR_NO_FILE) {
            $profileImagePath = storeCurrentUserProfileImage($imageUpload);
        }
    }

    $pdo->beginTransaction();

    updateCurrentUserProfile($pdo, [
        'full_name' => $fullName,
        'bio' => $bio,
        'location' => $location,
        'profile_image' => $profileImagePath,
    ]);

    $pdo->prepare('DELETE FROM user_skills WHERE user_id = ?')->execute([$userId]);
    $pdo->prepare('DELETE FROM user_interests WHERE user_id = ?')->execute([$userId]);

    if ($skillIds) {
        $skillCheck = $pdo->prepare('SELECT id FROM skills WHERE id = ?');
        $skillInsert = $pdo->prepare('INSERT INTO user_skills (user_id, skill_id) VALUES (?, ?)');
        foreach ($skillIds as $skillId) {
            if ($skillId > 0) {
                $skillCheck->execute([$skillId]);
                if ($skillCheck->fetchColumn()) {
                    $skillInsert->execute([$userId, $skillId]);
                }
            }
        }
    }

    if ($interestIds) {
        $interestCheck = $pdo->prepare('SELECT id FROM interests WHERE id = ?');
        $interestInsert = $pdo->prepare('INSERT INTO user_interests (user_id, interest_id) VALUES (?, ?)');
        foreach ($interestIds as $interestId) {
            if ($interestId > 0) {
                $interestCheck->execute([$interestId]);
                if ($interestCheck->fetchColumn()) {
                    $interestInsert->execute([$userId, $interestId]);
                }
            }
        }
    }

    $pdo->commit();

    if ($profileImagePath && is_string($oldImagePath) && $oldImagePath !== '') {
        if (!deleteCurrentUserProfileImage($oldImagePath)) {
            error_log('Previous profile image could not be deleted for user ' . $userId);
        }
    }

    $_SESSION['profile_success'] = 'Your profile has been updated.';
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    if ($profileImagePath) {
        deleteCurrentUserProfileImage($profileImagePath);
    }

    error_log('Profile update error: ' . $e->getMessage());
    if ($e instanceof InvalidArgumentException) {
        $_SESSION['profile_error'] = $e->getMessage();
    } elseif ($e instanceof PDOException) {
        $_SESSION['profile_error'] = 'Your profile could not be updated. Please try again.';
    } elseif ($e instanceof RuntimeException) {
        $_SESSION['profile_error'] = $e->getMessage();
    } else {
        $_SESSION['profile_error'] = 'Your profile could not be updated. Please try again.';
    }
}

header('Location: ../profile/index.php');
exit;

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

    if (!empty($_FILES['profile_image']) && $_FILES['profile_image']['error'] !== UPLOAD_ERR_NO_FILE) {
        $upload = $_FILES['profile_image'];

        if (!isset($upload['error']) || $upload['error'] !== UPLOAD_ERR_OK) {
            throw new RuntimeException('The profile photo could not be uploaded.');
        }

        if (!isset($upload['tmp_name']) || !is_uploaded_file($upload['tmp_name'])) {
            throw new RuntimeException('The profile photo upload was invalid.');
        }

        if (!isset($upload['size']) || $upload['size'] > 5 * 1024 * 1024) {
            throw new RuntimeException('The profile photo must be 5 MB or smaller.');
        }

        $imageInfo = getimagesize($upload['tmp_name']);
        $allowedTypes = [
            IMAGETYPE_JPEG => 'jpg',
            IMAGETYPE_PNG => 'png',
            IMAGETYPE_WEBP => 'webp',
        ];

        if ($imageInfo === false || !isset($allowedTypes[$imageInfo[2]])) {
            throw new RuntimeException('Please upload a JPG, PNG or WEBP image.');
        }

        $uploadDirectory = __DIR__ . '/../uploads/profiles';
        if (!is_dir($uploadDirectory) && !mkdir($uploadDirectory, 0755, true)) {
            throw new RuntimeException('The profile image directory is unavailable.');
        }

        $extension = $allowedTypes[$imageInfo[2]];
        $filename = 'profile_' . $userId . '_' . bin2hex(random_bytes(12)) . '.' . $extension;
        $destination = $uploadDirectory . '/' . $filename;

        if (!move_uploaded_file($upload['tmp_name'], $destination)) {
            throw new RuntimeException('The profile photo could not be saved.');
        }

        $profileImagePath = 'uploads/profiles/' . $filename;
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
        $oldImageName = basename($oldImagePath);
        $oldImageFullPath = __DIR__ . '/../uploads/profiles/' . $oldImageName;
        if (is_file($oldImageFullPath)) {
            unlink($oldImageFullPath);
        }
    }

    $_SESSION['profile_success'] = 'Your profile has been updated.';
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    if ($profileImagePath) {
        $newImageFullPath = __DIR__ . '/../' . $profileImagePath;
        if (is_file($newImageFullPath)) {
            unlink($newImageFullPath);
        }
    }

    error_log('Profile update error: ' . $e->getMessage());
    $_SESSION['profile_error'] = $e instanceof RuntimeException
        ? $e->getMessage()
        : 'Your profile could not be updated. Please try again.';
}

header('Location: ../profile/index.php');
exit;

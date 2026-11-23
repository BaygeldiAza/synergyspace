<?php

function profileCurrentUserId()
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $userId = filter_var($_SESSION['user_id'] ?? null, FILTER_VALIDATE_INT);
    if ($userId === false || $userId === null || $userId < 1) {
        throw new RuntimeException('An authenticated user is required.');
    }

    return $userId;
}

function getCurrentUserAccount(PDO $pdo)
{
    $stmt = $pdo->prepare(
        'SELECT email, role, created_at
         FROM users
         WHERE id = ?
         LIMIT 1'
    );
    $stmt->execute([profileCurrentUserId()]);
    $account = $stmt->fetch();

    return $account ?: null;
}

function getCurrentUserProfile(PDO $pdo)
{
    $stmt = $pdo->prepare(
        'SELECT user_id, full_name, bio, location, profile_image, created_at, update_at
         FROM profiles
         WHERE user_id = ?
         LIMIT 1'
    );
    $stmt->execute([profileCurrentUserId()]);
    $profile = $stmt->fetch();

    return $profile ?: null;
}

function getCurrentUserSkills(PDO $pdo)
{
    $stmt = $pdo->prepare(
        'SELECT s.id, s.name
         FROM user_skills us
         INNER JOIN skills s ON s.id = us.skill_id
         WHERE us.user_id = ?
         ORDER BY s.name'
    );
    $stmt->execute([profileCurrentUserId()]);

    return $stmt->fetchAll();
}

function getCurrentUserInterests(PDO $pdo)
{
    $stmt = $pdo->prepare(
        'SELECT i.id, i.name
         FROM user_interests ui
         INNER JOIN interests i ON i.id = ui.interest_id
         WHERE ui.user_id = ?
         ORDER BY i.name'
    );
    $stmt->execute([profileCurrentUserId()]);

    return $stmt->fetchAll();
}

function updateCurrentUserProfile(PDO $pdo, array $profileData)
{
    $fullName = trim((string) ($profileData['full_name'] ?? ''));
    $bio = trim((string) ($profileData['bio'] ?? ''));
    $location = trim((string) ($profileData['location'] ?? ''));
    $profileImage = $profileData['profile_image'] ?? null;

    if ($fullName === '' || mb_strlen($fullName) > 150) {
        throw new InvalidArgumentException('Full name must be between 1 and 150 characters.');
    }

    if (mb_strlen($bio) > 1000) {
        throw new InvalidArgumentException('Bio must be 1000 characters or fewer.');
    }

    if (mb_strlen($location) > 255) {
        throw new InvalidArgumentException('Location must be 255 characters or fewer.');
    }

    if ($profileImage !== null && (!is_string($profileImage) || mb_strlen($profileImage) > 255)) {
        throw new InvalidArgumentException('Profile image path is invalid.');
    }

    $stmt = $pdo->prepare(
        'INSERT INTO profiles (user_id, full_name, bio, location, profile_image)
         VALUES (?, ?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE
             full_name = VALUES(full_name),
             bio = VALUES(bio),
             location = VALUES(location),
             profile_image = COALESCE(VALUES(profile_image), profile_image)'
    );
    $stmt->execute([
        profileCurrentUserId(),
        $fullName,
        $bio !== '' ? $bio : null,
        $location !== '' ? $location : null,
        $profileImage,
    ]);
}

function profileEscape($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function profileCsrfToken()
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (empty($_SESSION['profile_csrf'])) {
        $_SESSION['profile_csrf'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['profile_csrf'];
}

function profileVerifyCsrf($token)
{
    return is_string($token)
        && isset($_SESSION['profile_csrf'])
        && hash_equals($_SESSION['profile_csrf'], $token);
}

function profileInitials($fullName)
{
    $parts = preg_split('/\s+/', trim((string) $fullName));
    if (!$parts) {
        return 'U';
    }

    $initials = '';
    foreach (array_slice($parts, 0, 2) as $part) {
        $initials .= mb_strtoupper(mb_substr($part, 0, 1));
    }

    return $initials ?: 'U';
}

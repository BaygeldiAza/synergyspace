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

    if (
        $profileImage !== null
        && (!is_string($profileImage) || profileCurrentUserImageFilename($profileImage) === null)
    ) {
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

function profileImageUploadDirectory()
{
    $projectRoot = realpath(dirname(__DIR__));
    if ($projectRoot === false) {
        return null;
    }

    $expectedDirectory = $projectRoot . '/uploads/profiles';
    if (is_link($expectedDirectory)) {
        return null;
    }

    $realDirectory = realpath($expectedDirectory);
    if ($realDirectory === false || $realDirectory !== $expectedDirectory) {
        return null;
    }

    return $realDirectory;
}

function validateProfileImageFile($filePath, $originalName)
{
    if (!is_string($filePath) || !is_file($filePath) || !is_string($originalName)) {
        throw new InvalidArgumentException('The image upload is invalid.');
    }

    $actualSize = filesize($filePath);
    if ($actualSize === false) {
        throw new RuntimeException('The uploaded image could not be checked.');
    }
    if ($actualSize > 2 * 1024 * 1024) {
        throw new InvalidArgumentException('The image must be no larger than 2 MB.');
    }

    $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];
    if (!in_array($extension, $allowedExtensions, true)) {
        throw new InvalidArgumentException('Choose a JPG, JPEG, PNG, or WebP image.');
    }

    $fileInfo = finfo_open(FILEINFO_MIME_TYPE);
    if ($fileInfo === false) {
        throw new RuntimeException('Image type detection is unavailable.');
    }

    $actualMime = finfo_file($fileInfo, $filePath);
    finfo_close($fileInfo);

    $mimeToExtension = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];
    if ($actualMime === false || !isset($mimeToExtension[$actualMime])) {
        throw new InvalidArgumentException('Choose a valid JPG, JPEG, PNG, or WebP image.');
    }

    $imageInfo = getimagesize($filePath);
    if ($imageInfo === false || ($imageInfo['mime'] ?? null) !== $actualMime) {
        throw new InvalidArgumentException('The uploaded file is not a valid supported image.');
    }

    return $mimeToExtension[$actualMime];
}

function storeCurrentUserProfileImage(array $upload)
{
    $uploadError = $upload['error'] ?? null;
    if (!is_int($uploadError)) {
        throw new InvalidArgumentException('The image upload is invalid.');
    }

    if ($uploadError !== UPLOAD_ERR_OK) {
        if ($uploadError === UPLOAD_ERR_INI_SIZE || $uploadError === UPLOAD_ERR_FORM_SIZE) {
            throw new InvalidArgumentException('The image must be no larger than 2 MB.');
        }
        if ($uploadError === UPLOAD_ERR_PARTIAL) {
            throw new InvalidArgumentException('The image upload was incomplete. Please try again.');
        }
        if ($uploadError === UPLOAD_ERR_NO_FILE) {
            throw new InvalidArgumentException('Choose an image to upload.');
        }

        throw new RuntimeException('The image could not be uploaded. Please try again.');
    }

    $temporaryPath = $upload['tmp_name'] ?? null;
    if (!is_string($temporaryPath) || !is_uploaded_file($temporaryPath)) {
        throw new InvalidArgumentException('The image upload is invalid.');
    }

    $originalName = $upload['name'] ?? null;
    $canonicalExtension = validateProfileImageFile($temporaryPath, $originalName);

    $uploadDirectory = dirname(__DIR__) . '/uploads/profiles';
    if (!is_dir($uploadDirectory) && !mkdir($uploadDirectory, 0750, true) && !is_dir($uploadDirectory)) {
        throw new RuntimeException('The profile image directory is unavailable.');
    }

    $realDirectory = profileImageUploadDirectory();
    if ($realDirectory === null || !is_writable($realDirectory)) {
        throw new RuntimeException('The profile image directory is unavailable.');
    }

    $userId = profileCurrentUserId();
    $filename = 'profile_' . $userId . '_' . bin2hex(random_bytes(16)) . '.' . $canonicalExtension;
    $destination = $realDirectory . DIRECTORY_SEPARATOR . $filename;

    if (file_exists($destination) || is_link($destination)) {
        throw new RuntimeException('A unique profile image name could not be generated.');
    }

    if (!move_uploaded_file($temporaryPath, $destination)) {
        throw new RuntimeException('The profile image could not be saved.');
    }

    return 'uploads/profiles/' . $filename;
}

function profileCurrentUserImageFilename($storedPath)
{
    if (!is_string($storedPath)) {
        return null;
    }

    $userId = profileCurrentUserId();
    $pattern = '~\Auploads/profiles/profile_' . preg_quote((string) $userId, '~')
        . '_(?:[a-f0-9]{24}|[a-f0-9]{32})\.(?:jpg|jpeg|png|webp)\z~D';
    if (!preg_match($pattern, $storedPath)) {
        return null;
    }

    $filename = basename($storedPath);
    $directory = profileImageUploadDirectory();
    if ($directory === null) {
        return null;
    }

    $filePath = $directory . DIRECTORY_SEPARATOR . $filename;
    $realFilePath = realpath($filePath);
    if (
        $realFilePath === false
        || is_link($filePath)
        || !is_file($realFilePath)
        || dirname($realFilePath) !== $directory
    ) {
        return null;
    }

    return $filename;
}

function profileCurrentUserImageUrl($storedPath, $urlPrefix = '../uploads/profiles/')
{
    $filename = profileCurrentUserImageFilename($storedPath);
    if ($filename === null) {
        return null;
    }

    return $urlPrefix . rawurlencode($filename);
}

function deleteCurrentUserProfileImage($storedPath)
{
    $filename = profileCurrentUserImageFilename($storedPath);
    if ($filename === null) {
        return false;
    }

    $directory = profileImageUploadDirectory();
    if ($directory === null) {
        return false;
    }

    return unlink($directory . DIRECTORY_SEPARATOR . $filename);
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

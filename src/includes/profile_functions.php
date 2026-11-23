<?php

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

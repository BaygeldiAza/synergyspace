<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check whether the user is logged in
function requireLogin() {

    if (!isset($_SESSION["user_id"])) {
        header("Location: /synergyspace/src/login.php");
        exit;
    }
}

// Check whether the user has the correct role
function requireRole($role) {

    requireLogin();

    if (!isset($_SESSION["role"]) || $_SESSION["role"] !== $role) {
        header("Location: /synergyspace/src/index.php");
        exit;
    }
}

?>

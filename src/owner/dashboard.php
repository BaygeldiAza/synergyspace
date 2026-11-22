
<?php
require_once __DIR__ . "/../includes/auth_check.php";

requireRole("owner");
?>

<h1>Owner Dashboard</h1>

<p>Welcome, <?= htmlspecialchars($_SESSION["email"]) ?></p>

<a href="../logout.php">Logout</a>


<?php
require_once __DIR__ . "/../includes/auth_check.php";

requireRole("owner");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Owner Dashboard | SynergySpace</title>
    <link rel="stylesheet" href="../css/style.css">
    <script src="https://kit.fontawesome.com/90b5aa2433.js" crossorigin="anonymous"></script>
</head>
<body>
    <?php require_once __DIR__ . '/../includes/header.php'; ?>
    <section class="dashboard-welcome">
        <div>
            <span>Owner workspace</span>
            <h1>Welcome back</h1>
            <p>Manage your spaces and connect with tenants.</p>
        </div>
        <a class="profile-primary-action" href="../profile/index.php">View profile</a>
    </section>
    <section class="stats">
        <article class="stat-card searchable-card" data-search="available spaces">
            <div class="stat-icon"><i class="fas fa-building"></i></div>
            <h3>My Spaces</h3><p>0</p><span>Listed workspaces</span>
        </article>
        <article class="stat-card searchable-card" data-search="rental requests">
            <div class="stat-icon"><i class="fas fa-calendar-check"></i></div>
            <h3>Requests</h3><p>0</p><span>Waiting for review</span>
        </article>
    </section>
    <?php require_once __DIR__ . '/../includes/footer.php'; ?>
    <script src="../js/main.js"></script>
</body>
</html>

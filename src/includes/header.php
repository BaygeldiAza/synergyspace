
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$headerUserName = 'Guest';
$headerUserInitial = 'G';
$headerUserImage = null;
$headerProfileLink = '/synergyspace/src/login.php';
$headerAuthLink = '/synergyspace/src/login.php';
$headerAuthLabel = 'Login';

if (!empty($_SESSION['user_id'])) {
    $headerUserId = (int) $_SESSION['user_id'];
    $headerProfileLink = '/synergyspace/src/profile/index.php';
    $headerAuthLink = '/synergyspace/src/logout.php';
    $headerAuthLabel = 'Logout';

    require_once __DIR__ . '/../config/database.php';
    $headerProfileStmt = $pdo->prepare(
        'SELECT p.full_name, p.profile_image
         FROM profiles p
         WHERE p.user_id = ?
         LIMIT 1'
    );
    $headerProfileStmt->execute([$headerUserId]);
    $headerProfile = $headerProfileStmt->fetch();

    if ($headerProfile) {
        $headerUserName = $headerProfile['full_name'];
        $headerUserImage = !empty($headerProfile['profile_image'])
            ? '/synergyspace/src/' . ltrim($headerProfile['profile_image'], '/')
            : null;
        $headerParts = preg_split('/\s+/', trim($headerUserName));
        $headerUserInitial = mb_strtoupper(mb_substr($headerParts[0] ?? 'U', 0, 1));
    } else {
        $headerUserName = $_SESSION['email'] ?? 'Member';
        $headerUserInitial = mb_strtoupper(mb_substr($headerUserName, 0, 1));
    }
}

$activePage = $activePage ?? 'home';
?>

<div class="dashboard">
    <aside class="sidebar">
        <a class="logo" href="/synergyspace/src/index.php">
            <h2>Synergy<span>Space</span></h2>
            <p>Working Better Together</p>
        </a>

        <nav class="sidebar-nav" aria-label="Main navigation">
            <a href="/synergyspace/src/index.php" class="<?= $activePage === 'home' ? 'active' : '' ?>">
                <i class="fas fa-house"></i><span>Home</span>
            </a>
            <a href="/synergyspace/src/index.php#explore">
                <i class="fas fa-magnifying-glass"></i><span>Find Spaces</span>
            </a>
            <a href="/synergyspace/src/index.php#features">
                <i class="fas fa-briefcase"></i><span>My Spaces</span>
            </a>
            <a href="/synergyspace/src/index.php#network">
                <i class="fas fa-users"></i><span>My Network</span>
            </a>
            <a href="<?= $headerProfileLink ?>" class="<?= $activePage === 'profile' ? 'active' : '' ?>">
                <i class="fas fa-user"></i><span>My Profile</span>
            </a>
            <a href="/synergyspace/src/index.php#reviews">
                <i class="fas fa-star"></i><span>Ratings &amp; Reviews</span>
            </a>
            <a href="/synergyspace/src/index.php#messages">
                <i class="fas fa-message"></i><span>Messages</span>
            </a>
        </nav>

        <a class="sidebar-logout" href="<?= $headerAuthLink ?>">
            <i class="fas fa-right-from-bracket"></i><span><?= htmlspecialchars($headerAuthLabel) ?></span>
        </a>
    </aside>

    <div class="main-area">
        <header class="topbar">
            <label class="topbar-search" for="dashboardSearch">
                <i class="fas fa-magnifying-glass"></i>
                <input id="dashboardSearch" type="search" placeholder="Search spaces, skills or people">
            </label>

            <div class="user-info">
                <button class="icon notification-button" type="button" aria-label="Notifications">
                    <i class="fas fa-bell"></i>
                    <span class="notification-dot"></span>
                </button>
                <?php if ($headerUserImage): ?>
                    <img class="avatar" src="<?= htmlspecialchars($headerUserImage) ?>" alt="">
                <?php else: ?>
                    <span class="avatar"><?= htmlspecialchars($headerUserInitial) ?></span>
                <?php endif; ?>
                <a class="topbar-profile" href="<?= $headerProfileLink ?>">
                    <strong><?= htmlspecialchars($headerUserName) ?></strong>
                    <small>View profile</small>
                </a>
            </div>
        </header>

        <main class="content">

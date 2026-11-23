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
$profileFields = $profile ?? [];
$skills = getCurrentUserSkills($pdo);
$interests = getCurrentUserInterests($pdo);
$profileSuccess = $_SESSION['profile_success'] ?? '';
unset($_SESSION['profile_success']);

$fullName = trim((string) ($profileFields['full_name'] ?? ''));
$location = trim((string) ($profileFields['location'] ?? ''));
$bio = trim((string) ($profileFields['bio'] ?? ''));
$initials = $fullName !== '' ? profileInitials($fullName) : '';
$profileImage = !empty($profileFields['profile_image'])
    ? '../' . ltrim($profileFields['profile_image'], '/')
    : null;
$accountCreatedAt = null;
if (!empty($account['created_at'])) {
    $createdAtTimestamp = strtotime($account['created_at']);
    if ($createdAtTimestamp !== false) {
        $accountCreatedAt = date('F j, Y', $createdAtTimestamp);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile | SynergySpace</title>
    <link rel="stylesheet" href="../css/style.css">
    <script src="https://kit.fontawesome.com/90b5aa2433.js" crossorigin="anonymous"></script>
</head>
<body class="profile-page">
<?php
$activePage = 'profile';
require_once __DIR__ . '/../includes/header.php';
?>
    <div class="profile-content">
        <?php if ($profileSuccess !== ''): ?>
            <div class="profile-alert profile-alert-success" role="status">
                <i class="fas fa-circle-check" aria-hidden="true"></i>
                <?= profileEscape($profileSuccess) ?>
            </div>
        <?php endif; ?>

        <div class="profile-overview-heading">
            <div>
                <span class="profile-eyebrow">Your account</span>
                <h1>Profile overview</h1>
                <p>Keep your professional identity and community interests in one place.</p>
            </div>
        </div>

        <section class="profile-hero" aria-labelledby="profileName">
            <div class="profile-hero-cover"></div>
            <div class="profile-hero-body">
                <div class="profile-photo-wrap">
                    <?php if ($profileImage): ?>
                        <img
                            src="<?= profileEscape($profileImage) ?>"
                            alt="<?= $fullName !== '' ? profileEscape($fullName) . ' profile photo' : 'Profile photo' ?>"
                            class="profile-photo"
                        >
                    <?php elseif ($initials !== ''): ?>
                        <div class="profile-photo profile-photo-initials" aria-label="Default avatar">
                            <?= profileEscape($initials) ?>
                        </div>
                    <?php else: ?>
                        <div class="profile-photo profile-photo-initials profile-photo-default" aria-label="Default avatar">
                            <i class="fas fa-user" aria-hidden="true"></i>
                        </div>
                    <?php endif; ?>
                    <span class="profile-status-dot" title="SynergySpace member"></span>
                </div>

                <div class="profile-identity">
                    <div class="profile-name-row">
                        <h2 id="profileName">
                            <?= $fullName !== '' ? profileEscape($fullName) : 'Name not provided' ?>
                        </h2>
                        <span class="profile-role-badge"><?= profileEscape(ucfirst($account['role'])) ?></span>
                    </div>
                    <p class="profile-email">
                        <i class="fas fa-envelope" aria-hidden="true"></i>
                        <?= profileEscape($account['email']) ?>
                    </p>
                    <p class="profile-location">
                        <i class="fas fa-location-dot" aria-hidden="true"></i>
                        <?= $location !== '' ? profileEscape($location) : 'Location not provided' ?>
                    </p>
                </div>

                <div class="profile-edit-action">
                    <a href="./edit.php" class="profile-primary-action">
                        <i class="fas fa-pen" aria-hidden="true"></i>
                        Edit Profile
                    </a>
                </div>
            </div>
        </section>

        <?php if ($profile === null): ?>
            <div class="profile-alert profile-alert-info">
                <i class="fas fa-circle-info" aria-hidden="true"></i>
                Profile details have not been added yet.
            </div>
        <?php endif; ?>

        <div class="profile-grid">
            <section class="profile-card profile-about-card" aria-labelledby="aboutHeading">
                <div class="profile-card-heading">
                    <div>
                        <span>About</span>
                        <h2 id="aboutHeading">Professional biography</h2>
                    </div>
                    <span class="profile-section-icon"><i class="fas fa-feather-pointed" aria-hidden="true"></i></span>
                </div>
                <?php if ($bio !== ''): ?>
                    <p class="profile-bio"><?= nl2br(profileEscape($bio)) ?></p>
                <?php else: ?>
                    <div class="profile-empty">
                        <i class="fas fa-feather-pointed" aria-hidden="true"></i>
                        <p>No biography added yet.</p>
                        <span>Your bio will appear here when available.</span>
                    </div>
                <?php endif; ?>
            </section>

            <section class="profile-card profile-details-card" aria-labelledby="detailsHeading">
                <div class="profile-card-heading">
                    <div>
                        <span>Account</span>
                        <h2 id="detailsHeading">Profile details</h2>
                    </div>
                    <span class="profile-section-icon"><i class="fas fa-id-card" aria-hidden="true"></i></span>
                </div>
                <dl class="profile-details-list">
                    <div>
                        <dt><i class="fas fa-user" aria-hidden="true"></i>Full name</dt>
                        <dd><?= $fullName !== '' ? profileEscape($fullName) : 'Not provided' ?></dd>
                    </div>
                    <div>
                        <dt><i class="fas fa-envelope" aria-hidden="true"></i>Email address</dt>
                        <dd><?= profileEscape($account['email']) ?></dd>
                    </div>
                    <div>
                        <dt><i class="fas fa-user-tag" aria-hidden="true"></i>Account role</dt>
                        <dd><?= profileEscape(ucfirst($account['role'])) ?></dd>
                    </div>
                    <div>
                        <dt><i class="fas fa-location-dot" aria-hidden="true"></i>Location</dt>
                        <dd><?= $location !== '' ? profileEscape($location) : 'Not provided' ?></dd>
                    </div>
                    <div>
                        <dt><i class="fas fa-calendar-check" aria-hidden="true"></i>Member since</dt>
                        <dd><?= $accountCreatedAt !== null ? profileEscape($accountCreatedAt) : 'Date unavailable' ?></dd>
                    </div>
                </dl>
            </section>

            <section class="profile-card profile-skills-card" aria-labelledby="skillsHeading">
                <div class="profile-card-heading">
                    <div>
                        <span>Expertise</span>
                        <h2 id="skillsHeading">Skills</h2>
                    </div>
                    <span class="profile-count"><?= count($skills) ?></span>
                </div>
                <?php if ($skills): ?>
                    <div class="profile-tag-list">
                        <?php foreach ($skills as $skill): ?>
                            <span class="profile-tag profile-tag-skill">
                                <i class="fas fa-check" aria-hidden="true"></i>
                                <?= profileEscape($skill['name']) ?>
                            </span>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="profile-empty profile-empty-inline">
                        <p>No skills listed yet.</p>
                    </div>
                <?php endif; ?>
            </section>

            <section class="profile-card profile-interests-card" aria-labelledby="interestsHeading">
                <div class="profile-card-heading">
                    <div>
                        <span>Community</span>
                        <h2 id="interestsHeading">Interests</h2>
                    </div>
                    <span class="profile-count"><?= count($interests) ?></span>
                </div>
                <?php if ($interests): ?>
                    <div class="profile-tag-list">
                        <?php foreach ($interests as $interest): ?>
                            <span class="profile-tag profile-tag-interest">
                                <i class="fas fa-sparkles" aria-hidden="true"></i>
                                <?= profileEscape($interest['name']) ?>
                            </span>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="profile-empty profile-empty-inline">
                        <p>No interests listed yet.</p>
                    </div>
                <?php endif; ?>
            </section>
        </div>
    </div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>

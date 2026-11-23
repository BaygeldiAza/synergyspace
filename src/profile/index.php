<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/profile_functions.php';

requireLogin();
require_once __DIR__ . '/../config/database.php';

$userId = (int) $_SESSION['user_id'];
$isEditing = isset($_GET['edit']) && $_GET['edit'] === '1';

$profileStmt = $pdo->prepare(
    'SELECT p.full_name, p.bio, p.location, p.profile_image, p.created_at, p.update_at,
            u.email, u.role
     FROM profiles p
     INNER JOIN users u ON u.id = p.user_id
     WHERE p.user_id = ?
     LIMIT 1'
);
$profileStmt->execute([$userId]);
$profile = $profileStmt->fetch();

if (!$profile) {
    $pdo->beginTransaction();
    $nameStmt = $pdo->prepare('SELECT email FROM users WHERE id = ? LIMIT 1');
    $nameStmt->execute([$userId]);
    $email = (string) $nameStmt->fetchColumn();
    $fallbackName = strstr($email, '@', true) ?: 'SynergySpace Member';

    $createProfile = $pdo->prepare(
        'INSERT INTO profiles (user_id, full_name) VALUES (?, ?)'
    );
    $createProfile->execute([$userId, $fallbackName]);
    $pdo->commit();

    $profileStmt->execute([$userId]);
    $profile = $profileStmt->fetch();
}

$skillsStmt = $pdo->query(
    'SELECT id, name FROM skills ORDER BY name'
);
$allSkills = $skillsStmt->fetchAll();

$interestsStmt = $pdo->query(
    'SELECT id, name FROM interests ORDER BY name'
);
$allInterests = $interestsStmt->fetchAll();

$selectedSkillsStmt = $pdo->prepare(
    'SELECT s.id, s.name
     FROM user_skills us
     INNER JOIN skills s ON s.id = us.skill_id
     WHERE us.user_id = ?
     ORDER BY s.name'
);
$selectedSkillsStmt->execute([$userId]);
$selectedSkills = $selectedSkillsStmt->fetchAll();

$selectedInterestsStmt = $pdo->prepare(
    'SELECT i.id, i.name
     FROM user_interests ui
     INNER JOIN interests i ON i.id = ui.interest_id
     WHERE ui.user_id = ?
     ORDER BY i.name'
);
$selectedInterestsStmt->execute([$userId]);
$selectedInterests = $selectedInterestsStmt->fetchAll();

$selectedSkillIds = array_column($selectedSkills, 'id');
$selectedInterestIds = array_column($selectedInterests, 'id');
$initials = profileInitials($profile['full_name']);
$profileImage = !empty($profile['profile_image'])
    ? '../' . ltrim($profile['profile_image'], '/')
    : null;

$flashSuccess = $_SESSION['profile_success'] ?? '';
$flashError = $_SESSION['profile_error'] ?? '';
unset($_SESSION['profile_success'], $_SESSION['profile_error']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $isEditing ? 'Edit Profile' : 'My Profile' ?> | SynergySpace</title>
    <link rel="stylesheet" href="../css/style.css">
    <script src="https://kit.fontawesome.com/90b5aa2433.js" crossorigin="anonymous"></script>
</head>
<body class="profile-page">
<?php
$activePage = 'profile';
require_once __DIR__ . '/../includes/header.php';
?>
        <div class="profile-content">
            <?php if ($flashSuccess): ?>
                <div class="profile-alert profile-alert-success">
                    <i class="fas fa-circle-check"></i>
                    <?= profileEscape($flashSuccess) ?>
                </div>
            <?php endif; ?>

            <?php if ($flashError): ?>
                <div class="profile-alert profile-alert-error">
                    <i class="fas fa-circle-exclamation"></i>
                    <?= profileEscape($flashError) ?>
                </div>
            <?php endif; ?>

            <?php if (!$isEditing): ?>
                <section class="profile-hero">
                    <div class="profile-hero-cover"></div>
                    <div class="profile-hero-body">
                        <div class="profile-photo-wrap">
                            <?php if ($profileImage): ?>
                                <img src="<?= profileEscape($profileImage) ?>" alt="<?= profileEscape($profile['full_name']) ?>" class="profile-photo">
                            <?php else: ?>
                                <div class="profile-photo profile-photo-initials"><?= profileEscape($initials) ?></div>
                            <?php endif; ?>
                            <span class="profile-status-dot" title="Active"></span>
                        </div>

                        <div class="profile-identity">
                            <div class="profile-name-row">
                                <h2><?= profileEscape($profile['full_name']) ?></h2>
                                <span class="profile-role-badge"><?= profileEscape(ucfirst($profile['role'])) ?></span>
                            </div>
                            <?php if (!empty($profile['location'])): ?>
                                <p class="profile-location"><i class="fas fa-location-dot"></i><?= profileEscape($profile['location']) ?></p>
                            <?php endif; ?>
                            <p class="profile-email"><i class="fas fa-envelope"></i><?= profileEscape($profile['email']) ?></p>
                        </div>

                        <a href="./index.php?edit=1" class="profile-primary-action">
                            <i class="fas fa-pen"></i>
                            Edit profile
                        </a>
                    </div>
                </section>

                <div class="profile-grid">
                    <section class="profile-card profile-about-card">
                        <div class="profile-card-heading">
                            <div>
                                <span>About</span>
                                <h2>Professional summary</h2>
                            </div>
                            <a href="./index.php?edit=1" aria-label="Edit summary"><i class="fas fa-pen"></i></a>
                        </div>
                        <?php if (!empty($profile['bio'])): ?>
                            <p class="profile-bio"><?= nl2br(profileEscape($profile['bio'])) ?></p>
                        <?php else: ?>
                            <div class="profile-empty">
                                <i class="fas fa-feather-pointed"></i>
                                <p>Add a short bio to help people get to know you.</p>
                                <a href="./index.php?edit=1">Write your bio</a>
                            </div>
                        <?php endif; ?>
                    </section>

                    <section class="profile-card profile-details-card">
                        <div class="profile-card-heading">
                            <div>
                                <span>Details</span>
                                <h2>Profile information</h2>
                            </div>
                        </div>
                        <dl class="profile-details-list">
                            <div>
                                <dt><i class="fas fa-user"></i>Full name</dt>
                                <dd><?= profileEscape($profile['full_name']) ?></dd>
                            </div>
                            <div>
                                <dt><i class="fas fa-envelope"></i>Email</dt>
                                <dd><?= profileEscape($profile['email']) ?></dd>
                            </div>
                            <div>
                                <dt><i class="fas fa-location-dot"></i>Location</dt>
                                <dd><?= !empty($profile['location']) ? profileEscape($profile['location']) : 'Not added' ?></dd>
                            </div>
                            <div>
                                <dt><i class="fas fa-id-badge"></i>Account type</dt>
                                <dd><?= profileEscape(ucfirst($profile['role'])) ?></dd>
                            </div>
                        </dl>
                    </section>

                    <section class="profile-card profile-skills-card">
                        <div class="profile-card-heading">
                            <div>
                                <span>Expertise</span>
                                <h2>Skills</h2>
                            </div>
                            <span class="profile-count"><?= count($selectedSkills) ?></span>
                        </div>
                        <?php if ($selectedSkills): ?>
                            <div class="profile-tag-list">
                                <?php foreach ($selectedSkills as $skill): ?>
                                    <span class="profile-tag profile-tag-skill"><i class="fas fa-code"></i><?= profileEscape($skill['name']) ?></span>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="profile-empty profile-empty-inline">
                                <p>No skills added yet.</p>
                                <a href="./index.php?edit=1">Add skills</a>
                            </div>
                        <?php endif; ?>
                    </section>

                    <section class="profile-card profile-interests-card">
                        <div class="profile-card-heading">
                            <div>
                                <span>Community</span>
                                <h2>Interests</h2>
                            </div>
                            <span class="profile-count"><?= count($selectedInterests) ?></span>
                        </div>
                        <?php if ($selectedInterests): ?>
                            <div class="profile-tag-list">
                                <?php foreach ($selectedInterests as $interest): ?>
                                    <span class="profile-tag profile-tag-interest"><i class="fas fa-sparkles"></i><?= profileEscape($interest['name']) ?></span>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="profile-empty profile-empty-inline">
                                <p>No interests added yet.</p>
                                <a href="./index.php?edit=1">Add interests</a>
                            </div>
                        <?php endif; ?>
                    </section>
                </div>
            <?php else: ?>
                <form action="../actions/update_profile.php" method="POST" enctype="multipart/form-data" class="profile-edit-layout">
                    <input type="hidden" name="csrf_token" value="<?= profileEscape(profileCsrfToken()) ?>">

                    <section class="profile-edit-main">
                        <div class="profile-edit-section">
                            <div class="profile-section-heading">
                                <span>01</span>
                                <div>
                                    <h2>Profile photo</h2>
                                    <p>Choose a clear image that represents you professionally.</p>
                                </div>
                            </div>

                            <div class="profile-photo-editor">
                                <div id="profilePhotoPreview">
                                    <?php if ($profileImage): ?>
                                        <img src="<?= profileEscape($profileImage) ?>" alt="" class="profile-photo profile-photo-preview">
                                    <?php else: ?>
                                        <div class="profile-photo profile-photo-initials profile-photo-preview"><?= profileEscape($initials) ?></div>
                                    <?php endif; ?>
                                </div>
                                <div class="profile-upload-controls">
                                    <label for="profile_image" class="profile-upload-button">
                                        <i class="fas fa-camera"></i>
                                        Upload photo
                                    </label>
                                    <input id="profile_image" name="profile_image" type="file" accept="image/jpeg,image/png,image/webp">
                                    <p>JPG, PNG or WEBP. Maximum 5 MB.</p>
                                </div>
                            </div>
                        </div>

                        <div class="profile-edit-section">
                            <div class="profile-section-heading">
                                <span>02</span>
                                <div>
                                    <h2>Personal details</h2>
                                    <p>Keep your public profile clear and up to date.</p>
                                </div>
                            </div>

                            <div class="profile-form-grid">
                                <div class="profile-field profile-field-wide">
                                    <label for="full_name">Full name</label>
                                    <input id="full_name" name="full_name" type="text" maxlength="150" required value="<?= profileEscape($profile['full_name']) ?>">
                                </div>

                                <div class="profile-field">
                                    <label for="location">Location</label>
                                    <input id="location" name="location" type="text" maxlength="255" value="<?= profileEscape($profile['location'] ?? '') ?>" placeholder="City, Country">
                                </div>

                                <div class="profile-field">
                                    <label for="email">Email address</label>
                                    <input id="email" type="email" value="<?= profileEscape($profile['email']) ?>" disabled>
                                </div>

                                <div class="profile-field profile-field-wide">
                                    <label for="bio">Professional bio</label>
                                    <textarea id="bio" name="bio" rows="6" maxlength="1000" placeholder="Tell people about your experience, goals, and what you are looking for..."><?= profileEscape($profile['bio'] ?? '') ?></textarea>
                                    <div class="profile-field-meta">
                                        <span>Short, specific summaries work best</span>
                                        <span><b id="bioCount"><?= mb_strlen((string) ($profile['bio'] ?? '')) ?></b>/1000</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="profile-edit-section">
                            <div class="profile-section-heading">
                                <span>03</span>
                                <div>
                                    <h2>Skills & interests</h2>
                                    <p>Select the areas that represent you.</p>
                                </div>
                            </div>

                            <div class="profile-selection-group">
                                <h3><i class="fas fa-code"></i>Skills</h3>
                                <?php if ($allSkills): ?>
                                    <div class="profile-checkbox-grid">
                                        <?php foreach ($allSkills as $skill): ?>
                                            <label class="profile-checkbox">
                                                <input type="checkbox" name="skills[]" value="<?= (int) $skill['id'] ?>" <?= in_array((int) $skill['id'], $selectedSkillIds, true) ? 'checked' : '' ?>>
                                                <span><?= profileEscape($skill['name']) ?></span>
                                            </label>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <p class="profile-no-options">No skills have been configured yet.</p>
                                <?php endif; ?>
                            </div>

                            <div class="profile-selection-group">
                                <h3><i class="fas fa-sparkles"></i>Interests</h3>
                                <?php if ($allInterests): ?>
                                    <div class="profile-checkbox-grid">
                                        <?php foreach ($allInterests as $interest): ?>
                                            <label class="profile-checkbox">
                                                <input type="checkbox" name="interests[]" value="<?= (int) $interest['id'] ?>" <?= in_array((int) $interest['id'], $selectedInterestIds, true) ? 'checked' : '' ?>>
                                                <span><?= profileEscape($interest['name']) ?></span>
                                            </label>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <p class="profile-no-options">No interests have been configured yet.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </section>

                    <aside class="profile-edit-aside">
                        <div class="profile-save-card">
                            <div class="profile-save-icon"><i class="fas fa-shield-halved"></i></div>
                            <h2>Ready to save?</h2>
                            <p>Your profile is visible to other SynergySpace members.</p>
                            <button type="submit" class="profile-save-button">
                                <i class="fas fa-check"></i>
                                Save changes
                            </button>
                            <a href="./index.php" class="profile-cancel-button">Cancel</a>
                        </div>
                    </aside>
                </form>
            <?php endif; ?>
        </div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
<script src="../js/main.js"></script>
</body>
</html>

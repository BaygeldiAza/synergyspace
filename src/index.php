<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SynergySpace</title>
    <link rel="stylesheet" href="./css/style.css">
    <script src="https://kit.fontawesome.com/90b5aa2433.js" crossorigin="anonymous"></script>
</head>

<body>
    <?php
    $activePage = 'home';
    require_once "./includes/header.php";
    ?>
    
    <section class="welcome-section">
        <div class="welcome-content">
            <h1>SynergySpace</h1>
            <h2>Working Better Together</h2>
            <p>
                Find coworking spaces, meet amazing professionals 
                and build something great together.
            </p>
            <a href="#explore" class="primary-btn">Explore Spaces <i class="fas fa-arrow-right"></i></a>
        </div>
    </section>

    <section id="explore" class="stats" aria-label="Platform overview">
        <article class="stat-card searchable-card" data-search="available spaces workspaces">
            <div class="stat-icon"><i class="fas fa-building"></i></div>
            <h3>Available Spaces</h3>
            <p>24</p>
            <span>Spaces ready to explore</span>
        </article>
        <article class="stat-card searchable-card" data-search="my rentals bookings">
            <div class="stat-icon"><i class="fas fa-calendar-check"></i></div>
            <h3>My Rentals</h3>
            <p>3</p>
            <span>Your current rentals</span>
        </article>
        <article id="network" class="stat-card searchable-card" data-search="my network professionals connections">
            <div class="stat-icon"><i class="fas fa-users"></i></div>
            <h3>My Network</h3>
            <p>12</p>
            <span>Professional connections</span>
        </article>
        <article id="reviews" class="stat-card searchable-card" data-search="my rating reviews reputation">
            <div class="stat-icon"><i class="fas fa-star"></i></div>
            <h3>My Rating</h3>
            <p>4.8 ★</p>
            <span>Your reputation score</span>
        </article>
    </section>

    <section id="features" class="section-heading searchable-card" data-search="explore workspace network rentals">
        <h2>Explore SynergySpace</h2>
        <p>Discover what you can do on the platform.</p>
    </section>

    <section class="feature-grid">
        <a class="feature-card searchable-card" href="#explore" data-search="find workspace coworking space">
            <span class="feature-card-icon blue"><i class="fas fa-building"></i></span>
            <h3>Find a Workspace</h3>
            <p>Discover coworking spaces that match your needs.</p>
            <span class="feature-card-link">Browse spaces <i class="fas fa-arrow-right"></i></span>
        </a>
        <a id="messages" class="feature-card searchable-card" href="#network" data-search="network professionals people community">
            <span class="feature-card-icon purple"><i class="fas fa-users"></i></span>
            <h3>Build Your Network</h3>
            <p>Connect with professionals who share your workspace.</p>
            <span class="feature-card-link">Explore network <i class="fas fa-arrow-right"></i></span>
        </a>
        <a class="feature-card searchable-card" href="#explore" data-search="manage rentals booking agreements">
            <span class="feature-card-icon green"><i class="fas fa-calendar-days"></i></span>
            <h3>Manage Rentals</h3>
            <p>View and manage your workspace rental requests.</p>
            <span class="feature-card-link">My rentals <i class="fas fa-arrow-right"></i></span>
        </a>
    </section>
    <div id="dashboardSearchEmpty" class="dashboard-search-empty" hidden>
        <i class="fas fa-magnifying-glass"></i>
        <p>No matching dashboard items found.</p>
    </div>
    <?php require_once "./includes/footer.php"; ?>
    
</body>
<script src="./js/main.js"></script>
</html>
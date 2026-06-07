<?php
// this is the homepage
session_start();
include 'db.php';

// fetch the 6 most recent active listings to show on the homepage
$result = $conn->query("
    SELECT l.*, u.full_name, u.area
    FROM listings l
    JOIN users u ON l.user_id = u.user_id
    WHERE l.status = 'active'
    ORDER BY l.created_at DESC
    LIMIT 6
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TownTrade – Township Marketplace</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body class="bg-dark-green">

<!-- navbar -->
<nav class="navbar navbar-dark navbar-expand-lg" id="main-nav">
    <div class="container">
        <a class="navbar-brand fw-bold" href="index.php">🌿 Town<span class="text-gold">Trade</span></a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navmenu">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navmenu">
            <ul class="navbar-nav ms-auto align-items-lg-center">
                <li class="nav-item"><a class="nav-link active" href="index.php">Home</a></li>
                <li class="nav-item"><a class="nav-link" href="listings.php">Marketplace</a></li>

                <?php if (isset($_SESSION['user_id'])): ?>
                    <!-- logged in links -->
                    <?php if ($_SESSION['role_id'] == 2): ?>
                        <li class="nav-item"><a class="nav-link" href="sell.php">+ Sell</a></li>
                    <?php endif; ?>
                    <li class="nav-item"><a class="nav-link" href="profile.php">My Profile</a></li>
                    <li class="nav-item"><a class="nav-link" href="logout.php">Log Out</a></li>
                <?php else: ?>
                    <!-- not logged in links -->
                    <li class="nav-item"><a class="nav-link" href="login.php">Login</a></li>
                    <li class="nav-item ms-lg-2">
                        <a class="btn btn-gold px-3 py-1" href="register.php">Join Free</a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<!-- hero section -->
<div class="hero-section">
    <div class="container">
        <div class="hero-badge">🇿🇦 Built for South African Township Traders</div>
        <h1 class="text-white mb-3">
            Buy & sell in your<br><span class="text-gold">community.</span> Go digital.
        </h1>
        <p class="mb-4">
            TownTrade connects township micro-entrepreneurs with buyers in their
            own community — verified profiles, digital payments, and access to micro-credit.
        </p>
        <div class="d-flex gap-3 justify-content-center flex-wrap">
            <a href="register.php" class="btn btn-gold px-4 py-2 fw-bold">Join as a Trader</a>
            <a href="listings.php" class="btn btn-outline-green px-4 py-2">Browse Marketplace</a>
        </div>
    </div>
</div>

<!-- stats bar which contains numbers taken from the proposal (section 1.2) -->
<div class="stats-bar">
    <div class="container">
        <div class="row text-center g-3">
            <div class="col-6 col-md-3">
                <div class="stat-num">R900bn+</div>
                <div class="stat-label">Township economy annual value</div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-num">70%</div>
                <div class="stat-label">Informal workers as micro-entrepreneurs</div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-num">80%</div>
                <div class="stat-label">Businesses still unregistered</div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-num">10 000</div>
                <div class="stat-label">Traders targeted in year one</div>
            </div>
        </div>
    </div>
</div>

<!-- recent listings section -->
<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold text-white mb-1">Recent Listings</h2>
            <p class="text-muted mb-0" style="font-size:0.9rem;">Fresh products from verified township traders</p>
        </div>
        <a href="listings.php" class="btn btn-outline-green">View All</a>
    </div>

    <div class="row g-4">
        <?php if ($result && $result->num_rows > 0): ?>
            <?php while ($listing = $result->fetch_assoc()): ?>
                <div class="col-sm-6 col-lg-4">
                    <div class="listing-card">
                        <!-- if no image uploaded, show a placeholder -->
                        <?php if (!empty($listing['image_url'])): ?>
                            <img src="uploads/<?= htmlspecialchars($listing['image_url']) ?>"
                                 class="card-img-top" alt="<?= htmlspecialchars($listing['title']) ?>">
                        <?php else: ?>
                            <div class="card-img-top d-flex align-items-center justify-content-center"
                                 style="font-size:3rem; background:#1F3328;">📦</div>
                        <?php endif; ?>

                        <div class="card-body">
                            <div class="card-title"><?= htmlspecialchars($listing['title']) ?></div>
                            <p class="card-text mb-2">
                                📍 <?= htmlspecialchars($listing['area']) ?> &nbsp;·&nbsp;
                                <span class="verified-badge">✔ Verified</span>
                            </p>
                            <p class="card-text mb-3">
                                <?= htmlspecialchars(substr($listing['description'], 0, 65)) ?>...
                            </p>
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="price-tag">R<?= number_format($listing['price'], 2) ?></span>
                                <a href="product.php?id=<?= $listing['listing_id'] ?>"
                                   class="btn btn-green btn-sm">View</a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <!-- shown when the database has no listings yet -->
            <div class="col-12 text-center py-5">
                <p class="text-muted">No listings yet. Be the first to
                    <a href="register.php" class="text-green-light">sell something!</a>
                </p>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- how it works section -->
<div class="container pb-5">
    <h2 class="fw-bold text-white text-center mb-2">How TownTrade Works</h2>
    <p class="text-muted text-center mb-5" style="font-size:0.9rem;">
        Three steps to start trading digitally in your community
    </p>
    <div class="row g-4 text-center">
        <div class="col-md-4">
            <div class="card bg-card border-green p-4">
                <div style="font-size:2.5rem;" class="mb-3">📋</div>
                <h5 class="fw-bold text-white">1. Register & Get Verified</h5>
                <p class="text-muted" style="font-size:0.88rem;">
                    Create your profile with a verified ID and build trust with buyers in your area.
                </p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-card border-green p-4">
                <div style="font-size:2.5rem;" class="mb-3">📦</div>
                <h5 class="fw-bold text-white">2. List Your Products</h5>
                <p class="text-muted" style="font-size:0.88rem;">
                    Post items for sale with photos and prices. Reach customers beyond your street.
                </p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-card border-green p-4">
                <div style="font-size:2.5rem;" class="mb-3">💳</div>
                <h5 class="fw-bold text-white">3. Get Paid Digitally</h5>
                <p class="text-muted" style="font-size:0.88rem;">
                    Accept payments safely. Build a transaction history that unlocks micro-credit.
                </p>
            </div>
        </div>
    </div>
</div>

<!-- footer -->
<footer class="main-footer">
    <div class="container">
        <strong class="text-gold">TownTrade</strong> &nbsp;|&nbsp;
        ITECA3-B12 &nbsp;|&nbsp;
        Zanele Boitumelo Matjie &nbsp;|&nbsp;
        EDUV4963658 &nbsp;|&nbsp;
        Eduvos Pretoria
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

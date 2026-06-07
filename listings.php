<?php
// listings.php - this is the main marketplace page
// it shows all active listings with search and category filter
session_start();
include 'db.php';

// get the search and category values from the url if they exist
$search   = isset($_GET['search'])   ? trim($_GET['search'])   : '';
$category = isset($_GET['category']) ? trim($_GET['category']) : '';

// build the query depending on what filters are active
// i used a base query and added conditions on top
$sql = "
    SELECT l.*, u.full_name, u.area
    FROM listings l
    JOIN users u ON l.user_id = u.user_id
    WHERE l.status = 'active'
";

// add search condition if user typed something
if (!empty($search)) {
    $safe_search = $conn->real_escape_string($search);
    $sql .= " AND (l.title LIKE '%$safe_search%' OR l.description LIKE '%$safe_search%')";
}

// add category condition if user selected one
if (!empty($category)) {
    $safe_cat = $conn->real_escape_string($category);
    $sql .= " AND l.category = '$safe_cat'";
}

$sql .= " ORDER BY l.created_at DESC";
$result = $conn->query($sql);

// list of categories - used for the filter pills
$categories = ['Food & Produce', 'Clothing', 'Electronics', 'Crafts & Art', 'Services', 'Other'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Marketplace – TownTrade</title>
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
                <li class="nav-item"><a class="nav-link" href="index.php">Home</a></li>
                <li class="nav-item"><a class="nav-link active" href="listings.php">Marketplace</a></li>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <?php if ($_SESSION['role_id'] == 2): ?>
                        <li class="nav-item"><a class="nav-link" href="sell.php">+ Sell</a></li>
                    <?php endif; ?>
                    <li class="nav-item"><a class="nav-link" href="profile.php">My Profile</a></li>
                    <li class="nav-item"><a class="nav-link" href="logout.php">Log Out</a></li>
                <?php else: ?>
                    <li class="nav-item"><a class="nav-link" href="login.php">Login</a></li>
                    <li class="nav-item ms-lg-2">
                        <a class="btn btn-gold px-3 py-1" href="register.php">Join Free</a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<div class="container py-5">

    <h2 class="fw-bold text-white mb-1">Marketplace</h2>
    <p class="text-muted mb-4" style="font-size:0.9rem;">
        Browse verified township traders near you
    </p>

    <!-- search bar - submits to the same page using GET -->
    <form method="GET" action="listings.php" class="mb-4">
        <div class="d-flex gap-2">
            <input
                type="text"
                name="search"
                class="form-control bg-dark text-white border-secondary"
                placeholder="🔍  Search products, traders or areas..."
                value="<?= htmlspecialchars($search) ?>"
            >
            <!-- keep the category filter when searching -->
            <?php if (!empty($category)): ?>
                <input type="hidden" name="category" value="<?= htmlspecialchars($category) ?>">
            <?php endif; ?>
            <button type="submit" class="btn btn-green px-4">Search</button>
        </div>
    </form>

    <!-- category filter pills -->
    <div class="mb-4">
        <!-- "All" pill clears the category filter -->
        <a href="listings.php<?= !empty($search) ? '?search='.urlencode($search) : '' ?>"
           class="filter-pill <?= empty($category) ? 'active' : '' ?>">
            All
        </a>
        <?php foreach ($categories as $cat): ?>
            <a href="listings.php?category=<?= urlencode($cat) ?><?= !empty($search) ? '&search='.urlencode($search) : '' ?>"
               class="filter-pill <?= $category === $cat ? 'active' : '' ?>">
                <?= $cat ?>
            </a>
        <?php endforeach; ?>
    </div>

    <!-- show what filter is active -->
    <?php if (!empty($search) || !empty($category)): ?>
        <p class="text-muted mb-3" style="font-size:0.85rem;">
            Showing results
            <?= !empty($search)   ? 'for "<strong class="text-white">' . htmlspecialchars($search) . '</strong>"' : '' ?>
            <?= !empty($category) ? 'in <strong class="text-white">' . htmlspecialchars($category) . '</strong>' : '' ?>
            &nbsp;—&nbsp; <a href="listings.php" class="text-green-light">Clear filters</a>
        </p>
    <?php endif; ?>

    <!-- listings grid -->
    <div class="row g-4">
        <?php if ($result && $result->num_rows > 0): ?>
            <?php while ($listing = $result->fetch_assoc()): ?>
                <div class="col-sm-6 col-lg-4">
                    <div class="listing-card">
                        <?php if (!empty($listing['image_url'])): ?>
                            <img src="uploads/<?= htmlspecialchars($listing['image_url']) ?>"
                                 class="card-img-top" alt="<?= htmlspecialchars($listing['title']) ?>">
                        <?php else: ?>
                            <div class="card-img-top d-flex align-items-center justify-content-center"
                                 style="font-size:3rem; background:#1F3328;">📦</div>
                        <?php endif; ?>

                        <div class="card-body">
                            <div class="card-title"><?= htmlspecialchars($listing['title']) ?></div>
                            <p class="card-text mb-1" style="font-size:0.8rem;">
                                📍 <?= htmlspecialchars($listing['area']) ?>
                                &nbsp;·&nbsp;
                                <span class="verified-badge">✔ Verified</span>
                            </p>
                            <p class="card-text mb-1" style="font-size:0.78rem; color:#7A9E87;">
                                🏷 <?= htmlspecialchars($listing['category']) ?>
                            </p>
                            <p class="card-text mb-3">
                                <?= htmlspecialchars(substr($listing['description'], 0, 70)) ?>...
                            </p>
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="price-tag">R<?= number_format($listing['price'], 2) ?></span>
                                <a href="product.php?id=<?= $listing['listing_id'] ?>"
                                   class="btn btn-green btn-sm">View Item</a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="col-12 text-center py-5">
                <p style="font-size:3rem;">🔍</p>
                <p class="text-muted">No listings found.
                    <?php if (!empty($search) || !empty($category)): ?>
                        <a href="listings.php" class="text-green-light">Clear your filters</a>
                    <?php else: ?>
                        <a href="register.php" class="text-green-light">Be the first to sell!</a>
                    <?php endif; ?>
                </p>
            </div>
        <?php endif; ?>
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

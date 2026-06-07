<?php
// profile.php - shows the logged in user's profile, listings and messages
session_start();
include 'db.php';

// must be logged in to view this page
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// fetch the user's details
$stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// fetch the user's listings if they are a seller
$listings = [];
if ($user['role_id'] == 2) {
    $l_stmt = $conn->prepare("
        SELECT * FROM listings
        WHERE user_id = ?
        ORDER BY created_at DESC
    ");
    $l_stmt->bind_param("i", $user_id);
    $l_stmt->execute();
    $listings = $l_stmt->get_result();
}

// fetch messages received by this user
$msg_stmt = $conn->prepare("
    SELECT m.*, u.full_name AS sender_name, l.title AS listing_title
    FROM messages m
    JOIN users u ON m.sender_id = u.user_id
    LEFT JOIN listings l ON m.listing_id = l.listing_id
    WHERE m.receiver_id = ?
    ORDER BY m.created_at DESC
    LIMIT 20
");
$msg_stmt->bind_param("i", $user_id);
$msg_stmt->execute();
$messages = $msg_stmt->get_result();

// count unread messages
$unread_stmt = $conn->prepare("SELECT COUNT(*) AS total FROM messages WHERE receiver_id = ? AND is_read = 0");
$unread_stmt->bind_param("i", $user_id);
$unread_stmt->execute();
$unread = $unread_stmt->get_result()->fetch_assoc()['total'];

// mark all messages as read now that they are viewing this page
$conn->query("UPDATE messages SET is_read = 1 WHERE receiver_id = $user_id");

// role label for display
$role_labels = [1 => 'Admin', 2 => 'Seller', 3 => 'Buyer'];
$role_label  = $role_labels[$user['role_id']] ?? 'User';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile – TownTrade</title>
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
                <li class="nav-item"><a class="nav-link" href="listings.php">Marketplace</a></li>
                <?php if ($user['role_id'] == 2): ?>
                    <li class="nav-item"><a class="nav-link" href="sell.php">+ Sell</a></li>
                <?php endif; ?>
                <li class="nav-item"><a class="nav-link active" href="profile.php">My Profile</a></li>
                <li class="nav-item"><a class="nav-link" href="logout.php">Log Out</a></li>
            </ul>
        </div>
    </div>
</nav>

<div class="container py-5">

    <!-- profile header card -->
    <div class="card bg-card border-green mb-4">
        <div class="card-body p-4">
            <div class="d-flex align-items-center gap-4 flex-wrap">
                <div class="avatar-circle">
                    <?= strtoupper(substr($user['full_name'], 0, 1)) ?>
                </div>
                <div>
                    <h3 class="fw-bold text-white mb-1">
                        <?= htmlspecialchars($user['full_name']) ?>
                        <span class="verified-badge ms-2">✔ Verified</span>
                    </h3>
                    <p class="text-muted mb-1" style="font-size:0.88rem;">
                        📧 <?= htmlspecialchars($user['email']) ?>
                        &nbsp;·&nbsp;
                        📍 <?= htmlspecialchars($user['area'] ?? 'No area set') ?>
                    </p>
                    <p class="text-muted mb-0" style="font-size:0.85rem;">
                        Role: <strong class="text-white"><?= $role_label ?></strong>
                        &nbsp;·&nbsp;
                        Joined: <?= date('d M Y', strtotime($user['created_at'])) ?>
                    </p>
                </div>
                <?php if ($user['role_id'] == 2): ?>
                    <a href="sell.php" class="btn btn-gold ms-auto fw-bold">+ Post New Listing</a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- tabs for switching between listings and messages -->
    <ul class="nav nav-tabs mb-4" id="profileTabs">
        <?php if ($user['role_id'] == 2): ?>
            <li class="nav-item">
                <button class="nav-link active text-white" id="listings-tab"
                        data-bs-toggle="tab" data-bs-target="#listings-panel">
                    My Listings
                </button>
            </li>
        <?php endif; ?>
        <li class="nav-item">
            <button class="nav-link <?= $user['role_id'] != 2 ? 'active' : '' ?> text-white"
                    id="messages-tab"
                    data-bs-toggle="tab" data-bs-target="#messages-panel">
                Messages
                <?php if ($unread > 0): ?>
                    <span class="badge bg-danger ms-1"><?= $unread ?></span>
                <?php endif; ?>
            </button>
        </li>
    </ul>

    <div class="tab-content">

        <!-- listings tab -->
        <?php if ($user['role_id'] == 2): ?>
        <div class="tab-pane fade show active" id="listings-panel">
            <?php if ($listings && $listings->num_rows > 0): ?>
                <div class="row g-4">
                    <?php while ($listing = $listings->fetch_assoc()): ?>
                        <div class="col-sm-6 col-lg-4">
                            <div class="listing-card">
                                <?php if (!empty($listing['image_url'])): ?>
                                    <img src="uploads/<?= htmlspecialchars($listing['image_url']) ?>"
                                         class="card-img-top"
                                         alt="<?= htmlspecialchars($listing['title']) ?>">
                                <?php else: ?>
                                    <div class="card-img-top d-flex align-items-center justify-content-center"
                                         style="font-size:3rem; background:#1F3328;">📦</div>
                                <?php endif; ?>
                                <div class="card-body">
                                    <div class="card-title"><?= htmlspecialchars($listing['title']) ?></div>
                                    <p class="card-text mb-2">
                                        <!-- colour coded status badge -->
                                        <?php
                                        $status_colours = [
                                            'active'   => 'success',
                                            'pending'  => 'warning',
                                            'rejected' => 'danger',
                                            'sold'     => 'secondary'
                                        ];
                                        $badge = $status_colours[$listing['status']] ?? 'secondary';
                                        ?>
                                        <span class="badge bg-<?= $badge ?>">
                                            <?= ucfirst($listing['status']) ?>
                                        </span>
                                    </p>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="price-tag">R<?= number_format($listing['price'], 2) ?></span>
                                        <?php if ($listing['status'] == 'active'): ?>
                                            <a href="product.php?id=<?= $listing['listing_id'] ?>"
                                               class="btn btn-green btn-sm">View</a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="text-center py-5">
                    <p style="font-size:3rem;">📦</p>
                    <p class="text-muted">You have not posted any listings yet.</p>
                    <a href="sell.php" class="btn btn-gold fw-bold">Post Your First Listing</a>
                </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- messages tab -->
        <div class="tab-pane fade <?= $user['role_id'] != 2 ? 'show active' : '' ?>" id="messages-panel">
            <?php if ($messages && $messages->num_rows > 0): ?>
                <div class="d-flex flex-column gap-3">
                    <?php while ($msg = $messages->fetch_assoc()): ?>
                        <div class="card bg-card border-green">
                            <div class="card-body p-3">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div>
                                        <strong class="text-white" style="font-size:0.9rem;">
                                            <?= htmlspecialchars($msg['sender_name']) ?>
                                        </strong>
                                        <?php if (!empty($msg['listing_title'])): ?>
                                            <span class="text-muted" style="font-size:0.82rem;">
                                                &nbsp;about&nbsp;
                                                <em><?= htmlspecialchars($msg['listing_title']) ?></em>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    <span class="text-muted" style="font-size:0.78rem;">
                                        <?= date('d M Y, H:i', strtotime($msg['created_at'])) ?>
                                    </span>
                                </div>
                                <p class="text-white mb-0" style="font-size:0.88rem; line-height:1.6;">
                                    <?= htmlspecialchars($msg['content']) ?>
                                </p>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="text-center py-5">
                    <p style="font-size:3rem;">💬</p>
                    <p class="text-muted">No messages yet.</p>
                </div>
            <?php endif; ?>
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

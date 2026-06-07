<?php
// admin/index.php - the admin dashboard
// shows an overview of the whole platform
session_start();
include '../db.php';

// only admins (role_id = 1) can access any admin page
// everyone else gets sent back to the main site
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 1) {
    header("Location: ../login.php");
    exit();
}

// count total users
$total_users = $conn->query("SELECT COUNT(*) AS total FROM users")->fetch_assoc()['total'];

// count total listings
$total_listings = $conn->query("SELECT COUNT(*) AS total FROM listings")->fetch_assoc()['total'];

// count pending listings waiting for approval
$pending_listings = $conn->query("SELECT COUNT(*) AS total FROM listings WHERE status = 'pending'")->fetch_assoc()['total'];

// count total messages sent on the platform
$total_messages = $conn->query("SELECT COUNT(*) AS total FROM messages")->fetch_assoc()['total'];

// count how many sellers are registered
$total_sellers = $conn->query("SELECT COUNT(*) AS total FROM users WHERE role_id = 2")->fetch_assoc()['total'];

// count how many buyers are registered
$total_buyers = $conn->query("SELECT COUNT(*) AS total FROM users WHERE role_id = 3")->fetch_assoc()['total'];

// fetch the 5 most recent listings for the activity feed
$recent = $conn->query("
    SELECT l.title, l.status, l.created_at, u.full_name
    FROM listings l
    JOIN users u ON l.user_id = u.user_id
    ORDER BY l.created_at DESC
    LIMIT 5
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard – TownTrade</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="../style.css">
</head>
<body class="bg-dark-green">

<div class="admin-wrapper">

    <!-- sidebar navigation -->
    <div class="admin-sidebar">
        <a href="index.php" class="brand">🌿 TownTrade<br>
            <small style="font-size:0.7rem; color:#7A9E87; font-weight:400;">Admin Panel</small>
        </a>

        <a href="index.php" class="active">📊 Dashboard</a>
        <a href="users.php">👥 Users</a>
        <a href="listings.php">📦 Listings
            <?php if ($pending_listings > 0): ?>
                <span class="badge bg-warning text-dark ms-1"><?= $pending_listings ?></span>
            <?php endif; ?>
        </a>
        <a href="../index.php" style="margin-top:auto;">🌿 View Site</a>
        <a href="logout.php" style="color:#E05252;">🚪 Log Out</a>
    </div>

    <!-- main content area -->
    <div class="admin-content">

        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3 class="fw-bold text-white mb-1">Dashboard</h3>
                <p class="text-muted mb-0" style="font-size:0.88rem;">
                    Welcome back, <?= htmlspecialchars($_SESSION['full_name']) ?>
                </p>
            </div>
            <span class="text-muted" style="font-size:0.82rem;">
                <?= date('d F Y') ?>
            </span>
        </div>

        <!-- stat cards row -->
        <div class="row g-3 mb-4">
            <div class="col-sm-6 col-xl-4">
                <div class="admin-stat-card">
                    <div style="font-size:2rem;" class="mb-2">👥</div>
                    <div class="stat-num"><?= $total_users ?></div>
                    <div class="stat-label">Total Users</div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-4">
                <div class="admin-stat-card">
                    <div style="font-size:2rem;" class="mb-2">🛒</div>
                    <div class="stat-num"><?= $total_sellers ?></div>
                    <div class="stat-label">Registered Sellers</div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-4">
                <div class="admin-stat-card">
                    <div style="font-size:2rem;" class="mb-2">🛍️</div>
                    <div class="stat-num"><?= $total_buyers ?></div>
                    <div class="stat-label">Registered Buyers</div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-4">
                <div class="admin-stat-card">
                    <div style="font-size:2rem;" class="mb-2">📦</div>
                    <div class="stat-num"><?= $total_listings ?></div>
                    <div class="stat-label">Total Listings</div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-4">
                <div class="admin-stat-card">
                    <div style="font-size:2rem;" class="mb-2">⏳</div>
                    <div class="stat-num text-warning"><?= $pending_listings ?></div>
                    <div class="stat-label">Pending Approval</div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-4">
                <div class="admin-stat-card">
                    <div style="font-size:2rem;" class="mb-2">💬</div>
                    <div class="stat-num"><?= $total_messages ?></div>
                    <div class="stat-label">Messages Sent</div>
                </div>
            </div>
        </div>

        <!-- pending listings alert -->
        <?php if ($pending_listings > 0): ?>
            <div class="alert mb-4"
                 style="background:rgba(245,166,35,0.1); border:1px solid rgba(245,166,35,0.4); color:#F5A623;">
                ⚠️ You have <strong><?= $pending_listings ?></strong> listing(s) waiting for approval.
                <a href="listings.php" class="fw-bold ms-2" style="color:#F5A623;">Review now →</a>
            </div>
        <?php endif; ?>

        <!-- recent activity table -->
        <div class="card bg-card border-green">
            <div class="card-body p-0">
                <div class="p-3 border-bottom" style="border-color:#1F3328 !important;">
                    <h6 class="fw-bold text-white mb-0">Recent Listings</h6>
                </div>
                <div class="table-responsive">
                    <table class="table table-dark-custom mb-0">
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Seller</th>
                                <th>Status</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($recent && $recent->num_rows > 0): ?>
                                <?php while ($row = $recent->fetch_assoc()): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($row['title']) ?></td>
                                        <td><?= htmlspecialchars($row['full_name']) ?></td>
                                        <td>
                                            <?php
                                            $colours = [
                                                'active'   => 'success',
                                                'pending'  => 'warning',
                                                'rejected' => 'danger',
                                                'sold'     => 'secondary'
                                            ];
                                            $c = $colours[$row['status']] ?? 'secondary';
                                            ?>
                                            <span class="badge bg-<?= $c ?>">
                                                <?= ucfirst($row['status']) ?>
                                            </span>
                                        </td>
                                        <td><?= date('d M Y', strtotime($row['created_at'])) ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-4">
                                        No listings yet.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

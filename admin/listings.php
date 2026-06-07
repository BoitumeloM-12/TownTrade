<?php
// admin/listings.php - manage all listings
// admin can approve, reject or delete any listing
session_start();
include '../db.php';

// admin only page
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 1) {
    header("Location: ../login.php");
    exit();
}

$message = "";

// handle approve action
if (isset($_GET['approve']) && is_numeric($_GET['approve'])) {
    $lid = (int)$_GET['approve'];
    $conn->query("UPDATE listings SET status = 'active' WHERE listing_id = $lid");
    $message = "Listing approved and is now live.";
}

// handle reject action
if (isset($_GET['reject']) && is_numeric($_GET['reject'])) {
    $lid = (int)$_GET['reject'];
    $conn->query("UPDATE listings SET status = 'rejected' WHERE listing_id = $lid");
    $message = "Listing has been rejected.";
}

// handle delete action
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $lid = (int)$_GET['delete'];
    $conn->query("DELETE FROM listings WHERE listing_id = $lid");
    $message = "Listing has been deleted.";
}

// get filter from url - default shows all listings
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';

// build query based on filter
$sql = "
    SELECT l.*, u.full_name, u.area
    FROM listings l
    JOIN users u ON l.user_id = u.user_id
";

if ($filter != 'all') {
    $safe_filter = $conn->real_escape_string($filter);
    $sql .= " WHERE l.status = '$safe_filter'";
}

$sql .= " ORDER BY l.created_at DESC";
$listings = $conn->query($sql);

// counts for the filter tabs
$all_count     = $conn->query("SELECT COUNT(*) AS t FROM listings")->fetch_assoc()['t'];
$pending_count = $conn->query("SELECT COUNT(*) AS t FROM listings WHERE status = 'pending'")->fetch_assoc()['t'];
$active_count  = $conn->query("SELECT COUNT(*) AS t FROM listings WHERE status = 'active'")->fetch_assoc()['t'];
$rejected_count= $conn->query("SELECT COUNT(*) AS t FROM listings WHERE status = 'rejected'")->fetch_assoc()['t'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Listings – TownTrade Admin</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="../style.css">
</head>
<body class="bg-dark-green">

<div class="admin-wrapper">

    <!-- sidebar -->
    <div class="admin-sidebar">
        <a href="index.php" class="brand">🌿 TownTrade<br>
            <small style="font-size:0.7rem; color:#7A9E87; font-weight:400;">Admin Panel</small>
        </a>
        <a href="index.php">📊 Dashboard</a>
        <a href="users.php">👥 Users</a>
        <a href="listings.php" class="active">📦 Listings
            <?php if ($pending_count > 0): ?>
                <span class="badge bg-warning text-dark ms-1"><?= $pending_count ?></span>
            <?php endif; ?>
        </a>
        <a href="../index.php">🌿 View Site</a>
        <a href="logout.php" style="color:#E05252;">🚪 Log Out</a>
    </div>

    <!-- main content -->
    <div class="admin-content">

        <div class="mb-4">
            <h3 class="fw-bold text-white mb-1">Manage Listings</h3>
            <p class="text-muted mb-0" style="font-size:0.88rem;">
                Approve, reject or delete listings from the marketplace
            </p>
        </div>

        <!-- success message -->
        <?php if ($message): ?>
            <div class="alert alert-success mb-4"><?= $message ?></div>
        <?php endif; ?>

        <!-- filter tabs -->
        <div class="d-flex gap-2 flex-wrap mb-4">
            <a href="listings.php?filter=all"
               class="btn btn-sm <?= $filter == 'all' ? 'btn-green' : 'btn-outline-green' ?>">
                All (<?= $all_count ?>)
            </a>
            <a href="listings.php?filter=pending"
               class="btn btn-sm <?= $filter == 'pending' ? 'btn-gold' : 'btn-outline-green' ?>">
                ⏳ Pending (<?= $pending_count ?>)
            </a>
            <a href="listings.php?filter=active"
               class="btn btn-sm <?= $filter == 'active' ? 'btn-green' : 'btn-outline-green' ?>">
                ✅ Active (<?= $active_count ?>)
            </a>
            <a href="listings.php?filter=rejected"
               class="btn btn-sm <?= $filter == 'rejected' ? 'btn-danger' : 'btn-outline-green' ?>">
                ❌ Rejected (<?= $rejected_count ?>)
            </a>
        </div>

        <!-- listings table -->
        <div class="card bg-card border-green">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-dark-custom mb-0">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Title</th>
                                <th>Seller</th>
                                <th>Area</th>
                                <th>Category</th>
                                <th>Price</th>
                                <th>Status</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($listings && $listings->num_rows > 0): ?>
                                <?php while ($l = $listings->fetch_assoc()): ?>
                                    <tr>
                                        <td><?= $l['listing_id'] ?></td>
                                        <td>
                                            <div style="max-width:160px;">
                                                <div style="font-weight:600; font-size:0.88rem;">
                                                    <?= htmlspecialchars($l['title']) ?>
                                                </div>
                                                <div style="font-size:0.78rem; color:#7A9E87;">
                                                    <?= htmlspecialchars(substr($l['description'], 0, 50)) ?>...
                                                </div>
                                            </div>
                                        </td>
                                        <td style="font-size:0.85rem;">
                                            <?= htmlspecialchars($l['full_name']) ?>
                                        </td>
                                        <td style="font-size:0.83rem;">
                                            <?= htmlspecialchars($l['area']) ?>
                                        </td>
                                        <td style="font-size:0.83rem;">
                                            <?= htmlspecialchars($l['category']) ?>
                                        </td>
                                        <td style="font-size:0.88rem;">
                                            <strong class="text-gold">
                                                R<?= number_format($l['price'], 2) ?>
                                            </strong>
                                        </td>
                                        <td>
                                            <?php
                                            $colours = [
                                                'active'   => 'success',
                                                'pending'  => 'warning',
                                                'rejected' => 'danger',
                                                'sold'     => 'secondary'
                                            ];
                                            $c = $colours[$l['status']] ?? 'secondary';
                                            ?>
                                            <span class="badge bg-<?= $c ?>">
                                                <?= ucfirst($l['status']) ?>
                                            </span>
                                        </td>
                                        <td style="font-size:0.8rem;">
                                            <?= date('d M Y', strtotime($l['created_at'])) ?>
                                        </td>

                                        <!-- action buttons -->
                                        <td>
                                            <div class="d-flex gap-1 flex-wrap">
                                                <?php if ($l['status'] == 'pending'): ?>
                                                    <a href="listings.php?approve=<?= $l['listing_id'] ?>&filter=<?= $filter ?>"
                                                       class="btn btn-success btn-sm">
                                                       ✅ Approve
                                                    </a>
                                                    <a href="listings.php?reject=<?= $l['listing_id'] ?>&filter=<?= $filter ?>"
                                                       class="btn btn-danger btn-sm">
                                                       ❌ Reject
                                                    </a>
                                                <?php elseif ($l['status'] == 'active'): ?>
                                                    <a href="listings.php?reject=<?= $l['listing_id'] ?>&filter=<?= $filter ?>"
                                                       class="btn btn-warning btn-sm">
                                                       Unpublish
                                                    </a>
                                                <?php elseif ($l['status'] == 'rejected'): ?>
                                                    <a href="listings.php?approve=<?= $l['listing_id'] ?>&filter=<?= $filter ?>"
                                                       class="btn btn-success btn-sm">
                                                       Re-approve
                                                    </a>
                                                <?php endif; ?>
                                                <a href="listings.php?delete=<?= $l['listing_id'] ?>&filter=<?= $filter ?>"
                                                   class="btn btn-sm btn-danger"
                                                   onclick="return confirm('Permanently delete this listing?')">
                                                   🗑
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="9" class="text-center text-muted py-4">
                                        No listings found.
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

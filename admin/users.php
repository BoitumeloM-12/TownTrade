<?php
// admin/users.php - manage all registered users
// admin can block/unblock accounts and change user roles
session_start();
include '../db.php';

// admin only page
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 1) {
    header("Location: ../login.php");
    exit();
}

$message = "";

// handle block/unblock action
if (isset($_GET['block']) && is_numeric($_GET['block'])) {
    $uid = (int)$_GET['block'];
    // admin cannot block themselves
    if ($uid != $_SESSION['user_id']) {
        $conn->query("UPDATE users SET status = 'blocked' WHERE user_id = $uid");
        $message = "User has been blocked.";
    }
}

if (isset($_GET['unblock']) && is_numeric($_GET['unblock'])) {
    $uid = (int)$_GET['unblock'];
    $conn->query("UPDATE users SET status = 'active' WHERE user_id = $uid");
    $message = "User has been unblocked.";
}

// handle role change
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['change_role'])) {
    $uid     = (int)$_POST['user_id'];
    $new_role = (int)$_POST['role_id'];

    // only allow valid role ids and dont let admin demote themselves
    if (in_array($new_role, [1, 2, 3]) && $uid != $_SESSION['user_id']) {
        $stmt = $conn->prepare("UPDATE users SET role_id = ? WHERE user_id = ?");
        $stmt->bind_param("ii", $new_role, $uid);
        $stmt->execute();
        $message = "User role updated successfully.";
    }
}

// fetch all users with their role names
$users = $conn->query("
    SELECT u.*, r.role_name
    FROM users u
    JOIN roles r ON u.role_id = r.role_id
    ORDER BY u.created_at DESC
");

// count pending listings for sidebar badge
$pending_listings = $conn->query("SELECT COUNT(*) AS total FROM listings WHERE status = 'pending'")->fetch_assoc()['total'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users – TownTrade Admin</title>
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
        <a href="users.php" class="active">👥 Users</a>
        <a href="listings.php">📦 Listings
            <?php if ($pending_listings > 0): ?>
                <span class="badge bg-warning text-dark ms-1"><?= $pending_listings ?></span>
            <?php endif; ?>
        </a>
        <a href="../index.php">🌿 View Site</a>
        <a href="logout.php" style="color:#E05252;">🚪 Log Out</a>
    </div>

    <!-- main content -->
    <div class="admin-content">

        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3 class="fw-bold text-white mb-1">Manage Users</h3>
                <p class="text-muted mb-0" style="font-size:0.88rem;">
                    View all registered users, change roles and block accounts
                </p>
            </div>
        </div>

        <!-- success message -->
        <?php if ($message): ?>
            <div class="alert alert-success mb-4"><?= $message ?></div>
        <?php endif; ?>

        <!-- users table -->
        <div class="card bg-card border-green">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-dark-custom mb-0">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Area</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Joined</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($users && $users->num_rows > 0): ?>
                                <?php while ($u = $users->fetch_assoc()): ?>
                                    <tr>
                                        <td><?= $u['user_id'] ?></td>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <!-- small avatar with first letter -->
                                                <div style="
                                                    width:32px; height:32px; border-radius:50%;
                                                    background:#1A7A4A; display:flex;
                                                    align-items:center; justify-content:center;
                                                    font-weight:700; font-size:0.8rem; flex-shrink:0;">
                                                    <?= strtoupper(substr($u['full_name'], 0, 1)) ?>
                                                </div>
                                                <?= htmlspecialchars($u['full_name']) ?>
                                            </div>
                                        </td>
                                        <td style="font-size:0.83rem;">
                                            <?= htmlspecialchars($u['email']) ?>
                                        </td>
                                        <td><?= htmlspecialchars($u['area'] ?? '—') ?></td>

                                        <!-- role change dropdown -->
                                        <td>
                                            <?php if ($u['user_id'] == $_SESSION['user_id']): ?>
                                                <!-- cant change your own role -->
                                                <span class="badge bg-primary">Admin (You)</span>
                                            <?php else: ?>
                                                <form method="POST" action="users.php" class="d-flex gap-1">
                                                    <input type="hidden" name="user_id" value="<?= $u['user_id'] ?>">
                                                    <select name="role_id"
                                                            class="form-select form-select-sm bg-dark text-white border-secondary"
                                                            style="width:110px;">
                                                        <option value="1" <?= $u['role_id'] == 1 ? 'selected' : '' ?>>Admin</option>
                                                        <option value="2" <?= $u['role_id'] == 2 ? 'selected' : '' ?>>Seller</option>
                                                        <option value="3" <?= $u['role_id'] == 3 ? 'selected' : '' ?>>Buyer</option>
                                                    </select>
                                                    <button type="submit" name="change_role"
                                                            class="btn btn-green btn-sm">Save</button>
                                                </form>
                                            <?php endif; ?>
                                        </td>

                                        <!-- account status -->
                                        <td>
                                            <?php if ($u['status'] == 'active'): ?>
                                                <span class="badge bg-success">Active</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger">Blocked</span>
                                            <?php endif; ?>
                                        </td>

                                        <td style="font-size:0.82rem;">
                                            <?= date('d M Y', strtotime($u['created_at'])) ?>
                                        </td>

                                        <!-- block/unblock button -->
                                        <td>
                                            <?php if ($u['user_id'] != $_SESSION['user_id']): ?>
                                                <?php if ($u['status'] == 'active'): ?>
                                                    <a href="users.php?block=<?= $u['user_id'] ?>"
                                                       class="btn btn-sm btn-danger"
                                                       onclick="return confirm('Block this user?')">
                                                       Block
                                                    </a>
                                                <?php else: ?>
                                                    <a href="users.php?unblock=<?= $u['user_id'] ?>"
                                                       class="btn btn-sm btn-success">
                                                       Unblock
                                                    </a>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <span class="text-muted" style="font-size:0.78rem;">—</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-4">
                                        No users registered yet.
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

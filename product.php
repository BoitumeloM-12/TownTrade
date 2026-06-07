<?php
// product.php - shows the full details of a single listing
// the listing id comes from the url e.g. product.php?id=3
session_start();
include 'db.php';

// get the listing id from the url
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// if no valid id was given, send them back to listings
if ($id === 0) {
    header("Location: listings.php");
    exit();
}

// fetch the listing and the seller's details in one query
$stmt = $conn->prepare("
    SELECT l.*, u.full_name, u.area, u.phone, u.bio, u.user_id AS seller_id
    FROM listings l
    JOIN users u ON l.user_id = u.user_id
    WHERE l.listing_id = ? AND l.status = 'active'
");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

// if listing doesnt exist or isnt active, go back
if ($result->num_rows === 0) {
    header("Location: listings.php");
    exit();
}

$listing = $result->fetch_assoc();

// handle the contact seller message form
$msg_sent  = false;
$msg_error = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['send_message'])) {

    // buyer must be logged in to send a message
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit();
    }

    $content     = trim($_POST['message']);
    $sender_id   = $_SESSION['user_id'];
    $receiver_id = $listing['seller_id'];

    if (empty($content)) {
        $msg_error = "Please type a message before sending.";
    } elseif ($sender_id == $receiver_id) {
        $msg_error = "You cannot message yourself.";
    } else {
        $msg_stmt = $conn->prepare("
            INSERT INTO messages (sender_id, receiver_id, listing_id, content)
            VALUES (?, ?, ?, ?)
        ");
        $msg_stmt->bind_param("iiis", $sender_id, $receiver_id, $id, $content);

        if ($msg_stmt->execute()) {
            $msg_sent = true;
        } else {
            $msg_error = "Something went wrong. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($listing['title']) ?> – TownTrade</title>
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

    <!-- back link -->
    <a href="listings.php" class="text-muted text-decoration-none mb-4 d-inline-block"
       style="font-size:0.88rem;">
        ← Back to Marketplace
    </a>

    <div class="row g-4">

        <!-- left column: product image and details -->
        <div class="col-lg-7">
            <div class="card bg-card border-green">

                <!-- product image -->
                <?php if (!empty($listing['image_url'])): ?>
                    <img src="uploads/<?= htmlspecialchars($listing['image_url']) ?>"
                         class="card-img-top"
                         style="max-height:380px; object-fit:cover;"
                         alt="<?= htmlspecialchars($listing['title']) ?>">
                <?php else: ?>
                    <div class="d-flex align-items-center justify-content-center"
                         style="height:260px; background:#1F3328; font-size:5rem;">
                        📦
                    </div>
                <?php endif; ?>

                <div class="card-body p-4">
                    <!-- category badge -->
                    <span class="verified-badge mb-2 d-inline-block">
                        🏷 <?= htmlspecialchars($listing['category']) ?>
                    </span>

                    <h2 class="fw-bold text-white mt-2 mb-1">
                        <?= htmlspecialchars($listing['title']) ?>
                    </h2>

                    <p class="text-muted mb-3" style="font-size:0.85rem;">
                        📍 <?= htmlspecialchars($listing['area']) ?>
                        &nbsp;·&nbsp;
                        Listed <?= date('d M Y', strtotime($listing['created_at'])) ?>
                    </p>

                    <!-- price -->
                    <div class="price-tag mb-4" style="font-size:1.8rem;">
                        R<?= number_format($listing['price'], 2) ?>
                    </div>

                    <!-- description -->
                    <h6 class="text-muted fw-bold mb-2" style="font-size:0.8rem; text-transform:uppercase; letter-spacing:0.05em;">
                        Description
                    </h6>
                    <p class="text-white" style="line-height:1.7; font-size:0.95rem;">
                        <?= nl2br(htmlspecialchars($listing['description'])) ?>
                    </p>
                </div>
            </div>
        </div>

        <!-- right column: seller info and contact form -->
        <div class="col-lg-5">

            <!-- seller profile card -->
            <div class="card bg-card border-green mb-3">
                <div class="card-body p-4">
                    <h6 class="text-muted fw-bold mb-3"
                        style="font-size:0.8rem; text-transform:uppercase; letter-spacing:0.05em;">
                        Seller
                    </h6>
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <div class="avatar-circle" style="width:55px; height:55px; font-size:1.3rem;">
                            <?= strtoupper(substr($listing['full_name'], 0, 1)) ?>
                        </div>
                        <div>
                            <div class="fw-bold text-white">
                                <?= htmlspecialchars($listing['full_name']) ?>
                                <span class="verified-badge ms-1">✔ Verified</span>
                            </div>
                            <div class="text-muted" style="font-size:0.82rem;">
                                📍 <?= htmlspecialchars($listing['area']) ?>
                            </div>
                        </div>
                    </div>
                    <?php if (!empty($listing['bio'])): ?>
                        <p class="text-muted" style="font-size:0.85rem; line-height:1.6;">
                            <?= htmlspecialchars(substr($listing['bio'], 0, 120)) ?>...
                        </p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- contact seller form -->
            <div class="card bg-card border-green">
                <div class="card-body p-4">
                    <h6 class="text-muted fw-bold mb-3"
                        style="font-size:0.8rem; text-transform:uppercase; letter-spacing:0.05em;">
                        Contact Seller
                    </h6>

                    <?php if ($msg_sent): ?>
                        <div class="alert alert-success" style="font-size:0.88rem;">
                            ✅ Message sent! The seller will get back to you soon.
                        </div>
                    <?php elseif (!isset($_SESSION['user_id'])): ?>
                        <!-- not logged in -->
                        <p class="text-muted mb-3" style="font-size:0.88rem;">
                            You need to be logged in to contact this seller.
                        </p>
                        <a href="login.php" class="btn btn-gold w-100 fw-bold">Log In to Message</a>

                    <?php else: ?>
                        <!-- logged in - show message form -->
                        <?php if ($msg_error): ?>
                            <div class="alert alert-danger" style="font-size:0.85rem;"><?= $msg_error ?></div>
                        <?php endif; ?>

                        <form method="POST" action="product.php?id=<?= $id ?>">
                            <div class="mb-3">
                                <textarea
                                    name="message"
                                    rows="4"
                                    class="form-control bg-dark text-white border-secondary"
                                    placeholder="Hi, I'm interested in this item. Is it still available?"
                                    required
                                ></textarea>
                            </div>
                            <button type="submit" name="send_message"
                                    class="btn btn-gold w-100 fw-bold">
                                Send Message
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
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

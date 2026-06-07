<?php
// sell.php - this is where sellers post new listings
session_start();
include 'db.php';

// only sellers can access this page
// if not logged in or not a seller, redirect them
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if ($_SESSION['role_id'] != 2) {
    header("Location: index.php");
    exit();
}

$error   = "";
$success = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $title       = trim($_POST['title']);
    $description = trim($_POST['description']);
    $price       = trim($_POST['price']);
    $category    = trim($_POST['category']);
    $user_id     = $_SESSION['user_id'];
    $image_url   = "";

    // basic validation
    if (empty($title) || empty($description) || empty($price) || empty($category)) {
        $error = "Please fill in all required fields.";

    } elseif (!is_numeric($price) || $price <= 0) {
        $error = "Please enter a valid price.";

    } else {

        // handle image upload if one was provided
        if (!empty($_FILES['image']['name'])) {

            $allowed_types = ['image/jpeg', 'image/png', 'image/webp'];
            $file_type     = $_FILES['image']['type'];
            $file_size     = $_FILES['image']['size'];

            if (!in_array($file_type, $allowed_types)) {
                $error = "Only JPG, PNG and WEBP images are allowed.";

            } elseif ($file_size > 2097152) {
                // 2MB max
                $error = "Image must be smaller than 2MB.";

            } else {
                // give the file a unique name so uploads dont overwrite each other
                $ext       = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
                $image_url = uniqid('img_') . '.' . $ext;
                $upload_path = 'uploads/' . $image_url;

                if (!move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
                    $error     = "Image upload failed. Please try again.";
                    $image_url = "";
                }
            }
        }

        // only save to database if no errors
        if (empty($error)) {
            $stmt = $conn->prepare("
                INSERT INTO listings (user_id, title, description, price, category, image_url, status)
                VALUES (?, ?, ?, ?, ?, ?, 'pending')
            ");
            $stmt->bind_param("issdss", $user_id, $title, $description, $price, $category, $image_url);

            if ($stmt->execute()) {
                $success = "Your listing has been submitted and is waiting for admin approval.";
            } else {
                $error = "Something went wrong saving your listing. Please try again.";
            }
        }
    }
}

$categories = ['Food & Produce', 'Clothing', 'Electronics', 'Crafts & Art', 'Services', 'Other'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Post a Listing – TownTrade</title>
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
                <li class="nav-item"><a class="nav-link active" href="sell.php">+ Sell</a></li>
                <li class="nav-item"><a class="nav-link" href="profile.php">My Profile</a></li>
                <li class="nav-item"><a class="nav-link" href="logout.php">Log Out</a></li>
            </ul>
        </div>
    </div>
</nav>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-7">

            <h2 class="fw-bold text-white mb-1">Post a New Listing</h2>
            <p class="text-muted mb-4" style="font-size:0.9rem;">
                Fill in the details below. Your listing will go live after admin approval.
            </p>

            <?php if ($error): ?>
                <div class="alert alert-danger"><?= $error ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success">
                    <?= $success ?>
                    <br><a href="listings.php" class="text-success fw-bold">Browse the Marketplace</a>
                    &nbsp;|&nbsp;
                    <a href="sell.php" class="text-success fw-bold">Post Another Listing</a>
                </div>
            <?php endif; ?>

            <div class="card bg-card border-green">
                <div class="card-body p-4">
                    <form method="POST" action="sell.php" enctype="multipart/form-data">

                        <div class="mb-3">
                            <label class="form-label text-muted">Listing Title *</label>
                            <input
                                type="text"
                                name="title"
                                class="form-control bg-dark text-white border-secondary"
                                placeholder="e.g. Fresh Spinach 500g"
                                required
                            >
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label text-muted">Price (R) *</label>
                                <input
                                    type="number"
                                    name="price"
                                    step="0.01"
                                    min="1"
                                    class="form-control bg-dark text-white border-secondary"
                                    placeholder="e.g. 35.00"
                                    required
                                >
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label text-muted">Category *</label>
                                <select name="category" class="form-select bg-dark text-white border-secondary" required>
                                    <option value="">-- Select a category --</option>
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?= $cat ?>"><?= $cat ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label text-muted">Description *</label>
                            <textarea
                                name="description"
                                rows="4"
                                class="form-control bg-dark text-white border-secondary"
                                placeholder="Describe your product — condition, quantity, how to collect, etc."
                                required
                            ></textarea>
                        </div>

                        <div class="mb-4">
                            <label class="form-label text-muted">Product Image (optional)</label>
                            <input
                                type="file"
                                name="image"
                                accept="image/jpeg, image/png, image/webp"
                                class="form-control bg-dark text-white border-secondary"
                            >
                            <div class="form-text text-muted" style="font-size:0.8rem;">
                                JPG, PNG or WEBP. Max 2MB. Listings with images get more views.
                            </div>
                        </div>

                        <button type="submit" class="btn btn-gold w-100 fw-bold py-2">
                            Submit Listing for Approval
                        </button>

                    </form>
                </div>
            </div>

            <!-- info box explaining the approval process -->
            <div class="card bg-card border-green mt-3">
                <div class="card-body p-3">
                    <p class="text-muted mb-0" style="font-size:0.83rem;">
                        All listings are reviewed by the TownTrade admin team before going live.
                        This usually takes less than 24 hours and helps keep the marketplace safe and trustworthy.
                    </p>
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

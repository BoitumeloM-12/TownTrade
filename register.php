<?php
// register.php - this is the signup page for new users
session_start();
include 'db.php';

$error = "";
$success = "";

// this runs when the form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $name     = trim($_POST['full_name']);
    $email    = trim($_POST['email']);
    $phone    = trim($_POST['phone']);
    $area     = trim($_POST['area']);
    $role     = $_POST['role_id'];  // 2 = Seller, 3 = Buyer
    $password = $_POST['password'];
    $confirm  = $_POST['confirm_password'];

    // basic checks
    if (empty($name) || empty($email) || empty($password)) {
        $error = "Please fill in all required fields.";

    } elseif ($password !== $confirm) {
        $error = "Passwords do not match.";

    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters.";

    } else {
        // check if email is already registered
        $check = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
        $check->bind_param("s", $email);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            $error = "That email is already registered. Please log in.";
        } else {
            // hash the password so it's not stored as plain text
            $hashed = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $conn->prepare("INSERT INTO users (full_name, email, phone, area, password, role_id) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssssi", $name, $email, $phone, $area, $hashed, $role);

            if ($stmt->execute()) {
                $success = "Account created! You can now log in.";
            } else {
                $error = "Something went wrong. Please try again.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register – TownTrade</title>
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
            <ul class="navbar-nav ms-auto">
                <li class="nav-item"><a class="nav-link" href="index.php">Home</a></li>
                <li class="nav-item"><a class="nav-link" href="listings.php">Marketplace</a></li>
                <li class="nav-item"><a class="nav-link" href="login.php">Login</a></li>
                <li class="nav-item"><a class="nav-link active" href="register.php">Register</a></li>
            </ul>
        </div>
    </div>
</nav>

<!-- registration form -->
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card bg-card border-green">
                <div class="card-body p-4">
                    <h3 class="fw-bold mb-1 text-white">Create an Account</h3>
                    <p class="text-muted mb-4">Join the township marketplace</p>

                    <!-- show error or success messages -->
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?= $error ?></div>
                    <?php endif; ?>
                    <?php if ($success): ?>
                        <div class="alert alert-success">
                            <?= $success ?> <a href="login.php">Log in here</a>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="register.php">

                        <div class="mb-3">
                            <label class="form-label text-muted">Full Name *</label>
                            <input type="text" name="full_name" class="form-control bg-dark text-white border-secondary" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label text-muted">Email Address *</label>
                            <input type="email" name="email" class="form-control bg-dark text-white border-secondary" required>
                        </div>

                        <div class="row">
                            <div class="col mb-3">
                                <label class="form-label text-muted">Phone Number</label>
                                <input type="tel" name="phone" class="form-control bg-dark text-white border-secondary">
                            </div>
                            <div class="col mb-3">
                                <label class="form-label text-muted">Your Area</label>
                                <select name="area" class="form-select bg-dark text-white border-secondary">
                                    <option value="">-- Select --</option>
                                    <option>Soweto, Gauteng</option>
                                    <option>Mamelodi, Pretoria</option>
                                    <option>Soshanguve, Pretoria</option>
                                    <option>Khayelitsha, Western Cape</option>
                                    <option>Umlazi, KwaZulu-Natal</option>
                                    <option>Other</option>
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label text-muted">I want to *</label>
                            <select name="role_id" class="form-select bg-dark text-white border-secondary" required>
                                <option value="3">Buy products (Buyer)</option>
                                <option value="2">Sell products (Seller)</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label text-muted">Password *</label>
                            <input type="password" name="password" class="form-control bg-dark text-white border-secondary" required>
                        </div>

                        <div class="mb-4">
                            <label class="form-label text-muted">Confirm Password *</label>
                            <input type="password" name="confirm_password" class="form-control bg-dark text-white border-secondary" required>
                        </div>

                        <button type="submit" class="btn btn-gold w-100 fw-bold">Create My Account</button>

                        <p class="text-center text-muted mt-3 mb-0" style="font-size:0.9rem;">
                            Already have an account? <a href="login.php" class="text-green-light">Log in here</a>
                        </p>

                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

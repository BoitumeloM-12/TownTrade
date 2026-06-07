<?php
// login.php - this is where users sign in
session_start();
include 'db.php';

$error = "";

// if user is already logged in, send them to the right page
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role_id'] == 1) {
        header("Location: admin/index.php");
    } else {
        header("Location: index.php");
    }
    exit();
}

// this runs when the form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $email    = trim($_POST['email']);
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        $error = "Please enter your email and password.";
    } else {
        // look for the user in the database by email
        $stmt = $conn->prepare("SELECT user_id, full_name, password, role_id, status FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows == 1) {
            $user = $result->fetch_assoc();

            // check if account is blocked
            if ($user['status'] == 'blocked') {
                $error = "Your account has been blocked. Please contact support.";

            // verify the password against the hashed one in the database
            } elseif (password_verify($password, $user['password'])) {

                // save user info in the session
                $_SESSION['user_id']   = $user['user_id'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['role_id']   = $user['role_id'];

                // send admin to admin panel, everyone else to homepage
                if ($user['role_id'] == 1) {
                    header("Location: admin/index.php");
                } else {
                    header("Location: index.php");
                }
                exit();

            } else {
                $error = "Incorrect password. Please try again.";
            }
        } else {
            $error = "No account found with that email.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login – TownTrade</title>
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
                <li class="nav-item"><a class="nav-link active" href="login.php">Login</a></li>
                <li class="nav-item"><a class="nav-link" href="register.php">Register</a></li>
            </ul>
        </div>
    </div>
</nav>

<!-- login form -->
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-5">
            <div class="card bg-card border-green">
                <div class="card-body p-4">
                    <h3 class="fw-bold mb-1 text-white">Welcome Back</h3>
                    <p class="text-muted mb-4">Log in to your TownTrade account</p>

                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?= $error ?></div>
                    <?php endif; ?>

                    <form method="POST" action="login.php">

                        <div class="mb-3">
                            <label class="form-label text-muted">Email Address</label>
                            <input type="email" name="email" class="form-control bg-dark text-white border-secondary" required>
                        </div>

                        <div class="mb-4">
                            <label class="form-label text-muted">Password</label>
                            <input type="password" name="password" class="form-control bg-dark text-white border-secondary" required>
                        </div>

                        <button type="submit" class="btn btn-gold w-100 fw-bold">Log In</button>

                        <p class="text-center text-muted mt-3 mb-0" style="font-size:0.9rem;">
                            Don't have an account? <a href="register.php" class="text-green-light">Register here</a>
                        </p>

                    </form>
                </div>
            </div>

            <!-- test accounts card - helpful for presentation -->
            <div class="card bg-card border-green mt-3">
                <div class="card-body p-3">
                    <p class="text-muted mb-2" style="font-size:0.85rem;"><strong class="text-white">Test Accounts:</strong></p>
                    <p class="text-muted mb-1" style="font-size:0.82rem;">🔴 Admin: admin@towntrade.co.za / password</p>
                    <p class="text-muted mb-0" style="font-size:0.82rem;">🟢 Register a new account to test Buyer/Seller</p>
                </div>
            </div>

        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
session_start();
require_once 'includes/db.php';

$error = '';
$csrf_token = bin2hex(random_bytes(32));
$_SESSION['csrf_token'] = $csrf_token;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = "Invalid CSRF token.";
    } else {
        $username = trim($_POST['username']);
        $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
        $password = $_POST['password'];

        // Password strength check: min 8 chars, 1 uppercase, 1 number
        if (!preg_match('/^(?=.*[A-Z])(?=.*\d).{8,}$/', $password)) {
            $error = "Password must be at least 8 characters, include 1 uppercase letter and 1 number.";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            try {
                $stmt = $pdo->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
                $stmt->execute([$username, $email, $hashed_password]);
                unset($_SESSION['csrf_token']);
                header("Location: login.php?registered=1");
                exit;
            } catch (PDOException $e) {
                $error = "Email already in use.";
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
<title>Register - TipVerse</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
<style>
body { font-family:'Inter',sans-serif; background:#f5f6fa; display:flex; justify-content:center; align-items:center; min-height:100vh; margin:0; }
.register-container { background:#fff; padding:40px; border-radius:12px; box-shadow:0 10px 25px rgba(0,0,0,0.1); width:100%; max-width:400px; }
.register-container h2 { font-weight:700; color:#0d6efd; margin-bottom:20px; text-align:center; }
.form-control { border-radius:8px; }
.btn-primary { width:100%; border-radius:8px; }
.register-footer { text-align:center; margin-top:15px; font-size:0.9rem; color:#555; }
.register-footer a { color:#0d6efd; text-decoration:none; }
.register-footer a:hover { text-decoration:underline; }
.alert { font-size:0.9rem; }
</style>
</head>
<body>

<div class="register-container">
    <h2><i class="bi bi-person-plus"></i> Register</h2>

    <?php if($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST">
        <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
        <div class="mb-3">
            <label for="username" class="form-label">Username</label>
            <input name="username" id="username" class="form-control" type="text" placeholder="Enter your username" required>
        </div>
        <div class="mb-3">
            <label for="email" class="form-label">Email</label>
            <input name="email" id="email" class="form-control" type="email" placeholder="Enter your email" required>
        </div>
        <div class="mb-3">
            <label for="password" class="form-label">Password</label>
            <input name="password" id="password" class="form-control" type="password" placeholder="At least 8 chars, 1 uppercase, 1 number" required>
        </div>
        <button type="submit" class="btn btn-primary"><i class="bi bi-box-arrow-in-right"></i> Register</button>
    </form>

    <div class="register-footer">
        Already have an account? <a href="login.php">Login here</a>
    </div>
</div>

</body>
</html>

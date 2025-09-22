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
        $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
        $password = $_POST['password'];

        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            // Reset CSRF token
            unset($_SESSION['csrf_token']);

            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            header("Location: dashboard.php");
            exit;
        } else {
            $error = "Invalid email or password.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login - TipVerse</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
<style>
body { font-family:'Inter',sans-serif; background:#f5f6fa; display:flex; justify-content:center; align-items:center; min-height:100vh; margin:0; }
.login-container { background:#fff; padding:40px; border-radius:12px; box-shadow:0 10px 25px rgba(0,0,0,0.1); width:100%; max-width:400px; }
.login-container h2 { font-weight:700; color:#0d6efd; margin-bottom:20px; text-align:center; }
.form-control { border-radius:8px; }
.btn-primary { width:100%; border-radius:8px; }
.login-footer { text-align:center; margin-top:15px; font-size:0.9rem; color:#555; }
.login-footer a { color:#0d6efd; text-decoration:none; }
.login-footer a:hover { text-decoration:underline; }
.alert { font-size:0.9rem; }
</style>
</head>
<body>

<div class="login-container">
    <h2><i class="bi bi-person-circle"></i> Login</h2>

    <?php if(isset($_GET['registered'])): ?>
        <div class="alert alert-success">Registered successfully! Please log in.</div>
    <?php endif; ?>
    <?php if($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST">
        <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
        <div class="mb-3">
            <label for="email" class="form-label">Email</label>
            <input name="email" id="email" class="form-control" type="email" placeholder="Enter your email" required>
        </div>
        <div class="mb-3">
            <label for="password" class="form-label">Password</label>
            <input name="password" id="password" class="form-control" type="password" placeholder="Enter your password" required>
        </div>
        <button type="submit" class="btn btn-primary"><i class="bi bi-box-arrow-in-right"></i> Login</button>
    </form>

    <div class="login-footer">
        Don't have an account? <a href="register.php">Register here</a>
    </div>
</div>

</body>
</html>

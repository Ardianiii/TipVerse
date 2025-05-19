<?php
session_start();
require_once 'includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $email = $_POST['email'];
  $password = $_POST['password'];

  $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
  $stmt->execute([$email]);
  $user = $stmt->fetch();

if ($user && password_verify($password, $user['password'])) {
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['role'] = $user['role'];  // <-- this line is crucial!
    header("Location: dashboard.php");
    exit;
}



}
?>

<!DOCTYPE html>
<html>
<head>
  <title>Login - TipVerse</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="container mt-5">
  <h2>Login</h2>
  <?php if (isset($_GET['registered'])) echo "<div class='alert alert-success'>Registered successfully! Please log in.</div>"; ?>
  <?php if (isset($error)) echo "<div class='alert alert-danger'>$error</div>"; ?>
  <form method="POST">
    <input name="email" class="form-control mb-2" type="email" placeholder="Email" required>
    <input name="password" class="form-control mb-2" type="password" placeholder="Password" required>
    <button class="btn btn-primary">Login</button>
  </form>
</body>
</html>

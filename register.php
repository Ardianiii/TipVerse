<?php
require_once 'includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $username = $_POST['username'];
  $email = $_POST['email'];
  $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

  $stmt = $pdo->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
  try {
    $stmt->execute([$username, $email, $password]);
    header("Location: login.php?registered=1");
  } catch (PDOException $e) {
    $error = "Email already in use.";
  }
}
?>

<!DOCTYPE html>
<html>
<head>
  <title>Register - TipVerse</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="container mt-5">
  <h2>Register</h2>
  <?php if (isset($error)) echo "<div class='alert alert-danger'>$error</div>"; ?>
  <form method="POST">
    <input name="username" class="form-control mb-2" placeholder="Username" required>
    <input name="email" class="form-control mb-2" type="email" placeholder="Email" required>
    <input name="password" class="form-control mb-2" type="password" placeholder="Password" required>
    <button class="btn btn-primary">Register</button>
  </form>
</body>
</html>

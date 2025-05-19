<?php
session_start();
require_once 'includes/db.php';

if (!isset($_SESSION['user_id'])) {
  header("Location: login.php");
  exit;
}

$user_id = $_SESSION['user_id'];

// Check if user has paid
$stmt = $pdo->prepare("SELECT * FROM payments WHERE user_id = ? AND status = 'completed'");
$stmt->execute([$user_id]);
$hasPaid = $stmt->rowCount() > 0;

// Load predictions: free + (paid if user hasPaid)
if ($hasPaid) {
  $predictions = $pdo->query("SELECT * FROM predictions ORDER BY created_at DESC")->fetchAll();
} else {
  $predictions = $pdo->query("SELECT * FROM predictions WHERE is_free = 1 ORDER BY created_at DESC")->fetchAll();
}
?>

<!DOCTYPE html>
<html>
<head>
  <title>Dashboard - TipVerse</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
  <div class="container">
    <a class="navbar-brand" href="index.php">TipVerse</a>
    <div class="ms-auto">
      <span class="text-white me-3">👋 Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?></span>
      <a href="logout.php" class="btn btn-outline-light">Logout</a>
    </div>
  </div>


</nav>

<div class="container mt-5">
  <h2 class="mb-4 text-center"><?php echo $hasPaid ? 'Your Predictions' : 'Free Tips'; ?></h2>

  <div class="row">
    <?php if (empty($predictions)): ?>
      <p class="text-center">No predictions available at the moment.</p>
    <?php endif; ?>
    
    <?php foreach ($predictions as $p): ?>
      <div class="col-md-4 mb-3">
        <div class="card border-<?php echo $p['is_free'] ? 'success' : 'primary'; ?>">
          <div class="card-body">
            <h5 class="card-title"><?php echo $p['match_title']; ?></h5>
            <p class="card-text">Tip: <strong><?php echo $p['tip']; ?></strong></p>
            <?php if ($p['is_free']): ?>
              <span class="badge bg-success">FREE</span>
            <?php else: ?>
              <span class="badge bg-primary">PAID</span>
            <?php endif; ?>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

  <?php if (!$hasPaid): ?>
    <div class="text-center mt-4">
      <a href="payment.php" class="btn btn-warning">Unlock All Predictions</a>
    </div>
  <?php endif; ?>
</div>

</body>
</html>

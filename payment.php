<?php
session_start();
require_once 'includes/db.php';

if (!isset($_SESSION['user_id'])) {
  header("Location: login.php");
  exit;
}

$user_id = $_SESSION['user_id'];
$message = "";

// Function to get current BTC price in USD from CoinGecko
function getBtcPriceUsd() {
    $url = "https://api.coingecko.com/api/v3/simple/price?ids=bitcoin&vs_currencies=usd";
    $json = @file_get_contents($url);
    if ($json === false) {
        return null;
    }
    $data = json_decode($json, true);
    return $data['bitcoin']['usd'] ?? null;
}

$btcPriceUsd = getBtcPriceUsd();
$priceUsd = 49.99;
$btcAmount = $btcPriceUsd ? round($priceUsd / $btcPriceUsd, 8) : "N/A";

$btcWallet = "bc1qxy2kgdygjrsqtzq2n0yrf2493p83kkfjhx0wlh";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tx_hash = trim($_POST['tx_hash'] ?? '');

    if (empty($tx_hash)) {
        $message = "Please enter the transaction hash.";
    } else {
        // Insert payment with status 'pending'
        $stmt = $pdo->prepare("INSERT INTO payments (user_id, status, tx_hash) VALUES (?, 'pending', ?)");
        $stmt->execute([$user_id, $tx_hash]);
        $message = "Payment submitted! Awaiting confirmation.";
    }
}

// --- Check if user has any completed payment ---
$stmt = $pdo->prepare("SELECT status FROM payments WHERE user_id = ? AND status = 'completed' ORDER BY created_at DESC LIMIT 1");
$stmt->execute([$user_id]);
$payment = $stmt->fetch();

$hasPaid = $payment ? true : false;

// You can now use $hasPaid to show premium content or not
?>

<!DOCTYPE html>
<html>
<head>
    <title>Payment - TipVerse</title>
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
  <h2 class="mb-4 text-center">Make a Payment</h2>

  <?php if ($message): ?>
    <div class="alert alert-info"><?php echo htmlspecialchars($message); ?></div>
  <?php endif; ?>

  <div class="card mx-auto" style="max-width: 500px;">
    <div class="card-body">
      <h5 class="card-title">Send Crypto Payment</h5>
      <p>Please send <strong>$49.99 worth of BTC</strong> to the wallet below:</p>
      <?php if ($btcAmount !== "N/A"): ?>
        <p><strong>Amount:</strong> <?php echo $btcAmount; ?> BTC</p>
      <?php else: ?>
        <p><em>Unable to fetch BTC price. Please try again later.</em></p>
      <?php endif; ?>
      <div class="mb-3 p-3 bg-light text-center" style="font-family: monospace; font-size: 1.2em;">
        <strong><?php echo $btcWallet; ?></strong>
      </div>

      <!-- QR code for scanning payment -->
      <?php if ($btcAmount !== "N/A"): 
          $btcUri = "bitcoin:$btcWallet?amount=$btcAmount";
      ?>
      <div class="text-center mb-3">
        <img src="assets/img/btc-address.jpeg=<?php echo urlencode($btcUri); ?>" alt="Bitcoin Payment QR Code" />
        <p class="small mt-2">Scan QR code to pay</p>
      </div>
      <?php endif; ?>

      <p>After sending, paste the transaction hash (TX ID) below to confirm your payment.</p>

      <form method="post" action="payment.php">
        <div class="mb-3">
          <label for="tx_hash" class="form-label">Transaction Hash (TX ID)</label>
          <input type="text" class="form-control" id="tx_hash" name="tx_hash" required>
        </div>
        <button type="submit" class="btn btn-primary w-100">Submit Payment</button>
      </form>
    </div>
  </div>

  <div class="text-center mt-4">
    <a href="dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
  </div>
</div>

</body>
</html>

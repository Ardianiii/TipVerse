<?php
session_start();
require_once 'includes/db.php';

if(!isset($_SESSION['user_id'])){
    header("Location: login.php"); exit;
}

$user_id = $_SESSION['user_id'];
$message = "";

// Pricing tiers
$pricing_tiers = [
    'medium'=>['usd'=>20],
    'premium'=>['usd'=>75]
];

// Crypto wallets
$crypto_options = [
    'btc'=>['label'=>'Bitcoin','wallet'=>'bc1qxy2kgdygjrsqtzq2n0yrf2493p83kkfjhx0wlh'],
    'trx'=>['label'=>'TRON','wallet'=>'TXXXXXXTRXADDRESS'],
    'usdt_bep20'=>['label'=>'USDT (BEP20)','wallet'=>'BEP20_WALLET_ADDRESS'],
    'usdt_trc20'=>['label'=>'USDT (TRC20)','wallet'=>'TRC20_WALLET_ADDRESS']
];

// Handle payment form
if($_SERVER['REQUEST_METHOD']==='POST'){
    $tx_hash = trim($_POST['tx_hash'] ?? '');
    $selected_crypto = $_POST['crypto'] ?? '';
    $tier = $_POST['tier'] ?? '';

    if(empty($tx_hash) || empty($selected_crypto) || !isset($crypto_options[$selected_crypto]) || !isset($pricing_tiers[$tier])){
        $message = "Please select packet, cryptocurrency and enter TX ID.";
    } else {
        $stmt = $pdo->prepare("INSERT INTO payments (user_id, status, tx_hash, crypto, tier) VALUES (?,?,?,?,?)");
        $stmt->execute([$user_id,'pending',$tx_hash,$selected_crypto,$tier]);
        $message = "Payment submitted! Awaiting confirmation.";
    }
}
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
<span class="text-white me-3">👋 Welcome, <?= htmlspecialchars($_SESSION['username']) ?></span>
<a href="logout.php" class="btn btn-outline-light">Logout</a>
</div>
</div>
</nav>

<div class="container mt-5">
<h2 class="mb-4 text-center">Make a Payment</h2>

<?php if($message): ?>
<div class="alert alert-info"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>

<div class="card mx-auto" style="max-width:500px;">
<div class="card-body">
<form method="post" action="payment.php">
<div class="mb-3">
<label for="tier" class="form-label">Select Packet</label>
<select name="tier" id="tier" class="form-select" required>
<option value="medium">Medium Packet - $20</option>
<option value="premium">Premium Packet - $75</option>
</select>
</div>

<div class="mb-3">
<label for="crypto" class="form-label">Select Cryptocurrency</label>
<select name="crypto" id="crypto" class="form-select" required>
<?php foreach($crypto_options as $key=>$c): ?>
<option value="<?= $key ?>"><?= $c['label'] ?></option>
<?php endforeach; ?>
</select>
</div>

<div class="mb-3">
<label for="tx_hash" class="form-label">Transaction Hash (TX ID)</label>
<input type="text" class="form-control" id="tx_hash" name="tx_hash" required>
</div>

<div class="mb-3 p-3 bg-light text-center" id="wallet-display">
<strong>Wallet Address will appear here after selecting crypto</strong>
</div>

<button type="submit" class="btn btn-primary w-100">Submit Payment</button>
</form>
</div>
</div>

<div class="text-center mt-4">
<a href="dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
</div>
</div>

<script>
const cryptoOptions = <?php echo json_encode($crypto_options); ?>;
const cryptoSelect = document.getElementById('crypto');
const walletDisplay = document.getElementById('wallet-display');

cryptoSelect.addEventListener('change', function(){
    const selected = this.value;
    walletDisplay.innerHTML = "<strong>"+cryptoOptions[selected].wallet+"</strong>";
});
</script>

</body>
</html>

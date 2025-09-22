<?php
session_start();
$is_logged_in = isset($_SESSION['user_id']);
require_once 'includes/db.php';

// Pricing tiers
$pricing_tiers = [
    'free' => ['label'=>'Free Packet','odds'=>[1.50],'price_usd'=>0, 'color'=>'#28a745'],   // green
    'medium' => ['label'=>'Medium Packet','odds'=>range(3.0,10.0,0.35),'price_usd'=>20, 'color'=>'#17a2b8'], // teal
    'premium' => ['label'=>'Premium Packet','odds'=>[20.0],'price_usd'=>75, 'color'=>'#6610f2'] // purple
];

// Fetch user payment status
$user = null;
if ($is_logged_in) {
    $stmt = $pdo->prepare("SELECT payment_completed FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
}

// Teams
$teams = [
    ['name'=>'Manchester City','logo'=>'https://upload.wikimedia.org/wikipedia/en/e/eb/Manchester_City_FC_badge.svg'],
    ['name'=>'Arsenal','logo'=>'https://upload.wikimedia.org/wikipedia/en/5/53/Arsenal_FC.svg'],
    ['name'=>'Real Madrid','logo'=>'https://upload.wikimedia.org/wikipedia/en/5/56/Real_Madrid_CF.svg'],
    ['name'=>'Bayern Munich','logo'=>'https://upload.wikimedia.org/wikipedia/commons/1/1f/FC_Bayern_München_logo_(2017).svg'],
    ['name'=>'Paris Saint-Germain','logo'=>'https://upload.wikimedia.org/wikipedia/en/a/a7/Paris_Saint-Germain_F.C..svg']
];

// Create matches
function createMatchesForDate($date, $teams, $count=10){
    global $pricing_tiers;
    $matches=[];
    $total_teams=count($teams);
    for($i=0;$i<$count;$i++){
        $team1=$teams[$i % $total_teams];
        $team2=$teams[($i+1) % $total_teams];
        $tier=($i%3===0)?'free':(($i%3===1)?'medium':'premium');
        $odds=$pricing_tiers[$tier]['odds'][array_rand($pricing_tiers[$tier]['odds'])];
        $matches[]=[
            'teams'=>$team1['name'].' vs '.$team2['name'],
            'logos'=>[$team1['logo'],$team2['logo']],
            'tip'=>($tier==='free')?'Tip: '.['Home Win','Draw','Over 2.5 Goals','Both Teams to Score'][$i%4]:null,
            'date'=>$date.' '.sprintf('%02d:00',14+$i),
            'odds'=>round($odds,2),
            'tier'=>$tier,
            'status'=>($tier==='free')?'free':'locked'
        ];
    }
    return $matches;
}

// Matches by date
$matches_by_date=[
    '2025-05-20'=>createMatchesForDate('2025-05-20',$teams),
    '2025-05-21'=>createMatchesForDate('2025-05-21',$teams),
    '2025-05-22'=>createMatchesForDate('2025-05-22',$teams)
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>TipVerse - Professional Predictions</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
<style>
body { font-family:'Inter',sans-serif; background:#f5f6fa; color:#212121; margin:0; padding:0; }
.navbar { background:#fff; box-shadow:0 2px 6px rgba(0,0,0,0.05); }
.nav-link { color:#212121; font-weight:500; }
.nav-link:hover { color:#0d6efd; }
header { text-align:center; padding:50px 20px; }
header h1 { font-weight:700; font-size:2.5rem; color:#0d6efd; }
header p { font-size:1.1rem; color:#555; }

/* Cards */
.match-card { border-radius:12px; border:1px solid #e0e0e0; background:#fff; transition:transform .3s ease,box-shadow .3s ease; position:relative; }
.match-card:hover { transform:translateY(-5px); box-shadow:0 10px 20px rgba(0,0,0,0.1); }
.team-logo { height:50px; width:auto; }
.card-body { padding:20px; }
.locked-overlay { position:absolute; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.65); color:#fff; display:flex; justify-content:center; align-items:center; text-align:center; border-radius:12px; font-weight:600; font-size:1rem; }
.tier-badge { display:inline-block; padding:0.35em 0.75em; border-radius:10px; font-size:0.85rem; font-weight:500; color:#fff; margin-top:5px; }

/* Footer */
footer { text-align:center; padding:30px 20px; background:#fff; border-top:1px solid #e0e0e0; color:#777; font-size:0.9rem; }

/* Responsive */
@media(max-width:768px){ .team-logo { height:40px; } header h1{font-size:2rem;} header p{font-size:1rem;} }
</style>
</head>
<body>

<header>
<h1><i class="bi bi-bar-chart-line-fill"></i> TipVerse</h1>
<p>Professional Betting Predictions — Free & Premium</p>
</header>

<nav class="navbar navbar-expand-lg">
<div class="container">
<a class="navbar-brand fw-bold" href="#"><i class="bi bi-house-fill"></i> TipVerse</a>
<button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
<span class="navbar-toggler-icon"></span>
</button>
<div class="collapse navbar-collapse" id="navbarNav">
<ul class="navbar-nav ms-auto">
<?php if($is_logged_in): ?>
<li class="nav-item"><a class="nav-link" href="dashboard.php"><i class="bi bi-speedometer2"></i> Dashboard</a></li>
<li class="nav-item"><a class="nav-link" href="logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
<?php else: ?>
<li class="nav-item"><a class="nav-link" href="login.php"><i class="bi bi-person-circle"></i> Login</a></li>
<li class="nav-item"><a class="nav-link" href="register.php"><i class="bi bi-person-plus"></i> Register</a></li>
<?php endif; ?>
</ul>
</div>
</div>
</nav>

<main class="container my-5">
<?php foreach($matches_by_date as $date => $matches): ?>
<section class="mb-5">
<h2 class="mb-4 fw-bold text-dark"><?= date('F j, Y',strtotime($date)) ?></h2>
<div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
<?php foreach($matches as $match): ?>
<div class="col">
<div class="card match-card">
<?php if($match['status']==='locked' && (!$is_logged_in || ($is_logged_in && !$user['payment_completed']))): ?>
<div class="locked-overlay"><i class="bi bi-lock-fill"></i> Premium Tip – <a href="payment.php" class="text-warning text-decoration-none fw-bold">Unlock Now</a></div>
<?php endif; ?>
<div class="card-body text-center">
<div class="d-flex justify-content-center align-items-center mb-3">
<img src="<?= $match['logos'][0] ?>" alt="Team 1" class="team-logo me-2" />
<strong class="text-muted mx-2">vs</strong>
<img src="<?= $match['logos'][1] ?>" alt="Team 2" class="team-logo ms-2" />
</div>
<h5 class="card-title"><?= htmlspecialchars($match['teams']) ?></h5>
<p class="text-muted mb-2"><i class="bi bi-clock-fill"></i> <?= date('H:i - M d, Y',strtotime($match['date'])) ?></p>
<?php if($match['status']==='free' || ($is_logged_in && $user['payment_completed'])): ?>
<p class="mb-2"><?= htmlspecialchars($match['tip']) ?></p>
<span class="tier-badge" style="background:<?= $pricing_tiers[$match['tier']]['color'] ?>">
<i class="bi bi-star-fill"></i> <?= $pricing_tiers[$match['tier']]['label'] ?> – Odds: <?= number_format($match['odds'],2) ?>
</span>
<?php endif; ?>
</div>
</div>
</div>
<?php endforeach; ?>
</div>
</section>
<?php endforeach; ?>
</main>

<footer>
&copy; 2025 TipVerse. All Rights Reserved.
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

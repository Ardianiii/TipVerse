<?php
session_start();
$is_logged_in = isset($_SESSION['user_id']);

require_once 'includes/db.php';

// Fetch predictions based on login and payment status
if (!$is_logged_in) {
    // Not logged in: only show free/unlocked predictions
    $predictions = $pdo->query("SELECT * FROM predictions WHERE locked = 0")->fetchAll();
} else {
    // Logged in: check if payment completed
    $stmt = $pdo->prepare("SELECT payment_completed FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();

    if ($user && $user['payment_completed'] == 1) {
        // Payment completed: show all predictions
        $predictions = $pdo->query("SELECT * FROM predictions")->fetchAll();
    } else {
        // Payment not done: show only free/unlocked and show warning
        $predictions = $pdo->query("SELECT * FROM predictions WHERE locked = 0")->fetchAll();
        $payment_warning = "<p class='alert alert-warning'>Complete your payment to unlock premium predictions!</p>";
    }
}

// Team logos and names
$teams = [
    ['name' => 'Manchester City', 'logo' => 'https://upload.wikimedia.org/wikipedia/en/e/eb/Manchester_City_FC_badge.svg'],
    ['name' => 'Arsenal', 'logo' => 'https://upload.wikimedia.org/wikipedia/en/5/53/Arsenal_FC.svg'],
    ['name' => 'Real Madrid', 'logo' => 'https://upload.wikimedia.org/wikipedia/en/5/56/Real_Madrid_CF.svg'],
    ['name' => 'Bayern Munich', 'logo' => 'https://upload.wikimedia.org/wikipedia/commons/1/1f/FC_Bayern_München_logo_(2017).svg'],
    ['name' => 'Paris Saint-Germain', 'logo' => 'https://upload.wikimedia.org/wikipedia/en/a/a7/Paris_Saint-Germain_F.C..svg'],
    ['name' => 'Juventus', 'logo' => 'https://upload.wikimedia.org/wikipedia/en/3/3e/Juventus_FC_2017_logo.svg'],
    ['name' => 'Liverpool', 'logo' => 'https://upload.wikimedia.org/wikipedia/en/0/0c/Liverpool_FC.svg'],
    ['name' => 'Chelsea', 'logo' => 'https://upload.wikimedia.org/wikipedia/en/c/cc/Chelsea_FC.svg'],
    ['name' => 'Borussia Dortmund', 'logo' => 'https://upload.wikimedia.org/wikipedia/commons/6/67/Borussia_Dortmund_logo.svg'],
    ['name' => 'Inter Milan', 'logo' => 'https://upload.wikimedia.org/wikipedia/commons/0/05/FC_Internazionale_Milano_2014_logo.svg'],
    ['name' => 'AC Milan', 'logo' => 'https://upload.wikimedia.org/wikipedia/commons/d/d0/Logo_of_AC_Milan.svg'],
    ['name' => 'Tottenham Hotspur', 'logo' => 'https://upload.wikimedia.org/wikipedia/en/b/b4/Tottenham_Hotspur.svg'],
    ['name' => 'Leicester City', 'logo' => 'https://upload.wikimedia.org/wikipedia/en/6/63/Leicester_City_crest.svg'],
    ['name' => 'Napoli', 'logo' => 'https://upload.wikimedia.org/wikipedia/commons/2/2d/SSC_Neapel_Logo.svg'],
    ['name' => 'Ajax', 'logo' => 'https://upload.wikimedia.org/wikipedia/en/7/79/Ajax_Amsterdam.svg'],
    ['name' => 'Atletico Madrid', 'logo' => 'https://upload.wikimedia.org/wikipedia/en/f/f4/Atletico_Madrid_2017_logo.svg'],
    ['name' => 'Sevilla', 'logo' => 'https://upload.wikimedia.org/wikipedia/en/f/f4/Sevilla_FC_logo.svg'],
    ['name' => 'Valencia', 'logo' => 'https://upload.wikimedia.org/wikipedia/en/7/77/Valencia_CF_logo.svg'],
    ['name' => 'Roma', 'logo' => 'https://upload.wikimedia.org/wikipedia/en/f/f7/AS_Roma_logo.svg'],
    ['name' => 'Monaco', 'logo' => 'https://upload.wikimedia.org/wikipedia/en/e/e3/AS_Monaco_FC.svg']
];

// Function to create matches for a date
function createMatchesForDate($date, $teams, $count = 10) {
    $matches = [];
    $total_teams = count($teams);
    for ($i = 0; $i < $count; $i++) {
        $team1 = $teams[$i % $total_teams];
        $team2 = $teams[($i + 1) % $total_teams];
        $is_free = ($i % 3) === 0; // every 3rd match free
        $matches[] = [
            'teams' => $team1['name'] . ' vs ' . $team2['name'],
            'logos' => [$team1['logo'], $team2['logo']],
            'tip' => $is_free ? 'Tip: ' . ['Home Win', 'Draw', 'Over 2.5 Goals', 'Both Teams to Score'][$i % 4] : null,
            'date' => $date . ' ' . sprintf('%02d:00', 14 + $i),
            'odds' => round(1.5 + $i * 0.1, 2),
            'status' => $is_free ? 'free' : 'locked',
        ];
    }
    return $matches;
}

$matches_by_date = [
    '2025-05-20' => createMatchesForDate('2025-05-20', $teams),
    '2025-05-21' => createMatchesForDate('2025-05-21', $teams),
    '2025-05-22' => createMatchesForDate('2025-05-22', $teams),
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>TipVerse - Match Predictions</title>
  <link
    href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css"
    rel="stylesheet"
  />
  <link
    href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css"
    rel="stylesheet"
  />
  <style>
    .team-logo {
      width: 50px;
      height: 50px;
      object-fit: contain;
      filter: drop-shadow(0 0 1px rgba(0,0,0,0.2));
      background: white;
      padding: 2px;
      border-radius: 6px;
    }
    .match-card {
      position: relative;
      overflow: hidden;
      transition: transform 0.2s ease-in-out;
    }
    .match-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 6px 12px rgba(0,0,0,0.15);
    }
    .locked-overlay {
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background-color: rgba(255, 255, 255, 0.85);
      display: flex;
      justify-content: center;
      align-items: center;
      font-size: 1.5rem;
      color: #444;
      z-index: 10;
      cursor: default;
      font-weight: 600;
      flex-direction: column;
      padding: 1rem;
      text-align: center;
    }
    .locked-overlay i {
      font-size: 2rem;
      margin-bottom: 0.25rem;
    }
  </style>
</head>
<body>
  <nav class="navbar navbar-expand-lg navbar-dark bg-dark px-3">
    <a class="navbar-brand" href="#">TipVerse</a>
    <button
      class="navbar-toggler"
      type="button"
      data-bs-toggle="collapse"
      data-bs-target="#navbarNav"
      aria-controls="navbarNav"
      aria-expanded="false"
      aria-label="Toggle navigation"
    >
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav ms-auto">
        <?php if ($is_logged_in): ?>
          <li class="nav-item">
            <a class="nav-link" href="dashboard.php">Dashboard</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="logout.php">Logout</a>
          </li>
        <?php else: ?>
          <li class="nav-item">
            <a class="nav-link" href="login.php">Login</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="register.php">Register</a>
          </li>
        <?php endif; ?>
      </ul>
    </div>
  </nav>

  <main class="container my-5">
    <h1 class="mb-4 text-center">Match Predictions</h1>

    <?php
    // Show payment warning if exists
    if (!empty($payment_warning)) {
        echo $payment_warning;
    }
    ?>

    <?php foreach ($matches_by_date as $date => $matches): ?>
      <section class="mb-5">
        <h2 class="mb-4 text-primary"><?= date('F j, Y', strtotime($date)) ?></h2>
        <div class="row gy-4">
         
<?php foreach ($matches as $match): ?>
          <div class="col-md-6 col-lg-4">
          <div class="card shadow match-card">
            <?php if ($match['status'] === 'locked' && (!$is_logged_in || ($is_logged_in && !$user['payment_completed']))): ?>
              <div class="locked-overlay">
                <i class="bi bi-lock-fill"></i>
                Premium Tip – <a href="payment.php" class="text-decoration-none text-danger">Unlock</a>
              </div>
            <?php endif; ?>
            <div class="card-body">
              <div class="d-flex justify-content-between align-items-center mb-3">
                <img src="<?= $match['logos'][0] ?>" alt="Team 1" class="team-logo" />
                <strong>vs</strong>
                <img src="<?= $match['logos'][1] ?>" alt="Team 2" class="team-logo" />
              </div>
              <h5 class="card-title"><?= htmlspecialchars($match['teams']) ?></h5>
              <p class="card-text text-muted mb-1"><?= date('H:i - M d, Y', strtotime($match['date'])) ?></p>
              <?php if ($match['status'] === 'free' || ($is_logged_in && $user['payment_completed'])): ?>
                <p class="card-text"><?= htmlspecialchars($match['tip']) ?></p>
                <p class="card-text"><strong>Odds:</strong> <?= number_format($match['odds'], 2) ?></p>
              <?php endif; ?>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </section>
<?php endforeach; ?>

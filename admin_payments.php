<?php
session_start();
require_once 'includes/db.php';

// Composer autoloader for PHPMailer
require 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// --- ADMIN AUTH CHECK ---
// if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
//     header("Location: login.php");
//     exit;
// }

// Debug session (remove or comment out in production)
// echo '<pre>'; print_r($_SESSION); echo '</pre>';

// --- MARK PAYMENT AS COMPLETE ---
if (isset($_GET['mark_complete'])) {
    $payment_id = (int)$_GET['mark_complete'];

    // Fetch payment info + user details for email
    $stmt = $pdo->prepare("SELECT p.tx_hash, u.email, u.username, p.user_id FROM payments p JOIN users u ON p.user_id = u.id WHERE p.id = ?");
    $stmt->execute([$payment_id]);
    $payment = $stmt->fetch();

    if ($payment) {
        // Update payment status
        $stmt = $pdo->prepare("UPDATE payments SET status = 'completed' WHERE id = ?");
        $stmt->execute([$payment_id]);

        // Update user's payment_completed status to 1 (paid)
        $updateUser = $pdo->prepare("UPDATE users SET payment_completed = 1 WHERE id = ?");
        $updateUser->execute([$payment['user_id']]);

        // Send confirmation email
        sendPaymentConfirmationEmail($payment['email'], $payment['username'], $payment['tx_hash']);
    }

    header("Location: admin_payments.php");
    exit;
}

// --- FETCH ALL PAYMENTS ---
$payments = $pdo->query("
    SELECT p.id, p.user_id, p.status, p.tx_hash, p.created_at, u.username
    FROM payments p
    JOIN users u ON p.user_id = u.id
    ORDER BY p.created_at DESC
")->fetchAll();

function sendPaymentConfirmationEmail($toEmail, $username, $txHash) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'your-email@gmail.com';        // CHANGE THIS!
        $mail->Password   = 'your-app-password';           // CHANGE THIS!
        $mail->SMTPSecure = 'tls';
        $mail->Port       = 587;

        $mail->setFrom('no-reply@tipverse.com', 'TipVerse');
        $mail->addAddress($toEmail, $username);

        $mail->isHTML(false);
        $mail->Subject = 'Your Payment is Confirmed - TipVerse';
        $mail->Body = "Hi $username,\n\n"
                    . "Your payment with transaction hash $txHash has been confirmed. "
                    . "You now have full access to the premium betting predictions on TipVerse.\n\n"
                    . "Thank you for your support!\n"
                    . "— The TipVerse Team";

        $mail->send();
    } catch (Exception $e) {
        error_log("Mailer Error: {$mail->ErrorInfo}");
    }
}
?>

<!DOCTYPE html>
<html>
<head>
  <title>Admin Payments - TipVerse</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
  <h2 class="mb-4 text-center">Admin Payments Panel</h2>

  <table class="table table-striped">
    <thead>
      <tr>
        <th>Payment ID</th>
        <th>User</th>
        <th>Status</th>
        <th>TX Hash</th>
        <th>Submitted At</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($payments as $payment): ?>
        <tr>
          <td><?= $payment['id'] ?></td>
          <td><?= htmlspecialchars($payment['username']) ?></td>
          <td>
            <?php if ($payment['status'] === 'pending'): ?>
              <span class="badge bg-warning text-dark">Pending</span>
            <?php else: ?>
              <span class="badge bg-success">Completed</span>
            <?php endif; ?>
          </td>
          <td style="font-family: monospace; font-size: 0.9em;"><?= htmlspecialchars($payment['tx_hash']) ?></td>
          <td><?= $payment['created_at'] ?></td>
          <td>
            <?php if ($payment['status'] === 'pending'): ?>
              <a href="admin_payments.php?mark_complete=<?= $payment['id'] ?>" class="btn btn-sm btn-success" onclick="return confirm('Mark payment as completed?')">Mark Completed</a>
            <?php else: ?>
              <span class="text-muted">—</span>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>

  <div class="text-center mt-4">
    <a href="dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
  </div>
</div>
</body>
</html>

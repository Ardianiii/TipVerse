<?php
require_once 'includes/db.php';

// Change password here before running
$adminUsername = 'admin';
$adminPassword = 'StrongAdminPass123'; // Change this!
$hashedPassword = password_hash($adminPassword, PASSWORD_DEFAULT);

$stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
$stmt->execute([$adminUsername]);
$user = $stmt->fetch();

if ($user) {
    echo "Admin user already exists.";
} else {
    $stmt = $pdo->prepare("INSERT INTO users (username, password, email) VALUES (?, ?, ?)");
    $stmt->execute([$adminUsername, $hashedPassword, 'admin@tipverse.com']);
    echo "Admin user created successfully.";
}

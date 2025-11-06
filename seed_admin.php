<?php
// seed_admin.php — запусти один раз
require_once __DIR__ . '/init.php';

$username = 'admin';
$email = 'admin@example.com';
$password = 'admin123'; // поменяй на безопасный

// Проверим есть ли админ
$stmt = $pdo->prepare("SELECT id FROM users WHERE username = :u LIMIT 1");
$stmt->execute([':u' => $username]);
if ($stmt->fetch()) {
    echo "Admin already exists\n";
    exit;
}

$hash = password_hash($password, PASSWORD_DEFAULT);
$ins = $pdo->prepare("INSERT INTO users (username, email, password_hash, role) VALUES (:u,:e,:p,'admin')");
$ins->execute([':u' => $username, ':e' => $email, ':p' => $hash]);

echo "Admin created: $username\n";

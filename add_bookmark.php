<?php
// add_bookmark.php
require_once __DIR__ . '/init.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo "Method not allowed";
    exit;
}

if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
    die('Invalid CSRF token');
}

// Ожидаемые поля: anime_id, anime_title, anime_url, anime_image (все строки)
$anime_id = isset($_POST['anime_id']) ? intval($_POST['anime_id']) : 0;
$anime_title = trim($_POST['anime_title'] ?? '');
$anime_url = trim($_POST['anime_url'] ?? '');
$anime_image = trim($_POST['anime_image'] ?? '');

if (!$anime_id || $anime_title === '' || $anime_url === '') {
    $_SESSION['flash_error'] = 'Неверные данные для закладки.';
    header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? 'index.php'));
    exit;
}

// Проверим, нет ли уже такой закладки у пользователя (по anime_id)
$stmt = $pdo->prepare("SELECT id FROM bookmarks WHERE user_id = :uid AND anime_id = :aid LIMIT 1");
$stmt->execute([':uid' => current_user_id(), ':aid' => $anime_id]);
if ($stmt->fetch()) {
    $_SESSION['flash_info'] = 'Аниме уже в закладках.';
    header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? 'index.php'));
    exit;
}

// Вставка
$ins = $pdo->prepare("INSERT INTO bookmarks (user_id, anime_id, anime_title, anime_url, anime_image) VALUES (:uid,:aid,:title,:url,:img)");
$ins->execute([
    ':uid' => current_user_id(),
    ':aid' => $anime_id,
    ':title' => $anime_title,
    ':url' => $anime_url,
    ':img' => $anime_image
]);

log_activity($pdo, current_user_id(), 'add_bookmark', "Добавлено в закладки: {$anime_title} (id={$anime_id})");

$_SESSION['flash_success'] = 'Добавлено в закладки.';
header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? 'bookmarks.php'));
exit;

<?php
// add_review.php
require_once __DIR__ . '/init.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit;
}
if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
    die('Invalid CSRF token');
}

$user_id = current_user_id();
$anime_id = isset($_POST['anime_id']) ? intval($_POST['anime_id']) : 0;
$anime_title = trim($_POST['anime_title'] ?? '');
$rating = isset($_POST['rating']) ? intval($_POST['rating']) : null;
$review_text = trim($_POST['review_text'] ?? '');

if (!$anime_id || $anime_title === '' || $rating === null || $rating < 1 || $rating > 10) {
    $_SESSION['flash_error'] = 'Неверные данные отзыва (оценка 1-10 и название обязательны).';
    header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? 'index.php'));
    exit;
}

// Если пользователь уже оставлял отзыв для этого anime — обновим, иначе вставим
$stmt = $pdo->prepare("SELECT id FROM reviews WHERE user_id = :uid AND anime_id = :aid LIMIT 1");
$stmt->execute([':uid' => $user_id, ':aid' => $anime_id]);
$existing = $stmt->fetch();

if ($existing) {
    $upd = $pdo->prepare("UPDATE reviews SET rating = :rating, review_text = :text, updated_at = NOW(), anime_title = :title WHERE id = :id");
    $upd->execute([
        ':rating' => $rating,
        ':text' => $review_text,
        ':title' => $anime_title,
        ':id' => $existing['id']
    ]);
    log_activity($pdo, $user_id, 'update_review', "Обновлен отзыв id={$existing['id']} anime_id={$anime_id}");
    $_SESSION['flash_success'] = 'Отзыв обновлён.';
} else {
    $ins = $pdo->prepare("INSERT INTO reviews (user_id, anime_id, anime_title, rating, review_text) VALUES (:uid,:aid,:title,:rating,:text)");
    $ins->execute([
        ':uid' => $user_id,
        ':aid' => $anime_id,
        ':title' => $anime_title,
        ':rating' => $rating,
        ':text' => $review_text
    ]);
    $rid = $pdo->lastInsertId();
    log_activity($pdo, $user_id, 'add_review', "Добавлен отзыв id={$rid} anime_id={$anime_id}");
    $_SESSION['flash_success'] = 'Отзыв опубликован.';
}

header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? 'index.php'));
exit;

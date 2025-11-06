<?php
// delete_review.php
require_once __DIR__ . '/init.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit;
}
if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
    die('Invalid CSRF');
}

$id = isset($_POST['id']) ? intval($_POST['id']) : 0;
if (!$id) {
    $_SESSION['flash_error'] = 'Неправильный id';
    header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? 'index.php'));
    exit;
}

// Получаем отзыв
$stmt = $pdo->prepare("SELECT * FROM reviews WHERE id = :id LIMIT 1");
$stmt->execute([':id' => $id]);
$rev = $stmt->fetch();
if (!$rev) {
    $_SESSION['flash_error'] = 'Отзыв не найден';
    header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? 'index.php'));
    exit;
}

// Проверка прав: владелец или админ
if ($rev['user_id'] != current_user_id() && ($_SESSION['role'] ?? '') !== 'admin') {
    http_response_code(403);
    echo "Нет доступа";
    exit;
}

// Удаляем
$del = $pdo->prepare("DELETE FROM reviews WHERE id = :id");
$del->execute([':id' => $id]);
log_activity($pdo, current_user_id(), 'delete_review', "Удалён отзыв id={$id} anime_id={$rev['anime_id']}");

$_SESSION['flash_success'] = 'Отзыв удалён.';
header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? 'index.php'));
exit;

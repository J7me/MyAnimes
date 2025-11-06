<?php
// remove_bookmark.php
require_once __DIR__ . '/init.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit;
}
if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
    die('Invalid CSRF token');
}

// Можно удалить по bookmark_id или по anime_id
$bookmark_id = isset($_POST['bookmark_id']) ? intval($_POST['bookmark_id']) : null;
$anime_id = isset($_POST['anime_id']) ? intval($_POST['anime_id']) : null;
$uid = current_user_id();

if ($bookmark_id) {
    $del = $pdo->prepare("DELETE FROM bookmarks WHERE id = :id AND user_id = :uid");
    $del->execute([':id' => $bookmark_id, ':uid' => $uid]);
    log_activity($pdo, $uid, 'remove_bookmark', "Удалена закладка id={$bookmark_id}");
} elseif ($anime_id) {
    $del = $pdo->prepare("DELETE FROM bookmarks WHERE anime_id = :aid AND user_id = :uid");
    $del->execute([':aid' => $anime_id, ':uid' => $uid]);
    log_activity($pdo, $uid, 'remove_bookmark', "Удалена закладка anime_id={$anime_id}");
} else {
    $_SESSION['flash_error'] = 'Нечего удалять.';
    header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? 'bookmarks.php'));
    exit;
}

$_SESSION['flash_success'] = 'Удалено.';
header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? 'bookmarks.php'));
exit;

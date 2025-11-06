<?php
// edit_review.php
require_once __DIR__ . '/init.php';
require_login();

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if (!$id) {
    header('Location: index.php');
    exit;
}

// Получаем отзыв
$stmt = $pdo->prepare("SELECT * FROM reviews WHERE id = :id LIMIT 1");
$stmt->execute([':id' => $id]);
$rev = $stmt->fetch();
if (!$rev) {
    echo "Отзыв не найден";
    exit;
}

// Проверим права: только владелец или админ
if ($rev['user_id'] != current_user_id() && ($_SESSION['role'] ?? '') !== 'admin') {
    http_response_code(403);
    echo "Нет доступа";
    exit;
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Неверный CSRF';
    } else {
        $rating = intval($_POST['rating'] ?? 0);
        $text = trim($_POST['review_text'] ?? '');
        if ($rating < 1 || $rating > 10) $errors[] = 'Оценка от 1 до 10';
        if (empty($errors)) {
            $upd = $pdo->prepare("UPDATE reviews SET rating = :rating, review_text = :text, updated_at = NOW() WHERE id = :id");
            $upd->execute([':rating' => $rating, ':text' => $text, ':id' => $id]);
            log_activity($pdo, current_user_id(), 'edit_review', "Редактирование отзыва id={$id}");
            $_SESSION['flash_success'] = 'Отзыв обновлён.';
            header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? 'index.php'));
            exit;
        }
    }
}

// Форма редактирования
$csrf = csrf_token();
?>
<!doctype html>
<html lang="ru">

<head>
    <meta charset="utf-8">
    <title>Редактировать отзыв</title>
</head>

<body>
    <h1>Редактирование отзыва</h1>
    <?php if ($errors): foreach ($errors as $e): ?><div style="color:red;"><?= e($e) ?></div><?php endforeach;
                                                                                    endif; ?>

    <form method="post">
        <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
        <label>Оценка (1-10)<br><input name="rating" value="<?= e($rev['rating']) ?>"></label><br><br>
        <label>Текст<br><textarea name="review_text" rows="6" cols="60"><?= e($rev['review_text']) ?></textarea></label><br><br>
        <button type="submit">Сохранить</button>
    </form>
</body>

</html>
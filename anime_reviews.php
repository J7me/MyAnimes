<?php
// anime_reviews.php
require_once __DIR__ . '/init.php';

$anime_id = isset($_GET['anime_id']) ? intval($_GET['anime_id']) : 0;
if (!$anime_id) {
    echo "Не указан anime_id";
    exit;
}

// Получим отзывы
$stmt = $pdo->prepare("SELECT r.*, u.username FROM reviews r JOIN users u ON u.id = r.user_id WHERE r.anime_id = :aid ORDER BY r.created_at DESC");
$stmt->execute([':aid' => $anime_id]);
$reviews = $stmt->fetchAll();

// Средний рейтинг
$avgStmt = $pdo->prepare("SELECT AVG(rating) as avg_rating, COUNT(*) as cnt FROM reviews WHERE anime_id = :aid");
$avgStmt->execute([':aid' => $anime_id]);
$agg = $avgStmt->fetch();

$csrf = csrf_token();
?>
<!doctype html>
<html lang="ru">

<head>
    <meta charset="utf-8">
    <title>Отзывы</title>
</head>

<body>
    <h1>Отзывы</h1>

    <p>Аниме ID: <?= e($anime_id) ?></p>
    <p>Средний рейтинг: <?= $agg && $agg['cnt'] ? number_format($agg['avg_rating'], 1) . " / 10 ({$agg['cnt']} отзывов)" : "нет данных" ?></p>

    <?php if (is_logged_in()): ?>
        <h2>Оставить или обновить отзыв</h2>
        <form method="post" action="add_review.php">
            <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
            <input type="hidden" name="anime_id" value="<?= e($anime_id) ?>">
            <label>Название (впиши так, как отображать):<br><input name="anime_title" required></label><br><br>
            <label>Оценка 1-10:<br><input name="rating" type="number" min="1" max="10" required></label><br><br>
            <label>Текст отзыва:<br><textarea name="review_text" rows="6" cols="60"></textarea></label><br><br>
            <button type="submit">Отправить</button>
        </form>
    <?php else: ?>
        <p><a href="login.php">Войдите</a> чтобы оставить отзыв.</p>
    <?php endif; ?>

    <hr>

    <h2>Существующие отзывы</h2>
    <?php if (empty($reviews)): ?>
        <p>Нет отзывов.</p>
        <?php else: foreach ($reviews as $r): ?>
            <div style="border:1px solid #e6eef6;padding:10px;border-radius:8px;margin-bottom:10px">
                <strong><?= e($r['username']) ?></strong> — <small><?= e($r['created_at']) ?><?= $r['updated_at'] ? ' (обновлён: ' . e($r['updated_at']) . ')' : '' ?></small><br>
                Оценка: <?= e($r['rating']) ?> / 10<br>
                <p><?= nl2br(e($r['review_text'])) ?></p>

                <?php if (is_logged_in() && (current_user_id() == $r['user_id'] || ($_SESSION['role'] ?? '') === 'admin')): ?>
                    <form method="post" style="display:inline-block" action="delete_review.php">
                        <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
                        <input type="hidden" name="id" value="<?= e($r['id']) ?>">
                        <button type="submit">Удалить</button>
                    </form>
                    <a href="edit_review.php?id=<?= e($r['id']) ?>">Редактировать</a>
                <?php endif; ?>
            </div>
    <?php endforeach;
    endif; ?>

</body>

</html>
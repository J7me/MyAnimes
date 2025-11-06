<?php
// bookmarks.php
require_once __DIR__ . '/init.php';
require_login();

$uid = current_user_id();
$stmt = $pdo->prepare("SELECT * FROM bookmarks WHERE user_id = :uid ORDER BY created_at DESC");
$stmt->execute([':uid' => $uid]);
$items = $stmt->fetchAll();

$csrf = csrf_token();
?>
<!doctype html>
<html lang="ru">

<head>
    <meta charset="utf-8">
    <title>Мои закладки</title>
    <link rel="stylesheet" href="css/bookmarks.css">
</head>

<body>
    <div class="container">
        <h1>Мои закладки</h1>

        <?php if (!empty($_SESSION['flash_success'])): ?>
            <div class="flash success"><?= e($_SESSION['flash_success']);
                                        unset($_SESSION['flash_success']); ?></div>
        <?php endif; ?>
        <?php if (!empty($_SESSION['flash_error'])): ?>
            <div class="flash error"><?= e($_SESSION['flash_error']);
                                        unset($_SESSION['flash_error']); ?></div>
        <?php endif; ?>

        <?php if (empty($items)): ?>
            <p>Пусто — добавь аниме в закладки с главной страницы.</p>
        <?php else: ?>
            <?php foreach ($items as $it): ?>
                <div class="card">
                    <?php
                    $img = $it['anime_image']
                        ? $it['anime_image']
                        : 'data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="80" height="110"><rect width="100%" height="100%" fill="#e6eef6"/></svg>';
                    ?>
                    <img src="<?= e($img) ?>" alt="">
                    <div style="flex:1">
                        <strong><?= e($it['anime_title']) ?></strong><br>
                        <small>ID: <?= e($it['anime_id']) ?></small><br>
                        <small>Добавлено: <?= e($it['created_at']) ?></small>
                    </div>
                    <div>
                        <a class="btn open" href="anime.php?id=<?= e($it['anime_id']) ?>">Открыть</a>
                        <form method="post" style="display:inline-block;margin-top:8px">
                            <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
                            <input type="hidden" name="bookmark_id" value="<?= e($it['id']) ?>">
                            <button class="btn del" type="submit" formaction="remove_bookmark.php">Удалить</button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
        <footer class="footer">
            <div class="footer-content">
                <span>© <?= date('Y') ?> MyAnimes</span>


            </div>
        </footer>
    </div>
</body>

</html>
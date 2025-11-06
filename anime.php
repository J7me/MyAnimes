<?php
require_once __DIR__ . '/init.php';

// безопасный вывод
function e($s)
{
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

// Проверим параметр id
$animeId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($animeId <= 0) {
    echo "Аниме не найдено.";
    exit;
}

// функция для API (если не определена)
if (!function_exists('shiki_get')) {
    define('SHIKI_API', 'https://shikimori.one/api/');
    function shiki_get($endpoint, $params = [])
    {
        $url = SHIKI_API . $endpoint;
        if (!empty($params)) $url .= '?' . http_build_query($params);
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERAGENT, "MyAnimes/1.1");
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        $resp = curl_exec($ch);
        $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if (!$resp || $http >= 400) return null;
        return json_decode($resp, true);
    }
}

// Получаем данные аниме
$anime = shiki_get("animes/{$animeId}");
if (!$anime) {
    echo "Не удалось загрузить данные о аниме.";
    exit;
}

$csrf = function_exists('csrf_token') ? csrf_token() : '';

// Обработка добавления отзыва
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['review_text'])) {
    if (function_exists('verify_csrf_token')) {
        if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
            $_SESSION['flash_error'] = 'Неверный CSRF токен.';
            header("Location: anime.php?id=" . $animeId);
            exit;
        }
    }

    if (!function_exists('is_logged_in') || !is_logged_in()) {
        $_SESSION['flash_error'] = 'Вы должны войти, чтобы оставить отзыв.';
        header("Location: login.php");
        exit;
    }

    $user_id = current_user_id();
    $anime_id = (int)($_POST['anime_id'] ?? 0);
    $anime_title = trim($_POST['anime_title'] ?? '');
    $review_text = trim($_POST['review_text'] ?? '');
    $rating = (int)($_POST['rating'] ?? 0);

    if ($review_text === '') {
        $_SESSION['flash_error'] = 'Отзыв не может быть пустым.';
        header("Location: anime.php?id=" . $anime_id);
        exit;
    }

    $stmt = $pdo->prepare("
        INSERT INTO reviews (user_id, anime_id, anime_title, review_text, rating)
        VALUES (:uid, :aid, :title, :text, :rating)
    ");
    $stmt->execute([
        ':uid' => $user_id,
        ':aid' => $anime_id,
        ':title' => $anime_title,
        ':text' => $review_text,
        ':rating' => $rating
    ]);

    if (function_exists('log_activity')) {
        log_activity($pdo, $user_id, 'review_add', "Добавлен отзыв для anime_id={$anime_id}");
    }

    $_SESSION['flash_success'] = 'Ваш отзыв успешно добавлен.';
    header("Location: anime.php?id=" . $anime_id);
    exit;
}

// Получаем отзывы
$stmt = $pdo->prepare("
    SELECT r.*, u.username 
    FROM reviews r
    JOIN users u ON u.id = r.user_id
    WHERE r.anime_id = :id
    ORDER BY r.created_at DESC
");
$stmt->execute([':id' => $animeId]);
$reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);

// жанры
$genres_str = '';
if (!empty($anime['genres']) && is_array($anime['genres'])) {
    $parts = [];
    foreach ($anime['genres'] as $g) {
        $parts[] = $g['russian'] ?? $g['name'] ?? '';
    }
    $genres_str = implode(', ', $parts);
}

// обложка
$img_url = '';
if (!empty($anime['image'])) {
    $img_url = $anime['image']['original'] ?? $anime['image']['preview'] ?? '';
    if ($img_url && strpos($img_url, 'http') !== 0) {
        $img_url = 'https://shikimori.one' . $img_url;
    }
}

// описание
$description = trim($anime['description_html'] ?? $anime['description'] ?? '');
if ($description) {
    $description = strip_tags($description, '<br><p><b><i><em><strong><ul><li><ol>');
}
?>
<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <title><?= e($anime['russian'] ?? $anime['name'] ?? 'Аниме') ?></title>
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <link rel="stylesheet" href="css/anime.css">
</head>

<body>
    <div class="container">
        <div class="navbar">
            <div><a href="index.php">&larr; Назад</a></div>
            <div>
                <?php if (function_exists('is_logged_in') && is_logged_in()): ?>
                    <span style="margin-right:10px">Привет, <?= e($_SESSION['username']) ?></span>
                    <a href="bookmarks.php">Мои закладки</a>
                    <a href="logout.php">Выход</a>
                <?php else: ?>
                    <a href="login.php">Войти</a>
                    <a href="register.php">Регистрация</a>
                <?php endif; ?>
            </div>
        </div>

        <div class="header-row">
            <img class="cover" src="<?= e($img_url ?: 'data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="260" height="380"><rect width="100%" height="100%" fill="%23e6eef6"/></svg>') ?>" alt="">
            <div class="info">
                <div class="title"><?= e($anime['russian'] ?? $anime['name']) ?></div>
                <div class="meta"><strong>Тип:</strong> <?= e($anime['kind'] ?? '-') ?> &nbsp;·&nbsp; <strong>Эпизоды:</strong> <?= e($anime['episodes'] ?? '-') ?></div>
                <div class="meta"><strong>Рейтинг Shikimori:</strong> <?= e($anime['score'] ?? '-') ?></div>
                <div class="genre-badges">
                    <?php if (!empty($anime['genres'])): foreach ($anime['genres'] as $g): ?>
                            <span class="tag"><?= e($g['russian'] ?? $g['name'] ?? '') ?></span>
                    <?php endforeach;
                    endif; ?>
                </div>

                <div class="actions">
                    <a class="btn" href="https://shikimori.one<?= e($anime['url'] ?? '#') ?>" target="_blank">Открыть на Shikimori</a>
                    <?php if (function_exists('is_logged_in') && is_logged_in()): ?>
                        <!-- кнопка "В закладки" -->
                        <?php
                        $is_bookmarked = false;
                        if (isset($_SESSION['user_id'])) {
                            $stmtB = $pdo->prepare("SELECT id FROM bookmarks WHERE user_id = :uid AND anime_id = :aid LIMIT 1");
                            $stmtB->execute([':uid' => current_user_id(), ':aid' => $animeId]);
                            if ($stmtB->fetch()) $is_bookmarked = true;
                        }
                        ?>
                        <?php if ($is_bookmarked): ?>
                            <form method="post" action="remove_bookmark.php" style="display:inline">
                                <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
                                <input type="hidden" name="anime_id" value="<?= e($animeId) ?>">
                                <button class="btn ghost" type="submit">Удалить из закладок</button>
                            </form>
                        <?php else: ?>
                            <form method="post" action="add_bookmark.php" style="display:inline">
                                <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
                                <input type="hidden" name="anime_id" value="<?= e($animeId) ?>">
                                <input type="hidden" name="anime_title" value="<?= e($anime['russian'] ?? $anime['name']) ?>">
                                <input type="hidden" name="anime_url" value="<?= e('https://shikimori.one' . ($anime['url'] ?? '#')) ?>">
                                <input type="hidden" name="anime_image" value="<?= e($img_url) ?>">
                                <button class="btn" type="submit">Добавить в закладки</button>
                            </form>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <?php if ($description): ?>
            <div class="description" role="region" aria-label="Описание">
                <h3>Описание</h3>
                <?= $description ?>
            </div>
        <?php endif; ?>

        <div class="reviews">
            <h2>Отзывы</h2>

            <?php if (!empty($_SESSION['flash_success'])): ?>
                <div class="flashes">
                    <div class="flash success"><?= e($_SESSION['flash_success']);
                                                unset($_SESSION['flash_success']); ?></div>
                </div>
            <?php endif; ?>
            <?php if (!empty($_SESSION['flash_error'])): ?>
                <div class="flashes">
                    <div class="flash error"><?= e($_SESSION['flash_error']);
                                                unset($_SESSION['flash_error']); ?></div>
                </div>
            <?php endif; ?>

            <?php if (function_exists('is_logged_in') && is_logged_in()): ?>
                <form method="post" class="review-form" aria-label="Форма отзыва">
                    <?php if ($csrf): ?><input type="hidden" name="csrf_token" value="<?= e($csrf) ?>"><?php endif; ?>
                    <input type="hidden" name="anime_id" value="<?= e($animeId) ?>">
                    <input type="hidden" name="anime_title" value="<?= e($anime['russian'] ?? $anime['name']) ?>">

                    <div class="form-row">
                        <label class="small">Оценка:
                            <select name="rating">
                                <option value="0">—</option>
                                <?php for ($i = 1; $i <= 10; $i++): ?>
                                    <option value="<?= $i ?>"><?= $i ?></option>
                                <?php endfor; ?>
                            </select>
                        </label>
                    </div>

                    <div style="margin-top:12px">
                        <textarea name="review_text" placeholder="Напишите ваш развёрнутый отзыв..." required></textarea>
                    </div>

                    <div class="form-actions">
                        <button class="btn" type="submit">Отправить отзыв</button>

                    </div>
                </form>
            <?php else: ?>
                <p class="meta">Чтобы оставить отзыв, <a href="login.php">войдите</a> на сайт.</p>
            <?php endif; ?>

            <div class="review-list" aria-live="polite">
                <?php if (!empty($reviews)): foreach ($reviews as $rev): ?>
                        <article class="review-card" aria-label="Отзыв пользователя">
                            <div class="reviewer"><?= strtoupper(substr($rev['username'], 0, 1)) ?></div>
                            <div class="review-body">
                                <div class="review-head">
                                    <div>
                                        <div class="reviewer-name"><?= e($rev['username']) ?></div>
                                        <div class="review-time"><?= e($rev['created_at']) ?></div>
                                    </div>
                                    <?php if (!empty($rev['rating']) && is_numeric($rev['rating'])): ?>
                                        <div class="review-rating"><?= e($rev['rating']) ?>/10</div>
                                    <?php endif; ?>
                                </div>
                                <div class="review-text"><?= nl2br(e($rev['review_text'])) ?></div>
                            </div>
                        </article>
                    <?php endforeach;
                else: ?>
                    <p class="meta">Отзывов пока нет — будьте первым!</p>
                <?php endif; ?>
            </div>
        </div>
        <footer class="footer">
            <span>© <?= date('Y') ?> MyAnimes</span>
            <span class="dot">·</span>
        
        </footer>

    </div>
</body>

</html>
<?php

require_once __DIR__ . '/init.php';

// Если shiki_get уже определён (в utils.php), не переопределяем его
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

// Получаем жанры (fallback — если API упал)
$genres = shiki_get('genres');
if (!is_array($genres) || empty($genres)) {
    // Небольшой запасной список — чтобы селектор не был пуст
    $genres = [
        ['id' => 1, 'russian' => 'Экшен', 'name' => 'action'],
        ['id' => 2, 'russian' => 'Романтика', 'name' => 'romance'],
        ['id' => 3, 'russian' => 'Комедия', 'name' => 'comedy'],
        ['id' => 4, 'russian' => 'Фэнтези', 'name' => 'fantasy'],
    ];
}

// Параметры страницы
$genreId = isset($_GET['genre']) && $_GET['genre'] !== '' ? $_GET['genre'] : null;
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 20;

$animeList = [];
$selectedGenre = null;

if ($genreId) {
    // Найдём объект жанра в списке (по id)
    foreach ($genres as $g) {
        // некоторые списки жанров могут возвращать id как строку — приводим к int для сравнения
        if ((string)($g['id'] ?? '') === (string)$genreId) {
            $selectedGenre = $g;
            break;
        }
    }

    if ($selectedGenre) {
        // ВАЖНО: используем поиск по названию жанра  
        // Берём англ. имя если есть, иначе русский
        $query = $selectedGenre['name'] ?? $selectedGenre['russian'] ?? '';
        // Если имя пустое — не делаем запрос
        if ($query !== '') {
            $animeList = shiki_get('animes', [
                'search' => $query,
                'order' => 'popularity',
                'limit' => $limit,
                'page' => $page
            ]);
            if (!is_array($animeList)) $animeList = [];
        }
    }
}

// CSRF токен (функция из auth.php)
$csrf = function_exists('csrf_token') ? csrf_token() : '';

// Текущий пользователь
$user_id = function_exists('current_user_id') ? current_user_id() : null;

// безопасный вывод
function e($s)
{
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <title>Аниме по жанрам</title>
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <link rel="stylesheet" href="css/index.css">
</head>

<body>
    <div class="container">

        <div class="navbar">
            <div><strong>MyAnimes</strong></div>
            <div>
                <?php if (function_exists('is_logged_in') && is_logged_in()): ?>
                    <span class="small">Привет, <?= e($_SESSION['username'] ?? '') ?></span>
                    <a href="bookmarks.php">Мои закладки</a>
                    <?php if (($_SESSION['role'] ?? '') === 'admin'): ?><a href="admin/index.php">Админ</a><?php endif; ?>
                    <a href="logout.php">Выход</a>
                <?php else: ?>
                    <a href="register.php">Регистрация</a> |
                    <a href="login.php">Вход</a>
                <?php endif; ?>
            </div>
        </div>

        <h1>Аниме по жанрам</h1>

        <form method="GET">
            <select name="genre" aria-label="Выбор жанра">
                <option value="">— Выберите жанр —</option>
                <?php foreach ($genres as $g): ?>
                    <option value="<?= e($g['id']) ?>" <?= ((string)$genreId === (string)($g['id'] ?? '') ? 'selected' : '') ?>>
                        <?= e($g['russian'] ?? $g['name'] ?? $g['slug'] ?? '') ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <button type="submit">Показать</button>
        </form>

        <?php if (!empty($_SESSION['flash_success'])): ?>
            <div class="flash success"><?= e($_SESSION['flash_success']);
                                        unset($_SESSION['flash_success']); ?></div>
        <?php endif; ?>
        <?php if (!empty($_SESSION['flash_error'])): ?>
            <div class="flash error"><?= e($_SESSION['flash_error']);
                                        unset($_SESSION['flash_error']); ?></div>
        <?php endif; ?>

        <?php if ($selectedGenre): ?>
            <h2>Жанр: <?= e($selectedGenre['russian'] ?? $selectedGenre['name'] ?? '') ?></h2>

            <div class="grid">
                <?php foreach ($animeList as $anime): ?>
                    <?php
                    // безопасно получить id (если нет — 0)
                    $anime_id = isset($anime['id']) ? intval($anime['id']) : 0;
                    $img = $anime['image']['preview'] ?? ($anime['image']['original'] ?? null);
                    if ($img && strpos($img, 'http') !== 0) $img = 'https://shikimori.one' . $img;
                    $title = $anime['russian'] ?? $anime['name'] ?? '—';
                    // проверим, есть ли у пользователя такая закладка
                    $is_bookmarked = false;
                    if ($user_id && $anime_id) {
                        $stmt = $pdo->prepare("SELECT id FROM bookmarks WHERE user_id = :uid AND anime_id = :aid LIMIT 1");
                        $stmt->execute([':uid' => $user_id, ':aid' => $anime_id]);
                        if ($stmt->fetch()) $is_bookmarked = true;
                    }
                    ?>
                    <div class="card">
                        <a href="anime.php?id=<?= e($anime_id) ?>" style="display:block; text-decoration:none; color:inherit;">
                            <img src="<?= e($img ?: 'data:image/svg+xml;utf8,<svg xmlns=&quot;http://www.w3.org/2000/svg&quot; width=&quot;400&quot; height=&quot;600&quot;><rect width=&quot;100%&quot; height=&quot;100%&quot; fill=&quot;%23e6eef6&quot;/></svg>') ?>" alt="">
                            <div class="card-title"><?= e($title) ?></div>
                        </a>


                        <?php if (function_exists('is_logged_in') && is_logged_in()): ?>
                            <?php if ($is_bookmarked): ?>
                                <form method="post" action="remove_bookmark.php" style="margin:0;position:absolute;top:6px;right:6px">
                                    <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
                                    <input type="hidden" name="anime_id" value="<?= e($anime_id) ?>">
                                    <button type="submit" class="bookmark-btn added" title="Удалить из закладок">
                                        <svg viewBox="0 0 24 24">
                                            <path d="M6 4v17l6-5.5 6 5.5V4z" />
                                        </svg>
                                    </button>
                                </form>
                            <?php else: ?>
                                <form method="post" action="add_bookmark.php" style="margin:0;position:absolute;top:6px;right:6px">
                                    <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
                                    <input type="hidden" name="anime_id" value="<?= e($anime_id) ?>">
                                    <input type="hidden" name="anime_title" value="<?= e($title) ?>">
                                    <input type="hidden" name="anime_url" value="<?= e('https://shikimori.one' . ($anime['url'] ?? '#')) ?>">
                                    <input type="hidden" name="anime_image" value="<?= e($img) ?>">
                                    <button type="submit" class="bookmark-btn" title="Добавить в закладки">
                                        <svg viewBox="0 0 24 24">
                                            <path d="M6 4v17l6-5.5 6 5.5V4z" />
                                        </svg>
                                    </button>
                                </form>
                            <?php endif; ?>
                        <?php else: ?>
                            <a href="login.php" class="bookmark-btn" title="Войдите чтобы добавить" style="position:absolute;top:6px;right:6px;display:flex;align-items:center;justify-content:center;text-decoration:none;color:#666;background:#fff;border:1px solid #ddd;border-radius:50%;width:34px;height:34px">
                                <svg viewBox="0 0 24 24" style="width:18px;height:18px;fill:#bbb">
                                    <path d="M6 4v17l6-5.5 6 5.5V4z" />
                                </svg>
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Пагинация -->
            <div class="pagination" role="navigation" aria-label="Пагинация">
                <?php if ($page > 1): ?>
                    <a class="page-btn" href="?genre=<?= e($genreId) ?>&page=<?= $page - 1 ?>">« Назад</a>
                <?php endif; ?>
                <span class="page-btn active"><?= e($page) ?></span>
                <?php if (count($animeList) == $limit): ?>
                    <a class="page-btn" href="?genre=<?= e($genreId) ?>&page=<?= $page + 1 ?>">Вперед »</a>
                <?php endif; ?>
            </div>

        <?php else: ?>
            <p style="margin-top:12px">Выберите жанр в селекторе сверху.</p>
        <?php endif; ?>
        <footer class="footer">
            <div class="footer-content">
                <span>© <?= date('Y') ?> MyAnimes</span>
       
               
            </div>
        </footer>
    </div>


</body>

</html>
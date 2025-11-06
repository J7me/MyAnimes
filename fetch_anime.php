<?php
define('SHIKI_API', 'https://shikimori.one/api/');

function shiki_get($endpoint, $params = [])
{
    $url = SHIKI_API . $endpoint . '?' . http_build_query($params);
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERAGENT, "AnimeGenreApp/1.1");
    $resp = curl_exec($ch);
    curl_close($ch);
    return json_decode($resp, true);
}

// Получаем жанры
$genres = shiki_get('genres');

// Параметры страницы
$genreId = $_GET['genre'] ?? null;
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 20;

$animeList = [];
$selectedGenre = null;

if ($genreId) {
    foreach ($genres as $g) {
        if ($g['id'] == $genreId) {
            $selectedGenre = $g;
            break;
        }
    }

    if ($selectedGenre) {
        $query = $selectedGenre['name'];
        $animeList = shiki_get('animes', [
            'search' => $query,
            'order' => 'popularity',
            'limit' => $limit,
            'page' => $page
        ]);
    }
}

// безоп. вывод
function e($s)
{
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}
?>
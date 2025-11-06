<?php
// utils.php
require_once __DIR__ . '/db.php';

// shiki_get — простая обёртка curl, если ты уже использовал другой вариант — не подключай этот файл
function shiki_get($endpoint, $params = [])
{
    $base = 'https://shikimori.one/api/';
    $qs = $params ? ('?' . http_build_query($params)) : '';
    $url = $base . $endpoint . $qs;
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERAGENT, "AnimeGenreApp/1.0");
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $resp = curl_exec($ch);
    $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if (!$resp || $http >= 400) return null;
    $json = json_decode($resp, true);
    return $json;
}

// Проверка, в закладках ли аниме у пользователя
function is_bookmarked(PDO $pdo, int $user_id, int $anime_id): bool
{
    $stmt = $pdo->prepare("SELECT id FROM bookmarks WHERE user_id = :uid AND anime_id = :aid LIMIT 1");
    $stmt->execute([':uid' => $user_id, ':aid' => $anime_id]);
    return (bool)$stmt->fetch();
}

// Получить закладку
function get_bookmark(PDO $pdo, int $user_id, int $anime_id)
{
    $stmt = $pdo->prepare("SELECT * FROM bookmarks WHERE user_id = :uid AND anime_id = :aid LIMIT 1");
    $stmt->execute([':uid' => $user_id, ':aid' => $anime_id]);
    return $stmt->fetch();
}

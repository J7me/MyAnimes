<?php
 
$config = require __DIR__ . '/config.php';
$db = $config['db'];

$dsn = "mysql:host={$db['host']};dbname={$db['name']};charset={$db['charset']}";
try {
    $pdo = new PDO($dsn, $db['user'], $db['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    // на продакшне логирую вместо echo 
    echo "DB connection failed: " . htmlspecialchars($e->getMessage());
    exit;
}


// Если пользователь авторизован, проверим актуальность роли
if (!empty($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("SELECT id, username, role FROM users WHERE id = :id LIMIT 1");
    $stmt->execute([':id' => $_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        // Если роль в сессии отличается от базы — обновляем
        if (!isset($_SESSION['role']) || $_SESSION['role'] !== $user['role']) {
            $_SESSION['role'] = $user['role'];
        }

        // (опционально) можно также обновлять имя, если его поменяли
        if (!isset($_SESSION['username']) || $_SESSION['username'] !== $user['username']) {
            $_SESSION['username'] = $user['username'];
        }
    } else {
        // Пользователя удалили из базы — разлогиниваем
        session_destroy();
        header("Location: login.php");
        exit;
    }
}

// Функции помощники
function current_user_id()
{
    return $_SESSION['user_id'] ?? null;
}

function current_user_role()
{
    return $_SESSION['role'] ?? 'user';
}

function is_admin()
{
    return (isset($_SESSION['role']) && $_SESSION['role'] === 'admin');
}

function is_logged_in()
{
    return !empty($_SESSION['user_id']);
}
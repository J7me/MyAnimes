<?php
// auth.php
 

if (!function_exists('is_logged_in')) {
    function is_logged_in(): bool
    {
        return !empty($_SESSION['user_id']);
    }
}

if (!function_exists('current_user_id')) {
    function current_user_id(): ?int
    {
        return !empty($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
    }
}

if (!function_exists('current_user')) {
    function current_user(PDO $pdo): ?array
    {
        $id = current_user_id();
        if (!$id) return null;
        $stmt = $pdo->prepare("SELECT id, username, email, role, created_at, last_login FROM users WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch() ?: null;
    }
}

if (!function_exists('require_login')) {
    function require_login()
    {
        if (!is_logged_in()) {
        
            header('Location: login.php');
            exit;
        }
    }
}

if (!function_exists('require_admin')) {
    function require_admin()
    {
        if (!is_logged_in() || ($_SESSION['role'] ?? '') !== 'admin') {
            http_response_code(403);
            echo 'Доступ запрещён';
            exit;
        }
    }
}

/* CSRF simple token */
if (!function_exists('csrf_token')) {
    function csrf_token(): string
    {
        if (empty($_SESSION['_csrf_token'])) {
            $_SESSION['_csrf_token'] = bin2hex(random_bytes(24));
        }
        return $_SESSION['_csrf_token'];
    }
}

if (!function_exists('verify_csrf_token')) {
    function verify_csrf_token(string $token): bool
    {
        if (empty($_SESSION['_csrf_token'])) return false;
        return hash_equals($_SESSION['_csrf_token'], $token);
    }
}

/* Логирование активности */
if (!function_exists('log_activity')) {
    function log_activity(PDO $pdo, int $user_id, string $action, ?string $details = null)
    {
        try {
            $stmt = $pdo->prepare("INSERT INTO activity_log (user_id, action, details) VALUES (:uid, :act, :det)");
            $stmt->execute([':uid' => $user_id, ':act' => $action, ':det' => $details]);
        } catch (Exception $e) {
            // не фатальная ошибка — можно логировать в файл
        }
    }
}

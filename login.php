<?php
// login.php
require_once __DIR__ . '/init.php';
// Если пользователь уже вошёл — перенаправляем
if (is_logged_in()) {
    header('Location: index.php');
    exit;
}
$errors = [];
$usernameOrEmail = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Неверный CSRF токен.';
    }

    $usernameOrEmail = trim($_POST['user'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($usernameOrEmail === '' || $password === '') {
        $errors[] = 'Заполните все поля.';
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = :u OR email = :u LIMIT 1");
        $stmt->execute([':u' => $usernameOrEmail]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password_hash'])) {
            $errors[] = 'Неверный логин или пароль.';
        } else {
            $_SESSION['user_id'] = (int)$user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];

            $u = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = :id");
            $u->execute([':id' => $user['id']]);

            log_activity($pdo, $user['id'], 'login', 'Успешный вход');

            header('Location: index.php');
            exit;
        }
    }
}

$csrf = csrf_token();
?>
<!doctype html>
<html lang="ru">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Вход — MyAnimes</title>
    <link rel="stylesheet" href="css/login.css">
</head>

<body>
    <div class="wrap">
        <div class="card">
            <div class="grid">
                <div>
                    <div class="brand">MyAnimes</div>
                    <h1>Вход</h1>
                    <p class="lead">Добро пожаловать! Войдите в свой аккаунт, чтобы продолжить.</p>

                    <?php if ($errors): ?>
                        <div class="errors" role="alert">
                            <strong>Ошибки:</strong>
                            <ul style="margin:8px 0 0 18px;padding:0">
                                <?php foreach ($errors as $err): ?>
                                    <li><?= e($err) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <form method="post" action="">
                        <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">

                        <label for="user">Имя пользователя или email</label>
                        <input id="user" name="user" type="text" value="<?= e($usernameOrEmail) ?>" required placeholder="Введите логин или email">

                        <label for="password">Пароль</label>
                        <input id="password" name="password" type="password" required placeholder="Введите пароль">

                        <div style="display:flex;gap:12px;align-items:center;margin-top:6px">
                            <button class="btn" type="submit">Войти</button>
                            <a class="btn ghost" href="index.php">На главную</a>
                        </div>

                        <div class="small" style="margin-top:12px">
                            Нет аккаунта? <a class="link" href="register.php">Зарегистрироваться</a>
                        </div>
                    </form>
                </div>

                <aside class="side">
                    <h3 style="margin-top:0">Советы по безопасности</h3>
                    <ul class="small" style="margin:8px 0 0 18px;padding:0">
                        <li>Не сообщайте пароль другим людям</li>
                        <li>Используйте надёжные комбинации символов</li>
                        <li>Выходите из аккаунта на чужих устройствах</li>
                    </ul>

                    <hr style="margin:14px 0;border:none;border-top:1px solid #f1f5f9">

                    <div class="small"><strong>Забыли пароль?</strong></div>
                    <p class="small" style="margin-top:6px">Свяжитесь с администратором для восстановления доступа.</p>

                    <div style="margin-top:12px" class="small">
                        <strong>Поддержка</strong><br>
                        <a class="link" href="mailto:admin@example.com">admin@example.com</a>
                    </div>
                </aside>
            </div>
        </div>
        <footer class="footer">
            <div class="footer-content">
                <span>© <?= date('Y') ?> MyAnimes</span>


            </div>
        </footer>
    </div>
</body>

</html>
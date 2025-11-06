<?php
// register.php
require_once __DIR__ . '/init.php';
// Запрещаем доступ авторизованным пользователям
if (is_logged_in()) {
    header('Location: index.php');
    exit;
}
$errors = [];
$username = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Неверный CSRF токен.';
    }

    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $password2 = $_POST['password2'] ?? '';

    if ($username === '' || strlen($username) < 3) $errors[] = 'Имя должно быть минимум 3 символа.';
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Неверный email.';
    if ($password === '' || strlen($password) < 6) $errors[] = 'Пароль минимум 6 символов.';
    if ($password !== $password2) $errors[] = 'Пароли не совпадают.';

    if (empty($errors)) {
        // Проверим уникальность username/email
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = :u OR email = :e LIMIT 1");
        $stmt->execute([':u' => $username, ':e' => $email]);
        if ($stmt->fetch()) {
            $errors[] = 'Пользователь с таким именем или email уже существует.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $ins = $pdo->prepare("INSERT INTO users (username, email, password_hash) VALUES (:u,:e,:p)");
            $ins->execute([':u' => $username, ':e' => $email, ':p' => $hash]);
            $user_id = $pdo->lastInsertId();

            // лог активности
            log_activity($pdo, $user_id, 'register', 'Новый пользователь зарегистрировался');

            // авто-вход после регистрации
            $_SESSION['user_id'] = (int)$user_id;
            $_SESSION['username'] = $username;
            $_SESSION['role'] = 'user';

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
    <title>Регистрация — MyAnimes</title>
    <link rel="stylesheet" href="css/register.css">
</head>

<body>
    <div class="wrap">
        <div class="card">
            <div class="grid">
                <div>
                    <div class="brand">MyAnimes</div>
                    <h1>Создать аккаунт</h1>
                    <p class="lead">Быстрая регистрация — после входа вы сможете добавлять аниме в закладки и оставлять отзывы.</p>

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

                        <label for="username">Имя пользователя</label>
                        <input id="username" name="username" type="text" value="<?= e($username) ?>" required placeholder="Ваш никнейм">

                        <label for="email">Email</label>
                        <input id="email" name="email" type="email" value="<?= e($email) ?>" required placeholder="you@example.com">

                        <div class="row">
                            <div style="flex:1">
                                <label for="password">Пароль</label>
                                <input id="password" name="password" type="password" required placeholder="Минимум 6 символов">
                            </div>
                            <div style="flex:1">
                                <label for="password2">Повторите пароль</label>
                                <input id="password2" name="password2" type="password" required placeholder="Ещё раз">
                            </div>
                        </div>

                        <div style="display:flex;gap:12px;align-items:center;margin-top:6px">
                            <button class="btn" type="submit">Зарегистрироваться</button>
                            <a class="btn ghost" href="index.php">Отмена</a>
                        </div>

                        <div class="links small">
                            Есть аккаунт? <a class="link" href="login.php">Войти</a>
                        </div>
                    </form>
                </div>

                <aside class="side">
                    <h3 style="margin-top:0">Почему стоит зарегистрироваться</h3>
                    <ul class="small" style="margin:8px 0 0 18px;padding:0">
                        <li>Добавляйте аниме в персональные закладки</li>
                        <li>Оставляйте отзывы и оценки</li>
                        <li>Сохраняйте историю просмотра</li>
                        <li>Получайте персональные рекомендации (в будущем)</li>
                    </ul>

                    <hr style="margin:14px 0;border:none;border-top:1px solid #f1f5f9">

                    <div class="small"><strong>Безопасность</strong></div>
                    <p class="small" style="margin-top:6px">Пароли хранятся безопасно (хеширование). Рекомендуем использовать уникальный пароль.</p>

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
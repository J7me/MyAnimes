<?php
require_once __DIR__ . '/common.php';
admin_header('Пользователи');

// Обработка смены роли / удаления
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        echo "CSRF error";
        exit;
    }
    if (isset($_POST['action']) && $_POST['action'] === 'delete' && isset($_POST['user_id'])) {
        $uid = intval($_POST['user_id']);
        // нельзя удалить себя
        if ($uid === current_user_id()) {
            $_SESSION['flash_error'] = 'Нельзя удалить текущего администратора.';
        } else {
            $del = $pdo->prepare("DELETE FROM users WHERE id = :id");
            $del->execute([':id' => $uid]);
        }
        header('Location: users.php');
        exit;
    }
    if (isset($_POST['action']) && $_POST['action'] === 'role' && isset($_POST['user_id']) && isset($_POST['role'])) {
        $uid = intval($_POST['user_id']);
        $role = $_POST['role'] === 'admin' ? 'admin' : 'user';
        $upd = $pdo->prepare("UPDATE users SET role = :role WHERE id = :id");
        $upd->execute([':role' => $role, ':id' => $uid]);
        log_activity($pdo, current_user_id(), 'change_role', "role to {$role} for user_id={$uid}");
        header('Location: users.php');
        exit;
    }
}

// Вывод списка
$stmt = $pdo->query("SELECT id, username, email, role, created_at, last_login FROM users ORDER BY created_at DESC");
$users = $stmt->fetchAll();
$csrf = csrf_token();
?>
<div class="box">
    <h3>Пользователи</h3>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Логин</th>
                <th>Email</th>
                <th>Роль</th>
                <th>Создан</th>
                <th>Последний вход</th>
                <th>Действия</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($users as $u): ?>
                <tr>
                    <td><?= e($u['id']) ?></td>
                    <td><?= e($u['username']) ?></td>
                    <td><?= e($u['email']) ?></td>
                    <td><?= e($u['role']) ?></td>
                    <td><?= e($u['created_at']) ?></td>
                    <td><?= e($u['last_login']) ?></td>
                    <td>
                        <form method="post" style="display:inline">
                            <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
                            <input type="hidden" name="user_id" value="<?= e($u['id']) ?>">
                            <input type="hidden" name="action" value="role">
                            <select name="role" onchange="this.form.submit()">
                                <option value="user" <?= $u['role'] === 'user' ? 'selected' : '' ?>>user</option>
                                <option value="admin" <?= $u['role'] === 'admin' ? 'selected' : '' ?>>admin</option>
                            </select>
                        </form>
                        <?php if ($u['id'] != current_user_id()): ?>
                            <form method="post" style="display:inline" onsubmit="return confirm('Удалить пользователя?');">
                                <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
                                <input type="hidden" name="user_id" value="<?= e($u['id']) ?>">
                                <input type="hidden" name="action" value="delete">
                                <button class="btn danger" type="submit">Удалить</button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <footer class="footer">
        <span>© <?= date('Y') ?> MyAnimes</span>
        <span class="dot">·</span>

    </footer>
</div>

<?php admin_footer(); ?>
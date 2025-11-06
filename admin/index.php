<?php
require_once __DIR__ . '/common.php';
admin_header('Панель администратора (обзор)');

// Короткая статистика
// Кол-во пользователей
$stmt = $pdo->query("SELECT COUNT(*) AS cnt FROM users");
$totalUsers = $stmt->fetchColumn();
// Кол-во закладок
$stmt = $pdo->query("SELECT COUNT(*) AS cnt FROM bookmarks");
$totalBookmarks = $stmt->fetchColumn();
// Кол-во отзывов
$stmt = $pdo->query("SELECT COUNT(*) AS cnt FROM reviews");
$totalReviews = $stmt->fetchColumn();

?>
<div class="box">
    <strong>Обзор</strong>
    <p>Пользователей: <?= e($totalUsers) ?> · Закладок: <?= e($totalBookmarks) ?> · Отзывов: <?= e($totalReviews) ?></p>
</div>

<div class="box">
    <h3>Последние действия (activity_log)</h3>
    <?php
    $stmt = $pdo->query("SELECT a.*, u.username FROM activity_log a JOIN users u ON u.id = a.user_id ORDER BY a.created_at DESC LIMIT 10");
    $rows = $stmt->fetchAll();
    if ($rows): ?>
        <table>
            <thead>
                <tr>
                    <th>Время</th>
                    <th>Пользователь</th>
                    <th>Действие</th>
                    <th>Детали</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $r): ?>
                    <tr>
                        <td><?= e($r['created_at']) ?></td>
                        <td><?= e($r['username']) ?></td>
                        <td><?= e($r['action']) ?></td>
                        <td><?= e($r['details']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: echo "<p>Нет действий</p>";
    endif; ?>
    <footer class="footer">
        <span>© <?= date('Y') ?> MyAnimes</span>
        <span class="dot">·</span>
       
    </footer>
</div>

<?php admin_footer(); ?>
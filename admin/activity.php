<?php
require_once __DIR__ . '/common.php';
admin_header('Журнал активности');

$filter_user = isset($_GET['user']) ? intval($_GET['user']) : null;
$sql = "SELECT a.*, u.username FROM activity_log a JOIN users u ON u.id = a.user_id";
$params = [];
if ($filter_user) {
    $sql .= " WHERE a.user_id = :uid";
    $params[':uid'] = $filter_user;
}
$sql .= " ORDER BY a.created_at DESC LIMIT 200";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();
$csrf = csrf_token();
?>
<div class="box">
    <h3>Журнал активности</h3>
    <form method="get" style="margin-bottom:8px">
        <label>Фильтр по пользователю ID: <input name="user" value="<?= e($filter_user) ?>"></label>
        <button type="submit" class="btn">Фильтровать</button>
    </form>

    <?php if (empty($rows)): echo "<p>Нет записей</p>";
    else: ?>
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
    <?php endif; ?>
    <footer class="footer">
        <span>© <?= date('Y') ?> MyAnimes</span>
        <span class="dot">·</span>

    </footer>
</div>

<?php admin_footer(); ?>
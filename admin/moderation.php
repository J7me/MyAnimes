<?php
require_once __DIR__ . '/common.php';
admin_header('Модерация отзывов');

// Удаление отзывов через POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        echo "CSRF error";
        exit;
    }
    if (isset($_POST['delete_review']) && isset($_POST['id'])) {
        $id = intval($_POST['id']);
        $del = $pdo->prepare("DELETE FROM reviews WHERE id = :id");
        $del->execute([':id' => $id]);
        log_activity($pdo, current_user_id(), 'moderation_delete_review', "Модерация: удалён отзыв id={$id}");
        header('Location: moderation.php');
        exit;
    }
}

// Пагинация простая
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 50;
$offset = ($page - 1) * $limit;

$stmt = $pdo->prepare("SELECT r.*, u.username FROM reviews r JOIN users u ON u.id = r.user_id ORDER BY r.created_at DESC LIMIT :lim OFFSET :off");
$stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
$stmt->bindValue(':off', $offset, PDO::PARAM_INT);
$stmt->execute();
$rows = $stmt->fetchAll();
$csrf = csrf_token();
?>
<div class="box">
    <h3>Отзывы (модерация)</h3>
    <?php if (empty($rows)): echo "<p>Нет отзывов</p>";
    else: ?>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Пользователь</th>
                    <th>Anime ID</th>
                    <th>Оценка</th>
                    <th>Текст</th>
                    <th>Действие</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $r): ?>
                    <tr>
                        <td><?= e($r['id']) ?></td>
                        <td><?= e($r['username']) ?></td>
                        <td><?= e($r['anime_id']) ?></td>
                        <td><?= e($r['rating']) ?></td>
                        <td><?= e(mb_strimwidth($r['review_text'], 0, 200, '...')) ?></td>
                        <td>
                            <form method="post" onsubmit="return confirm('Удалить отзыв?');">
                                <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
                                <input type="hidden" name="id" value="<?= e($r['id']) ?>">
                                <button class="btn danger" name="delete_review" type="submit">Удалить</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<div style="margin-top:12px">
    <?php if ($page > 1): ?><a class="btn" href="?page=<?= $page - 1 ?>">« Prev</a><?php endif; ?>
    <span class="small">Страница <?= $page ?></span>
    <?php if (count($rows) == $limit): ?><a class="btn" href="?page=<?= $page + 1 ?>">Next »</a><?php endif; ?>
    <footer class="footer">
        <span>© <?= date('Y') ?> MyAnimes</span>
        <span class="dot">·</span>

    </footer>
</div>

<?php admin_footer(); ?>
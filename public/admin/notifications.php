<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
require_admin();
$pageTitle = 'Notificaciones';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $id = filter_input(INPUT_POST, 'notification_id', FILTER_VALIDATE_INT);
    if ($id) { $s = db()->prepare('UPDATE notifications SET is_read=1 WHERE id=?'); $s->execute([$id]); }
    redirect('/admin/notifications.php');
}
$rows = db()->query('SELECT * FROM notifications ORDER BY created_at DESC LIMIT 200')->fetchAll();
require __DIR__ . '/../../includes/header.php';
?>
<section class="section admin-section"><span class="eyebrow">CENTRO DE ACTIVIDAD</span><h1>Notificaciones</h1><p>Pedidos, compras, registros y eventos importantes.</p>
<div class="notification-list">
<?php if (!$rows): ?><div class="empty-state"><span>✿</span><h2>Todo tranquilo por aquí</h2><p>Las notificaciones aparecerán cuando haya actividad.</p></div><?php endif; ?>
<?php foreach ($rows as $n): ?><article class="notification-row <?= $n['is_read'] ? '' : 'unread' ?>"><div><strong><?= e($n['title']) ?></strong><p><?= e($n['message']) ?></p><small><?= e($n['created_at']) ?> · <?= e($n['type']) ?></small></div>
<?php if (!$n['is_read']): ?><form method="post"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="notification_id" value="<?= (int)$n['id'] ?>"><button class="button button-small button-outline" type="submit">Marcar leída</button></form><?php endif; ?></article><?php endforeach; ?>
</div></section>
<?php require __DIR__ . '/../../includes/footer.php'; ?>

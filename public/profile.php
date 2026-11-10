<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_login();
$pageTitle = 'Mi cuenta';
$user = current_user();
$stmt = db()->prepare('SELECT order_number,status,payment_status,total,created_at FROM orders WHERE user_id=? ORDER BY created_at DESC LIMIT 10');
$stmt->execute([(int)$user['id']]); $orders = $stmt->fetchAll();
require __DIR__ . '/../includes/header.php';
?>
<section class="section profile-section"><span class="eyebrow">TU ESPACIO ROSETTA</span><h1>Hola, <?= e($user['name']) ?>.</h1><p>Aquí podrás consultar tus pedidos y el estado de tus compras.</p>
<div class="profile-panel"><h2>Mis últimos pedidos</h2>
<?php if (!$orders): ?><p>Aún no tienes pedidos. Cuando hagas tu primera compra, aparecerá aquí.</p>
<?php else: ?><div class="table-wrap"><table><thead><tr><th>Pedido</th><th>Fecha</th><th>Estado</th><th>Pago</th><th>Total</th></tr></thead><tbody>
<?php foreach ($orders as $o): ?><tr><td><?= e($o['order_number']) ?></td><td><?= e($o['created_at']) ?></td><td><?= e($o['status']) ?></td><td><?= e($o['payment_status']) ?></td><td>$<?= number_format((float)$o['total'],0,',','.') ?></td></tr><?php endforeach; ?>
</tbody></table></div><?php endif; ?></div></section>
<?php require __DIR__ . '/../includes/footer.php'; ?>

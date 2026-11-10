<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
require_admin();
$pageTitle = 'Panel administrador';
$pdo = db();
$stats = [
 'products'=>(int)$pdo->query('SELECT COUNT(*) FROM products')->fetchColumn(),
 'users'=>(int)$pdo->query("SELECT COUNT(*) FROM users WHERE role='customer'")->fetchColumn(),
 'orders'=>(int)$pdo->query('SELECT COUNT(*) FROM orders')->fetchColumn(),
 'unread'=>(int)$pdo->query('SELECT COUNT(*) FROM notifications WHERE is_read=0')->fetchColumn(),
];
// Solo pedidos con pago confirmado. Los pedidos pendientes/expirados no son ingresos.
$paidWhere = "payment_status='paid' AND status NOT IN ('cancelled','expired')";
$paidStats = $pdo->query("SELECT COUNT(*) AS paid_orders, COALESCE(SUM(total),0) AS revenue FROM orders WHERE $paidWhere")->fetch();
$stats['paid_orders'] = (int)$paidStats['paid_orders'];
$stats['revenue'] = (float)$paidStats['revenue'];
$stats['units_sold'] = (int)$pdo->query("SELECT COALESCE(SUM(oi.quantity),0) FROM order_items oi JOIN orders o ON o.id=oi.order_id WHERE o.$paidWhere")->fetchColumn();
$monthlySales = $pdo->query("SELECT DATE_FORMAT(COALESCE(paid_at,created_at),'%Y-%m') AS month, SUM(total) AS revenue FROM orders WHERE $paidWhere AND COALESCE(paid_at,created_at) >= DATE_FORMAT(DATE_SUB(CURDATE(),INTERVAL 5 MONTH),'%Y-%m-01') GROUP BY month ORDER BY month")->fetchAll(PDO::FETCH_KEY_PAIR);
$months = [];
for ($i=5;$i>=0;$i--) {
 $date = new DateTimeImmutable('first day of this month');
 $key = $date->modify("-$i months")->format('Y-m');
 $months[] = ['label'=>$date->modify("-$i months")->format('m/Y'),'value'=>(float)($monthlySales[$key]??0)];
}
$maxMonth = max(1.0,...array_column($months,'value'));
$topProducts = $pdo->query("SELECT oi.product_name, SUM(oi.quantity) AS units FROM order_items oi JOIN orders o ON o.id=oi.order_id WHERE o.$paidWhere GROUP BY oi.product_id,oi.product_name ORDER BY units DESC,oi.product_name ASC LIMIT 5")->fetchAll();
$maxUnits = max(1, (int) max(array_merge([0], array_map('intval', array_column($topProducts, 'units')))));
$notifications = $pdo->query('SELECT id,type,title,message,is_read,created_at FROM notifications ORDER BY created_at DESC LIMIT 8')->fetchAll();
require __DIR__ . '/../../includes/header.php';
?>
<section class="section admin-section"><span class="eyebrow">GESTIÓN DE TIENDA</span><h1>Panel de administración</h1><p>Bienvenido, <?= e(current_user()['name']) ?>. Aquí comienza la gestión de Rosetta.</p>
<div class="stats-grid">
<div class="stat-card"><span>Productos</span><strong><?= $stats['products'] ?></strong></div>
<div class="stat-card"><span>Clientes</span><strong><?= $stats['users'] ?></strong></div>
<div class="stat-card"><span>Pedidos</span><strong><?= $stats['orders'] ?></strong></div>
<div class="stat-card"><span>Notificaciones sin leer</span><strong><?= $stats['unread'] ?></strong></div>
</div>
<div class="stats-grid">
<div class="stat-card"><span>Ingresos confirmados</span><strong>$<?= number_format($stats['revenue'],0,',','.') ?></strong></div>
<div class="stat-card"><span>Compras pagadas</span><strong><?= $stats['paid_orders'] ?></strong></div>
<div class="stat-card"><span>Unidades vendidas</span><strong><?= $stats['units_sold'] ?></strong></div>
<div class="stat-card"><span>Pedidos totales (incluye pendientes)</span><strong><?= $stats['orders'] ?></strong></div>
</div>
<div class="sales-dashboard">
 <section class="profile-panel"><h2>Ingresos por mes</h2><p>Últimos seis meses · COP</p>
 <div class="sales-chart" role="img" aria-label="Gráfico de ingresos confirmados de los últimos seis meses">
 <?php foreach($months as $month): ?><div class="sales-chart-item"><span class="sales-chart-value">$<?= number_format($month['value'],0,',','.') ?></span><span class="sales-chart-bar" style="height:<?= round($month['value']/$maxMonth*150) ?>px"></span><span class="sales-chart-label"><?= e($month['label']) ?></span></div><?php endforeach; ?>
 </div></section>
 <section class="profile-panel"><h2>Productos más vendidos</h2><p>Clasificación por unidades pagadas</p>
 <?php if (!$topProducts): ?><p class="sales-note">Aún no hay compras con pago confirmado.</p><?php else: ?>
 <div class="top-products"><?php foreach($topProducts as $product): ?><div class="top-product-row"><div class="top-product-head"><span><?= e($product['product_name']) ?></span><strong><?= (int)$product['units'] ?> uds.</strong></div><div class="top-product-track"><div class="top-product-fill" style="width:<?= round((int)$product['units']/$maxUnits*100) ?>%"></div></div></div><?php endforeach; ?></div>
 <?php endif; ?></section>
</div>
<p class="sales-note">Las estadísticas se actualizan al abrir o recargar este panel. Solo cuentan pedidos marcados como pagados tras verificar el pago; Wompi todavía no está integrado.</p>
<div class="profile-panel"><h2>Notificaciones recientes</h2>
<?php if (!$notifications): ?><p>Aún no hay notificaciones.</p><?php else: ?>
<div class="notification-list"><?php foreach ($notifications as $n): ?><article class="notification-row <?= $n['is_read'] ? '' : 'unread' ?>"><div><strong><?= e($n['title']) ?></strong><p><?= e($n['message']) ?></p><small><?= e($n['created_at']) ?> · <?= e($n['type']) ?></small></div></article><?php endforeach; ?></div>
<?php endif; ?>
</div>
<div class="admin-links"><a class="button button-dark" href="<?= BASE_URL ?>/admin/products.php">Gestionar productos</a><a class="button button-outline" href="<?= BASE_URL ?>/admin/users.php">Gestionar usuarios</a><a class="button button-outline" href="<?= BASE_URL ?>/admin/notifications.php">Ver notificaciones</a></div>
</section>
<?php require __DIR__ . '/../../includes/footer.php'; ?>

<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_login();
$id=filter_input(INPUT_GET,'id',FILTER_VALIDATE_INT);
$s=db()->prepare('SELECT * FROM orders WHERE id=? AND user_id=?');$s->execute([$id,(int)current_user()['id']]);$order=$s->fetch();
if(!$order){http_response_code(404);exit('Pedido no encontrado.');}
$i=db()->prepare('SELECT * FROM order_items WHERE order_id=?');$i->execute([$id]);$items=$i->fetchAll();
$remaining=max(0,strtotime((string)$order['expires_at'])-time());
$pageTitle='Pedido '.$order['order_number'];require __DIR__.'/../includes/header.php';
?>
<section class="section order-section"><span class="eyebrow">PEDIDO REGISTRADO</span><h1>¡Gracias por tu pedido!</h1><div class="profile-panel"><h2><?= e($order['order_number']) ?></h2><p>Estado: <strong><?= e($order['status']) ?></strong> · Pago: <strong><?= e($order['payment_status']) ?></strong></p>
<?php if($order['status']==='pending_payment'): ?><div class="notice">Tu pedido tiene una reserva de inventario por 60 minutos. El tiempo restante al consultar esta página es aproximadamente <strong><?= gmdate('H:i:s',$remaining) ?></strong>. La confirmación del pago aún no está conectada a Wompi.</div><?php elseif($order['status']==='expired'): ?><div class="notice error">El plazo de pago terminó y las unidades fueron liberadas.</div><?php endif; ?>
<?php foreach($items as $it): ?><p><?= e($it['product_name']) ?> × <?= (int)$it['quantity'] ?> — $<?= number_format((float)$it['unit_price']*(int)$it['quantity'],0,',','.') ?> COP</p><?php endforeach; ?>
<hr><h2>Total: $<?= number_format((float)$order['total'],0,',','.') ?> COP</h2><p>Medio elegido: <?= e($order['payment_method']) ?></p><a class="button button-outline" href="<?= BASE_URL ?>/profile.php">Ver mis pedidos</a></div></section>
<?php require __DIR__.'/../includes/footer.php'; ?>

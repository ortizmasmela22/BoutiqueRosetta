<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_login();
$pdo=db(); $uid=(int)current_user()['id'];
$s=$pdo->prepare('SELECT id FROM carts WHERE user_id=?');$s->execute([$uid]);$cartId=(int)$s->fetchColumn();
if(!$cartId){flash('error','Tu carrito está vacío.');redirect('/cart.php');}
$s=$pdo->prepare('SELECT ci.*,p.name,p.price,p.image_path,p.stock,p.reserved_stock FROM cart_items ci JOIN products p ON p.id=ci.product_id WHERE ci.cart_id=?');$s->execute([$cartId]);$items=$s->fetchAll();
if(!$items){flash('error','Tu carrito está vacío.');redirect('/cart.php');}
$total=0;foreach($items as $it)$total+=(float)$it['price']*(int)$it['quantity'];
$methods=['pse'=>'PSE','card'=>'Tarjeta débito o crédito','nequi'=>'Nequi','cash'=>'Efectivo en puntos autorizados'];
if($_SERVER['REQUEST_METHOD']==='POST'){
 verify_csrf();$method=$_POST['payment_method']??'';
 if(!isset($methods[$method])){flash('error','Selecciona un medio de pago.');redirect('/checkout.php');}
 $pdo->beginTransaction();
 try{
  $lock=$pdo->prepare('SELECT ci.*,p.name,p.price,p.image_path,p.stock,p.reserved_stock,p.is_active FROM cart_items ci JOIN products p ON p.id=ci.product_id WHERE ci.cart_id=? FOR UPDATE');
  $lock->execute([$cartId]);$locked=$lock->fetchAll();
  if(!$locked)throw new RuntimeException('El carrito está vacío.');
  $subtotal=0;
  foreach($locked as $it){
   $qty=(int)$it['quantity'];$available=(int)$it['stock']-(int)$it['reserved_stock'];
   if(!(int)$it['is_active']||$qty>$available)throw new RuntimeException('El inventario de '. $it['name'].' cambió. Actualiza tu carrito.');
   $subtotal+=(float)$it['price']*$qty;
  }
  $orderNo='ROS-'.date('ymd').'-'.strtoupper(bin2hex(random_bytes(3)));
  $ins=$pdo->prepare("INSERT INTO orders (order_number,user_id,status,payment_status,subtotal,total,currency,payment_method,expires_at) VALUES (?,?, 'pending_payment','pending',?,?,'COP',?,DATE_ADD(NOW(),INTERVAL 60 MINUTE))");
  $ins->execute([$orderNo,$uid,$subtotal,$subtotal,$method]);$orderId=(int)$pdo->lastInsertId();
  $oi=$pdo->prepare('INSERT INTO order_items (order_id,product_id,product_name,product_image,unit_price,quantity,selected_size,selected_color) VALUES (?,?,?,?,?,?,?,?)');
  foreach($locked as $it){
   $qty=(int)$it['quantity'];
   $reserve=$pdo->prepare('UPDATE products SET reserved_stock=reserved_stock+? WHERE id=? AND stock-reserved_stock>=?');
   $reserve->execute([$qty,(int)$it['product_id'],$qty]);
   if($reserve->rowCount()!==1)throw new RuntimeException('Una unidad acaba de agotarse. Actualiza tu carrito.');
   $oi->execute([$orderId,(int)$it['product_id'],$it['name'],$it['image_path'],$it['price'],$qty,$it['selected_size'],$it['selected_color']]);
  }
  $pdo->prepare("INSERT INTO payments (order_id,provider,status,amount,currency) VALUES (?,'wompi','created',?,'COP')")->execute([$orderId,$subtotal]);
  $pdo->prepare("INSERT INTO notifications (type,title,message,related_user_id,related_order_id) VALUES ('order_created','Nuevo pedido',?,?,?)")
      ->execute(['Se creó el pedido '.$orderNo.' y está pendiente de pago.', $uid, $orderId]);
  $pdo->prepare('DELETE FROM cart_items WHERE cart_id=?')->execute([$cartId]);
  $pdo->commit();redirect('/order.php?id='.$orderId);
 }catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();flash('error',$e->getMessage());redirect('/cart.php');}
}
$pageTitle='Finalizar compra';require __DIR__.'/../includes/header.php';
?>
<section class="section checkout-section"><span class="eyebrow">CASI ES TUYO</span><h1>Finalizar compra</h1><div class="checkout-layout"><form class="profile-panel" method="post"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><h2>Medio de pago</h2><p>Selecciona cómo deseas pagar. La conexión real con Wompi se configurará después de probar el flujo de pedidos.</p>
<?php foreach($methods as $key=>$label): ?><label class="payment-option"><input type="radio" name="payment_method" value="<?= e($key) ?>" <?= $key==='pse'?'checked':'' ?>> <span><?= e($label) ?></span></label><?php endforeach; ?>
<button class="button button-dark" type="submit">Confirmar pedido y reservar por 60 minutos</button></form><aside class="cart-summary"><h2>Tu pedido</h2><?php foreach($items as $it): ?><div><span><?= e($it['name']) ?> × <?= (int)$it['quantity'] ?></span><strong>$<?= number_format((float)$it['price']*(int)$it['quantity'],0,',','.') ?></strong></div><?php endforeach; ?><hr><div class="summary-total"><span>Total</span><strong>$<?= number_format($total,0,',','.') ?> COP</strong></div></aside></div></section>
<?php require __DIR__.'/../includes/footer.php'; ?>

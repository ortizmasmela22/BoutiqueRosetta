<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_login();
$uid=(int)current_user()['id'];
function get_cart_id(PDO $pdo, int $uid): int {
    $s=$pdo->prepare('SELECT id FROM carts WHERE user_id=?'); $s->execute([$uid]); $id=$s->fetchColumn();
    if ($id) return (int)$id;
    $pdo->prepare('INSERT INTO carts (user_id) VALUES (?)')->execute([$uid]); return (int)$pdo->lastInsertId();
}
$pdo=db(); $cartId=get_cart_id($pdo,$uid);
if ($_SERVER['REQUEST_METHOD']==='POST') {
    verify_csrf();
    $action=$_POST['action'] ?? '';
    if ($action==='add') {
        $pid=filter_input(INPUT_POST,'product_id',FILTER_VALIDATE_INT);
        $qty=filter_input(INPUT_POST,'quantity',FILTER_VALIDATE_INT);
        $size=trim((string)($_POST['selected_size'] ?? '')); $color=trim((string)($_POST['selected_color'] ?? ''));
        $s=$pdo->prepare('SELECT * FROM products WHERE id=? AND is_active=1'); $s->execute([$pid]); $product=$s->fetch();
        if (!$product || !$qty || $qty<1) { flash('error','No pudimos agregar ese producto.'); redirect('/products.php'); }
        $available=(int)$product['stock']-(int)$product['reserved_stock'];
        $sizes=json_decode((string)$product['size_options'],true)?:[]; $colors=json_decode((string)$product['color_options'],true)?:[];
        if (($sizes && !in_array($size,$sizes,true)) || ($colors && !in_array($color,$colors,true))) { flash('error','Selecciona una talla y un color válidos.'); redirect('/product.php?id='.$pid); }
        $find=$pdo->prepare('SELECT id,quantity FROM cart_items WHERE cart_id=? AND product_id=? AND COALESCE(selected_size,"")=? AND COALESCE(selected_color,"")=?');
        $find->execute([$cartId,$pid,$size,$color]); $existing=$find->fetch();
        $newQty=$qty+(int)($existing['quantity']??0);
        if ($newQty>$available) { flash('error','No hay suficientes unidades disponibles.'); redirect('/product.php?id='.$pid); }
        if ($existing) $pdo->prepare('UPDATE cart_items SET quantity=? WHERE id=?')->execute([$newQty,(int)$existing['id']]);
        else $pdo->prepare('INSERT INTO cart_items (cart_id,product_id,quantity,selected_size,selected_color) VALUES (?,?,?,?,?)')->execute([$cartId,$pid,$qty,$size?:null,$color?:null]);
        flash('success','Producto agregado al carrito.'); redirect('/cart.php');
    }
    if ($action==='update') {
        $itemId=(int)($_POST['item_id']??0); $qty=(int)($_POST['quantity']??0);
        $s=$pdo->prepare('SELECT ci.id,p.stock,p.reserved_stock FROM cart_items ci JOIN products p ON p.id=ci.product_id WHERE ci.id=? AND ci.cart_id=?');
        $s->execute([$itemId,$cartId]); $it=$s->fetch();
        if ($it && $qty>0 && $qty<=((int)$it['stock']-(int)$it['reserved_stock'])) $pdo->prepare('UPDATE cart_items SET quantity=? WHERE id=?')->execute([$qty,$itemId]);
        else flash('error','Cantidad no válida o inventario insuficiente.');
        redirect('/cart.php');
    }
    if ($action==='remove') {
        $pdo->prepare('DELETE FROM cart_items WHERE id=? AND cart_id=?')->execute([(int)($_POST['item_id']??0),$cartId]);
        flash('success','Producto eliminado del carrito.'); redirect('/cart.php');
    }
}
$s=$pdo->prepare("SELECT ci.*,p.name,p.price,p.image_path,p.stock,p.reserved_stock FROM cart_items ci JOIN products p ON p.id=ci.product_id WHERE ci.cart_id=? ORDER BY ci.created_at DESC");
$s->execute([$cartId]); $items=$s->fetchAll(); $total=0;
foreach($items as &$it){$it['line_total']=(float)$it['price']*(int)$it['quantity'];$total+=$it['line_total'];} unset($it);
$pageTitle='Mi carrito'; require __DIR__.'/../includes/header.php';
?>
<section class="section cart-section">
 <span class="eyebrow">TU SELECCIÓN ROSETTA</span><h1>Mi carrito</h1>
 <?php if(!$items): ?><div class="empty-state"><span>♡</span><h2>Tu carrito está esperando algo especial</h2><p>Explora nuestra colección y encuentra tu próximo detalle favorito.</p><a class="button button-dark" href="<?= BASE_URL ?>/products.php">Ver colección</a></div>
 <?php else: ?>
 <div class="cart-layout"><div class="cart-items">
 <?php foreach($items as $it): ?><article class="cart-item" data-cart-row data-unit-price="<?= e((string)$it['price']) ?>">
  <div class="cart-thumb"><?php if($it['image_path']): ?><img src="<?= e($it['image_path']) ?>" alt="<?= e($it['name']) ?>"><?php else: ?>ROSETTA<?php endif; ?></div>
  <div class="cart-item-info"><h3><?= e($it['name']) ?></h3><small><?= $it['selected_size']?'Talla: '.e($it['selected_size']).' · ':'' ?><?= $it['selected_color']?'Color: '.e($it['selected_color']):'' ?></small><strong>$<?= number_format((float)$it['price'],0,',','.') ?> COP</strong>
   <div class="cart-actions"><form method="post"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="update"><input type="hidden" name="item_id" value="<?= (int)$it['id'] ?>"><label class="qty-label">Cantidad <input type="number" name="quantity" min="1" max="<?= max(1,(int)$it['stock']-(int)$it['reserved_stock']) ?>" value="<?= (int)$it['quantity'] ?>"></label><button class="button button-outline button-small">Actualizar</button></form>
   <form method="post"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="remove"><input type="hidden" name="item_id" value="<?= (int)$it['id'] ?>"><button class="text-link" type="submit">Eliminar</button></form></div>
  </div><strong class="cart-line-total" data-line-total>$<?= number_format($it['line_total'],0,',','.') ?></strong>
 </article><?php endforeach; ?>
 </div><aside class="cart-summary"><h2>Resumen</h2><div><span>Subtotal</span><strong data-cart-total>$<?= number_format($total,0,',','.') ?> COP</strong></div><div><span>Envío</span><small>Se calculará al confirmar la entrega</small></div><hr><div class="summary-total"><span>Total parcial</span><strong data-cart-total>$<?= number_format($total,0,',','.') ?> COP</strong></div><a class="button button-dark" href="<?= BASE_URL ?>/checkout.php">Continuar con la compra</a><a class="text-link" href="<?= BASE_URL ?>/products.php">Seguir comprando</a></aside></div>
 <?php endif; ?>
</section>
<?php require __DIR__.'/../includes/footer.php'; ?>

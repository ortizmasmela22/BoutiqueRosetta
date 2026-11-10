<?php
require_once __DIR__ . '/../includes/bootstrap.php';
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) { http_response_code(404); exit('Producto no encontrado.'); }
$stmt = db()->prepare("SELECT p.*, c.name AS category_name FROM products p LEFT JOIN categories c ON c.id=p.category_id WHERE p.id=? AND p.is_active=1");
$stmt->execute([$id]); $p=$stmt->fetch();
if (!$p) { http_response_code(404); exit('Producto no encontrado.'); }
$sizes = json_decode((string)$p['size_options'], true) ?: [];
$colors = json_decode((string)$p['color_options'], true) ?: [];
$available = max(0, (int)$p['stock'] - (int)$p['reserved_stock']);
$pageTitle=$p['name'];
require __DIR__ . '/../includes/header.php';
?>
<section class="section product-detail">
  <a class="text-link" href="<?= BASE_URL ?>/products.php">← Volver a la colección</a>
  <div class="detail-grid">
    <div class="detail-image"><?php if ($p['image_path']): ?><img src="<?= e($p['image_path']) ?>" alt="<?= e($p['name']) ?>"><?php else: ?><span class="image-placeholder">ROSETTA</span><?php endif; ?></div>
    <div class="detail-copy">
      <span class="eyebrow"><?= e($p['category_name'] ?? 'Colección') ?></span>
      <h1><?= e($p['name']) ?></h1>
      <strong class="detail-price">$<?= number_format((float)$p['price'],0,',','.') ?> COP</strong>
      <p><?= nl2br(e($p['description'] ?? 'Un detalle especial de Boutique Rosetta.')) ?></p>
      <p><strong><?= $available ?> unidades disponibles</strong></p>
      <?php if ($available > 0): ?>
      <form class="purchase-form" method="post" action="<?= BASE_URL ?>/cart.php">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="action" value="add">
        <input type="hidden" name="product_id" value="<?= (int)$p['id'] ?>">
        <?php if ($sizes): ?><label>Talla<select name="selected_size" required><option value="">Selecciona una talla</option><?php foreach($sizes as $s): ?><option value="<?= e((string)$s) ?>"><?= e((string)$s) ?></option><?php endforeach; ?></select></label><?php endif; ?>
        <?php if ($colors): ?><label>Color<select name="selected_color" required><option value="">Selecciona un color</option><?php foreach($colors as $c): ?><option value="<?= e((string)$c) ?>"><?= e((string)$c) ?></option><?php endforeach; ?></select></label><?php endif; ?>
        <label>Cantidad<input type="number" name="quantity" min="1" max="<?= $available ?>" value="1" required></label>
        <button class="button button-dark" type="submit">Agregar al carrito</button>
        <a class="button button-outline" href="<?= BASE_URL ?>/cart.php">Ver carrito</a>
      </form>
      <?php else: ?><div class="notice">Este producto está agotado por el momento.</div><?php endif; ?>
    </div>
  </div>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>

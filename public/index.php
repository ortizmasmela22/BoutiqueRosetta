<?php
require_once __DIR__ . '/../includes/bootstrap.php';
$pageTitle = 'Inicio';
$featured = [];
try {
    $stmt = db()->query("SELECT p.id,p.name,p.description,p.price,p.image_path,c.name AS category_name
      FROM products p LEFT JOIN categories c ON c.id=p.category_id
      WHERE p.is_active=1 AND p.is_featured=1 ORDER BY p.created_at DESC LIMIT 4");
    $featured = $stmt->fetchAll();
} catch (Throwable $e) {
    error_log($e->getMessage());
}
require __DIR__ . '/../includes/header.php';
?>
<section class="hero">
  <div class="hero-copy">
    <span class="eyebrow">DETALLES QUE SE SIENTEN</span>
    <h1>Pequeños momentos,<br><em>grandes emociones.</em></h1>
    <p>Descubre pijamas, regalos y detalles pensados para acompañar tus momentos más especiales.</p>
    <div class="hero-actions"><a class="button button-dark" href="<?= BASE_URL ?>/products.php">Descubrir colección</a><a class="text-link" href="<?= BASE_URL ?>/register.php">Únete a Rosetta ↗</a></div>
  </div>
  <div class="hero-art" aria-label="Composición decorativa de Boutique Rosetta">
    <div class="hero-sun"></div>
    <div class="hero-card card-one hero-polaroid">
      <img src="<?= BASE_URL ?>/assets/images/familia-pijama-roja.png" alt="Familia luciendo pijamas navideñas rojas de Boutique Rosetta" loading="eager" decoding="async">
      <div class="polaroid-caption"><span>ROSETTA · NAVIDAD</span><b>Para ti, con amor.</b></div>
    </div>
    <div class="hero-card card-two hero-polaroid">
      <img src="<?= BASE_URL ?>/assets/images/familia-pijama-verde.png" alt="Familia luciendo pijamas navideñas verdes de Boutique Rosetta" loading="eager" decoding="async">
      <div class="polaroid-caption"><span>COLECCIÓN FAMILIAR</span><b>Comodidad con estilo.</b></div>
    </div>
    <div class="hero-flower">✿</div>
  </div>
</section>
<section class="section section-intro">
  <span class="eyebrow">NUESTRA ESENCIA</span><h2>Detalles que hacen especial lo cotidiano</h2>
  <p>Encuentra algo bonito para ti o para alguien que quieres.</p>
  <a class="text-link" href="<?= BASE_URL ?>/products.php">Explorar todos los productos ↗</a>
</section>
<?php if ($featured): ?>
<section class="section">
  <div class="section-heading"><div><span class="eyebrow">SELECCIÓN ROSETTA</span><h2>Favoritos de la casa</h2></div><a class="text-link" href="<?= BASE_URL ?>/products.php">Ver colección ↗</a></div>
  <div class="product-grid">
  <?php foreach ($featured as $p): ?>
    <article class="product-card">
      <a class="product-image" href="<?= BASE_URL ?>/product.php?id=<?= (int)$p['id'] ?>">
        <?php if ($p['image_path']): ?><img src="<?= e($p['image_path']) ?>" alt="<?= e($p['name']) ?>" loading="lazy"><?php else: ?><span class="image-placeholder">ROSETTA</span><?php endif; ?>
      </a>
      <div class="product-meta"><span><?= e($p['category_name'] ?? 'Colección') ?></span><h3><?= e($p['name']) ?></h3><strong>$<?= number_format((float)$p['price'], 0, ',', '.') ?> COP</strong></div>
    </article>
  <?php endforeach; ?>
  </div>
</section>
<?php endif; ?>
<?php require __DIR__ . '/../includes/footer.php'; ?>

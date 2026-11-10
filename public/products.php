<?php
require_once __DIR__ . '/../includes/bootstrap.php';

$pageTitle = 'Colección';

$products = db()->query(
    "SELECT p.id, p.name, p.description, p.price, p.image_path, p.stock, c.name AS category_name
     FROM products p
     LEFT JOIN categories c ON c.id = p.category_id
     WHERE p.is_active = 1
     ORDER BY p.is_featured DESC, p.created_at DESC"
)->fetchAll();

require __DIR__ . '/../includes/header.php';
?>
<section class="section catalog-section">
    <span class="eyebrow">ENCUENTRA TU FAVORITO</span>
    <h1>Nuestra colección</h1>
    <p>Prendas y detalles pensados para regalar comodidad y alegría.</p>

    <div class="catalog-toolbar">
        <label class="search-field">Buscar productos
            <input id="productSearch" type="search" placeholder="Busca por nombre...">
        </label>

        <label>Ordenar
            <select id="productSort">
                <option value="featured">Destacados</option>
                <option value="price-asc">Precio: menor a mayor</option>
                <option value="price-desc">Precio: mayor a menor</option>
            </select>
        </label>
    </div>

    <div class="product-grid" id="productGrid">
        <?php foreach ($products as $p): ?>
            <article class="product-card"
                     data-name="<?= e(mb_strtolower($p['name'])) ?>"
                     data-price="<?= e((string)$p['price']) ?>">

                <a class="product-image" href="<?= BASE_URL ?>/product.php?id=<?= (int)$p['id'] ?>">
                    <?php if ($p['image_path']): ?>
                        <img src="<?= e($p['image_path']) ?>" alt="<?= e($p['name']) ?>" loading="lazy">
                    <?php else: ?>
                        <span class="image-placeholder">ROSETTA</span>
                    <?php endif; ?>
                </a>

                <div class="product-meta">
                    <span><?= e($p['category_name'] ?? 'Colección') ?></span>
                    <h3><?= e($p['name']) ?></h3>
                    <strong>$<?= number_format((float)$p['price'], 0, ',', '.') ?> COP</strong>
                    <small><?= (int)$p['stock'] > 0 ? 'Disponible' : 'Agotado' ?></small>
                </div>
            </article>
        <?php endforeach; ?>
    </div>

    <?php if (!$products): ?>
        <div class="empty-state">
            <span>✿</span>
            <h2>Estamos preparando algo bonito</h2>
            <p>Pronto encontrarás aquí la colección Rosetta.</p>
        </div>
    <?php endif; ?>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>
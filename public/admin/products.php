<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
require_admin();

$pageTitle = 'Productos';
$pdo = db();
$errors = [];

// Configuración de subida de imágenes
$uploadDir   = __DIR__ . '/../uploads';
$uploadUrl   = BASE_URL . '/uploads/';
$maxSize     = 2 * 1024 * 1024; // 2 MB
$allowedMime = [
    'image/jpeg' => 'jpg',
    'image/png'  => 'png',
    'image/webp' => 'webp',
];

$action = (string)($_POST['action'] ?? 'create');

// ELIMINAR producto
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'delete') {
    verify_csrf();

    $deleteId = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

    if ($deleteId) {
        try {
            $q = $pdo->prepare('SELECT image_path FROM products WHERE id = ?');
            $q->execute([$deleteId]);
            $oldImage = (string)$q->fetchColumn();

            $d = $pdo->prepare('DELETE FROM products WHERE id = ?');
            $d->execute([$deleteId]);

            // Borra el archivo si era una imagen subida desde el panel
            if ($oldImage !== '' && str_starts_with($oldImage, $uploadUrl)) {
                $oldFile = $uploadDir . '/' . basename($oldImage);
                if (is_file($oldFile)) {
                    @unlink($oldFile);
                }
            }

            flash('success', 'Producto eliminado.');
            redirect('/admin/products.php');
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                $errors[] = 'No se puede eliminar: el producto ya está asociado a pedidos. '
                          . 'Edítalo y desmarca "Producto visible en la tienda" para ocultarlo.';
            } else {
                error_log($e->getMessage());
                $errors[] = 'No pudimos eliminar el producto. Inténtalo más tarde.';
            }
        }
    }
}

// CREAR producto
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'create') {
    verify_csrf();

    $name        = trim((string)($_POST['name'] ?? ''));
    $description = trim((string)($_POST['description'] ?? ''));
    $price       = filter_input(INPUT_POST, 'price', FILTER_VALIDATE_FLOAT);
    $stock       = filter_input(INPUT_POST, 'stock', FILTER_VALIDATE_INT);
    $categoryId  = filter_input(INPUT_POST, 'category_id', FILTER_VALIDATE_INT);
    $image       = trim((string)($_POST['image_path'] ?? ''));
    $active      = isset($_POST['is_active']) ? 1 : 0;
    $uploadExt   = null;

    if ($name === '' || mb_strlen($name) > 180) {
        $errors[] = 'El nombre es obligatorio y debe tener máximo 180 caracteres.';
    }
    if ($price === false || $price === null || $price < 0) {
        $errors[] = 'El precio debe ser un número válido.';
    }
    if ($stock === false || $stock === null || $stock < 0) {
        $errors[] = 'El inventario debe ser un entero igual o mayor que cero.';
    }
    if ($image !== '' && !preg_match('#^(https?://|/)[^\s]*$#i', $image)) {
        $errors[] = 'La imagen debe ser una URL http(s) o una ruta local que comience por /.';
    }

    // Validar el archivo subido (si se eligió uno)
    $file = $_FILES['image_file'] ?? null;
    if ($file && $file['error'] !== UPLOAD_ERR_NO_FILE) {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'No se pudo subir la imagen. Inténtalo de nuevo.';
        } elseif ($file['size'] > $maxSize) {
            $errors[] = 'La imagen no debe pesar más de 2 MB.';
        } else {
            $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
            if (!isset($allowedMime[$mime])) {
                $errors[] = 'La imagen debe ser JPG, PNG o WEBP.';
            } else {
                $uploadExt = $allowedMime[$mime];
            }
        }
    }

    // Guardar el archivo en public/uploads
    if (!$errors && $uploadExt !== null) {
        if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true)) {
            $errors[] = 'No se pudo crear la carpeta de imágenes.';
        } else {
            $filename = bin2hex(random_bytes(8)) . '.' . $uploadExt;
            if (move_uploaded_file($file['tmp_name'], $uploadDir . '/' . $filename)) {
                $image = $uploadUrl . $filename;
            } else {
                $errors[] = 'No se pudo guardar la imagen en el servidor.';
            }
        }
    }

    if (!$errors) {
        $slugBase = trim(
            preg_replace('/[^a-z0-9]+/i', '-', iconv('UTF-8', 'ASCII//TRANSLIT', $name)),
            '-'
        );
        $slug = strtolower($slugBase) . '-' . bin2hex(random_bytes(3));

        $s = $pdo->prepare(
            'INSERT INTO products (category_id, name, slug, description, image_path, price, stock, is_active)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $s->execute([
            $categoryId ?: null,
            $name,
            $slug,
            $description,
            $image ?: null,
            $price,
            $stock,
            $active,
        ]);

        flash('success', 'Producto creado y guardado en MySQL.');
        redirect('/admin/products.php');
    }
}

$categories = $pdo->query(
    'SELECT id, name FROM categories WHERE is_active = 1 ORDER BY name'
)->fetchAll();

$products = $pdo->query(
    'SELECT p.id, p.name, p.price, p.stock, p.is_active, c.name category_name
     FROM products p
     LEFT JOIN categories c ON c.id = p.category_id
     ORDER BY p.created_at DESC'
)->fetchAll();

require __DIR__ . '/../../includes/header.php';
?>
<section class="section admin-section">
    <span class="eyebrow">INVENTARIO</span>
    <h1>Gestionar productos</h1>
    <p>Los productos creados aquí se guardan directamente en MySQL.</p>

    <div class="profile-panel">
        <h2>Agregar producto</h2>

        <?php foreach ($errors as $error): ?>
            <div class="notice error"><?= e($error) ?></div>
        <?php endforeach; ?>

        <form method="post" class="form-grid" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="action" value="create">

            <label>Nombre
                <input name="name" required maxlength="180">
            </label>

            <label>Categoría
                <select name="category_id">
                    <option value="">Sin categoría</option>
                    <?php foreach ($categories as $c): ?>
                        <option value="<?= (int)$c['id'] ?>"><?= e($c['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>

            <label>Precio (COP)
                <input name="price" type="number" min="0" step="100" required>
            </label>

            <label>Unidades disponibles
                <input name="stock" type="number" min="0" step="1" required>
            </label>

            <label class="full-span">Subir imagen (JPG, PNG o WEBP, máx. 2 MB)
                <input name="image_file" type="file" accept="image/jpeg,image/png,image/webp">
            </label>

            <label class="full-span">O usa una ruta o URL de imagen
                <input name="image_path" placeholder="/boutique-rosetta/public/uploads/producto.jpg">
            </label>

            <label class="full-span">Descripción
                <textarea name="description" rows="3"></textarea>
            </label>

            <label class="check-label">
                <input type="checkbox" name="is_active" checked> Producto visible en la tienda
            </label>

            <div class="full-span">
                <button class="button button-dark" type="submit">Guardar producto</button>
            </div>
        </form>
    </div>

    <div class="profile-panel">
        <h2>Inventario actual</h2>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Producto</th>
                        <th>Categoría</th>
                        <th>Precio</th>
                        <th>Stock</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($products as $p): ?>
                        <tr>
                            <td><?= e($p['name']) ?></td>
                            <td><?= e($p['category_name'] ?? '—') ?></td>
                            <td>$<?= number_format((float)$p['price'], 0, ',', '.') ?></td>
                            <td><?= (int)$p['stock'] ?></td>
                            <td><?= $p['is_active'] ? 'Activo' : 'Oculto' ?></td>
                            <td>
                                <div style="display:flex;gap:8px;align-items:center;">
                                    <a class="button button-outline"
                                       style="padding:6px 14px;font-size:.85rem;"
                                       href="<?= BASE_URL ?>/admin/product-edit.php?id=<?= (int)$p['id'] ?>">Editar</a>

                                    <form method="post" style="margin:0;"
                                          onsubmit="return confirm('¿Eliminar este producto? Esta acción no se puede deshacer.');">
                                        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                                        <button class="button button-dark" type="submit"
                                                style="padding:6px 14px;font-size:.85rem;">Eliminar</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>

                    <?php if (!$products): ?>
                        <tr>
                            <td colspan="6">Todavía no hay productos registrados.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
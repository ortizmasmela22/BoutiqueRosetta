<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
require_admin();

$pageTitle = 'Editar producto';
$pdo = db();
$errors = [];

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    http_response_code(404);
    exit('Producto no encontrado.');
}

$stmt = $pdo->prepare('SELECT * FROM products WHERE id = ?');
$stmt->execute([$id]);
$product = $stmt->fetch();

if (!$product) {
    http_response_code(404);
    exit('Producto no encontrado.');
}

// Configuración de subida de imágenes
$uploadDir   = __DIR__ . '/../uploads';
$uploadUrl   = BASE_URL . '/uploads/';
$maxSize     = 2 * 1024 * 1024; // 2 MB
$allowedMime = [
    'image/jpeg' => 'jpg',
    'image/png'  => 'png',
    'image/webp' => 'webp',
];

// Valores iniciales del formulario (los de la base de datos)
$oldImage = (string)($product['image_path'] ?? '');
$form = [
    'name'        => (string)$product['name'],
    'description' => (string)($product['description'] ?? ''),
    'price'       => (string)(float)$product['price'],
    'stock'       => (string)(int)$product['stock'],
    'category_id' => (int)($product['category_id'] ?? 0),
    'image_path'  => $oldImage,
    'is_active'   => (int)$product['is_active'],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $form['name']        = trim((string)($_POST['name'] ?? ''));
    $form['description'] = trim((string)($_POST['description'] ?? ''));
    $form['price']       = (string)($_POST['price'] ?? '');
    $form['stock']       = (string)($_POST['stock'] ?? '');
    $form['category_id'] = (int)($_POST['category_id'] ?? 0);
    $form['image_path']  = trim((string)($_POST['image_path'] ?? ''));
    $form['is_active']   = isset($_POST['is_active']) ? 1 : 0;

    $price     = filter_var($form['price'], FILTER_VALIDATE_FLOAT);
    $stock     = filter_var($form['stock'], FILTER_VALIDATE_INT);
    $image     = $form['image_path'];
    $uploadExt = null;

    if ($form['name'] === '' || mb_strlen($form['name']) > 180) {
        $errors[] = 'El nombre es obligatorio y debe tener máximo 180 caracteres.';
    }
    if ($price === false || $price < 0) {
        $errors[] = 'El precio debe ser un número válido.';
    }
    if ($stock === false || $stock < 0) {
        $errors[] = 'El inventario debe ser un entero igual o mayor que cero.';
    }
    if ($image !== '' && !preg_match('#^(https?://|/)[^\s]*$#i', $image)) {
        $errors[] = 'La imagen debe ser una URL http(s) o una ruta local que comience por /.';
    }

    // Validar el archivo nuevo (si se eligió uno)
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

    // Guardar el archivo nuevo en public/uploads
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
        $u = $pdo->prepare(
            'UPDATE products
             SET category_id = ?, name = ?, description = ?, image_path = ?,
                 price = ?, stock = ?, is_active = ?
             WHERE id = ?'
        );
        $u->execute([
            $form['category_id'] ?: null,
            $form['name'],
            $form['description'],
            $image ?: null,
            $price,
            $stock,
            $form['is_active'],
            $id,
        ]);

        // Si la imagen cambió y la anterior era una subida local, se borra el archivo viejo
        if ($oldImage !== '' && $oldImage !== $image && str_starts_with($oldImage, $uploadUrl)) {
            $oldFile = $uploadDir . '/' . basename($oldImage);
            if (is_file($oldFile)) {
                @unlink($oldFile);
            }
        }

        flash('success', 'Producto actualizado.');
        redirect('/admin/products.php');
    }
}

$categories = $pdo->query(
    'SELECT id, name FROM categories WHERE is_active = 1 ORDER BY name'
)->fetchAll();

require __DIR__ . '/../../includes/header.php';
?>
<section class="section admin-section">
    <span class="eyebrow">INVENTARIO</span>
    <h1>Editar producto</h1>
    <p><a class="text-link" href="<?= BASE_URL ?>/admin/products.php">← Volver a productos</a></p>

    <div class="profile-panel">
        <h2><?= e((string)$product['name']) ?></h2>

        <?php foreach ($errors as $error): ?>
            <div class="notice error"><?= e($error) ?></div>
        <?php endforeach; ?>

        <form method="post" class="form-grid" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">

            <label>Nombre
                <input name="name" value="<?= e($form['name']) ?>" required maxlength="180">
            </label>

            <label>Categoría
                <select name="category_id">
                    <option value="">Sin categoría</option>
                    <?php foreach ($categories as $c): ?>
                        <option value="<?= (int)$c['id'] ?>"
                            <?= $form['category_id'] === (int)$c['id'] ? 'selected' : '' ?>>
                            <?= e($c['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>

            <label>Precio (COP)
                <input name="price" type="number" min="0" step="100"
                       value="<?= e($form['price']) ?>" required>
            </label>

            <label>Unidades disponibles
                <input name="stock" type="number" min="0" step="1"
                       value="<?= e($form['stock']) ?>" required>
            </label>

            <?php if ($form['image_path'] !== ''): ?>
                <div class="full-span">
                    <small>Imagen actual:</small><br>
                    <img src="<?= e($form['image_path']) ?>" alt="Imagen actual"
                         style="max-width:160px;border-radius:8px;margin-top:6px;">
                </div>
            <?php endif; ?>

            <label class="full-span">Reemplazar imagen (JPG, PNG o WEBP, máx. 2 MB)
                <input name="image_file" type="file" accept="image/jpeg,image/png,image/webp">
            </label>

            <label class="full-span">Ruta o URL de imagen (déjala vacía para quitar la imagen)
                <input name="image_path" value="<?= e($form['image_path']) ?>">
            </label>

            <label class="full-span">Descripción
                <textarea name="description" rows="3"><?= e($form['description']) ?></textarea>
            </label>

            <label class="check-label">
                <input type="checkbox" name="is_active" <?= $form['is_active'] ? 'checked' : '' ?>>
                Producto visible en la tienda
            </label>

            <div class="full-span">
                <button class="button button-dark" type="submit">Guardar cambios</button>
                <a class="button button-outline" href="<?= BASE_URL ?>/admin/products.php">Cancelar</a>
            </div>
        </form>
    </div>
</section>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
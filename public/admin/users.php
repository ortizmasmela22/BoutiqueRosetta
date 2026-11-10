<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
require_admin();

$pageTitle = 'Gestión de usuarios';
$pdo = db();
$errors = [];
$editingUser = null;

// Procesar acciones del administrador: editar, bloquear, desbloquear o eliminar.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = (string)($_POST['action'] ?? '');
    $userId = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);

    if (!$userId || $userId < 1) {
        $errors[] = 'No se encontró un usuario válido.';
    } else {
        $find = $pdo->prepare("SELECT id, name, email, role, status, created_at FROM users WHERE id = ? LIMIT 1");
        $find->execute([$userId]);
        $target = $find->fetch();

        if (!$target) {
            $errors[] = 'El usuario ya no existe.';
        } elseif ($target['role'] === 'admin') {
            $errors[] = 'Por seguridad, este módulo solo permite gestionar cuentas de clientes.';
        } elseif ($action === 'toggle_status') {
            $newStatus = $target['status'] === 'active' ? 'disabled' : 'active';
            $update = $pdo->prepare('UPDATE users SET status = ? WHERE id = ? AND role = ?');
            $update->execute([$newStatus, $userId, 'customer']);
            flash('success', $newStatus === 'active' ? 'Cliente desbloqueado correctamente.' : 'Cliente bloqueado correctamente.');
            redirect('/admin/users.php');
        } elseif ($action === 'delete') {
            try {
                $delete = $pdo->prepare('DELETE FROM users WHERE id = ? AND role = ?');
                $delete->execute([$userId, 'customer']);
                flash('success', 'Cliente eliminado correctamente.');
                redirect('/admin/users.php');
            } catch (PDOException $e) {
                if ($e->getCode() === '23000') {
                    $errors[] = 'No se puede eliminar este cliente porque tiene pedidos asociados. Puedes bloquear su cuenta para impedir que inicie sesión y conservar su historial.';
                } else {
                    error_log('Error al eliminar cliente: ' . $e->getMessage());
                    $errors[] = 'No fue posible eliminar el cliente. Inténtalo nuevamente.';
                }
            }
        } elseif ($action === 'update') {
            $name = trim((string)($_POST['name'] ?? ''));
            $email = strtolower(trim((string)($_POST['email'] ?? '')));
            if ($name === '' || mb_strlen($name) > 120) {
                $errors[] = 'El nombre es obligatorio y debe tener máximo 120 caracteres.';
            }
            if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 190) {
                $errors[] = 'Escribe un correo electrónico válido.';
            }
            if (!$errors) {
                $duplicate = $pdo->prepare('SELECT id FROM users WHERE email = ? AND id <> ? LIMIT 1');
                $duplicate->execute([$email, $userId]);
                if ($duplicate->fetchColumn()) {
                    $errors[] = 'Ese correo ya está registrado en otra cuenta.';
                } else {
                    $update = $pdo->prepare('UPDATE users SET name = ?, email = ? WHERE id = ? AND role = ?');
                    $update->execute([$name, $email, $userId, 'customer']);
                    flash('success', 'Los datos del cliente se actualizaron correctamente.');
                    redirect('/admin/users.php');
                }
            }
            $editingUser = $target;
            $editingUser['name'] = $name;
            $editingUser['email'] = $email;
        } else {
            $errors[] = 'La acción solicitada no es válida.';
        }
    }
}

// Cargar el cliente seleccionado para editar.
if ($editingUser === null && isset($_GET['edit'])) {
    $editId = filter_input(INPUT_GET, 'edit', FILTER_VALIDATE_INT);
    if ($editId) {
        $stmt = $pdo->prepare("SELECT id, name, email, role, status, created_at FROM users WHERE id = ? AND role = 'customer' LIMIT 1");
        $stmt->execute([$editId]);
        $editingUser = $stmt->fetch() ?: null;
        if (!$editingUser) $errors[] = 'No se encontró el cliente que deseas editar.';
    }
}

$search = trim((string)($_GET['q'] ?? ''));
$statusFilter = (string)($_GET['status'] ?? 'all');
$where = ["role = 'customer'"];
$params = [];
if ($search !== '') {
    $where[] = '(name LIKE ? OR email LIKE ?)';
    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
}
if (in_array($statusFilter, ['active', 'disabled'], true)) {
    $where[] = 'status = ?';
    $params[] = $statusFilter;
}
$sql = 'SELECT id, name, email, status, created_at FROM users WHERE ' . implode(' AND ', $where) . ' ORDER BY created_at DESC';
$list = $pdo->prepare($sql);
$list->execute($params);
$customers = $list->fetchAll();

$totals = [
    'all' => (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role='customer'")->fetchColumn(),
    'active' => (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role='customer' AND status='active'")->fetchColumn(),
    'disabled' => (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role='customer' AND status='disabled'")->fetchColumn(),
];

require __DIR__ . '/../../includes/header.php';
?>
<section class="section admin-section">
  <span class="eyebrow">GESTIÓN DE CLIENTES</span>
  <h1>Usuarios y clientes</h1>
  <p>Consulta y administra las cuentas registradas en Boutique Rosetta.</p>

  <?php foreach ($errors as $error): ?><div class="notice error"><?= e($error) ?></div><?php endforeach; ?>

  <div class="stats-grid">
    <div class="stat-card"><span>Total de clientes</span><strong><?= $totals['all'] ?></strong></div>
    <div class="stat-card"><span>Cuentas activas</span><strong><?= $totals['active'] ?></strong></div>
    <div class="stat-card"><span>Cuentas bloqueadas</span><strong><?= $totals['disabled'] ?></strong></div>
  </div>

  <?php if ($editingUser): ?>
    <div class="profile-panel">
      <h2>Editar información del cliente</h2>
      <form method="post" class="form-grid">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="action" value="update">
        <input type="hidden" name="user_id" value="<?= (int)$editingUser['id'] ?>">
        <label>Nombre completo<input type="text" name="name" maxlength="120" value="<?= e($editingUser['name']) ?>" required></label>
        <label>Correo electrónico<input type="email" name="email" maxlength="190" value="<?= e($editingUser['email']) ?>" required></label>
        <div class="full-span admin-links">
          <button class="button button-dark" type="submit">Guardar cambios</button>
          <a class="button button-outline" href="<?= BASE_URL ?>/admin/users.php">Cancelar</a>
        </div>
      </form>
    </div>
  <?php endif; ?>

  <div class="profile-panel">
    <h2>Directorio de clientes</h2>
    <form method="get" class="catalog-toolbar user-filter-form">
      <label>Buscar cliente<input type="search" name="q" value="<?= e($search) ?>" placeholder="Nombre o correo electrónico"></label>
      <label>Estado<select name="status"><option value="all" <?= $statusFilter === 'all' ? 'selected' : '' ?>>Todos</option><option value="active" <?= $statusFilter === 'active' ? 'selected' : '' ?>>Activos</option><option value="disabled" <?= $statusFilter === 'disabled' ? 'selected' : '' ?>>Bloqueados</option></select></label>
      <button class="button button-dark" type="submit">Filtrar</button>
      <a class="button button-outline" href="<?= BASE_URL ?>/admin/users.php">Limpiar</a>
    </form>

    <?php if (!$customers): ?>
      <p>No se encontraron clientes con esos criterios.</p>
    <?php else: ?>
      <div class="table-wrap">
        <table class="admin-users-table">
          <thead><tr><th>Cliente</th><th>Correo</th><th>Registro</th><th>Estado</th><th>Acciones</th></tr></thead>
          <tbody>
          <?php foreach ($customers as $customer): ?>
            <tr>
              <td><?= e($customer['name']) ?></td>
              <td><?= e($customer['email']) ?></td>
              <td><?= e(date('d/m/Y', strtotime($customer['created_at']))) ?></td>
              <td><span class="user-status <?= $customer['status'] === 'active' ? 'is-active' : 'is-disabled' ?>"><?= $customer['status'] === 'active' ? 'Activo' : 'Bloqueado' ?></span></td>
              <td>
                <div class="user-action-list">
                  <a class="button button-outline button-small" href="<?= BASE_URL ?>/admin/users.php?edit=<?= (int)$customer['id'] ?>">Editar</a>
                  <form method="post">
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="action" value="toggle_status">
                    <input type="hidden" name="user_id" value="<?= (int)$customer['id'] ?>">
                    <button class="button button-small <?= $customer['status'] === 'active' ? 'button-soft-danger' : 'button-soft-success' ?>" type="submit" onclick="return confirm('¿Seguro que deseas <?= $customer['status'] === 'active' ? 'bloquear' : 'desbloquear' ?> esta cuenta?')"><?= $customer['status'] === 'active' ? 'Bloquear' : 'Desbloquear' ?></button>
                  </form>
                  <form method="post" onsubmit="return confirm('Esta acción eliminará la cuenta del cliente si no tiene pedidos asociados. ¿Deseas continuar?')">
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="user_id" value="<?= (int)$customer['id'] ?>">
                    <button class="button button-small button-soft-danger" type="submit">Eliminar</button>
                  </form>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>
  <div class="admin-links"><a class="button button-outline" href="<?= BASE_URL ?>/admin/index.php">Volver al panel</a></div>
</section>
<?php require __DIR__ . '/../../includes/footer.php'; ?>

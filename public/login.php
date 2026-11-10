<?php
require_once __DIR__ . '/../includes/bootstrap.php';
if (current_user()) redirect('/profile.php');
$pageTitle = 'Ingresar';
$error = null; $email = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $email = strtolower(trim((string)($_POST['email'] ?? '')));
    $password = (string)($_POST['password'] ?? '');
    $stmt = db()->prepare('SELECT id,name,email,password_hash,role,status FROM users WHERE email=? LIMIT 1');
    $stmt->execute([$email]); $u = $stmt->fetch();
    if (!$u || !password_verify($password, $u['password_hash']) || $u['status'] !== 'active') {
        $error = 'Correo o contraseña incorrectos, o cuenta deshabilitada.';
    } else {
        session_regenerate_id(true);
        unset($u['password_hash'], $u['status']);
        $_SESSION['user'] = $u;
        redirect(($u['role'] === 'admin') ? '/admin/index.php' : '/profile.php');
    }
}
require __DIR__ . '/../includes/header.php';
?>
<section class="auth-wrap"><div class="auth-aside"><span class="eyebrow">QUÉ BUENO TENERTE AQUÍ</span><h1>Vuelve a tu<br><em>lugar favorito.</em></h1><p>Tu comodidad y tus detalles especiales te esperan.</p></div>
<div class="auth-card"><span class="eyebrow">TU CUENTA ROSETTA</span><h2>Iniciar sesión</h2>
<?php if ($error): ?><div class="notice error"><?= e($error) ?></div><?php endif; ?>
<form method="post" class="form-stack"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
<label>Correo electrónico<input type="email" name="email" value="<?= e($email) ?>" required autocomplete="email"></label>
<label>Contraseña<input type="password" name="password" required autocomplete="current-password"></label>
<button class="button button-dark" type="submit">Ingresar</button></form>
<p class="form-bottom">¿Primera vez aquí? <a href="<?= BASE_URL ?>/register.php">Crea tu cuenta</a></p></div></section>
<?php require __DIR__ . '/../includes/footer.php'; ?>

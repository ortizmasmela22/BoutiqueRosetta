<?php
require_once __DIR__ . '/../includes/bootstrap.php';
if (current_user()) redirect('/profile.php');
$pageTitle = 'Crear cuenta';
$errors = [];
$name = ''; $email = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $name = trim((string)($_POST['name'] ?? ''));
    $email = strtolower(trim((string)($_POST['email'] ?? '')));
    $password = (string)($_POST['password'] ?? '');
    $confirm = (string)($_POST['confirm_password'] ?? '');
    if (mb_strlen($name) < 2 || mb_strlen($name) > 120) $errors[] = 'Escribe tu nombre (entre 2 y 120 caracteres).';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Escribe un correo válido.';
    if (strlen($password) < 10) $errors[] = 'La contraseña debe tener al menos 10 caracteres.';
    if ($password !== $confirm) $errors[] = 'Las contraseñas no coinciden.';
    if (!$errors) {
        try {
            $stmt = db()->prepare('INSERT INTO users (name,email,password_hash) VALUES (?,?,?)');
            $stmt->execute([$name,$email,password_hash($password, PASSWORD_DEFAULT)]);
            $newId = (int)db()->lastInsertId();
            $n = db()->prepare("INSERT INTO notifications (type,title,message,related_user_id) VALUES ('user_registered','Nuevo registro',?,?)");
            $n->execute(["Se registró una nueva cuenta: {$name} ({$email}).", $newId]);
            flash('success', 'Tu cuenta fue creada. Ya puedes iniciar sesión.');
            redirect('/login.php');
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') $errors[] = 'Ese correo ya tiene una cuenta registrada.';
            else { error_log($e->getMessage()); $errors[] = 'No pudimos crear la cuenta. Inténtalo más tarde.'; }
        }
    }
}
require __DIR__ . '/../includes/header.php';
?>
<section class="auth-wrap"><div class="auth-aside"><span class="eyebrow">BIENVENIDA A ROSETTA</span><h1>Un espacio<br><em>para ti.</em></h1><p>Crea tu cuenta y guarda tus momentos favoritos en un solo lugar.</p></div>
<div class="auth-card"><span class="eyebrow">EMPIEZA AQUÍ</span><h2>Crear cuenta</h2>
<?php foreach ($errors as $error): ?><div class="notice error"><?= e($error) ?></div><?php endforeach; ?>
<form method="post" class="form-stack">
<input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
<label>Nombre completo<input name="name" value="<?= e($name) ?>" required maxlength="120" autocomplete="name"></label>
<label>Correo electrónico<input type="email" name="email" value="<?= e($email) ?>" required autocomplete="email"></label>
<label>Contraseña<input type="password" name="password" required minlength="10" autocomplete="new-password"><small>Usa al menos 10 caracteres.</small></label>
<label>Confirmar contraseña<input type="password" name="confirm_password" required minlength="10" autocomplete="new-password"></label>
<button class="button button-dark" type="submit">Crear mi cuenta</button>
</form><p class="form-bottom">¿Ya tienes cuenta? <a href="<?= BASE_URL ?>/login.php">Inicia sesión</a></p></div></section>
<?php require __DIR__ . '/../includes/footer.php'; ?>

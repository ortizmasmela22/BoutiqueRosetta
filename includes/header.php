<?php
$pageTitle = $pageTitle ?? APP_NAME;
$user = current_user();
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="theme-color" content="#f6a6a0">
  <title><?= e($pageTitle) ?> · Boutique Rosetta</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Dancing+Script:wght@600;700&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>
<div class="page-loader" id="pageLoader" aria-label="Cargando Boutique Rosetta">
  <div class="loader-orbit"><img class="loader-logo" src="<?= BASE_URL ?>/assets/images/logo-rosetta.jpg" alt="Logo Boutique Rosetta"></div>
  <p>Boutique Rosetta</p><small>Amor y comodidad en cada detalle</small>
  <div class="loader-line"><span></span></div>
</div>
<header class="site-header">
  <a class="brand" href="<?= BASE_URL ?>/index.php" aria-label="Boutique Rosetta, inicio">
    <img class="brand-mark" src="<?= BASE_URL ?>/assets/images/logo-rosetta.jpg" alt="Logo Boutique Rosetta">
    <span class="brand-copy"><strong>Boutique Rosetta</strong><small>Amor y comodidad en cada detalle</small></span>
  </a>
  <button class="menu-toggle" id="menuToggle" aria-expanded="false" aria-controls="mainNav">☰</button>
  <nav class="main-nav" id="mainNav">
    <a href="<?= BASE_URL ?>/index.php">Inicio</a>
    <a href="<?= BASE_URL ?>/products.php">Colección</a>
    <a href="<?= BASE_URL ?>/cart.php">Carrito<?php if ($user): ?> (<?= cart_count() ?>)<?php endif; ?></a>
    <?php if ($user): ?>
      <a href="<?= BASE_URL ?>/profile.php">Mi cuenta</a>
      <?php if (($user['role'] ?? '') === 'admin'): ?><a href="<?= BASE_URL ?>/admin/index.php">Administración</a><?php endif; ?>
      <a href="<?= BASE_URL ?>/logout.php">Salir</a>
    <?php else: ?>
      <a href="<?= BASE_URL ?>/login.php">Ingresar</a>
      <a class="nav-cta" href="<?= BASE_URL ?>/register.php">Crear cuenta</a>
    <?php endif; ?>
  </nav>
</header>
<main class="site-main">
<?php if ($msg = flash('success')): ?><div class="notice success"><?= e($msg) ?></div><?php endif; ?>
<?php if ($msg = flash('error')): ?><div class="notice error"><?= e($msg) ?></div><?php endif; ?>

<?php
declare(strict_types=1);

/*
 * Configuración local. No subas credenciales reales a repositorios públicos.
 * En XAMPP suele funcionar con usuario root y contraseña vacía en local.
 */
define('APP_NAME', 'Boutique Rosetta');
define('BASE_URL', '/boutique-rosetta/public');
define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'boutique_rosetta');
define('DB_USER', 'root');
define('DB_PASS', '');
define('SESSION_NAME', 'rosetta_session');

<?php
declare(strict_types=1);

function db(): PDO {
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $esLocal = in_array($_SERVER['SERVER_NAME'] ?? 'localhost', ['localhost', '127.0.0.1'], true);

    if ($esLocal) {
        $host = 'localhost';
        $db   = 'boutique_rosetta';
        $user = 'root';
        $pass = '';
    } else {
        $host = 'sql113.infinityfree.com';
        $db   = 'TU_BASE_EN_INFINITYFREE';
        $user = 'TU_USUARIO_INFINITYFREE';
        $pass = 'TU_CLAVE_INFINITYFREE';
    }

    $pdo = new PDO(
        "mysql:host=$host;dbname=$db;charset=utf8mb4",
        $user,
        $pass,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );

    return $pdo;
}
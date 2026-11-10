<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_name(SESSION_NAME);
    session_set_cookie_params([
        'httponly' => true,
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'samesite' => 'Lax',
    ]);
    session_start();
}
function e(?string $value): string {
    return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
function redirect(string $path): never {
    header('Location: ' . BASE_URL . $path);
    exit;
}
function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf_token'];
}
function verify_csrf(): void {
    $sent = $_POST['csrf_token'] ?? '';
    if (!is_string($sent) || !hash_equals(csrf_token(), $sent)) {
        http_response_code(419);
        exit('La sesión del formulario expiró. Regresa e inténtalo nuevamente.');
    }
}
function current_user(): ?array { return $_SESSION['user'] ?? null; }
function require_login(): void {
    if (!current_user()) {
        flash('error', 'Inicia sesión para continuar con tu compra.');
        redirect('/login.php');
    }
}
function require_admin(): void {
    require_login();
    if ((current_user()['role'] ?? '') !== 'admin') {
        http_response_code(403);
        exit('No tienes permiso para acceder a esta sección.');
    }
}
function flash(string $key, ?string $message = null): ?string {
    if ($message !== null) { $_SESSION['flash'][$key] = $message; return null; }
    $value = $_SESSION['flash'][$key] ?? null;
    unset($_SESSION['flash'][$key]);
    return $value;
}
/**
 * Expira pedidos pendientes y devuelve al inventario las unidades reservadas.
 * Se ejecuta al cargar páginas de la tienda; no necesita un cron para la primera versión.
 */
function expire_pending_orders(): void {
    $pdo = db();
    $pdo->beginTransaction();
    try {
        $q = $pdo->query("SELECT id FROM orders
            WHERE status='pending_payment' AND payment_status IN ('unpaid','pending')
            AND expires_at IS NOT NULL AND expires_at <= NOW()
            FOR UPDATE");
        $ids = $q->fetchAll(PDO::FETCH_COLUMN);
        foreach ($ids as $orderId) {
            $items = $pdo->prepare('SELECT product_id, quantity FROM order_items WHERE order_id=? AND product_id IS NOT NULL');
            $items->execute([(int)$orderId]);
            foreach ($items->fetchAll() as $item) {
                $release = $pdo->prepare('UPDATE products SET reserved_stock = GREATEST(0, reserved_stock - ?) WHERE id=?');
                $release->execute([(int)$item['quantity'], (int)$item['product_id']]);
            }
            $pdo->prepare("UPDATE orders SET status='expired', payment_status='failed', cancelled_at=NOW(),
                internal_note=CONCAT(COALESCE(internal_note,''), '\nPedido vencido automáticamente después de 60 minutos.')
                WHERE id=? AND status='pending_payment'")->execute([(int)$orderId]);
            $pdo->prepare("INSERT INTO notifications (type,title,message,related_order_id)
                VALUES ('order_expired','Pedido vencido','Un pedido superó el límite de 60 minutos sin confirmación de pago.',?)")
                ->execute([(int)$orderId]);
        }
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log('Error al expirar pedidos: ' . $e->getMessage());
    }
}
expire_pending_orders();
function cart_count(): int {
    if (!current_user()) return 0;
    $stmt = db()->prepare('SELECT COALESCE(SUM(ci.quantity),0) FROM carts c JOIN cart_items ci ON ci.cart_id=c.id WHERE c.user_id=?');
    $stmt->execute([(int)current_user()['id']]);
    return (int)$stmt->fetchColumn();
}

# Boutique Rosetta — base desde cero

Proyecto PHP + MySQL para ejecutar localmente con XAMPP.

## Requisitos
- XAMPP con Apache y MySQL/MariaDB.
- PHP 8.1 o superior recomendado.
- Navegador moderno.

## Instalación local
1. Copia la carpeta `boutique-rosetta` dentro de `C:\xampp\htdocs\`.
2. Inicia Apache y MySQL desde XAMPP.
3. Abre `http://localhost/phpmyadmin`.
4. Importa el archivo `database.sql` (pestaña Importar).
5. Revisa `config/config.php` y configura los datos de conexión.
6. Abre `http://localhost/boutique-rosetta/public/`.

## Crear el primer administrador
Por seguridad, el proyecto no incluye una contraseña de administrador predeterminada.
1. Registra una cuenta desde la web.
2. En phpMyAdmin abre la tabla `users` y cambia el campo `role` de esa cuenta de `customer` a `admin`.
3. Cierra sesión y vuelve a iniciar sesión. El enlace al panel de administración aparecerá para esa cuenta.

## Importante
- Esta es la primera entrega de la estructura y la base de datos. La integración real de Wompi requiere credenciales del comercio, configuración de servidor y verificación de webhooks; no está activada en este paquete.
- El flujo de pago está preparado para ampliarse, pero no se deben marcar pagos como confirmados basándose solo en una redirección del navegador.
- Para producción se necesita HTTPS, credenciales seguras, configuración de correo, copias de seguridad y pruebas de seguridad.


### Ajustes del panel y carrito
- El carrito muestra una vista previa inmediata del total al cambiar cantidades; pulsa **Actualizar** para guardar el cambio en MySQL. El checkout vuelve a calcular los importes en el servidor.
- Las gráficas del panel se calculan directamente con las tablas `orders` y `order_items`, y cuentan solo pedidos con `payment_status='paid'` que no estén cancelados ni expirados. Se actualizan al recargar el panel.
- No se requiere volver a importar `database.sql` ni borrar los datos existentes.
- La pasarela Wompi aún es una integración pendiente: crear un pedido no equivale a cobrarlo.

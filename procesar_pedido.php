// procesar_pedido.php (ajustado para single: inserta con cantidad e id_producto si el esquema lo tiene, y también agrega a detalles para consistencia futura)
<?php
// procesar_pedido.php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
include "taqueriadb.php";
$nombre = $_POST['nombre'] ?? '';
$telefono = $_POST['telefono'] ?? '';
$direccion = $_POST['direccion'] ?? '';
$id_producto = intval($_POST['id_producto'] ?? 0);
$cantidad = intval($_POST['cantidad'] ?? 1);
if ($nombre === '' || $id_producto <= 0) {
    echo "Datos incompletos. <a href='menu.php'>Volver</a>";
    exit;
}
// Insertar cliente
$stmt = $conn->prepare("INSERT INTO clientes (nombre, telefono, direccion) VALUES (?, ?, ?)");
$stmt->bind_param("sss", $nombre, $telefono, $direccion);
$stmt->execute();
$id_cliente = $stmt->insert_id;
$stmt->close();
// Obtener precio del producto
$stmt = $conn->prepare("SELECT precio FROM productos WHERE id = ?");
$stmt->bind_param("i", $id_producto);
$stmt->execute();
$stmt->bind_result($precio);
$precio_obtenido = 0;
if ($stmt->fetch()) {
    $precio_obtenido = $precio;
}
$stmt->close();
$total = $precio_obtenido * $cantidad;
// Insertar pedido (incluyendo cantidad e id_producto para compatibilidad con tu esquema)
$stmt = $conn->prepare("INSERT INTO pedidos (id_cliente, total, fecha, cantidad, id_producto) VALUES (?, ?, NOW(), ?, ?)");
$stmt->bind_param("idii", $id_cliente, $total, $cantidad, $id_producto);
$stmt->execute();
$id_pedido = $stmt->insert_id;
$stmt->close();
// También agregar a detalles para consistencia (no afecta lo anterior)
$stmt = $conn->prepare("INSERT INTO detalles_pedidos (id_pedido, id_producto, cantidad) VALUES (?, ?, ?)");
$stmt->bind_param("iii", $id_pedido, $id_producto, $cantidad);
$stmt->execute();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Pedido Procesado</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header>
        <nav>
            <a href="menu.php"> Inicio </a>
            <a href="carrito.php"> Pedidos </a>
            <a href="logout.php" style="background:#ff5722;color:#fff;padding:8px 12px;border-radius:6px;text-decoration:none;"> Cerrar sesión </a>
        </nav>
    </header>
    <div class="contenedor">
        <h2> ¡Pedido agregado correctamente! 🎉 </h2>
        <p> <b> Cliente: </b> <?= htmlspecialchars($nombre) ?> </p>
        <p> <b> Total: </b> $ <?= number_format($total,2) ?> </p>
        <a href="menu.php" class="btn"> 🍽️ Volver al menú </a>
    </div>
</body>
</html>

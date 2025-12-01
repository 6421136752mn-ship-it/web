<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
include "taqueriadb.php";

if (!isset($_GET['id'])) {
    die("ID de pedido no especificado.");
}
$id = intval($_GET['id']);

$sql = "SELECT p.id, p.total, p.fecha, c.nombre, c.telefono, c.direccion 
        FROM pedidos p 
        JOIN clientes c ON p.id_cliente = c.id 
        WHERE p.id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$res = $stmt->get_result();
if ($res->num_rows == 0) {
    die("Pedido no encontrado.");
}
$pedido = $res->fetch_assoc();
$stmt->close();

$detalles = [];
$sql_det = "SELECT dp.cantidad, pr.nombre, pr.precio 
            FROM detalles_pedidos dp 
            JOIN productos pr ON dp.id_producto = pr.id 
            WHERE dp.id_pedido = ?";
$stmt_det = $conn->prepare($sql_det);
$stmt_det->bind_param("i", $id);
$stmt_det->execute();
$res_det = $stmt_det->get_result();
while ($row = $res_det->fetch_assoc()) {
    $detalles[] = $row;
}
$stmt_det->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Detalles del Pedido</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<!-- MISMO FONDO Y DISEÑO QUE EDITAR_PEDIDO.PHP -->
<body class="bg-gradient-to-br from-orange-100 to-red-100 min-h-screen flex items-center justify-center">

    <div class="bg-white p-8 rounded-xl shadow-2xl w-full max-w-md">
        <h2 class="text-2xl font-bold text-orange-600 mb-6 text-center">Detalles del Pedido 🌮</h2>

        <p><b>Cliente:</b> <?= htmlspecialchars($pedido['nombre']) ?></p>
        <p><b>Teléfono:</b> <?= htmlspecialchars($pedido['telefono']) ?></p>
        <p><b>Dirección:</b> <?= htmlspecialchars($pedido['direccion']) ?></p>
        <p><b>Fecha:</b> <?= htmlspecialchars($pedido['fecha']) ?></p>
        <p><b>Total:</b> $<?= number_format($pedido['total'], 2) ?></p>

        <h3 class="text-xl font-bold mt-4 mb-2 text-orange-600">Total:</h3>

        <?php if (!empty($detalles)): ?>
            <ul class="space-y-2">
                <?php foreach ($detalles as $item): ?>
                    <li class="border-b pb-2">
                        <b><?= htmlspecialchars($item['nombre']) ?></b><br>
                        Cantidad: <?= $item['cantidad'] ?><br>
                        Subtotal: $<?= number_format($item['precio'] * $item['cantidad'], 2) ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p class="text-red-500">Pedido sin detalles registrados.</p>
        <?php endif; ?>

        <nav class="mt-6 text-center space-x-4">
            <a href="menu.php" class="text-orange-600 hover:underline">Inicio</a>
            <a href="pedidos.php" class="text-orange-600 hover:underline">Pedidos</a>
            <a href="logout.php" class="bg-red-500 text-white px-4 py-2 rounded-lg hover:bg-red-600">Cerrar sesión</a>
        </nav>
    </div>

</body>
</html>

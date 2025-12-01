// agregar_pedido.php (Modificado para un diseño más hermoso con Tailwind CSS, manteniendo la funcionalidad intacta)
<?php
// agregar_pedido.php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
include "taqueriadb.php";
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nombre = $_POST['nombre'] ?? '';
    $telefono = $_POST['telefono'] ?? '';
    $direccion = $_POST['direccion'] ?? '';
    $total = floatval($_POST['total'] ?? 0);
    $stmt = $conn->prepare("INSERT INTO clientes (nombre, telefono, direccion) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $nombre, $telefono, $direccion);
    $stmt->execute();
    $id_cliente = $stmt->insert_id;
    $stmt->close();
    $stmt2 = $conn->prepare("INSERT INTO pedidos (id_cliente, total) VALUES (?, ?)");
    $stmt2->bind_param("id", $id_cliente, $total);
    $stmt2->execute();
    $stmt2->close();
    echo "<script>alert('Pedido agregado correctamente'); window.location='carrito.php';</script>";
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Agregar Pedido</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-br from-orange-100 to-red-100 min-h-screen flex items-center justify-center">
    <div class="bg-white p-8 rounded-xl shadow-2xl w-full max-w-md">
        <h2 class="text-2xl font-bold text-orange-600 mb-6 text-center">Agregar Nuevo Pedido 🌮</h2>
        <form method="POST" class="space-y-4">
            <input type="text" name="nombre" placeholder="Nombre del cliente" required class="w-full p-3 border border-orange-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500">
            <input type="text" name="telefono" placeholder="Teléfono" required class="w-full p-3 border border-orange-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500">
            <input type="text" name="direccion" placeholder="Dirección" required class="w-full p-3 border border-orange-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500">
            <input type="number" step="0.01" name="total" placeholder="Total del pedido" required class="w-full p-3 border border-orange-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500">
            <button type="submit" class="w-full bg-orange-500 text-white py-3 rounded-lg font-semibold hover:bg-orange-600 transition">Agregar Pedido</button>
        </form>
        <nav class="mt-6 text-center space-x-4">
            <a href="menu.php" class="text-orange-600 hover:underline">Inicio</a>
            <a href="carrito.php" class="text-orange-600 hover:underline">Pedidos</a>
            <a href="logout.php" class="bg-red-500 text-white px-4 py-2 rounded-lg hover:bg-red-600">Cerrar sesión</a>
        </nav>
    </div>
</body>
</html>
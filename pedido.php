<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
include "taqueriadb.php";

$id_producto = intval($_GET['id'] ?? 0);
$sql = "SELECT * FROM productos WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id_producto);
$stmt->execute();
$result = $stmt->get_result();
$producto = $result->fetch_assoc();
$stmt->close();

if (!$producto) {
    echo "Producto no encontrado. <a href='menu.php'>Volver al menú</a>";
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Agregar al Carrito</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen flex items-center justify-center bg-cover bg-center" style="background-image: url('img/Fondo para Pedido.jpg');">
    <div class="bg-white p-8 rounded-xl shadow-2xl w-full max-w-md">
        <h2 class="text-2xl font-bold text-orange-600 mb-6 text-center">
            Agregar <?= htmlspecialchars($producto['nombre']) ?> al Carrito 🌮
        </h2>
        <form method="post" action="agregar_al_carrito.php" class="space-y-4">
            <input type="hidden" name="id_producto" value="<?= htmlspecialchars($producto['id']) ?>">
            <label class="block text-gray-700">Cantidad:</label>
            <input type="number" name="cantidad" value="1" min="1" required class="w-full p-3 border border-orange-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500">
            <button type="submit" class="w-full bg-orange-500 text-white py-3 rounded-lg font-semibold hover:bg-orange-600 transition">
                Agregar al Carrito
            </button>
        </form>
        <nav class="mt-6 text-center space-x-4">
            <a href="menu.php" class="text-orange-600 hover:underline">Volver al Menú</a>
            <a href="carrito.php" class="text-orange-600 hover:underline">Ver Carrito</a>
            <a href="logout.php" class="bg-red-500 text-white px-4 py-2 rounded-lg hover:bg-red-600">Cerrar sesión</a>
        </nav>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        <?php
        // Verifica si existe el mensaje de éxito en la sesión
        if (isset($_SESSION['mensaje_exito'])):
        ?>
            Swal.fire({
                title: '¡Añadido! 🎉',
                text: '<?php echo htmlspecialchars($_SESSION['mensaje_exito']); ?>',
                icon: 'success', // Muestra el ícono de éxito (palomita)
                confirmButtonText: 'Aceptar',
                confirmButtonColor: '#ea580c' // Color naranja de Tailwind (orange-600)
            });
            <?php
            // IMPORTANTE: Elimina el mensaje de la sesión para que no se muestre al refrescar
            unset($_SESSION['mensaje_exito']);
            ?>
        <?php endif; ?>
    </script>
</body>
</html>
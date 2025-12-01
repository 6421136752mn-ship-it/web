<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
include "taqueriadb.php";

// ============= ACCIONES =============
if (isset($_GET['accion']) && isset($_GET['id'])) {
    $id = $_GET['id'];

    if ($_GET['accion'] == 'eliminar') {
        unset($_SESSION['carrito'][$id]);
        header("Location: carrito.php?msg=eliminado");
        exit;
    }

    if ($_GET['accion'] == 'sumar') {
        $_SESSION['carrito'][$id]['cantidad']++;
        header("Location: carrito.php?msg=sumado");
        exit;
    }

    if ($_GET['accion'] == 'restar') {
        $_SESSION['carrito'][$id]['cantidad']--;
        if ($_SESSION['carrito'][$id]['cantidad'] <= 0) {
            unset($_SESSION['carrito'][$id]);
            header("Location: carrito.php?msg=eliminado");
            exit;
        }
        header("Location: carrito.php?msg=restado");
        exit;
    }
}

// =========== PROCESAR PEDIDO ==========
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nombre = $_POST['nombre'] ?? '';
    $telefono = $_POST['telefono'] ?? '';
    $direccion = $_POST['direccion'] ?? '';

    if ($nombre === '' || empty($_SESSION['carrito'])) {
        header("Location: carrito.php?error=1");
        exit;
    }

    // Calcular total
    $total = 0;
    foreach ($_SESSION['carrito'] as $item) {
        $total += $item['precio'] * $item['cantidad'];
    }

    // Insertar cliente
    $stmt = $conn->prepare("INSERT INTO clientes (nombre, telefono, direccion) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $nombre, $telefono, $direccion);
    $stmt->execute();
    $id_cliente = $stmt->insert_id;
    $stmt->close();

    // Insertar pedido
    $stmt = $conn->prepare("INSERT INTO pedidos (id_cliente, total, fecha) VALUES (?, ?, NOW())");
    $stmt->bind_param("id", $id_cliente, $total);
    $stmt->execute();
    $id_pedido = $stmt->insert_id;
    $stmt->close();

    // Insertar detalles
    foreach ($_SESSION['carrito'] as $item) {
        $stmt = $conn->prepare("INSERT INTO detalles_pedidos (id_pedido, id_producto, cantidad) VALUES (?, ?, ?)");
        $stmt->bind_param("iii", $id_pedido, $item['id'], $item['cantidad']);
        $stmt->execute();
        $stmt->close();
    }

    unset($_SESSION['carrito']);

    header("Location: carrito.php?pedido=ok");
    exit;
}

// ============ MOSTRAR CARRITO ============
$carrito = $_SESSION['carrito'] ?? [];
$total = 0;
foreach ($carrito as $item) {
    $total += $item['precio'] * $item['cantidad'];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Carrito Actual</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body class="min-h-screen p-6 bg-cover bg-center" style="background-image: url('img/Fondo para Carrito.jpg');">

<header class="bg-orange-600 text-white p-4 rounded-lg shadow-md mb-6">
    <nav class="flex justify-between items-center">
        <div class="flex space-x-8 items-center">
            <a href="menu.php" class="hover:scale-125 transition transform">
                <i data-lucide="home" class="w-8 h-8"></i>
            </a>
            <a href="carrito.php" class="hover:scale-125 transition transform font-semibold">
                <i data-lucide="shopping-cart" class="w-8 h-8"></i>
            </a>
            <a href="pedidos.php" class="hover:scale-125 transition transform">
                <i data-lucide="clipboard-list" class="w-8 h-8"></i>
            </a>
        </div>
        <div class="flex items-center space-x-4">
            <span>Bienvenido, <?= htmlspecialchars($_SESSION['usuario_nombre'] ?? '') ?></span>
            <a href="logout.php" class="bg-red-500 px-4 py-2 rounded-lg hover:bg-red-600">Cerrar sesión</a>
        </div>
    </nav>
</header>

<main class="bg-white p-6 rounded-xl shadow-2xl max-w-4xl mx-auto">
    <h2 class="text-2xl font-bold text-orange-600 mb-4 text-center">Tu Carrito Actual 🌮</h2>

    <?php if (!empty($carrito)): ?>
        <table class="w-full border-collapse mb-6">
            <thead>
                <tr class="bg-orange-200">
                    <th class="p-3 border border-orange-300">Producto</th>
                    <th class="p-3 border border-orange-300">Cantidad</th>
                    <th class="p-3 border border-orange-300">Precio Unitario</th>
                    <th class="p-3 border border-orange-300">Subtotal</th>
                    <th class="p-3 border border-orange-300">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($carrito as $id => $item): ?>
                    <tr class="hover:bg-orange-50">
                        <td class="p-3 border border-orange-300"><?= $item['nombre'] ?></td>
                        <td class="p-3 border border-orange-300">
                            <a href="carrito.php?accion=restar&id=<?= $id ?>" class="px-2 font-bold text-lg">➖</a>
                            <?= $item['cantidad'] ?>
                            <a href="carrito.php?accion=sumar&id=<?= $id ?>" class="px-2 font-bold text-lg">➕</a>
                        </td>
                        <td class="p-3 border border-orange-300">$ <?= number_format($item['precio'], 2) ?></td>
                        <td class="p-3 border border-orange-300">$ <?= number_format($item['precio'] * $item['cantidad'], 2) ?></td>
                        <td class="p-3 border border-orange-300 text-center">
                            <a href="carrito.php?accion=eliminar&id=<?= $id ?>" class="text-red-600 font-bold">🗑 Eliminar</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <p class="text-xl font-bold text-right mb-4">Total: $ <?= number_format($total, 2) ?></p>

        <h3 class="text-xl font-bold text-orange-600 mb-4">Información del Cliente</h3>
        <form method="POST" class="space-y-4">
            <input type="text" name="nombre" placeholder="Nombre del cliente" required class="w-full p-3 border border-orange-300 rounded-lg">
            <input type="text" name="telefono" placeholder="Teléfono" required class="w-full p-3 border border-orange-300 rounded-lg">
            <input type="text" name="direccion" placeholder="Dirección" required class="w-full p-3 border border-orange-300 rounded-lg">
            <button type="submit" class="w-full bg-orange-500 text-white py-3 rounded-lg font-semibold hover:bg-orange-600 transition">Procesar Pedido Mixto</button>
        </form>

        <div class="text-center mt-4">
            <a href="menu.php" class="text-blue-600 hover:underline font-semibold">⬅ Volver al inicio</a>
        </div>

    <?php else: ?>
        <p class="text-center text-gray-600">El carrito está vacío. Agrega productos desde el menú.</p>
    <?php endif; ?>
</main>

<script src="https://unpkg.com/lucide@latest"></script>
<script> lucide.createIcons(); </script>

<script>
// ======================= SweetAlerts =======================

<?php if (isset($_GET['error']) && $_GET['error'] == 1): ?>
Swal.fire({
    icon: 'error',
    title: 'Datos incompletos',
    text: 'Falta el nombre o el carrito está vacío.',
    confirmButtonColor: '#d33'
});
<?php endif; ?>

<?php if (isset($_GET['pedido']) && $_GET['pedido'] == 'ok'): ?>
Swal.fire({
    icon: 'success',
    title: 'Pedido procesado',
    text: '¡Pedido mixto registrado correctamente!',
    confirmButtonColor: '#28a745'
});
<?php endif; ?>

<?php if (isset($_GET['msg'])): 
    $msgs = [
        'eliminado' => ['Producto eliminado', 'info'],
        'sumado'    => ['Cantidad aumentada', 'success'],
        'restado'   => ['Cantidad disminuida', 'warning']
    ];
    $m = $msgs[$_GET['msg']] ?? null;
    if ($m): ?>
Swal.fire({
    icon: '<?= $m[1] ?>',
    title: '<?= $m[0] ?>',
    timer: 1200,
    showConfirmButton: false
});
<?php endif; endif; ?>

</script>

</body>
</html>

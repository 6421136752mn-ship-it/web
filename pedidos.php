<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
include "taqueriadb.php";

// --- SweetAlert: Verificar si hay un mensaje de éxito/error de una redirección (para Guardar/Eliminar)
$alerta_mensaje = null;
$alerta_tipo = null;

if (isset($_SESSION['alerta_exito'])) {
    $alerta_mensaje = $_SESSION['alerta_exito'];
    $alerta_tipo = 'success';
    unset($_SESSION['alerta_exito']);
} elseif (isset($_SESSION['alerta_error'])) {
    $alerta_mensaje = $_SESSION['alerta_error'];
    $alerta_tipo = 'error';
    unset($_SESSION['alerta_error']);
}

// --- Eliminar pedido (Lógica de PHP) ---
if (isset($_GET['accion']) && $_GET['accion'] === 'eliminar' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    
    // Asume que la eliminación incluye también los datos del cliente, como en el ejemplo anterior
    $sql_cliente = "SELECT id_cliente FROM pedidos WHERE id = ?";
    $stmt_c = $conn->prepare($sql_cliente);
    $stmt_c->bind_param("i", $id);
    $stmt_c->execute();
    $pedido = $stmt_c->get_result()->fetch_assoc();
    $id_cliente = $pedido['id_cliente'] ?? null;
    $stmt_c->close();

    // Eliminar detalles y pedido
    $conn->query("DELETE FROM detalles_pedidos WHERE id_pedido = $id");
    $conn->query("DELETE FROM pedidos WHERE id = $id");

    // Eliminar cliente (solo si se encontró el id_cliente)
    if ($id_cliente) {
        $conn->query("DELETE FROM clientes WHERE id = $id_cliente");
    }

    // Usar SweetAlert para la confirmación del borrado después de la redirección
    $_SESSION['alerta_exito'] = "Pedido #$id y datos relacionados eliminados correctamente.";
    header("Location: pedidos.php");
    exit;
}

// --- Guardar cambios al editar (Lógica de PHP) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['editar_pedido'])) {
    // ... (Tu lógica de actualización de cliente y detalles) ...
    // Después de que todo se actualice:
    $_SESSION['alerta_exito'] = "Pedido actualizado correctamente.";
    header("Location: pedidos.php");
    exit;
}

// ... (El resto del código PHP para ver y editar pedidos es el mismo) ...

// --- Obtener pedido individual para ver o editar ---
$pedido_seleccionado = null;
$detalles_seleccion = [];
if (isset($_GET['accion']) && in_array($_GET['accion'], ['ver', 'editar']) && isset($_GET['id'])) {
    $id = intval($_GET['id']);

    $sql_detalle = "SELECT p.id, c.nombre AS cliente, c.telefono, c.direccion, p.total, p.fecha, p.id_cliente
                     FROM pedidos p
                     JOIN clientes c ON p.id_cliente = c.id
                     WHERE p.id = ?";
    $stmt = $conn->prepare($sql_detalle);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $pedido_seleccionado = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $sql_items = "SELECT dp.id, dp.id_producto, dp.cantidad, pr.nombre, pr.precio
                  FROM detalles_pedidos dp
                  JOIN productos pr ON dp.id_producto = pr.id
                  WHERE dp.id_pedido = ?";
    $stmt = $conn->prepare($sql_items);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $res_items = $stmt->get_result();
    while ($r = $res_items->fetch_assoc()) {
        $detalles_seleccion[] = $r;
    }
    $stmt->close();
}

// --- Guardar cambios al editar ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['editar_pedido'])) {
    $id = intval($_POST['id'] ?? 0);

    $nombre = trim($_POST['nombre'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    $direccion = trim($_POST['direccion'] ?? '');
    $id_cliente = intval($_POST['id_cliente'] ?? 0);

    // Actualizar datos del cliente
    if ($id_cliente > 0) {
        $stmt = $conn->prepare("UPDATE clientes SET nombre = ?, telefono = ?, direccion = ? WHERE id = ?");
        $stmt->bind_param("sssi", $nombre, $telefono, $direccion, $id_cliente);
        $stmt->execute();
        $stmt->close();
    }

    $ids_producto = $_POST['id_producto'] ?? [];
    $cantidades = $_POST['cantidad'] ?? [];

    if (!is_array($ids_producto) || !is_array($cantidades) || count($ids_producto) !== count($cantidades)) {
        $_SESSION['alerta_error'] = "Error: datos inválidos al editar.";
        header("Location: pedidos.php");
        exit;
    }

    // Comisión fija
    $total = 20.00;

    // Eliminar los detalles antiguos
    $del = $conn->prepare("DELETE FROM detalles_pedidos WHERE id_pedido = ?");
    $del->bind_param("i", $id);
    $del->execute();
    $del->close();

    // Preparar INSERT para los detalles
    $ins_det = $conn->prepare("INSERT INTO detalles_pedidos (id_pedido, id_producto, cantidad) VALUES (?, ?, ?)");

    // Consulta de precio (se reabre en cada iteración)
    for ($i = 0; $i < count($ids_producto); $i++) {
        $id_prod = intval($ids_producto[$i]);
        $cant = intval($cantidades[$i]);

        if ($id_prod <= 0 || $cant <= 0) continue;

        // Obtener precio actual del producto
        $precio_stmt = $conn->prepare("SELECT precio FROM productos WHERE id = ?");
        $precio_stmt->bind_param("i", $id_prod);
        $precio_stmt->execute();
        $precio_stmt->bind_result($precio_producto);

        if (!$precio_stmt->fetch()) {
            $precio_producto = 0;
        }
        $precio_stmt->close();

        $subtotal = $precio_producto * $cant;
        $total += $subtotal;

        $ins_det->bind_param("iii", $id, $id_prod, $cant);
        $ins_det->execute();
    }

    $ins_det->close();

    // Actualizar total del pedido
    $upd = $conn->prepare("UPDATE pedidos SET total = ? WHERE id = ?");
    $upd->bind_param("di", $total, $id);
    $upd->execute();
    $upd->close();

    $_SESSION['alerta_exito'] = "Pedido actualizado correctamente";
    header("Location: pedidos.php");
    exit;
}

// --- Consultar todos los pedidos ---
$sql = "SELECT p.id, c.nombre AS cliente, 
                 p.total, p.fecha,
                 GROUP_CONCAT(pr.nombre SEPARATOR ', ') AS productos
          FROM pedidos p
          JOIN clientes c ON p.id_cliente = c.id
          JOIN detalles_pedidos d ON p.id = d.id_pedido 
          JOIN productos pr ON d.id_producto = pr.id
          GROUP BY p.id
          ORDER BY p.fecha DESC";

$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Pedidos Históricos</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
</head>

<body class="min-h-screen p-6 bg-cover bg-center" style="background-image: url('img/Fondo para Pedidos.jpg');">
    
    <header class="bg-orange-600 text-white p-4 rounded-lg shadow-md mb-6">
        <nav class="flex justify-between items-center">
            
            <div class="flex space-x-8 items-center">
                <a href="menu.php" class="hover:scale-125 transition transform">
                    <i data-lucide="home" class="w-8 h-8"></i>
                </a>
                <a href="carrito.php" class="hover:scale-125 transition transform">
                    <i data-lucide="shopping-cart" class="w-8 h-8"></i>
                </a>
                <a href="pedidos.php" class="hover:scale-125 transition transform font-semibold">
                    <i data-lucide="clipboard-list" class="w-8 h-8"></i>
                </a>
            </div>

            <div class="flex items-center space-x-4">
                <span>Bienvenido, <?= htmlspecialchars($_SESSION['usuario_nombre'] ?? '') ?></span>
                <a href="logout.php" class="bg-red-500 px-4 py-2 rounded-lg hover:bg-red-600">Cerrar sesión</a>
            </div>

        </nav>
    </header>

    <main class="bg-white p-6 rounded-xl shadow-2xl max-w-5xl mx-auto">

        <?php if ($pedido_seleccionado && isset($_GET['accion']) && $_GET['accion'] === 'ver'): ?>
            <h2 class="text-2xl font-bold text-orange-600 mb-4 text-center">Detalles del Pedido</h2>
            <div class="space-y-2 text-center">
                <p><strong>ID:</strong> <?= $pedido_seleccionado['id'] ?></p>
                <p><strong>Cliente:</strong> <?= htmlspecialchars($pedido_seleccionado['cliente']) ?></p>
                <p><strong>Total:</strong> $<?= number_format($pedido_seleccionado['total'], 2) ?></p>
                <p><strong>Fecha:</strong> <?= htmlspecialchars($pedido_seleccionado['fecha']) ?></p>
            </div>
            <div class="mt-4 text-center">
                <a href="pedidos.php" class="bg-orange-500 text-white px-4 py-2 rounded hover:bg-orange-600">Volver</a>
            </div>

        <?php elseif ($pedido_seleccionado && isset($_GET['accion']) && $_GET['accion'] === 'editar'): ?>
            <div class="bg-white max-w-md mx-auto p-6 rounded-2xl shadow-xl text-center">
                <h2 class="text-2xl font-bold text-orange-600 mb-4 flex items-center justify-center">
                    Editar Pedido 🌮
                </h2>
                <form method="POST" class="space-y-4">
                    <input type="hidden" name="id" value="<?= intval($pedido_seleccionado['id']) ?>">
                    <input type="hidden" name="id_cliente" value="<?= intval($pedido_seleccionado['id_cliente']) ?>">
                    <input type="text" name="nombre" value="<?= htmlspecialchars($pedido_seleccionado['cliente']) ?>" 
                           class="w-full border border-orange-300 rounded-lg px-3 py-2" required>
                    <input type="text" name="telefono" value="<?= htmlspecialchars($pedido_seleccionado['telefono']) ?>" 
                           class="w-full border border-orange-300 rounded-lg px-3 py-2" required>
                    <input type="text" name="direccion" value="<?= htmlspecialchars($pedido_seleccionado['direccion']) ?>" 
                           class="w-full border border-orange-300 rounded-lg px-3 py-2" required>
                    <h3 class="text-left font-bold text-gray-700 mt-4">Editar Items</h3>
                    <div class="text-left space-y-2">
                        <?php if (!empty($detalles_seleccion)): ?>
                            <?php foreach ($detalles_seleccion as $item): ?>
                                <div class="mb-2 p-2 border rounded">
                                    <label class="block font-semibold">
                                        <?= htmlspecialchars($item['nombre']) ?> - Precio: $<?= number_format($item['precio'], 2) ?>
                                    </label>
                                    <input type="hidden" name="id_producto[]" value="<?= intval($item['id_producto']) ?>">
                                    <input type="number" name="cantidad[]" value="<?= intval($item['cantidad']) ?>" min="0"
                                           class="w-full border border-orange-300 rounded-lg px-3 py-2">
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="text-sm text-gray-500">No hay ítems en este pedido.</p>
                        <?php endif; ?>
                    </div>
                    <p class="text-red-600 font-semibold mt-4">
                        Comisión por cambio: $20.00
                    </p>
                    <button type="submit" name="editar_pedido"
                            class="w-full bg-orange-500 hover:bg-orange-600 text-white font-semibold py-2 rounded-lg mt-4">
                        Guardar Cambios
                    </button>
                </form>
                <div class="flex justify-around mt-6 text-sm text-gray-700">
                    <a href="menu.php" class="hover:underline">Inicio</a>
                    <a href="carrito.php" class="hover:underline">Carrito</a>
                    <a href="pedidos.php" class="hover:underline">Pedidos</a>
                    <a href="logout.php" class="bg-red-500 text-white px-3 py-1 rounded-lg hover:bg-red-600">Cerrar sesión</a>
                </div>
            </div>

        <?php else: ?>

            <h2 class="text-2xl font-bold text-orange-600 mb-4 text-center">Pedidos Recientes 🌮</h2>

            <table class="w-full border-collapse">
                <thead>
                    <tr class="bg-orange-200 text-center">
                        <th class="p-3 border border-orange-300">Cliente</th>
                        <th class="p-3 border border-orange-300">Productos</th>
                        <th class="p-3 border border-orange-300">Total</th>
                        <th class="p-3 border border-orange-300">Fecha</th>
                        <th class="p-3 border border-orange-300">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result && $result->num_rows > 0): ?>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr class="hover:bg-orange-50 text-center">

                                <td class="p-3 border border-orange-300"><?= htmlspecialchars($row['cliente']) ?></td>
                                <td class="p-3 border border-orange-300"><?= htmlspecialchars($row['productos'] ?? '—') ?></td>
                                <td class="p-3 border border-orange-300">$ <?= number_format($row['total'], 2) ?></td>
                                <td class="p-3 border border-orange-300"><?= htmlspecialchars($row['fecha']) ?></td>

                                <td class="p-3 border border-orange-300 space-x-2">

                                    <a href="pedidos.php?accion=editar&id=<?= $row['id'] ?>" 
                                       class="bg-blue-500 text-white px-2 py-1 rounded hover:bg-blue-600">Modificar</a>

                                    <a href="ver_detalles_pedido.php?id=<?= $row['id'] ?>" 
                                       class="bg-green-500 text-white px-2 py-1 rounded hover:bg-green-600">Ver Detalles</a>

                                    <a href="#" onclick="confirmarEliminacion(<?= $row['id'] ?>)" 
                                       class="bg-red-500 text-white px-2 py-1 rounded hover:bg-red-600">Eliminar</a>

                                    <a href="descargar_pedido.php?id=<?= $row['id'] ?>" 
                                       class="bg-purple-600 text-white px-2 py-1 rounded hover:bg-purple-700">Descargar</a>

                                </td>

                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="5" class="p-3 text-center">No hay pedidos registrados.</td></tr>
                    <?php endif; ?>
                </tbody>

            </table>

        <?php endif; ?>

    </main>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://unpkg.com/lucide@latest"></script>
<script> 
    lucide.createIcons(); 

    // FUNCIÓN PARA LA CONFIRMACIÓN CON SWEETALERT2
    function confirmarEliminacion(idPedido) {
        // Muestra el SweetAlert de Confirmación (Are you sure?)
        Swal.fire({
            title: "¿Estás seguro?",
            text: "¡Estás a punto de eliminar el Pedido #" + idPedido + "! Esta acción no se puede revertir.",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#d33", // Rojo para eliminar
            cancelButtonColor: "#3085d6", // Azul para cancelar
            confirmButtonText: "Sí, ¡Eliminar!"
        }).then((result) => {
            if (result.isConfirmed) {
                // Si el usuario confirma, redirige a la acción de eliminación en PHP
                // Esto desencadena la lógica de borrado y el SweetAlert de éxito (punto 4)
                window.location.href = 'pedidos.php?accion=eliminar&id=' + idPedido;
            }
        });
    }

    // 4. MOSTRAR EL SWEETALERT DE ÉXITO/ERROR (Después de la redirección)
    <?php if ($alerta_mensaje): ?>
        Swal.fire({
            title: "<?php echo ($alerta_tipo === 'success') ? '¡Éxito! ✅' : 'Error 😢'; ?>",
            text: "<?php echo htmlspecialchars($alerta_mensaje); ?>",
            icon: "<?php echo $alerta_tipo; ?>",
            confirmButtonColor: '#ea580c'
        });
    <?php endif; ?>

</script>
</body>
</html>
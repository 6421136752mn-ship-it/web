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

// =========================
// OBTENER DATOS DEL PEDIDO
// =========================
$sql = "SELECT p.id, p.total, c.nombre, c.telefono, c.direccion, p.id_cliente 
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

// =========================
// OBTENER DETALLES
// =========================
$detalles = [];
$sql_det = "SELECT dp.id, dp.id_producto, dp.cantidad, pr.nombre, pr.precio 
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

// =========================
// PROCESAR ACTUALIZACIÓN
// =========================
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $nombre = $_POST['nombre'] ?? '';
    $telefono = $_POST['telefono'] ?? '';
    $direccion = $_POST['direccion'] ?? '';

    // Actualizar datos del cliente
    $stmt = $conn->prepare("UPDATE clientes SET nombre=?, telefono=?, direccion=? WHERE id=?");
    $stmt->bind_param("sssi", $nombre, $telefono, $direccion, $pedido['id_cliente']);
    $stmt->execute();
    $stmt->close();

    // Reiniciar total con comisión obligatoria
    $total = 20.00;

    // Borrar detalles anteriores
    $conn->query("DELETE FROM detalles_pedidos WHERE id_pedido = $id");

    // Insertar nuevamente los productos editados
    foreach ($_POST['cantidad'] as $det_id => $cant) {
        $cant = intval($cant);

        if ($cant > 0) {
            $id_prod = intval($_POST['id_producto'][$det_id]);

            $sql = "SELECT precio FROM productos WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $id_prod);
            $stmt->execute();
            $stmt->bind_result($precio);
            $stmt->fetch();
            $stmt->close();

            $subtotal = $precio * $cant;
            $total += $subtotal;

            $stmt = $conn->prepare("INSERT INTO detalles_pedidos (id_pedido, id_producto, cantidad) VALUES (?, ?, ?)");
            $stmt->bind_param("iii", $id, $id_prod, $cant);
            $stmt->execute();
            $stmt->close();
        }
    }

    // Actualizar total del pedido
    $stmt = $conn->prepare("UPDATE pedidos SET total=? WHERE id=?");
    $stmt->bind_param("di", $total, $id);
    $stmt->execute();
    $stmt->close();

    echo "<script>
        Swal.fire({
            icon: 'success',
            title: 'Pedido actualizado correctamente',
            confirmButtonColor: '#28a745'
        }).then(() => {
            window.location='pedidos.php';
        });
    </script>";
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Editar Pedido</title>
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- SWEETALERT -->
    <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
</head>

<body class="bg-gradient-to-br from-orange-100 to-red-100 min-h-screen flex items-center justify-center">
    <div class="bg-white p-8 rounded-xl shadow-2xl w-full max-w-md">

        <h2 class="text-2xl font-bold text-orange-600 mb-6 text-center">Editar Pedido 🌮</h2>

        <!-- FORMULARIO -->
        <form id="formEditar" method="POST" class="space-y-4">

            <input type="text" name="nombre" value="<?= htmlspecialchars($pedido['nombre']) ?>" required
                class="w-full p-3 border border-orange-300 rounded-lg">

            <input type="text" name="telefono" value="<?= htmlspecialchars($pedido['telefono']) ?>" required
                class="w-full p-3 border border-orange-300 rounded-lg">

            <input type="text" name="direccion" value="<?= htmlspecialchars($pedido['direccion']) ?>" required
                class="w-full p-3 border border-orange-300 rounded-lg">

            <h3 class="text-xl font-bold text-orange-600 mb-2">Editar Items</h3>

            <?php foreach ($detalles as $index => $item): ?>
                <div class="space-y-2 border-b pb-2">
                    <p><?= htmlspecialchars($item['nombre']) ?> - Precio: $<?= number_format($item['precio'], 2) ?></p>

                    <input type="hidden" name="id_producto[<?= $index ?>]" value="<?= $item['id_producto'] ?>">

                    <input type="number" min="0"
                        name="cantidad[<?= $index ?>]"
                        value="<?= $item['cantidad'] ?>"
                        class="w-full p-3 border border-orange-300 rounded-lg">
                </div>
            <?php endforeach; ?>

            <p class="text-xl font-bold text-red-600 mb-4">
                Comisión por Cambio: $20.00
            </p>

            <button type="submit"
                class="w-full bg-orange-500 text-white py-3 rounded-lg font-semibold hover:bg-orange-600 transition">
                Guardar Cambios
            </button>

        </form>

        <script>
        document.getElementById("formEditar").addEventListener("submit", function(e) {
            e.preventDefault(); // Detener envío

            Swal.fire({
                title: "¿Deseas actualizar tus datos?",
                icon: "question",
                showCancelButton: true,
                confirmButtonText: "Guardar",
                cancelButtonText: "Cancelar",
                confirmButtonColor: "#28a745",
                cancelButtonColor: "#d33"
            }).then((result) => {

                if (result.isConfirmed) {
                    // Enviar formulario
                    this.submit();

                } else {
                    Swal.fire({
                        icon: "info",
                        title: "Cancelado",
                        confirmButtonColor: "#d33"
                    });
                }

            });
        });
        </script>

        <nav class="mt-6 text-center space-x-4">
            <a href="menu.php" class="text-orange-600">Inicio</a>
            <a href="carrito.php" class="text-orange-600">Carrito</a>
            <a href="pedidos.php" class="text-orange-600">Pedidos</a>
            <a href="logout.php" class="bg-red-500 text-white px-4 py-2 rounded-lg">Cerrar sesión</a>
        </nav>
    </div>

</body>
</html>

<?php
session_start();
include "taqueriadb.php";

// Parámetros
$id = intval($_GET['id'] ?? 0);
// Usamos el parámetro 'confirm' para distinguir la fase de confirmación de la fase de ejecución
$confirmado = isset($_GET['confirm']) && $_GET['confirm'] === 'true';

if ($id <= 0) {
    // Si no hay ID, redirigimos inmediatamente con un mensaje de error
    echo "<script>alert('Error: ID de pedido no especificado.'); window.location='pedidos.php';</script>";
    exit;
}

// ==========================================================
// 1. FASE DE CONFIRMACIÓN (Muestra el "Are you sure?")
// Se ejecuta si no está confirmado.
// ==========================================================
if (!$confirmado) {
    // Imprimimos el HTML/JS para mostrar el SweetAlert de confirmación
    ?>
    <!DOCTYPE html>
    <html lang="es">
    <head>
        <title>Confirmar Eliminación</title>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    </head>
    <body>
    <script>
        // Muestra el SweetAlert de Confirmación (Are you sure?)
        Swal.fire({
            title: "¿Estás seguro?",
            text: "¡Estás a punto de eliminar el Pedido #<?php echo $id; ?>! Esta acción no se puede revertir.",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#d33", // Color rojo para eliminar
            cancelButtonColor: "#3085d6", // Color azul para cancelar
            confirmButtonText: "Sí, ¡Eliminar!"
        }).then((result) => {
            if (result.isConfirmed) {
                // Si el usuario confirma, se llama a este mismo script con ?confirm=true
                window.location.href = 'eliminar_pedido.php?id=<?php echo $id; ?>&confirm=true';
            } else {
                // Si el usuario cancela, lo redirigimos a la lista de pedidos
                window.location.href = 'pedidos.php';
            }
        });
    </script>
    </body>
    </html>
    <?php
    exit;
}

// ==========================================================
// 2. FASE DE EJECUCIÓN (Lógica de borrado)
// Se ejecuta SOLO si el parámetro ?confirm=true está presente.
// ==========================================================

// Obtener ID del cliente antes de borrar el pedido
$sql = "SELECT id_cliente FROM pedidos WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$res = $stmt->get_result();
$pedido = $res->fetch_assoc();
$stmt->close();

$borrado_exitoso = false;
$id_cliente = $pedido['id_cliente'] ?? null;
$mensaje = "El pedido no fue encontrado o ya fue eliminado.";

if ($id_cliente) {
    // Ejecutamos el borrado real en la base de datos
    $conn->query("DELETE FROM detalles_pedidos WHERE id_pedido = $id");
    $conn->query("DELETE FROM pedidos WHERE id = $id");
    $conn->query("DELETE FROM clientes WHERE id = $id_cliente");
    
    // Asumimos éxito si el ID del cliente existía para borrar
    $borrado_exitoso = true;
    $mensaje = "El Pedido #$id y sus datos han sido eliminados correctamente.";
}

// Imprimimos el SweetAlert de resultado (Deleted!)
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <title>Resultado de Eliminación</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
<script>
    Swal.fire({
        title: "<?php echo $borrado_exitoso ? '¡Eliminado! ✅' : 'Error 😢'; ?>",
        text: "<?php echo $mensaje; ?>",
        icon: "<?php echo $borrado_exitoso ? 'success' : 'error'; ?>",
        confirmButtonText: 'Aceptar',
        confirmButtonColor: '#ea580c'
    }).then(() => {
        // Redirigir a la lista de pedidos después de mostrar el resultado
        window.location.href = 'pedidos.php';
    });
</script>
</body>
</html>
<?php
exit;
?>
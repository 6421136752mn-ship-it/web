<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}
include "taqueriadb.php";

$id_producto = intval($_POST['id_producto'] ?? 0);
$cantidad = intval($_POST['cantidad'] ?? 1);

if ($id_producto <= 0 || $cantidad <= 0) {
    echo "<script>alert('Error: Producto o cantidad inválida.'); window.location='menu.php';</script>";
    exit;
}

// 1. Obtener datos del producto de la DB
$sql = "SELECT nombre, precio FROM productos WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id_producto);
$stmt->execute();
$result = $stmt->get_result();
$producto = $result->fetch_assoc();
$stmt->close();

if (!$producto) {
    echo "<script>alert('Error: Producto no encontrado.'); window.location='menu.php';</script>";
    exit;
}

// 2. Inicializar el carrito si no existe
if (!isset($_SESSION['carrito'])) {
    $_SESSION['carrito'] = [];
}

// 3. Agregar/Actualizar el producto en el carrito
if (isset($_SESSION['carrito'][$id_producto])) {
    // Si el producto ya está, solo se suma la cantidad
    $_SESSION['carrito'][$id_producto]['cantidad'] += $cantidad;
} else {
    // Si el producto no está, se agrega al carrito
    $_SESSION['carrito'][$id_producto] = [
        'id' => $id_producto,
        'nombre' => $producto['nombre'],
        'precio' => $producto['precio'],
        'cantidad' => $cantidad
    ];
}

// ==========================================================
// CÓDIGO NUEVO PARA SWEETALERT2
// 1. Imprimimos el HTML base y las librerías necesarias.
$nombre_producto = htmlspecialchars($producto['nombre']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <title>Producto Añadido</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
<script>
    // Mostrar el SweetAlert2
    Swal.fire({
        title: "¡Listo! 🌮",
        text: "<?php echo $nombre_producto; ?> agregado al carrito.",
        icon: "success",
        confirmButtonText: 'Continuar',
        confirmButtonColor: '#ea580c' // Botón color naranja
    }).then((result) => {
        // Redirigir al menú o a la página de tu elección una vez que el usuario haga clic en "Continuar"
        window.location='menu.php';
    });
</script>
</body>
</html>
<?php
// Detener la ejecución del script PHP después de imprimir el HTML
exit;
?>
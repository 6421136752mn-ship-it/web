<?php
// descargar_pedido.php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}
include "taqueriadb.php";

// 1. Incluir la librería dompdf
require_once 'dompdf/autoload.inc.php'; // ASEGÚRATE QUE LA RUTA SEA CORRECTA
use Dompdf\Dompdf;
use Dompdf\Options;

if (!isset($_GET['id'])) {
    die("ID de pedido no especificado.");
}
$id_pedido = intval($_GET['id']);

// 2. Obtener los datos del pedido 
$sql = "SELECT p.id, p.total, p.fecha, c.nombre, c.telefono, c.direccion 
         FROM pedidos p JOIN clientes c ON p.id_cliente = c.id WHERE p.id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id_pedido);
$stmt->execute();
$res = $stmt->get_result();
$pedido = $res->fetch_assoc();
$stmt->close();

if (!$pedido) {
    die("Pedido no encontrado.");
}

// Obtener detalles del pedido
$detalles = [];
$sql_det = "SELECT dp.cantidad, pr.nombre, pr.precio FROM detalles_pedidos dp JOIN productos pr ON dp.id_producto = pr.id WHERE dp.id_pedido = ?";
$stmt_det = $conn->prepare($sql_det);
$stmt_det->bind_param("i", $id_pedido);
$stmt_det->execute();
$res_det = $stmt_det->get_result();
while ($row = $res_det->fetch_assoc()) {
    $detalles[] = $row;
}
$stmt_det->close();

// Fallback para pedidos antiguos de un solo producto si no tienen detalle
if (empty($detalles) && isset($pedido['id_producto'])) {
     // Aquí faltaría la lógica para cargar el producto singular si el campo existe en la tabla pedidos
     // Por simplicidad, si no hay detalles, se mostrará solo el total. 
}

// 3. Crear el HTML para el PDF
$html = '
    <style>
        body { font-family: sans-serif; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .total { text-align: right; font-size: 1.2em; margin-top: 20px; }
    </style>
    <h1>Comprobante de Pedido Taquería 🌮</h1>
    <hr>
    <p><strong>Pedido ID:</strong> ' . $pedido['id'] . '</p>
    <p><strong>Cliente:</strong> ' . htmlspecialchars($pedido['nombre']) . '</p>
    <p><strong>Teléfono:</strong> ' . htmlspecialchars($pedido['telefono']) . '</p>
    <p><strong>Dirección:</strong> ' . htmlspecialchars($pedido['direccion']) . '</p>
    <p><strong>Fecha:</strong> ' . $pedido['fecha'] . '</p>
    
    <h2>Productos:</h2>
    <table>
        <thead>
            <tr>
                <th>Producto</th>
                <th>Cantidad</th>
                <th>Precio Unitario</th>
                <th>Subtotal</th>
            </tr>
        </thead>
        <tbody>';

foreach ($detalles as $item) {
    $subtotal = $item['precio'] * $item['cantidad'];
    $html .= '
        <tr>
            <td>' . htmlspecialchars($item['nombre']) . '</td>
            <td>' . $item['cantidad'] . '</td>
            <td>$ ' . number_format($item['precio'], 2) . '</td>
            <td>$ ' . number_format($subtotal, 2) . '</td>
        </tr>';
}

$html .= '
        </tbody>
    </table>
    <p class="total"><strong>TOTAL FINAL: $ ' . number_format($pedido['total'], 2) . '</strong></p>
';

// 4. Configurar y generar PDF
$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true);
$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

// 5. Enviar el PDF al navegador
$filename = "Pedido_" . $id_pedido . ".Descargar";
$dompdf->stream($filename, ["Attachment" => true]);
?>
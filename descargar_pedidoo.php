<?php
require 'dompdf/autoload.inc.php';
include "taqueriadb.php";

use Dompdf\Dompdf;

if (!isset($_GET['id'])) {
    die("ID inválido");
}

$id = intval($_GET['id']);

$sql = "SELECT p.id, c.nombre AS cliente, p.total, p.fecha,
               GROUP_CONCAT(pr.nombre SEPARATOR ', ') AS productos
        FROM pedidos p
        JOIN clientes c ON p.id_cliente = c.id
        JOIN detalle_pedido d ON p.id = d.id_pedido
        JOIN productos pr ON d.id_producto = pr.id
        WHERE p.id = $id
        GROUP BY p.id";

$data = $conn->query($sql)->fetch_assoc();

if (!$data) {
    die("Pedido no encontrado.");
}

$html = "
<h2 style='text-align:center;'>Reporte de Pedido</h2>
<p><strong>ID:</strong> {$data['id']}</p>
<p><strong>Cliente:</strong> {$data['cliente']}</p>
<p><strong>Productos:</strong> {$data['productos']}</p>
<p><strong>Total:</strong> $ {$data['total']}</p>
<p><strong>Fecha:</strong> {$data['fecha']}</p>
";

$dompdf = new Dompdf();
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();
$dompdf->stream("pedido_{$id}.pdf", ["Attachment" => true]);

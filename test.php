<?php
include "taqueriadb.php";

if ($conn->connect_error) {
    die("❌ Error de conexión: " . $conn->connect_error);
}
echo "✅ Conexión exitosa con la base de datos taqueria_db";
?>

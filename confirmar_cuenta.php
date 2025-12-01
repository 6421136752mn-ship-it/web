<?php
require "db.php";

if (!isset($_GET['token'])) {
    die("Token no válido.");
}

$token = $_GET['token'];

$stmt = $conn->prepare("SELECT id, expira_token FROM usuarios WHERE token = ?");
$stmt->bind_param("s", $token);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows == 0) {
    die("Token inválido o ya usado.");
}

$u = $res->fetch_assoc();

// Verificar expiración
if (strtotime($u["expira_token"]) < time()) {
    die("El enlace de verificación ha expirado. Regístrate nuevamente.");
}

// Activar cuenta
$upd = $conn->prepare("UPDATE usuarios SET token = NULL, expira_token = NULL WHERE id = ?");
$upd->bind_param("i", $u["id"]);
$upd->execute();

echo "<script>
alert('Cuenta confirmada. Ya puedes iniciar sesión.');
window.location='login.php';
</script>";
?>

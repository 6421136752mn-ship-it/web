<<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Restablecer contraseña</title>
</head>
<body>

<h2>Restablecer contraseña</h2>

<?php
if (isset($_GET['error'])) {
    echo "<p style='color:red'>" . htmlspecialchars($_GET['error']) . "</p>";
}
if (isset($_GET['success'])) {
    echo "<p style='color:green'>" . htmlspecialchars($_GET['success']) . "</p>";
}
?>

<form action="send_reset.php" method="POST">
    <input type="email" name="correo" placeholder="Ingresa tu correo" required>
    <button type="submit">Enviar enlace</button>
</form>

</body>
</html>

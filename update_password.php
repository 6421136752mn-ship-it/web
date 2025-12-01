<?php
require "taqueriadb.php";

// 1. Validar token
if (!isset($_POST['token']) || empty($_POST['token'])) {
    die("Token no válido.");
}

$token = $_POST['token'];

// 2. Validar contraseña
if (!isset($_POST['password']) || empty($_POST['password'])) {
    die("Debes ingresar una contraseña nueva.");
}

$nueva = password_hash($_POST['password'], PASSWORD_DEFAULT);

// 3. Consultar usuario
$sql = "
    SELECT id 
    FROM usuarios 
    WHERE reset_token = ? AND reset_token_expire > NOW()
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $token);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    die("Token inválido o expirado.");
}

$usuario = $result->fetch_assoc();
$id = $usuario['id'];

// 4. Actualizar contraseña
$update = "
    UPDATE usuarios
    SET contrasena = ?, reset_token = NULL, reset_token_expire = NULL
    WHERE id = ?
";

$stmt2 = $conn->prepare($update);
$stmt2->bind_param("si", $nueva, $id);
$stmt2->execute();

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Contraseña Actualizada</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-br from-orange-100 to-red-100 min-h-screen flex items-center justify-center">
    <div class="bg-white p-8 rounded-xl shadow-2xl w-full max-w-md text-center">
        <div class="mb-6 bg-green-100 p-4 rounded-lg text-green-700">
            <h2 class="text-2xl font-bold">✔ Contraseña Actualizada Correctamente</h2>
            <p class="mt-2">Ahora puedes iniciar sesión con tu nueva contraseña.</p>
        </div>
        
        <a href="login.php" class="block w-full bg-orange-500 text-white py-3 rounded-lg font-semibold hover:bg-orange-600 transition">Volver a Iniciar Sesión</a>
    </div>
</body>
</html>
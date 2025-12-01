<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require "db.php";

function passwordValida($pass) {
    return preg_match('/[A-Z]/', $pass) &&   // mayúscula
           preg_match('/[0-9]/', $pass) &&   // número
           preg_match('/[\W]/', $pass) &&    // símbolo
           strlen($pass) >= 8;               // mínimo 8
}

if (!isset($_GET['token'])) {
    die("Token inválido.");
}

$token = $_GET['token'];

$consulta = $conn->prepare("SELECT id, reset_token_expire FROM usuarios WHERE reset_token = ?");
$consulta->bind_param("s", $token);
$consulta->execute();
$res = $consulta->get_result();

if ($res->num_rows === 0) {
    die("Token no válido.");
}

$usuario = $res->fetch_assoc();

if (strtotime($usuario["reset_token_expire"]) < time()) {
    die("El enlace para restablecer la contraseña ha expirado.");
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $pass = $_POST["password"] ?? "";

    if (!passwordValida($pass)) {
        echo "<script>alert('La contraseña NO cumple los requisitos (mayúscula, número, símbolo y mínimo 8 caracteres).');</script>";
    } else {

        $nuevaHash = password_hash($pass, PASSWORD_DEFAULT);

        $update = $conn->prepare("UPDATE usuarios SET contrasena=?, reset_token=NULL, reset_token_expire=NULL WHERE id=?");
        $update->bind_param("si", $nuevaHash, $usuario["id"]);
        $update->execute();

        echo "<script>
            alert('Tu contraseña ha sido actualizada correctamente.');
            window.location='login.php';
        </script>";
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Restablecer tu Contraseña</title>
<script src="https://cdn.tailwindcss.com"></script>
<script src="https://kit.fontawesome.com/a2d9d5a64b.js" crossorigin="anonymous"></script>
</head>

<body class="bg-gradient-to-br from-orange-100 to-orange-200 flex justify-center items-center h-screen">

<div class="bg-white p-8 rounded-xl shadow-xl w-96">

    <h2 class="text-2xl font-bold text-center text-orange-600 mb-4">
        Restablecer tu Contraseña 🌮
    </h2>

    <form action="" method="POST">

        <!-- CONTENEDOR DEL OJO -->
        <div class="relative mb-3">
            <input type="password" id="newpass" name="password" placeholder="Nueva contraseña"
                class="w-full p-3 pr-12 border rounded" required>

            <!-- ÍCONO OJO -->
            <i id="togglePass" 
                class="fa-solid fa-eye absolute right-4 top-1/2 -translate-y-1/2 text-gray-500 cursor-pointer text-lg">
            </i>
        </div>

        <!-- LISTA DE REGLAS -->
        <ul class="text-xs text-gray-600 mb-4">
            <li>• Al menos 1 mayúscula</li>
            <li>• Al menos 1 número</li>
            <li>• Al menos 1 símbolo</li>
            <li>• Mínimo 8 caracteres</li>
        </ul>

        <button type="submit"
            class="w-full bg-orange-500 text-white py-2 rounded hover:bg-orange-600 duration-200">
            Actualizar Contraseña
        </button>

    </form>

    <p class="text-center text-sm mt-4">
        <a href="login.php" class="text-orange-600">Cancelar y volver a iniciar sesión</a>
    </p>

</div>

<!-- JS DEL OJO -->
<script>
document.getElementById("togglePass").addEventListener("click", function() {
    const input = document.getElementById("newpass");
    const isPassword = input.type === "password";
    input.type = isPassword ? "text" : "password";

    this.classList.toggle("fa-eye");
    this.classList.toggle("fa-eye-slash");
});
</script>

</body>
</html>

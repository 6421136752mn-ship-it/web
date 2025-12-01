<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require "db.php";
require __DIR__ . "/PHPMailer/src/Exception.php";
require __DIR__ . "/PHPMailer/src/PHPMailer.php";
require __DIR__ . "/PHPMailer/src/SMTP.php";

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function passwordValida($pass) {
    return preg_match('/[A-Z]/', $pass) &&
           preg_match('/[0-9]/', $pass) &&
           preg_match('/[\W]/', $pass) &&
           strlen($pass) >= 8;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $nombre = trim($_POST["nombre"] ?? "");
    $correo = trim($_POST["correo"] ?? "");
    $pass_raw = $_POST["contrasena"] ?? "";

    if ($nombre === "" || $correo === "" || $pass_raw === "") {
        echo "<script>alert('Completa todos los campos');</script>";
    } 
    else if (!passwordValida($pass_raw)) {
        echo "<script>alert('La contraseña no cumple los requisitos de seguridad.');</script>";
    }
    else {

        $verificar = $conn->prepare("SELECT id FROM usuarios WHERE correo = ?");
        $verificar->bind_param("s", $correo);
        $verificar->execute();
        $res = $verificar->get_result();

        if ($res->num_rows > 0) {
            echo "<script>alert('El correo ya está registrado.');</script>";
        } else {

            $contrasena = password_hash($pass_raw, PASSWORD_DEFAULT);
            $token = bin2hex(random_bytes(32));
            $expira = date("Y-m-d H:i:s", time() + 3600);

            $query = $conn->prepare("INSERT INTO usuarios (nombre, correo, contrasena, token, expira_token) VALUES (?, ?, ?, ?, ?)");
            $query->bind_param("sssss", $nombre, $correo, $contrasena, $token, $expira);

            if ($query->execute()) {

                try {
                    $mail = new PHPMailer(true);
                    $mail->isSMTP();
                    $mail->Host = 'smtp.gmail.com';
                    $mail->SMTPAuth = true;
                    $mail->Username = 'psoto2687@gmail.com';
                    $mail->Password = 'zyaf iazg xyol hnte';
                    $mail->SMTPSecure = 'tls';
                    $mail->Port = 587;

                    $mail->setFrom('psoto2687@gmail.com', 'Taquería');
                    $mail->addAddress($correo);

                    $mail->isHTML(true);
                    $mail->Subject = "Confirma tu cuenta - Taquería";

                    $link = "http://localhost/taqueria/confirmar_cuenta.php?token=$token";

                    $mail->Body = "
                        <h2>Hola $nombre</h2>
                        <p>Para activar tu cuenta haz clic en el siguiente enlace:</p>
                        <a href='$link'>Confirmar Cuenta</a><br><br>
                        <small>Este enlace expira en 1 hora</small>
                    ";

                    $mail->send();

                    echo "<script>
                        alert('Te enviamos un correo de verificación.');
                        window.location='login.php';
                    </script>";

                } catch (Exception $e) {
                    $del = $conn->prepare("DELETE FROM usuarios WHERE correo = ?");
                    $del->bind_param("s", $correo);
                    $del->execute();
                    die('Error enviando correo: ' . $mail->ErrorInfo);
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Crear cuenta</title>

<!-- TAILWIND -->
<script src="https://cdn.tailwindcss.com"></script>

<!-- FONT AWESOME OFICIAL -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

</head>

<body class="bg-gradient-to-br from-orange-100 to-orange-200 flex justify-center items-center h-screen">

<div class="bg-white p-8 rounded-xl shadow-xl w-96">

    <h2 class="text-2xl font-bold text-center text-orange-600 mb-4">Crear Cuenta 🌮</h2>

    <form action="" method="POST">

        <input type="text" name="nombre" placeholder="Nombre completo" 
            class="w-full p-3 border rounded mb-3" required>

        <input type="email" name="correo" placeholder="Correo electrónico" 
            class="w-full p-3 border rounded mb-3" required>

        <!-- INPUT CON OJO -->
        <div class="relative mb-3">
            <input type="password" id="pass" name="contrasena" placeholder="Contraseña"
                class="w-full p-3 pr-12 border rounded" required>

            <i id="togglePass"
               class="fa-solid fa-eye absolute right-4 top-1/2 -translate-y-1/2 text-gray-600 cursor-pointer text-xl"></i>
        </div>

        <ul class="text-xs text-gray-600 mb-4">
            <li>• Al menos 1 mayúscula</li>
            <li>• Al menos 1 número</li>
            <li>• Al menos 1 símbolo</li>
            <li>• Mínimo 8 caracteres</li>
        </ul>

        <button type="submit" 
            class="w-full bg-orange-500 text-white py-2 rounded hover:bg-orange-600 duration-200">
            Registrarme
        </button>

    </form>

    <p class="text-center text-sm mt-4">
        ¿Ya tienes cuenta? <a href="login.php" class="text-orange-600">Iniciar sesión</a>
    </p>
</div>

<!-- SCRIPT DEL OJO -->
<script>
document.getElementById("togglePass").addEventListener("click", function() {
    const input = document.getElementById("pass");
    const isPassword = input.type === "password";
    input.type = isPassword ? "text" : "password";

    this.classList.toggle("fa-eye");
    this.classList.toggle("fa-eye-slash");
});
</script>

</body>
</html>

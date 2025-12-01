<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require "db.php";

// Cargar PHPMailer
require __DIR__ . "/PHPMailer/src/Exception.php";
require __DIR__ . "/PHPMailer/src/PHPMailer.php";
require __DIR__ . "/PHPMailer/src/SMTP.php";

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// 1. Validar correo
if (!isset($_POST['correo']) || empty(trim($_POST['correo']))) {
    header("Location: reset_request.php?error=Debes ingresar un correo");
    exit;
}

$correo = trim($_POST['correo']);

// 2. Verificar si existe
$stmt = $conn->prepare("SELECT id FROM usuarios WHERE correo = ?");
$stmt->bind_param("s", $correo);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    header("Location: reset_request.php?error=Ese correo no existe");
    exit;
}

// 3. Generar token y expiración
$token  = bin2hex(random_bytes(32));
$expira = date("Y-m-d H:i:s", strtotime("+1 hour"));

// 4. Guardar token
$stmt2 = $conn->prepare("
    UPDATE usuarios 
    SET reset_token = ?, reset_token_expire = ?
    WHERE correo = ?
");
$stmt2->bind_param("sss", $token, $expira, $correo);
$stmt2->execute();

// 5. Enlace de reseteo real
$enlace = "http://localhost/taqueria/reset_password.php?token=" . $token;

// 6. Enviar correo
$mail = new PHPMailer(true);

try {
    $mail->isSMTP();
    $mail->Host       = "smtp.gmail.com";
    $mail->SMTPAuth   = true;
    $mail->Username   = "psoto2687@gmail.com";
    $mail->Password   = "zyafiazgxyolhnte"; 
    $mail->SMTPSecure = "tls";
    $mail->Port       = 587;

    // OBLIGATORIO: usar el mismo correo
    $mail->setFrom("psoto2687@gmail.com", "Soporte Taquería");
    $mail->addAddress($correo);

    $mail->isHTML(true);
    $mail->Subject = "Restablecer contraseña";
    $mail->Body = "
        <h3>Restablecimiento de contraseña</h3>
        <p>Haz clic en el siguiente enlace para restablecer tu contraseña:</p>
        <p><a href='$enlace'>$enlace</a></p>
        <p>El enlace expira en 1 hora.</p>
    ";

    $mail->send();

    header("Location: reset_request.php?success=Se envió un enlace a tu correo.");
    exit;

} catch (Exception $e) {
    die("Error enviando correo:<br><br>" . $mail->ErrorInfo);
}

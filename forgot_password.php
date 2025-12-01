<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Recuperar Contraseña</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-br from-orange-100 to-red-100 min-h-screen flex items-center justify-center">
    <div class="bg-white p-8 rounded-xl shadow-2xl w-full max-w-md text-center">
        <h2 class="text-2xl font-bold text-orange-600 mb-6">¿Olvidaste tu Contraseña? 🌮</h2>
        
        <?php
        // Incluir conexión BD
        require "taqueriadb.php";
        
        // Cargar PHPMailer
        require __DIR__ . "/PHPMailer/src/Exception.php";
        require __DIR__ . "/PHPMailer/src/PHPMailer.php";
        require __DIR__ . "/PHPMailer/src/SMTP.php";
        
        use PHPMailer\PHPMailer\PHPMailer;
        use PHPMailer\PHPMailer\Exception;
        
        $error = '';
        $success = '';
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // 1. Validar correo
            if (!isset($_POST['correo']) || empty(trim($_POST['correo']))) {
                $error = "Debes ingresar un correo";
            } else {
                $correo = trim($_POST['correo']);
                
                // 2. Verificar si existe
                $stmt = $conn->prepare("SELECT id FROM usuarios WHERE correo = ?");
                $stmt->bind_param("s", $correo);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows == 0) {
                    $error = "Ese correo no existe";
                } else {
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
                        
                        $success = "Se envió un enlace a tu correo. Revisa tu bandeja de entrada (o spam).";
                        
                    } catch (Exception $e) {
                        $error = "Error enviando correo: " . $mail->ErrorInfo;
                    }
                }
            }
        }
        ?>
        
        <?php if ($error): ?>
            <div class="mb-4 text-red-600 bg-red-100 p-3 rounded-lg"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="mb-4 text-green-600 bg-green-100 p-3 rounded-lg"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        
        <form action="" method="POST" class="space-y-4">
            <input type="email" name="correo" placeholder="Ingresa tu correo" required class="w-full p-3 border border-orange-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500">
            <button type="submit" class="w-full bg-orange-500 text-white py-3 rounded-lg font-semibold hover:bg-orange-600 transition">Enviar Enlace de Recuperación</button>
        </form>
        
        <nav class="mt-6 text-center space-x-4">
            <a href="login.php" class="text-orange-600 hover:underline">Volver a Iniciar Sesión</a>
            <a href="registro.php" class="text-orange-600 hover:underline">Crear Cuenta</a>
        </nav>
    </div>
</body>
</html>
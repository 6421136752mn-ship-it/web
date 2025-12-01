<?php
session_start();
include(__DIR__ . "/taqueriadb.php");

if (isset($_SESSION['usuario_id'])) {
    header("Location: menu.php");
    exit;
}

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $correo = trim($_POST['correo'] ?? '');
    $contrasena = $_POST['contrasena'] ?? '';

    if ($correo === "" || $contrasena === "") {
        $error = "Completa correo y contraseña.";
    } else {
        $stmt = $conn->prepare("SELECT id, nombre, contrasena FROM usuarios WHERE correo = ?");
        if ($stmt) {
            $stmt->bind_param("s", $correo);
            $stmt->execute();
            $res = $stmt->get_result();

            if ($res && $res->num_rows === 1) {
                $u = $res->fetch_assoc();
                if (password_verify($contrasena, $u['contrasena']) || $contrasena === $u['contrasena']) {
                    $_SESSION['usuario_id'] = $u['id'];
                    $_SESSION['usuario_nombre'] = $u['nombre'];
                    header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
                    header("Pragma: no-cache");
                    header("Location: menu.php");
                    exit;
                } else {
                    $error = "Contraseña incorrecta.";
                }
            } else {
                $error = "No existe una cuenta con ese correo.";
            }
            $stmt->close();
        } else {
            $error = "Error interno al preparar la consulta.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión | El Buen Sabor</title>
    <style>
        body {
            margin: 0;
            font-family: 'Segoe UI', sans-serif;
            background: url('Fondo.jpg') no-repeat center center fixed;
            background-size: cover;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            color: #fff;
        }

        .login-container {
            background-color: rgba(0, 0, 0, 0.75);
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 0 20px rgba(255, 255, 255, 0.2);
            width: 350px;
            text-align: center;
        }

        h2 {
            color: #facc15;
            margin-bottom: 20px;
            font-size: 28px;
        }

        input[type="email"], input[type="password"], input[type="text"] {
            width: 100%;
            padding: 10px 45px 10px 10px;
            margin: 10px 0;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            box-sizing: border-box;
        }

        input[type="submit"] {
            width: 95%;
            background-color: #facc15;
            color: #000;
            cursor: pointer;
            font-weight: bold;
            transition: background 0.3s;
            padding: 10px;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            margin-top: 10px;
        }

        input[type="submit"]:hover {
            background-color: #eab308;
        }

        a {
            color: #facc15;
            text-decoration: none;
        }

        a:hover {
            text-decoration: underline;
        }

        .error {
            background-color: rgba(255, 0, 0, 0.2);
            padding: 10px;
            border-radius: 10px;
            margin-top: 10px;
        }

        .password-wrapper {
            position: relative;
            width: 100%;
        }

        .toggle-pass {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: white;
            font-size: 22px;
            user-select: none;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h2>Bienvenido a El Buen Sabor</h2>

        <form method="POST" action="">
            <input type="email" name="correo" placeholder="Correo electrónico" required>

            <div class="password-wrapper">
                <input type="password" id="contrasena" name="contrasena" placeholder="Contraseña" required>
                <span class="toggle-pass" onclick="togglePassword()">👁️‍🗨️</span>
            </div>

            <input type="submit" value="Iniciar sesión">
        </form>

        <p><a href="registro.php">¿No tienes cuenta? Regístrate</a></p>
        <a href="forgot_password.php">¿Olvidaste tu contraseña?</a>


        <?php if ($error): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
    </div>

    <script>
        function togglePassword() {
            const input = document.getElementById("contrasena");
            input.type = input.type === "password" ? "text" : "password";
        }
    </script>
</body>
</html>


<?php
session_start();
// Redirigir si el usuario no ha iniciado sesión
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}

include "taqueriadb.php";

$usuario_id = $_SESSION['usuario_id'];
$alerta_mensaje = null;
$alerta_tipo = null;
$ruta_base_fotos = 'uploads/perfiles/'; // Directorio donde se guardarán las fotos

// Crear el directorio si no existe
if (!is_dir($ruta_base_fotos)) {
    // Intentar crear la carpeta con permisos 0777
    mkdir($ruta_base_fotos, 0777, true); 
}

// ==========================================================
// 1. Lógica para ACTUALIZAR el Perfil (POST)
// ==========================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guardar_perfil'])) {
    $nuevo_nombre = trim($_POST['nombre'] ?? '');
    $nuevo_correo = trim($_POST['correo'] ?? '');
    $nueva_contrasena = $_POST['contrasena'] ?? '';
    $campos_actualizados = [];
    $tipos = '';
    $valores = [];
    $error_subida = false;

    // Obtener datos actuales del usuario para comparación
    $stmt_old = $conn->prepare("SELECT nombre, correo, foto_perfil FROM usuarios WHERE id = ?");
    $stmt_old->bind_param("i", $usuario_id);
    $stmt_old->execute();
    $old_data = $stmt_old->get_result()->fetch_assoc();
    $stmt_old->close();

    // --- MANEJO DE FOTO DE PERFIL ---
    if (isset($_FILES['foto_perfil']) && $_FILES['foto_perfil']['error'] === UPLOAD_ERR_OK) {
        $file_tmp = $_FILES['foto_perfil']['tmp_name'];
        $file_name = $_FILES['foto_perfil']['name'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        $extensiones_permitidas = ['jpg', 'jpeg', 'png', 'gif'];

        if (!in_array($file_ext, $extensiones_permitidas)) {
            $alerta_mensaje = "Solo se permiten archivos JPG, JPEG, PNG y GIF para la foto de perfil.";
            $alerta_tipo = 'error';
            $error_subida = true;
        } else {
            // Nombre único: ID de usuario + marca de tiempo
            $nombre_archivo_final = $usuario_id . '_' . time() . '.' . $file_ext;
            $ruta_completa = $ruta_base_fotos . $nombre_archivo_final;

            if (move_uploaded_file($file_tmp, $ruta_completa)) {
                // Si ya tenía una foto, la eliminamos (opcional)
                if (!empty($old_data['foto_perfil']) && file_exists($old_data['foto_perfil'])) {
                    unlink($old_data['foto_perfil']);
                }
                
                $campos_actualizados[] = "foto_perfil = ?";
                $tipos .= 's';
                $valores[] = $ruta_completa;
            } else {
                $alerta_mensaje = "Error al mover el archivo subido.";
                $alerta_tipo = 'error';
                $error_subida = true;
            }
        }
    }


    // --- 1. Nombre ---
    if (!empty($nuevo_nombre) && $nuevo_nombre !== $old_data['nombre']) {
        $campos_actualizados[] = "nombre = ?";
        $tipos .= 's';
        $valores[] = $nuevo_nombre;
        $_SESSION['usuario_nombre'] = $nuevo_nombre;
    }

    // --- 2. Correo ---
    if (!empty($nuevo_correo) && $nuevo_correo !== $old_data['correo']) {
        $stmt_check = $conn->prepare("SELECT id FROM usuarios WHERE correo = ? AND id != ?");
        $stmt_check->bind_param("si", $nuevo_correo, $usuario_id);
        $stmt_check->execute();
        if ($stmt_check->get_result()->num_rows > 0) {
            $alerta_mensaje = "El correo electrónico ya está registrado por otro usuario.";
            $alerta_tipo = 'error';
        } else {
            $campos_actualizados[] = "correo = ?";
            $tipos .= 's';
            $valores[] = $nuevo_correo;
            $_SESSION['usuario_correo'] = $nuevo_correo;
        }
        $stmt_check->close();
    }
    
    // --- 3. Contraseña ---
    if (!empty($nueva_contrasena)) {
        $hash_contrasena = password_hash($nueva_contrasena, PASSWORD_DEFAULT);
        $campos_actualizados[] = "contrasena = ?";
        $tipos .= 's';
        $valores[] = $hash_contrasena;
    }

    // --- Ejecución de la Actualización ---
    if (!empty($campos_actualizados) && $alerta_tipo !== 'error' && !$error_subida) {
        $sql = "UPDATE usuarios SET " . implode(", ", $campos_actualizados) . " WHERE id = ?";
        $tipos .= 'i';
        $valores[] = $usuario_id;
        
        $stmt_update = $conn->prepare($sql);
        
        // Ejecutar bind_param dinámicamente
        $stmt_update->bind_param($tipos, ...$valores);
        
        if ($stmt_update->execute()) {
            $alerta_mensaje = "¡Tu perfil se ha actualizado correctamente!";
            $alerta_tipo = 'success';
        } else {
            $alerta_mensaje = "Error al actualizar el perfil: " . $conn->error;
            $alerta_tipo = 'error';
        }
        $stmt_update->close();
    } elseif (empty($campos_actualizados) && $alerta_tipo !== 'error' && !$error_subida) {
         $alerta_mensaje = "No se detectaron cambios para guardar.";
         $alerta_tipo = 'info';
    }
}

// ==========================================================
// 2. Obtener datos actuales del usuario (incluyendo foto_perfil)
// ==========================================================
$sql_user = "SELECT nombre, correo, foto_perfil FROM usuarios WHERE id = ?";
$stmt_user = $conn->prepare($sql_user);
$stmt_user->bind_param("i", $usuario_id);
$stmt_user->execute();
$result_user = $stmt_user->get_result();
$usuario = $result_user->fetch_assoc();
$stmt_user->close();

if (!$usuario) {
    session_destroy();
    header("Location: login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Perfil de Usuario</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <style>
        .foto-perfil {
            width: 150px;
            height: 150px;
            object-fit: cover;
            border-radius: 50%;
            border: 4px solid #f97316; /* Naranja de Tailwind (orange-500) */
        }
        /* Oculta la entrada de archivo original */
        .input-file-hidden {
            display: none;
        }
    </style>
</head>

<body class="min-h-screen p-6 bg-cover bg-center" style="background-image: url('img/Fondo para Pedidos.jpg');">
    
    <header class="bg-orange-600 text-white p-4 rounded-lg shadow-md mb-6">
        <nav class="flex justify-between items-center">
            <a href="menu.php" class="hover:underline">Volver al Menú</a>
            <div class="flex items-center space-x-4">
                <span class="font-bold">Perfil: <?= htmlspecialchars($usuario['nombre']) ?></span>
                <a href="logout.php" class="bg-red-500 px-4 py-2 rounded-lg hover:bg-red-600">Cerrar sesión</a>
            </div>
        </nav>
    </header>

    <main class="bg-white p-8 rounded-xl shadow-2xl max-w-lg mx-auto">
        <h2 class="text-3xl font-bold text-orange-600 mb-6 text-center">
            Configuración de Perfil
        </h2>
        
        <form method="POST" enctype="multipart/form-data" class="space-y-6">
            
            <div class="text-center mb-8 relative w-40 mx-auto">
                
                <img src="<?= !empty($usuario['foto_perfil']) && file_exists($usuario['foto_perfil']) ? htmlspecialchars($usuario['foto_perfil']) : 'img/default_user.png' ?>" 
                     alt="" 
                     class="foto-perfil mx-auto shadow-md">
                
                <input type="file" id="foto_perfil" name="foto_perfil" 
                       accept="image/*"
                       class="input-file-hidden">

                <label for="foto_perfil" 
                       class="absolute bottom-0 right-0 p-2 bg-orange-600 rounded-full cursor-pointer hover:bg-orange-700 transition transform hover:scale-110 shadow-lg">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-6 h-6 text-white">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.827 6.175A2.31 2.31 0 0 1 5.186 7.23c-.38.054-.757.112-1.134.175M4.965 20.003h12.553C17.755 20.003 19 18.758 19 17.15c0-2.486-2.5-4.48-5-4.48S9 14.667 9 17.15c0 1.594 1.245 2.848 2.529 2.853L12 20.003M6.5 12h11M7.5 15h9M12 9h.01M9 12.5v.01" />
                    </svg>
                </label>
            </div>
            <div>
                <label for="nombre" class="block text-sm font-medium text-gray-700">Nombre de Usuario</label>
                <input type="text" id="nombre" name="nombre" 
                       value="<?= htmlspecialchars($usuario['nombre']) ?>" 
                       class="mt-1 w-full p-3 border border-orange-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500" required>
            </div>

            <div>
                <label for="correo" class="block text-sm font-medium text-gray-700">Correo Electrónico</label>
                <input type="email" id="correo" name="correo" 
                       value="<?= htmlspecialchars($usuario['correo']) ?>" 
                       class="mt-1 w-full p-3 border border-orange-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500" required>
            </div>

            <div>
                <label for="contrasena" class="block text-sm font-medium text-gray-700">Nueva Contraseña</label>
                <div class="relative mt-1">
                    <input type="password" id="contrasena" name="contrasena" 
                           placeholder="********" 
                           class="w-full p-3 border border-orange-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500 pr-12">
                    
                    <button type="button" id="togglePassword" 
                            class="absolute inset-y-0 right-0 flex items-center px-4 text-gray-600 hover:text-orange-500">
                         <svg id="eye-open" xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="display: none;">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                         </svg>
                         <svg id="eye-closed" xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.024 10.024 0 0112 19c-4.478 0-8.268-2.943-9.542-7 1.274-4.057 5.064-7 9.542-7a9.96 9.96 0 013.875.825M17.5 14.5a2.5 2.5 0 00-5 0M10.75 10.75L19 19" />
                         </svg>
                    </button>
                </div>
                <p class="mt-1 text-xs text-gray-500">Dejar vacío para no cambiar. Si deseas cambiarla, ingresa la nueva contraseña aquí.</p>
            </div>

            <button type="submit" name="guardar_perfil"
                    class="w-full bg-orange-500 text-white py-3 rounded-lg font-semibold hover:bg-orange-600 transition">
                Guardar Cambios
            </button>
        </form>

    </main>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    // --- Lógica de SweetAlert2 ---
    <?php if ($alerta_mensaje): ?>
    Swal.fire({
        title: "<?php echo ($alerta_tipo === 'success') ? '¡Actualizado! ✅' : (($alerta_tipo === 'error') ? 'Error 😢' : 'Info ℹ️'); ?>",
        text: "<?php echo htmlspecialchars($alerta_mensaje); ?>",
        icon: "<?php echo $alerta_tipo; ?>",
        confirmButtonColor: '#ea580c'
    });
    <?php endif; ?>

    // --- Lógica de Mostrar/Ocultar Contraseña ---
    document.getElementById('togglePassword').addEventListener('click', function (e) {
        const passwordInput = document.getElementById('contrasena');
        const eyeOpen = document.getElementById('eye-open');
        const eyeClosed = document.getElementById('eye-closed');

        // Alternar el atributo type (text/password)
        const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
        passwordInput.setAttribute('type', type);

        // Alternar íconos
        if (type === 'text') {
            eyeOpen.style.display = 'block';
            eyeClosed.style.display = 'none';
        } else {
            eyeOpen.style.display = 'none';
            eyeClosed.style.display = 'block';
        }
    });
</script>
</body>
</html>
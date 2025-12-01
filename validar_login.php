<?php
// validar_login.php - Versión Segura y Hash
session_start();
include "taqueriadb.php";

// Obtener datos del formulario
// Se asume que el input de contraseña en login.php tiene name="contrasena"
$correo = $_POST['correo'] ?? '';
$contrasena = $_POST['contrasena'] ?? ''; 

if (empty($correo) || empty($contrasena)) {
    echo "<script>alert('Por favor, completa ambos campos.'); window.location='login.php';</script>";
    exit();
}

// Buscar el usuario de forma segura con consulta preparada
$stmt = $conn->prepare("SELECT id, nombre, contrasena, rol FROM usuarios WHERE correo = ?");
$stmt->bind_param("s", $correo);
$stmt->execute();
$resultado = $stmt->get_result();

if ($resultado && $resultado->num_rows === 1) {
    $usuario = $resultado->fetch_assoc();
    
    // Comparar contraseñas usando el hash
    if (password_verify($contrasena, $usuario['contrasena'])) { 
        // Crear sesión
        $_SESSION['usuario_id'] = $usuario['id'];
        $_SESSION['usuario_nombre'] = $usuario['nombre'];
        $_SESSION['usuario_rol'] = $usuario['rol'];

        // Redirigir según el rol
        if ($usuario['rol'] === 'admin') {
            header("Location: admin.php");
        } else {
            header("Location: menu.php"); 
        }
        exit();
    } else {
        echo "<script>alert('Contraseña incorrecta'); window.location='login.php';</script>";
    }
} else {
    echo "<script>alert('Usuario no encontrado'); window.location='login.php';</script>";
}

$stmt->close();
$conn->close();
?>
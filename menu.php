<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
include "taqueriadb.php";

$result = $conn->query("SELECT * FROM productos");

// 🔸 Productos adicionales manuales (Tacos + Bebidas)
$extras = [
    [
        "nombre" => "Taco de Suadero",
        "descripcion" => "Taco jugoso de suadero doradito.",
        "precio" => 18.00,
        "imagen" => "img/Taco de Suadero.webp"
    ],
    [
        "nombre" => "Taco de Carnitas",
        "descripcion" => "Carnitas suaves estilo Michoacán.",
        "precio" => 18.00,
        "imagen" => "img/Taco de Carnitas.webp"
    ],
    [
        "nombre" => "Taco de Cabeza",
        "descripcion" => "Taco suavecito de cabeza cocida.",
        "precio" => 18.00,
        "imagen" => "img/Taco de Cabeza.webp"
    ],
    [
        "nombre" => "Taco de Tripa",
        "descripcion" => "Tripa doradita y crujiente.",
        "precio" => 20.00,
        "imagen" => "img/Taco de tripa.jpg"
    ],

    // <-- Productos que faltaban integrados -->
    [
        "nombre" => "Taco de Birria",
        "descripcion" => "Taco de birria jugosa estilo Jalisco.",
        "precio" => 20.00,
        "imagen" => "img/Taco de Birria.webp"
    ],
    [
        "nombre" => "Taco de Barbacoa",
        "descripcion" => "Barbacoa suave estilo tradicional.",
        "precio" => 20.00,
        "imagen" => "img/Taco de Barbacoa.webp"
    ],

    [
        "nombre" => "Agua de Jamaica",
        "descripcion" => "Agua fresca de jamaica natural.",
        "precio" => 18.00,
        "imagen" => "img/Agua de Jamaica.webp"
    ],
    [
        "nombre" => "Agua de Limón",
        "descripcion" => "Limonada fresca natural.",
        "precio" => 18.00,
        "imagen" => "img/Agua de limon.webp"
    ],
    [
        "nombre" => "Agua de Tamarindo",
        "descripcion" => "Agua fresca natural de tamarindo.",
        "precio" => 18.00,
        "imagen" => "img/Agua de Tamarindo.webp"
    ],
    [
        "nombre" => "Coca-Cola",
        "descripcion" => "Envase 600ml",
        "precio" => 20.00,
        "imagen" => "img/Coca cola.png"
    ],
    [
        "nombre" => "Sprite",
        "descripcion" => "Refresco Sprite 600ml.",
        "precio" => 20.00,
        "imagen" => "img/Sprite.webp"
    ]
];


// 🔥 ELIMINAR PRODUCTOS REPETIDOS (Comparación por nombre)
$db_names = [];
$productos_db = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $lower_name = strtolower(trim($row['nombre']));
        if (!in_array($lower_name, $db_names)) {  // Evitar duplicados en BD si hay
            $db_names[] = $lower_name;
            $productos_db[] = $row;
        }
    }
}

// Insertar extras en BD si no existen y obtener sus IDs
$all_productos = $productos_db;  // Empezar con los de BD
foreach ($extras as $extra) {
    $lower_name = strtolower(trim($extra['nombre']));
    if (!in_array($lower_name, $db_names)) {
        // Insertar en BD
        $stmt = $conn->prepare("INSERT INTO productos (nombre, descripcion, precio) VALUES (?, ?, ?)");
        $stmt->bind_param("ssd", $extra['nombre'], $extra['descripcion'], $extra['precio']);
        $stmt->execute();
        $extra['id'] = $stmt->insert_id;  // Obtener ID recién insertado
        $stmt->close();

        $all_productos[] = $extra;
        $db_names[] = $lower_name;  // Agregar a lista para evitar futuros duplicados
    }
}

// Clasificar en tacos/comida y bebidas basados en keywords
$tacos = [];
$bebidas = [];
$bebida_keywords = ['agua', 'refresco', 'coca', 'sprite', 'horchata', 'jamaica', 'limón', 'limon', 'tamarindo'];  // Keywords para bebidas

foreach ($all_productos as $row) {
    $nombre_lower = strtolower($row['nombre']);
    // si la fila de BD trae imagen (por ejemplo añadida manualmente a BD), la usamos; si no, fallback.
    $imagen = isset($row['imagen']) && $row['imagen'] !== '' ? $row['imagen'] : 'img/Fondo.jpg';

    // Asignar imagen basada en nombre si no tiene (como en código original)
    if (!isset($row['imagen']) || $row['imagen'] === '') {
        if (strpos($nombre_lower, 'pastor') !== false) $imagen = 'img/Taco al Pastor.webp';
        elseif (strpos($nombre_lower, 'asada') !== false) $imagen = 'img/Taco de Asada.jpg';
        elseif (strpos($nombre_lower, 'refresco') !== false) $imagen = 'img/Refresco.webp';
        elseif (strpos($nombre_lower, 'horchata') !== false) $imagen = 'img/Agua de Horchata.webp';
        elseif (strpos($nombre_lower, 'quesadilla') !== false) $imagen = 'img/Quesadilla.jpg';
        elseif (strpos($nombre_lower, 'suadero') !== false) $imagen = 'img/Taco de Suadero.webp';
        elseif (strpos($nombre_lower, 'carnitas') !== false) $imagen = 'img/Taco de carnita.webp';
        elseif (strpos($nombre_lower, 'cabeza') !== false) $imagen = 'img/Taco de Cabeza.webp';
        elseif (strpos($nombre_lower, 'tripa') !== false) $imagen = 'img/Taco de tripa.jpg';
        elseif (strpos($nombre_lower, 'birria') !== false) $imagen = 'img/Taco de Birria.webp';
        elseif (strpos($nombre_lower, 'barbacoa') !== false) $imagen = 'img/Taco de barbacoa.jpg';
        elseif (strpos($nombre_lower, 'jamaica') !== false) $imagen = 'img/Agua de Jamaica.webp';
        elseif (strpos($nombre_lower, 'limón') !== false || strpos($nombre_lower, 'limon') !== false) $imagen = 'img/Agua de limon.webp';
        elseif (strpos($nombre_lower, 'tamarindo') !== false) $imagen = 'img/Agua de Tamarindo.png';
        elseif (strpos($nombre_lower, 'coca') !== false) $imagen = 'img/Coca cola.png';
        elseif (strpos($nombre_lower, 'sprite') !== false) $imagen = 'img/Sprite.webp';
    }

    $row['imagen'] = $imagen;

    // Clasificar: Si coincide con keywords de bebida, es bebida; else comida/taco
    $is_bebida = false;
    foreach ($bebida_keywords as $kw) {
        if (strpos($nombre_lower, $kw) !== false) {
            $is_bebida = true;
            break;
        }
    }

    if ($is_bebida) {
        $bebidas[] = $row;
    } else {
        $tacos[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Menú Taquería</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .hero {
            background-image: url('Fondo.jpg');
            background-size: cover;
            background-position: center;
            position: relative;
            color: white;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.7);
        }
        .hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.4);
        }
        .img-producto {
            width: 100%;
            height: 180px;
            object-fit: cover;
            border-radius: 10px;
            margin-bottom: 10px;
        }
        .producto-card {
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            min-height: 350px;  /* Aumentado para acomodar descripciones más largas */
        }
        .producto-card p.descripcion {
            flex-grow: 1;
            overflow: hidden;
            text-overflow: ellipsis;
            display: -webkit-box;
            -webkit-line-clamp: 3; /* Limita a 3 líneas */
            -webkit-box-orient: vertical;
        }
        .producto-card .btn-agregar {
            margin-top: auto;  /* Empuja botón al fondo */
        }
    </style>
</head>
<body class="bg-gradient-to-br from-orange-100 to-red-100 min-h-screen">
    <div class="hero relative py-32 text-center">
        <h1 class="text-6xl font-bold relative z-10">
            Bienvenido a El Buen Sabor, <?= htmlspecialchars($_SESSION['usuario_nombre'] ?? '') ?> 🌮
        </h1>
    </div>

    <header class="bg-orange-600 text-white p-4 shadow-md">
        <nav class="flex justify-between items-center max-w-6xl mx-auto">

            <div class="flex space-x-8 items-center">
                <a href="menu.php" class="hover:scale-125 transition transform">
                    <i data-lucide="home" class="w-8 h-8"></i>
                </a>
                <a href="carrito.php" class="hover:scale-125 transition transform">
                    <i data-lucide="shopping-cart" class="w-8 h-8"></i>
                </a>
                <a href="pedidos.php" class="hover:scale-125 transition transform">
                    <i data-lucide="clipboard-list" class="w-8 h-8"></i>
                </a>
            </div>

            <div class="flex items-center space-x-4">
                
                <a href="perfil_usuario.php" 
                   class="flex items-center space-x-2 px-3 py-2 rounded-lg hover:bg-orange-500 hover:scale-110 transition transform">
                    <i data-lucide="user" class="w-6 h-6"></i>
                    <span><?= htmlspecialchars($_SESSION['usuario_nombre'] ?? '') ?></span>
                </a>
                <a href="logout.php"
                   class="flex items-center space-x-2 bg-red-500 px-3 py-2 rounded-lg hover:bg-red-600 hover:scale-110 transition transform">
                   <i data-lucide="log-out" class="w-6 h-6"></i>
                </a>
            </div>
        </nav>
    </header>

    <main class="max-w-6xl mx-auto p-6">
        <h2 class="text-3xl font-bold text-orange-600 mb-6 text-center">Nuestros Productos 🌮</h2>

        <?php if (!empty($tacos)): ?>
            <h3 class="text-2xl font-bold text-orange-600 mb-4 text-center">Tacos</h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6 mb-12">
                <?php foreach ($tacos as $row): ?>
                    <div class="bg-white p-4 rounded-xl shadow-lg hover:shadow-2xl transition producto-card">
                        <img src="<?= htmlspecialchars($row['imagen']) ?>" class="img-producto" alt="<?= htmlspecialchars($row['nombre']) ?>">
                        <h3 class="text-xl font-semibold text-orange-600"><?= htmlspecialchars($row['nombre']) ?></h3>
                        <p class="text-gray-600 my-2 descripcion"><?= htmlspecialchars($row['descripcion'] ?? 'Descripción no disponible') ?></p>
                        <p class="text-lg font-bold">$<?= number_format($row['precio'], 2) ?></p>
                        <a href="pedido.php?id=<?= isset($row['id']) ? htmlspecialchars($row['id']) : '' ?>"
                           class="block mt-4 bg-orange-500 text-white py-2 rounded-lg text-center hover:bg-orange-600 btn-agregar">
                            Agregar al Carrito
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($bebidas)): ?>
            <h3 class="text-2xl font-bold text-orange-600 mb-4 text-center">Bebidas</h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
                <?php foreach ($bebidas as $row): ?>
                    <div class="bg-white p-4 rounded-xl shadow-lg hover:shadow-2xl transition producto-card">
                        <img src="<?= htmlspecialchars($row['imagen']) ?>" class="img-producto" alt="<?= htmlspecialchars($row['nombre']) ?>">
                        <h3 class="text-xl font-semibold text-orange-600"><?= htmlspecialchars($row['nombre']) ?></h3>
                        <p class="text-gray-600 my-2 descripcion"><?= htmlspecialchars($row['descripcion'] ?? 'Descripción no disponible') ?></p>
                        <p class="text-lg font-bold">$<?= number_format($row['precio'], 2) ?></p>
                        <a href="pedido.php?id=<?= isset($row['id']) ? htmlspecialchars($row['id']) : '' ?>"
                           class="block mt-4 bg-orange-500 text-white py-2 rounded-lg text-center hover:bg-orange-600 btn-agregar">
                            Agregar al Carrito
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    </main>

    <script src="https://unpkg.com/lucide@latest"></script>
    <script> lucide.createIcons(); </script>

</body>
</html>
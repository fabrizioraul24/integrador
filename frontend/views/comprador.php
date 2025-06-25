<?php
session_start();

// Habilitar errores para depuración
ini_set('display_errors', 0); // Desactivamos display_errors en producción
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);

$host = '127.0.0.1';
$user = 'root';
$pass = '';
$db = 'noah';
$port = 3308;

$conn = new mysqli($host, $user, $pass, $db, $port);
if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

// Obtener categorías desde la tabla categorias
$categories = [];
$categorySql = "SELECT id_categoria, nombre_categoria FROM categorias";
$categoryResult = $conn->query($categorySql);
if ($categoryResult) {
    while ($row = $categoryResult->fetch_assoc()) {
        $categories[$row['id_categoria']] = htmlspecialchars($row['nombre_categoria']);
    }
}

// Consulta para obtener productos con su categoría
$sql = "SELECT p.id_producto, p.nombre_producto, p.precio, p.cantidad, p.foto, p.descripcion, p.id_categoria 
        FROM productos p 
        WHERE p.deleted = 0";
$result = $conn->query($sql);

$products = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $products[] = [
            'id' => $row['id_producto'],
            'name' => htmlspecialchars($row['nombre_producto']),
            'price' => floatval($row['precio']),
            'stock' => intval($row['cantidad']),
            'image' => !empty($row['foto']) ? "Uploads/" . basename($row['foto']) : null,
            'description' => htmlspecialchars($row['descripcion'] ?? 'Producto lácteo de calidad premium'),
            'category_id' => $row['id_categoria']
        ];
    }
} else {
    error_log("Error en la consulta de productos: " . $conn->connect_error);
}

$products_json = json_encode($products, JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS);
$categories_json = json_encode(array_values($categories));

// Manejo de logout
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: comprador.php");
    exit();
}

// Obtener cantidad total de productos en el carrito
$cartCount = 0;
if (isset($_SESSION['id_usuario'])) {
    $userId = $_SESSION['id_usuario'];
    $cartSql = "SELECT SUM(cantidad) as total FROM carrito WHERE id_usuario = ? AND fecha_agregado > DATE_SUB(NOW(), INTERVAL 30 MINUTE)";
    $stmt = $conn->prepare($cartSql);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $cartResult = $stmt->get_result();
    if ($row = $cartResult->fetch_assoc()) {
        $cartCount = $row['total'] ?? 0;
    }
    $stmt->close();
}

// Función para agregar al carrito en la base de datos
function addToCartDB($conn, $userId, $productId, $quantity) {
    $checkSql = "SELECT cantidad FROM carrito WHERE id_usuario = ? AND id_producto = ? AND fecha_agregado > DATE_SUB(NOW(), INTERVAL 30 MINUTE)";
    $stmt = $conn->prepare($checkSql);
    $stmt->bind_param("ii", $userId, $productId);
    $stmt->execute();
    $result = $stmt->get_result();
    $existing = $result->fetch_assoc();

    if ($existing && $existing['cantidad'] + $quantity <= getProductStock($conn, $productId)) {
        $updateSql = "UPDATE carrito SET cantidad = cantidad + ? WHERE id_usuario = ? AND id_producto = ?";
        $stmt = $conn->prepare($updateSql);
        $stmt->bind_param("iii", $quantity, $userId, $productId);
    } else {
        $insertSql = "INSERT INTO carrito (id_usuario, id_producto, cantidad, fecha_agregado) VALUES (?, ?, ?, NOW())";
        $stmt = $conn->prepare($insertSql);
        $stmt->bind_param("iii", $userId, $productId, $quantity);
    }
    $success = $stmt->execute();
    $stmt->close();
    return $success;
}

function getProductStock($conn, $productId) {
    $sql = "SELECT cantidad FROM productos WHERE id_producto = ? AND deleted = 0";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $productId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    return $row ? $row['cantidad'] : 0;
}

// Manejo de la adición al carrito
if (isset($_POST['add_to_cart']) && isset($_SESSION['id_usuario'])) {
    $userId = $_SESSION['id_usuario'];
    $productId = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);
    $quantity = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_INT) ?? 1;

    if ($productId && $quantity > 0 && $quantity <= getProductStock($conn, $productId)) {
        if (addToCartDB($conn, $userId, $productId, $quantity)) {
            $productName = array_filter($products, fn($p) => $p['id'] == $productId)[array_key_first(array_filter($products, fn($p) => $p['id'] == $productId))]['name'];
            $_SESSION['cart_message'] = "¡" . htmlspecialchars($productName) . " agregado al carrito!";
            header("Location: comprador.php");
            exit();
        } else {
            $_SESSION['cart_error'] = "Error al agregar el producto al carrito.";
        }
    } else {
        $_SESSION['cart_error'] = "Cantidad inválida o producto no disponible.";
    }
} elseif (isset($_POST['add_to_cart']) && !isset($_SESSION['id_usuario'])) {
    $_SESSION['cart_error'] = "Por favor inicia sesión para agregar productos al carrito.";
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PIL Andina - Productos Lácteos Premium de Bolivia</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-light: #7fb3d5;
            --primary-blue: #2980b9;
            --primary-medium: #5499c7;
            --primary-soft: #a9cce3;
            --hover-light: #d4e6f1;
            --price-yellow: #ffd700;
            --price-black: #1a202c;
        }

        * {
            font-family: 'Poppins', sans-serif;
        }

        body {
            background: linear-gradient(135deg, #f0f4f8 0%, #d3e0ea 100%);
            min-height: 100vh;
            position: relative;
            overflow-x: hidden;
            margin: 0;
        }

        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1000 1000"><polygon fill="%23ffffff08" points="0,1000 1000,0 1000,1000"/></svg>');
            z-index: -1;
        }

        .glass-card {
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            border-radius: 16px;
        }

        .glass-strong {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            box-shadow: 0 8px 40px rgba(0, 0, 0, 0.12);
            border-radius: 16px;
        }

        .product-card {
            background: rgba(255, 255, 255, 0.98);
            border: 2px solid rgba(255, 255, 255, 0.3);
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            position: relative;
            overflow: hidden;
            border-radius: 15px;
            padding: 8px;
        }

        .product-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(45deg, var(--primary-blue), var(--primary-light));
            opacity: 0;
            transition: opacity 0.3s ease;
            z-index: -1;
        }

        .product-card:hover {
            transform: translateY(-8px) scale(1.02);
            box-shadow: 0 15px 40px rgba(41, 128, 185, 0.3);
            border-color: var(--primary-blue);
        }

        .product-card:hover::before {
            opacity: 0.05;
        }

        .btn-pil {
            background: linear-gradient(135deg, var(--primary-blue), var(--primary-medium));
            color: white;
            border: none;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
            border-radius: 10px;
            padding: 6px 12px;
        }

        .btn-pil::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.4), transparent);
            transition: left 0.5s;
        }

        .btn-pil:hover::before {
            left: 100%;
        }

        .btn-pil:hover {
            background: linear-gradient(135deg, var(--primary-medium), var(--primary-blue));
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(41, 128, 185, 0.4);
        }

        .btn-outline-pil {
            border: 2px solid var(--primary-blue);
            color: var(--primary-blue);
            background: #ffffff;
            transition: all 0.3s ease;
            border-radius: 10px;
            padding: 6px 12px;
        }

        .btn-outline-pil:hover {
            background: var(--hover-light);
            color: var(--primary-blue);
            transform: translateY(-1px);
        }

        .filter-tag {
            background: #ffffff;
            border: 2px solid rgba(41, 128, 185, 0.3);
            color: var(--primary-blue);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
            border-radius: 8px;
            padding: 6px 12px;
        }

        .filter-tag.active {
            background: var(--primary-blue);
            color: white;
            border-color: var(--primary-blue);
            transform: scale(1.05);
        }

        .filter-tag:hover {
            background: var(--hover-light);
            color: var(--primary-blue);
            transform: translateY(-1px);
        }

        .cart-floating {
            background: linear-gradient(135deg, var(--primary-blue), var(--primary-medium));
            box-shadow: 0 10px 40px rgba(41, 128, 185, 0.4);
            transition: all 0.3s ease;
            position: relative;
            border-radius: 10px;
            padding: 10px 20px;
        }

        .cart-floating:hover {
            transform: translateY(-3px) scale(1.05);
            box-shadow: 0 15px 50px rgba(41, 128, 185, 0.6);
        }

        .hero-banner {
            background: linear-gradient(135deg, var(--primary-blue), var(--primary-medium));
            position: relative;
            overflow: hidden;
            border-radius: 0 0 15px 15px;
        }

        .hero-banner::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1000 1000"><circle fill="%23ffffff15" cx="200" cy="200" r="100"/><circle fill="%23ffffff10" cx="800" cy="300" r="150"/><circle fill="%23ffffff05" cx="500" cy="600" r="200"/></svg>');
        }

        .product-image-container {
            height: 200px;
            background: linear-gradient(135deg, var(--primary-soft), #ffffff);
            border-radius: 10px;
            overflow: hidden;
            position: relative;
            border: 2px solid rgba(41, 128, 185, 0.1);
        }

        .product-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.4s ease;
        }

        .product-card:hover .product-image {
            transform: scale(1.1) rotate(2deg);
        }

        .search-container {
            position: relative;
            background: #ffffff;
            border-radius: 20px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
        }

        .search-input {
            background: transparent;
            border: none;
            outline: none;
            padding: 12px 50px 12px 20px;
            font-size: 14px;
            width: 100%;
            border-radius: 10px;
            color: var(--primary-blue);
        }

        .modal {
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(5px);
        }

        .modal-content {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 12px;
            padding: 10px 16px;
            box-shadow: 0 3px 15px rgba(0, 0, 0, 0.2);
            animation: popIn 0.3s ease-out;
        }

        .cart-success-modal {
            background: rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(8px);
            opacity: 0;
            transition: opacity 0.3s ease-out;
            display: none;
        }

        .cart-success-modal.show {
            opacity: 1;
            display: flex;
        }

        .cart-success-content {
            background: linear-gradient(135deg, var(--primary-soft), #ffffff);
            border-radius: 20px;
            padding: 24px;
            box-shadow: 0 10px 30px rgba(41, 128, 185, 0.3);
            transform: scale(0.7);
            animation: popIn 0.4s ease-out forwards;
            max-width: 400px;
            width: 90%;
            position: relative;
            overflow: hidden;
            border: 2px solid var(--primary-blue);
        }

        .cart-success-content::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><circle fill="%232980b910" cx="50" cy="50" r="50"/></svg>');
            opacity: 0.1;
            z-index: 0;
        }

        .cart-success-content > * {
            position: relative;
            z-index: 1;
        }

        @keyframes popIn {
            from {
                opacity: 0;
                transform: scale(0.7);
            }
            to {
                opacity: 1;
                transform: scale(1);
            }
        }

        .floating-particles {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: 0;
        }

        .particle {
            position: absolute;
            background: rgba(41, 128, 185, 0.1);
            border-radius: 50%;
            animation: float 6s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-20px) rotate(180deg); }
        }

        .stock-indicator {
            position: absolute;
            top: 8px;
            right: 8px;
            padding: 3px 6px;
            border-radius: 10px;
            font-size: 10px;
            font-weight: 600;
            backdrop-filter: blur(10px);
        }

        .stock-high {
            background: rgba(34, 197, 94, 0.9);
            color: white;
        }

        .stock-medium {
            background: rgba(234, 179, 8, 0.9);
            color: white;
        }

        .stock-low {
            background: rgba(239, 68, 68, 0.9);
            color: white;
        }

        .price-tag {
            background: var(--price-yellow);
            color: var(--price-black);
            font-weight: 800;
            font-size: 1rem;
            padding: 6px 12px;
            border-radius: 15px;
            box-shadow: 0 3px 10px rgba(255, 215, 0, 0.4);
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.2);
        }

        .category-badge {
            background: linear-gradient(135deg, var(--primary-medium), var(--primary-soft));
            color: white;
            font-size: 8px;
            font-weight: 600;
            padding: 3px 6px;
            border-radius: 6px;
            text-transform: uppercase;
        }

        .quantity-control {
            display: flex;
            align-items: center;
            background: #ffffff;
            border: 2px solid var(--primary-blue);
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 3px 8px rgba(41, 128, 185, 0.2);
        }

        .quantity-btn {
            background: var(--primary-blue);
            color: white;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: background 0.3s ease, transform 0.2s ease;
            font-size: 1rem;
            font-weight: 600;
        }

        .quantity-btn:hover {
            background: var(--primary-medium);
            transform: scale(1.1);
        }

        .quantity-input {
            width: 50px;
            height: 30px;
            text-align: center;
            border: none;
            background: transparent;
            font-size: 0.875rem;
            font-weight: 600;
            color: var(--primary-blue);
            outline: none;
            -moz-appearance: textfield;
        }

        .quantity-input::-webkit-outer-spin-button,
        .quantity-input::-webkit-inner-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }

        header {
            background: var(--primary-soft);
            border-bottom: 1px solid rgba(0, 0, 0, 0.1);
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            border-radius: 0;
        }

        footer {
            background: var(--primary-soft);
            border-top: 1px solid rgba(0, 0, 0, 0.1);
            box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.1);
            border-radius: 0;
        }

        header a,
        header span,
        footer a,
        footer span,
        footer p {
            color: #1a202c !important;
        }

        .btn-outline-pil,
        .filter-tag,
        .quantity-control,
        .search-container {
            background: #ffffff;
            transition: background-color 0.3s ease;
        }

        .btn-outline-pil:hover,
        .filter-tag:hover,
        .quantity-control:hover,
        .search-container:hover {
            background: var(--hover-light);
        }

        .cart-floating {
            background: linear-gradient(135deg, var(--primary-blue), var(--primary-medium));
            transition: background 0.3s ease;
        }

        .cart-floating:hover {
            background: linear-gradient(135deg, var(--primary-medium), var(--primary-blue));
        }

        .main-content {
            overflow-y: auto;
            max-height: calc(100vh - 64px);
            scrollbar-width: thin;
            scrollbar-color: var(--primary-soft) #ffffff;
        }

        .main-content::-webkit-scrollbar {
            width: 8px;
        }

        .main-content::-webkit-scrollbar-track {
            background: #ffffff;
        }

        .main-content::-webkit-scrollbar-thumb {
            background-color: var(--primary-soft);
            border-radius: 10px;
            border: 2px solid #ffffff;
        }

        .unified-scroll {
            display: flex;
            flex-direction: row;
            gap: 10px;
            width: 100%;
        }

        .categories-panel {
            min-width: 150px;
            margin-left: -20px;
        }

        .products-panel {
            flex-grow: 5;
        }
    </style>
</head>
<body>
    <!-- Partículas flotantes -->
    <div class="floating-particles">
        <div class="particle" style="left: 10%; animation-delay: 0s; width: 4px; height: 4px;"></div>
        <div class="particle" style="left: 20%; animation-delay: 1s; width: 6px; height: 6px;"></div>
        <div class="particle" style="left: 30%; animation-delay: 2s; width: 3px; height: 3px;"></div>
        <div class="particle" style="left: 40%; animation-delay: 3s; width: 5px; height: 5px;"></div>
        <div class="particle" style="left: 50%; animation-delay: 4s; width: 4px; height: 4px;"></div>
        <div class="particle" style="left: 60%; animation-delay: 1.5s; width: 6px; height: 6px;"></div>
        <div class="particle" style="left: 70%; animation-delay: 2.5s; width: 3px; height: 3px;"></div>
        <div class="particle" style="left: 80%; animation-delay: 3.5s; width: 5px; height: 5px;"></div>
        <div class="particle" style="left: 90%; animation-delay: 0.5s; width: 4px; height: 4px;"></div>
    </div>

    <!-- Header -->
    <header class="sticky top-0 z-40">
        <div class="max-w-7xl mx-auto px-4 py-6">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-6">
                    <div class="w-24 h-24 bg-gradient-to-br from-white/20 to-white/10 rounded-2xl flex items-center justify-center backdrop-blur-lg border border-white/30 shadow-xl">
                        <img src="../views/logo/pil.svg" alt="PIL Andina Logo" class="w-20 h-20 object-contain" 
                             onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                        <div class="hidden flex-col items-center text-black">
                            <i class="fas fa-cow text-3xl mb-1"></i>
                            <span class="text-xs font-bold">PIL</span>
                        </div>
                    </div>
                    <div>
                        <h1 class="text-3xl font-bold text-black mb-1">PIL Andina</h1>
                        <p class="text-black/80 text-sm">Productos Lácteos Premium de Bolivia 🇧🇴</p>
                        <div class="flex items-center space-x-2 mt-1">
                            <i class="fas fa-award text-yellow-500 text-xs"></i>
                            <span class="text-black/70 text-xs">Calidad Certificada desde 1978</span>
                        </div>
                    </div>
                </div>
                
                <div class="flex items-center space-x-4">
                    <?php if (isset($_SESSION['nombre'])): ?>
                        <div class="flex items-center space-x-4">
                            <div class="bg-white px-4 py-2 rounded-xl shadow-md">
                                <span class="text-black font-medium">¡Hola, <?php echo htmlspecialchars($_SESSION['nombre']); ?>!</span>
                            </div>
                            <a href="?logout=1" class="btn-outline-pil px-6 py-3 rounded-xl font-medium text-black border-black/50 hover:bg-d4e6f1 hover:text-primary-blue">
                                <i class="fas fa-sign-out-alt mr-2"></i>Salir
                            </a>
                        </div>
                    <?php else: ?>
                        <a href="login.php" class="btn-outline-pil px-6 py-3 rounded-xl font-medium text-black border-black/50 hover:bg-d4e6f1 hover:text-primary-blue">
                            <i class="fas fa-user mr-2"></i>Iniciar Sesión
                        </a>
                    <?php endif; ?>
                    <a href="carrito.php" class="cart-floating text-black px-8 py-4 rounded-2xl font-bold shadow-xl hover:shadow-2xl transition-all">
                        <i class="fas fa-shopping-cart mr-3 text-lg"></i>
                        Mi Carrito
                        <span id="cartCount" class="ml-3 bg-white px-3 py-1 rounded-full text-sm font-bold <?php echo $cartCount > 0 ? '' : 'hidden'; ?>"><?php echo $cartCount; ?></span>
                    </a>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content with Single Scroll -->
    <div class="main-content">
        <!-- Hero Banner -->
        <div class="hero-banner py-12 relative">
            <div class="max-w-7xl mx-auto px-4 text-center relative z-10">
                <h2 class="text-4xl font-bold text-white mb-3 animate__animated animate__fadeInUp">
                    Tradición Láctea Boliviana
                </h2>
                <p class="text-lg text-white/90 mb-6 animate__animated animate__fadeInUp animate__delay-1s">
                    Descubre nuestros productos premium elaborados con la mejor leche de los Andes
                </p>
                <div class="flex justify-center space-x-3 animate__animated animate__fadeInUp animate__delay-2s">
                    <div class="glass-card px-4 py-2 rounded-xl">
                        <i class="fas fa-leaf text-green-300 mr-1"></i>
                        <span class="text-white font-medium">100% Natural</span>
                    </div>
                    <div class="glass-card px-4 py-2 rounded-xl">
                        <i class="fas fa-certificate text-yellow-300 mr-1"></i>
                        <span class="text-white font-medium">Calidad Premium</span>
                    </div>
                    <div class="glass-card px-4 py-2 rounded-xl">
                        <i class="fas fa-truck text-blue-300 mr-1"></i>
                        <span class="text-white font-medium">Envío Fresco</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="max-w-7xl mx-auto px-4 py-4">
            <!-- Barra de búsqueda y filtros -->
            <div class="mb-4">
                <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-4 mb-3">
                    <div class="flex-1 max-w-2xl w-full">
                        <div class="search-container">
                            <i class="absolute left-5 top-1/2 transform -translate-y-1/2 text-black text-base"></i>
                            <input type="text" id="searchInput" placeholder="Buscar productos lácteos..." class="search-input">
                            <button class="absolute right-3 top-1/2 transform -translate-y-1/2 btn-outline-pil px-3 py-1 rounded-xl">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Categorías y Productos -->
                <div class="unified-scroll">
                    <div class="categories-panel">
                        <div class="glass-strong rounded-2xl p-3">
                            <h3 class="font-bold text-black mb-2 flex items-center">
                                <i class="fas fa-tags mr-1 text-primary-blue"></i>
                                Categorías
                            </h3>
                            <div class="flex flex-col gap-1" id="categoryFilters">
                                <button onclick="filterByCategory('')" class="filter-tag px-2 py-1 rounded-lg text-sm active">
                                    Todas
                                </button>
                                <?php foreach ($categories as $catId => $catName): ?>
                                    <button onclick="filterByCategory('<?php echo htmlspecialchars($catName); ?>')" class="filter-tag px-2 py-1 rounded-lg text-sm" 
                                            data-cat-id="<?php echo $catId; ?>">
                                        <?php echo htmlspecialchars($catName); ?>
                                    </button>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <div class="products-panel">
                        <div id="productsList" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                            <!-- Los productos se cargan con JavaScript -->
                        </div>

                        <!-- Sin resultados -->
                        <div id="noResults" class="text-center py-4 hidden">
                            <div class="glass-strong rounded-2xl p-4 max-w-md mx-auto">
                                <div class="text-3xl mb-1 opacity-30">
                                    <i class="fas fa-search text-black"></i>
                                </div>
                                <h3 class="text-base font-bold text-black mb-1">
                                    No encontramos productos
                                </h3>
                                <p class="text-black/70 mb-1">
                                    Intenta con otros términos de búsqueda o filtros diferentes
                                </p>
                                <button onclick="clearFilters()" class="btn-outline-pil px-3 py-1 rounded-xl font-medium">
                                    <i class="fas fa-refresh mr-1"></i>Limpiar Filtros
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <footer class="">
            <div class="max-w-7xl mx-auto px-4 py-8">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                    <div>
                        <div class="flex items-center space-x-2 mb-3">
                            <div class="w-10 h-10 bg-white rounded-lg flex items-center justify-center">
                                <i class="fas fa-cow text-black text-lg"></i>
                            </div>
                            <div>
                                <h3 class="font-bold text-black text-base mb-1">PIL Andina</h3>
                                <p class="text-black/70 text-xs">Desde 1978</p>
                            </div>
                        </div>
                        <p class="text-black/80 text-xs leading-relaxed">
                            Tradición láctea boliviana con la más alta calidad y frescura para tu familia.
                        </p>
                    </div>
                    
                    <div>
                        <h4 class="font-bold text-black mb-3">Productos</h4>
                        <ul class="space-y-1 text-black/80 text-xs">
                            <li><a href="#" class="hover:text-black transition-colors">Leche Fresca</a></li>
                            <li><a href="#" class="hover:text-black transition-colors">Yogurts</a></li>
                            <li><a href="#" class="hover:text-black transition-colors">Quesos</a></li>
                            <li><a href="#" class="hover:text-black transition-colors">Mantequilla</a></li>
                        </ul>
                    </div>
                    
                    <div>
                        <h4 class="font-bold text-black mb-3">Contacto</h4>
                        <ul class="space-y-1 text-black/80 text-xs">
                            <li class="flex items-center">
                                <i class="fas fa-phone mr-1"></i>
                                +591 2 123-4567
                            </li>
                            <li class="flex items-center">
                                <i class="fas fa-envelope mr-1"></i>
                                info@pilandina.com
                            </li>
                            <li class="flex items-center">
                                <i class="fas fa-map-marker-alt mr-1"></i>
                                La Paz, Bolivia
                            </li>
                        </ul>
                    </div>
                    
                    <div>
                        <h4 class="font-bold text-black mb-3">Síguenos</h4>
                        <div class="flex space-x-2">
                            <a href="#" class="w-8 h-8 bg-white rounded-lg flex items-center justify-center hover:bg-d4e6f1 transition-colors">
                                <i class="fab fa-facebook-f text-black text-sm"></i>
                            </a>
                            <a href="#" class="w-8 h-8 bg-white rounded-lg flex items-center justify-center hover:bg-d4e6f1 transition-colors">
                                <i class="fab fa-instagram text-black text-sm"></i>
                            </a>
                            <a href="#" class="w-8 h-8 bg-white rounded-lg flex items-center justify-center hover:bg-d4e6f1 transition-colors">
                                <i class="fab fa-twitter text-black text-sm"></i>
                            </a>
                            <a href="#" class="w-8 h-8 bg-white rounded-lg flex items-center justify-center hover:bg-d4e6f1 transition-colors">
                                <i class="fab fa-whatsapp text-black text-sm"></i>
                            </a>
                        </div>
                    </div>
                </div>
                
                <div class="border-t border-black/20 mt-6 pt-6 text-center">
                    <p class="text-black/70 text-xs">
                        © 2025 PIL Andina. Todos los derechos reservados. 
                        <span class="text-black/50">|</span>
                        Hecho con ❤️ en Bolivia 🇧🇴
                    </p>
                </div>
            </div>
        </footer>
    </div>

    <!-- Modal de éxito al agregar al carrito -->
    <div id="cartSuccessModal" class="cart-success-modal fixed inset-0 z-50 items-center justify-center p-4">
        <div class="cart-success-content text-center">
            <i class="fas fa-check-circle text-green-500 text-4xl mb-4 animate__animated animate__bounceIn"></i>
            <h3 class="text-xl font-bold text-primary-blue mb-2">¡Agregado al Carrito!</h3>
            <p id="cartSuccessMessage" class="text-black font-medium text-base mb-4"></p>
            <a href="carrito.php" class="btn-pil inline-flex items-center px-6 py-2 rounded-xl font-medium">
                <i class="fas fa-shopping-cart mr-2"></i>Ver Carrito
            </a>
        </div>
    </div>

    <!-- Modal de error -->
    <div id="cartErrorModal" class="cart-success-modal fixed inset-0 z-50 items-center justify-center p-4">
        <div class="cart-success-content text-center">
            <i class="fas fa-exclamation-circle text-red-500 text-4xl mb-4 animate__animated animate__bounceIn"></i>
            <h3 class="text-xl font-bold text-primary-blue mb-2">Error</h3>
            <p id="cartErrorMessage" class="text-black font-medium text-base mb-4"></p>
            <button onclick="closeCartError()" class="btn-pil inline-flex items-center px-6 py-2 rounded-xl font-medium">
                <i class="fas fa-times mr-2"></i>Cerrar
            </button>
        </div>
    </div>

    <!-- Modal de detalles del producto -->
    <div id="productModal" class="modal fixed inset-0 z-50 hidden items-center justify-center p-4">
        <div class="modal-content rounded-2xl max-w-3xl w-full max-h-[85vh] overflow-y-auto">
            <div class="p-6">
                <div class="flex justify-between items-start mb-4">
                    <h2 id="modalTitle" class="text-2xl font-bold text-black"></h2>
                    <button onclick="closeModal()" class="text-black hover:text-gray-600 text-xl">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <div>
                        <div id="modalImageContainer" class="product-image-container mb-3">
                            <img id="modalImage" src="" alt="" class="product-image">
                        </div>
                        <div class="flex space-x-3">
                            <div class="category-badge" id="modalCategory"></div>
                        </div>
                    </div>
                    
                    <div>
                        <div class="price-tag mb-4 inline-block" id="modalPrice"></div>
                        <p id="modalDescription" class="text-black mb-4 text-base leading-relaxed"></p>
                        
                        <div class="mb-4">
                            <div class="flex items-center justify-between mb-2">
                                <span class="font-semibold text-black">Disponibilidad:</span>
                                <span id="modalStock" class="font-bold"></span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2">
                                <div id="modalStockBar" class="h-2 rounded-full transition-all duration-500"></div>
                            </div>
                        </div>
                        
                        <form method="POST" action="" class="space-y-3">
                            <div class="flex items-center space-x-3">
                                <label class="font-semibold text-black">Cantidad:</label>
                                <div class="quantity-control">
                                    <button type="button" class="quantity-btn" data-action="-">-</button>
                                    <input type="number" name="quantity" id="modalQuantity" class="quantity-input" min="1" value="1">
                                    <button type="button" class="quantity-btn" data-action="+">+</button>
                                </div>
                            </div>
                            <input type="hidden" name="product_id" id="modalProductId" value="">
                            <button type="submit" name="add_to_cart" id="modalAddToCart" 
                                    class="btn-pil py-3 px-6 rounded-2xl font-bold shadow-lg w-full text-base">
                                <i class="fas fa-cart-plus mr-2"></i>
                                Agregar al Carrito
                            </button>
                        </form>
                        
                        <div class="mt-6 grid grid-cols-3 gap-3 text-center">
                            <div class="glass-card p-3 rounded-xl">
                                <i class="fas fa-shield-alt text-primary-blue text-xl mb-1"></i>
                                <div class="text-xs font-semibold text-black">Garantía</div>
                                <div class="text-xs text-black/70">100% Natural</div>
                            </div>
                            <div class="glass-card p-3 rounded-xl">
                                <i class="fas fa-truck text-green-500 text-xl mb-1"></i>
                                <div class="text-xs font-semibold text-black">Envío</div>
                                <div class="text-xs text-black/70">Refrigerado</div>
                            </div>
                            <div class="glass-card p-3 rounded-xl">
                                <i class="fas fa-medal text-yellow-500 text-xl mb-1"></i>
                                <div class="text-xs font-semibold text-black">Calidad</div>
                                <div class="text-xs text-black/70">Premium</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        const products = <?php echo $products_json; ?>;
        const categories = <?php echo $categories_json; ?>;
        const cartCountElement = document.getElementById('cartCount');
        
        let filteredProducts = [...products];
        let currentCategory = '';

        function displayProducts(productsToShow = filteredProducts) {
            const list = document.getElementById('productsList');
            const noResults = document.getElementById('noResults');
            const userId = <?php echo isset($_SESSION['id_usuario']) ? $_SESSION['id_usuario'] : 'null'; ?>;
            
            list.innerHTML = '';
            
            if (productsToShow.length === 0) {
                noResults.classList.remove('hidden');
                return;
            }
            
            noResults.classList.add('hidden');
            
            productsToShow.forEach((product, index) => {
                const categoryName = categories[product.category_id - 1] || 'General';
                const card = document.createElement('div');
                card.className = 'product-card rounded-2xl p-4 animate__animated animate__fadeInUp';
                card.style.animationDelay = `${index * 0.1}s`;
                
                const stockClass = product.stock > 10 ? 'stock-high' : product.stock > 5 ? 'stock-medium' : 'stock-low';
                const stockIcon = product.stock > 10 ? 'fa-check-circle' : product.stock > 5 ? 'fa-exclamation-triangle' : 'fa-times-circle';
                
                card.innerHTML = `
                    <div class="product-image-container mb-3 relative">
                        ${product.image ? 
                            `<img src="${product.image}" alt="${product.name}" class="product-image">` : 
                            `<div class="flex items-center justify-center h-full text-black bg-gradient-to-br from-primary-soft to-primary-light">
                                <div class="text-center">
                                    <i class="fas fa-cow text-4xl mb-2 text-primary-blue"></i>
                                    <div class="text-xs font-semibold">PIL Andina</div>
                                </div>
                            </div>`
                        }
                        <div class="stock-indicator ${stockClass}">
                            <i class="fas ${stockIcon} mr-1"></i>
                            ${product.stock}
                        </div>
                        ${product.stock === 0 ? 
                            '<div class="absolute inset-0 bg-black/60 flex items-center justify-center rounded-2xl"><span class="text-white font-bold text-base bg-red-500 px-3 py-1 rounded-xl">AGOTADO</span></div>' : 
                            ''
                        }
                    </div>
                    
                    <div class="space-y-2">
                        <div class="flex justify-between items-start">
                            <div class="category-badge">${categoryName}</div>
                        </div>
                        
                        <h3 class="font-bold text-lg text-black leading-tight">${product.name}</h3>
                        
                        <div class="flex justify-between items-center mt-2">
                            <div class="price-tag">Bs. ${product.price.toFixed(2)}</div>
                        </div>
                        
                        <div class="flex space-x-2 mt-2">
                            <button onclick="showProductDetails(${product.id})" 
                                    class="btn-outline-pil py-2 px-3 rounded-xl font-medium flex-1 text-sm">
                                <i class="fas fa-info-circle mr-1"></i>
                                Detalles
                            </button>
                            
                            <form method="POST" action="" class="flex-1">
                                <input type="hidden" name="product_id" value="${product.id}">
                                <div class="quantity-control">
                                    <button type="button" class="quantity-btn" data-action="-">-</button>
                                    <input type="number" name="quantity" class="quantity-input" value="1" min="1" max="${product.stock}">
                                    <button type="button" class="quantity-btn" data-action="+">+</button>
                                </div>
                                <button type="submit" name="add_to_cart" 
                                        class="btn-pil py-2 px-3 rounded-xl font-medium w-full mt-1 ${product.stock === 0 ? 'opacity-50 cursor-not-allowed' : ''}" 
                                        ${product.stock === 0 ? 'disabled' : ''}>
                                    <i class="fas fa-cart-plus mr-1"></i>
                                    ${product.stock === 0 ? 'Agotado' : 'Agregar'}
                                </button>
                            </form>
                        </div>
                    </div>
                `;
                
                list.appendChild(card);
            });
        }

        function showProductDetails(productId) {
            const product = products.find(p => p.id === productId);
            if (!product) return;

            const categoryName = categories[product.category_id - 1] || 'General';
            document.getElementById('modalTitle').textContent = product.name;
            document.getElementById('modalPrice').textContent = `Bs. ${product.price.toFixed(2)}`;
            document.getElementById('modalDescription').textContent = product.description;
            document.getElementById('modalCategory').textContent = categoryName;
            document.getElementById('modalProductId').value = product.id;
            let modalQuantity = document.getElementById('modalQuantity');
            modalQuantity.max = product.stock;
            modalQuantity.value = 1;
            
            const modalImage = document.getElementById('modalImage');
            const modalImageContainer = document.getElementById('modalImageContainer');
            
            if (product.image) {
                modalImage.src = product.image;
                modalImage.alt = product.name;
                modalImage.style.display = 'block';
            } else {
                modalImageContainer.innerHTML = `
                    <div class="flex items-center justify-center h-full text-black bg-gradient-to-br from-primary-soft to-primary-light">
                        <div class="text-center">
                            <i class="fas fa-cow text-6xl mb-3 text-primary-blue"></i>
                            <div class="text-base font-semibold text-primary-blue">PIL Andina</div>
                            <div class="text-sm text-primary-medium">Producto Premium</div>
                        </div>
                    </div>
                `;
            }
            
            const stockPercentage = Math.min((product.stock / 50) * 100, 100);
            const stockColor = product.stock > 10 ? 'bg-green-500' : product.stock > 5 ? 'bg-yellow-500' : 'bg-red-500';
            const stockText = product.stock > 10 ? 'Alta' : product.stock > 5 ? 'Media' : 'Baja';
            
            document.getElementById('modalStock').innerHTML = `
                <span class="${product.stock > 10 ? 'text-green-600' : product.stock > 5 ? 'text-yellow-600' : 'text-red-600'}">
                    <i class="fas ${product.stock > 10 ? 'fa-check-circle' : product.stock > 5 ? 'fa-exclamation-triangle' : 'fa-times-circle'} mr-1"></i>
                    ${stockText} (${product.stock} unidades)
                </span>
            `;
            
            document.getElementById('modalStockBar').className = `h-2 rounded-full transition-all duration-500 ${stockColor}`;
            document.getElementById('modalStockBar').style.width = `${stockPercentage}%`;
            
            const addButton = document.getElementById('modalAddToCart');
            if (product.stock === 0) {
                addButton.disabled = true;
                addButton.classList.add('opacity-50', 'cursor-not-allowed');
                addButton.innerHTML = '<i class="fas fa-times mr-2"></i>Producto Agotado';
            } else {
                addButton.disabled = false;
                addButton.classList.remove('opacity-50', 'cursor-not-allowed');
                addButton.innerHTML = '<i class="fas fa-cart-plus mr-2"></i>Agregar al Carrito';
            }
            
            document.getElementById('productModal').classList.remove('hidden');
            document.getElementById('productModal').classList.add('flex');
        }

        function closeModal() {
            document.getElementById('productModal').classList.add('hidden');
            document.getElementById('productModal').classList.remove('flex');
        }

        function filterProducts(type) {
            document.querySelectorAll('.filter-tag').forEach(btn => {
                if (!btn.onclick || btn.onclick.toString().includes('filterBy')) return;
                btn.classList.remove('active');
            });
            event.target.classList.add('active');
            
            let sorted = [...filteredProducts];
            
            switch(type) {
                case 'precio-asc':
                    sorted.sort((a, b) => a.price - b.price);
                    break;
                case 'precio-desc':
                    sorted.sort((a, b) => b.price - a.price);
                    break;
                case 'stock':
                    sorted.sort((a, b) => b.stock - a.stock);
                    break;
                case 'popular':
                    sorted.sort((a, b) => a.id - b.id);
                    break;
            }
            
            displayProducts(sorted);
        }

        function filterByCategory(category) {
            document.querySelectorAll('#categoryFilters .filter-tag').forEach(btn => {
                btn.classList.remove('active');
            });
            event.target.classList.add('active');
            
            currentCategory = category;
            applyFilters();
        }

        function applyFilters() {
            let filtered = [...products];
            
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            if (searchTerm) {
                filtered = filtered.filter(product => 
                    product.name.toLowerCase().includes(searchTerm) ||
                    product.description.toLowerCase().includes(searchTerm)
                );
            }
            
            if (currentCategory) {
                const categoryId = Array.from(document.querySelectorAll('#categoryFilters .filter-tag'))
                    .find(btn => btn.textContent.trim() === currentCategory)
                    ?.getAttribute('data-cat-id');
                if (categoryId) {
                    filtered = filtered.filter(product => product.category_id == categoryId);
                }
            }
            
            filteredProducts = filtered;
            displayProducts();
        }

        function clearFilters() {
            document.getElementById('searchInput').value = '';
            currentCategory = '';
            
            document.querySelectorAll('.filter-tag').forEach(btn => {
                btn.classList.remove('active');
            });
            
            document.querySelector('#categoryFilters .filter-tag').classList.add('active');
            
            filteredProducts = [...products];
            displayProducts();
        }

        function updateCartBadge() {
            fetch('update_cart_count.php')
                .then(response => response.json())
                .then(data => {
                    const count = data.count || 0;
                    cartCountElement.textContent = count;
                    cartCountElement.classList.toggle('hidden', count === 0);
                })
                .catch(error => console.error('Error updating cart count:', error));
        }

        function showCartSuccess(message) {
            const modal = document.getElementById('cartSuccessModal');
            const messageElement = document.getElementById('cartSuccessMessage');
            messageElement.textContent = message;
            modal.classList.add('show');
            setTimeout(() => {
                modal.classList.remove('show');
            }, 3000);
        }

        function showCartError(message) {
            const modal = document.getElementById('cartErrorModal');
            const messageElement = document.getElementById('cartErrorMessage');
            messageElement.textContent = message;
            modal.classList.add('show');
            setTimeout(() => {
                modal.classList.remove('show');
            }, 3000);
        }

        function closeCartError() {
            const modal = document.getElementById('cartErrorModal');
            modal.classList.remove('show');
        }

        document.getElementById('searchInput').addEventListener('input', applyFilters);

        document.getElementById('productModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeModal();
                closeCartError();
                document.getElementById('cartSuccessModal').classList.remove('show');
            }
        });

        document.addEventListener('DOMContentLoaded', function() {
            displayProducts();
            updateCartBadge();
            setInterval(updateCartBadge, 5000);

            <?php if (isset($_SESSION['cart_message'])): ?>
                showCartSuccess("<?php echo $_SESSION['cart_message']; ?>");
                <?php unset($_SESSION['cart_message']); ?>
            <?php endif; ?>

            <?php if (isset($_SESSION['cart_error'])): ?>
                showCartError("<?php echo $_SESSION['cart_error']; ?>");
                <?php unset($_SESSION['cart_error']); ?>
            <?php endif; ?>

            const particles = document.querySelectorAll('.particle');
            particles.forEach((particle, index) => {
                particle.style.top = Math.random() * 100 + '%';
                particle.style.animationDuration = (Math.random() * 3 + 3) + 's';
                particle.style.animationDelay = Math.random() * 2 + 's';
            });

            document.querySelectorAll('.quantity-control').forEach(control => {
                const btns = control.querySelectorAll('.quantity-btn');
                const input = control.querySelector('.quantity-input');
                const max = parseInt(input.max);

                btns.forEach(btn => {
                    btn.addEventListener('click', () => {
                        let value = parseInt(input.value);
                        if (btn.dataset.action === '-') {
                            value = Math.max(1, value - 1);
                        } else {
                            value = Math.min(max, value + 1);
                        }
                        input.value = value;
                    });
                });

                input.addEventListener('change', () => {
                    let value = parseInt(input.value);
                    if (value > max) input.value = max;
                    if (value < 1) input.value = 1;
                });
            });
        });
    </script>
</body>
</html>
<?php $conn->close(); ?>
<?php
session_start();

// Habilitar errores para depuración (desactivado en producción)
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);

// Configuración de la base de datos
$host = '127.0.0.1';
$user = 'root';
$pass = '';
$db = 'noah';
$port = 3308;

// Manejo de solicitudes AJAX para actualizar cantidades
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_cart') {
    header('Content-Type: application/json');
    
    try {
        $conn = new mysqli($host, $user, $pass, $db, $port);
        if ($conn->connect_error) {
            throw new Exception('Error de conexión a la base de datos: ' . $conn->connect_error);
        }

        $userId = $_SESSION['id_usuario'] ?? null;
        if (!$userId) {
            throw new Exception('Usuario no autenticado');
        }

        $items = json_decode($_POST['items'], true);
        if (!is_array($items) || empty($items)) {
            throw new Exception('Datos de carrito inválidos');
        }

        $response = ['success' => true, 'messages' => [], 'cartTotal' => 0];

        foreach ($items as $item) {
            $cartId = filter_var($item['cart_id'], FILTER_VALIDATE_INT);
            $productId = filter_var($item['product_id'], FILTER_VALIDATE_INT);
            $quantity = filter_var($item['quantity'], FILTER_VALIDATE_INT);

            if (!$cartId || !$productId || !$quantity || $quantity < 1) {
                $response['success'] = false;
                $response['messages'][] = "Datos inválidos para el producto ID: $productId";
                continue;
            }

            // Verificar stock
            $sql = "SELECT cantidad FROM productos WHERE id_producto = ?";
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                throw new Exception('Error preparando consulta de stock: ' . $conn->error);
            }
            $stmt->bind_param("i", $productId);
            $stmt->execute();
            $result = $stmt->get_result();
            $stock = $result->fetch_assoc()['cantidad'] ?? 0;
            $stmt->close();

            if ($quantity > $stock) {
                $response['success'] = false;
                $response['messages'][] = "Cantidad solicitada ($quantity) excede el stock ($stock) para el producto ID: $productId";
                continue;
            }

            // Actualizar cantidad
            $sql = "UPDATE carrito SET cantidad = ? WHERE id_carrito = ? AND id_usuario = ?";
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                throw new Exception('Error preparando actualización: ' . $conn->error);
            }
            $stmt->bind_param("iii", $quantity, $cartId, $userId);
            if (!$stmt->execute()) {
                $response['success'] = false;
                $response['messages'][] = "Error al actualizar la cantidad para el producto ID: $productId";
            }
            $stmt->close();
        }

        // Calcular total del carrito
        $sql = "SELECT SUM(p.precio * c.cantidad) as total 
                FROM carrito c 
                JOIN productos p ON c.id_producto = p.id_producto 
                WHERE c.id_usuario = ? AND c.fecha_agregado > DATE_SUB(NOW(), INTERVAL 30 MINUTE)";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception('Error preparando consulta de total: ' . $conn->error);
        }
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $response['cartTotal'] = $result->fetch_assoc()['total'] ?? 0;
        $stmt->close();

        if (!$response['success'] && empty($response['messages'])) {
            $response['messages'][] = 'No se realizaron cambios en el carrito.';
        }

        echo json_encode($response);
        $conn->close();
    } catch (Exception $e) {
        error_log('Error en carrito.php (AJAX): ' . $e->getMessage());
        echo json_encode(['success' => false, 'messages' => [$e->getMessage()]]);
    }
    exit();
}

// Manejo de eliminación de productos
if (isset($_POST['remove_from_cart']) && isset($_SESSION['id_usuario'])) {
    $conn = new mysqli($host, $user, $pass, $db, $port);
    if ($conn->connect_error) {
        $_SESSION['cart_error'] = "Error de conexión: " . $conn->connect_error;
        header("Location: carrito.php");
        exit();
    }

    $cartId = filter_input(INPUT_POST, 'cart_id', FILTER_VALIDATE_INT);
    $userId = $_SESSION['id_usuario'];

    if ($cartId) {
        $sql = "DELETE FROM carrito WHERE id_carrito = ? AND id_usuario = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $cartId, $userId);
        if ($stmt->execute()) {
            $_SESSION['cart_message'] = "Producto eliminado del carrito.";
        } else {
            $_SESSION['cart_error'] = "Error al eliminar el producto.";
        }
        $stmt->close();
    } else {
        $_SESSION['cart_error'] = "ID de carrito inválido.";
    }
    $conn->close();
    header("Location: carrito.php");
    exit();
}

// Conexión para mostrar el carrito
$conn = new mysqli($host, $user, $pass, $db, $port);
if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

$userId = $_SESSION['id_usuario'] ?? null;
$cartItems = [];
$total = 0;

if ($userId) {
    $sql = "SELECT c.id_carrito, c.id_producto, p.nombre_producto, p.precio, c.cantidad, p.foto, p.cantidad as stock 
            FROM carrito c 
            JOIN productos p ON c.id_producto = p.id_producto 
            WHERE c.id_usuario = ? AND c.fecha_agregado > DATE_SUB(NOW(), INTERVAL 30 MINUTE)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $cartItems[] = [
            'id_carrito' => $row['id_carrito'],
            'id_producto' => $row['id_producto'],
            'nombre_producto' => htmlspecialchars($row['nombre_producto']),
            'precio' => floatval($row['precio']),
            'cantidad' => intval($row['cantidad']),
            'foto' => $row['foto'] ? "Uploads/" . basename($row['foto']) : null,
            'stock' => intval($row['stock'])
        ];
        $total += $row['precio'] * $row['cantidad'];
    }
    $stmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Carrito - PIL Andina</title>
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

        .cart-item {
            background: rgba(255, 255, 255, 0.98);
            border: 2px solid rgba(255, 255, 255, 0.3);
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            position: relative;
            overflow: hidden;
            border-radius: 15px;
            padding: 8px;
        }

        .cart-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(41, 128, 185, 0.3);
            border-color: var(--primary-blue);
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

        .product-image-container {
            width: 80px;
            height: 80px;
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

        .cart-item:hover .product-image {
            transform: scale(1.1);
        }

        .cart-success-modal {
            background: rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(8px);
            opacity: 0;
            transition: opacity 0.3s ease-out;
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 50;
            align-items: center;
            justify-content: center;
            padding: 16px;
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
            width: 45px;
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
        .quantity-control {
            background: #ffffff;
            transition: background-color 0.3s ease;
        }

        .btn-outline-pil:hover,
        .quantity-control:hover {
            background: var(--hover-light);
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
                            <a href="comprador.php?logout=1" class="btn-outline-pil px-6 py-3 rounded-xl font-medium text-black border-black/50 hover:bg-d4e6f1 hover:text-primary-blue">
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
                        <span id="cartCount" class="ml-3 bg-white px-3 py-1 rounded-full text-sm font-bold <?php echo count($cartItems) > 0 ? '' : 'hidden'; ?>"><?php echo array_sum(array_column($cartItems, 'cantidad')); ?></span>
                    </a>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <div class="main-content">
        <div class="max-w-4xl mx-auto px-4 py-8">
            <div class="glass-strong rounded-2xl p-6">
                <div class="flex items-center justify-between mb-6">
                    <h1 class="text-3xl font-bold text-primary-blue">Tu Carrito</h1>
                    <div class="w-12 h-12 bg-gradient-to-br from-primary-soft to-primary-light rounded-full flex items-center justify-center">
                        <i class="fas fa-shopping-cart text-primary-blue text-2xl"></i>
                    </div>
                </div>

                <div id="cartItems" class="space-y-4 mb-6">
                    <?php if (empty($cartItems)): ?>
                        <div class="text-center py-10">
                            <div class="glass-card rounded-2xl p-6 max-w-md mx-auto">
                                <i class="fas fa-shopping-cart text-primary-blue text-4xl mb-4 opacity-50"></i>
                                <h3 class="text-lg font-bold text-black mb-2">Tu carrito está vacío</h3>
                                <p class="text-black/70 mb-4">¡Explora nuestros productos lácteos premium!</p>
                                <a href="comprador.php" class="btn-pil px-6 py-3 rounded-xl font-medium">
                                    <i class="fas fa-store mr-2"></i>Seguir Comprando
                                </a>
                            </div>
                        </div>
                    <?php else: ?>
                        <?php foreach ($cartItems as $index => $item): ?>
                            <div class="cart-item rounded-2xl p-4 animate__animated animate__fadeInUp" style="animation-delay: <?php echo $index * 0.1; ?>s;" data-cart-id="<?php echo $item['id_carrito']; ?>" data-product-id="<?php echo $item['id_producto']; ?>">
                                <div class="flex items-center space-x-4">
                                    <div class="product-image-container">
                                        <?php if ($item['foto']): ?>
                                            <img src="<?php echo $item['foto']; ?>" alt="<?php echo htmlspecialchars($item['nombre_producto']); ?>" class="product-image">
                                        <?php else: ?>
                                            <div class="flex items-center justify-center h-full text-black bg-gradient-to-br from-primary-soft to-primary-light">
                                                <i class="fas fa-cow text-2xl text-primary-blue"></i>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="flex-1">
                                        <h3 class="text-lg font-bold text-black"><?php echo $item['nombre_producto']; ?></h3>
                                        <div class="price-tag inline-block mt-1" data-unit-price="<?php echo $item['precio']; ?>">Bs. <?php echo number_format($item['precio'], 2); ?> / unidad</div>
                                    </div>
                                    <div class="flex items-center space-x-4">
                                        <div class="quantity-control">
                                            <button type="button" class="quantity-btn" data-action="-">-</button>
                                            <input type="number" class="quantity-input" value="<?php echo $item['cantidad']; ?>" min="1" max="<?php echo $item['stock']; ?>">
                                            <button type="button" class="quantity-btn" data-action="+">+</button>
                                        </div>
                                        <span class="item-total text-lg font-semibold text-primary-blue">Bs. <?php echo number_format($item['precio'] * $item['cantidad'], 2); ?></span>
                                        <form method="POST" action="">
                                            <input type="hidden" name="cart_id" value="<?php echo $item['id_carrito']; ?>">
                                            <button type="submit" name="remove_from_cart" class="text-red-500 hover:text-red-700 transition-colors">
                                                <i class="fas fa-trash text-xl"></i>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        <div class="mt-4">
                            <button id="updateCartButton" class="btn-pil px-6 py-3 rounded-xl font-bold">
                                <i class="fas fa-sync-alt mr-2"></i>Actualizar Carrito
                            </button>
                        </div>
                    <?php endif; ?>
                </div>

                <?php if (!empty($cartItems)): ?>
                    <div class="glass-card rounded-2xl p-6">
                        <div class="flex justify-between items-center text-lg font-semibold mb-4">
                            <span class="text-black">Total</span>
                            <span id="cartTotal" class="text-primary-blue price-tag">Bs. <?php echo number_format($total, 2); ?></span>
                        </div>
                        <a href="checkout.php" class="btn-pil w-full py-3 px-6 rounded-xl font-bold text-lg flex items-center justify-center space-x-2">
                            <i class="fas fa-credit-card mr-2"></i>
                            <span>Proceder al Pago</span>
                        </a>
                    </div>
                <?php endif; ?>

                <a href="comprador.php" class="mt-4 inline-block btn-outline-pil px-6 py-2 rounded-xl font-medium">
                    <i class="fas fa-arrow-left mr-2"></i>Seguir Comprando
                </a>
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

    <!-- Modal de éxito -->
    <div id="cartSuccessModal" class="cart-success-modal">
        <div class="cart-success-content text-center">
            <i class="fas fa-check-circle text-green-500 text-4xl mb-4 animate__animated animate__bounceIn"></i>
            <h3 class="text-xl font-bold text-primary-blue mb-2">¡Operación Exitosa!</h3>
            <p id="cartSuccessMessage" class="text-black font-medium text-base mb-4"></p>
            <button onclick="closeCartSuccess()" class="btn-pil inline-flex items-center px-6 py-2 rounded-xl font-medium">
                <i class="fas fa-times mr-2"></i>Cerrar
            </button>
        </div>
    </div>

    <!-- Modal de error -->
    <div id="cartErrorModal" class="cart-success-modal">
        <div class="cart-success-content text-center">
            <i class="fas fa-exclamation-circle text-red-500 text-4xl mb-4 animate__animated animate__bounceIn"></i>
            <h3 class="text-xl font-bold text-primary-blue mb-2">Error</h3>
            <p id="cartErrorMessage" class="text-black font-medium text-base mb-4"></p>
            <button onclick="closeCartError()" class="btn-pil inline-flex items-center px-6 py-2 rounded-xl font-medium">
                <i class="fas fa-times mr-2"></i>Cerrar
            </button>
        </div>
    </div>

    <script>
        function showCartSuccess(message) {
            const modal = document.getElementById('cartSuccessModal');
            const messageElement = document.getElementById('cartSuccessMessage');
            messageElement.textContent = message;
            modal.classList.add('show');
            setTimeout(() => {
                modal.classList.remove('show');
            }, 3000);
        }

        function closeCartSuccess() {
            document.getElementById('cartSuccessModal').classList.remove('show');
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
            document.getElementById('cartErrorModal').classList.remove('show');
        }

        function updateCartBadge() {
            fetch('update_cart_count.php')
                .then(response => response.json())
                .then(data => {
                    const count = data.count || 0;
                    const cartCountElement = document.getElementById('cartCount');
                    cartCountElement.textContent = count;
                    cartCountElement.classList.toggle('hidden', count === 0);
                })
                .catch(error => console.error('Error actualizando conteo del carrito:', error));
        }

        function updateCartTotal() {
            let total = 0;
            document.querySelectorAll('.cart-item').forEach(item => {
                const quantity = parseInt(item.querySelector('.quantity-input').value);
                const unitPrice = parseFloat(item.querySelector('.price-tag').dataset.unitPrice);
                const itemTotal = quantity * unitPrice;
                item.querySelector('.item-total').textContent = `Bs. ${itemTotal.toFixed(2)}`;
                total += itemTotal;
            });
            document.getElementById('cartTotal').textContent = `Bs. ${total.toFixed(2)}`;
        }

        function updateCart() {
            const items = [];
            document.querySelectorAll('.cart-item').forEach(item => {
                const cartId = parseInt(item.dataset.cartId);
                const productId = parseInt(item.dataset.productId);
                const quantity = parseInt(item.querySelector('.quantity-input').value);
                items.push({ cart_id: cartId, product_id: productId, quantity: quantity });
            });

            // Cálculo local como respaldo
            updateCartTotal();

            // Solicitud AJAX
            fetch('carrito.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: `action=update_cart&items=${encodeURIComponent(JSON.stringify(items))}`
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                console.log('Respuesta del servidor:', data);
                if (data.success) {
                    showCartSuccess('Carrito actualizado correctamente.');
                    document.getElementById('cartTotal').textContent = `Bs. ${data.cartTotal.toFixed(2)}`;
                    updateCartBadge();
                } else {
                    showCartError(data.messages.join('\n'));
                    updateCartTotal(); // Revertir cambios locales si falla
                }
            })
            .catch(error => {
                console.error('Error en AJAX:', error);
                showCartError('Error en la conexión con el servidor. Los cambios se aplicaron localmente.');
            });
        }

        document.addEventListener('DOMContentLoaded', function() {
            updateCartBadge();
            setInterval(updateCartBadge, 3000);

            <?php if (isset($_SESSION['cart_message'])): ?>
                showCartSuccess("<?php echo $_SESSION['cart_message']; ?>");
                <?php unset($_SESSION['cart_message']); ?>
            <?php endif; ?>

            <?php if (isset($_SESSION['cart_error'])): ?>
                showCartError("<?php echo htmlspecialchars($_SESSION['cart_error']); ?>");
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
                        updateCartTotal();
                    });
                });

                input.addEventListener('change', () => {
                    let value = parseInt(input.value);
                    if (value > max) {
                        input.value = max;
                        value = max;
                    }
                    if (value < 1) {
                        input.value = 1;
                        value = 1;
                    }
                    updateCartTotal();
                });
            });

            document.getElementById('updateCartButton')?.addEventListener('click', updateCart);

            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    closeCartSuccess();
                    closeCartError();
                }
            });
        });
    </script>
</body>
</html>
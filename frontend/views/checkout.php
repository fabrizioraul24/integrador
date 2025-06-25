<?php
session_start();

// Configuración de errores (desactivado en producción)
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);

// Configuración de la base de datos
$host = '127.0.0.1';
$user = 'root';
$pass = '';
$db = 'noah';
$port = 3308;

// Manejo de solicitud AJAX para procesar el pago
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'process_payment') {
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

        $paymentMethod = filter_var($_POST['payment_method'], FILTER_SANITIZE_STRING);
        if (!in_array($paymentMethod, ['QR', 'Tarjeta', 'Efectivo'])) {
            throw new Exception('Método de pago inválido');
        }

        // Obtener items del carrito
        $sql = "SELECT c.id_carrito, c.id_producto, c.cantidad, p.nombre_producto, p.precio, p.cantidad as stock 
                FROM carrito c 
                JOIN productos p ON c.id_producto = p.id_producto 
                WHERE c.id_usuario = ? AND c.fecha_agregado > DATE_SUB(NOW(), INTERVAL 30 MINUTE)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $cartItems = [];
        $total = 0;

        while ($row = $result->fetch_assoc()) {
            if ($row['cantidad'] > $row['stock']) {
                throw new Exception("Stock insuficiente para el producto: {$row['nombre_producto']}");
            }
            $cartItems[] = $row;
            $total += $row['precio'] * $row['cantidad'];
        }
        $stmt->close();

        if (empty($cartItems)) {
            throw new Exception('El carrito está vacío');
        }

        // Iniciar transacción
        $conn->begin_transaction();

        // Crear orden
        $sql = "INSERT INTO ordenes (id_usuario, total, metodo_pago) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ids", $userId, $total, $paymentMethod);
        $stmt->execute();
        $orderId = $conn->insert_id;
        $stmt->close();

        // Insertar detalles de la orden y actualizar stock
        foreach ($cartItems as $item) {
            $sql = "INSERT INTO ordenes_detalles (id_orden, id_producto, cantidad, precio_unitario) VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("iiid", $orderId, $item['id_producto'], $item['cantidad'], $item['precio']);
            $stmt->execute();
            $stmt->close();

            $sql = "UPDATE productos SET cantidad = cantidad - ? WHERE id_producto = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ii", $item['cantidad'], $item['id_producto']);
            $stmt->execute();
            $stmt->close();
        }

        // Vaciar carrito
        $sql = "DELETE FROM carrito WHERE id_usuario = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $stmt->close();

        // Confirmar transacción
        $conn->commit();

        echo json_encode(['success' => true, 'message' => 'Pago procesado correctamente. ¡Gracias por tu compra!']);
        $conn->close();
    } catch (Exception $e) {
        $conn->rollback();
        error_log('Error en checkout.php (AJAX): ' . $e->getMessage());
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
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

// Generar token CSRF
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pasarela de Pago - PIL Andina</title>
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
            --bnb-yellow: #FFC107;
            --bnb-blue: #003087;
            --bnb-white: #FFFFFF;
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
            color: white;
        }

        .cart-floating:hover {
            transform: translateY(-3px) scale(1.05);
            box-shadow: 0 15px 50px rgba(41, 128, 185, 0.6);
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

        .payment-tab {
            background: rgba(255, 255, 255, 0.1);
            border: 2px solid var(--primary-blue);
            color: var(--primary-blue);
            font-weight: 600;
            padding: 10px 20px;
            border-radius: 10px;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .payment-tab.active {
            background: linear-gradient(135deg, var(--primary-blue), var(--primary-medium));
            color: white;
            box-shadow: 0 4px 15px rgba(41, 128, 185, 0.3);
        }

        .payment-tab:hover {
            background: var(--hover-light);
            transform: translateY(-2px);
        }

        .qr-container {
            background: var(--bnb-white);
            border: 2px solid var(--bnb-blue);
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 8px 20px rgba(0, 48, 135, 0.2);
        }

        .qr-button {
            background: linear-gradient(135deg, var(--bnb-yellow), #ffb300);
            color: var(--bnb-blue);
            border: none;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
            border-radius: 10px;
            padding: 6px 12px;
            font-weight: 600;
        }

        .qr-button::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.4), transparent);
            transition: left 0.5s;
        }

        .qr-button:hover::before {
            left: 100%;
        }

        .qr-button:hover {
            background: linear-gradient(135deg, #ffb300, var(--bnb-yellow));
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(255, 193, 7, 0.4);
        }

        .card-input {
            background: rgba(255, 255, 255, 0.8);
            border: 2px solid var(--primary-blue);
            border-radius: 8px;
            padding: 10px;
            color: var(--primary-blue);
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .card-input:focus {
            outline: none;
            border-color: var(--primary-medium);
            box-shadow: 0 0 10px rgba(41, 128, 185, 0.3);
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

        header a, header span, footer a, footer span, footer p {
            color: #1a202c !important;
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
                        <img src="../views/logo/pil.svg" alt="PIL Andina Logo" class="w-20 h-20 object-contain" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
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
                    <a href="carrito.php" class="cart-floating px-8 py-4 rounded-2xl font-bold shadow-xl hover:shadow-2xl transition-all">
                        <i class="fas fa-shopping-cart mr-3 text-lg"></i>
                        Mi Carrito
                        <span id="cartCount" class="ml-3 bg-white px-3 py-1 rounded-full text-sm font-bold <?php echo count($cartItems) > 0 ? '' : 'hidden'; ?>"><?php echo array_sum(array_column($cartItems, 'cantidad')); ?></span>
                    </a>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <div class="max-w-6xl mx-auto px-4 py-8">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Resumen del Carrito -->
            <div class="glass-strong rounded-2xl p-6">
                <div class="flex items-center justify-between mb-6">
                    <h1 class="text-3xl font-bold text-primary-blue">Resumen de tu Compra</h1>
                    <div class="w-12 h-12 bg-gradient-to-br from-primary-soft to-primary-light rounded-full flex items-center justify-center">
                        <i class="fas fa-receipt text-primary-blue text-xl"></i>
                    </div>
                </div>
                <div class="space-y-4 mb-6">
                    <?php if (empty($cartItems)): ?>
                        <div class="text-center py-10">
                            <i class="fas fa-shopping-cart text-primary-blue text-4xl mb-4 opacity-50"></i>
                            <h3 class="text-lg font-bold text-black mb-2">Tu carrito está vacío</h3>
                            <p class="text-black/70 mb-4">¡Explora nuestros productos lácteos premium!</p>
                            <a href="comprador.php" class="btn-pil px-6 py-3 rounded-xl font-medium">
                                <i class="fas fa-store mr-2"></i>Seguir Comprando
                            </a>
                        </div>
                    <?php else: ?>
                        <?php foreach ($cartItems as $index => $item): ?>
                            <div class="flex items-center space-x-4 py-2 animate__animated animate__fadeInUp" style="animation-delay: <?php echo $index * 0.1; ?>s;">
                                <div class="product-image-container">
                                    <?php if ($item['foto']): ?>
                                        <img src="<?php echo htmlspecialchars($item['foto']); ?>" alt="<?php echo htmlspecialchars($item['nombre_producto']); ?>" class="product-image">
                                    <?php else: ?>
                                        <div class="flex items-center justify-center h-full bg-gradient-to-br from-primary-soft to-white">
                                            <i class="fas fa-cow text-primary-blue text-xl"></i>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="flex-1">
                                    <h3 class="text-md font-semibold text-black"><?php echo $item['nombre_producto']; ?></h3>
                                    <p class="text-sm text-black/70">Bs. <?php echo number_format($item['precio'], 2); ?> x <?php echo $item['cantidad']; ?></p>
                                </div>
                                <div class="text-right">
                                    <span class="price-tag font-bold">Bs. <?php echo number_format($item['precio'] * $item['cantidad'], 2); ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        <div class="flex justify-between text-lg font-semibold border-t border-gray-200 pt-4">
                            <span class="text-black">Total:</span>
                            <span id="cartTotal" class="price-tag">Bs. <?php echo number_format($total, 2); ?></span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Métodos de Pago -->
            <div class="glass-strong rounded-2xl p-6">
                <h2 class="text-3xl font-bold text-primary-blue mb-6">Selecciona un Método de Pago</h2>
                <div class="flex space-x-4 mb-6">
                    <button class="payment-tab flex-1 active" data-tab="qr">QR</button>
                    <button class="payment-tab flex-1" data-tab="card">Tarjeta</button>
                    <button class="payment-tab flex-1" data-tab="cash">Efectivo</button>
                </div>
                <div id="payment-content">
                    <!-- Pago con QR -->
                    <div id="qr" class="payment-content active">
                        <div class="qr-container text-center">
                            <div class="flex justify-center mb-4">
                                <img src="https://images.unsplash.com/photo-1622553771961-2f1e73e74294" alt="BNB Logo" class="w-24 h-24 object-contain" onerror="this.style.display='none';">
                            </div>
                            <h3 class="text-xl font-bold text-bnb-blue mb-4">Paga con QR - Banco Nacional de Bolivia</h3>
                            <img src="../views/logo/QR.jpg" alt="Código QR Placeholder" class="w-80 h-100 mx-auto mb-4 rounded-lg border-4 border-bnb-yellow shadow-lg" id="qrPlaceholder">
                            <p class="text-bnb-blue font-medium mb-2">Escanea este código con la app BNB Móvil</p>
                            <p class="text-bnb-blue/70 text-sm mb-4">Válido por <span id="qrTimer">5:00</span> minutos</p>
                            <div class="glass-card p-4 mb-4 bg-bnb-white/80">
                                <p class="text-bnb-blue font-semibold">Monto: Bs. <?php echo number_format($total, 2); ?></p>
                                <p class="text-bnb-blue/70 text-sm">Beneficiario: Fabrizio Aliaga</p>
                                <p class="text-bnb-blue/70 text-sm">Referencia: Compra #<?php echo time(); ?></p>
                            </div>
                            <button id="payButtonQR" class="qr-button w-full py-3 px-6 rounded-xl font-bold text-lg flex items-center justify-center space-x-2" data-method="QR">
                                <i class="fas fa-check-circle mr-2"></i>
                                <span>Pagado</span>
                            </button>
                        </div>
                    </div>
                    <!-- Pago con Tarjeta -->
                    <div id="card" class="payment-content hidden">
                        <form id="cardForm" class="space-y-4">
                            <div>
                                <label for="cardNumber" class="block text-sm font-medium text-black mb-1">Número de Tarjeta</label>
                                <input type="text" id="cardNumber" class="card-input w-full text-sm" placeholder="1234 5678 9012 3456" maxlength="19" required>
                            </div>
                            <div>
                                <label for="cardName" class="block text-sm font-medium text-black mb-1">Nombre en la Tarjeta</label>
                                <input type="text" id="cardName" class="card-input w-full text-sm" placeholder="Juan Pérez" required>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label for="cardExpiry" class="block text-sm font-medium text-black mb-1">Fecha de Expiración</label>
                                    <input type="text" id="cardExpiry" class="card-input w-full text-sm" placeholder="MM/AA" maxlength="5" required>
                                </div>
                                <div>
                                    <label for="cardCVV" class="block text-sm font-medium text-black mb-1">CVV</label>
                                    <input type="text" id="cardCVV" class="card-input w-full text-sm" placeholder="123" maxlength="4" required>
                                </div>
                            </div>
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                            <button type="submit" id="payButtonCard" class="btn-pil w-full py-3 px-6 rounded-xl font-bold text-lg flex items-center justify-center space-x-2" data-method="Tarjeta">
                                <i class="fas fa-credit-card mr-2"></i>
                                <span>Pagado</span>
                            </button>
                        </form>
                    </div>
                    <!-- Pago en Efectivo -->
                    <div id="cash" class="payment-content hidden">
                        <div class="glass-card p-6 text-center">
                            <h3 class="text-xl font-bold text-primary-blue mb-4">Pago en Efectivo</h3>
                            <p class="text-black font-medium mb-4">Paga tu pedido en cualquier sucursal de PIL Andina</p>
                            <p class="text-black/70 text-sm mb-4">Presenta este código en caja:</p>
                            <div class="glass-card p-4 mb-4">
                                <p class="text-black font-bold text-lg">Código: #<?php echo time(); ?></p>
                                <p class="text-black font-semibold">Monto: Bs. <?php echo number_format($total, 2); ?></p>
                            </div>
                            <button id="payButtonCash" class="btn-pil w-full py-3 px-6 rounded-xl font-bold text-lg flex items-center justify-center space-x-2" data-method="Efectivo">
                                <i class="fas fa-money-bill-wave mr-2"></i>
                                <span>Confirmar Pedido</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Éxito -->
    <div id="cartSuccessModal" class="cart-success-modal">
        <div class="cart-success-content text-center">
            <i class="fas fa-check-circle text-green-500 text-4xl mb-4 animate__animated animate__bounceIn"></i>
            <h3 class="text-xl font-bold text-primary-blue mb-2">¡Pago Exitoso!</h3>
            <p id="cartSuccessMessage" class="text-black font-medium text-base mb-4"></p>
            <button onclick="window.location.href='comprador.php'" class="btn-pil inline-flex items-center px-6 py-2 rounded-xl font-medium">
                <i class="fas fa-home mr-2"></i>Continuar Comprando
            </button>
        </div>
    </div>

    <!-- Modal de Error -->
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

    <script>
        // Temporizador para QR
        function startQRTimer() {
            let time = 5 * 60; // 5 minutos en segundos
            const timerElement = document.getElementById('qrTimer');
            const interval = setInterval(() => {
                const minutes = Math.floor(time / 60);
                const seconds = time % 60;
                timerElement.textContent = `${minutes}:${seconds < 10 ? '0' : ''}${seconds}`;
                time--;
                if (time < 0) {
                    clearInterval(interval);
                    timerElement.textContent = 'Expirado';
                    document.getElementById('payButtonQR').disabled = true;
                }
            }, 1000);
        }

        // Validación de formulario de tarjeta
        function validateCardForm() {
            const cardNumber = document.getElementById('cardNumber').value.replace(/\s/g, '');
            const cardName = document.getElementById('cardName').value;
            const expiry = document.getElementById('cardExpiry').value;
            const cvv = document.getElementById('cardCVV').value;

            if (!/^\d{16}$/.test(cardNumber)) {
                showCartError('Número de tarjeta inválido (16 dígitos).');
                return false;
            }
            if (!/^[A-Za-z\s]+$/.test(cardName)) {
                showCartError('Nombre inválido.');
                return false;
            }
            if (!/^(0[1-9]|1[0-2])\/\d{2}$/.test(expiry)) {
                showCartError('Fecha de expiración inválida (MM/AA).');
                return false;
            }
            if (!/^\d{3,4}$/.test(cvv)) {
                showCartError('CVV inválido (3-4 dígitos).');
                return false;
            }
            return true;
        }

        // Formatear número de tarjeta
        document.getElementById('cardNumber')?.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            value = value.match(/.{1,4}/g)?.join(' ') || value;
            e.target.value = value.slice(0, 19);
        });

        // Formatear fecha de expiración
        document.getElementById('cardExpiry')?.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length > 2) {
                value = value.slice(0, 2) + '/' + value.slice(2);
            }
            e.target.value = value.slice(0, 5);
        });

        // Mostrar modal de éxito
        function showCartSuccess(message) {
            const modal = document.getElementById('cartSuccessModal');
            const messageElement = document.getElementById('cartSuccessMessage');
            messageElement.textContent = message;
            modal.classList.add('show');
        }

        // Mostrar modal de error
        function showCartError(message) {
            const modal = document.getElementById('cartErrorModal');
            const messageElement = document.getElementById('cartErrorMessage');
            messageElement.textContent = message;
            modal.classList.add('show');
            setTimeout(() => {
                modal.classList.remove('show');
            }, 3000);
        }

        // Cerrar modal de error
        function closeCartError() {
            document.getElementById('cartErrorModal').classList.remove('show');
        }

        // Cambiar entre pestañas de pago
        document.querySelectorAll('.payment-tab').forEach(tab => {
            tab.addEventListener('click', () => {
                document.querySelectorAll('.payment-tab').forEach(t => t.classList.remove('active'));
                document.querySelectorAll('.payment-content').forEach(c => c.classList.add('hidden'));
                tab.classList.add('active');
                document.getElementById(tab.dataset.tab).classList.remove('hidden');
                if (tab.dataset.tab === 'qr') {
                    startQRTimer();
                }
            });
        });

        // Procesar pago
        function processPayment(method) {
            if (method === 'Tarjeta' && !validateCardForm()) {
                return;
            }

            const formData = new FormData();
            formData.append('action', 'process_payment');
            formData.append('payment_method', method);
            formData.append('csrf_token', '<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>');

            fetch('checkout.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showCartSuccess(data.message);
                    updateCartBadge();
                } else {
                    showCartError(data.message);
                }
            })
            .catch(error => {
                console.error('Error en AJAX:', error);
                showCartError('Error en la conexión con el servidor.');
            });
        }

        // Actualizar badge del carrito
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

        // Event Listeners
        document.addEventListener('DOMContentLoaded', function() {
            startQRTimer();
            updateCartBadge();

            document.getElementById('payButtonQR')?.addEventListener('click', () => processPayment('QR'));
            document.getElementById('payButtonCash')?.addEventListener('click', () => processPayment('Efectivo'));

            document.getElementById('cardForm')?.addEventListener('submit', (e) => {
                e.preventDefault();
                processPayment('Tarjeta');
            });

            const particles = document.querySelectorAll('.particle');
            particles.forEach((particle, index) => {
                particle.style.top = Math.random() * 100 + '%';
                particle.style.animationDuration = (Math.random() * 3 + 3) + 's';
                particle.style.animationDelay = Math.random() * 2 + 's';
            });

            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    closeCartError();
                }
            });
        });
    </script>
</body>
</html>
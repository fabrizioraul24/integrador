<?php
session_start();

// Habilitar errores para depuración
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
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

$userId = $_SESSION['id_usuario'] ?? null;
$cartItems = [];

if ($userId) {
    $sql = "SELECT c.id_carrito, p.id_producto, p.nombre_producto, p.precio, c.cantidad 
            FROM carrito c 
            JOIN productos p ON c.id_producto = p.id_producto 
            WHERE c.id_usuario = ? AND c.fecha_agregado > DATE_SUB(NOW(), INTERVAL 30 MINUTE)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $cartItems[] = $row;
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Carrito - PIL Premium</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary: #a9cce3;
            --secondary: #5499c7;
            --accent: #7fb3d5;
        }
        body {
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            font-family: 'Inter', sans-serif;
        }
    </style>
</head>
<body class="p-6">
    <div class="max-w-4xl mx-auto">
        <h1 class="text-3xl font-bold mb-6" style="color: var(--secondary);">Tu Carrito</h1>
        <div id="cartItems" class="space-y-4">
            <?php if (empty($cartItems)): ?>
                <p class="text-gray-500 mt-4">Tu carrito está vacío.</p>
            <?php else: ?>
                <?php foreach ($cartItems as $item): ?>
                    <div class="p-4 bg-white rounded-lg shadow-md flex justify-between items-center">
                        <span><?php echo htmlspecialchars($item['nombre_producto']); ?> - Cantidad: <?php echo $item['cantidad']; ?> - Total: $<?php echo number_format($item['precio'] * $item['cantidad'], 2); ?></span>
                        <button onclick="removeFromCart(<?php echo $item['id_carrito']; ?>)" class="text-red-500 hover:text-red-700">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <a href="comprador.php" class="btn-outline px-4 py-2 rounded-lg text-sm font-medium mt-6">Seguir Comprando</a>
    </div>

    <script>
        function removeFromCart(cartId) {
            if (confirm('¿Estás seguro de eliminar este producto del carrito?')) {
                const xhr = new XMLHttpRequest();
                xhr.open("POST", "remove_from_cart.php", true);
                xhr.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
                xhr.onreadystatechange = function() {
                    if (xhr.readyState == 4 && xhr.status == 200) {
                        location.reload();
                    }
                };
                xhr.send("cart_id=" + cartId);
            }
        }
    </script>
</body>
</html>
<?php $conn->close(); ?>
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

$sql = "SELECT id_producto, nombre_producto, precio, cantidad, foto FROM productos WHERE deleted = 0";
$result = $conn->query($sql);

$products = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $products[] = [
            'id' => $row['id_producto'],
            'name' => htmlspecialchars($row['nombre_producto']),
            'price' => floatval($row['precio']),
            'stock' => intval($row['cantidad']),
            'image' => !empty($row['foto']) ? "Uploads/" . basename($row['foto']) : null
        ];
    }
} else {
    die("Error en la consulta: " . $conn->connect_error);
}
$products_json = json_encode($products, JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS);

// Manejo de logout
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: comprador.php");
    exit();
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
    $stmt->execute();
    $stmt->close();
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

if (isset($_POST['add_to_cart'])) {
    $userId = $_SESSION['id_usuario'] ?? null;
    $productId = $_POST['product_id'];
    $quantity = $_POST['quantity'];

    if ($userId && $quantity > 0 && $quantity <= getProductStock($conn, $productId)) {
        addToCartDB($conn, $userId, $productId, $quantity);
        header("Location: comprador.php");
        exit();
    } else {
        echo "<script>alert('Error al agregar al carrito.');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PIL - Productos Lácteos Premium</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #a9cce3;
            --secondary: #5499c7;
            --accent: #7fb3d5;
        }

        * {
            font-family: 'Inter', sans-serif;
        }

        body {
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            min-height: 100vh;
        }

        .glass-subtle {
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.5);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        }

        .product-card {
            background: white;
            border: 1px solid #e2e8f0;
            transition: all 0.3s ease;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
        }

        .product-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.12);
            border-color: var(--primary);
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--secondary), var(--accent));
            color: white;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, var(--accent), var(--secondary));
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(84, 153, 199, 0.3);
        }

        .btn-outline {
            border: 2px solid var(--primary);
            color: var(--secondary);
            background: transparent;
            transition: all 0.3s ease;
        }

        .btn-outline:hover {
            background: var(--primary);
            color: white;
        }

        .filter-btn {
            background: white;
            border: 1px solid #e2e8f0;
            color: #64748b;
            transition: all 0.3s ease;
        }

        .filter-btn.active {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
        }

        .cart-float {
            background: linear-gradient(135deg, var(--secondary), var(--accent));
            box-shadow: 0 8px 25px rgba(84, 153, 199, 0.4);
            transition: all 0.3s ease;
        }

        .cart-float:hover {
            transform: translateY(-1px);
            box-shadow: 0 12px 30px rgba(84, 153, 199, 0.6);
        }

        .product-image-container {
            height: 280px;
            background: #f8fafc;
            border-radius: 12px;
            overflow: hidden;
            position: relative;
        }

        .product-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
        }

        .product-card:hover .product-image {
            transform: scale(1.05);
        }

        .search-input {
            background: white;
            border: 2px solid #e2e8f0;
            transition: all 0.3s ease;
        }

        .search-input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(169, 204, 227, 0.1);
            outline: none;
        }

        .quantity-input {
            width: 100px;
            padding: 8px 12px;
            border: none;
            border-radius: 20px;
            text-align: center;
            background: linear-gradient(135deg, var(--secondary), var(--accent));
            color: white;
            font-weight: 500;
            box-shadow: 0 4px 12px rgba(84, 153, 199, 0.3);
            transition: all 0.3s ease;
        }

        .quantity-input:focus {
            outline: none;
            transform: scale(1.05);
            box-shadow: 0 6px 15px rgba(84, 153, 199, 0.5);
        }

        .quantity-input::-webkit-inner-spin-button,
        .quantity-input::-webkit-outer-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="glass-subtle border-b border-gray-200 sticky top-0 z-40">
        <div class="max-w-7xl mx-auto px-4 py-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <div class="w-20 h-20 bg-gradient-to-br from-blue-100 to-blue-200 rounded-xl flex items-center justify-center">
                        <img src="../views/logo/pil.svg" alt="PIL Logo" class="w-40 h-40 object-contain" 
                             onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                        <i class="fas fa-cow text-2xl hidden" style="color: var(--secondary);"></i>
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold" style="color: var(--secondary);">PIL Compras</h1>
                        <p class="text-gray-600 text-sm">Productos Lácteos de Calidad</p>
                    </div>
                </div>
                
                <div class="flex items-center space-x-4">
                    <?php if (isset($_SESSION['nombre'])): ?>
                        <div class="flex items-center space-x-4">
                            <span class="text-sm font-medium" style="color: var(--secondary);">Bienvenido, <?php echo htmlspecialchars($_SESSION['nombre'] ?? 'Usuario'); ?></span>
                            <a href="?logout=1" class="btn-outline px-4 py-2 rounded-lg text-sm font-medium">Salir</a>
                        </div>
                    <?php else: ?>
                        <a href="login.php" class="btn-outline px-4 py-2 rounded-lg text-sm font-medium">Iniciar Sesión</a>
                    <?php endif; ?>
                    <a href="carrito.php" 
                       class="cart-float text-white px-6 py-3 rounded-xl font-medium shadow-lg hover:shadow-xl transition-all">
                        <i class="fas fa-shopping-cart mr-2"></i>
                        Ver Carrito
                        <span id="cartCount" class="ml-2 bg-white/20 px-2 py-1 rounded-full text-sm hidden">0</span>
                    </a>
                </div>
            </div>
        </div>
    </header>

    <div class="max-w-7xl mx-auto px-4 py-8">
        <!-- Filtros y Búsqueda -->
        <div class="mb-8">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 mb-6">
                <div class="flex-1 max-w-md">
                    <div class="relative">
                        <i class="fas fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                        <input type="text" id="searchInput" placeholder="Buscar productos..." 
                               class="search-input w-full pl-10 pr-4 py-3 rounded-xl">
                    </div>
                </div>
                
                <div class="flex flex-wrap gap-2">
                    <button onclick="filterProducts('precio-asc')" class="filter-btn px-4 py-2 rounded-lg text-sm font-medium">
                        <i class="fas fa-sort-amount-up mr-1"></i> Precio ↑
                    </button>
                    <button onclick="filterProducts('precio-desc')" class="filter-btn px-4 py-2 rounded-lg text-sm font-medium">
                        <i class="fas fa-sort-amount-down mr-1"></i> Precio ↓
                    </button>
                    <button onclick="filterProducts('stock')" class="filter-btn px-4 py-2 rounded-lg text-sm font-medium">
                        <i class="fas fa-boxes mr-1"></i> En Stock
                    </button>
                </div>
            </div>
        </div>

        <!-- Productos -->
        <div id="productsList" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
            <!-- Productos se cargan con JavaScript -->
        </div>

        <!-- Sin resultados -->
        <div id="noResults" class="text-center py-12 hidden">
            <div class="text-6xl mb-4 opacity-30">
                <i class="fas fa-search"></i>
            </div>
            <h3 class="text-xl font-semibold text-gray-600 mb-2">No se encontraron productos</h3>
            <p class="text-gray-500">Intenta con otros términos de búsqueda o filtros</p>
        </div>
    </div>

    <script>
        const products = <?php echo $products_json; ?>;
        let filteredProducts = [...products];

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
            
            productsToShow.forEach(product => {
                const card = document.createElement('div');
                card.className = 'product-card rounded-2xl p-6 hover:scale-[1.02] transition-all duration-300';
                
                card.innerHTML = `
                    <div class="product-image-container mb-4">
                        ${product.image ? 
                            `<img src="${product.image}" alt="${product.name}" class="product-image">` : 
                            `<div class="flex items-center justify-center h-full text-gray-400">
                                <i class="fas fa-image text-4xl"></i>
                            </div>`
                        }
                        ${product.stock === 0 ? '<div class="absolute inset-0 bg-black/50 flex items-center justify-center rounded-xl"><span class="text-white font-bold">SIN STOCK</span></div>' : ''}
                    </div>
                    
                    <div>
                        <h3 class="font-semibold text-lg text-gray-800 mb-2">${product.name}</h3>
                        <div class="flex justify-between items-center mb-3">
                            <span class="text-2xl font-bold" style="color: var(--secondary);">$${product.price.toFixed(2)}</span>
                            <span class="text-sm ${product.stock > 5 ? 'text-green-600' : product.stock > 0 ? 'text-yellow-600' : 'text-red-600'}">
                                <i class="fas fa-box mr-1"></i>
                                ${product.stock} disponibles
                            </span>
                        </div>
                        
                        <form method="POST" action="" class="flex items-center space-x-2">
                            <input type="number" name="quantity" id="quantity-${product.id}" class="quantity-input" min="1" max="${product.stock}" value="1" onchange="validateQuantity(${product.id}, ${product.stock})">
                            <input type="hidden" name="product_id" value="${product.id}">
                            <button type="submit" name="add_to_cart" 
                                    class="btn-primary py-3 px-6 rounded-xl font-medium shadow-md w-full ${product.stock === 0 ? 'opacity-50 cursor-not-allowed' : ''}" 
                                    ${product.stock === 0 ? 'disabled' : ''}>
                                <i class="fas fa-cart-plus mr-2"></i>
                                ${product.stock === 0 ? 'Sin Stock' : 'Agregar al Carrito'}
                            </button>
                        </form>
                    </div>
                `;
                
                list.appendChild(card);
            });
        }

        function validateQuantity(productId, maxStock) {
            const input = document.getElementById(`quantity-${productId}`);
            if (input.value > maxStock) {
                input.value = maxStock;
            } else if (input.value < 1) {
                input.value = 1;
            }
        }

        function filterProducts(type) {
            document.querySelectorAll('.filter-btn').forEach(btn => {
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
            }
            
            displayProducts(sorted);
        }

        function applyFilters() {
            let filtered = [...products];
            
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            if (searchTerm) {
                filtered = filtered.filter(product => 
                    product.name.toLowerCase().includes(searchTerm)
                );
            }
            
            filteredProducts = filtered;
            displayProducts();
        }

        function updateCartBadge() {
            const badge = document.getElementById('cartCount');
            const userId = <?php echo isset($_SESSION['id_usuario']) ? $_SESSION['id_usuario'] : 'null'; ?>;
            if (userId) {
                const sql = "SELECT SUM(cantidad) as total FROM carrito WHERE id_usuario = ? AND fecha_agregado > DATE_SUB(NOW(), INTERVAL 30 MINUTE)";
                const stmt = new XMLHttpRequest();
                stmt.open("POST", "get_cart_count.php", true);
                stmt.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
                stmt.onreadystatechange = function() {
                    if (stmt.readyState == 4 && stmt.status == 200) {
                        const total = parseInt(stmt.responseText) || 0;
                        if (total > 0) {
                            badge.textContent = total;
                            badge.classList.remove('hidden');
                        } else {
                            badge.classList.add('hidden');
                        }
                    }
                };
                stmt.send("user_id=" + userId);
            }
        }

        document.getElementById('searchInput').addEventListener('input', applyFilters);

        document.addEventListener('DOMContentLoaded', function() {
            displayProducts();
            updateCartBadge();
        });
    </script>
</body>
</html>
<?php $conn->close(); ?>
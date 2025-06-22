<?php
// Backend dentro del mismo archivo
session_start();

// Conexión a la base de datos (ajusta estos valores)
$host = '127.0.0.1';
$user = 'root';      // Cambia según tu configuración
$pass = '';          // Contraseña de MariaDB
$db = 'noah';        // Base de datos
$port = 3308;        // Puerto de MariaDB

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$db", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Obtener todos los productos (activos por defecto, opción para inactivos)
    $show_deleted = isset($_GET['show_deleted']) && $_GET['show_deleted'] === 'true';
    $query = $show_deleted 
        ? "SELECT id_producto, id_categoria, nombre_producto, tipo_de_presentacion, descripcion, cantidad, precio, foto FROM productos"
        : "SELECT id_producto, id_categoria, nombre_producto, tipo_de_presentacion, descripcion, cantidad, precio, foto FROM productos WHERE deleted_at IS NULL";
    $stmt = $pdo->query($query);
    $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Ajustar ruta de fotos y manejar errores
    foreach ($productos as &$producto) {
        $imagePath = 'views/upload/' . $producto['foto'];
        $producto['foto'] = file_exists($imagePath) 
            ? $imagePath 
            : 'https://via.placeholder.com/300x300?text=Sin+Imagen';
    }

    // Categorías únicas (usar id_categoria como placeholder)
    $categorias = array_unique(array_column($productos, 'id_categoria'));
} catch (PDOException $e) {
    $productos = [];
    $categorias = [];
    $error_msg = "Error de conexión: " . htmlspecialchars($e->getMessage());
    echo "<script>alert('$error_msg');</script>";
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pil Andina - Dashboard de Vendedor</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.1.0/fonts/remixicon.css" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#4e6baf',
                        'primary-dark': '#3a5186',
                        'primary-light': '#86acd4',
                        panel: '#f8fafc',
                        accent: '#42568b',
                        'text-primary': '#1e293b',
                        'text-secondary': '#64748b',
                    },
                    animation: {
                        'fade-in': 'fadeIn 0.6s ease-in-out',
                        'slide-up': 'slideUp 0.5s ease-out',
                        'scale-in': 'scaleIn 0.4s ease-out',
                        'pulse-glow': 'pulseGlow 2s infinite',
                        'float': 'float 3s ease-in-out infinite',
                        'glow': 'glow 1.5s ease-in-out infinite',
                        'ripple': 'ripple 0.6s linear',
                    },
                    keyframes: {
                        fadeIn: {
                            '0%': { opacity: '0', transform: 'translateY(20px)' },
                            '100%': { opacity: '1', transform: 'translateY(0)' },
                        },
                        slideUp: {
                            '0%': { opacity: '0', transform: 'translateY(30px)' },
                            '100%': { opacity: '1', transform: 'translateY(0)' },
                        },
                        scaleIn: {
                            '0%': { transform: 'scale(0.95)', opacity: '0' },
                            '100%': { transform: 'scale(1)', opacity: '1' },
                        },
                        pulseGlow: {
                            '0%, 100%': { boxShadow: '0 0 15px rgba(78, 107, 175, 0.3)' },
                            '50%': { boxShadow: '0 0 25px rgba(78, 107, 175, 0.6)' },
                        },
                        float: {
                            '0%, 100%': { transform: 'translateY(0)' },
                            '50%': { transform: 'translateY(-10px)' },
                        },
                        glow: {
                            '0%, 100%': { boxShadow: '0 0 5px rgba(78, 107, 175, 0.3)' },
                            '50%': { boxShadow: '0 0 15px rgba(78, 107, 175, 0.6)' },
                        },
                        ripple: {
                            '0%': { transform: 'scale(0)', opacity: '0.7' },
                            '100%': { transform: 'scale(4)', opacity: '0' },
                        }
                    }
                }
            }
        }
    </script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
        
        * {
            font-family: 'Inter', sans-serif;
        }
        
        .glass-effect {
            backdrop-filter: blur(12px);
            background: rgba(255, 255, 255, 0.25);
            border: 1px solid rgba(255, 255, 255, 0.5);
        }
        
        .card-hover {
            transition: all 0.5s cubic-bezier(0.25, 0.8, 0.25, 1);
        }
        
        .card-hover:hover {
            transform: translateY(-15px) scale(1.03);
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.3);
        }
        
        .scrollbar-thin::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }
        
        .scrollbar-thin::-webkit-scrollbar-track {
            background: #f1f5f9;
            border-radius: 4px;
        }
        
        .scrollbar-thin::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 4px;
            border: 2px solid #f8fafc;
        }
        
        .scrollbar-thin::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }
        
        .category-badge {
            position: absolute;
            top: 1.5rem;
            left: 1.5rem;
            background: rgba(78, 107, 175, 0.95);
            color: white;
            padding: 0.5rem 1.25rem;
            border-radius: 9999px;
            font-size: 0.875rem;
            font-weight: 600;
            text-transform: uppercase;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }
        
        .search-input:focus ~ .search-icon {
            color: #4e6baf;
            text-shadow: 0 0 10px rgba(78, 107, 175, 0.5);
        }
        
        .price-glow {
            animation: glow 1.5s ease-in-out infinite;
        }
        
        .ripple-effect {
            position: relative;
            overflow: hidden;
        }
        
        .ripple-effect::after {
            content: '';
            position: absolute;
            width: 150px;
            height: 150px;
            background: rgba(255, 255, 255, 0.4);
            border-radius: 50%;
            transform: scale(0);
            pointer-events: none;
        }
        
        .ripple-effect:active::after {
            animation: ripple 0.6s linear;
        }
        
        .range-ticks {
            position: relative;
            margin-top: 0.5rem;
        }
        
        .range-ticks::before {
            content: '';
            position: absolute;
            top: -0.25rem;
            left: 0;
            width: 100%;
            height: 2px;
            background: #e2e8f0;
        }
        
        .range-ticks::after {
            content: '';
            position: absolute;
            top: -0.5rem;
            left: 0;
            width: 100%;
            height: 8px;
            background: linear-gradient(to right, #e2e8f0 0%, #e2e8f0 20%, #4e6baf 20%, #4e6baf 40%, #e2e8f0 40%, #e2e8f0 60%, #4e6baf 60%, #4e6baf 80%, #e2e8f0 80%, #e2e8f0 100%);
            background-size: 12.5%;
        }
    </style>
</head>
<body class="bg-panel min-h-screen">
    <!-- Navigation -->
    <nav class="fixed top-0 left-0 w-full bg-panel shadow-lg z-50 glass-effect">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-20">
                <!-- Logo -->
                <div class="flex items-center">
                    <div class="relative">
                        <div class="w-12 h-12 bg-gradient-to-br from-primary to-primary-dark rounded-full flex items-center justify-center shadow-lg animate-pulse-glow">
                            <i class="fas fa-industry text-white text-xl"></i>
                        </div>
                        <div class="absolute -top-1 -right-1 w-4 h-4 bg-green-500 rounded-full animate-bounce-light"></div>
                    </div>
                    <a href="landing.php" class="ml-4 text-3xl font-bold text-text-primary tracking-wide">
                        Pil<span class="text-primary">Andina</span>
                    </a>
                </div>

                <!-- Menu Button (Mobile) -->
                <div class="sm:hidden flex items-center space-x-4">
                    <button id="filter-toggle" class="text-text-secondary hover:text-primary transition-colors">
                        <i class="ri-filter-3-line text-3xl"></i>
                    </button>
                    <button id="menu-btn" class="text-text-secondary hover:text-primary transition-colors">
                        <i class="ri-menu-line text-3xl"></i>
                    </button>
                </div>

                <!-- Nav Links -->
                <div class="hidden sm:flex items-center space-x-8">
                    <ul class="flex space-x-8 nav__links">
                        <li><a href="landing.php#home" class="text-text-secondary hover:text-primary font-semibold transition-colors">Inicio</a></li>
                        <li><a href="#products" class="text-text-secondary hover:text-primary font-semibold transition-colors">Productos</a></li>
                        <li><a href="landing.php#chef" class="text-text-secondary hover:text-primary font-semibold transition-colors">Quienes Somos</a></li>
                        <li><a href="landing.php#contact" class="text-text-secondary hover:text-primary font-semibold transition-colors">Contáctanos</a></li>
                    </ul>
                    <a href="carrito.php" class="relative">
                        <button class="text-text-secondary hover:text-primary ripple-effect transition-colors">
                            <i class="ri-shopping-cart-line text-3xl"></i>
                            <span id="cart-count" class="absolute -top-2 -right-2 bg-primary text-white text-sm rounded-full w-6 h-6 flex items-center justify-center animate-scale-in">0</span>
                        </button>
                    </a>
                    <a href="logout.php">
                        <button class="bg-primary hover:bg-primary-dark text-white font-semibold py-3 px-6 rounded-xl flex items-center ripple-effect transition-all duration-300">
                            <i class="ri-logout-box-line mr-2"></i> Cerrar Sesión
                        </button>
                    </a>
                    <a href="?show_deleted=true" class="text-text-secondary hover:text-primary font-semibold transition-colors">
                        <i class="ri-eye-off-line mr-2"></i> Ver Eliminados
                    </a>
                </div>
            </div>
            <!-- Mobile Menu -->
            <div id="nav-links" class="hidden sm:hidden bg-panel shadow-lg">
                <ul class="flex flex-col space-y-6 p-6">
                    <li><a href="landing.php#home" class="text-text-secondary hover:text-primary font-semibold transition-colors">Inicio</a></li>
                    <li><a href="#products" class="text-text-secondary hover:text-primary font-semibold transition-colors">Productos</a></li>
                    <li><a href="landing.php#chef" class="text-text-secondary hover:text-primary font-semibold transition-colors">Quienes Somos</a></li>
                    <li><a href="landing.php#contact" class="text-text-secondary hover:text-primary font-semibold transition-colors">Contáctanos</a></li>
                    <li>
                        <a href="carrito.php" class="block text-text-secondary hover:text-primary font-semibold transition-colors">
                            <i class="ri-shopping-cart-line mr-2"></i> Carrito (<span id="cart-count-mobile">0</span>)
                        </a>
                    </li>
                    <li>
                        <a href="logout.php" class="block bg-primary hover:bg-primary-dark text-white font-semibold py-3 px-6 rounded-xl text-center transition-all duration-300">
                            <i class="ri-logout-box-line mr-2"></i> Cerrar Sesión
                        </a>
                    </li>
                    <li>
                        <a href="?show_deleted=true" class="block text-text-secondary hover:text-primary font-semibold transition-colors">
                            <i class="ri-eye-off-line mr-2"></i> Ver Eliminados
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Filters Sidebar (Mobile) -->
    <div id="filter-sidebar" class="fixed inset-y-0 left-0 w-72 bg-panel shadow-lg z-40 transform -translate-x-full transition-transform duration-300 sm:hidden">
        <div class="p-6">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-xl font-semibold text-text-primary">Filtros</h3>
                <button id="close-filter" class="text-text-secondary hover:text-primary transition-colors">
                    <i class="ri-close-line text-3xl"></i>
                </button>
            </div>
            <div class="space-y-6">
                <div>
                    <label class="block text-text-secondary text-base mb-2 flex items-center">
                        <i class="ri-filter-fill mr-2 text-primary"></i> Categoría
                    </label>
                    <select id="category-filter-mobile" class="w-full px-5 py-3 rounded-xl border border-gray-200 focus:outline-none focus:ring-2 focus:ring-primary text-text-primary transition-all duration-300">
                        <option value="">Todas las Categorías</option>
                        <?php foreach ($categorias as $categoria): ?>
                            <option value="<?= htmlspecialchars($categoria) ?>"><?= htmlspecialchars($categoria) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-text-secondary text-base mb-2 flex items-center">
                        <i class="ri-money-dollar-circle-fill mr-2 text-primary"></i> Rango de Precio
                    </label>
                    <div class="flex flex-col gap-3">
                        <input id="price-min-mobile" type="range" min="0" max="100" step="1" value="0" class="w-full accent-primary">
                        <input id="price-max-mobile" type="range" min="0" max="100" step="1" value="100" class="w-full accent-primary">
                        <div class="range-ticks"></div>
                        <div class="flex justify-between text-text-secondary text-base">
                            <span id="price-min-value-mobile">0 bs</span>
                            <span id="price-max-value-mobile">100 bs</span>
                        </div>
                    </div>
                </div>
                <div>
                    <label class="block text-text-secondary text-base mb-2 flex items-center">
                        <i class="ri-sort-asc mr-2 text-primary"></i> Ordenar
                    </label>
                    <select id="sort-filter-mobile" class="w-full px-5 py-3 rounded-xl border border-gray-200 focus:outline-none focus:ring-2 focus:ring-primary text-text-primary transition-all duration-300">
                        <option value="name-asc">Nombre (A-Z)</option>
                        <option value="name-desc">Nombre (Z-A)</option>
                        <option value="price-asc">Precio (Menor a Mayor)</option>
                        <option value="price-desc">Precio (Mayor a Menor)</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <main class="pt-24 pb-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Hero -->
            <div class="relative bg-gradient-to-r from-primary-light/30 via-panel to-primary/10 rounded-2xl p-10 mb-12 animate-slide-up glow-effect">
                <h1 class="text-4xl sm:text-5xl font-bold text-text-primary mb-6 drop-shadow-lg">Explora Nuestros Productos</h1>
                <p class="text-xl text-text-secondary max-w-3xl leading-relaxed">
                    Descubre la frescura y calidad de los lácteos y bebidas de Pil Andina. ¡Todos los productos disponibles están aquí para ti!
                </p>
                <div class="absolute top-0 right-0 w-48 h-48 bg-primary-light/40 rounded-full blur-3xl -z-10"></div>
                <div class="absolute bottom-0 left-0 w-72 h-72 bg-primary/20 rounded-full blur-4xl -z-10"></div>
            </div>

            <!-- Search and Filters -->
            <div class="grid grid-cols-1 lg:grid-cols-4 gap-10 mb-12">
                <!-- Filters (Desktop) -->
                <div class="hidden lg:block bg-white glass-effect rounded-xl p-8 shadow-lg animate-slide-up">
                    <h3 class="text-xl font-semibold text-text-primary mb-6">Filtros</h3>
                    <div class="space-y-6">
                        <div>
                            <label class="block text-text-secondary text-base mb-2 flex items-center">
                                <i class="ri-filter-fill mr-2 text-primary"></i> Categoría
                            </label>
                            <select id="category-filter" class="w-full px-5 py-3 rounded-xl border border-gray-200 focus:outline-none focus:ring-2 focus:ring-primary text-text-primary transition-all duration-300">
                                <option value="">Todas las Categorías</option>
                                <?php foreach ($categorias as $categoria): ?>
                                    <option value="<?= htmlspecialchars($categoria) ?>"><?= htmlspecialchars($categoria) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-text-secondary text-base mb-2 flex items-center">
                                <i class="ri-money-dollar-circle-fill mr-2 text-primary"></i> Rango de Precio
                            </label>
                            <div class="flex flex-col gap-3">
                                <input id="price-min" type="range" min="0" max="100" step="1" value="0" class="w-full accent-primary">
                                <input id="price-max" type="range" min="0" max="100" step="1" value="100" class="w-full accent-primary">
                                <div class="range-ticks"></div>
                                <div class="flex justify-between text-text-secondary text-base">
                                    <span id="price-min-value">0 bs</span>
                                    <span id="price-max-value">100 bs</span>
                                </div>
                            </div>
                        </div>
                        <div>
                            <label class="block text-text-secondary text-base mb-2 flex items-center">
                                <i class="ri-sort-asc mr-2 text-primary"></i> Ordenar
                            </label>
                            <select id="sort-filter" class="w-full px-5 py-3 rounded-xl border border-gray-200 focus:outline-none focus:ring-2 focus:ring-primary text-text-primary transition-all duration-300">
                                <option value="name-asc">Nombre (A-Z)</option>
                                <option value="name-desc">Nombre (Z-A)</option>
                                <option value="price-asc">Precio (Menor a Mayor)</option>
                                <option value="price-desc">Precio (Mayor a Menor)</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Search and Products -->
                <div class="lg:col-span-3">
                    <!-- Search -->
                    <div class="relative mb-10 animate-fade-in">
                        <input 
                            id="search-input" 
                            type="text" 
                            placeholder="Buscar productos..." 
                            class="w-full pl-14 pr-6 py-4 rounded-xl border border-gray-200 focus:outline-none focus:ring-2 focus:ring-primary text-text-primary shadow-md bg-white/80 backdrop-blur-sm transition-all duration-300 glow-effect"
                        >
                        <i class="ri-search-line absolute left-5 top-1/2 transform -translate-y-1/2 text-text-secondary search-icon transition-colors duration-300"></i>
                    </div>

                    <!-- Products Grid -->
                    <section id="products" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-8 animate-slide-up">
                        <!-- Products will be dynamically inserted here -->
                    </section>
                </div>
            </div>
        </div>
    </main>

    <!-- Add to Cart Modal -->
    <div id="cart-modal" class="fixed inset-0 bg-black/60 flex items-center justify-center z-50 hidden">
        <div class="bg-white rounded-2xl p-8 max-w-md w-full animate-scale-in">
            <div class="flex items-center space-x-3 mb-6">
                <i class="ri-checkbox-circle-fill text-green-600 text-3xl"></i>
                <h3 class="text-2xl font-semibold text-text-primary">¡Añadido al Carrito!</h3>
            </div>
            <p id="modal-product-name" class="text-lg text-text-secondary mb-6"></p>
            <div class="flex justify-end space-x-4">
                <button id="close-modal" class="px-6 py-3 bg-gray-200 text-text-primary rounded-xl hover:bg-gray-300 transition-colors">Cerrar</button>
                <a href="carrito.php" class="px-6 py-3 bg-primary text-white rounded-xl hover:bg-primary-dark transition-colors">Ver Carrito</a>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-panel py-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 grid grid-cols-1 md:grid-cols-4 gap-10">
            <div class="footer__col">
                <div class="flex items-center mb-6">
                    <div class="relative">
                        <div class="w-12 h-12 bg-gradient-to-br from-primary to-primary-dark rounded-full flex items-center justify-center shadow-lg animate-pulse-glow">
                            <i class="fas fa-industry text-white text-xl"></i>
                        </div>
                        <div class="absolute -top-1 -right-1 w-4 h-4 bg-green-500 rounded-full animate-bounce-light"></div>
                    </div>
                    <a href="landing.php" class="ml-4 text-2xl font-bold text-text-primary tracking-wide">
                        Pil<span class="text-primary">Andina</span>
                    </a>
                </div>
                <p class="text-text-secondary leading-relaxed">
                    Descubre nuestra variedad de lácteos frescos y nutritivos, ideales para toda la familia. En Pil Andina, te ofrecemos productos que combinan sabor, frescura y calidad.
                </p>
            </div>
            <div class="footer__col">
                <h4 class="text-lg font-semibold text-text-primary mb-6">Producto</h4>
                <ul class="space-y-4">
                    <li><a href="#" class="text-text-secondary hover:text-primary transition-colors">Menú</a></li>
                    <li><a href="#" class="text-text-secondary hover:text-primary transition-colors">Nuevas Entradas</a></li>
                    <li><a href="#" class="text-text-secondary hover:text-primary transition-colors">Contáctanos</a></li>
                </ul>
            </div>
            <div class="footer__col">
                <h4 class="text-lg font-semibold text-text-primary mb-6">Contáctanos</h4>
                <ul class="space-y-4">
                    <li><a href="#" class="text-text-secondary hover:text-primary transition-colors">Hablemos</a></li>
                    <li><a href="#" class="text-text-secondary hover:text-primary transition-colors">WhatsApp</a></li>
                    <li><a href="#" class="text-text-secondary hover:text-primary transition-colors">Facebook</a></li>
                    <li><a href="#" class="text-text-secondary hover:text-primary transition-colors">Telegram</a></li>
                </ul>
            </div>
            <div class="footer__col">
                <h4 class="text-lg font-semibold text-text-primary mb-6">Compañía</h4>
                <ul class="space-y-4">
                    <li><a href="#" class="text-text-secondary hover:text-primary transition-colors">Nuestra Historia</a></li>
                    <li><a href="#" class="text-text-secondary hover:text-primary transition-colors">Términos de Servicio</a></li>
                    <li><a href="#" class="text-text-secondary hover:text-primary transition-colors">Política de Privacidad</a></li>
                </ul>
            </div>
        </div>
        <div class="mt-10 text-center text-text-secondary">
            © Copyright 2025. Todos los derechos reservados.
        </div>
    </footer>

    <script src="https://unpkg.com/scrollreveal"></script>
    <script>
        // Initialize Cart
        let cart = JSON.parse(localStorage.getItem('cart')) || [];

        // Products from PHP
        const products = <?= json_encode($productos) ?>;

        // DOM Elements
        const productsGrid = document.getElementById('products');
        const searchInput = document.getElementById('search-input');
        const categoryFilter = document.getElementById('category-filter');
        const priceMin = document.getElementById('price-min');
        const priceMax = document.getElementById('price-max');
        const sortFilter = document.getElementById('sort-filter');
        const cartCount = document.getElementById('cart-count');
        const cartCountMobile = document.getElementById('cart-count-mobile');
        const modal = document.getElementById('cart-modal');
        const modalProductName = document.getElementById('modal-product-name');
        const closeModal = document.getElementById('close-modal');
        const filterSidebar = document.getElementById('filter-sidebar');
        const filterToggle = document.getElementById('filter-toggle');
        const closeFilter = document.getElementById('close-filter');
        const categoryFilterMobile = document.getElementById('category-filter-mobile');
        const priceMinMobile = document.getElementById('price-min-mobile');
        const priceMaxMobile = document.getElementById('price-max-mobile');
        const sortFilterMobile = document.getElementById('sort-filter-mobile');
        const priceMinValue = document.getElementById('price-min-value');
        const priceMaxValue = document.getElementById('price-max-value');
        const priceMinValueMobile = document.getElementById('price-min-value-mobile');
        const priceMaxValueMobile = document.getElementById('price-max-value-mobile');

        // Update Price Values
        function updatePriceValues() {
            priceMinValue.textContent = `${priceMin.value} bs`;
            priceMaxValue.textContent = `${priceMax.value} bs`;
            priceMinValueMobile.textContent = `${priceMinMobile.value} bs`;
            priceMaxValueMobile.textContent = `${priceMaxMobile.value} bs`;
        }

        // Render Products
        function renderProducts(filteredProducts) {
            productsGrid.innerHTML = '';
            if (filteredProducts.length === 0) {
                productsGrid.innerHTML = '<p class="text-center text-text-secondary text-xl col-span-full">No se encontraron productos.</p>';
                return;
            }
            filteredProducts.forEach(product => {
                const productCard = document.createElement('div');
                productCard.className = 'relative bg-white rounded-2xl shadow-md p-6 card-hover animate-fade-in';
                productCard.innerHTML = `
                    <span class="category-badge">Cat: ${product.id_categoria}</span>
                    <img src="${product.foto}" alt="${product.nombre_producto}" class="w-full h-60 object-cover rounded-xl mb-4 transition-opacity duration-300 hover:opacity-90">
                    <h4 class="text-xl font-semibold text-text-primary mb-2">${product.nombre_producto}</h4>
                    <p class="text-text-secondary text-base line-clamp-3 mb-4">${product.descripcion || 'Sin descripción'}</p>
                    <p class="text-text-secondary text-sm mb-2">Presentación: ${product.tipo_de_presentacion || 'N/A'}</p>
                    <p class="text-text-secondary text-sm mb-2">Cantidad: ${product.cantidad || 0}</p>
                    <div class="flex justify-between items-center">
                        <p class="text-lg font-medium text-text-primary price-glow">${parseFloat(product.precio).toFixed(2)} bs</p>
                        <button class="add-to-cart bg-primary hover:bg-primary-dark text-white font-medium py-2 px-5 rounded-lg flex items-center ripple-effect transition-all duration-300" data-id="${product.id_producto}" data-name="${product.nombre_producto}">
                            <i class="ri-shopping-cart-fill mr-2"></i> Añadir
                        </button>
                    </div>
                `;
                productsGrid.appendChild(productCard);
            });
        }

        // Filter and Sort Products
        function filterAndSortProducts() {
            let filteredProducts = [...products];

            // Search Filter
            const searchTerm = searchInput.value.toLowerCase();
            if (searchTerm) {
                filteredProducts = filteredProducts.filter(product => 
                    product.nombre_producto.toLowerCase().includes(searchTerm)
                );
            }

            // Category Filter
            const category = categoryFilter.value || categoryFilterMobile.value;
            if (category) {
                filteredProducts = filteredProducts.filter(product => 
                    product.id_categoria == category
                );
            }

            // Price Filter
            const minPrice = parseFloat(priceMin.value) || parseFloat(priceMinMobile.value) || 0;
            const maxPrice = parseFloat(priceMax.value) || parseFloat(priceMaxMobile.value) || Infinity;
            filteredProducts = filteredProducts.filter(product => 
                parseFloat(product.precio) >= minPrice && parseFloat(product.precio) <= maxPrice
            );

            // Sort
            const sortOption = sortFilter.value || sortFilterMobile.value;
            if (sortOption === 'name-asc') {
                filteredProducts.sort((a, b) => a.nombre_producto.localeCompare(b.nombre_producto));
            } else if (sortOption === 'name-desc') {
                filteredProducts.sort((a, b) => b.nombre_producto.localeCompare(a.nombre_producto));
            } else if (sortOption === 'price-asc') {
                filteredProducts.sort((a, b) => parseFloat(a.precio) - parseFloat(b.precio));
            } else if (sortOption === 'price-desc') {
                filteredProducts.sort((a, b) => parseFloat(b.precio) - parseFloat(a.precio));
            }

            renderProducts(filteredProducts);
        }

        // Add to Cart
        function addToCart(productId, productName) {
            const product = products.find(p => p.id_producto === parseInt(productId));
            cart.push(product);
            localStorage.setItem('cart', JSON.stringify(cart));
            cartCount.textContent = cart.length;
            cartCountMobile.textContent = cart.length;
            modalProductName.textContent = `${productName} ha sido añadido al carrito.`;
            modal.classList.remove('hidden');
        }

        // Initialize
        function init() {
            renderProducts(products);
            updatePriceValues();
            cartCount.textContent = cart.length;
            cartCountMobile.textContent = cart.length;
        }

        // Event Listeners
        searchInput.addEventListener('input', filterAndSortProducts);
        categoryFilter.addEventListener('change', () => {
            categoryFilterMobile.value = categoryFilter.value;
            filterAndSortProducts();
        });
        categoryFilterMobile.addEventListener('change', () => {
            categoryFilter.value = categoryFilterMobile.value;
            filterAndSortProducts();
        });
        priceMin.addEventListener('input', () => {
            priceMinMobile.value = priceMin.value;
            updatePriceValues();
            filterAndSortProducts();
        });
        priceMax.addEventListener('input', () => {
            priceMaxMobile.value = priceMax.value;
            updatePriceValues();
            filterAndSortProducts();
        });
        priceMinMobile.addEventListener('input', () => {
            priceMin.value = priceMinMobile.value;
            updatePriceValues();
            filterAndSortProducts();
        });
        priceMaxMobile.addEventListener('input', () => {
            priceMax.value = priceMaxMobile.value;
            updatePriceValues();
            filterAndSortProducts();
        });
        sortFilter.addEventListener('change', () => {
            sortFilterMobile.value = sortFilter.value;
            filterAndSortProducts();
        });
        sortFilterMobile.addEventListener('change', () => {
            sortFilter.value = sortFilterMobile.value;
            filterAndSortProducts();
        });

        productsGrid.addEventListener('click', (e) => {
            const button = e.target.closest('.add-to-cart');
            if (button) {
                const productId = button.dataset.id;
                const productName = button.dataset.name;
                addToCart(productId, productName);
            }
        });

        closeModal.addEventListener('click', () => modal.classList.add('hidden'));

        // Mobile Menu Toggle
        const menuBtn = document.getElementById('menu-btn');
        const navLinks = document.getElementById('nav-links');

        menuBtn.addEventListener('click', () => {
            navLinks.classList.toggle('hidden');
        });

        // Filter Sidebar Toggle
        filterToggle.addEventListener('click', () => {
            filterSidebar.classList.remove('-translate-x-full');
        });

        closeFilter.addEventListener('click', () => {
            filterSidebar.classList.add('-translate-x-full');
        });

        // ScrollReveal Animations
        ScrollReveal().reveal('.animate-slide-up', {
            duration: 1000,
            distance: '40px',
            origin: 'bottom',
            easing: 'ease-out',
            interval: 150,
        });

        ScrollReveal().reveal('.animate-fade-in', {
            duration: 800,
            distance: '20px',
            origin: 'left',
            easing: 'ease-out',
        });

        ScrollReveal().reveal('.animate-scale-in', {
            duration: 400,
            scale: 0.9,
            easing: 'ease-out',
        });

        // Start
        init();
    </script>
</body>
</html>
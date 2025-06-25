<?php
// Iniciar sesión para mantener la cotización
session_start();

// Conexión a la base de datos
$host = '127.0.0.1';
$user = 'root';
$pass = '';
$db = 'noah';
$port = 3308;

$conn = new mysqli($host, $user, $pass, $db, $port);

if ($conn->connect_error) {
    die("<div class='p-4 bg-red-100 text-red-800 rounded'>Error de conexión: " . $conn->connect_error . "</div>");
}

$conn->set_charset("utf8mb4");

// Función para obtener productos
function obtenerProductos($conexion) {
    $sql = "SELECT id_producto, nombre_producto, precio, cantidad FROM productos";
    $result = $conexion->query($sql);
    
    if (!$result) {
        die("<div class='p-4 bg-red-100 text-red-800 rounded'>Error en la consulta: " . $conexion->error . "</div>");
    }
    
    $productos = array();
    while($row = $result->fetch_assoc()) {
        $productos[] = $row;
    }
    
    return $productos;
}

// Obtener productos
$productos = obtenerProductos($conn);

// Limpiar cotización anterior si se solicita nueva
if (isset($_GET['nueva'])) {
    unset($_SESSION['cotizacion']);
    header("Location: cotizaciones.php");
    exit;
}

// Procesar cotización
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $items = $_POST['items'] ?? [];
    $detalle = array();
    $total = 0;
    
    foreach ($items as $id => $cantidad) {
        if ($cantidad > 0) {
            foreach ($productos as $producto) {
                if ($producto['id_producto'] == $id) {
                    $subtotal = $producto['precio'] * $cantidad;
                    $total += $subtotal;
                    
                    $detalle[] = array(
                        'id' => $id,
                        'nombre' => $producto['nombre_producto'],
                        'precio' => $producto['precio'],
                        'cantidad' => $cantidad,
                        'subtotal' => $subtotal
                    );
                    break;
                }
            }
        }
    }
    
    // Guardar cotización en sesión
    $_SESSION['cotizacion'] = array(
        'detalle' => $detalle,
        'total' => $total,
        'fecha' => date('d/m/Y H:i:s')
    );
    
    // Redirigir para evitar reenvío del formulario
    header("Location: cotizaciones.php");
    exit;
}

// Generar PDF si se solicita
if (isset($_GET['imprimir']) && isset($_SESSION['cotizacion'])) {
    require('fpdf/fpdf.php');
    
    $cotizacion = $_SESSION['cotizacion'];
    
    // Crear PDF
    $pdf = new FPDF();
    $pdf->AddPage();
    $pdf->SetFont('Arial', 'B', 16);
    
    // Encabezado
    $pdf->SetTextColor(78, 107, 175);
    $pdf->Cell(0, 10, 'COTIZACION', 0, 1, 'C');
    $pdf->SetFont('Arial', '', 10);
    $pdf->Cell(0, 10, 'Fecha: ' . $cotizacion['fecha'], 0, 1, 'R');
    $pdf->Ln(10);
    
    // Configurar anchos de columna
    $w = array(80, 30, 30, 40);
    
    // Encabezados de tabla
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->SetFillColor(78, 107, 175);
    $pdf->SetTextColor(255);
    $pdf->Cell($w[0], 10, 'Producto', 1, 0, 'C', true);
    $pdf->Cell($w[1], 10, 'Precio Unit.', 1, 0, 'C', true);
    $pdf->Cell($w[2], 10, 'Cantidad', 1, 0, 'C', true);
    $pdf->Cell($w[3], 10, 'Subtotal', 1, 1, 'C', true);
    
    // Contenido de la tabla
    $pdf->SetFont('Arial', '', 10);
    $pdf->SetTextColor(0);
    $pdf->SetFillColor(224, 235, 255);
    $fill = false;
    
    foreach($cotizacion['detalle'] as $item) {
        $pdf->Cell($w[0], 10, iconv('UTF-8', 'windows-1252', $item['nombre']), 1, 0, 'L', $fill);
        $pdf->Cell($w[1], 10, 'Bs ' . number_format($item['precio'], 2), 1, 0, 'R', $fill);
        $pdf->Cell($w[2], 10, $item['cantidad'], 1, 0, 'C', $fill);
        $pdf->Cell($w[3], 10, 'Bs ' . number_format($item['subtotal'], 2), 1, 1, 'R', $fill);
        $fill = !$fill;
    }
    
    // Total
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell($w[0]+$w[1]+$w[2], 10, 'TOTAL', 1, 0, 'R', true);
    $pdf->Cell($w[3], 10, 'Bs ' . number_format($cotizacion['total'], 2), 1, 1, 'R', true);
    
    // Pie de página
    $pdf->Ln(15);
    $pdf->SetFont('Arial', 'I', 10);
    $pdf->Cell(0, 10, iconv('UTF-8', 'windows-1252', 'Gracias por su preferencia'), 0, 1, 'C');
    
    // Salida del PDF
    $pdf->Output('I', 'cotizacion_'.date('YmdHis').'.pdf');
    exit;
}

// Obtener cotización de sesión para mostrar
$cotizacion = $_SESSION['cotizacion'] ?? null;
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Cotizaciones - Pil Andina</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#4e6baf',
                        'primary-dark': '#3a5186',
                        'primary-light': '#86acd4',
                        panel: '#ffffff',
                        'panel-dark': '#1f2937',
                        accent: '#42568b',
                        'soft-red': '#fee2e2',
                        'text-soft-red': '#b91c1c',
                    },
                    animation: {
                        'fade-in': 'fadeIn 0.5s ease-in',
                        'slide-up': 'slideUp 0.4s ease-out',
                        'bounce-light': 'bounceLight 2s infinite',
                        'pulse-glow': 'pulseGlow 2s infinite',
                    },
                    backgroundImage: {
                        'gradient-primary': 'linear-gradient(135deg, #42568b 0%, #86acd4 100%)',
                        'gradient-card': 'linear-gradient(145deg, #ffffff 0%, #f8fafc 100%)',
                        'gradient-dark': 'linear-gradient(145deg, #1f2937 0%, #374151 100%)',
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
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        @keyframes bounceLight {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-5px); }
        }
        
        @keyframes pulseGlow {
            0%, 100% { box-shadow: 0 0 20px rgba(78, 107, 175, 0.3); }
            50% { box-shadow: 0 0 30px rgba(78, 107, 175, 0.6); }
        }
        
        .glass-effect {
            backdrop-filter: blur(10px);
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .dark .glass-effect {
            background: rgba(31, 41, 55, 0.7);
            border: 1px solid rgba(55, 65, 81, 0.3);
        }
        
        .card-hover {
            transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
        }
        
        .card-hover:hover {
            transform: translateY(-8px) scale(1.02);
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.15);
        }
        
        .dark .card-hover:hover {
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.4);
        }
        
        .sidebar-animation {
            transition: all 0.4s cubic-bezier(0.68, -0.55, 0.265, 1.55);
        }
        
        .floating-gradient {
            background: linear-gradient(-45deg, #4e6baf, #86acd4, #42568b, #5a7bc7);
            background-size: 400% 400%;
            animation: gradient 15s ease infinite;
        }
        
        @keyframes gradient {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        
        .text-shadow {
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        
        .scrollbar-thin::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }
        
        .scrollbar-thin::-webkit-scrollbar-track {
            background: #f1f5f9;
            border-radius: 3px;
        }
        
        .scrollbar-thin::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 3px;
        }
        
        .scrollbar-thin::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }
        
        .dark .scrollbar-thin::-webkit-scrollbar-track {
            background: #374151;
        }
        
        .dark .scrollbar-thin::-webkit-scrollbar-thumb {
            background: #6b7280;
        }
        
        .form-control {
            border-radius: 10px;
            padding: 10px;
            border: 1px solid #ccc;
            box-shadow: inset 0 0 8px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            border-color: #4e6baf;
            box-shadow: inset 0 0 5px rgba(78, 107, 175, 0.4);
            outline: none;
        }
        
        .dark .form-control {
            background-color: #3a3b3c;
            color: #ccc;
            border-color: #4b5563;
        }
        
        .dark .form-control:focus {
            border-color: #86acd4;
            box-shadow: inset 0 0 5px rgba(134, 172, 212, 0.4);
        }
    </style>
</head>
<body class="bg-gray-50 transition-colors duration-300 dark:bg-gray-900">
    <!-- Sidebar -->
    <aside id="sidebar" class="fixed left-0 top-0 z-40 h-screen w-64 transition-transform duration-300 transform -translate-x-full lg:translate-x-0 sidebar-animation">
        <div class="h-full px-3 py-4 overflow-y-auto bg-white dark:bg-gray-800 shadow-2xl">
            <!-- Logo -->
            <div class="flex items-center justify-center mb-8 p-4">
                <div class="relative">
                    <div class="w-13 h-13 bg-gradient-primary rounded-full flex items-center justify-center shadow-lg animate-pulse-glow overflow-hidden">
                        <img src="../views/logo/image.jpg" alt="Pil Andina Logo" class="w-full h-full object-cover">
                    </div>
                    <div class="absolute -top-1 -right-1 w-4 h-4 bg-green-400 rounded-full animate-bounce-light"></div>
                </div>
                <div class="ml-3">
                    <h1 class="text-xl font-bold text-gray-800 dark:text-white text-shadow">Pil Andina</h1>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Sistema de Gestión</p>
                </div>
            </div>

            <!-- Navigation -->
            <nav class="space-y-2">
               <a href="../views/dashboard.php" class="flex items-center p-3 text-gray-700 dark:text-gray-200 rounded-lg hover:bg-primary hover:text-white transition-all duration-300 group card-hover">
                    <div class="w-8 h-8 bg-gradient-primary rounded-lg flex items-center justify-center shadow-sm group-hover:shadow-lg transition-all duration-300">
                        <i class="fas fa-tachometer-alt text-white text-sm"></i>
                    </div>
                    <span class="ml-3 font-medium">Dashboard</span>
                </a>
                
                <a href="../views/usuarios.php" class="flex items-center p-3 text-gray-700 dark:text-gray-200 rounded-lg hover:bg-primary hover:text-white transition-all duration-300 group card-hover">
                    <div class="w-8 h-8 bg-blue-500 rounded-lg flex items-center justify-center shadow-sm group-hover:shadow-lg transition-all duration-300">
                        <i class="fas fa-users text-white text-sm"></i>
                    </div>
                    <span class="ml-3 font-medium">Usuarios</span>
                </a>
                
                <a href="../views/clientes.php" class="flex items-center p-3 text-gray-700 dark:text-gray-200 rounded-lg hover:bg-primary hover:text-white transition-all duration-300 group card-hover">
                    <div class="w-8 h-8 bg-green-500 rounded-lg flex items-center justify-center shadow-sm group-hover:shadow-lg transition-all duration-300">
                        <i class="fas fa-user-friends text-white text-sm"></i>
                    </div>
                    <span class="ml-3 font-medium">Clientes</span>
                </a>
                
                <a href="../views/listado_productos.php" class="flex items-center p-3 text-gray-700 dark:text-gray-200 rounded-lg hover:bg-primary hover:text-white transition-all duration-300 group card-hover">
                    <div class="w-8 h-8 bg-purple-500 rounded-lg flex items-center justify-center shadow-sm group-hover:shadow-lg transition-all duration-300">
                        <i class="fas fa-box text-white text-sm"></i>
                    </div>
                    <span class="ml-3 font-medium">Productos</span>
                </a>
                
                 <a href="../views/categorias.php" class="flex items-center p-3 text-gray-700 dark:text-gray-200 rounded-lg hover:bg-primary hover:text-white transition-all duration-300 group card-hover">
                    <div class="w-8 h-8 bg-pink-500 rounded-lg flex items-center justify-center shadow-sm group-hover:shadow-lg transition-all duration-300">
                        <i class="fas fa-tags text-white text-sm"></i>
                    </div>
                    <span class="ml-3 font-medium">Categorias</span>
                </a>

                <a href="../views/traspaso.php" class="flex items-center p-3 text-gray-700 dark:text-gray-200 rounded-lg hover:bg-primary hover:text-white transition-all duration-300 group card-hover">
                    <div class="w-8 h-8 bg-orange-500 rounded-lg flex items-center justify-center shadow-sm group-hover:shadow-lg transition-all duration-300">
                        <i class="fas fa-exchange-alt text-white text-sm"></i>
                    </div>
                    <span class="ml-3 font-medium">Traspaso</span>
                </a>
                
                <a href="../views/ventas_admin.php" class="flex items-center p-3 text-gray-700 dark:text-gray-200 rounded-lg hover:bg-primary hover:text-white transition-all duration-300 group card-hover">
                    <div class="w-8 h-8 bg-red-500 rounded-lg flex items-center justify-center shadow-sm group-hover:shadow-lg transition-all duration-300">
                        <i class="fas fa-shopping-bag text-white text-sm"></i>
                    </div>
                    <span class="ml-3 font-medium">Ventas</span>
                </a>
                
                <a href="../views/cotizaciones.php" class="flex items-center p-3 text-gray-700 dark:text-gray-200 rounded-lg bg-primary text-white group card-hover">
                    <div class="w-8 h-8 bg-yellow-500 rounded-lg flex items-center justify-center shadow-lg transition-all duration-300">
                        <i class="fas fa-file-invoice text-white text-sm"></i>
                    </div>
                    <span class="ml-3 font-medium">Cotizaciones</span>
                </a>
                
                <a href="../views/kardex.php" class="flex items-center p-3 text-gray-700 dark:text-gray-200 rounded-lg hover:bg-primary hover:text-white transition-all duration-300 group card-hover">
                    <div class="w-8 h-8 bg-teal-500 rounded-lg flex items-center justify-center shadow-sm group-hover:shadow-lg transition-all duration-300">
                        <i class="fas fa-clipboard-list text-white text-sm"></i>
                    </div>
                    <span class="ml-3 font-medium">Kardex</span>
                </a>
            </nav>

            <!-- Bottom Section -->
            <div class="absolute bottom-4 left-3 right-3">
                <div class="flex items-center justify-between p-3 bg-gray-100 dark:bg-gray-700 rounded-lg">
                    <button id="darkModeToggle" class="flex items-center space-x-2 text-gray-600 dark:text-gray-300 hover:text-primary transition-colors duration-300">
                        <i id="theme-icon" class="fas fa-moon"></i>
                        <span class="text-sm font-medium theme-text">Modo Oscuro</span>
                    </button>
                    <a href="?logout=true" class="text-gray-500 hover:text-red-500 transition-colors duration-300">
                        <i class="fas fa-sign-out-alt"></i>
                    </a>
                </div>
            </div>
        </div>
    </aside>

    <!-- Mobile overlay -->
    <div id="sidebarOverlay" class="fixed inset-0 z-30 bg-black bg-opacity-50 lg:hidden hidden"></div>

    <!-- Main Content -->
    <div class="lg:ml-64">
        <!-- Header -->
        <header class="bg-white dark:bg-gray-800 shadow-sm border-b border-gray-200 dark:border-gray-700 sticky top-0 z-20">
            <div class="flex items-center justify-between p-4">
                <div class="flex items-center space-x-4">
                    <button id="sidebarToggle" class="lg:hidden p-2 rounded-lg text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors duration-300">
                        <i class="fas fa-bars text-xl"></i>
                    </button>
                    <div>
                        <h1 class="text-2xl font-bold text-gray-800 dark:text-white">Sistema de Cotizaciones</h1>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Bienvenido, <?php echo isset($_SESSION['nombre_usu']) ? htmlspecialchars($_SESSION['nombre_usu']) : 'Admin'; ?></p>
                    </div>
                </div>

                <div class="flex items-center space-x-4">
                    <!-- Search -->
                    <div class="relative hidden md:block">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-search text-gray-400"></i>
                        </div>
                        <input type="text" placeholder="Buscar..." class="pl-10 pr-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-primary focus:border-transparent transition-all duration-300">
                    </div>

                    <!-- Notifications -->
                    <button class="relative p-2 text-gray-600 dark:text-gray-300 hover:text-primary transition-colors duration-300">
                        <i class="fas fa-bell text-xl"></i>
                        <span class="absolute top-0 right-0 w-2 h-2 bg-red-500 rounded-full animate-pulse"></span>
                    </button>

                    <!-- Profile -->
                    <div class="flex items-center space-x-3">
                        <div class="w-10 h-10 bg-gradient-primary rounded-full flex items-center justify-center shadow-lg">
                            <i class="fas fa-user text-white text-sm"></i>
                        </div>
                        <div class="hidden md:block">
                            <p class="text-sm font-medium text-gray-700 dark:text-gray-200"><?php echo isset($_SESSION['nombre_usu']) ? htmlspecialchars($_SESSION['nombre_usu']) : 'Administrador'; ?></p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">admin@pilandina.com</p>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="p-6 space-y-8">
            <?php if (isset($cotizacion) && !empty($cotizacion['detalle'])): ?>
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 animate-fade-in border border-gray-100 dark:border-gray-700 card-hover">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-6">
                        <i class="fas fa-receipt mr-2 text-primary"></i>Cotización Generada
                    </h3>
                    <div class="overflow-x-auto scrollbar-thin">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-primary text-white">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider">Producto</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider">Precio Unit.</th>
                                    <th class="px-4 py-3 text-center text-sm font-medium uppercase tracking-wider">Cantidad</th>
                                    <th class="px-4 py-3 text-right text-sm font-medium uppercase tracking-wider">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-y-0">
                                <?php foreach($cotizacion['detalle'] as $item): ?>
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors duration-200">
                                        <td class="px-4 py-4 text-sm text-gray-700 dark:text-gray-300"><?php echo htmlspecialchars($item['nombre']); ?></td>
                                        <td class="px-4 py-4 text-sm text-right text-gray-700 dark:text-gray-300">Bs <?php echo number_format($item['precio'], 2); ?></td>
                                        <td class="px-4 py-4 text-sm text-gray-700 dark:text-gray-300 text-center"><?php echo $item['cantidad']; ?></td>
                                        <td class="px-4 py-4 text-sm font-medium text-right text-gray-700 dark:text-gray-100">Bs <?php echo number_format($item['subtotal'], 2); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <td colspan="3" class="px-4 py-3 text-right font-semibold text-gray-900 dark:text-gray-100">Total</td>
                                    <td class="px-4 py-4 text-right font-bold text-gray-900 dark:text-gray-100">Bs <?php echo number_format($cotizacion['total'], 2); ?></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    <div class="mt-6 flex flex-col sm:flex-row justify-between gap-4">
                        <a href="?nueva=1" class="bg-primary hover:bg-primary-dark text-white font-medium py-2 px-4 rounded-lg shadow transition-all duration-300 flex items-center justify-center">
                            <i class="fas fa-plus mr-2"></i> Nueva Cotización</a>
                        <a href="?imprimir=1" class="bg-green-600 hover:bg-green-700 text-white font-medium py-2 px-4 rounded-lg shadow transition-all duration-300 flex items-center">
                            <i class="fas fa-print mr-2"></i> Imprimir Cotización
                        </a>
                    </div>
                </div>
            <?php elseif (empty($productos)): ?>
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 animate-slide bg-gray-100 border border-gray-100 dark:border-gray-700 card-hover">
                    <div class="flex items-center text-center p-4 bg-red-200 dark:bg-red-900 rounded-lg">
                        <i class="fas fa-exclamation-circle mr-2 text-red-600 dark:text-red-400"></i>
                        <span class="text-red-600 dark:text-red-400">No hay productos disponibles para cotizar.</span>
                    </div>
                </div>
            <?php elseif (isset($cotización) && empty($cotización['detalle'])): ?>
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 animate-slide-up border border-gray-100 dark:border-gray-700 card-hover">
                    <div class="flex items-center p-4 bg-yellow-200 dark:bg-yellow-900 rounded-lg">
                        <i class="fas fa-exclamation-triangle mr-2 text-yellow-600 dark:text-yellow-400"></i>
                        <span class="text-yellow-600 dark:text-yellow-400">No seleccionaste ningún producto para cotizar.</span>
                    </div>
                    <div class="mt-4">
                        <a href="cotizaciones.php" class="bg-primary hover:bg-primary-dark text-white font-medium py-2 px-4 rounded-lg shadow transition-all duration-300 flex items-center">
                            <i class="fas fa-arrow-left mr-2"></i> Volver
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 animate-slide-up border border-gray-100 dark:border-gray-700 card-hover">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-6">
                        <i class="fas fa-calculator mr-2 text-primary"></i>Generar Nueva Cotización
                    </h3>
                    <form method="post" action="cotizaciones.php">
                        <div class="overflow-x-auto scrollbar-thin">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="bg-primary text-white">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider">Producto</th>
                                        <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider">Precio</th>
                                        <th class="px-4 py-3 text-center text-xs font-medium uppercase tracking-wider">Stock</th>
                                        <th class="px-4 py-3 text-center text-xs font-medium uppercase tracking-wider">Cantidad</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                    <?php foreach($productos as $producto): ?>
                                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors duration-200">
                                            <td class="px-4 py-4 text-sm text-gray-700 dark:text-gray-300"><?php echo htmlspecialchars($producto['nombre_producto']); ?></td>
                                            <td class="px-4 py-4 text-sm text-right text-gray-700 dark:text-gray-300">Bs <?php echo number_format($producto['precio'], 2); ?></td>
                                            <td class="px-4 py-4 text-sm text-center text-gray-700 dark:text-gray-300"><?php echo $producto['cantidad']; ?></td>
                                            <td class="px-4 py-4 text-sm text-gray-700 dark:text-gray-300">
                                                <input type="number" 
                                                       name="items[<?php echo $producto['id_producto']; ?>]" 
                                                       min="0" 
                                                       max="<?php echo $producto['cantidad']; ?>" 
                                                       value="0"
                                                       class="form-control w-20 text-gray-900 dark:text-gray-100 dark:bg-gray-700 text-center">
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-6 flex justify-end">
                            <button type="submit" class="bg-primary hover:bg-primary-dark text-white font-medium py-2 px-4 rounded-lg shadow transition-all duration-300 flex items-center">
                                <i class="fas fa-check-circle mr-2"></i> Generar Cotización
                            </button>
                        </div>
                    </form>
                </div>
            <?php endif; ?>
        </main>
    </div>

    <script>
        // Sidebar Toggle
        const sidebar = document.getElementById('sidebar');
        const sidebarToggle = document.getElementById('sidebarToggle');
        const sidebarOverlay = document.getElementById('sidebarOverlay');

        sidebarToggle.addEventListener('click', () => {
            sidebar.classList.toggle('-translate-x-full');
            sidebarOverlay.classList.toggle('hidden');
        });

        sidebarOverlay.addEventListener('click', () => {
            sidebar.classList.add('-translate-x-full');
            sidebarOverlay.classList.add('hidden');
        });

        // Dark Mode Toggle
        const darkModeToggle = document.getElementById('darkModeToggle');
        const themeIcon = document.getElementById('theme-icon');
        const themeText = document.querySelector('.theme-text');

        let isDarkMode = localStorage.getItem('theme') === 'dark';

        function updateThemeUI() {
            if (isDarkMode) {
                document.body.classList.add('dark');
                themeIcon.classList.remove('fa-moon');
                themeIcon.classList.add('fa-sun');
                themeText.textContent = 'Modo Claro';
            } else {
                document.body.classList.remove('dark');
                themeIcon.classList.remove('fa-sun');
                themeIcon.classList.add('fa-moon');
                themeText.textContent = 'Modo Oscuro';
            }
        }

        updateThemeUI();

        darkModeToggle.addEventListener('click', () => {
            isDarkMode = !isDarkMode;
            localStorage.setItem('theme', isDarkMode ? 'dark' : 'light');
            updateThemeUI();
        });
    </script>
</body>
</html>

<?php
$conn->close();
?>
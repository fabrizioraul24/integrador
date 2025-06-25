<?php
// Conexión a la base de datos
$host = '127.0.0.1';
$user = 'root';
$pass = '';
$db = 'noah';
$port = 3308;

$conn = new mysqli($host, $user, $pass, $db, $port);
$conn->set_charset("utf8mb4");

if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

// Configuración para reportes
$archivo_numeracion = 'ultimo_numero_reporte_ventas.txt';
date_default_timezone_set('America/La_Paz'); // Zona horaria de Bolivia

// Función para obtener el siguiente número de reporte
function obtener_siguiente_numero($archivo) {
    $numero = 100; // Valor inicial si el archivo no existe
    
    if (file_exists($archivo)) {
        $numero = (int)file_get_contents($archivo);
    }
    
    // Incrementar el número
    $nuevo_numero = $numero + 1;
    
    // Guardar el nuevo número
    file_put_contents($archivo, $nuevo_numero);
    
    return $nuevo_numero;
}

// Generar reporte en PDF
if (isset($_GET['reporte_ventas'])) {
    require('fpdf/fpdf.php');
    
    // Obtener datos de ventas
    $sql = "SELECT v.id_venta, v.fecha_venta, v.total, 
                   c.nombres_y_apellidos as cliente
            FROM ventas v
            JOIN clientes c ON v.id_cliente = c.id_cliente
            ORDER BY v.fecha_venta DESC";
    
    $result = $conn->query($sql);
    
    // Obtener el siguiente número de reporte
    $numero_reporte = obtener_siguiente_numero($archivo_numeracion);
    
    // Crear el código único del reporte
    $fecha_actual = date("d-m-Y");
    $hora_actual = date("H-i");
    $codigo_reporte = "REP-VENTAS-$numero_reporte-$fecha_actual-$hora_actual";
    
    // Crear PDF
    $pdf = new FPDF('L', 'mm', 'A4');
    $pdf->AddPage();
    $pdf->AliasNbPages();
    
    // Encabezado
    $pdf->SetFont('Arial', 'B', 16);
    $pdf->SetTextColor(78, 107, 175);
    $pdf->Cell(0, 10, 'REPORTE DE VENTAS - PIL ANDINA', 0, 1, 'C');
    
    // Código del reporte
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->SetTextColor(255, 0, 0);
    $pdf->Cell(0, 10, 'Codigo del Reporte: ' . $codigo_reporte, 0, 1, 'C');
    $pdf->SetTextColor(0, 0, 0);
    
    // Información de generación
    $pdf->SetFont('Arial', '', 10);
    $pdf->Cell(40, 7, 'Fecha de generacion:', 0, 0);
    $pdf->Cell(0, 7, date("d/m/Y H:i:s"), 0, 1);
    
    $pdf->Ln(10);
    
    // Tabla de ventas
    $pdf->SetFont('Arial', 'B', 11);
    $pdf->SetFillColor(78, 107, 175);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->Cell(15, 10, '#', 1, 0, 'C', true);
    $pdf->Cell(30, 10, 'ID VENTA', 1, 0, 'C', true);
    $pdf->Cell(50, 10, 'FECHA', 1, 0, 'C', true);
    $pdf->Cell(80, 10, 'CLIENTE', 1, 0, 'C', true);
    $pdf->Cell(30, 10, 'TOTAL', 1, 1, 'C', true);
    
    // Contenido de la tabla
    $pdf->SetFont('Arial', '', 10);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFillColor(240, 245, 255);
    
    $row_num = 0;
    while ($row = $result->fetch_assoc()) {
        $fill = ($row_num % 2) ? true : false;
        $pdf->Cell(15, 8, ++$row_num, 1, 0, 'C', $fill);
        $pdf->Cell(30, 8, $row['id_venta'], 1, 0, 'C', $fill);
        $pdf->Cell(50, 8, $row['fecha_venta'], 1, 0, 'C', $fill);
        $pdf->Cell(80, 8, utf8_decode($row['cliente']), 1, 0, 'L', $fill);
        $pdf->Cell(30, 8, number_format($row['total'], 2), 1, 1, 'R', $fill);
    }
    
    // Pie de página
    $pdf->SetY(-20);
    $pdf->SetFont('Arial', 'I', 8);
    $pdf->Cell(0, 5, 'Codigo del documento: ' . $codigo_reporte, 0, 1, 'L');
    $pdf->Cell(0, 5, 'Pagina ' . $pdf->PageNo() . ' de {nb}', 0, 0, 'C');
    
    // Salida del PDF
    $pdf->Output('I', 'Reporte_Ventas_' . $codigo_reporte . '.pdf');
    exit();
}

// Obtener lista de clientes para mostrar en el select
$clientes = [];
$res_clientes = $conn->query("SELECT id_cliente, nombres_y_apellidos FROM clientes ORDER BY nombres_y_apellidos ASC");
if ($res_clientes && $res_clientes->num_rows > 0) {
    while ($c = $res_clientes->fetch_assoc()) {
        $clientes[] = $c;
    }
}

// Obtener datos de un producto por ID (GET)
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['id_producto'])) {
    $id_producto = intval($_GET['id_producto']);

    if ($id_producto <= 0) {
        echo json_encode(['success' => false, 'message' => 'ID inválido']);
        exit;
    }

    $query = "SELECT id_producto, nombre_producto, tipo_de_presentacion, descripcion, cantidad, precio, foto FROM productos WHERE id_producto = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $id_producto);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $producto = $result->fetch_assoc();
        // Asegurarse de que los valores numéricos sean números
        $producto['cantidad'] = (int)$producto['cantidad'];
        $producto['precio'] = (float)$producto['precio'];
        echo json_encode(['success' => true, 'producto' => $producto]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Producto no encontrado']);
    }
    exit;
}

// Confirmar la venta (POST con datos de la lista temporal)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    if (!is_array($data) || empty($data) || !isset($data['productos']) || !isset($data['id_cliente'])) {
        echo json_encode(['success' => false, 'message' => 'Datos inválidos']);
        exit;
    }

    $productosData = $data['productos'];
    $id_cliente = intval($data['id_cliente']);

    if ($id_cliente <= 0) {
        echo json_encode(['success' => false, 'message' => 'ID de cliente inválido']);
        exit;
    }

    // Validar stock de cada producto
    $total = 0.0;
    $productosVenta = [];

    foreach ($productosData as $item) {
        $id_producto = intval($item['id']);
        $cantidad = intval($item['cantidad']);

        if ($id_producto <= 0 || $cantidad <= 0) {
            echo json_encode(['success' => false, 'message' => 'Datos de producto o cantidad inválidos']);
            exit;
        }

        $query = "SELECT nombre_producto, cantidad, precio FROM productos WHERE id_producto = ? LIMIT 1";
        $stmt = $conn->prepare($query);
        $stmt->bind_param('i', $id_producto);
        $stmt->execute();
        $res = $stmt->get_result();

        if ($res->num_rows <= 0) {
            echo json_encode(['success' => false, 'message' => 'Producto no encontrado (ID '.$id_producto.')']);
            exit;
        }

        $prod = $res->fetch_assoc();
        $stock_actual = (int)$prod['cantidad'];
        $precio_unitario = (float)$prod['precio'];

        if ($cantidad > $stock_actual) {
            echo json_encode(['success' => false, 'message' => "Stock insuficiente para el producto {$prod['nombre_producto']}. Solicita: $cantidad, Stock: $stock_actual"]);
            exit;
        }

        $subtotal = $cantidad * $precio_unitario;
        $total += $subtotal;

        $productosVenta[] = [
            'id_producto' => $id_producto,
            'cantidad' => $cantidad,
            'precio_unitario' => $precio_unitario,
            'subtotal' => $subtotal
        ];
    }

    // Insertar venta
    $conn->begin_transaction();
    try {
        $stmt_venta = $conn->prepare("INSERT INTO ventas (id_cliente, total, fecha_venta) VALUES (?, ?, NOW())");
        $stmt_venta->bind_param("id", $id_cliente, $total);
        $stmt_venta->execute();
        $id_venta = $stmt_venta->insert_id;

        // Insertar detalles de la venta y actualizar stock
        foreach ($productosVenta as $pv) {
            $stmt_detalle = $conn->prepare("INSERT INTO detalle_ventas (id_venta, id_producto, cantidad, precio_unitario, subtotal) VALUES (?, ?, ?, ?, ?)");
            $stmt_detalle->bind_param("iiidd", $id_venta, $pv['id_producto'], $pv['cantidad'], $pv['precio_unitario'], $pv['subtotal']);
            $stmt_detalle->execute();

            // Actualizar stock
            $stmt_update = $conn->prepare("UPDATE productos SET cantidad = cantidad - ? WHERE id_producto = ?");
            $stmt_update->bind_param("ii", $pv['cantidad'], $pv['id_producto']);
            $stmt_update->execute();
        }

        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Venta registrada con éxito.', 'id_venta' => $id_venta]);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Error al registrar la venta: '.$e->getMessage()]);
    }
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Ventas - Pil Andina</title>
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
        
        .form-select {
            border-radius: 10px;
            padding: 10px;
            border: 1px solid #ccc;
            background-color: #fff;
        }
        
        .dark .form-control,
        .dark .form-select {
            background-color: #3a3b3c;
            color: #ccc;
            border-color: #4b5563;
        }
        
        .dark .form-control:focus,
        .dark .form-select:focus {
            border-color: #86acd4;
            box-shadow: inset 0 0 5px rgba(134, 172, 212, 0.4);
        }
        
        .toast {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1050;
            opacity: 0;
            transform: translateX(100%);
            transition: opacity 0.3s ease, transform 0.3s ease;
        }
        
        .toast.show {
            opacity: 1;
            transform: translateX(0);
        }
        
        .product-image {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 4px;
            border: 1px solid #ddd;
        }
        
        .dark .product-image {
            border-color: #4b5563;
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
                
                <a href="../views/ventas_admin.php" class="flex items-center p-3 text-gray-700 dark:text-gray-200 rounded-lg bg-primary text-white group card-hover">
                    <div class="w-8 h-8 bg-red-500 rounded-lg flex items-center justify-center shadow-lg transition-all duration-300">
                        <i class="fas fa-shopping-bag text-white text-sm"></i>
                    </div>
                    <span class="ml-3 font-medium">Ventas</span>
                </a>
                
                <a href="../views/cotizaciones.php" class="flex items-center p-3 text-gray-700 dark:text-gray-200 rounded-lg hover:bg-primary hover:text-white transition-all duration-300 group card-hover">
                    <div class="w-8 h-8 bg-yellow-500 rounded-lg flex items-center justify-center shadow-sm group-hover:shadow-lg transition-all duration-300">
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
                        <h1 class="text-2xl font-bold text-gray-800 dark:text-white">Gestión de Ventas</h1>
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
            <!-- Notification -->
            <div id="notification" class="toast" role="alert">
                <div class="bg-green-500 text-white px-4 py-2 rounded-md shadow-lg" id="notification-body">
                    Acción realizada con éxito.
                </div>
            </div>

            <!-- Report Info -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 animate-fade-in border border-gray-100 dark:border-gray-700 card-hover">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                        <i class="fas fa-file-alt mr-2 text-primary"></i>Información de Reportes
                    </h3>
                    <a href="?reporte_ventas=true" class="bg-green-600 hover:bg-green-700 text-white font-medium py-2 px-4 rounded-lg shadow transition duration-200 flex items-center">
                        <i class="fas fa-file-pdf mr-2"></i> Generar Reporte PDF
                    </a>
                </div>
                <p class="text-gray-600 dark:text-gray-400 mb-2">El próximo reporte generado tendrá un número consecutivo mayor al último utilizado.</p>
                <?php
                $ultimo_numero = file_exists($archivo_numeracion) ? (int)file_get_contents($archivo_numeracion) : 100;
                echo "<p class='font-medium text-gray-900 dark:text-gray-200'><span class='text-primary'>Último número usado:</span> <span class='font-bold'>$ultimo_numero</span></p>";
                ?>
            </div>

            <!-- Add Sale Form -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 animate-slide-up border border-gray-100 dark:border-gray-700 card-hover">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-6">
                    <i class="fas fa-plus-circle mr-2 text-primary"></i>Registrar Nueva Venta
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <div>
                        <label for="id_cliente" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Seleccionar Cliente</label>
                        <select id="id_cliente" class="form-select w-full text-gray-900 dark:text-gray-100 dark:bg-gray-700" required>
                            <option value="">-- Seleccione Cliente --</option>
                            <?php foreach ($clientes as $c): ?>
                                <option value="<?= $c['id_cliente'] ?>"><?= htmlspecialchars($c['nombres_y_apellidos']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label for="id_producto" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Buscar Producto</label>
                        <div class="flex gap-2">
                            <input type="number" id="id_producto" class="form-control flex-grow text-gray-900 dark:text-gray-100 dark:bg-gray-700" placeholder="ID Producto" required>
                            <button type="button" id="buscar-producto" class="bg-primary hover:bg-primary-dark text-white font-medium py-2 px-4 rounded-lg shadow transition duration-200 flex items-center">
                                <i class="fas fa-search mr-2"></i> Buscar
                            </button>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label for="nombre_producto" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Nombre Producto</label>
                        <input type="text" id="nombre_producto" class="form-control w-full text-gray-900 dark:text-gray-100 dark:bg-gray-700" readonly>
                    </div>
                    <div>
                        <label for="tipo_presentacion" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Presentación</label>
                        <input type="text" id="tipo_presentacion" class="form-control w-full text-gray-900 dark:text-gray-100 dark:bg-gray-700" readonly>
                    </div>
                    <div>
                        <label for="precio" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Precio Unitario</label>
                        <input type="number" id="precio" step="0.01" class="form-control w-full text-gray-900 dark:text-gray-100 dark:bg-gray-700" readonly>
                    </div>
                    <div>
                        <label for="cantidad_actual" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Stock Disponible</label>
                        <input type="number" id="cantidad_actual" class="form-control w-full text-gray-900 dark:text-gray-100 dark:bg-gray-700" readonly>
                    </div>
                    <div>
                        <label for="cantidad" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Cantidad a Vender</label>
                        <input type="number" id="cantidad" class="form-control w-full text-gray-900 dark:text-gray-100 dark:bg-gray-700" placeholder="Cantidad" required>
                    </div>
                    <div class="flex items-end">
                        <button type="button" id="add-to-list" class="bg-primary hover:bg-primary-dark text-white font-medium py-2 px-6 rounded-lg shadow transition duration-200 flex items-center justify-center w-full">
                            <i class="fas fa-cart-plus mr-2"></i> Agregar al Carrito
                        </button>
                    </div>
                </div>
            </div>

            <!-- Temporary List -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 animate-slide-up border border-gray-100 dark:border-gray-700 card-hover" style="animation-delay: 0.1s;">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                    <i class="fas fa-shopping-cart mr-2 text-primary"></i>Detalle de la Venta
                </h3>
                <div class="overflow-x-auto scrollbar-thin">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-primary text-white">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider">ID</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider">Producto</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider">Presentación</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider">Precio Unit.</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider">Cantidad</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider">Subtotal</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider">Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="temp-list" class="divide-y divide-gray-200 dark:divide-gray-700"></tbody>
                        <tfoot>
                            <tr>
                                <td colspan="5" class="px-4 py-3 text-right font-bold text-gray-900 dark:text-gray-100">TOTAL:</td>
                                <td id="total-venta" class="px-4 py-3 font-bold text-gray-900 dark:text-gray-100">0.00</td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                <div class="mt-6 flex justify-end">
                    <button id="save-all" class="bg-green-600 hover:bg-green-700 text-white font-medium py-2 px-6 rounded-lg shadow transition duration-200 flex items-center">
                        <i class="fas fa-check-circle mr-2"></i> Confirmar Venta
                    </button>
                </div>
            </div>
        </main>
    </div>

<script>
    // Elementos del DOM
    const idProductoInput = document.getElementById('id_producto');
    const cantidadInput = document.getElementById('cantidad');
    const notification = document.getElementById('notification');
    const notificationBody = document.getElementById('notification-body');
    const tempList = document.getElementById('temp-list');
    const idClienteSelect = document.getElementById('id_cliente');
    const totalVentaElement = document.getElementById('total-venta');
    const buscarProductoBtn = document.getElementById('buscar-producto');
    const addToListBtn = document.getElementById('add-to-list');
    const saveAllBtn = document.getElementById('save-all');

    // Campos del formulario
    const campos = {
        nombre: document.getElementById('nombre_producto'),
        presentacion: document.getElementById('tipo_presentacion'),
        precio: document.getElementById('precio'),
        cantidadActual: document.getElementById('cantidad_actual'),
        foto: ''
    };

    // Variables de estado
    let tempItems = [];
    let totalVenta = 0.0;

    // Función para buscar producto
    function buscarProducto() {
        const idProducto = idProductoInput.value.trim();
        
        if (!idProducto) {
            showError('Por favor ingrese un ID de producto');
            return;
        }

        fetch(`?id_producto=${idProducto}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Error en la respuesta del servidor');
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    const producto = data.producto;
                    
                    // Actualizar todos los campos del formulario
                    campos.nombre.value = producto.nombre_producto || '';
                    campos.presentacion.value = producto.tipo_de_presentacion || '';
                    campos.precio.value = producto.precio ? parseFloat(producto.precio).toFixed(2) : '0.00';
                    campos.cantidadActual.value = producto.cantidad || '0';
                    campos.foto = producto.foto || '';
                    
                    showSuccess('Producto encontrado');
                    cantidadInput.focus();
                } else {
                    showError(data.message || 'Producto no encontrado');
                    limpiarCamposProducto();
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showError('Error al buscar el producto');
                limpiarCamposProducto();
            });
    }

    // Función para agregar producto a la lista temporal
    function agregarALista() {
        const idProducto = idProductoInput.value.trim();
        const cantidadSolicitada = cantidadInput.value.trim();

        // Validaciones básicas
        if (!idProducto || !campos.nombre.value) {
            showError('Primero debe buscar un producto válido');
            return;
        }

        if (!cantidadSolicitada || isNaN(cantidadSolicitada) || parseInt(cantidadSolicitada) <= 0) {
            showError('Por favor ingrese una cantidad válida');
            cantidadInput.focus();
            return;
        }

        const stockDisponible = parseInt(campos.cantidadActual.value);
        const cantidad = parseInt(cantidadSolicitada);

        if (cantidad > stockDisponible) {
            showError(`No hay suficiente stock. Disponible: ${stockDisponible}`);
            cantidadInput.focus();
            return;
        }

        const precioUnitario = parseFloat(campos.precio.value);
        const subtotal = precioUnitario * cantidad;

        // Crear objeto del producto a agregar
        const producto = {
            id: idProducto,
            nombre: campos.nombre.value,
            presentacion: campos.presentacion.value,
            cantidad: cantidad,
            precio: precioUnitario,
            subtotal: subtotal,
            foto: campos.foto ? `Uploads/${campos.foto}` : ''
        };

        // Agregar a la lista temporal
        tempItems.push(producto);
        totalVenta += subtotal;
        
        // Actualizar la interfaz
        actualizarListaTemporal();
        limpiarCamposProducto();
        idProductoInput.focus();
    }

    // Función para actualizar la lista temporal en la interfaz
    function actualizarListaTemporal() {
        tempList.innerHTML = '';
        totalVenta = 0; // Reiniciar el total
        
        tempItems.forEach((item, index) => {
            totalVenta += item.subtotal;
            
            const fila = document.createElement('tr');
            fila.className = 'hover:bg-gray-50 dark:hover:bg-gray-700 transition duration-150';
            fila.innerHTML = `
                <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300">${item.id}</td>
                <td class="px-4 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-100">${item.nombre}</td>
                <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300">${item.presentacion}</td>
                <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300">${item.precio.toFixed(2)}</td>
                <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300">${item.cantidad}</td>
                <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300">${item.subtotal.toFixed(2)}</td>
                <td class="px-4 py-4 whitespace-nowrap text-right text-sm font-medium">
                    <button onclick="eliminarProducto(${index})" class="text-red-600 hover:text-red-900">
                        <i class="fas fa-trash-alt mr-1"></i> Eliminar
                    </button>
                </td>
            `;
            
            tempList.appendChild(fila);
        });

        // Actualizar el total
        totalVentaElement.textContent = totalVenta.toFixed(2);
    }

    // Función para confirmar la venta
    function confirmarVenta() {
        if (tempItems.length === 0) {
            showError('No hay productos en la lista de venta');
            return;
        }

        const idCliente = idClienteSelect.value;
        if (!idCliente) {
            showError('Por favor seleccione un cliente');
            idClienteSelect.focus();
            return;
        }

        // Preparar los datos para enviar
        const datosVenta = {
            productos: tempItems.map(item => ({
                id: item.id,
                cantidad: item.cantidad
            })),
            id_cliente: idCliente
        };

        // Enviar la solicitud al servidor
        fetch('', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(datosVenta),
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showSuccess(data.message);
                // Limpiar todo después de una venta exitosa
                tempItems = [];
                totalVenta = 0;
                actualizarListaTemporal();
                limpiarCamposProducto();
                idClienteSelect.value = '';
            } else {
                showError(data.message || 'Error al procesar la venta');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showError('Error al procesar la venta');
        });
    }

    // Función para eliminar un producto de la lista temporal
    function eliminarProducto(index) {
        if (index >= 0 && index < tempItems.length) {
            totalVenta -= tempItems[index].subtotal;
            tempItems.splice(index, 1);
            actualizarListaTemporal();
        }
    }

    // Función para limpiar los campos del producto
    function limpiarCamposProducto() {
        idProductoInput.value = '';
        campos.nombre.value = '';
        campos.presentacion.value = '';
        campos.precio.value = '';
        campos.cantidadActual.value = '';
        cantidadInput.value = '';
        campos.foto = '';
    }

    // Funciones para mostrar notificaciones
    function showError(message) {
        notificationBody.textContent = message;
        notificationBody.className = 'bg-red-500 text-white px-4 py-2 rounded-md shadow-lg';
        notification.classList.add('show');
        setTimeout(() => notification.classList.remove('show'), 3000);
    }

    function showSuccess(message) {
        notificationBody.textContent = message;
        notificationBody.className = 'bg-green-500 text-white px-4 py-2 rounded-md shadow-lg';
        notification.classList.add('show');
        setTimeout(() => notification.classList.remove('show'), 3000);
    }

    // Event Listeners
    buscarProductoBtn.addEventListener('click', buscarProducto);
    idProductoInput.addEventListener('keypress', (e) => {
        if (e.key === 'Enter') {
            e.preventDefault();
            buscarProducto();
        }
    });

    addToListBtn.addEventListener('click', agregarALista);
    cantidadInput.addEventListener('keypress', (e) => {
        if (e.key === 'Enter') {
            e.preventDefault();
            agregarALista();
        }
    });

    saveAllBtn.addEventListener('click', confirmarVenta);

    // Hacer accesibles las funciones desde el HTML
    window.eliminarProducto = eliminarProducto;

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
<?php
session_start();

date_default_timezone_set('America/La_Paz');

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

$archivo_numeracion = 'ultimo_numero_reporte_usuarios.txt';
$search = isset($_GET['search']) ? $_GET['search'] : '';

function obtener_siguiente_numero($archivo) {
    $numero = 100;
    if (file_exists($archivo)) {
        $numero = (int)file_get_contents($archivo);
    }
    $nuevo_numero = $numero + 1;
    file_put_contents($archivo, $nuevo_numero);
    return $nuevo_numero;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'];
    if ($accion === 'agregar') {
        $nombre = $conn->real_escape_string($_POST['nombre']);
        $password = password_hash($_POST['password'], PASSWORD_BCRYPT);
        $id_rol = intval($_POST['id_rol']);
        $email = $conn->real_escape_string($_POST['email']);
        $sql = "INSERT INTO usuarios (nombre_usu, password, id_rol, email) VALUES ('$nombre', '$password', $id_rol, '$email')";
        if ($conn->query($sql)) {
            $mensaje = "Usuario agregado correctamente";
            $tipo_mensaje = "success";
        } else {
            $mensaje = "Error al agregar usuario: " . $conn->error;
            $tipo_mensaje = "error";
        }
    } elseif ($accion === 'editar') {
        $id = intval($_POST['id_usuario']);
        $nombre = $conn->real_escape_string($_POST['nombre']);
        $id_rol = intval($_POST['id_rol']);
        $email = $conn->real_escape_string($_POST['email']);
        $sql_password = "";
        if (!empty($_POST['password'])) {
            $password = password_hash($_POST['password'], PASSWORD_BCRYPT);
            $sql_password = ", password = '$password'";
        }
        $sql = "UPDATE usuarios SET nombre_usu = '$nombre', id_rol = $id_rol, email = '$email' $sql_password WHERE id_usuario = $id";
        if ($conn->query($sql)) {
            $mensaje = "Usuario actualizado correctamente";
            $tipo_mensaje = "success";
        } else {
            $mensaje = "Error al actualizar usuario: " . $conn->error;
            $tipo_mensaje = "error";
        }
    }
}

if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    if (isset($_SESSION['id_usuario']) && $id == $_SESSION['id_usuario']) {
        $mensaje = "No puedes eliminar tu propio usuario mientras estás conectado";
        $tipo_mensaje = "error";
    } else {
        $sql = "DELETE FROM usuarios WHERE id_usuario = $id";
        if ($conn->query($sql)) {
            $mensaje = "Usuario eliminado correctamente";
            $tipo_mensaje = "success";
        } else {
            $mensaje = "Error al eliminar usuario: " . $conn->error;
            $tipo_mensaje = "error";
        }
    }
}

if (isset($_GET['reporte'])) {
    require('fpdf/fpdf.php');
    $sql = "SELECT u.*, ru.nombre_rol FROM usuarios u LEFT JOIN roles_usuarios ru ON u.id_rol = ru.id_rol";
    if (!empty($search)) {
        $sql = "SELECT u.*, ru.nombre_rol FROM usuarios u LEFT JOIN roles_usuarios ru ON u.id_rol = ru.id_rol WHERE u.nombre_usu LIKE '%$search%' OR u.email LIKE '%$search%'";
    }
    $result = $conn->query($sql);
    $numero_reporte = obtener_siguiente_numero($archivo_numeracion);
    $fecha_actual = date("d-m-Y");
    $hora_actual = date("H:i");
    $codigo_reporte = "$numero_reporte-$fecha_actual-$hora_actual-$numero_reporte";
    $pdf = new FPDF('P', 'mm', 'A4');
    $pdf->AddPage();
    $pdf->AliasNbPages();
    $pdf->SetFont('Arial', 'B', 16);
    $pdf->SetTextColor(78, 107, 175);
    $pdf->Cell(0, 10, 'REPORTE DE USUARIOS - PIL ANDINA', 0, 1, 'C');
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->SetTextColor(255, 0, 0);
    $pdf->Cell(0, 10, 'Codigo del Reporte: ' . $codigo_reporte, 0, 1, 'C');
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFont('Arial', '', 10);
    $pdf->Cell(40, 7, 'Fecha de generacion:', 0, 0);
    $pdf->Cell(0, 7, date("d/m/Y H:i:s"), 0, 1);
    $usuario_generado = isset($_SESSION['nombre_usu']) ? $_SESSION['nombre_usu'] : 'Desconocido';
    $pdf->Cell(40, 7, 'Generado por:', 0, 0);
    $pdf->Cell(0, 7, $usuario_generado, 0, 1);
    $pdf->Ln(10);
    $pdf->SetFont('Arial', 'B', 11);
    $pdf->SetFillColor(78, 107, 175);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->Cell(15, 10, '#', 1, 0, 'C', true);
    $pdf->Cell(45, 10, 'USUARIO', 1, 0, 'C', true);
    $pdf->Cell(45, 10, 'EMAIL', 1, 0, 'C', true);
    $pdf->Cell(30, 10, 'ROL', 1, 0, 'C', true);
    $pdf->Cell(30, 10, 'REGISTRO', 1, 1, 'C', true);
    $pdf->SetFont('Arial', '', 10);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFillColor(240, 245, 255);
    $row_num = 0;
    while ($row = $result->fetch_assoc()) {
        $fill = ($row_num % 2) ? true : false;
        $pdf->Cell(15, 8, ++$row_num, 1, 0, 'C', $fill);
        $pdf->Cell(45, 8, utf8_decode($row['nombre_usu']), 1, 0, 'L', $fill);
        $pdf->Cell(45, 8, utf8_decode($row['email']), 1, 0, 'L', $fill);
        $pdf->Cell(30, 8, utf8_decode($row['nombre_rol'] ?? 'Sin rol'), 1, 0, 'C', $fill);
        $pdf->Cell(30, 8, date("d/m/Y", strtotime($row['fecha_registro'])), 1, 1, 'C', $fill);
    }
    $pdf->SetY(-30);
    $pdf->SetFont('Arial', 'I', 8);
    $pdf->Cell(0, 5, 'Codigo del documento: ' . $codigo_reporte, 0, 1, 'L');
    $pdf->Cell(0, 5, 'Pagina ' . $pdf->PageNo() . ' de {nb}', 0, 0, 'C');
    $pdf->Output('I', 'Reporte_Usuarios_' . $codigo_reporte . '.pdf');
    exit();
}

$roles = [];
$result_roles = $conn->query("SELECT * FROM roles_usuarios ORDER BY nombre_rol");
if ($result_roles) {
    while ($row = $result_roles->fetch_assoc()) {
        $roles[] = $row;
    }
}

$sql = "SELECT u.*, ru.nombre_rol FROM usuarios u LEFT JOIN roles_usuarios ru ON u.id_rol = ru.id_rol";
if (!empty($search)) {
    $sql = "SELECT u.*, ru.nombre_rol FROM usuarios u LEFT JOIN roles_usuarios ru ON u.id_rol = ru.id_rol WHERE u.nombre_usu LIKE '%$search%' OR u.email LIKE '%$search%'";
}
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Usuarios - Pil Andina</title>
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
            border-color: var(--primary);
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
            border-color: var(--primary-light);
            box-shadow: inset 0 0 5px rgba(134, 172, 212, 0.4);
        }
        
        .mensaje {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1000;
            padding: 15px 25px;
            border-radius: 8px;
            color: white;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            animation: slideIn 0.5s, fadeOut 0.5s 2.5s forwards;
        }
        
        @keyframes slideIn {
            from { transform: translateX(100%); }
            to { transform: translateX(0); }
        }
        
        @keyframes fadeOut {
            from { opacity: 1; }
            to { opacity: 0; }
        }
        
        .mensaje-success {
            background-color: #48BB78;
        }
        
        .mensaje-error {
            background-color: #F56565;
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
                
                <a href="../views/usuarios.php" class="flex items-center p-3 text-gray-700 dark:text-gray-200 rounded-lg bg-primary text-white group card-hover">
                    <div class="w-8 h-8 bg-blue-500 rounded-lg flex items-center justify-center shadow-lg transition-all duration-300">
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
                    <a href="../logout.php" class="text-gray-500 hover:text-red-500 transition-colors duration-300">
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
                        <h1 class="text-2xl font-bold text-gray-800 dark:text-white">Gestión de Usuarios</h1>
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
            <!-- Messages -->
            <?php if (isset($mensaje)): ?>
                <div class="mensaje mensaje-<?php echo $tipo_mensaje; ?>">
                    <i class="fas <?php echo $tipo_mensaje === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?> mr-2"></i>
                    <?php echo htmlspecialchars($mensaje); ?>
                </div>
            <?php endif; ?>

            <!-- Report Info -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 animate-fade-in border border-gray-100 dark:border-gray-700 card-hover">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                        <i class="fas fa-file-alt mr-2 text-primary"></i>Información de Reportes
                    </h3>
                    <a href="?reporte=true" class="bg-green-600 hover:bg-green-700 text-white font-medium py-2 px-4 rounded-lg shadow transition duration-200 flex items-center">
                        <i class="fas fa-file-pdf mr-2"></i> Generar Reporte PDF
                    </a>
                </div>
                <p class="text-gray-600 dark:text-gray-400 mb-2">El próximo reporte generado tendrá un número consecutivo mayor al último utilizado.</p>
                <?php
                $ultimo_numero = file_exists($archivo_numeracion) ? (int)file_get_contents($archivo_numeracion) : 100;
                echo "<p class='font-medium text-gray-900 dark:text-gray-200'><span class='text-primary'>Último número usado:</span> <span class='font-bold'>$ultimo_numero</span></p>";
                ?>
            </div>

            <!-- Add User Form -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 animate-slide-up border border-gray-100 dark:border-gray-700 card-hover">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-6">
                    <i class="fas fa-user-plus mr-2 text-primary"></i>Agregar Nuevo Usuario
                </h3>
                <form action="" method="POST">
                    <input type="hidden" name="accion" value="agregar">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <div>
                            <label for="nombre" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Nombre de Usuario</label>
                            <input type="text" name="nombre" id="nombre" class="form-control w-full text-gray-900 dark:text-gray-100 dark:bg-gray-700" required>
                        </div>
                        <div>
                            <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Email</label>
                            <input type="email" name="email" id="email" class="form-control w-full text-gray-900 dark:text-gray-100 dark:bg-gray-700" required>
                        </div>
                        <div>
                            <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Contraseña</label>
                            <input type="password" name="password" id="password" class="form-control w-full text-gray-900 dark:text-gray-100 dark:bg-gray-700" required>
                        </div>
                        <div>
                            <label for="id_rol" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Rol</label>
                            <select name="id_rol" id="id_rol" class="form-select w-full text-gray-900 dark:text-gray-100 dark:bg-gray-700" required>
                                <option value="">Seleccione un rol</option>
                                <?php foreach ($roles as $rol): ?>
                                    <option value="<?php echo $rol['id_rol']; ?>"><?php echo htmlspecialchars($rol['nombre_rol']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <button type="submit" class="bg-primary hover:bg-primary-dark text-white font-medium py-2 px-6 rounded-lg shadow transition duration-200 flex items-center">
                        <i class="fas fa-save mr-2"></i> Guardar Usuario
                    </button>
                </form>
            </div>

            <!-- Search Form -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 animate-slide-up border border-gray-100 dark:border-gray-700 card-hover" style="animation-delay: 0.1s;">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                    <i class="fas fa-search mr-2 text-primary"></i>Buscar Usuarios
                </h3>
                <form action="" method="GET" class="flex flex-col md:flex-row gap-4">
                    <input type="text" name="search" class="form-control flex-grow text-gray-900 dark:text-gray-100 dark:bg-gray-700" placeholder="Buscar usuario por nombre o email..." value="<?php echo htmlspecialchars($search); ?>">
                    <div class="flex gap-2">
                        <button type="submit" class="bg-primary hover:bg-primary-dark text-white font-medium py-2 px-4 rounded-lg shadow transition duration-200 flex items-center">
                            <i class="fas fa-search mr-2"></i> Buscar
                        </button>
                        <a href="?" class="bg-gray-500 hover:bg-gray-600 text-white font-medium py-2 px-4 rounded-lg shadow transition duration-200 flex items-center">
                            <i class="fas fa-sync-alt mr-2"></i> Resetear
                        </a>
                    </div>
                </form>
            </div>

            <!-- Users Table -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 animate-slide-up border border-gray-100 dark:border-gray-700 card-hover" style="animation-delay: 0.2s;">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                    <i class="fas fa-users mr-2 text-primary"></i>Listado de Usuarios
                </h3>
                <div class="overflow-x-auto scrollbar-thin">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-primary text-white">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">ID</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Usuario</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Email</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Rol</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Registro</th>
                                <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            <?php while ($row = $result->fetch_assoc()): ?>
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition duration-150">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300"><?php echo $row['id_usuario']; ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-100"><?php echo htmlspecialchars($row['nombre_usu']); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300"><?php echo htmlspecialchars($row['email']); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300"><?php echo htmlspecialchars($row['nombre_rol'] ?? 'Sin rol'); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300"><?php echo date("d/m/Y", strtotime($row['fecha_registro'])); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <button class="text-yellow-600 hover:text-yellow-900 mr-3 edit-btn" 
                                                data-id="<?php echo $row['id_usuario']; ?>"
                                                data-nombre="<?php echo htmlspecialchars($row['nombre_usu']); ?>"
                                                data-email="<?php echo htmlspecialchars($row['email']); ?>"
                                                data-id_rol="<?php echo $row['id_rol']; ?>">
                                            <i class="fas fa-edit mr-1"></i> Editar
                                        </button>
                                        <a href="?delete=<?php echo $row['id_usuario']; ?>" class="text-red-600 hover:text-red-900" onclick="return confirm('¿Estás seguro de eliminar este usuario?')">
                                            <i class="fas fa-trash-alt mr-1"></i> Eliminar
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <!-- Edit Modal -->
    <div class="fixed inset-0 z-50 hidden bg-black bg-opacity-50" id="editModal">
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20">
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl transform transition-all max-w-lg w-full">
                <div class="bg-gradient-primary p-4 text-white flex items-center justify-between">
                    <h3 class="text-lg font-semibold">
                        <i class="fas fa-edit mr-2"></i>Editar Usuario
                    </h3>
                    <button onclick="closeModal()" class="text-white hover:text-gray-200">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
                <div class="p-6">
                    <form action="" method="POST">
                        <input type="hidden" name="accion" value="editar">
                        <input type="hidden" name="id_usuario" id="edit-id_usuario">
                        <div class="mb-4">
                            <label for="edit-nombre" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Nombre de Usuario</label>
                            <input type="text" name="nombre" id="edit-nombre" class="form-control w-full text-gray-900 dark:text-gray-100 dark:bg-gray-700" required>
                        </div>
                        <div class="mb-4">
                            <label for="edit-email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Email</label>
                            <input type="email" name="email" id="edit-email" class="form-control w-full text-gray-900 dark:text-gray-100 dark:bg-gray-700" required>
                        </div>
                        <div class="mb-4">
                            <label for="edit-password" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Nueva Contraseña (dejar vacío para no cambiar)</label>
                            <input type="password" name="password" id="edit-password" class="form-control w-full text-gray-900 dark:text-gray-100 dark:bg-gray-700">
                        </div>
                        <div class="mb-6">
                            <label for="edit-id_rol" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Rol</label>
                            <select name="id_rol" id="edit-id_rol" class="form-select w-full text-gray-900 dark:text-gray-100 dark:bg-gray-700" required>
                                <option value="">Seleccione un rol</option>
                                <?php foreach ($roles as $rol): ?>
                                    <option value="<?php echo $rol['id_rol']; ?>"><?php echo htmlspecialchars($rol['nombre_rol']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="flex justify-end gap-2">
                            <button type="submit" class="bg-primary hover:bg-primary-dark text-white font-medium py-2 px-4 rounded-lg shadow transition duration-200 flex items-center">
                                <i class="fas fa-save mr-2"></i> Guardar Cambios
                            </button>
                            <button type="button" onclick="closeModal()" class="bg-gray-500 hover:bg-gray-600 text-white font-medium py-2 px-4 rounded-lg shadow transition duration-200 flex items-center">
                                <i class="fas fa-times mr-2"></i> Cancelar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Sidebar and Overlay Toggle
        const sidebar = document.getElementById('sidebar');
        const sidebarToggle = document.getElementById('sidebarToggle');
        const sidebarOverlay = document.getElementById('sidebarOverlay');

        sidebarToggle.addEventListener('click', () => {
            sidebar.classList.toggle('-translate-x-full');
            sidebarOverlay.classList.toggle('flex');
            sidebarOverlay.classList.toggle('hidden');
        });

        sidebarOverlay.addEventListener('click', () => {
            sidebar.classList.add('-translate-x-full');
            sidebarOverlay.classList.add('flex');
            sidebarOverlay.classList.remove('hidden');
        });

        // Dark Mode Toggle
        const darkModeToggle = document.getElementById('darkModeToggle');
        const themeIcon = document.getElementById('theme-icon');
        const themeText = document.querySelector('.theme-text');

        // Check for saved theme preference
        let isDarkMode = localStorage.getItem('theme') === 'dark';

        // Function to update UI based on theme
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

        // Apply initial theme
        updateThemeUI();

        darkModeToggle.addEventListener('click', () => {
            isDarkMode = !isDarkMode;
            localStorage.setItem('theme', isDarkMode ? 'dark' : 'light');
            updateThemeUI();
        });

        // Modal Functionality
        function openModal() {
            document.getElementById('editModal').classList.remove('hidden');
        }

        function closeModal() {
            document.getElementById('editModal').classList.add('hidden');
        }

        document.querySelectorAll('.edit-btn').forEach(button => {
            button.addEventListener('click', function() {
                document.getElementById('edit-id_usuario').value = this.getAttribute('data-id');
                document.getElementById('edit-nombre').value = this.getAttribute('data-nombre');
                document.getElementById('edit-email').value = this.getAttribute('data-email');
                document.getElementById('edit-id_rol').value = this.getAttribute('data-id_rol');
                openModal();
            });
        });

        document.getElementById('editModal').addEventListener('click', function(e) {
            if (e.target === this) closeModal();
        });

        // Auto-dismiss messages
        setTimeout(() => {
            document.querySelectorAll('.mensaje').forEach(mensaje => mensaje.style.display = 'none');
        }, 3000);
    </script>
</body>
</html>
<?php
session_start();

if (isset($_GET['logout'])) {
    session_unset();
    session_destroy();
    header("Location: login.php");
    exit();
}

date_default_timezone_set('America/La_Paz');

$host = '127.0.0.1';
$user = 'root';
$pass = '';
$db = 'noah';
$port = 3308;

$conn = new mysqli($host, $user, $pass, $db, $port);
if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");

$fecha_inicio = $_GET['fecha_inicio'] ?? date('Y-m-01');
$fecha_fin = $_GET['fecha_fin'] ?? date('Y-m-d');
$busqueda = $_GET['busqueda'] ?? '';

$fecha_inicio_esc = $conn->real_escape_string($fecha_inicio);
$fecha_fin_esc = $conn->real_escape_string($fecha_fin);
$busqueda_esc = $conn->real_escape_string($busqueda);

$sql = "
SELECT ra.id_actividad, ra.id_usuario, ra.actividad, ra.fecha_actividad, u.nombre_usu 
FROM registro_actividades ra
LEFT JOIN usuarios u ON ra.id_usuario = u.id_usuario
WHERE ra.fecha_actividad BETWEEN '$fecha_inicio_esc 00:00:00' AND '$fecha_fin_esc 23:59:59'
AND (ra.actividad LIKE '%$busqueda_esc%' OR u.nombre_usu LIKE '%$busqueda_esc%')
ORDER BY ra.fecha_actividad DESC
";

$result = $conn->query($sql);

// AJAX detalle
if (isset($_GET['detalle_id'])) {
    $id_actividad = intval($_GET['detalle_id']);
    $stmt = $conn->prepare("SELECT ra.*, u.nombre_usu FROM registro_actividades ra LEFT JOIN usuarios u ON ra.id_usuario = u.id_usuario WHERE ra.id_actividad = ?");
    $stmt->bind_param("i", $id_actividad);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res->num_rows > 0) {
        $detalle = $res->fetch_assoc();
        echo json_encode([
            'success' => true,
            'actividad' => $detalle['actividad'],
            'fecha' => $detalle['fecha_actividad'],
            'usuario' => $detalle['nombre_usu'] ?? 'Desconocido'
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'No se encontró detalle.']);
    }
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kardex - Historial de Movimientos - Pil Andina</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
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
        
        .modal {
            animation: fadeIn 0.3s ease-in;
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
                
                <a href="../views/cotizaciones.php" class="flex items-center p-3 text-gray-700 dark:text-gray-200 rounded-lg hover:bg-primary hover:text-white transition-all duration-300 group card-hover">
                    <div class="w-8 h-8 bg-yellow-500 rounded-lg flex items-center justify-center shadow-sm group-hover:shadow-lg transition-all duration-300">
                        <i class="fas fa-file-invoice text-white text-sm"></i>
                    </div>
                    <span class="ml-3 font-medium">Cotizaciones</span>
                </a>
                
                <a href="../views/kardex.php" class="flex items-center p-3 text-gray-700 dark:text-gray-200 rounded-lg bg-primary text-white group card-hover">
                    <div class="w-8 h-8 bg-teal-500 rounded-lg flex items-center justify-center shadow-lg transition-all duration-300">
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
                        <h1 class="text-2xl font-bold text-gray-800 dark:text-white">Kardex - Historial de Movimientos</h1>
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
            <!-- Header Section -->
            <div class="text-center animate-fade-in">
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">
                    <i class="fas fa-history mr-2 text-primary"></i>Historial de Movimientos (Kardex)
                </h1>
                <p class="text-gray-600 dark:text-gray-400">Consulta y seguimiento detallado de todas las actividades del sistema</p>
            </div>

            <!-- Filter Form -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 animate-slide-up border border-gray-100 dark:border-gray-700 card-hover">
                <form method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
                    <div>
                        <label for="fecha_inicio" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Desde</label>
                        <input type="date" name="fecha_inicio" id="fecha_inicio" value="<?php echo htmlspecialchars($fecha_inicio); ?>" class="form-control w-full text-gray-900 dark:text-gray-100 dark:bg-gray-700" required>
                    </div>
                    <div>
                        <label for="fecha_fin" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Hasta</label>
                        <input type="date" name="fecha_fin" id="fecha_fin" value="<?php echo htmlspecialchars($fecha_fin); ?>" class="form-control w-full text-gray-900 dark:text-gray-100 dark:bg-gray-700" required>
                    </div>
                    <div>
                        <label for="busqueda" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Buscar</label>
                        <input type="text" name="busqueda" id="busqueda" placeholder="Usuario o actividad..." value="<?php echo htmlspecialchars($busqueda); ?>" class="form-control w-full text-gray-900 dark:text-gray-100 dark:bg-gray-700">
                    </div>
                    <div class="md:col-span-3 flex justify-end">
                        <button type="submit" class="bg-primary hover:bg-primary-dark text-white font-medium py-2 px-6 rounded-lg shadow transition-all duration-300 flex items-center">
                            <i class="fas fa-filter mr-2"></i> Filtrar
                        </button>
                    </div>
                </form>
            </div>

            <!-- Activities Table -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 animate-slide-up border border-gray-100 dark:border-gray-700 card-hover" style="animation-delay: 0.1s;">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-6">
                    <i class="fas fa-clipboard-list mr-2 text-primary"></i>Registro de Actividades
                </h3>
                <div class="overflow-x-auto scrollbar-thin">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-primary text-white">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Fecha</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Usuario</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Actividad</th>
                                <th class="px-6 py-3 text-center text-xs font-medium uppercase tracking-wider">Detalles</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            <?php if ($result && $result->num_rows > 0): ?>
                                <?php while($row = $result->fetch_assoc()): ?>
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors duration-200">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300"><?php echo htmlspecialchars(date('d/m/Y H:i', strtotime($row['fecha_actividad']))); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-100"><?php echo htmlspecialchars($row['nombre_usu'] ?? 'Usuario desconocido'); ?></td>
                                        <td class="px-6 py-4 text-sm text-gray-700 dark:text-gray-300"><?php echo htmlspecialchars($row['actividad']); ?></td>
                                        <td class="px-6 py-4 text-center">
                                            <button class="btn-detalles bg-primary hover:bg-primary-dark text-white px-3 py-1 rounded-lg transition-all duration-300" data-id="<?php echo $row['id_actividad']; ?>">
                                                <i class="fas fa-eye mr-1"></i> Ver
                                            </button>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="px-6 py-6 text-center text-gray-500 dark:text-gray-400">No se encontraron movimientos.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <!-- Modal Detalle -->
    <div id="modalDetalle" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg max-w-md w-full mx-4 p-6 modal border border-gray-100 dark:border-gray-700">
            <h2 class="text-xl font-semibold text-primary dark:text-primary-light mb-4">Detalle del Movimiento</h2>
            <div class="space-y-3">
                <p><strong class="text-gray-700 dark:text-gray-300">Usuario:</strong> <span id="modalUsuario" class="text-gray-900 dark:text-gray-100"></span></p>
                <p><strong class="text-gray-700 dark:text-gray-300">Fecha:</strong> <span id="modalFecha" class="text-gray-900 dark:text-gray-100"></span></p>
                <p><strong class="text-gray-700 dark:text-gray-300">Actividad:</strong> <span id="modalActividad" class="text-gray-900 dark:text-gray-100"></span></p>
            </div>
            <div class="mt-6 flex justify-end">
                <button id="cerrarModal" class="bg-green-600 hover:bg-green-700 text-white font-medium py-2 px-4 rounded-lg shadow transition-all duration-300">
                    <i class="fas fa-times mr-2"></i> Cerrar
                </button>
            </div>
        </div>
    </div>

    <script>
        $(document).ready(function(){
            $('.btn-detalles').click(function(){
                const id = $(this).data('id');
                $.get('?detalle_id='+id, function(data){
                    try {
                        const res = JSON.parse(data);
                        if(res.success){
                            $('#modalUsuario').text(res.usuario);
                            $('#modalFecha').text(new Date(res.fecha).toLocaleString('es-ES', { 
                                day: '2-digit', 
                                month: '2-digit', 
                                year: 'numeric', 
                                hour: '2-digit', 
                                minute: '2-digit' 
                            }));
                            $('#modalActividad').text(res.actividad);
                            $('#modalDetalle').removeClass('hidden');
                        } else {
                            alert(res.message);
                        }
                    } catch (e) {
                        console.error('Error parsing JSON:', e);
                        alert('Error al obtener los detalles.');
                    }
                }).fail(function(jqXHR, textStatus, errorThrown) {
                    console.error('AJAX error:', textStatus, errorThrown);
                    alert('Error al conectar con el servidor.');
                });
            });

            $('#cerrarModal').click(function(){
                $('#modalDetalle').addClass('hidden');
            });

            $('#modalDetalle').click(function(e){
                if(e.target === this){
                    $('#modalDetalle').addClass('hidden');
                }
            });
        });

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
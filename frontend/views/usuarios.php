<?php
// Iniciar sesión para acceder a la variable $_SESSION
session_start();

// Configurar la zona horaria para Bolivia
date_default_timezone_set('America/La_Paz');

// Conexión a la base de datos
$host = '127.0.0.1';
$user = 'root';
$pass = '';
$db = 'noah';
$port = 3308;

$conn = new mysqli($host, $user, $pass, $db, $port);

if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

// Inicializar variables
$archivo_numeracion = 'ultimo_numero_reporte_usuarios.txt';
$search = isset($_GET['search']) ? $_GET['search'] : '';

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

// Procesar formularios
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'];
    
    if ($accion === 'agregar') {
        // Agregar nuevo usuario
        $nombre = $conn->real_escape_string($_POST['nombre']);
        $password = password_hash($_POST['password'], PASSWORD_BCRYPT);
        $id_rol = intval($_POST['id_rol']);
        $email = $conn->real_escape_string($_POST['email']);
        
        $sql = "INSERT INTO usuarios (nombre_usu, password, id_rol, email) 
                VALUES ('$nombre', '$password', $id_rol, '$email')";
        
        if ($conn->query($sql)) {
            $mensaje = "Usuario agregado correctamente";
            $tipo_mensaje = "success";
        } else {
            $mensaje = "Error al agregar usuario: " . $conn->error;
            $tipo_mensaje = "error";
        }
    } elseif ($accion === 'editar') {
        // Editar usuario existente
        $id = intval($_POST['id_usuario']);
        $nombre = $conn->real_escape_string($_POST['nombre']);
        $id_rol = intval($_POST['id_rol']);
        $email = $conn->real_escape_string($_POST['email']);
        
        // Actualizar contraseña solo si se proporcionó una nueva
        $sql_password = "";
        if (!empty($_POST['password'])) {
            $password = password_hash($_POST['password'], PASSWORD_BCRYPT);
            $sql_password = ", password = '$password'";
        }
        
        $sql = "UPDATE usuarios SET 
                nombre_usu = '$nombre', 
                id_rol = $id_rol, 
                email = '$email'
                $sql_password
                WHERE id_usuario = $id";
        
        if ($conn->query($sql)) {
            $mensaje = "Usuario actualizado correctamente";
            $tipo_mensaje = "success";
        } else {
            $mensaje = "Error al actualizar usuario: " . $conn->error;
            $tipo_mensaje = "error";
        }
    }
}

// Eliminar usuario
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    
    // No permitir eliminar el usuario actual
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

// Generar reporte en PDF
if (isset($_GET['reporte'])) {
    require('fpdf/fpdf.php');
    
    // Obtener datos de usuarios
    $sql = "SELECT u.*, ru.nombre_rol FROM usuarios u 
            LEFT JOIN roles_usuarios ru ON u.id_rol = ru.id_rol";
    if (!empty($search)) {
        $sql = "SELECT u.*, ru.nombre_rol FROM usuarios u 
                LEFT JOIN roles_usuarios ru ON u.id_rol = ru.id_rol 
                WHERE u.nombre_usu LIKE '%$search%' OR u.email LIKE '%$search%'";
    }
    $result = $conn->query($sql);
    
    // Obtener el siguiente número de reporte
    $numero_reporte = obtener_siguiente_numero($archivo_numeracion);
    
    // Crear el código único del reporte
    $fecha_actual = date("d-m-Y");
    $hora_actual = date("H:i");
    $codigo_reporte = "$numero_reporte-$fecha_actual-$hora_actual-$numero_reporte";
    
    // Crear PDF
    $pdf = new FPDF('P', 'mm', 'A4');
    $pdf->AddPage();
    $pdf->AliasNbPages();
    
    // Encabezado
    $pdf->SetFont('Arial', 'B', 16);
    $pdf->SetTextColor(78, 107, 175);
    $pdf->Cell(0, 10, 'REPORTE DE USUARIOS - PIL ANDINA', 0, 1, 'C');
    
    // Código del reporte
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->SetTextColor(255, 0, 0);
    $pdf->Cell(0, 10, 'Codigo del Reporte: ' . $codigo_reporte, 0, 1, 'C');
    $pdf->SetTextColor(0, 0, 0);
    
    // Información de generación
    $pdf->SetFont('Arial', '', 10);
    $pdf->Cell(40, 7, 'Fecha de generacion:', 0, 0);
    $pdf->Cell(0, 7, date("d/m/Y H:i:s"), 0, 1);
    
    $usuario_generado = isset($_SESSION['nombre_usu']) ? $_SESSION['nombre_usu'] : 'Desconocido';
    $pdf->Cell(40, 7, 'Generado por:', 0, 0);
    $pdf->Cell(0, 7, $usuario_generado, 0, 1);
    
    $pdf->Ln(10);
    
    // Tabla de usuarios
    $pdf->SetFont('Arial', 'B', 11);
    $pdf->SetFillColor(78, 107, 175);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->Cell(15, 10, '#', 1, 0, 'C', true);
    $pdf->Cell(45, 10, 'USUARIO', 1, 0, 'C', true);
    $pdf->Cell(45, 10, 'EMAIL', 1, 0, 'C', true);
    $pdf->Cell(30, 10, 'ROL', 1, 0, 'C', true);
    $pdf->Cell(30, 10, 'REGISTRO', 1, 1, 'C', true);
    
    // Contenido de la tabla
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
    
    // Pie de página
    $pdf->SetY(-30);
    $pdf->SetFont('Arial', 'I', 8);
    $pdf->Cell(0, 5, 'Codigo del documento: ' . $codigo_reporte, 0, 1, 'L');
    $pdf->Cell(0, 5, 'Pagina ' . $pdf->PageNo() . ' de {nb}', 0, 0, 'C');
    
    // Salida del PDF
    $pdf->Output('I', 'Reporte_Usuarios_' . $codigo_reporte . '.pdf');
    exit(); // Terminar la ejecución para que no se muestre HTML
}

// Obtener roles para los select
$roles = [];
$result_roles = $conn->query("SELECT * FROM roles_usuarios ORDER BY nombre_rol");
if ($result_roles) {
    while ($row = $result_roles->fetch_assoc()) {
        $roles[] = $row;
    }
}

// Obtener datos para la vista HTML
$sql = "SELECT u.*, ru.nombre_rol FROM usuarios u 
        LEFT JOIN roles_usuarios ru ON u.id_rol = ru.id_rol";
if (!empty($search)) {
    $sql = "SELECT u.*, ru.nombre_rol FROM usuarios u 
            LEFT JOIN roles_usuarios ru ON u.id_rol = ru.id_rol 
            WHERE u.nombre_usu LIKE '%$search%' OR u.email LIKE '%$search%'";
}
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Usuarios</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Font Awesome CDN para íconos -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Estilos personalizados adicionales */
        .bg-primary {
            background-color: #4E6BAF;
        }
        .hover\:bg-primary-dark:hover {
            background-color: #3a5688;
        }
        .text-primary {
            color: #4E6BAF;
        }
        .border-primary {
            border-color: #4E6BAF;
        }
        /* Estilos para mensajes */
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
<body class="bg-gray-50 font-sans">
<?php if (isset($mensaje)): ?>
    <div class="mensaje mensaje-<?= $tipo_mensaje ?>">
        <i class="fas <?= $tipo_mensaje === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle' ?> mr-2"></i>
        <?= $mensaje ?>
    </div>
<?php endif; ?>

<div class="container mx-auto px-4 py-8 max-w-7xl">
    <!-- Encabezado -->
    <header class="mb-8 text-center">
        <h1 class="text-4xl font-bold text-primary mb-2">
            <i class="fas fa-users-cog mr-2"></i>Gestión de Usuarios
        </h1>
        <p class="text-gray-600">Sistema integral de administración de usuarios</p>
    </header>

    <!-- Información de reportes -->
    <div class="bg-blue-50 border-l-4 border-primary rounded-lg p-4 mb-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-2">
            <i class="fas fa-file-alt mr-2 text-primary"></i>Información de Reportes
        </h3>
        <p class="text-gray-600 mb-1">El próximo reporte generado tendrá un número consecutivo mayor al último utilizado.</p>
        <?php
        $ultimo_numero = file_exists($archivo_numeracion) ? (int)file_get_contents($archivo_numeracion) : 100;
        echo "<p class='font-medium'><span class='text-primary'>Último número usado:</span> <span class='font-bold'>$ultimo_numero</span></p>";
        ?>
    </div>

    <!-- Botón para generar reporte -->
    <div class="flex justify-end mb-6">
        <a href="?reporte=true" class="bg-green-600 hover:bg-green-700 text-white font-medium py-2 px-4 rounded-lg shadow transition duration-200 flex items-center">
            <i class="fas fa-file-pdf mr-2"></i> Generar Reporte PDF
        </a>
    </div>

    <!-- Formulario para agregar usuario -->
    <div class="bg-white rounded-xl shadow-md overflow-hidden mb-8">
        <div class="bg-primary p-4 text-white">
            <h2 class="text-xl font-semibold">
                <i class="fas fa-user-plus mr-2"></i>Agregar Nuevo Usuario
            </h2>
        </div>
        <div class="p-6">
            <form action="" method="POST">
                <input type="hidden" name="accion" value="agregar">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label for="nombre" class="block text-sm font-medium text-gray-700 mb-1">Nombre de Usuario</label>
                        <input type="text" name="nombre" id="nombre" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent" required>
                    </div>
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                        <input type="email" name="email" id="email" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent" required>
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Contraseña</label>
                        <input type="password" name="password" id="password" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent" required>
                    </div>
                    <div>
                        <label for="id_rol" class="block text-sm font-medium text-gray-700 mb-1">Rol</label>
                        <select name="id_rol" id="id_rol" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent" required>
                            <option value="">Seleccione un rol</option>
                            <?php foreach ($roles as $rol): ?>
                                <option value="<?= $rol['id_rol'] ?>"><?= htmlspecialchars($rol['nombre_rol']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <button type="submit" class="bg-primary hover:bg-primary-dark text-white font-medium py-2 px-6 rounded-md shadow transition duration-200 flex items-center justify-center">
                    <i class="fas fa-save mr-2"></i> Guardar Usuario
                </button>
            </form>
        </div>
    </div>

    <!-- Barra de búsqueda -->
    <div class="bg-white rounded-xl shadow-md overflow-hidden mb-6">
        <div class="p-4">
            <form action="" method="GET" class="flex flex-col md:flex-row gap-4">
                <div class="flex-grow">
                    <input type="text" name="search" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent" placeholder="Buscar usuario por nombre o email..." value="<?= htmlspecialchars($search) ?>">
                </div>
                <div class="flex gap-2">
                    <button type="submit" class="bg-primary hover:bg-primary-dark text-white font-medium py-2 px-4 rounded-md shadow transition duration-200 flex items-center">
                        <i class="fas fa-search mr-2"></i> Buscar
                    </button>
                    <a href="?" class="bg-gray-500 hover:bg-gray-600 text-white font-medium py-2 px-4 rounded-md shadow transition duration-200 flex items-center">
                        <i class="fas fa-sync-alt mr-2"></i> Resetear
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Tabla de usuarios -->
    <div class="bg-white rounded-xl shadow-md overflow-hidden mb-8">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
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
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php while ($row = $result->fetch_assoc()) { ?>
                    <tr class="hover:bg-gray-50 transition duration-150">
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700"><?= $row['id_usuario'] ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?= htmlspecialchars($row['nombre_usu']) ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700"><?= $row['email'] ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700"><?= htmlspecialchars($row['nombre_rol'] ?? 'Sin rol') ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700"><?= date("d/m/Y", strtotime($row['fecha_registro'])) ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <button class="text-yellow-600 hover:text-yellow-900 mr-3 edit-btn" 
                                    data-id="<?= $row['id_usuario'] ?>"
                                    data-nombre="<?= htmlspecialchars($row['nombre_usu']) ?>"
                                    data-email="<?= $row['email'] ?>"
                                    data-id_rol="<?= $row['id_rol'] ?>">
                                <i class="fas fa-edit mr-1"></i> Editar
                            </button>
                            <a href="?delete=<?= $row['id_usuario'] ?>" class="text-red-600 hover:text-red-900" onclick="return confirm('¿Estás seguro de eliminar este usuario?')">
                                <i class="fas fa-trash-alt mr-1"></i> Eliminar
                            </a>
                        </td>
                    </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal de Edición -->
<div class="fixed inset-0 z-50 hidden" id="editModal">
    <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 transition-opacity" aria-hidden="true">
            <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
        </div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <div class="bg-primary px-4 py-3 sm:px-6 sm:flex sm:items-center">
                <h3 class="text-lg leading-6 font-medium text-white" id="modalTitle">
                    <i class="fas fa-edit mr-2"></i>Editar Usuario
                </h3>
            </div>
            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <form action="" method="POST">
                    <input type="hidden" name="accion" value="editar">
                    <input type="hidden" name="id_usuario" id="edit-id_usuario">
                    <div class="mb-4">
                        <label for="edit-nombre" class="block text-sm font-medium text-gray-700 mb-1">Nombre de Usuario</label>
                        <input type="text" name="nombre" id="edit-nombre" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent" required>
                    </div>
                    <div class="mb-4">
                        <label for="edit-email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                        <input type="email" name="email" id="edit-email" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent" required>
                    </div>
                    <div class="mb-4">
                        <label for="edit-password" class="block text-sm font-medium text-gray-700 mb-1">Nueva Contraseña (dejar vacío para no cambiar)</label>
                        <input type="password" name="password" id="edit-password" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent">
                    </div>
                    <div class="mb-4">
                        <label for="edit-id_rol" class="block text-sm font-medium text-gray-700 mb-1">Rol</label>
                        <select name="id_rol" id="edit-id_rol" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent" required>
                            <option value="">Seleccione un rol</option>
                            <?php foreach ($roles as $rol): ?>
                                <option value="<?= $rol['id_rol'] ?>"><?= htmlspecialchars($rol['nombre_rol']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-primary text-base font-medium text-white hover:bg-primary-dark focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary sm:ml-3 sm:w-auto sm:text-sm">
                            <i class="fas fa-save mr-2"></i> Guardar Cambios
                        </button>
                        <button type="button" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm" onclick="closeModal()">
                            <i class="fas fa-times mr-2"></i> Cancelar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Función para abrir el modal
    function openModal() {
        document.getElementById('editModal').classList.remove('hidden');
    }
    
    // Función para cerrar el modal
    function closeModal() {
        document.getElementById('editModal').classList.add('hidden');
    }
    
    // Configurar los botones de edición
    document.querySelectorAll('.edit-btn').forEach(button => {
        button.addEventListener('click', function() {
            document.getElementById('edit-id_usuario').value = this.getAttribute('data-id');
            document.getElementById('edit-nombre').value = this.getAttribute('data-nombre');
            document.getElementById('edit-email').value = this.getAttribute('data-email');
            document.getElementById('edit-id_rol').value = this.getAttribute('data-id_rol');
            openModal();
        });
    });
    
    // Cerrar modal al hacer clic fuera del contenido
    document.getElementById('editModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeModal();
        }
    });
    
    // Auto-ocultar mensajes después de 3 segundos
    setTimeout(() => {
        const mensajes = document.querySelectorAll('.mensaje');
        mensajes.forEach(mensaje => {
            mensaje.style.display = 'none';
        });
    }, 3000);
</script>
</body>
</html>
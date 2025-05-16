<?php
<<<<<<< HEAD
=======
// Iniciar sesión para acceder a la variable $_SESSION
session_start();

// Configurar la zona horaria para Bolivia
date_default_timezone_set('America/La_Paz');

>>>>>>> f40753a (comit con el nuevo dash y el cotizaciones)
// Conexión a la base de datos
$host = '127.0.0.1';
$user = 'root';
$pass = '';
$db = 'noah';
<<<<<<< HEAD
$port = 3307;
=======
$port = 3308;
>>>>>>> f40753a (comit con el nuevo dash y el cotizaciones)

$conn = new mysqli($host, $user, $pass, $db, $port);

if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

<<<<<<< HEAD
// Procesar formulario para agregar cliente
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['accion']) && $_POST['accion'] == 'agregar') {
    $nombre = $_POST['nombre'];
    $direccion = $_POST['direccion'];
    $telefono = $_POST['telefono'];
    $nit = $_POST['nit'];
    $email = $_POST['email'];

    $sql = "INSERT INTO clientes (nombres_y_apellidos, direccion, telefono, NIT, email) 
            VALUES ('$nombre', '$direccion', '$telefono', '$nit', '$email')";

    if ($conn->query($sql) === TRUE) {
        echo "<div class='alert alert-success' role='alert'>Cliente agregado correctamente.</div>";
    } else {
        echo "<div class='alert alert-danger' role='alert'>Error: " . $sql . "<br>" . $conn->error . "</div>";
    }
}

// Procesar formulario para editar cliente
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['accion']) && $_POST['accion'] == 'editar') {
    $id_cliente = $_POST['id_cliente'];
    $nombre = $_POST['nombre'];
    $direccion = $_POST['direccion'];
    $telefono = $_POST['telefono'];
    $nit = $_POST['nit'];
    $email = $_POST['email'];

    $sql = "UPDATE clientes SET 
            nombres_y_apellidos = '$nombre', 
            direccion = '$direccion', 
            telefono = '$telefono', 
            NIT = '$nit', 
            email = '$email'
            WHERE id_cliente = $id_cliente";

    if ($conn->query($sql) === TRUE) {
        echo "<div class='alert alert-success' role='alert'>Cliente actualizado correctamente.</div>";
    } else {
        echo "<div class='alert alert-danger' role='alert'>Error: " . $sql . "<br>" . $conn->error . "</div>";
    }
}

// Eliminar cliente
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $sql = "DELETE FROM clientes WHERE id_cliente = $id";
    if ($conn->query($sql) === TRUE) {
        echo "<div class='alert alert-success' role='alert'>Cliente eliminado correctamente.</div>";
    } else {
        echo "<div class='alert alert-danger' role='alert'>Error al eliminar el cliente: " . $conn->error . "</div>";
    }
}

// Búsqueda de clientes por nombre
$search = '';
if (isset($_GET['search'])) {
    $search = $_GET['search'];
    $sql = "SELECT * FROM clientes WHERE nombres_y_apellidos LIKE '%$search%'";
} else {
    $sql = "SELECT * FROM clientes";
}

=======
// Inicializar variables
$archivo_numeracion = 'ultimo_numero_reporte.txt';
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

// Generar reporte en PDF (DEBE estar antes de cualquier HTML)
if (isset($_GET['reporte'])) {
    require('fpdf/fpdf.php');
    
    // Obtener datos de clientes
    $sql = "SELECT * FROM clientes";
    if (!empty($search)) {
        $sql = "SELECT * FROM clientes WHERE nombres_y_apellidos LIKE '%$search%'";
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
    $pdf->Cell(0, 10, 'REPORTE DE CLIENTES - PIL ANDINA', 0, 1, 'C');
    
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
    
    // Tabla de clientes
    $pdf->SetFont('Arial', 'B', 11);
    $pdf->SetFillColor(78, 107, 175);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->Cell(15, 10, '#', 1, 0, 'C', true);
    $pdf->Cell(55, 10, 'NOMBRE COMPLETO', 1, 0, 'C', true);
    $pdf->Cell(70, 10, 'DIRECCION', 1, 0, 'C', true);
    $pdf->Cell(25, 10, 'TELEFONO', 1, 0, 'C', true);
    $pdf->Cell(25, 10, 'NIT', 1, 1, 'C', true);
    
    // Contenido de la tabla
    $pdf->SetFont('Arial', '', 10);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFillColor(240, 245, 255);
    
    $row_num = 0;
    while ($row = $result->fetch_assoc()) {
        $fill = ($row_num % 2) ? true : false;
        $pdf->Cell(15, 8, ++$row_num, 1, 0, 'C', $fill);
        $pdf->Cell(55, 8, utf8_decode($row['nombres_y_apellidos']), 1, 0, 'L', $fill);
        $pdf->Cell(70, 8, utf8_decode($row['direccion']), 1, 0, 'L', $fill);
        $pdf->Cell(25, 8, $row['telefono'], 1, 0, 'C', $fill);
        $pdf->Cell(25, 8, $row['NIT'], 1, 1, 'C', $fill);
    }
    
    // Pie de página
    $pdf->SetY(-30);
    $pdf->SetFont('Arial', 'I', 8);
    $pdf->Cell(0, 5, 'Codigo del documento: ' . $codigo_reporte, 0, 1, 'L');
    $pdf->Cell(0, 5, 'Pagina ' . $pdf->PageNo() . ' de {nb}', 0, 0, 'C');
    
    // Salida del PDF
    $pdf->Output('I', 'Reporte_Clientes_' . $codigo_reporte . '.pdf');
    exit(); // Terminar la ejecución para que no se muestre HTML
}

// [Resto del código PHP para procesar formularios...]

// Obtener datos para la vista HTML
$sql = "SELECT * FROM clientes";
if (!empty($search)) {
    $sql = "SELECT * FROM clientes WHERE nombres_y_apellidos LIKE '%$search%'";
}
>>>>>>> f40753a (comit con el nuevo dash y el cotizaciones)
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Clientes</title>
<<<<<<< HEAD
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f4f6f9; font-family: 'Arial', sans-serif; }
        .container { max-width: 1200px; margin-top: 50px; }
        .form-container { background-color: #fff; padding: 20px; border-radius: 8px; margin-bottom: 30px; }
        .table { margin-top: 30px; }
        .btn { border-radius: 50px; }
    </style>
</head>
<body>
<div class="container">
    <h1>Gestión de Clientes</h1>

    <!-- Formulario para agregar cliente -->
    <div class="form-container">
        <h2>Agregar Cliente</h2>
        <form action="" method="POST">
            <input type="hidden" name="accion" value="agregar">
            <div class="mb-3">
                <label for="nombre" class="form-label">Nombre y Apellidos</label>
                <input type="text" name="nombre" id="nombre" class="form-control" required>
            </div>
            <div class="mb-3">
                <label for="direccion" class="form-label">Dirección</label>
                <input type="text" name="direccion" id="direccion" class="form-control" required>
            </div>
            <div class="mb-3">
                <label for="telefono" class="form-label">Teléfono</label>
                <input type="text" name="telefono" id="telefono" class="form-control" required>
            </div>
            <div class="mb-3">
                <label for="nit" class="form-label">NIT</label>
                <input type="text" name="nit" id="nit" class="form-control" required>
            </div>
            <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <input type="email" name="email" id="email" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary">Agregar Cliente</button>
        </form>
    </div>

    <!-- Tabla de clientes -->
    <table class="table table-bordered">
        <thead>
        <tr>
            <th>ID</th>
            <th>Nombre</th>
            <th>Dirección</th>
            <th>Teléfono</th>
            <th>NIT</th>
            <th>Email</th>
            <th>Acciones</th>
        </tr>
        </thead>
        <tbody>
        <?php while ($row = $result->fetch_assoc()) { ?>
            <tr>
                <td><?= $row['id_cliente'] ?></td>
                <td><?= $row['nombres_y_apellidos'] ?></td>
                <td><?= $row['direccion'] ?></td>
                <td><?= $row['telefono'] ?></td>
                <td><?= $row['NIT'] ?></td>
                <td><?= $row['email'] ?></td>
                <td>
                    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#editModal"
                            data-id="<?= $row['id_cliente'] ?>"
                            data-nombre="<?= $row['nombres_y_apellidos'] ?>"
                            data-direccion="<?= $row['direccion'] ?>"
                            data-telefono="<?= $row['telefono'] ?>"
                            data-nit="<?= $row['NIT'] ?>"
                            data-email="<?= $row['email'] ?>">Editar</button>
                    <a href="?delete=<?= $row['id_cliente'] ?>" class="btn btn-danger">Eliminar</a>
                </td>
            </tr>
        <?php } ?>
        </tbody>
    </table>
</div>

<!-- Modal de Edición -->
<div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editModalLabel">Editar Cliente</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="" method="POST">
                    <input type="hidden" name="accion" value="editar">
                    <input type="hidden" name="id_cliente" id="edit-id_cliente">
                    <div class="mb-3">
                        <label for="edit-nombre" class="form-label">Nombre y Apellidos</label>
                        <input type="text" name="nombre" id="edit-nombre" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit-direccion" class="form-label">Dirección</label>
                        <input type="text" name="direccion" id="edit-direccion" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit-telefono" class="form-label">Teléfono</label>
                        <input type="text" name="telefono" id="edit-telefono" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit-nit" class="form-label">NIT</label>
                        <input type="text" name="nit" id="edit-nit" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit-email" class="form-label">Email</label>
                        <input type="email" name="email" id="edit-email" class="form-control" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Guardar Cambios</button>
=======
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
    </style>
</head>
<body class="bg-gray-50 font-sans">
<!-- [Resto del código HTML permanece igual] -->
<body class="bg-gray-50 font-sans">
<div class="container mx-auto px-4 py-8 max-w-7xl">
    <!-- Encabezado -->
    <header class="mb-8 text-center">
        <h1 class="text-4xl font-bold text-primary mb-2">
            <i class="fas fa-users mr-2"></i>Gestión de Clientes
        </h1>
        <p class="text-gray-600">Sistema integral de administración de clientes</p>
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

    <!-- Formulario para agregar cliente -->
    <div class="bg-white rounded-xl shadow-md overflow-hidden mb-8">
        <div class="bg-primary p-4 text-white">
            <h2 class="text-xl font-semibold">
                <i class="fas fa-user-plus mr-2"></i>Agregar Nuevo Cliente
            </h2>
        </div>
        <div class="p-6">
            <form action="" method="POST">
                <input type="hidden" name="accion" value="agregar">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label for="nombre" class="block text-sm font-medium text-gray-700 mb-1">Nombre y Apellidos</label>
                        <input type="text" name="nombre" id="nombre" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent" required>
                    </div>
                    <div>
                        <label for="direccion" class="block text-sm font-medium text-gray-700 mb-1">Dirección</label>
                        <input type="text" name="direccion" id="direccion" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent" required>
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                    <div>
                        <label for="telefono" class="block text-sm font-medium text-gray-700 mb-1">Teléfono</label>
                        <input type="text" name="telefono" id="telefono" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent" required>
                    </div>
                    <div>
                        <label for="nit" class="block text-sm font-medium text-gray-700 mb-1">NIT</label>
                        <input type="text" name="nit" id="nit" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent" required>
                    </div>
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                        <input type="email" name="email" id="email" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent" required>
                    </div>
                </div>
                <button type="submit" class="bg-primary hover:bg-primary-dark text-white font-medium py-2 px-6 rounded-md shadow transition duration-200 flex items-center justify-center">
                    <i class="fas fa-save mr-2"></i> Guardar Cliente
                </button>
            </form>
        </div>
    </div>

    <!-- Barra de búsqueda -->
    <div class="bg-white rounded-xl shadow-md overflow-hidden mb-6">
        <div class="p-4">
            <form action="" method="GET" class="flex flex-col md:flex-row gap-4">
                <div class="flex-grow">
                    <input type="text" name="search" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent" placeholder="Buscar cliente por nombre..." value="<?= htmlspecialchars($search) ?>">
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

    <!-- Tabla de clientes -->
    <div class="bg-white rounded-xl shadow-md overflow-hidden mb-8">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-primary text-white">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">ID</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Nombre</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Dirección</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Teléfono</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">NIT</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Email</th>
                        <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider">Acciones</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php while ($row = $result->fetch_assoc()) { ?>
                    <tr class="hover:bg-gray-50 transition duration-150">
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700"><?= $row['id_cliente'] ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?= htmlspecialchars($row['nombres_y_apellidos']) ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700"><?= htmlspecialchars($row['direccion']) ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700"><?= $row['telefono'] ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700"><?= $row['NIT'] ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700"><?= $row['email'] ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <button class="text-yellow-600 hover:text-yellow-900 mr-3 edit-btn" 
                                    data-id="<?= $row['id_cliente'] ?>"
                                    data-nombre="<?= htmlspecialchars($row['nombres_y_apellidos']) ?>"
                                    data-direccion="<?= htmlspecialchars($row['direccion']) ?>"
                                    data-telefono="<?= $row['telefono'] ?>"
                                    data-nit="<?= $row['NIT'] ?>"
                                    data-email="<?= $row['email'] ?>">
                                <i class="fas fa-edit mr-1"></i> Editar
                            </button>
                            <a href="?delete=<?= $row['id_cliente'] ?>" class="text-red-600 hover:text-red-900" onclick="return confirm('¿Estás seguro de eliminar este cliente?')">
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
                    <i class="fas fa-edit mr-2"></i>Editar Cliente
                </h3>
            </div>
            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <form action="" method="POST">
                    <input type="hidden" name="accion" value="editar">
                    <input type="hidden" name="id_cliente" id="edit-id_cliente">
                    <div class="mb-4">
                        <label for="edit-nombre" class="block text-sm font-medium text-gray-700 mb-1">Nombre y Apellidos</label>
                        <input type="text" name="nombre" id="edit-nombre" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent" required>
                    </div>
                    <div class="mb-4">
                        <label for="edit-direccion" class="block text-sm font-medium text-gray-700 mb-1">Dirección</label>
                        <input type="text" name="direccion" id="edit-direccion" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent" required>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label for="edit-telefono" class="block text-sm font-medium text-gray-700 mb-1">Teléfono</label>
                            <input type="text" name="telefono" id="edit-telefono" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent" required>
                        </div>
                        <div>
                            <label for="edit-nit" class="block text-sm font-medium text-gray-700 mb-1">NIT</label>
                            <input type="text" name="nit" id="edit-nit" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent" required>
                        </div>
                    </div>
                    <div class="mb-4">
                        <label for="edit-email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                        <input type="email" name="email" id="edit-email" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent" required>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-primary text-base font-medium text-white hover:bg-primary-dark focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary sm:ml-3 sm:w-auto sm:text-sm">
                            <i class="fas fa-save mr-2"></i> Guardar Cambios
                        </button>
                        <button type="button" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm" onclick="closeModal()">
                            <i class="fas fa-times mr-2"></i> Cancelar
                        </button>
                    </div>
>>>>>>> f40753a (comit con el nuevo dash y el cotizaciones)
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.bundle.min.js"></script>
<script>
<<<<<<< HEAD
    const editModal = document.getElementById('editModal');
    editModal.addEventListener('show.bs.modal', function (event) {
        const button = event.relatedTarget;
        document.getElementById('edit-id_cliente').value = button.getAttribute('data-id');
        document.getElementById('edit-nombre').value = button.getAttribute('data-nombre');
        document.getElementById('edit-direccion').value = button.getAttribute('data-direccion');
        document.getElementById('edit-telefono').value = button.getAttribute('data-telefono');
        document.getElementById('edit-nit').value = button.getAttribute('data-nit');
        document.getElementById('edit-email').value = button.getAttribute('data-email');
    });
</script>
</body>
</html>
=======
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
            document.getElementById('edit-id_cliente').value = this.getAttribute('data-id');
            document.getElementById('edit-nombre').value = this.getAttribute('data-nombre');
            document.getElementById('edit-direccion').value = this.getAttribute('data-direccion');
            document.getElementById('edit-telefono').value = this.getAttribute('data-telefono');
            document.getElementById('edit-nit').value = this.getAttribute('data-nit');
            document.getElementById('edit-email').value = this.getAttribute('data-email');
            openModal();
        });
    });
    
    // Cerrar modal al hacer clic fuera del contenido
    document.getElementById('editModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeModal();
        }
    });
</script>
</body>
</html>
>>>>>>> f40753a (comit con el nuevo dash y el cotizaciones)

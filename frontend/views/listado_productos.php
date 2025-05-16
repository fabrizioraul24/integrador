<?php
// Conexión a la base de datos
$host = '127.0.0.1';
$user = 'root';
$pass = '';
<<<<<<< HEAD
$db = 'noah';  // Cambia por tu nombre de base de datos
$port = 3307;  // Puerto de la base de datos
=======
$db = 'noah';
$port = 3308;
>>>>>>> f40753a (comit con el nuevo dash y el cotizaciones)

$conn = new mysqli($host, $user, $pass, $db, $port);

if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

<<<<<<< HEAD
// Procesar formulario para agregar producto
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['accion']) && $_POST['accion'] == 'agregar') {
    // Recibir datos del formulario
    $nombre_producto = $_POST['nombre_producto'];
    $tipo_de_presentacion = $_POST['tipo_presentacion'];
    $descripcion = $_POST['descripcion'];
    $cantidad = $_POST['cantidad'];
    $precio = $_POST['precio'];
    $id_categoria = $_POST['id_categoria'];  // Obtener el id de la categoría seleccionada

    // Validar si la categoría existe
    $categoria_sql = "SELECT * FROM categorias WHERE id_categoria = $id_categoria";
    $categoria_result = $conn->query($categoria_sql);

    if ($categoria_result->num_rows == 0) {
        echo "<div class='alert alert-danger' role='alert'>Error: La categoría seleccionada no existe.</div>";
        exit;
    }

    // Subir imagen
    $foto = '';
    if ($_FILES['foto']['name']) {
        $foto = $_FILES['foto']['name'];
        $target_dir = $_SERVER['DOCUMENT_ROOT'] . "/sistema/backend/uploads/";
        $target_file = $target_dir . basename($foto);
        if (move_uploaded_file($_FILES["foto"]["tmp_name"], $target_file)) {
            // Imagen subida correctamente
        } else {
            $foto = '';  // Si no se pudo subir la imagen
        }
    }

    // Agregar producto
    $sql = "INSERT INTO productos (nombre_producto, tipo_de_presentacion, descripcion, cantidad, precio, foto, id_categoria) 
            VALUES ('$nombre_producto', '$tipo_de_presentacion', '$descripcion', '$cantidad', '$precio', '$foto', '$id_categoria')";

    if ($conn->query($sql) === TRUE) {
        echo "<div class='alert alert-success' role='alert'>Producto agregado correctamente.</div>";
    } else {
        echo "<div class='alert alert-danger' role='alert'>Error: " . $sql . "<br>" . $conn->error . "</div>";
    }
}

// Procesar formulario para editar producto
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['accion']) && $_POST['accion'] == 'editar') {
    // Recibir datos del formulario de edición
    $id_producto = $_POST['id_producto'];
    $nombre_producto = $_POST['nombre_producto'];
    $tipo_de_presentacion = $_POST['tipo_presentacion'];
    $descripcion = $_POST['descripcion'];
    $cantidad = $_POST['cantidad'];
    $precio = $_POST['precio'];
    $id_categoria = $_POST['id_categoria'];  // Obtener el id de la categoría seleccionada

    // Validar si la categoría existe
    $categoria_sql = "SELECT * FROM categorias WHERE id_categoria = $id_categoria";
    $categoria_result = $conn->query($categoria_sql);

    if ($categoria_result->num_rows == 0) {
        echo "<div class='alert alert-danger' role='alert'>Error: La categoría seleccionada no existe.</div>";
        exit;
    }

    // Subir imagen si hay nueva
    $foto = '';
    if ($_FILES['foto']['name']) {
        $foto = $_FILES['foto']['name'];
        $target_dir = $_SERVER['DOCUMENT_ROOT'] . "/sistema/backend/uploads/";
        $target_file = $target_dir . basename($foto);
        if (move_uploaded_file($_FILES["foto"]["tmp_name"], $target_file)) {
            // Imagen subida correctamente
        }
    } else {
        // Si no hay nueva imagen, conservar la imagen anterior
        $foto = $_POST['foto_actual'];
    }

    // Editar producto
    $sql = "UPDATE productos SET 
            nombre_producto = '$nombre_producto', 
            tipo_de_presentacion = '$tipo_de_presentacion', 
            descripcion = '$descripcion', 
            cantidad = '$cantidad', 
            precio = '$precio', 
            foto = '$foto',
            id_categoria = '$id_categoria'
            WHERE id_producto = $id_producto";

    if ($conn->query($sql) === TRUE) {
        echo "<div class='alert alert-success' role='alert'>Producto actualizado correctamente.</div>";
    } else {
        echo "<div class='alert alert-danger' role='alert'>Error: " . $sql . "<br>" . $conn->error . "</div>";
    }
}

// Eliminar producto
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $sql = "DELETE FROM productos WHERE id_producto = $id";
    if ($conn->query($sql) === TRUE) {
        echo "<div class='alert alert-success' role='alert'>Producto eliminado correctamente.</div>";
    } else {
        echo "<div class='alert alert-danger' role='alert'>Error al eliminar el producto: " . $conn->error . "</div>";
    }
}

// Búsqueda de productos por nombre
$search = '';
if (isset($_GET['search'])) {
    $search = $_GET['search'];
    $sql = "SELECT * FROM productos WHERE nombre_producto LIKE '%$search%'";
} else {
    $sql = "SELECT * FROM productos";
}

=======
// Configuración para subida de archivos
$upload_dir = __DIR__ . '/uploads/';
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

// Procesar formulario de agregar/editar producto
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $accion = $_POST['accion'] ?? '';
    
    // Validar y sanitizar datos
    $nombre = $conn->real_escape_string($_POST['nombre_producto'] ?? '');
    $presentacion = $conn->real_escape_string($_POST['tipo_presentacion'] ?? '');
    $descripcion = $conn->real_escape_string($_POST['descripcion'] ?? '');
    $cantidad = intval($_POST['cantidad'] ?? 0);
    $precio = floatval($_POST['precio'] ?? 0);
    $categoria = intval($_POST['id_categoria'] ?? 0);
    
    // Manejo de la imagen (CÓDIGO CORREGIDO)
    $foto_nombre = '';
    if (isset($_FILES['foto'])) {
        if ($_FILES['foto']['error'] == UPLOAD_ERR_OK) {
            $extension = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
            $foto_nombre = uniqid() . '.' . $extension;
            move_uploaded_file($_FILES['foto']['tmp_name'], $upload_dir . $foto_nombre);
        } elseif ($_FILES['foto']['error'] != UPLOAD_ERR_NO_FILE) {
            $mensaje = "Error al subir la imagen: " . $_FILES['foto']['error'];
            $tipo_mensaje = "error";
        }
    }
    
    if ($accion == 'agregar') {
        // Insertar nuevo producto
        $sql = "INSERT INTO productos (nombre_producto, tipo_de_presentacion, descripcion, cantidad, precio, id_categoria, foto) 
                VALUES ('$nombre', '$presentacion', '$descripcion', $cantidad, $precio, $categoria, " . 
                ($foto_nombre ? "'$foto_nombre'" : "NULL") . ")";
        
        if ($conn->query($sql)) {
            $mensaje = "Producto agregado correctamente";
            $tipo_mensaje = "success";
        } else {
            $mensaje = "Error al agregar producto: " . $conn->error;
            $tipo_mensaje = "error";
        }
    } elseif ($accion == 'editar') {
        $id = intval($_POST['id_producto'] ?? 0);
        $foto_actual = $_POST['foto_actual'] ?? '';
        
        // Si no se subió nueva imagen, mantener la actual
        if (!$foto_nombre && $foto_actual) {
            $foto_nombre = $foto_actual;
        }
        
        // Actualizar producto
        $sql = "UPDATE productos SET 
                nombre_producto = '$nombre',
                tipo_de_presentacion = '$presentacion',
                descripcion = '$descripcion',
                cantidad = $cantidad,
                precio = $precio,
                id_categoria = $categoria" .
                ($foto_nombre ? ", foto = '$foto_nombre'" : ", foto = NULL") .
                " WHERE id_producto = $id";
        
        if ($conn->query($sql)) {
            // Si se subió nueva imagen y había una anterior, eliminar la anterior
            if ($foto_nombre && $foto_actual && $foto_nombre != $foto_actual && file_exists($upload_dir . $foto_actual)) {
                unlink($upload_dir . $foto_actual);
            }
            
            $mensaje = "Producto actualizado correctamente";
            $tipo_mensaje = "success";
        } else {
            $mensaje = "Error al actualizar producto: " . $conn->error;
            $tipo_mensaje = "error";
        }
    }
    
    // Redirigir para evitar reenvío del formulario
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// Procesar eliminación de producto
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    
    // Primero obtener información de la foto para eliminarla
    $sql = "SELECT foto FROM productos WHERE id_producto = $id";
    $result = $conn->query($sql);
    $foto = $result->fetch_assoc()['foto'];
    
    // Eliminar el producto
    $sql = "DELETE FROM productos WHERE id_producto = $id";
    if ($conn->query($sql)) {
        // Eliminar la foto si existe
        if ($foto && file_exists($upload_dir . $foto)) {
            unlink($upload_dir . $foto);
        }
        
        $mensaje = "Producto eliminado correctamente";
        $tipo_mensaje = "success";
    } else {
        $mensaje = "Error al eliminar producto: " . $conn->error;
        $tipo_mensaje = "error";
    }
    
    // Redirigir para evitar reenvío del formulario
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// Inicializar variables para reportes
$archivo_numeracion = 'ultimo_numero_reporte_productos.txt';
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
    
    // Establecer zona horaria de Bolivia
    date_default_timezone_set('America/La_Paz');
    
    // Obtener datos de productos
    $sql = "SELECT p.*, c.nombre_categoria FROM productos p 
            LEFT JOIN categorias c ON p.id_categoria = c.id_categoria";
    if (!empty($search)) {
        $sql .= " WHERE p.nombre_producto LIKE '%$search%'";
    }
    $result = $conn->query($sql);
    
    // Obtener el siguiente número de reporte
    $numero_reporte = obtener_siguiente_numero($archivo_numeracion);
    
    // Crear el código único del reporte con fecha y hora de Bolivia
    $fecha_actual = date("d-m-Y");
    $hora_actual = date("H-i");
    $codigo_reporte = "REP-$numero_reporte-$fecha_actual-$hora_actual";
    
    // Crear PDF
    $pdf = new FPDF('L', 'mm', 'A4');
    $pdf->AddPage();
    $pdf->AliasNbPages();
    
    // Encabezado
    $pdf->SetFont('Arial', 'B', 16);
    $pdf->SetTextColor(78, 107, 175);
    $pdf->Cell(0, 10, 'REPORTE DE PRODUCTOS - PIL ANDINA', 0, 1, 'C');
    
    // Código del reporte
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->SetTextColor(255, 0, 0);
    $pdf->Cell(0, 10, 'Código del Reporte: ' . $codigo_reporte, 0, 1, 'C');
    $pdf->SetTextColor(0, 0, 0);
    
    // Información de generación (con fecha y hora de Bolivia)
    $pdf->SetFont('Arial', '', 10);
    $pdf->Cell(40, 7, 'Fecha de generacion:', 0, 0);
    $pdf->Cell(0, 7, date("d/m/Y H:i:s"), 0, 1);
    
    
    $pdf->Ln(10);
    
    // Tabla de productos
    $pdf->SetFont('Arial', 'B', 11);
    $pdf->SetFillColor(78, 107, 175);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->Cell(15, 10, '#', 1, 0, 'C', true);
    $pdf->Cell(50, 10, 'PRODUCTO', 1, 0, 'C', true);
    $pdf->Cell(40, 10, 'PRESENTACION', 1, 0, 'C', true);
    $pdf->Cell(75, 10, 'DESCRIPCION', 1, 0, 'C', true);
    $pdf->Cell(30, 10, 'CANTIDAD', 1, 0, 'C', true);
    $pdf->Cell(20, 10, 'PRECIO', 1, 0, 'C', true);
    $pdf->Cell(40, 10, 'CATEGORIA', 1, 1, 'C', true);
    
    // Contenido de la tabla
    $pdf->SetFont('Arial', '', 10);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFillColor(240, 245, 255);
    
    $row_num = 0;
    while ($row = $result->fetch_assoc()) {
        $fill = ($row_num % 2) ? true : false;
        $pdf->Cell(15, 8, ++$row_num, 1, 0, 'C', $fill);
        $pdf->Cell(50, 8, utf8_decode($row['nombre_producto']), 1, 0, 'L', $fill);
        $pdf->Cell(40, 8, utf8_decode($row['tipo_de_presentacion']), 1, 0, 'L', $fill);
        $pdf->Cell(60, 8, utf8_decode(substr($row['descripcion'], 0, 50) . (strlen($row['descripcion']) > 50 ? '...' : '')), 1, 0, 'L', $fill);
        $pdf->Cell(30, 8, $row['cantidad'], 1, 0, 'C', $fill);
        $pdf->Cell(20, 8, $row['precio'], 1, 0, 'C', $fill);
        $pdf->Cell(40, 8, utf8_decode($row['nombre_categoria']), 1, 1, 'L', $fill);
    }
    
    // Pie de página
    $pdf->SetY(-20);
    $pdf->SetFont('Arial', 'I', 8);
    $pdf->Cell(0, 5, 'Codigo del documento: ' . $codigo_reporte, 0, 1, 'L');
    $pdf->Cell(0, 5, 'Pagina ' . $pdf->PageNo() . ' de {nb}', 0, 0, 'C');
    
    // Salida del PDF
    $pdf->Output('I', 'Reporte_Productos_' . $codigo_reporte . '.pdf');
    exit();
}

// Obtener datos para la vista HTML
$sql = "SELECT p.*, c.nombre_categoria FROM productos p 
        LEFT JOIN categorias c ON p.id_categoria = c.id_categoria";
if (!empty($search)) {
    $sql .= " WHERE p.nombre_producto LIKE '%$search%'";
}
>>>>>>> f40753a (comit con el nuevo dash y el cotizaciones)
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Productos</title>
<<<<<<< HEAD
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f4f6f9;
            font-family: 'Arial', sans-serif;
        }

        .container {
            max-width: 1200px;
            margin-top: 50px;
        }

        .table th, .table td {
            text-align: center;
        }

        .form-container {
            background-color: #ffffff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
        }

        .form-container input,
        .form-container select,
        .form-container textarea {
            margin-bottom: 15px;
        }

        .form-container h2 {
            text-align: center;
            margin-bottom: 20px;
        }

        .card-img-top {
            max-width: 100px;
            max-height: 100px;
            object-fit: cover;
        }

        .table img {
            width: 120px;
        }

        .search-container {
            margin-bottom: 30px;
        }

        .table-container {
            margin-top: 30px;
        }

        .search-bar {
            width: 300px;
            margin: 10px auto;
        }

        .btn {
            border-radius: 50px;
            font-size: 16px;
            padding: 10px 30px;
        }

        .btn-primary {
            background-color: #007bff;
            border: none;
        }

        .btn-primary:hover {
            background-color: #0056b3;
        }

        .btn-success {
            background-color: #28a745;
            border: none;
        }

        .btn-success:hover {
            background-color: #218838;
        }

        .btn-danger {
            background-color: #dc3545;
            border: none;
        }

        .btn-danger:hover {
            background-color: #c82333;
        }
    </style>
</head>
<body>
<div class="container">
    <h1>Gestión de Productos</h1>

    <!-- Formulario para agregar producto -->
    <div class="form-container">
        <h2>Agregar Producto</h2>
        <form action="" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="accion" value="agregar">
            <div class="mb-3">
                <label for="nombre_producto" class="form-label">Nombre del Producto</label>
                <input type="text" name="nombre_producto" id="nombre_producto" class="form-control" required>
            </div>

            <div class="mb-3">
                <label for="tipo_presentacion" class="form-label">Tipo de Presentación</label>
                <input type="text" name="tipo_presentacion" id="tipo_presentacion" class="form-control" required>
            </div>

            <div class="mb-3">
                <label for="descripcion" class="form-label">Descripción</label>
                <textarea name="descripcion" id="descripcion" class="form-control" rows="3" required></textarea>
            </div>

            <div class="mb-3">
                <label for="cantidad" class="form-label">Cantidad</label>
                <input type="number" name="cantidad" id="cantidad" class="form-control" required>
            </div>

            <div class="mb-3">
                <label for="precio" class="form-label">Precio</label>
                <input type="number" name="precio" id="precio" class="form-control" required>
            </div>

            <div class="mb-3">
                <label for="id_categoria" class="form-label">Categoría</label>
                <select name="id_categoria" id="id_categoria" class="form-control" required>
                    <?php
                    $categorias = $conn->query("SELECT * FROM categorias");
                    while ($categoria = $categorias->fetch_assoc()) {
                        echo "<option value='{$categoria['id_categoria']}'>{$categoria['nombre_categoria']}</option>";
                    }
                    ?>
                </select>
            </div>

            <div class="mb-3">
                <label for="foto" class="form-label">Foto</label>
                <input type="file" name="foto" id="foto" class="form-control">
            </div>

            <button type="submit" class="btn btn-primary">Agregar Producto</button>
        </form>
    </div>

    <!-- Barra de búsqueda -->
    <div class="search-container">
        <form class="search-bar">
            <input type="text" class="form-control" placeholder="Buscar producto" name="search" value="<?= $search ?>">
            <button type="submit" class="btn btn-primary">Buscar</button>
        </form>
    </div>

    <!-- Tabla de productos -->
    <div class="table-container">
        <table class="table table-bordered table-hover">
            <thead>
            <tr>
                <th>ID</th>
                <th>Producto</th>
                <th>Presentación</th>
                <th>Descripción</th>
                <th>Cantidad</th>
                <th>Precio</th>
                <th>Foto</th>
                <th>Categoría</th>
                <th>Acciones</th>
            </tr>
            </thead>
            <tbody>
            <?php while ($row = $result->fetch_assoc()) { ?>
                <tr>
                    <td><?= $row['id_producto'] ?></td>
                    <td><?= $row['nombre_producto'] ?></td>
                    <td><?= $row['tipo_de_presentacion'] ?></td>
                    <td><?= $row['descripcion'] ?></td>
                    <td><?= $row['cantidad'] ?></td>
                    <td><?= $row['precio'] ?></td>
                    <td><img src="/sistema/backend/uploads/<?= $row['foto'] ?>" class="img-thumbnail" alt="Foto Producto"></td>
                    <?php
                    $categoria_sql = "SELECT nombre_categoria FROM categorias WHERE id_categoria = {$row['id_categoria']}";
                    $categoria_result = $conn->query($categoria_sql);
                    $categoria_nombre = $categoria_result->fetch_assoc()['nombre_categoria'] ?? 'Sin categoría';
                    ?>
                    <td><?= $categoria_nombre ?></td>
                    <td>
                        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#editModal"
                                data-id="<?= $row['id_producto'] ?>"
                                data-nombre="<?= $row['nombre_producto'] ?>"
                                data-tipo="<?= $row['tipo_de_presentacion'] ?>"
                                data-descripcion="<?= $row['descripcion'] ?>"
                                data-cantidad="<?= $row['cantidad'] ?>"
                                data-precio="<?= $row['precio'] ?>"
                                data-categoria="<?= $row['id_categoria'] ?>"
                                data-foto="<?= $row['foto'] ?>">Editar
                        </button>
                        <a href="?delete=<?= $row['id_producto'] ?>" class="btn btn-danger">Eliminar</a>
                    </td>
                </tr>
            <?php } ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal de Edición -->
<div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editModalLabel">Editar Producto</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="editForm" action="" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="accion" value="editar">
                    <input type="hidden" name="id_producto" id="edit-id_producto">
                    <input type="hidden" name="foto_actual" id="edit-foto_actual">
                    
                    <div class="mb-3">
                        <label for="edit-nombre_producto" class="form-label">Nombre del Producto</label>
                        <input type="text" name="nombre_producto" id="edit-nombre_producto" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label for="edit-tipo_presentacion" class="form-label">Tipo de Presentación</label>
                        <input type="text" name="tipo_presentacion" id="edit-tipo_presentacion" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label for="edit-descripcion" class="form-label">Descripción</label>
                        <textarea name="descripcion" id="edit-descripcion" class="form-control" rows="3" required></textarea>
                    </div>

                    <div class="mb-3">
                        <label for="edit-cantidad" class="form-label">Cantidad</label>
                        <input type="number" name="cantidad" id="edit-cantidad" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label for="edit-precio" class="form-label">Precio</label>
                        <input type="number" name="precio" id="edit-precio" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label for="edit-id_categoria" class="form-label">Categoría</label>
                        <select name="id_categoria" id="edit-id_categoria" class="form-control" required>
=======
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Font Awesome CDN para íconos -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
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
        .max-h-64 {
            max-height: 16rem;
        }
    </style>
</head>
<body class="bg-gray-50 font-sans">
<?php if (isset($mensaje)): ?>
<div class="fixed top-4 right-4 z-50">
    <div class="<?= $tipo_mensaje == 'success' ? 'bg-green-500' : 'bg-red-500' ?> text-white px-4 py-2 rounded-md shadow-lg flex items-center">
        <i class="fas <?= $tipo_mensaje == 'success' ? 'fa-check-circle' : 'fa-exclamation-circle' ?> mr-2"></i>
        <?= $mensaje ?>
    </div>
</div>

<script>
    // Ocultar el mensaje después de 3 segundos
    setTimeout(() => {
        document.querySelector('.fixed.top-4.right-4').remove();
    }, 3000);
</script>
<?php endif; ?>

<div class="container mx-auto px-4 py-8 max-w-7xl">
    <!-- Encabezado -->
    <header class="mb-8 text-center">
        <h1 class="text-4xl font-bold text-primary mb-2">
            <i class="fas fa-boxes mr-2"></i>Gestión de Productos
        </h1>
        <p class="text-gray-600">Sistema integral de administración de productos</p>
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

    <!-- Formulario para agregar producto -->
    <div class="bg-white rounded-xl shadow-md overflow-hidden mb-8">
        <div class="bg-primary p-4 text-white">
            <h2 class="text-xl font-semibold">
                <i class="fas fa-plus-circle mr-2"></i>Agregar Nuevo Producto
            </h2>
        </div>
        <div class="p-6">
            <form action="" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="accion" value="agregar">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label for="nombre_producto" class="block text-sm font-medium text-gray-700 mb-1">Nombre del Producto</label>
                        <input type="text" name="nombre_producto" id="nombre_producto" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent" required>
                    </div>
                    <div>
                        <label for="tipo_presentacion" class="block text-sm font-medium text-gray-700 mb-1">Tipo de Presentación</label>
                        <input type="text" name="tipo_presentacion" id="tipo_presentacion" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent" required>
                    </div>
                </div>
                <div class="mb-4">
                    <label for="descripcion" class="block text-sm font-medium text-gray-700 mb-1">Descripción</label>
                    <textarea name="descripcion" id="descripcion" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent" required></textarea>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                    <div>
                        <label for="cantidad" class="block text-sm font-medium text-gray-700 mb-1">Cantidad</label>
                        <input type="number" name="cantidad" id="cantidad" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent" required>
                    </div>
                    <div>
                        <label for="precio" class="block text-sm font-medium text-gray-700 mb-1">Precio</label>
                        <input type="number" step="0.01" name="precio" id="precio" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent" required>
                    </div>
                    <div>
                        <label for="id_categoria" class="block text-sm font-medium text-gray-700 mb-1">Categoría</label>
                        <select name="id_categoria" id="id_categoria" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent" required>
>>>>>>> f40753a (comit con el nuevo dash y el cotizaciones)
                            <?php
                            $categorias = $conn->query("SELECT * FROM categorias");
                            while ($categoria = $categorias->fetch_assoc()) {
                                echo "<option value='{$categoria['id_categoria']}'>{$categoria['nombre_categoria']}</option>";
                            }
                            ?>
                        </select>
                    </div>
<<<<<<< HEAD

                    <div class="mb-3">
                        <label for="edit-foto" class="form-label">Foto</label>
                        <input type="file" name="foto" id="edit-foto" class="form-control">
                        <img src="" id="edit-img" alt="Foto Producto" class="img-thumbnail mt-2" style="max-width: 100px;">
                    </div>

                    <button type="submit" class="btn btn-primary">Guardar Cambios</button>
=======
                </div>
                <div class="mb-6">
                    <label for="foto" class="block text-sm font-medium text-gray-700 mb-1">Foto del Producto</label>
                    <input type="file" name="foto" id="foto" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent">
                </div>
                <button type="submit" class="bg-primary hover:bg-primary-dark text-white font-medium py-2 px-6 rounded-md shadow transition duration-200 flex items-center justify-center">
                    <i class="fas fa-save mr-2"></i> Guardar Producto
                </button>
            </form>
        </div>
    </div>

    <!-- Barra de búsqueda -->
    <div class="bg-white rounded-xl shadow-md overflow-hidden mb-6">
        <div class="p-4">
            <form action="" method="GET" class="flex flex-col md:flex-row gap-4">
                <div class="flex-grow">
                    <input type="text" name="search" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent" placeholder="Buscar producto por nombre..." value="<?= htmlspecialchars($search) ?>">
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

    <!-- Tabla de productos -->
    <div class="bg-white rounded-xl shadow-md overflow-hidden mb-8">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-primary text-white">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider">ID</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider">Producto</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider">Presentación</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider">Descripción</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider">Cantidad</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider">Precio</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider">Foto</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider">Categoría</th>
                        <th class="px-12 py-3 text-right text-xs font-medium uppercase tracking-wider">Acciones</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php while ($row = $result->fetch_assoc()) { ?>
                    <tr class="hover:bg-gray-50 transition duration-150">
                        <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-700"><?= $row['id_producto'] ?></td>
                        <td class="px-4 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?= htmlspecialchars($row['nombre_producto']) ?></td>
                        <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-700"><?= htmlspecialchars($row['tipo_de_presentacion']) ?></td>
                        <td class="px-4 py-4 text-sm text-gray-700 max-w-xs truncate"><?= htmlspecialchars($row['descripcion']) ?></td>
                        <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-700"><?= $row['cantidad'] ?></td>
                        <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-700"><?= number_format($row['precio'], 2) ?></td>
                        <td class="px-4 py-4 whitespace-nowrap">
                            <?php if (!empty($row['foto'])): ?>
                            <img src="uploads/<?= $row['foto'] ?>" alt="Foto Producto" class="h-16 w-16 object-cover rounded-md">
                            <?php else: ?>
                            <span class="text-gray-400">Sin imagen</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-700"><?= htmlspecialchars($row['nombre_categoria']) ?></td>
                        <td class="px-4 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <button class="text-yellow-600 hover:text-yellow-900 mr-3 edit-btn" 
                                    data-id="<?= $row['id_producto'] ?>"
                                    data-nombre="<?= htmlspecialchars($row['nombre_producto']) ?>"
                                    data-tipo="<?= htmlspecialchars($row['tipo_de_presentacion']) ?>"
                                    data-descripcion="<?= htmlspecialchars($row['descripcion']) ?>"
                                    data-cantidad="<?= $row['cantidad'] ?>"
                                    data-precio="<?= $row['precio'] ?>"
                                    data-categoria="<?= $row['id_categoria'] ?>"
                                    data-foto="<?= $row['foto'] ?>">
                                <i class="fas fa-edit mr-1"></i> Editar
                            </button>
                            <a href="?delete=<?= $row['id_producto'] ?>" class="text-red-600 hover:text-red-900" onclick="return confirm('¿Estás seguro de eliminar este producto?')">
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
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full">
            <div class="bg-primary px-4 py-3 sm:px-6 sm:flex sm:items-center">
                <h3 class="text-lg leading-6 font-medium text-white" id="modalTitle">
                    <i class="fas fa-edit mr-2"></i>Editar Producto
                </h3>
            </div>
            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <form id="editForm" action="" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="accion" value="editar">
                    <input type="hidden" name="id_producto" id="edit-id_producto">
                    <input type="hidden" name="foto_actual" id="edit-foto_actual">
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label for="edit-nombre_producto" class="block text-sm font-medium text-gray-700 mb-1">Nombre del Producto</label>
                            <input type="text" name="nombre_producto" id="edit-nombre_producto" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent" required>
                        </div>
                        <div>
                            <label for="edit-tipo_presentacion" class="block text-sm font-medium text-gray-700 mb-1">Tipo de Presentación</label>
                            <input type="text" name="tipo_presentacion" id="edit-tipo_presentacion" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent" required>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label for="edit-descripcion" class="block text-sm font-medium text-gray-700 mb-1">Descripción</label>
                        <textarea name="descripcion" id="edit-descripcion" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent" required></textarea>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label for="edit-cantidad" class="block text-sm font-medium text-gray-700 mb-1">Cantidad</label>
                            <input type="number" name="cantidad" id="edit-cantidad" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent" required>
                        </div>
                        <div>
                            <label for="edit-precio" class="block text-sm font-medium text-gray-700 mb-1">Precio</label>
                            <input type="number" step="0.01" name="precio" id="edit-precio" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent" required>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label for="edit-id_categoria" class="block text-sm font-medium text-gray-700 mb-1">Categoría</label>
                        <select name="id_categoria" id="edit-id_categoria" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent" required>
                            <?php
                            $categorias = $conn->query("SELECT * FROM categorias");
                            while ($categoria = $categorias->fetch_assoc()) {
                                echo "<option value='{$categoria['id_categoria']}'>{$categoria['nombre_categoria']}</option>";
                            }
                            ?>
                        </select>
                    </div>
                    
                    <div class="mb-4">
                        <label for="edit-foto" class="block text-sm font-medium text-gray-700 mb-1">Foto del Producto</label>
                        <input type="file" name="foto" id="edit-foto" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent">
                        <div class="mt-2">
                            <img src="" id="edit-img" alt="Foto Producto" class="h-32 w-32 object-cover rounded-md border border-gray-300">
                        </div>
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

<<<<<<< HEAD
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Cargar datos en el modal de edición
    const editModal = document.getElementById('editModal');
    editModal.addEventListener('show.bs.modal', function (event) {
        const button = event.relatedTarget;

        document.getElementById('edit-id_producto').value = button.getAttribute('data-id');
        document.getElementById('edit-nombre_producto').value = button.getAttribute('data-nombre');
        document.getElementById('edit-tipo_presentacion').value = button.getAttribute('data-tipo');
        document.getElementById('edit-descripcion').value = button.getAttribute('data-descripcion');
        document.getElementById('edit-cantidad').value = button.getAttribute('data-cantidad');
        document.getElementById('edit-precio').value = button.getAttribute('data-precio');
        document.getElementById('edit-id_categoria').value = button.getAttribute('data-categoria');
        document.getElementById('edit-foto_actual').value = button.getAttribute('data-foto');
        
        const imgSrc = "/sistema/backend/uploads/" + button.getAttribute('data-foto');
        const img = document.getElementById('edit-img');
        img.src = imgSrc;
    });
</script>

</body>
</html>
=======
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
            document.getElementById('edit-id_producto').value = this.getAttribute('data-id');
            document.getElementById('edit-nombre_producto').value = this.getAttribute('data-nombre');
            document.getElementById('edit-tipo_presentacion').value = this.getAttribute('data-tipo');
            document.getElementById('edit-descripcion').value = this.getAttribute('data-descripcion');
            document.getElementById('edit-cantidad').value = this.getAttribute('data-cantidad');
            document.getElementById('edit-precio').value = this.getAttribute('data-precio');
            document.getElementById('edit-id_categoria').value = this.getAttribute('data-categoria');
            document.getElementById('edit-foto_actual').value = this.getAttribute('data-foto');
            
            const imgSrc = "uploads/" + this.getAttribute('data-foto');
            const img = document.getElementById('edit-img');
            img.src = imgSrc;
            img.style.display = this.getAttribute('data-foto') ? 'block' : 'none';
            
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

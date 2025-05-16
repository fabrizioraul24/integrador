<?php
<<<<<<< HEAD
=======
// Conexión a la base de datos
>>>>>>> f40753a (comit con el nuevo dash y el cotizaciones)
$host = '127.0.0.1';
$user = 'root';
$pass = '';
$db = 'noah';
<<<<<<< HEAD
$port = 3307;

$conn = new mysqli($host, $user, $pass, $db, $port);
=======
$port = 3308;

$conn = new mysqli($host, $user, $pass, $db, $port);
$conn->set_charset("utf8mb4");
>>>>>>> f40753a (comit con el nuevo dash y el cotizaciones)

if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

<<<<<<< HEAD
=======
// Configuración para reportes
$archivo_numeracion = 'ultimo_numero_reporte_solicitudes.txt';
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
if (isset($_GET['reporte_solicitudes'])) {
    require('fpdf/fpdf.php');
    
    // Obtener datos de solicitudes
    $sql = "SELECT s.id_solicitud, s.fecha_solicitud, s.solicitante, s.departamento, s.estado,
                   p.nombre_producto, p.tipo_de_presentacion,
                   ds.cantidad, ds.observaciones
            FROM solicitudes s
            JOIN detalle_solicitudes ds ON s.id_solicitud = ds.id_solicitud
            JOIN productos p ON ds.id_producto = p.id_producto
            ORDER BY s.fecha_solicitud DESC";
    
    $result = $conn->query($sql);
    
    // Obtener el siguiente número de reporte
    $numero_reporte = obtener_siguiente_numero($archivo_numeracion);
    
    // Crear el código único del reporte
    $fecha_actual = date("d-m-Y");
    $hora_actual = date("H-i");
    $codigo_reporte = "REP-SOLIC-$numero_reporte-$fecha_actual-$hora_actual";
    
    // Crear PDF
    $pdf = new FPDF('L', 'mm', 'A4');
    $pdf->AddPage();
    $pdf->AliasNbPages();
    
    // Encabezado
    $pdf->SetFont('Arial', 'B', 16);
    $pdf->SetTextColor(78, 107, 175);
    $pdf->Cell(0, 10, 'REPORTE DE SOLICITUDES - PIL ANDINA', 0, 1, 'C');
    
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
    
    // Tabla de solicitudes
    $pdf->SetFont('Arial', 'B', 11);
    $pdf->SetFillColor(78, 107, 175);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->Cell(15, 10, '#', 1, 0, 'C', true);
    $pdf->Cell(15, 10, 'ID', 1, 0, 'C', true);
    $pdf->Cell(40, 10, 'FECHA', 1, 0, 'C', true);
    $pdf->Cell(40, 10, 'SOLICITANTE', 1, 0, 'C', true);
    $pdf->Cell(35, 10, 'DEPARTAMENTO', 1, 0, 'C', true);
    $pdf->Cell(50, 10, 'PRODUCTO', 1, 0, 'C', true);
    $pdf->Cell(25, 10, 'CANTIDAD', 1, 0, 'C', true);
    $pdf->Cell(45, 10, 'OBSERVACIONES', 1, 1, 'C', true);
    
    // Contenido de la tabla
    $pdf->SetFont('Arial', '', 10);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFillColor(240, 245, 255);
    
    $row_num = 0;
    while ($row = $result->fetch_assoc()) {
        $fill = ($row_num % 2) ? true : false;
        $pdf->Cell(15, 8, ++$row_num, 1, 0, 'C', $fill);
        $pdf->Cell(15, 8, $row['id_solicitud'], 1, 0, 'C', $fill);
        $pdf->Cell(40, 8, $row['fecha_solicitud'], 1, 0, 'C', $fill);
        $pdf->Cell(40, 8, utf8_decode($row['solicitante']), 1, 0, 'L', $fill);
        $pdf->Cell(35, 8, utf8_decode($row['departamento']), 1, 0, 'L', $fill);
        $pdf->Cell(50, 8, utf8_decode($row['nombre_producto'] . ' (' . $row['tipo_de_presentacion'] . ')'), 1, 0, 'L', $fill);
        $pdf->Cell(25, 8, $row['cantidad'], 1, 0, 'C', $fill);
        $pdf->Cell(45, 8, utf8_decode($row['observaciones']), 1, 1, 'L', $fill);
    }
    
    // Pie de página
    $pdf->SetY(-20);
    $pdf->SetFont('Arial', 'I', 8);
    $pdf->Cell(0, 5, 'Codigo del documento: ' . $codigo_reporte, 0, 1, 'L');
    $pdf->Cell(0, 5, 'Pagina ' . $pdf->PageNo() . ' de {nb}', 0, 0, 'C');
    
    // Salida del PDF
    $pdf->Output('I', 'Reporte_Solicitudes_' . $codigo_reporte . '.pdf');
    exit();
}

// Obtener datos de un producto por ID (GET)
>>>>>>> f40753a (comit con el nuevo dash y el cotizaciones)
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
<<<<<<< HEAD
        echo json_encode(['success' => true, 'producto' => $result->fetch_assoc()]);
=======
        $producto = $result->fetch_assoc();
        // Asegurarse de que los valores numéricos sean números
        $producto['cantidad'] = (int)$producto['cantidad'];
        $producto['precio'] = (float)$producto['precio'];
        echo json_encode(['success' => true, 'producto' => $producto]);
>>>>>>> f40753a (comit con el nuevo dash y el cotizaciones)
    } else {
        echo json_encode(['success' => false, 'message' => 'Producto no encontrado']);
    }
    exit;
}

<<<<<<< HEAD
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    if (!is_array($data) || empty($data)) {
=======
// Procesar solicitud (POST con datos de la lista temporal)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    if (!is_array($data) || empty($data) || !isset($data['productos']) || !isset($data['solicitante']) || !isset($data['departamento']) || !isset($data['observaciones'])) {
>>>>>>> f40753a (comit con el nuevo dash y el cotizaciones)
        echo json_encode(['success' => false, 'message' => 'Datos inválidos']);
        exit;
    }

<<<<<<< HEAD
    $success = true;
    foreach ($data as $item) {
=======
    $productosData = $data['productos'];
    $solicitante = $conn->real_escape_string($data['solicitante']);
    $departamento = $conn->real_escape_string($data['departamento']);
    $observaciones = $conn->real_escape_string($data['observaciones']);

    // Validar datos de productos
    $productosSolicitud = [];

    foreach ($productosData as $item) {
>>>>>>> f40753a (comit con el nuevo dash y el cotizaciones)
        $id_producto = intval($item['id']);
        $cantidad = intval($item['cantidad']);

        if ($id_producto <= 0 || $cantidad <= 0) {
<<<<<<< HEAD
            $success = false;
            continue;
        }

        $query = "UPDATE productos SET cantidad = cantidad + ? WHERE id_producto = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param('ii', $cantidad, $id_producto);
        $stmt->execute();

        if ($stmt->affected_rows <= 0) {
            $success = false;
        }
    }

    if ($success) {
        echo json_encode(['success' => true, 'message' => 'Traspasos guardados correctamente']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al guardar algunos traspasos']);
=======
            echo json_encode(['success' => false, 'message' => 'Datos de producto o cantidad inválidos']);
            exit;
        }

        $query = "SELECT nombre_producto FROM productos WHERE id_producto = ? LIMIT 1";
        $stmt = $conn->prepare($query);
        $stmt->bind_param('i', $id_producto);
        $stmt->execute();
        $res = $stmt->get_result();

        if ($res->num_rows <= 0) {
            echo json_encode(['success' => false, 'message' => 'Producto no encontrado (ID '.$id_producto.')']);
            exit;
        }

        $prod = $res->fetch_assoc();

        $productosSolicitud[] = [
            'id_producto' => $id_producto,
            'cantidad' => $cantidad,
            'nombre' => $prod['nombre_producto']
        ];
    }

    // Insertar solicitud y detalles
    $conn->begin_transaction();
    try {
        // Insertar cabecera de la solicitud
        $stmt_solicitud = $conn->prepare("INSERT INTO solicitudes (solicitante, departamento, observaciones, estado, fecha_solicitud) VALUES (?, ?, ?, 'Pendiente', NOW())");
        $stmt_solicitud->bind_param("sss", $solicitante, $departamento, $observaciones);
        $stmt_solicitud->execute();
        $id_solicitud = $stmt_solicitud->insert_id;

        // Insertar detalles de la solicitud
        foreach ($productosSolicitud as $ps) {
            $stmt_detalle = $conn->prepare("INSERT INTO detalle_solicitudes (id_solicitud, id_producto, cantidad, observaciones) VALUES (?, ?, ?, ?)");
            $stmt_detalle->bind_param("iiis", $id_solicitud, $ps['id_producto'], $ps['cantidad'], $observaciones);
            $stmt_detalle->execute();
        }

        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Solicitud registrada con éxito.', 'id_solicitud' => $id_solicitud]);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Error al registrar la solicitud: '.$e->getMessage()]);
>>>>>>> f40753a (comit con el nuevo dash y el cotizaciones)
    }
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
<<<<<<< HEAD
    <title>Gestión de Traspasos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.bundle.min.js"></script>
    <style>
        body {
            background-color: #f4f6f9;
            font-family: 'Arial', sans-serif;
        }
        .container {
            max-width: 1200px;
            margin-top: 50px;
        }
        .form-container {
            background-color: #ffffff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
        }
        .form-container h2 {
            text-align: center;
            margin-bottom: 20px;
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
=======
    <title>Sistema de Solicitud de Productos</title>
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
>>>>>>> f40753a (comit con el nuevo dash y el cotizaciones)
        }
        .toast {
            position: fixed;
            top: 1rem;
            right: 1rem;
            z-index: 1050;
            opacity: 0;
            transform: translateY(-20px);
            transition: opacity 0.3s ease, transform 0.3s ease;
        }
        .toast.show {
            opacity: 1;
            transform: translateY(0);
        }
<<<<<<< HEAD
        .error-message {
            color: red;
            font-size: 0.9em;
        }
        .table-container {
            background-color: #ffffff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-top: 30px;
        }
        .table img {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 4px;
        }
        .actions button {
            margin-right: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="text-center mb-4">Gestión de Traspasos</h1>

        <!-- Notificación flotante -->
        <div id="notification" class="toast" role="alert">
            <div class="toast-body bg-success text-white" id="notification-body">
                Acción realizada con éxito.
            </div>
        </div>

        <!-- Formulario de Traspaso -->
        <div class="form-container">
            <h2>Agregar Traspaso</h2>
            <form id="traspaso-form">
                <div class="row">
                    <div class="col-md-4">
                        <label for="id_producto" class="form-label">ID del Producto</label>
                        <input type="number" id="id_producto" class="form-control" placeholder="Ingrese el ID del producto" required>
                    </div>
                    <div class="col-md-8">
                        <label for="nombre_producto" class="form-label">Nombre del Producto</label>
                        <input type="text" id="nombre_producto" class="form-control" readonly>
                    </div>
                </div>

                <div class="row mt-3">
                    <div class="col-md-3">
                        <label for="tipo_presentacion" class="form-label">Presentación</label>
                        <input type="text" id="tipo_presentacion" class="form-control" readonly>
                    </div>
                    <div class="col-md-5">
                        <label for="descripcion" class="form-label">Descripción</label>
                        <input type="text" id="descripcion" class="form-control" readonly>
                    </div>
                    <div class="col-md-2">
                        <label for="cantidad_actual" class="form-label">Stock Actual</label>
                        <input type="number" id="cantidad_actual" class="form-control" readonly>
                    </div>
                    <div class="col-md-2">
                        <label for="precio" class="form-label">Precio</label>
                        <input type="number" id="precio" class="form-control" readonly>
                    </div>
                </div>

                <div class="row mt-3">
                    <div class="col-md-4">
                        <label for="cantidad" class="form-label">Cantidad a Solicitar</label>
                        <input type="number" id="cantidad" class="form-control" placeholder="Cantidad" required>
                    </div>
                </div>

                <div class="mt-3 text-end">
                    <button type="button" class="btn btn-primary" id="add-to-list">Agregar a la Lista</button>
                </div>
            </form>
        </div>

        <!-- Lista de Solicitudes Temporales -->
        <div class="table-container">
            <h2 class="text-center mb-4">Lista Temporal de Solicitudes</h2>
            <table class="table table-bordered text-center">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Producto</th>
                        <th>Presentación</th>
                        <th>Descripción</th>
                        <th>Cantidad</th>
                        <th>Precio</th>
                        <th>Foto</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody id="temp-list"></tbody>
            </table>
            <button class="btn btn-success mt-3" id="save-all">Guardar Traspasos</button>
        </div>
    </div>

    <script>
        const form = document.getElementById('traspaso-form');
        const idProductoInput = document.getElementById('id_producto');
        const cantidadInput = document.getElementById('cantidad');
        const notification = document.getElementById('notification');
        const notificationBody = document.getElementById('notification-body');
        const tempList = document.getElementById('temp-list');

        const campos = {
            nombre: document.getElementById('nombre_producto'),
            presentacion: document.getElementById('tipo_presentacion'),
            descripcion: document.getElementById('descripcion'),
            cantidad: document.getElementById('cantidad_actual'),
            precio: document.getElementById('precio')
        };

        let tempItems = [];

        // Cargar datos del producto al ingresar el ID
        idProductoInput.addEventListener('blur', () => {
            const idProducto = idProductoInput.value;
            if (idProducto) {
                fetch(`?id_producto=${idProducto}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            const producto = data.producto;
                            campos.nombre.value = producto.nombre_producto;
                            campos.presentacion.value = producto.tipo_de_presentacion;
                            campos.descripcion.value = producto.descripcion;
                            campos.cantidad.value = producto.cantidad;
                            campos.precio.value = producto.precio;
                            campos.foto = producto.foto;
                        } else {
                            showError(data.message);
                            clearFields();
                        }
                    })
                    .catch(() => showError('Error al cargar los datos del producto.'));
            }
        });

        document.getElementById('add-to-list').addEventListener('click', () => {
            const idProducto = idProductoInput.value;
            const cantidadSolicitada = cantidadInput.value;

            if (!idProducto || !cantidadSolicitada || cantidadSolicitada <= 0) {
                showError('Por favor, complete todos los campos correctamente');
                return;
            }

            const item = {
                id: idProducto,
                nombre: campos.nombre.value,
                presentacion: campos.presentacion.value,
                descripcion: campos.descripcion.value,
                cantidad: cantidadSolicitada,
                precio: campos.precio.value,
                foto: `/sistema/backend/uploads/${campos.foto}`
            };

            tempItems.push(item);
            updateTempList();
            form.reset();
            clearFields();
        });

        function updateTempList() {
            tempList.innerHTML = '';
            tempItems.forEach((item, index) => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>${item.id}</td>
                    <td>${item.nombre}</td>
                    <td>${item.presentacion}</td>
                    <td>${item.descripcion}</td>
                    <td>${item.cantidad}</td>
                    <td>${item.precio}</td>
                    <td><img src="${item.foto}" alt="Foto" style="width: 50px; height: 50px;"></td>
                    <td>
                        <button class="btn btn-danger btn-sm" onclick="removeItem(${index})">Eliminar</button>
                    </td>
                `;
                tempList.appendChild(row);
            });
        }

        document.getElementById('save-all').addEventListener('click', () => {
            if (tempItems.length === 0) {
                showError('No hay traspasos para guardar.');
                return;
            }

            fetch('', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(tempItems),
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showSuccess(data.message);
                        tempItems = [];
                        updateTempList();
                    } else {
                        showError(data.message);
                    }
                })
                .catch(() => showError('Error al procesar la solicitud.'));
        });

        function removeItem(index) {
            tempItems.splice(index, 1);
            updateTempList();
        }

        function clearFields() {
            Object.values(campos).forEach(campo => campo.value = '');
            idProductoInput.value = '';
            cantidadInput.value = '';
        }

        function showError(message) {
            notificationBody.textContent = message;
            notification.classList.add('show', 'bg-danger');
            setTimeout(() => notification.classList.remove('show', 'bg-danger'), 3000);
        }

        function showSuccess(message) {
            notificationBody.textContent = message;
            notification.classList.add('show', 'bg-success');
            setTimeout(() => notification.classList.remove('show', 'bg-success'), 3000);
        }
    </script>
</body>
</html>
=======
        .product-image {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 4px;
            border: 1px solid #ddd;
        }
    </style>
</head>
<body class="bg-gray-50 font-sans">
<div class="container mx-auto px-4 py-8 max-w-7xl">
    <!-- Encabezado -->
    <header class="mb-8 text-center">
        <h1 class="text-4xl font-bold text-primary mb-2">
            <i class="fas fa-clipboard-list mr-2"></i>Solicitud de Productos
        </h1>
        <p class="text-gray-600">Sistema de solicitud de productos para departamentos</p>
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
        <a href="?reporte_solicitudes=true" class="bg-green-600 hover:bg-green-700 text-white font-medium py-2 px-4 rounded-lg shadow transition duration-200 flex items-center">
            <i class="fas fa-file-pdf mr-2"></i> Generar Reporte PDF
        </a>
    </div>

    <!-- Notificaciones -->
    <div id="notification" class="toast" role="alert">
        <div class="bg-green-500 text-white px-4 py-2 rounded-md shadow-lg" id="notification-body">
            Acción realizada con éxito.
        </div>
    </div>

    <!-- Formulario para agregar solicitud -->
    <div class="bg-white rounded-xl shadow-md overflow-hidden mb-8">
        <div class="bg-primary p-4 text-white">
            <h2 class="text-xl font-semibold">
                <i class="fas fa-plus-circle mr-2"></i>Nueva Solicitud de Productos
            </h2>
        </div>
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                <!-- Solicitante -->
                <div class="col-span-1">
                    <label for="solicitante" class="block text-sm font-medium text-gray-700 mb-1">Solicitante</label>
                    <input type="text" id="solicitante" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent" placeholder="Nombre del solicitante" required>
                </div>
                
                <!-- Departamento -->
                <div class="col-span-1">
                    <label for="departamento" class="block text-sm font-medium text-gray-700 mb-1">Departamento</label>
                    <input type="text" id="departamento" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent" placeholder="Departamento/Área" required>
                </div>
                
                <!-- Observaciones -->
                <div class="col-span-1">
                    <label for="observaciones" class="block text-sm font-medium text-gray-700 mb-1">Observaciones</label>
                    <input type="text" id="observaciones" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent" placeholder="Motivo de la solicitud">
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Sección Buscar Producto -->
                <div class="col-span-1">
                    <label for="id_producto" class="block text-sm font-medium text-gray-700 mb-1">Buscar Producto</label>
                    <div class="flex gap-2">
                        <input type="number" id="id_producto" class="flex-grow px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent" placeholder="ID Producto" required>
                        <button type="button" id="buscar-producto" class="bg-primary hover:bg-primary-dark text-white font-medium py-2 px-4 rounded-md shadow transition duration-200 flex items-center">
                            <i class="fas fa-search mr-2"></i> Buscar
                        </button>
                    </div>
                </div>
                
                <!-- Sección Cantidad -->
                <div class="col-span-1">
                    <label for="cantidad" class="block text-sm font-medium text-gray-700 mb-1">Cantidad Solicitada</label>
                    <input type="number" id="cantidad" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent" placeholder="Cantidad" required>
                </div>
            </div>

            <!-- Detalles del Producto -->
            <div class="mt-6 grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label for="nombre_producto" class="block text-sm font-medium text-gray-700 mb-1">Nombre Producto</label>
                    <input type="text" id="nombre_producto" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent" readonly>
                </div>
                <div>
                    <label for="tipo_presentacion" class="block text-sm font-medium text-gray-700 mb-1">Presentación</label>
                    <input type="text" id="tipo_presentacion" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent" readonly>
                </div>
                <div>
                    <label for="cantidad_actual" class="block text-sm font-medium text-gray-700 mb-1">Stock Disponible</label>
                    <input type="number" id="cantidad_actual" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent" readonly>
                </div>
                <div class="flex items-end">
                    <button type="button" id="add-to-list" class="bg-primary hover:bg-primary-dark text-white font-medium py-2 px-6 rounded-md shadow transition duration-200 flex items-center justify-center w-full">
                        <i class="fas fa-cart-plus mr-2"></i> Agregar a Lista
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Lista Temporal de Productos -->
    <div class="bg-white rounded-xl shadow-md overflow-hidden mb-8">
        <div class="bg-primary p-4 text-white">
            <h2 class="text-xl font-semibold">
                <i class="fas fa-list-check mr-2"></i>Detalle de la Solicitud
            </h2>
        </div>
        <div class="p-6">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-primary text-white">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider">ID</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider">Producto</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider">Presentación</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider">Stock Actual</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider">Cantidad</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider">Foto</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider">Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="temp-list" class="bg-white divide-y divide-gray-200">
                        <!-- Los productos se agregarán aquí dinámicamente -->
                    </tbody>
                </table>
            </div>
            
            <div class="mt-6 flex justify-end">
                <button id="save-all" class="bg-green-600 hover:bg-green-700 text-white font-medium py-2 px-6 rounded-lg shadow transition duration-200 flex items-center">
                    <i class="fas fa-check-circle mr-2"></i> Enviar Solicitud
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    // Elementos del DOM
    const idProductoInput = document.getElementById('id_producto');
    const cantidadInput = document.getElementById('cantidad');
    const notification = document.getElementById('notification');
    const notificationBody = document.getElementById('notification-body');
    const tempList = document.getElementById('temp-list');
    const solicitanteInput = document.getElementById('solicitante');
    const departamentoInput = document.getElementById('departamento');
    const observacionesInput = document.getElementById('observaciones');
    const buscarProductoBtn = document.getElementById('buscar-producto');
    const addToListBtn = document.getElementById('add-to-list');
    const saveAllBtn = document.getElementById('save-all');

    // Campos del formulario
    const campos = {
        nombre: document.getElementById('nombre_producto'),
        presentacion: document.getElementById('tipo_presentacion'),
        cantidadActual: document.getElementById('cantidad_actual'),
        foto: ''
    };

    // Variables de estado
    let tempItems = [];

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

        // Crear objeto del producto a agregar
        const producto = {
            id: idProducto,
            nombre: campos.nombre.value,
            presentacion: campos.presentacion.value,
            cantidad: cantidad,
            stockActual: stockDisponible,
            foto: campos.foto ? `/sistema/backend/uploads/${campos.foto}` : '/sistema/backend/uploads/default-product.png'
        };

        // Verificar si el producto ya está en la lista
        const existe = tempItems.some(item => item.id === producto.id);
        if (existe) {
            showError('Este producto ya está en la lista de solicitud');
            return;
        }

        // Agregar a la lista temporal
        tempItems.push(producto);
        
        // Actualizar la interfaz
        actualizarListaTemporal();
        limpiarCamposProducto();
        idProductoInput.focus();
    }

    // Función para actualizar la lista temporal en la interfaz
    function actualizarListaTemporal() {
        tempList.innerHTML = '';
        
        tempItems.forEach((item, index) => {
            const fila = document.createElement('tr');
            fila.className = 'hover:bg-gray-50';
            fila.innerHTML = `
                <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-700">${item.id}</td>
                <td class="px-4 py-4 whitespace-nowrap text-sm font-medium text-gray-900">${item.nombre}</td>
                <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-700">${item.presentacion}</td>
                <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-700">${item.stockActual}</td>
                <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-700">${item.cantidad}</td>
                <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-700">
                    <img src="${item.foto}" alt="Producto" class="product-image mx-auto">
                </td>
                <td class="px-4 py-4 whitespace-nowrap text-right text-sm font-medium">
                    <button onclick="eliminarProducto(${index})" class="text-red-600 hover:text-red-900">
                        <i class="fas fa-trash-alt mr-1"></i> Eliminar
                    </button>
                </td>
            `;
            
            tempList.appendChild(fila);
        });
    }

    // Función para confirmar la solicitud
    function confirmarSolicitud() {
        if (tempItems.length === 0) {
            showError('No hay productos en la lista de solicitud');
            return;
        }

        const solicitante = solicitanteInput.value.trim();
        const departamento = departamentoInput.value.trim();
        
        if (!solicitante || !departamento) {
            showError('Por favor complete el solicitante y departamento');
            return;
        }

        const observaciones = observacionesInput.value.trim();

        // Preparar los datos para enviar
        const datosSolicitud = {
            productos: tempItems.map(item => ({
                id: item.id,
                cantidad: item.cantidad
            })),
            solicitante: solicitante,
            departamento: departamento,
            observaciones: observaciones
        };

        // Enviar la solicitud al servidor
        fetch('', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(datosSolicitud),
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showSuccess(data.message);
                // Limpiar todo después de una solicitud exitosa
                tempItems = [];
                actualizarListaTemporal();
                limpiarCamposProducto();
                solicitanteInput.value = '';
                departamentoInput.value = '';
                observacionesInput.value = '';
            } else {
                showError(data.message || 'Error al procesar la solicitud');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showError('Error al procesar la solicitud');
        });
    }

    // Función para eliminar un producto de la lista temporal
    function eliminarProducto(index) {
        if (index >= 0 && index < tempItems.length) {
            tempItems.splice(index, 1);
            actualizarListaTemporal();
        }
    }

    // Función para limpiar los campos del producto
    function limpiarCamposProducto() {
        idProductoInput.value = '';
        campos.nombre.value = '';
        campos.presentacion.value = '';
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

    saveAllBtn.addEventListener('click', confirmarSolicitud);

    // Hacer accesibles las funciones desde el HTML
    window.eliminarProducto = eliminarProducto;
</script>
</body>
</html>
>>>>>>> f40753a (comit con el nuevo dash y el cotizaciones)

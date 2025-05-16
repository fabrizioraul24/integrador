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
    <title>Gestión de Ventas</title>
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
    </style>
</head>
<body class="bg-gray-50 font-sans">
<div class="container mx-auto px-4 py-8 max-w-7xl">
    <!-- Encabezado -->
    <header class="mb-8 text-center">
        <h1 class="text-4xl font-bold text-primary mb-2">
            <i class="fas fa-cash-register mr-2"></i>Gestión de Ventas
        </h1>
        <p class="text-gray-600">Sistema integral de administración de ventas</p>
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
        <a href="?reporte_ventas=true" class="bg-green-600 hover:bg-green-700 text-white font-medium py-2 px-4 rounded-lg shadow transition duration-200 flex items-center">
            <i class="fas fa-file-pdf mr-2"></i> Generar Reporte PDF
        </a>
    </div>

    <!-- Notificaciones -->
    <div id="notification" class="toast" role="alert">
        <div class="bg-green-500 text-white px-4 py-2 rounded-md shadow-lg" id="notification-body">
            Acción realizada con éxito.
        </div>
    </div>

    <!-- Formulario para agregar venta -->
    <div class="bg-white rounded-xl shadow-md overflow-hidden mb-8">
        <div class="bg-primary p-4 text-white">
            <h2 class="text-xl font-semibold">
                <i class="fas fa-plus-circle mr-2"></i>Registrar Nueva Venta
            </h2>
        </div>
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Sección Cliente -->
                <div class="col-span-1">
                    <label for="id_cliente" class="block text-sm font-medium text-gray-700 mb-1">Seleccionar Cliente</label>
                    <select id="id_cliente" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent" required>
                        <option value="">-- Seleccione Cliente --</option>
                        <?php foreach ($clientes as $c): ?>
                            <option value="<?= $c['id_cliente'] ?>"><?= htmlspecialchars($c['nombres_y_apellidos']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
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
            </div>

            <!-- Detalles del Producto -->
            <div class="mt-6 grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label for="nombre_producto" class="block text-sm font-medium text-gray-700 mb-1">Nombre Producto</label>
                    <input type="text" id="nombre_producto" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent" readonly>
                </div>
                <div>
                    <label for="tipo_presentacion" class="block text-sm font-medium text-gray-700 mb-1">Presentación</label>
                    <input type="text" id="tipo_presentacion" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent" readonly>
                </div>
                <div>
                    <label for="precio" class="block text-sm font-medium text-gray-700 mb-1">Precio Unitario</label>
                    <input type="number" id="precio" step="0.01" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent" readonly>
                </div>
                <div>
                    <label for="cantidad_actual" class="block text-sm font-medium text-gray-700 mb-1">Stock Disponible</label>
                    <input type="number" id="cantidad_actual" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent" readonly>
                </div>
                <div>
                    <label for="cantidad" class="block text-sm font-medium text-gray-700 mb-1">Cantidad a Vender</label>
                    <input type="number" id="cantidad" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent" placeholder="Cantidad" required>
                </div>
                <div class="flex items-end">
                    <button type="button" id="add-to-list" class="bg-primary hover:bg-primary-dark text-white font-medium py-2 px-6 rounded-md shadow transition duration-200 flex items-center justify-center w-full">
                        <i class="fas fa-cart-plus mr-2"></i> Agregar al Carrito
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Lista Temporal de Productos -->
    <div class="bg-white rounded-xl shadow-md overflow-hidden mb-8">
        <div class="bg-primary p-4 text-white">
            <h2 class="text-xl font-semibold">
                <i class="fas fa-shopping-cart mr-2"></i>Detalle de la Venta
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
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider">Precio Unit.</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider">Cantidad</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider">Subtotal</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider">Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="temp-list" class="bg-white divide-y divide-gray-200">
                        <!-- Los productos se agregarán aquí dinámicamente -->
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="5" class="px-4 py-3 text-right font-bold">TOTAL:</td>
                            <td id="total-venta" class="px-4 py-3 font-bold">0.00</td>
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
    </div>
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
            foto: campos.foto ? `/sistema/backend/uploads/${campos.foto}` : ''
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
            fila.className = 'hover:bg-gray-50';
            fila.innerHTML = `
                <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-700">${item.id}</td>
                <td class="px-4 py-4 whitespace-nowrap text-sm font-medium text-gray-900">${item.nombre}</td>
                <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-700">${item.presentacion}</td>
                <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-700">${item.precio.toFixed(2)}</td>
                <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-700">${item.cantidad}</td>
                <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-700">${item.subtotal.toFixed(2)}</td>
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
</script>
</body>
</html>
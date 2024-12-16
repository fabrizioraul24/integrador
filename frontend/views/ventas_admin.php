<?php
$host = '127.0.0.1';
$user = 'root';
$pass = '';
$db   = 'noah';
$port = 3307;

$conn = new mysqli($host, $user, $pass, $db, $port);
$conn->set_charset("utf8mb4");

if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
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
        echo json_encode(['success' => true, 'producto' => $result->fetch_assoc()]);
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
        echo json_encode(['success' => true, 'message' => 'Venta registrada con éxito.']);
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
        <h1 class="text-center mb-4">Gestión de Ventas</h1>

        <!-- Notificación flotante -->
        <div id="notification" class="toast" role="alert">
            <div class="toast-body bg-success text-white" id="notification-body">
                Acción realizada con éxito.
            </div>
        </div>

        <!-- Formulario de Venta -->
        <div class="form-container">
            <h2>Agregar Venta</h2>
            <form id="venta-form">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="id_cliente" class="form-label">Seleccionar Cliente</label>
                        <select id="id_cliente" class="form-select" required>
                            <option value="">-- Seleccione Cliente --</option>
                            <?php foreach ($clientes as $c): ?>
                                <option value="<?php echo $c['id_cliente']; ?>"><?php echo $c['nombres_y_apellidos']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

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
                        <label for="cantidad" class="form-label">Cantidad a Vender</label>
                        <input type="number" id="cantidad" class="form-control" placeholder="Cantidad" required>
                    </div>
                </div>

                <div class="mt-3 text-end">
                    <button type="button" class="btn btn-primary" id="add-to-list">Agregar a la Lista</button>
                </div>
            </form>
        </div>

        <!-- Lista Temporal de Productos a Vender -->
        <div class="table-container">
            <h2 class="text-center mb-4">Lista Temporal de Productos a Vender</h2>
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
            <button class="btn btn-success mt-3" id="save-all">Confirmar Venta</button>
        </div>
    </div>

    <script>
        const form = document.getElementById('venta-form');
        const idProductoInput = document.getElementById('id_producto');
        const cantidadInput = document.getElementById('cantidad');
        const notification = document.getElementById('notification');
        const notificationBody = document.getElementById('notification-body');
        const tempList = document.getElementById('temp-list');
        const idClienteSelect = document.getElementById('id_cliente');

        const campos = {
            nombre: document.getElementById('nombre_producto'),
            presentacion: document.getElementById('tipo_presentacion'),
            descripcion: document.getElementById('descripcion'),
            cantidad: document.getElementById('cantidad_actual'),
            precio: document.getElementById('precio'),
            foto: ''
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

            // Validamos que la cantidad solicitada no supere el stock
            const stock = parseInt(campos.cantidad.value, 10);
            if (parseInt(cantidadSolicitada, 10) > stock) {
                showError(`Cantidad solicitada (${cantidadSolicitada}) excede el stock (${stock}).`);
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
                showError('No hay productos para vender.');
                return;
            }

            const idCliente = idClienteSelect.value;
            if (!idCliente) {
                showError('Por favor seleccione un cliente.');
                return;
            }

            const payload = {
                productos: tempItems,
                id_cliente: idCliente
            };

            fetch('', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(payload),
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
            Object.values(campos).forEach(campo => {
                if(campo instanceof HTMLInputElement) campo.value = '';
            });
            campos.foto = '';
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

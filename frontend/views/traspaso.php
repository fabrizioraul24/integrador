<?php
$host = '127.0.0.1';
$user = 'root';
$pass = '';
$db = 'noah';
$port = 3307;

$conn = new mysqli($host, $user, $pass, $db, $port);

if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    if (!is_array($data) || empty($data)) {
        echo json_encode(['success' => false, 'message' => 'Datos inválidos']);
        exit;
    }

    $success = true;
    foreach ($data as $item) {
        $id_producto = intval($item['id']);
        $cantidad = intval($item['cantidad']);

        if ($id_producto <= 0 || $cantidad <= 0) {
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
    }
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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

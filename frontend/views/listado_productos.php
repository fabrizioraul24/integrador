<?php
// Conexión a la base de datos
$host = '127.0.0.1';
$user = 'root';
$pass = '';
$db = 'noah';  // Cambia por tu nombre de base de datos
$port = 3307;  // Puerto de la base de datos

$conn = new mysqli($host, $user, $pass, $db, $port);

if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

// Procesar formulario para agregar producto
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['accion']) && $_POST['accion'] == 'agregar') {
    // Recibir datos del formulario
    $nombre_producto = $_POST['nombre_producto'];
    $tipo_de_presentacion = $_POST['tipo_presentacion'];
    $descripcion = $_POST['descripcion'];
    $cantidad = $_POST['cantidad'];
    $precio = $_POST['precio'];
    $id_categoria = $_POST['id_categoria'];  // Obtener el id de la categoría seleccionada
    
    // Subir imagen
    $foto = '';
    if ($_FILES['foto']['name']) {
        $foto = $_FILES['foto']['name'];
        $target_dir = $_SERVER['DOCUMENT_ROOT'] ."/sistema/backend/uploads/";
        $target_file = $target_dir . basename($foto);
        move_uploaded_file($_FILES["foto"]["tmp_name"], $target_file);
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
    
    // Subir imagen si hay nueva
    $foto = '';
    if ($_FILES['foto']['name']) {
        $foto = $_FILES['foto']['name'];
        // $target_dir = "../../../backend/uploads/";
        $target_dir = $_SERVER['DOCUMENT_ROOT'] . "/sistema/backend/uploads/";
        $target_file = $target_dir . basename($foto);
        move_uploaded_file($_FILES["foto"]["tmp_name"], $target_file);
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

$result = $conn->query($sql);

// Obtener un solo producto si estamos editando
$producto_editar = null;
if (isset($_GET['edit'])) {
    $id_producto_editar = $_GET['edit'];
    $sql_editar = "SELECT * FROM productos WHERE id_producto = $id_producto_editar";
    $producto_editar = $conn->query($sql_editar)->fetch_assoc();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Productos</title>

    <!-- Bootstrap CSS -->
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
            max-width: 50px;
            max-height: 50px;
            object-fit: cover;
        }

        .table img {
            width: 50px;
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

        .btn-warning {
            background-color: #ffc107;
            border: none;
        }

        .btn-warning:hover {
            background-color: #e0a800;
        }

        .modal-header {
            background-color: #f8f9fa;
            border-bottom: none;
        }

        .modal-content {
            border-radius: 8px;
        }

        .modal-body {
            padding: 30px;
        }

        .modal-footer {
            border-top: none;
        }
    </style>
</head>
<body>

<div class="container">
    <h1 class="text-center my-5">Gestión de Productos</h1>

    <!-- Búsqueda de productos -->
    <div class="search-container">
        <form method="GET" class="d-flex justify-content-center">
            <input type="text" class="form-control search-bar" name="search" placeholder="Buscar por nombre..." value="<?php echo htmlspecialchars($search); ?>">
            <button type="submit" class="btn btn-primary ms-2">Buscar</button>
        </form>
    </div>

    <!-- Formulario para agregar producto -->
    <div class="form-container">
        <h2>Agregar Producto</h2>
        <form action="" method="post" enctype="multipart/form-data">
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
                <textarea name="descripcion" id="descripcion" class="form-control" rows="4" required></textarea>
            </div>

            <div class="mb-3">
                <label for="cantidad" class="form-label">Cantidad</label>
                <input type="number" name="cantidad" id="cantidad" class="form-control" required>
            </div>

            <div class="mb-3">
                <label for="precio" class="form-label">Precio</label>
                <input type="number" step="0.01" name="precio" id="precio" class="form-control" required>
            </div>

            <div class="mb-3">
                <label for="foto" class="form-label">Foto</label>
                <input type="file" name="foto" id="foto" class="form-control">
            </div>

            <div class="mb-3">
                <label for="id_categoria" class="form-label">Categoría</label>
                <select name="id_categoria" id="id_categoria" class="form-control" required>
                    <option value="">Selecciona una categoría</option>
                    <?php
                    // Obtener las categorías
                    $categorias_result = $conn->query("SELECT * FROM categorias");
                    while ($categoria = $categorias_result->fetch_assoc()) {
                        echo "<option value='{$categoria['id_categoria']}'>{$categoria['nombre_categoria']}</option>";
                    }
                    ?>
                </select>
            </div>

            <button type="submit" class="btn btn-success">Agregar Producto</button>
        </form>
    </div>

    <!-- Tabla de productos -->
    <div class="table-container">
        <table class="table table-striped table-bordered">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Tipo de Presentación</th>
                    <th>Descripción</th>
                    <th>Cantidad</th>
                    <th>Precio</th>
                    <th>Foto</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        echo "<tr>
                                <td>{$row['id_producto']}</td>
                                <td>{$row['nombre_producto']}</td>
                                <td>{$row['tipo_de_presentacion']}</td>
                                <td>{$row['descripcion']}</td>
                                <td>{$row['cantidad']}</td>
                                <td>{$row['precio']}</td>
                                <td><img src='{$_SERVER['DOCUMENT_ROOT']}/sistema/backend/uploads/{$row['foto']}' alt='Foto producto' class='card-img-top'></td>
                                <td>
                                    <button type='button' class='btn btn-warning btn-sm' data-bs-toggle='modal' data-bs-target='#editModal' data-id='{$row['id_producto']}'
                                        data-nombre='{$row['nombre_producto']}' 
                                        data-presentacion='{$row['tipo_de_presentacion']}'
                                        data-descripcion='{$row['descripcion']}'
                                        data-cantidad='{$row['cantidad']}'
                                        data-precio='{$row['precio']}'
                                        data-foto='{$row['foto']}'
                                        data-categoria='{$row['id_categoria']}'>Editar</button>
                                    <a href='?delete={$row['id_producto']}' class='btn btn-danger btn-sm'>Eliminar</a>
                                </td>
                            </tr>";
                    }
                } else {
                    echo "<tr><td colspan='8' class='text-center'>No hay productos disponibles</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal para editar producto -->
<div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editModalLabel">Editar Producto</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="" method="post" enctype="multipart/form-data">
                    <input type="hidden" name="accion" value="editar">
                    <input type="hidden" name="id_producto" id="id_producto">

                    <div class="mb-3">
                        <label for="nombre_producto_modal" class="form-label">Nombre del Producto</label>
                        <input type="text" name="nombre_producto" id="nombre_producto_modal" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label for="tipo_presentacion_modal" class="form-label">Tipo de Presentación</label>
                        <input type="text" name="tipo_presentacion" id="tipo_presentacion_modal" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label for="descripcion_modal" class="form-label">Descripción</label>
                        <textarea name="descripcion" id="descripcion_modal" class="form-control" rows="4" required></textarea>
                    </div>

                    <div class="mb-3">
                        <label for="cantidad_modal" class="form-label">Cantidad</label>
                        <input type="number" name="cantidad" id="cantidad_modal" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label for="precio_modal" class="form-label">Precio</label>
                        <input type="number" step="0.01" name="precio" id="precio_modal" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label for="foto_modal" class="form-label">Foto</label>
                        <input type="file" name="foto" id="foto_modal" class="form-control">
                    </div>

                    <div class="mb-3">
                        <label for="id_categoria_modal" class="form-label">Categoría</label>
                        <select name="id_categoria" id="id_categoria_modal" class="form-control" required>
                            <option value="">Selecciona una categoría</option>
                            <?php
                            // Obtener las categorías
                            $categorias_result = $conn->query("SELECT * FROM categorias");
                            while ($categoria = $categorias_result->fetch_assoc()) {
                                echo "<option value='{$categoria['id_categoria']}'>{$categoria['nombre_categoria']}</option>";
                            }
                            ?>
                        </select>
                    </div>

                    <button type="submit" class="btn btn-warning">Actualizar Producto</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Bootstrap JS y dependencias -->
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.min.js"></script>

<script>
    // Llenar modal con los datos del producto seleccionado para editar
    var editButtons = document.querySelectorAll('[data-bs-toggle="modal"]');
    editButtons.forEach(function(button) {
        button.addEventListener('click', function() {
            var modal = new bootstrap.Modal(document.getElementById('editModal'));
            document.getElementById('id_producto').value = button.getAttribute('data-id');
            document.getElementById('nombre_producto_modal').value = button.getAttribute('data-nombre');
            document.getElementById('tipo_presentacion_modal').value = button.getAttribute('data-presentacion');
            document.getElementById('descripcion_modal').value = button.getAttribute('data-descripcion');
            document.getElementById('cantidad_modal').value = button.getAttribute('data-cantidad');
            document.getElementById('precio_modal').value = button.getAttribute('data-precio');
            document.getElementById('id_categoria_modal').value = button.getAttribute('data-categoria');
            modal.show();
        });
    });
</script>

</body>
</html>

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

$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Productos</title>
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
                    <option value="1">Categoría 1</option>
                    <option value="2">Categoría 2</option>
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
                    <td><?= $row['id_categoria'] ?></td>
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
                            <option value="1">Categoría 1</option>
                            <option value="2">Categoría 2</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="edit-foto" class="form-label">Foto</label>
                        <input type="file" name="foto" id="edit-foto" class="form-control">
                        <img src="" id="edit-img" alt="Foto Producto" class="img-thumbnail mt-2" style="max-width: 100px;">
                    </div>

                    <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                </form>
            </div>
        </div>
    </div>
</div>

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

<?php
// Conexión a la base de datos
$host = '127.0.0.1';
$user = 'root';
$pass = '';
$db = 'noah';
$port = 3307;

$conn = new mysqli($host, $user, $pass, $db, $port);

if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

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

$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Clientes</title>
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
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.bundle.min.js"></script>
<script>
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

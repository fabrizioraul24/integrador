<?php
require_once '../../backend/Config/config.php';

// Consultar usuarios
$query = "SELECT usuarios.id_usuario, usuarios.nombre_usu, usuarios.email, roles_usuarios.nombre_rol, usuarios.fecha_registro 
          FROM usuarios 
          INNER JOIN roles_usuarios ON usuarios.id_rol = roles_usuarios.id_rol";
$result = $conn->query($query);

if (!$result) {
    die("Error al obtener los usuarios: " . $conn->error);
}

// Consultar roles para el formulario de agregar usuarios
$query_roles = "SELECT id_rol, nombre_rol FROM roles_usuarios";
$result_roles = $conn->query($query_roles);

if (!$result_roles) {
    die("Error al obtener los roles: " . $conn->error);
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Usuarios</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #fafafa;
            font-family: 'Poppins', sans-serif;
            color: #333;
        }

        .container {
            max-width: 1100px;
            margin-top: 60px;
        }

        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            background-color: #ffffff;
            transition: all 0.3s ease-in-out;
        }

        .card:hover {
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
        }

        h2 {
            font-size: 24px;
            font-weight: 600;
            color: #333;
            margin-bottom: 30px;
            text-align: center;
        }

        .btn-primary {
            background-color: #007bff;
            border: none;
            border-radius: 50px;
            padding: 10px 30px;
            font-size: 16px;
            transition: background-color 0.3s ease;
        }

        .btn-primary:hover {
            background-color: #0056b3;
        }

        .btn-edit, .btn-delete {
            padding: 8px 20px;
            font-size: 14px;
            border-radius: 20px;
            transition: all 0.3s ease;
        }

        .btn-edit {
            background-color: #28a745;
            border: none;
        }

        .btn-edit:hover {
            background-color: #218838;
        }

        .btn-delete {
            background-color: #dc3545;
            border: none;
        }

        .btn-delete:hover {
            background-color: #c82333;
        }

        .table th {
            background-color: #f1f1f1;
            color: #333;
            font-weight: 600;
            text-align: center;
        }

        .form-container {
            margin-bottom: 40px;
        }

        .alert {
            margin-bottom: 20px;
            font-size: 15px;
            background-color: #d1ecf1;
            color: #0c5460;
            border-radius: 8px;
            padding: 12px;
        }

        .table-responsive {
            margin-top: 30px;
        }

        .form-label {
            font-weight: 500;
            color: #666;
        }

        .form-control {
            border-radius: 10px;
            padding: 10px;
            border: 1px solid #ccc;
            box-shadow: inset 0 0 8px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
        }

        .form-control:focus {
            border-color: #007bff;
            box-shadow: inset 0 0 5px rgba(0, 123, 255, 0.4);
        }

        .form-select {
            border-radius: 10px;
            padding: 10px;
            border: 1px solid #ccc;
        }

        .table-responsive::-webkit-scrollbar {
            height: 8px;
        }

        .table-responsive::-webkit-scrollbar-thumb {
            background-color: rgba(0, 0, 0, 0.1);
            border-radius: 10px;
        }

        .table-responsive::-webkit-scrollbar-track {
            background-color: #f1f1f1;
        }

        .table tbody tr {
            transition: background-color 0.3s ease;
        }

        .table tbody tr:hover {
            background-color: #f9f9f9;
        }
    </style>
</head>
<body>
    <div class="container">

        <!-- Mensajes de retroalimentación -->
        <?php if (isset($_GET['message'])): ?>
            <div class="alert">
                <?= htmlspecialchars($_GET['message']); ?>
            </div>
        <?php endif; ?>

        <!-- Formulario para agregar usuarios -->
        <div class="form-container">
            <div class="card">
                <h2>Agregar Usuario</h2>
                <form action="../../backend/controllers/UsuarioController.php?action=create" method="POST">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="nombre" class="form-label">Nombre de Usuario</label>
                                <input type="text" class="form-control" id="nombre" name="nombre" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="email" class="form-label">Correo Electrónico</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="password" class="form-label">Contraseña</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="rol" class="form-label">Rol</label>
                                <select class="form-select" id="rol" name="rol" required>
                                    <option value="">Seleccione un Rol</option>
                                    <?php while ($row_roles = $result_roles->fetch_assoc()) { ?>
                                        <option value="<?= $row_roles['id_rol']; ?>"><?= $row_roles['nombre_rol']; ?></option>
                                    <?php } ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary">Agregar Usuario</button>
                </form>
            </div>
        </div>

        <!-- Tabla de usuarios -->
        <div class="card">
            <h2>Listado de Usuarios</h2>
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nombre</th>
                            <th>Email</th>
                            <th>Rol</th>
                            <th>Fecha de Registro</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $result->fetch_assoc()) { ?>
                            <tr>
                                <td><?= $row['id_usuario']; ?></td>
                                <td><?= htmlspecialchars($row['nombre_usu']); ?></td>
                                <td><?= htmlspecialchars($row['email']); ?></td>
                                <td><?= htmlspecialchars($row['nombre_rol']); ?></td>
                                <td><?= $row['fecha_registro']; ?></td>
                                <td>
                                    <a href="editar_usuario.php?id=<?= $row['id_usuario']; ?>" class="btn btn-edit btn-sm">Editar</a>
                                    <a href="../../backend/controllers/UsuarioController.php?action=delete&id=<?= $row['id_usuario']; ?>" class="btn btn-delete btn-sm" onclick="return confirm('¿Estás seguro de eliminar este usuario?');">Eliminar</a>
                                </td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

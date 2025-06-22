<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agregar Usuario</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h2>Agregar Usuario</h2>
        <form action="../../backend/controllers/UsuarioController.php" method="POST">
            <input type="hidden" name="action" value="create">
            <div class="mb-3">
                <label for="nombre" class="form-label">Nombre</label>
                <input type="text" class="form-control" name="nombre" required>
            </div>
            <div class="mb-3">
                <label for="email" class="form-label">Correo Electrónico</label>
                <input type="email" class="form-control" name="email" required>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Contraseña</label>
                <input type="password" class="form-control" name="password" required>
            </div>
            <div class="mb-3">
                <label for="rol" class="form-label">Rol</label>
                <select class="form-select" name="rol" required>
                    <option value="1">Administrador</option>
                    <option value="2">Vendedor</option>
                    <option value="3">Comprador</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary">Agregar Usuario</button>
            <a href="listado_usuarios.php" class="btn btn-secondary">Volver</a>
        </form>
    </div>
</body>
</html>

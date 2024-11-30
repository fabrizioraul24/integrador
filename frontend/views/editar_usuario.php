<?php
require_once '../../backend/Config/config.php';

$id = (int)$_GET['id'];
$query = "SELECT * FROM usuarios WHERE id_usuario = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Usuario</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #fafafa;
            font-family: 'Poppins', sans-serif;
            color: #333;
        }

        .container {
            max-width: 800px;
            margin-top: 60px;
        }

        h2 {
            font-size: 24px;
            font-weight: 600;
            color: #333;
            margin-bottom: 30px;
            text-align: center;
        }

        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            background-color: #ffffff;
            transition: all 0.3s ease-in-out;
            padding: 30px;
        }

        .card:hover {
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
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

        .btn {
            border-radius: 50px;
            padding: 10px 30px;
            font-size: 16px;
            transition: background-color 0.3s ease;
        }

        .btn-success {
            background-color: #28a745;
            border: none;
        }

        .btn-success:hover {
            background-color: #218838;
        }

        .btn-secondary {
            background-color: #6c757d;
            border: none;
        }

        .btn-secondary:hover {
            background-color: #5a6268;
        }

        .btn:hover {
            opacity: 0.9;
        }

        .alert {
            margin-bottom: 20px;
            font-size: 15px;
            background-color: #d1ecf1;
            color: #0c5460;
            border-radius: 8px;
            padding: 12px;
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

        <div class="card">
            <h2>Editar Usuario</h2>
            <form action="../../backend/controllers/UsuarioController.php" method="POST">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="id" value="<?= $user['id_usuario']; ?>">
                
                <div class="mb-3">
                    <label for="nombre" class="form-label">Nombre</label>
                    <input type="text" class="form-control" name="nombre" value="<?= $user['nombre_usu']; ?>" required>
                </div>
                
                <div class="mb-3">
                    <label for="email" class="form-label">Correo Electrónico</label>
                    <input type="email" class="form-control" name="email" value="<?= $user['email']; ?>" required>
                </div>
                
                <div class="mb-3">
                    <label for="rol" class="form-label">Rol</label>
                    <select class="form-select" name="rol" required>
                        <option value="1" <?= $user['id_rol'] == 1 ? 'selected' : ''; ?>>Administrador</option>
                        <option value="2" <?= $user['id_rol'] == 2 ? 'selected' : ''; ?>>Vendedor</option>
                        <option value="3" <?= $user['id_rol'] == 3 ? 'selected' : ''; ?>>Comprador</option>
                    </select>
                </div>

                <button type="submit" class="btn btn-success">Actualizar Usuario</button>
                <a href="listado_usuarios.php" class="btn btn-secondary">Volver</a>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

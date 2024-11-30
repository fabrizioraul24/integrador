<?php
session_start();
if ($_SESSION['rol'] != 1) {
    die("Acceso denegado. Este panel es exclusivo para administradores.");
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Administrador</title>
</head>
<body>
    <h1>Bienvenido, Administrador <?php echo $_SESSION['nombre']; ?></h1>
    <p>Opciones de administración.</p>
</body>
</html>

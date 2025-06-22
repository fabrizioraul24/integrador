<?php
session_start();
if ($_SESSION['rol'] != 2) {
    die("Acceso denegado. Este panel es exclusivo para vendedores.");
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Vendedor</title>
</head>
<body>
    <h1>Bienvenido, Vendedor <?php echo $_SESSION['nombre']; ?></h1>
    <p>Opciones de ventas.</p>
</body>
</html>

<?php
session_start();
if ($_SESSION['rol'] != 3) {
    die("Acceso denegado. Este panel es exclusivo para compradores.");
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Comprador</title>
</head>
<body>
    <h1>Bienvenido, Comprador <?php echo $_SESSION['nombre']; ?></h1>
    <p>Opciones de compras.</p>
</body>
</html>

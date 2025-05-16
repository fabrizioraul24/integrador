<?php
$host = '127.0.0.1';
$user = 'root';      // Cambia según tu configuración
$pass = '';      // Contraseña de MariaDB3
$db = 'noah';         // Base de datos
<<<<<<< HEAD
$port = 3307;         // Puerto de MariaDB
=======
$port = 3308;         // Puerto de MariaDB
>>>>>>> f40753a (comit con el nuevo dash y el cotizaciones)

$conn = new mysqli($host, $user, $pass, $db, $port);

if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}
?>

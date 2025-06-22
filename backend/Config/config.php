<?php
$host = '127.0.0.1';
$user = 'root';      // Cambia según tu configuración
$pass = '';      // Contraseña de MariaDB3
$db = 'noah';         // Base de datos
$port = 3308;         // Puerto de MariaDB

$conn = new mysqli($host, $user, $pass, $db, $port);

if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}
?>

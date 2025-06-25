<?php
session_start();

$host = '127.0.0.1';
$user = 'root';
$pass = '';
$db = 'noah';
$port = 3308;

$conn = new mysqli($host, $user, $pass, $db, $port);
if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

if (isset($_POST['cart_id'])) {
    $cartId = $_POST['cart_id'];
    $userId = $_SESSION['id_usuario'] ?? null;

    if ($userId) {
        $sql = "DELETE FROM carrito WHERE id_carrito = ? AND id_usuario = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $cartId, $userId);
        $stmt->execute();
        $stmt->close();
    }
}

$conn->close();
echo "success";
?>
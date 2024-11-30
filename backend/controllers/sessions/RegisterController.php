<?php
require_once '../../config/config.php';

if (isset($_GET['action']) && $_GET['action'] === 'register') {
    $username = trim($_POST['username']);
    $password = password_hash(trim($_POST['password']), PASSWORD_BCRYPT);
    $email = trim($_POST['email']);
    $rol = 3; // Solo compradores

    if (empty($username) || empty($password) || empty($email)) {
        die("Todos los campos son obligatorios.");
    }

    $query = "INSERT INTO usuarios (nombre_usu, password, id_rol, email) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($query);

    if (!$stmt) {
        die("Error en la consulta: " . $conn->error);
    }

    $stmt->bind_param("ssis", $username, $password, $rol, $email);

    if ($stmt->execute()) {
        echo "Registro exitoso. Ahora puedes iniciar sesión.";
    } else {
        die("Error al registrar el usuario: " . $stmt->error);
    }
}
?>

<?php
require_once '../Config/config.php';

// Validación de conexión
if (!$conn) {
    error_log("UsuarioController: Error - No hay conexión con la base de datos.");
    die("Error de conexión a la base de datos");
}

// **Crear Usuario**
if (isset($_GET['action']) && $_GET['action'] === 'create') {
    $nombre = trim($_POST['nombre'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $rol = (int)($_POST['rol'] ?? 0);

    // Validaciones básicas
    if (empty($nombre) || empty($email) || empty($password) || $rol === 0) {
        header("Location: ../../frontend/views/listado_usuarios.php?message=Error: Todos los campos son obligatorios");
        exit;
    }

    // Encriptar la contraseña
    $hashed_password = password_hash($password, PASSWORD_BCRYPT);

    // Insertar en la base de datos
    $query = "INSERT INTO usuarios (nombre_usu, email, password, id_rol, fecha_registro) VALUES (?, ?, ?, ?, NOW())";
    $stmt = $conn->prepare($query);

    if ($stmt) {
        $stmt->bind_param("sssi", $nombre, $email, $hashed_password, $rol);
        if ($stmt->execute()) {
            header("Location: ../../frontend/views/listado_usuarios.php?message=Usuario creado con éxito");
        } else {
            error_log("UsuarioController: Error al ejecutar la consulta de creación - " . $stmt->error);
            header("Location: ../../frontend/views/listado_usuarios.php?message=Error al crear el usuario");
        }
    } else {
        error_log("UsuarioController: Error en la preparación de la consulta de creación - " . $conn->error);
        header("Location: ../../frontend/views/listado_usuarios.php?message=Error interno al crear el usuario");
    }
    exit;
}

// **Editar Usuario**
if (isset($_POST['action']) && $_POST['action'] === 'update') {
    $id = (int)$_POST['id'];
    $nombre = trim($_POST['nombre'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $rol = (int)($_POST['rol'] ?? 0);

    if (empty($id) || empty($nombre) || empty($email) || $rol === 0) {
        header("Location: ../../frontend/views/listado_usuarios.php?message=Error: Todos los campos son obligatorios para editar");
        exit;
    }

    $query = "UPDATE usuarios SET nombre_usu = ?, email = ?, id_rol = ? WHERE id_usuario = ?";
    $stmt = $conn->prepare($query);

    if ($stmt) {
        $stmt->bind_param("ssii", $nombre, $email, $rol, $id);
        if ($stmt->execute()) {
            header("Location: ../../frontend/views/listado_usuarios.php?message=Usuario actualizado con éxito");
        } else {
            error_log("UsuarioController: Error al ejecutar la consulta de actualización - " . $stmt->error);
            header("Location: ../../frontend/views/listado_usuarios.php?message=Error al actualizar el usuario");
        }
    } else {
        error_log("UsuarioController: Error en la preparación de la consulta de actualización - " . $conn->error);
        header("Location: ../../frontend/views/listado_usuarios.php?message=Error interno al actualizar el usuario");
    }
    exit;
}

// **Eliminar Usuario**
if (isset($_GET['action']) && $_GET['action'] === 'delete') {
    $id = (int)$_GET['id'];

    if (empty($id)) {
        header("Location: ../../frontend/views/listado_usuarios.php?message=Error: ID de usuario no válido");
        exit;
    }

    $query = "DELETE FROM usuarios WHERE id_usuario = ?";
    $stmt = $conn->prepare($query);

    if ($stmt) {
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            header("Location: ../../frontend/views/listado_usuarios.php?message=Usuario eliminado con éxito");
        } else {
            error_log("UsuarioController: Error al ejecutar la consulta de eliminación - " . $stmt->error);
            header("Location: ../../frontend/views/listado_usuarios.php?message=Error al eliminar el usuario");
        }
    } else {
        error_log("UsuarioController: Error en la preparación de la consulta de eliminación - " . $conn->error);
        header("Location: ../../frontend/views/listado_usuarios.php?message=Error interno al eliminar el usuario");
    }
    exit;
}

// **Acceso no permitido**
header("Location: ../../frontend/views/listado_usuarios.php?message=Acceso no permitido");
exit;

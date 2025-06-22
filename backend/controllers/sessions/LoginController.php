<?php
require_once '../../config/config.php';

if (isset($_GET['action']) && $_GET['action'] === 'login') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    // Validar si los campos están completos
    if (empty($username) || empty($password)) {
        echo "<script>
                alert('Por favor, completa todos los campos.');
                window.location.href = '../../../frontend/views/login.php';
              </script>";
        exit;
    }

    // Buscar el usuario
    $query = "SELECT * FROM usuarios WHERE nombre_usu = ?";
    $stmt = $conn->prepare($query);

    if (!$stmt) {
        echo "<script>
                alert('Error interno del servidor. Intenta más tarde.');
                window.location.href = '../../../frontend/views/login.php';
              </script>";
        exit;
    }

    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();

        // Verificar la contraseña
        if (password_verify($password, $user['password'])) {
            session_start();
            $_SESSION['id_usuario'] = $user['id_usuario'];
            $_SESSION['nombre'] = $user['nombre_usu'];
            $_SESSION['rol'] = $user['id_rol'];

            // Redirigir según el rol
            switch ($user['id_rol']) {
                case 1: // Administrador
                    header("Location: ../../../frontend/dash_new/dashboard.php");
                    break;
                case 2: // Vendedor
                    header("Location: ../../../frontend/views/vendedor_dashboard.php");
                    break;
                case 3: // Comprador
                    header("Location: ../../../frontend/views/compras.php");
                    break;
                default:
                    echo "<script>
                            alert('Rol no reconocido. Contacta al administrador.');
                            window.location.href = '../../../frontend/views/login.php';
                          </script>";
                    break;
            }
            exit;
        } else {
            echo "<script>
                    alert('Contraseña incorrecta. Intenta nuevamente.');
                    window.location.href = '../../../frontend/views/login.php';
                  </script>";
            exit;
        }
    } else {
        echo "<script>
                alert('Usuario no encontrado. Regístrate primero.');
                window.location.href = '../../../frontend/views/login.php';
              </script>";
        exit;
    }
} else {
    echo "<script>
            alert('Acceso no permitido.');
            window.location.href = '../../../frontend/views/login.php';
          </script>";
    exit;
}
?>

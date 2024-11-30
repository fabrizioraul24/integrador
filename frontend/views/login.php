<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión</title>
    <link rel="stylesheet" href="../css/loginandresgistro.css">
</head>
<body>
    <div class="login-container">
        <div class="login-box">
        <div id="message-box" class="hidden">
    <p id="message-content"></p>
</div>
            <h1>Iniciar Sesión</h1>
            <form action="../../backend/controllers/sessions/LoginController.php?action=login" method="POST">
                <label for="username">Usuario:</label>
                <input type="text" id="username" name="username" placeholder="Ingrese su usuario" required>
                <label for="password">Contraseña:</label>
                <input type="password" id="password" name="password" placeholder="Ingrese su contraseña" required>
                <button type="submit" class="btn">Ingresar</button>
            </form>
            <div class="extra-links">
                <a href="registro.php" class="btn-link">¿No tienes cuenta? Registrarse</a>
            </div>
        </div>
    </div>
</body>
</html>

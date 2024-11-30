<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro de Comprador</title>
    <link rel="stylesheet" href="../css/loginandresgistro.css">
</head>
<body>
    <div class="login-container">
        <div class="login-box">
        <div id="message-box" class="hidden">
    <p id="message-content"></p>
</div>
            <h1>Registro de Comprador</h1>
            <form action="../../backend/controllers/sessions/RegisterController.php?action=register" method="POST">
                <label for="username">Usuario:</label>
                <input type="text" id="username" name="username" placeholder="Ingrese su usuario" required>
                <label for="password">Contraseña:</label>
                <input type="password" id="password" name="password" placeholder="Ingrese su contraseña" required>
                <label for="email">Correo Electrónico:</label>
                <input type="email" id="email" name="email" placeholder="Ingrese su correo electrónico" required>
                <button type="submit" class="btn">Registrarse</button>
            </form>
            <div class="extra-links">
                <a href="login.php" class="btn-link">Volver al Login</a>
            </div>
        </div>
    </div>
</body>
</html>


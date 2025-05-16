<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro de Comprador</title>
<<<<<<< HEAD
    <link rel="stylesheet" href="../css/loginandresgistro.css">
=======
    <!-- Agregar Font Awesome para los íconos -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(-45deg, #1A2865, #394480, #4E6BAF, #394480);
            background-size: 400% 400%;
            animation: gradientMove 15s ease infinite;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            padding: 20px;
        }

        @keyframes gradientMove {
            0% { background-position: 0% 50%; }
            25% { background-position: 50% 50%; }
            50% { background-position: 100% 50%; }
            75% { background-position: 50% 50%; }
            100% { background-position: 0% 50%; }
        }

        .login-container {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100%;
            width: 100%;
        }

        .login-box {
            background: white;
            padding: 50px;
            border-radius: 15px;
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 600px;
            text-align: center;
            transition: transform 0.3s ease;
        }

        .login-box:hover {
            transform: translateY(-10px);
        }

        h1 {
            font-size: 32px;
            color: #333;
            margin-bottom: 30px;
            font-weight: 600;
        }

        .input-group {
            margin-bottom: 25px;
            text-align: left;
            position: relative;
        }

        .input-group input {
            width: 100%;
            padding: 14px;
            font-size: 16px;
            border: 2px solid #ddd;
            border-radius: 8px;
            transition: border-color 0.3s ease;
            padding-right: 40px; /* Espacio para el ícono */
        }

        .input-group input:focus {
            border-color: #4E6BAF;
            outline: none;
        }

        .eye-icon {
            position: absolute;
            right: 10px;
            top: 68%; /* Ajustado 5px más abajo */
            transform: translateY(-50%);
            cursor: pointer;
        }

        button {
            width: 100%;
            padding: 16px;
            background-color: #4E6BAF;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 20px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        button:hover {
            background-color: #394480;
        }

        .extra-links {
            margin-top: 25px;
            font-size: 16px;
        }

        .extra-links a {
            color: #4E6BAF;
            text-decoration: none;
            font-weight: 600;
        }

        .extra-links a:hover {
            text-decoration: underline;
        }

    </style>
>>>>>>> f40753a (comit con el nuevo dash y el cotizaciones)
</head>
<body>
    <div class="login-container">
        <div class="login-box">
<<<<<<< HEAD
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
=======
            <h1>Registro de Comprador</h1>
            <form action="../../backend/controllers/sessions/RegisterController.php?action=register" method="POST">
                <div class="input-group">
                    <label for="username">Usuario:</label>
                    <input type="text" id="username" name="username" placeholder="Ingrese su usuario" required>
                </div>
                <div class="input-group">
                    <label for="password">Contraseña:</label>
                    <input type="password" id="password" name="password" placeholder="Ingrese su contraseña" required>
                    <i id="eye-icon" class="fas fa-eye eye-icon" onclick="togglePassword()"></i> <!-- Ícono de ojo -->
                </div>
                <div class="input-group">
                    <label for="email">Correo Electrónico:</label>
                    <input type="email" id="email" name="email" placeholder="Ingrese su correo electrónico" required>
                </div>
>>>>>>> f40753a (comit con el nuevo dash y el cotizaciones)
                <button type="submit" class="btn">Registrarse</button>
            </form>
            <div class="extra-links">
                <a href="login.php" class="btn-link">Volver al Login</a>
            </div>
        </div>
    </div>
<<<<<<< HEAD
</body>
</html>

=======

    <script>
        function togglePassword() {
            const passwordField = document.getElementById('password');
            const eyeIcon = document.getElementById('eye-icon');
            
            // Alternar entre mostrar/ocultar la contraseña
            if (passwordField.type === 'password') {
                passwordField.type = 'text';
                eyeIcon.classList.remove('fa-eye');
                eyeIcon.classList.add('fa-eye-slash');
            } else {
                passwordField.type = 'password';
                eyeIcon.classList.remove('fa-eye-slash');
                eyeIcon.classList.add('fa-eye');
            }
        }
    </script>
</body>
</html>
>>>>>>> f40753a (comit con el nuevo dash y el cotizaciones)

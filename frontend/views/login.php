<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
<<<<<<< HEAD
    <title>Iniciar Sesión</title>
    <link rel="stylesheet" href="../css/loginandresgistro.css">
=======
    <title>Iniciar Sesión - Pil Andina</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        /* Reset general */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(-45deg, #1A2865, #394480, #4E6BAF, #394480); /* Nuevo gradiente */
            background-size: 400% 400%;
            animation: gradientMove 15s ease infinite; /* Animación de movimiento de gradiente */
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            padding: 20px;
        }

        /* Animación para el gradiente */
        @keyframes gradientMove {
            0% { background-position: 0% 50%; }
            25% { background-position: 50% 50%; }
            50% { background-position: 100% 50%; }
            75% { background-position: 50% 50%; }
            100% { background-position: 0% 50%; }
        }

        /* Contenedor de la caja de login */
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
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.1); /* Sombra más grande */
            width: 100%;
            max-width: 600px; /* Caja más ancha */
            text-align: center;
            transition: transform 0.3s ease;
        }

        /* Efecto al pasar el mouse sobre la caja */
        .login-box:hover {
            transform: translateY(-10px); /* Efecto sutil de hover */
        }

        /* Título del formulario */
        h1 {
            font-size: 32px;
            color: #333;
            margin-bottom: 30px;
            font-weight: 600;
        }

        /* Grupos de campos de entrada */
        .input-group {
            margin-bottom: 25px;
            text-align: left;
            position: relative;
        }

        .input-group label {
            display: block;
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 10px;
            color: #555;
        }

        /* Estilos para los inputs */
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
            border-color: #4E6BAF; /* Cambia el color al enfocar el input */
            outline: none;
        }

        /* Estilos para el ícono de ojo */
        .eye-icon {
            position: absolute;
            right: 10px;
            top: -58%;
            transform: translateY(-50%);
            cursor: pointer;
        }

        /* Estilos para el botón de enviar */
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

        /* Enlaces adicionales */
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

        /* Mensaje de error */
        #message-box {
            background: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 8px;
            margin-top: 20px;
            font-size: 14px;
        }

        .hidden {
            display: none;
        }

        /* Media Queries para pantallas más pequeñas */
        @media (max-width: 768px) {
            .login-box {
                padding: 40px;
                max-width: 90%; /* Se hace más pequeño en pantallas móviles */
            }

            h1 {
                font-size: 28px;
            }

            button {
                font-size: 18px;
            }
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
            <h1>Iniciar Sesión</h1>
            <form action="../../backend/controllers/sessions/LoginController.php?action=login" method="POST">
                <label for="username">Usuario:</label>
                <input type="text" id="username" name="username" placeholder="Ingrese su usuario" required>
                <label for="password">Contraseña:</label>
                <input type="password" id="password" name="password" placeholder="Ingrese su contraseña" required>
=======
            <div id="message-box" class="hidden">
                <p id="message-content"></p>
            </div>
            <h1>Iniciar Sesión</h1>
            <form action="../../backend/controllers/sessions/LoginController.php?action=login" method="POST">
                <div class="input-group">
                    <label for="username">Usuario</label>
                    <input type="text" id="username" name="username" placeholder="Ingrese su usuario" required>
                </div>
                <div class="input-group">
                    <label for="password">Contraseña</label>
                    <input type="password" id="password" name="password" placeholder="Ingrese su contraseña" required>
                    <i id="eye-icon" class="fas fa-eye eye-icon" onclick="togglePassword()"></i> <!-- Ícono de ojo -->
                </div>
>>>>>>> f40753a (comit con el nuevo dash y el cotizaciones)
                <button type="submit" class="btn">Ingresar</button>
            </form>
            <div class="extra-links">
                <a href="registro.php" class="btn-link">¿No tienes cuenta? Registrarse</a>
            </div>
        </div>
    </div>
<<<<<<< HEAD
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
>>>>>>> f40753a (comit con el nuevo dash y el cotizaciones)
</body>
</html>

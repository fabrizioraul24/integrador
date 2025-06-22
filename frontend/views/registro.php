<!DOCTYPE html>
<html lang="es">
<head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Registro de Comprador - Compañador</title>
        <script src="https://cdn.tailwindcss.com"></script>
        <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
        <script>
            tailwind.config = {
                theme: {
                    extend: {
                        colors: {
                            primary: '#4e6baf',
                            'primary-dark': '#4e6b',
                            'primary-dark': '#3a5186',
                            'primary-light': '#86acd4',
                            panel: '#ffffff',
                            'panel-dark': '#1f2937',
                            accent: '#42568b',
                            'soft-red': '#fee2e2',
                            'text-soft-red': '#b91c1c',
                        },
                        animation: {
                            'fade-in': 'fadeIn 0.5s ease-in',
                            'slide-y': 'slideY 0.4s ease-out',
                            'bounce-light': 'bounceLight 0.3s infinite',
                            'pulse-glow': 'pulseGlow 2s infinite',
                        },
                        backgroundImage: {
                            'gradient-primary': 'linear-gradient(135deg, #42568b 0%, #86acd4 100%)',
                            'gradient-card': 'linear-gradient(145deg, #ffffff 0%, #f4fafc 100%)',
                            'gradient-dark': 'linear-gradient(145deg, #1f2937 0%, #374151 100%)',
                            'gradient-login': 'linear-gradient(-45deg, #1A2865, #394480, #4E6BAF, #394480)',
                        },
                    }
                }
            }
        </script>
        <style>
            @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;600;700&display=swap');
            
            * {
                font-family: 'Inter', sans-serif;
            }
            
            @keyframes fadeIn {
                from { opacity: 0; transform: translateY(20px); }
                to { opacity: 1; transform: translateY(0); }
            }
            
            @keyframes slideUp {
                from { opacity: 0; transform: translateY(30px); }
                to { opacity: 1; transform: translateY(0); }
            }
            
            @keyframes bounceLight {
                0%, 100% { transform: translateY(0); }
                50% { transform: translateY(-5px); }
            }
            
            @keyframes pulseGlow {
                0%, 100% { box-shadow: 0 0 20px rgba(78, 107, 175, 0.3); }
                50% { box-shadow: 0 0 30px rgba(78, 107, 175, 0.6); }
            }
            
            .glass-effect {
                backdrop-filter: blur(10px);
                background: rgba(255, 255, 255, 0.1);
                border: 1px solid rgba(255, 255, 255, 0.2);
            }
            
            .dark .glass-effect {
                background: rgba(31, 41, 55, 0.7);
                border: 1px solid rgba(55, 65, 81, 0.3);
            }
            
            .card-hover {
                transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
            }
            
            .card-hover:hover {
                transform: translateY(-10px) scale(1.02);
                box-shadow: 0 25px 50px rgba(0, 0, 0, 0.15);
            }
            
            .dark .card-hover:hover {
                box-shadow: 0 25px 50px rgba(0, 0, 0, 0.4);
            }
            
            .floating-gradient {
                background-size: 400% 400%;
                animation: gradient 15s ease infinite;
            }
            
            @keyframes gradient {
                0% { background-position: 0% 50%; }
                50% { background-position: 100% 50%; }
                100% { background-position: 0% 50%; }
            }
            
            .text-shadow {
                text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            }
            
            .form-control {
                border-radius: 10px;
                padding: 12px;
                border: 1px solid #ccc;
                box-shadow: inset 0 0 8px rgba(0, 0, 0, 0.05);
                transition: all 0.3s ease;
            }
            
            .form-control:focus {
                border-color: #4e6baf;
                box-shadow: inset 0 0 5px rgba(78, 107, 175, 0.4);
                outline: none;
            }
            
            .dark .form-control {
                background-color: #3a3b3c;
                color: #ccc;
                border-color: #4b5563;
            }
            
            .dark .form-control:focus {
                border-color: #86acd4;
                box-shadow: inset 0 0 5px rgba(134, 172, 212, 0.4);
            }
            
            .dark .form-control::placeholder {
                color: #9ca3af;
            }
            
            .eye-icon {
                position: absolute;
                right: 12px;
                top: 65%;
                transform: translateY(-50%);
                cursor: pointer;
                color: #6b7280;
            }
            
            .dark .eye-icon {
                color: #9ca3af;
            }
        </style>
    </head>
    <body class="bg-gray-900 floating-gradient bg-gradient-login min-h-screen flex items-center justify-center p-4 transition-colors duration-300 dark:bg-gray-900">
        <div class="max-w-md w-full">
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-8 animate-slide-up border border-gray-100 dark:border-gray-700 card-hover">
                <!-- Logo -->
                <div class="flex justify-center mb-6">
                    <div class="relative">
                        <div class="w-16 h-16 bg-gradient-primary rounded-full flex items-center justify-center shadow-lg animate-pulse-glow">
                            <i class="fas fa-industry text-white text-2xl"></i>
                        </div>
                        <div class="absolute -top-1 -right-1 w-4 h-4 bg-green-400 rounded-full animate-bounce-light"></div>
                    </div>
                </div>

                <!-- Title -->
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white text-center mb-6 text-shadow">
                    Registro de Comprador
                </h1>

                <!-- Message Box -->
                <div id="message-box" class="hidden bg-soft-red dark:bg-red-900 text-text-soft-red dark:text-red-300 p-4 rounded-lg mb-6">
                    <p id="message-content"></p>
                </div>

                <!-- Form -->
                <form action="../../backend/controllers/sessions/RegisterController.php?action=register" method="POST">
                    <div class="mb-6">
                        <label for="username" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Usuario</label>
                        <input type="text" id="username" name="username" placeholder="Ingrese su usuario" required
                               class="form-control w-full text-gray-900 dark:text-gray-100 dark:bg-gray-700">
                    </div>
                    <div class="mb-6 relative">
                        <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Contraseña</label>
                        <input type="password" id="password" name="password" placeholder="Ingrese su contraseña" required
                               class="form-control w-full text-gray-900 dark:text-gray-100 dark:bg-gray-700">
                        <i id="eye-icon" class="fas fa-eye eye-icon" onclick="togglePassword()"></i>
                    </div>
                    <div class="mb-6">
                        <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Correo Electrónico</label>
                        <input type="email" id="email" name="email" placeholder="Ingrese su correo electrónico" required
                               class="form-control w-full text-gray-900 dark:text-gray-100 dark:bg-gray-700">
                    </div>
                    <button type="submit" class="w-full bg-primary hover:bg-primary-dark text-white font-medium py-3 px-4 rounded-lg shadow transition-all duration-300 flex items-center justify-center">
                        <i class="fas fa-user-plus mr-2"></i> Registrarse
                    </button>
                </form>

                <!-- Extra Links -->
                <div class="mt-6 text-center">
                    <a href="login.php" class="text-primary hover:text-primary-dark dark:text-primary-light dark:hover:text-primary font-medium transition-colors duration-300">
                        Volver al Login
                    </a>
                </div>
            </div>

            <!-- Dark Mode Toggle -->
            <div class="mt-4 flex justify-center">
                <button id="darkModeToggle" class="flex items-center space-x-2 text-gray-600 dark:text-gray-300 hover:text-primary dark:hover:text-primary-light transition-colors duration-300">
                    <i id="theme-icon" class="fas fa-moon"></i>
                    <span class="text-sm font-medium theme-text">Modo Oscuro</span>
                </button>
            </div>
        </div>

        <script>
            function togglePassword() {
                const passwordField = document.getElementById('password');
                const eyeIcon = document.getElementById('eye-icon');
                
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

            // Dark Mode Toggle
            const darkModeToggle = document.getElementById('darkModeToggle');
            const themeIcon = document.getElementById('theme-icon');
            const themeText = document.querySelector('.theme-text');

            let isDarkMode = localStorage.getItem('theme') === 'dark';

            function updateThemeUI() {
                if (isDarkMode) {
                    document.body.classList.add('dark');
                    themeIcon.classList.remove('fa-moon');
                    themeIcon.classList.add('fa-sun');
                    themeText.textContent = 'Modo Claro';
                } else {
                    document.body.classList.remove('dark');
                    themeIcon.classList.remove('fa-sun');
                    themeIcon.classList.add('fa-moon');
                    themeText.textContent = 'Modo Oscuro';
                }
            }

            updateThemeUI();

            darkModeToggle.addEventListener('click', () => {
                isDarkMode = !isDarkMode;
                localStorage.setItem('theme', isDarkMode ? 'dark' : 'light');
                updateThemeUI();
            });
        </script>
    </body>
</html>
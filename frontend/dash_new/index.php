<?php
// ==================== CONEXIÓN A LA BASE DE DATOS ====================
session_start();
date_default_timezone_set('America/La_Paz');

// Configuración de conexión (usando tus parámetros)
$host = '127.0.0.1';
$user = 'root';
$pass = '';
$db = 'noah';
$port = 3308;

// Crear conexión
$conn = new mysqli($host, $user, $pass, $db, $port);

// Verificar conexión
if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

// Función para obtener conteos de la base de datos
function obtenerConteos($conn) {
    $conteos = [
        'clientes' => 0,
        'productos' => 0,
        'ventas' => 0
    ];
    
    try {
        // Obtener total de clientes
        $query = $conn->query("SELECT COUNT(*) as total FROM clientes");
        if ($query) {
            $conteos['clientes'] = $query->fetch_assoc()['total'];
        }
        
        // Obtener total de productos
        $query = $conn->query("SELECT COUNT(*) as total FROM productos");
        if ($query) {
            $conteos['productos'] = $query->fetch_assoc()['total'];
        }
        
        // Obtener total de ventas
        $query = $conn->query("SELECT COUNT(*) as total FROM ventas");
        if ($query) {
            $conteos['ventas'] = $query->fetch_assoc()['total'];
        }
        
    } catch (Exception $e) {
        error_log("Error al obtener conteos: " . $e->getMessage());
    }
    
    return $conteos;
}

// Obtener datos para mostrar
$conteos = obtenerConteos($conn);

// Cerrar conexión (la cerraremos al final del script)
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Pil Andina</title>
    
    <!----===== Iconscout CSS ===== -->
    <link rel="stylesheet" href="https://unicons.iconscout.com/release/v4.0.0/css/line.css">

    <style>
        /* [Todo el CSS anterior permanece igual] */
        /* ===== Google Font Import - Poppins ===== */
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@200;300;400;500;600&display=swap');
        *{
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        :root{
            /* ===== Colors ===== */
            --primary-color: #0E4BF1;
            --panel-color: #FFF;
            --text-color: #000;
            --black-light-color: #707070;
            --border-color: #e6e5e5;
            --toggle-color: #DDD;
            --box1-color: #4DA3FF;
            --box2-color: #FFE6AC;
            --box3-color: #E7D1FC;
            --title-icon-color: #fff;
            
            /* ====== Transition ====== */
            --tran-05: all 0.5s ease;
            --tran-03: all 0.3s ease;
            --tran-03: all 0.2s ease;
        }

        body{
            min-height: 100vh;
            background-color: var(--primary-color);
        }
        body.dark{
            --primary-color: #3A3B3C;
            --panel-color: #242526;
            --text-color: #CCC;
            --black-light-color: #CCC;
            --border-color: #4D4C4C;
            --toggle-color: #FFF;
            --box1-color: #3A3B3C;
            --box2-color: #3A3B3C;
            --box3-color: #3A3B3C;
            --title-icon-color: #CCC;
        }
        /* === Custom Scroll Bar CSS === */
        ::-webkit-scrollbar {
            width: 8px;
        }
        ::-webkit-scrollbar-track {
            background: #f1f1f1;
        }
        ::-webkit-scrollbar-thumb {
            background: var(--primary-color);
            border-radius: 12px;
            transition: all 0.3s ease;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: #0b3cc1;
        }

        body.dark::-webkit-scrollbar-thumb:hover,
        body.dark .activity-data::-webkit-scrollbar-thumb:hover{
            background: #3A3B3C;
        }

        nav{
            position: fixed;
            top: 0;
            left: 0;
            height: 100%;
            width: 250px;
            padding: 10px 14px;
            background-color: var(--panel-color);
            border-right: 1px solid var(--border-color);
            transition: var(--tran-05);
        }
        nav.close{
            width: 73px;
        }
        nav .logo-name{
            display: flex;
            align-items: center;
        }
        nav .logo-image{
            display: flex;
            justify-content: center;
            min-width: 45px;
        }
        nav .logo-image img{
            width: 40px;
            object-fit: cover;
            border-radius: 50%;
        }

        nav .logo-name .logo_name{
            font-size: 22px;
            font-weight: 600;
            color: var(--text-color);
            margin-left: 14px;
            transition: var(--tran-05);
        }
        nav.close .logo_name{
            opacity: 0;
            pointer-events: none;
        }
        nav .menu-items{
            margin-top: 40px;
            height: calc(100% - 90px);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        .menu-items li{
            list-style: none;
        }
        .menu-items li a{
            display: flex;
            align-items: center;
            height: 50px;
            text-decoration: none;
            position: relative;
        }
        .nav-links li a:hover:before{
            content: "";
            position: absolute;
            left: -7px;
            height: 5px;
            width: 5px;
            border-radius: 50%;
            background-color: var(--primary-color);
        }
        body.dark li a:hover:before{
            background-color: var(--text-color);
        }
        .menu-items li a i{
            font-size: 24px;
            min-width: 45px;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--black-light-color);
        }
        .menu-items li a .link-name{
            font-size: 18px;
            font-weight: 400;
            color: var(--black-light-color);    
            transition: var(--tran-05);
        }
        nav.close li a .link-name{
            opacity: 0;
            pointer-events: none;
        }
        .nav-links li a:hover i,
        .nav-links li a:hover .link-name{
            color: var(--primary-color);
        }
        body.dark .nav-links li a:hover i,
        body.dark .nav-links li a:hover .link-name{
            color: var(--text-color);
        }
        .menu-items .logout-mode{
            padding-top: 10px;
            border-top: 1px solid var(--border-color);
        }
        .menu-items .mode{
            display: flex;
            align-items: center;
            white-space: nowrap;
        }
        .menu-items .mode-toggle{
            position: absolute;
            right: 14px;
            height: 50px;
            min-width: 45px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
        }
        .mode-toggle .switch{
            position: relative;
            display: inline-block;
            height: 22px;
            width: 40px;
            border-radius: 25px;
            background-color: var(--toggle-color);
        }
        .switch:before{
            content: "";
            position: absolute;
            left: 5px;
            top: 50%;
            transform: translateY(-50%);
            height: 15px;
            width: 15px;
            background-color: var(--panel-color);
            border-radius: 50%;
            transition: var(--tran-03);
        }
        body.dark .switch:before{
            left: 20px;
        }

        .dashboard{
            position: relative;
            left: 250px;
            background-color: var(--panel-color);
            min-height: 100vh;
            width: calc(100% - 250px);
            padding: 10px 14px;
            transition: var(--tran-05);
        }
        nav.close ~ .dashboard{
            left: 73px;
            width: calc(100% - 73px);
        }
        .dashboard .top{
            position: fixed;
            top: 0;
            left: 250px;
            display: flex;
            width: calc(100% - 250px);
            justify-content: space-between;
            align-items: center;
            padding: 10px 14px;
            background-color: var(--panel-color);
            transition: var(--tran-05);
            z-index: 10;
        }
        nav.close ~ .dashboard .top{
            left: 73px;
            width: calc(100% - 73px);
        }
        .dashboard .top .sidebar-toggle{
            font-size: 26px;
            color: var(--text-color);
            cursor: pointer;
        }
        .dashboard .top .search-box{
            position: relative;
            height: 45px;
            max-width: 600px;
            width: 100%;
            margin: 0 30px;
        }
        .top .search-box input{
            position: absolute;
            border: 1px solid var(--border-color);
            background-color: var(--panel-color);
            padding: 0 25px 0 50px;
            border-radius: 5px;
            height: 100%;
            width: 100%;
            color: var(--text-color);
            font-size: 15px;
            font-weight: 400;
            outline: none;
        }
        .top .search-box i{
            position: absolute;
            left: 15px;
            font-size: 22px;
            z-index: 10;
            top: 50%;
            transform: translateY(-50%);
            color: var(--black-light-color);
        }
        .top img{
            width: 40px;
            border-radius: 50%;
        }
        .dashboard .dash-content{
            padding-top: 50px;
        }
        .dash-content .title{
            display: flex;
            align-items: center;
            margin: 60px 0 30px 0;
        }
        .dash-content .title i{
            position: relative;
            height: 35px;
            width: 35px;
            background-color: var(--primary-color);
            border-radius: 6px;
            color: var(--title-icon-color);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }
        .dash-content .title .text{
            font-size: 24px;
            font-weight: 500;
            color: var(--text-color);
            margin-left: 10px;
        }
        .dash-content .boxes{
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
        }
        .dash-content .boxes .box{
            display: flex;
            flex-direction: column;
            align-items: center;
            border-radius: 12px;
            width: calc(100% / 3 - 15px);
            padding: 15px 20px;
            background-color: var(--box1-color);
            transition: var(--tran-05);
        }
        .boxes .box i{
            font-size: 35px;
            color: var(--text-color);
        }
        .boxes .box .text{
            white-space: nowrap;
            font-size: 18px;
            font-weight: 500;
            color: var(--text-color);
        }
        .boxes .box .number{
            font-size: 40px;
            font-weight: 500;
            color: var(--text-color);
        }
        .boxes .box.box2{
            background-color: var(--box2-color);
        }
        .boxes .box.box3{
            background-color: var(--box3-color);
        }
        .dash-content .activity .activity-data{
            display: flex;
            justify-content: space-between;
            align-items: center;
            width: 100%;
        }
        .activity .activity-data{
            display: flex;
        }
        .activity-data .data{
            display: flex;
            flex-direction: column;
            margin: 0 15px;
        }
        .activity-data .data-title{
            font-size: 20px;
            font-weight: 500;
            color: var(--text-color);
        }
        .activity-data .data .data-list{
            font-size: 18px;
            font-weight: 400;
            margin-top: 20px;
            white-space: nowrap;
            color: var(--text-color);
        }

        @media (max-width: 1000px) {
            nav{
                width: 73px;
            }
            nav.close{
                width: 250px;
            }
            nav .logo_name{
                opacity: 0;
                pointer-events: none;
            }
            nav.close .logo_name{
                opacity: 1;
                pointer-events: auto;
            }
            nav li a .link-name{
                opacity: 0;
                pointer-events: none;
            }
            nav.close li a .link-name{
                opacity: 1;
                pointer-events: auto;
            }
            nav ~ .dashboard{
                left: 73px;
                width: calc(100% - 73px);
            }
            nav.close ~ .dashboard{
                left: 250px;
                width: calc(100% - 250px);
            }
            nav ~ .dashboard .top{
                left: 73px;
                width: calc(100% - 73px);
            }
            nav.close ~ .dashboard .top{
                left: 250px;
                width: calc(100% - 250px);
            }
            .activity .activity-data{
                overflow-X: scroll;
            }
        }

        @media (max-width: 780px) {
            .dash-content .boxes .box{
                width: calc(100% / 2 - 15px);
                margin-top: 15px;
            }
        }
        @media (max-width: 560px) {
            .dash-content .boxes .box{
                width: 100% ;
            }
        }
        @media (max-width: 400px) {
            nav{
                width: 0px;
            }
            nav.close{
                width: 73px;
            }
            nav .logo_name{
                opacity: 0;
                pointer-events: none;
            }
            nav.close .logo_name{
                opacity: 0;
                pointer-events: none;
            }
            nav li a .link-name{
                opacity: 0;
                pointer-events: none;
            }
            nav.close li a .link-name{
                opacity: 0;
                pointer-events: none;
            }
            nav ~ .dashboard{
                left: 0;
                width: 100%;
            }
            nav.close ~ .dashboard{
                left: 73px;
                width: calc(100% - 73px);
            }
            nav ~ .dashboard .top{
                left: 0;
                width: 100%;
            }
            nav.close ~ .dashboard .top{
                left: 0;
                width: 100%;
            }
        }

        /* Estilos adicionales para los boxes dinámicos */
        .box {
            border-radius: 10px;
            padding: 20px;
            color: white;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }
        
        .box:hover {
            transform: translateY(-5px);
        }
        
        .box1 { background: linear-gradient(135deg, #42568b 0%, #86acd4 100%); }
        .box2 { background: linear-gradient(135deg, #42568b 0%, #86acd4 100%); }
        .box3 { background: linear-gradient(135deg, #42568b 0%, #86acd4 100%); }
        
        .number {
            font-size: 2.5rem;
            font-weight: 700;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <nav class="close">
        <div class="logo-name">
            <div class="logo-image">
                <img src="images/pil.png" alt="">
            </div>

            <span class="logo_name">Pil Andina</span>
        </div>

        <div class="menu-items">
            <ul class="nav-links">
                <li><a href="../views/usuarios.php">
                    <i class="uil uil-users-alt"></i>
                    <span class="link-name">ABM de Usuarios</span>
                </a></li>
                <li><a href="../views/clientes.php">
                    <i class="uil uil-shopping-cart"></i>
                    <span class="link-name">ABM de Clientes</span>
                </a></li>
                <li><a href="../views/listado_productos.php">
                    <i class="uil uil-box"></i>
                    <span class="link-name">Productos</span>
                </a></li>
                <li><a href="../views/traspaso.php">
                    <i class="uil uil-angle-double-right"></i>
                    <span class="link-name">Traspaso</span>
                </a></li>
                <li><a href="../views/ventas_admin.php">
                    <i class="uil uil-shopping-bag"></i>
                    <span class="link-name">Ventas</span>
                </a></li>
                <li><a href="../views/cotizaciones.php">
                   <i class="uil uil-newspaper"></i>
                    <span class="link-name">Cotizaciones</span>
                </a></li>
            </ul>
            
            <ul class="logout-mode">
                <li><a href="#">
                    <i class="uil uil-signout"></i>
                    <span class="link-name">Salir</span>
                </a></li>

                <li class="mode">
                    <a href="#">
                        <i class="uil uil-moon"></i>
                    <span class="link-name">Modo Oscuro</span>
                </a>

                <div class="mode-toggle">
                  <span class="switch"></span>
                </div>
            </li>
            </ul>
        </div>
    </nav>

    <section class="dashboard">
        <div class="top">
            <i class="uil uil-bars sidebar-toggle"></i>

            <div class="search-box">
                <i class="uil uil-search"></i>
                <input type="text" placeholder="Search here...">
            </div>
            
            <img src="images/profile.jpg" alt="">
        </div>

        <div class="dash-content">
            <div class="overview">
                <div class="title">
                    <i class="uil uil-tachometer-fast-alt"></i>
                    <span class="text">Dashboard</span>
                </div>

                <div class="boxes">
                    <div class="box box1">
                        <i class="uil uil-users-alt"></i>
                        <span class="text">Clientes Registrados</span>
                        <span class="number"><?php echo $conteos['clientes']; ?></span>
                    </div>
                    <div class="box box2">
                        <i class="uil uil-box"></i>
                        <span class="text">Productos Registrados</span>
                        <span class="number"><?php echo $conteos['productos']; ?></span>
                    </div>
                    <div class="box box3">
                        <i class="uil uil-shopping-bag"></i>
                        <span class="text">Total Ventas</span>
                        <span class="number"><?php echo $conteos['ventas']; ?></span>
                    </div>
                </div>
            </div>

            <div class="activity">
                <div class="title">
                    <i class="uil uil-clock-three"></i>
                    <span class="text">Actividad Reciente</span>
                </div>

                <div class="activity-data">
                    <div class="data names">
                        <span class="data-title">Nombre</span>
                        <span class="data-list"><?php echo isset($_SESSION['nombre_usu']) ? $_SESSION['nombre_usu'] : 'Admin'; ?></span>
                        <span class="data-list">Usuario 2</span>
                    </div>
                    <div class="data email">
                        <span class="data-title">Acción</span>
                        <span class="data-list">Inició sesión</span>
                        <span class="data-list">Actualizó cliente</span>
                    </div>
                    <div class="data joined">
                        <span class="data-title">Fecha</span>
                        <span class="data-list"><?php echo date('d/m/Y H:i'); ?></span>
                        <span class="data-list"><?php echo date('d/m/Y H:i', strtotime('-1 hour')); ?></span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <script>
        // Selección de elementos del DOM
        const body = document.querySelector("body"),
              modeToggle = body.querySelector(".mode-toggle"),
              sidebar = body.querySelector("nav"),
              sidebarToggle = body.querySelector(".sidebar-toggle");

        // Cargar preferencias del usuario desde localStorage
        let getMode = localStorage.getItem("mode");
        let getStatus = localStorage.getItem("status");

        // Aplicar modo oscuro si estaba activado
        if(getMode && getMode === "dark"){
            body.classList.toggle("dark");
        }

        // Aplicar estado del sidebar si estaba cerrado
        if(getStatus && getStatus === "close"){
            sidebar.classList.toggle("close");
        }

        // Evento para el toggle del modo oscuro
        modeToggle.addEventListener("click", () => {
            body.classList.toggle("dark");
            
            // Guardar preferencia en localStorage
            if(body.classList.contains("dark")){
                localStorage.setItem("mode", "dark");
            } else {
                localStorage.setItem("mode", "light");
            }
        });

        // Evento para el toggle del sidebar
        sidebarToggle.addEventListener("click", () => {
            sidebar.classList.toggle("close");
            
            // Guardar estado en localStorage
            if(sidebar.classList.contains("close")){
                localStorage.setItem("status", "close");
            } else {
                localStorage.setItem("status", "open");
            }
        });

        // Función para actualizar los datos del dashboard
        function actualizarDatos() {
            fetch('dashboard_data.php')
                .then(response => {
                    if(!response.ok) {
                        throw new Error('Error en la respuesta del servidor');
                    }
                    return response.json();
                })
                .then(data => {
                    // Actualizar los boxes con los datos recibidos
                    if(data.clientes !== undefined) {
                        document.querySelector('.box1 .number').textContent = data.clientes;
                    }
                    if(data.productos !== undefined) {
                        document.querySelector('.box2 .number').textContent = data.productos;
                    }
                    if(data.ventas !== undefined) {
                        document.querySelector('.box3 .number').textContent = data.ventas;
                    }
                })
                .catch(error => {
                    console.error('Error al actualizar datos:', error);
                });
        }

        // Actualizar datos al cargar la página
        document.addEventListener('DOMContentLoaded', function() {
            // Configurar actualización periódica cada 5 minutos (300000 ms)
            setInterval(actualizarDatos, 300000);
        });

        // Función para formatear números grandes
        function formatoNumero(num) {
            return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
        }
    </script>
</body>
</html>
<?php
// Cerrar conexión a la base de datos al final del script
$conn->close();
?>
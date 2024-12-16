<?php
session_start();
if (!isset($_SESSION['rol']) || $_SESSION['rol'] != 1) {
    header("Location: ../views/login.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">

  <title>Dashboard Completo</title>
  <meta content="" name="description">
  <meta content="" name="keywords">

  <!-- Favicons -->
  <link href="assets/img/favicon.png" rel="icon">
  <link href="assets/img/apple-touch-icon.png" rel="apple-touch-icon">

  <!-- Google Fonts -->
  <link href="https://fonts.gstatic.com" rel="preconnect">
  <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,400|Nunito:300,400|Poppins:300,400" rel="stylesheet">

  <!-- Vendor CSS Files -->
  <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
  <link href="assets/vendor/boxicons/css/boxicons.min.css" rel="stylesheet">
  <link href="assets/vendor/echarts/echarts.min.css" rel="stylesheet">

  <!-- Template Main CSS File -->
  <link href="assets/css/style.css" rel="stylesheet">

</head>

<body>

  <!-- ======= Header ======= -->
  <header id="header" class="header fixed-top d-flex align-items-center">
    <div class="d-flex align-items-center justify-content-between">
      <a href="index.html" class="logo d-flex align-items-center">
        <img src="assets/img/logo.png" alt="">
        <span class="d-none d-lg-block">Dashboard Pil Andina</span>
      </a>
      <i class="bi bi-list toggle-sidebar-btn"></i>
    </div>

    <nav class="header-nav ms-auto">
      <ul class="d-flex align-items-center">
        <li class="nav-item dropdown pe-3">
          <a class="nav-link nav-profile d-flex align-items-center pe-0" href="#" data-bs-toggle="dropdown">
            <img src="assets/img/profile-img.jpg" alt="Profile" class="rounded-circle">
            <span class="d-none d-md-block dropdown-toggle ps-2">Noah1</span>
          </a>

          <ul class="dropdown-menu dropdown-menu-end dropdown-menu-arrow profile">
            <li class="dropdown-header">
              <h6>Noah1</h6>
              <span>Rol</span>
            </li>
            <li>
              <a class="dropdown-item d-flex align-items-center" href="#">
                <i class="bi bi-box-arrow-right"></i>
                <span>Cerrar Sesión</span>
              </a>
            </li>
          </ul>
        </li>
      </ul>
    </nav>
  </header>

  <!-- ======= Sidebar ======= -->
  <aside id="sidebar" class="sidebar">
    <ul class="sidebar-nav" id="sidebar-nav">

      <li class="nav-item">
        <a class="nav-link " href="index.html">
          <i class="bi bi-grid"></i>
          <span>Dashboard</span>
        </a>
      </li>

      <li class="nav-item">
        <a class="nav-link collapsed" data-bs-target="#comercial-nav" data-bs-toggle="collapse" href="#">
          <i class="bi bi-person-circle"></i><span>Personal</span><i class="bi bi-chevron-down ms-auto"></i>
        </a>
        <ul id="comercial-nav" class="nav-content collapse " data-bs-parent="#sidebar-nav">
        <li class="nav-item">
    <a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'listado_usuarios.php' ? 'active' : ''; ?>" href="../views/listado_usuarios.php">
        <i class="bi bi-circle"></i><span>ABM de Usuarios</span>
    </a>
</li>
<li class="nav-item">
    <a class="nav-link collapsed" href="../views/clientes.php">
        <i class="bi bi-circle"></i><span>ABM de Clientes</span>
    </a>
    </li>

    </ul>
    <li class="nav-item">
        <a class="nav-link collapsed" data-bs-target="#comercial-nav" data-bs-toggle="collapse" href="#">
          <i class="bi bi-shop-window"></i><span>Comercial</span><i class="bi bi-chevron-down ms-auto"></i>
        </a>
        <ul id="comercial-nav" class="nav-content collapse " data-bs-parent="#sidebar-nav">
        <li class="nav-item">
    <a class="nav-link collapsed" href="../views/listado_productos.php">
        <i class="bi bi-circle"></i><span>ABM de Productos</span>
    </a>
</li>
<li class="nav-item">
    <a class="nav-link collapsed" href="../views/traspaso.php">
        <i class="bi bi-circle"></i><span>Traspaso de Productos</span>
    </a>
</li>
<li class="nav-item">
    <a class="nav-link collapsed" href="../views/ventas_admin.php">
        <i class="bi bi-circle"></i><span>Ventas Admin</span>
    </a>
    </li>

    </ul>
  </aside>

  <!-- ======= Main ======= -->
  <main id="main" class="main">
    <div class="pagetitle">
      <h1>Dashboard</h1>
    </div>

    <section class="section dashboard">
      <div class="row">

        <!-- Tarjetas de Información -->
        <?php
// Conexión a la base de datos
$conn = new mysqli('127.0.0.1','root','','noah',3307);
$conn->set_charset("utf8mb4");
if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

// Total de ventas acumuladas (Contamos el número total de ventas)
$sql_total = "SELECT COUNT(*) AS total_ventas FROM ventas";
$res_total = $conn->query($sql_total);
$total_ventas = 0;
if ($res_total && $row = $res_total->fetch_assoc()) {
    $total_ventas = $row['total_ventas'] ?? 0;
}

// Ventas mes actual (Contamos el número de ventas del mes actual)
$sql_mes_actual = "
    SELECT COUNT(*) AS total_mes_actual
    FROM ventas
    WHERE MONTH(fecha_venta) = MONTH(CURDATE())
    AND YEAR(fecha_venta) = YEAR(CURDATE())";
$res_mes_actual = $conn->query($sql_mes_actual);
$ventas_mes_actual = 0;
if ($res_mes_actual && $row = $res_mes_actual->fetch_assoc()) {
    $ventas_mes_actual = $row['total_mes_actual'] ?? 0;
}

// Ventas mes anterior (Contamos el número de ventas del mes anterior)
$sql_mes_anterior = "
    SELECT COUNT(*) AS total_mes_anterior
    FROM ventas
    WHERE MONTH(fecha_venta) = MONTH(CURDATE() - INTERVAL 1 MONTH)
    AND YEAR(fecha_venta) = YEAR(CURDATE() - INTERVAL 1 MONTH)";
$res_mes_anterior = $conn->query($sql_mes_anterior);
$ventas_mes_anterior = 0;
if ($res_mes_anterior && $row = $res_mes_anterior->fetch_assoc()) {
    $ventas_mes_anterior = $row['total_mes_anterior'] ?? 0;
}

// Incremento porcentual
$incremento = 0;
if ($ventas_mes_anterior > 0) {
    $incremento = (($ventas_mes_actual - $ventas_mes_anterior) / $ventas_mes_anterior) * 100;
}

// Formatear incremento
$incremento_formato = number_format($incremento, 2, '.', ',');

$conn->close();
?>

<div class="col-lg-3 col-md-6">
  <div class="card info-card sales-card">
    <div class="card-body">
      <h5 class="card-title">Ventas Totales</h5>
      <div class="d-flex align-items-center">
        <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
          <i class="bi bi-cart"></i>
        </div>
        <div class="ps-3">
          <h6><?php echo $total_ventas; ?> ventas</h6>
          <?php if ($incremento >= 0): ?>
            <span class="text-success small pt-1 fw-bold">+<?php echo $incremento_formato; ?>%</span>
          <?php else: ?>
            <span class="text-danger small pt-1 fw-bold"><?php echo $incremento_formato; ?>%</span>
          <?php endif; ?>
          <span class="text-muted small pt-2 ps-1">incremento</span>
        </div>
      </div>
    </div>
  </div>
</div>


<?php
// Conexión a la base de datos
$host = '127.0.0.1';
$user = 'root';
$pass = '';
$db   = 'noah';
$port = 3307;

$conn = new mysqli($host, $user, $pass, $db, $port);
$conn->set_charset("utf8mb4");
if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

// Obtener total de ingresos (todas las ventas)
$sql_total = "SELECT SUM(total) AS total_ingresos FROM ventas";
$res_total = $conn->query($sql_total);
$total_ingresos = 0;
if ($res_total && $row = $res_total->fetch_assoc()) {
    $total_ingresos = (float)$row['total_ingresos'];
}

// Ingresos mes actual
$sql_mes_actual = "
    SELECT SUM(total) AS ingresos_mes_actual
    FROM ventas
    WHERE MONTH(fecha_venta) = MONTH(CURDATE())
    AND YEAR(fecha_venta) = YEAR(CURDATE())";
$res_mes_actual = $conn->query($sql_mes_actual);
$ingresos_mes_actual = 0;
if ($res_mes_actual && $row = $res_mes_actual->fetch_assoc()) {
    $ingresos_mes_actual = (float)$row['ingresos_mes_actual'];
}

// Ingresos mes anterior
$sql_mes_anterior = "
    SELECT SUM(total) AS ingresos_mes_anterior
    FROM ventas
    WHERE MONTH(fecha_venta) = MONTH(CURDATE() - INTERVAL 1 MONTH)
    AND YEAR(fecha_venta) = YEAR(CURDATE() - INTERVAL 1 MONTH)";
$res_mes_anterior = $conn->query($sql_mes_anterior);
$ingresos_mes_anterior = 0;
if ($res_mes_anterior && $row = $res_mes_anterior->fetch_assoc()) {
    $ingresos_mes_anterior = (float)$row['ingresos_mes_anterior'];
}

// Calcular incremento porcentual
$incremento = 0;
if ($ingresos_mes_anterior > 0) {
    $incremento = (($ingresos_mes_actual - $ingresos_mes_anterior) / $ingresos_mes_anterior) * 100;
}

// Formatear los valores
$total_ingresos_formato = '$' . number_format($total_ingresos, 2, '.', ',');
$incremento_formato = number_format($incremento, 2, '.', ',');

$conn->close();
?>

<div class="col-lg-3 col-md-6">
  <div class="card info-card revenue-card">
    <div class="card-body">
      <h5 class="card-title">Ingresos</h5>
      <div class="d-flex align-items-center">
        <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
          <i class="bi bi-currency-dollar"></i>
        </div>
        <div class="ps-3">
          <!-- Mostrar el total de ingresos -->
          <h6><?php echo $total_ingresos_formato; ?></h6>

          <!-- Mostrar incremento con color según el valor -->
          <?php if ($incremento >= 0): ?>
            <span class="text-success small pt-1 fw-bold">+<?php echo $incremento_formato; ?>%</span>
          <?php else: ?>
            <span class="text-danger small pt-1 fw-bold"><?php echo $incremento_formato; ?>%</span>
          <?php endif; ?>
          
          <span class="text-muted small pt-2 ps-1">incremento</span>
        </div>
      </div>
    </div>
  </div>
</div>



<div class="col-lg-3 col-md-6">
    <div class="card info-card products-card">
        <div class="card-body">
            <h5 class="card-title">Cantidad de Productos</h5>
            <div class="d-flex align-items-center">
                <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                    <i class="bi bi-box"></i>
                </div>
                <div class="ps-3">
                    <?php
                    // Conexión a la base de datos
                    $host = '127.0.0.1';
                    $user = 'root';
                    $pass = '';
                    $db = 'noah';
                    $port = 3307;

                    $conn = new mysqli($host, $user, $pass, $db, $port);

                    if ($conn->connect_error) {
                        die("Error de conexión: " . $conn->connect_error);
                    }

                    // Consulta para obtener la cantidad total de productos
                    $query = "SELECT SUM(cantidad) AS total_productos FROM productos";
                    $result = $conn->query($query);
                    $total_productos = 0;

                    if ($result && $row = $result->fetch_assoc()) {
                        $total_productos = $row['total_productos'];
                    }

                    $conn->close();
                    ?>
                    <h6><?php echo $total_productos; ?></h6>
                    <span class="text-muted small pt-2 ps-1">en inventario</span>
                </div>
            </div>
        </div>
    </div>
</div>


<div class="col-lg-3 col-md-6">
    <div class="card info-card customers-card">
        <div class="card-body">
            <h5 class="card-title">Clientes Totales</h5>
            <div class="d-flex align-items-center">
                <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                    <i class="bi bi-people"></i>
                </div>
                <div class="ps-3">
                    <?php
                    // Conexión a la base de datos
                    $host = '127.0.0.1';
                    $user = 'root';
                    $pass = '';
                    $db = 'noah';
                    $port = 3307;

                    $conn = new mysqli($host, $user, $pass, $db, $port);

                    if ($conn->connect_error) {
                        die("Error de conexión: " . $conn->connect_error);
                    }

                    // Consulta para contar la cantidad total de clientes
                    $query = "SELECT COUNT(*) AS total_clientes FROM clientes";
                    $result = $conn->query($query);
                    $total_clientes = 0;

                    if ($result && $row = $result->fetch_assoc()) {
                        $total_clientes = $row['total_clientes'];
                    }

                    $conn->close();
                    ?>
                    <h6><?php echo $total_clientes; ?></h6>
                    <span class="text-muted small pt-2 ps-1">registrados</span>
                </div>
            </div>
        </div>
    </div>
</div>

        <!-- Gráficos -->
        <div class="col-lg-6">
          <div class="card">
            <div class="card-body">
              <h5 class="card-title">Gráfico de Barras</h5>
              <div id="barChart" style="height: 300px;"></div>
              <script>
                document.addEventListener("DOMContentLoaded", () => {
                  const chart = echarts.init(document.querySelector("#barChart"));
                  chart.setOption({
                    xAxis: { type: 'category', data: ['Lun', 'Mar', 'Mié', 'Jue', 'Vie'] },
                    yAxis: { type: 'value' },
                    series: [{ data: [120, 200, 150, 80, 70], type: 'bar' }]
                  });
                });
              </script>
            </div>
          </div>
        </div>

        <div class="col-lg-6">
          <div class="card">
            <div class="card-body">
              <h5 class="card-title">Gráfico Circular</h5>
              <div id="pieChart" style="height: 300px;"></div>
              <script>
                document.addEventListener("DOMContentLoaded", () => {
                  const chart = echarts.init(document.querySelector("#pieChart"));
                  chart.setOption({
                    series: [
                      {
                        type: 'pie',
                        data: [
                          { value: 40, name: 'Leche' },
                          { value: 30, name: 'Mantequilla' },
                          { value: 20, name: 'Jugos' },
                          { value: 10, name: 'Mermelada' }
                        ]
                      }
                    ]
                  });
                });
              </script>
            </div>
          </div>
        </div>

        <!-- Gráficos Adicionales -->
        <div class="col-lg-6">
          <div class="card">
            <div class="card-body">
              <h5 class="card-title">Gráfico de Líneas</h5>
              <div id="lineChart" style="height: 300px;"></div>
              <script>
                document.addEventListener("DOMContentLoaded", () => {
                  const chart = echarts.init(document.querySelector("#lineChart"));
                  chart.setOption({
                    xAxis: { type: 'category', data: ['Ene', 'Feb', 'Mar', 'Abr', 'May'] },
                    yAxis: { type: 'value' },
                    series: [{ data: [300, 400, 320, 480, 600], type: 'line' }]
                  });
                });
              </script>
            </div>
          </div>
        </div>

        <div class="col-lg-6">
          <div class="card">
            <div class="card-body">
              <h5 class="card-title">Gráfico de Áreas</h5>
              <div id="areaChart" style="height: 300px;"></div>
              <script>
                document.addEventListener("DOMContentLoaded", () => {
                  const chart = echarts.init(document.querySelector("#areaChart"));
                  chart.setOption({
                    xAxis: { type: 'category', data: ['Lun', 'Mar', 'Mié', 'Jue', 'Vie'] },
                    yAxis: { type: 'value' },
                    series: [{ data: [120, 180, 150, 200, 170], type: 'line', areaStyle: {} }]
                  });
                });
              </script>
            </div>
          </div>
        </div>

      </div>
    </section>
  </main>

  <!-- Vendor JS Files -->
  <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="assets/vendor/echarts/echarts.min.js"></script>

  <!-- Template Main JS File -->
  <script src="assets/js/main.js"></script>

</body>

</html>

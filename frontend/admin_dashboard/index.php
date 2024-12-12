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
        <a class="nav-link collapsed" data-bs-target="#inventarios-nav" data-bs-toggle="collapse" href="#">
          <i class="bi bi-box"></i><span>Usuarios</span><i class="bi bi-chevron-down ms-auto"></i>
        </a>
        <ul id="inventarios-nav" class="nav-content collapse " data-bs-parent="#sidebar-nav">
        <li class="nav-item">
    <a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'listado_usuarios.php' ? 'active' : ''; ?>" href="../views/listado_usuarios.php">
        <i class="bi bi-circle"></i><span>ABM de Usuarios</span>
    </a>
</li>
          <li>
            <a href="#">
              <i class="bi bi-circle"></i><span>listado de usuarios</span>
            </a>
          </li>
          <li>
            <a href="#">
              <i class="bi bi-circle"></i><span>Reporte de Usuarios</span>
            </a>
          </li>
        </ul>
      </li>

      <li class="nav-item">
        <a class="nav-link collapsed" data-bs-target="#comercial-nav" data-bs-toggle="collapse" href="#">
          <i class="bi bi-shop"></i><span>Comercial</span><i class="bi bi-chevron-down ms-auto"></i>
        </a>
        <ul id="comercial-nav" class="nav-content collapse " data-bs-parent="#sidebar-nav">
        <li class="nav-item">
    <a class="nav-link collapsed" href="../views/listado_productos.php">
        <i class="bi bi-box"></i><span>Listado de Productos</span>
    </a>
</li>
<li class="nav-item">
    <a class="nav-link collapsed" href="../views/traspaso.php">
        <i class="bi bi-box"></i><span>Traspaso de Productos</span>
    </a>
</li>
          <li>
            <a href="#">
              <i class="bi bi-circle"></i><span>Registro de Ventas</span>
            </a>
          </li>
        </ul>
      </li>

      <li class="nav-item">
        <a class="nav-link collapsed" data-bs-target="#reportes-nav" data-bs-toggle="collapse" href="#">
          <i class="bi bi-bar-chart"></i><span>Reportes en Gráficos</span><i class="bi bi-chevron-down ms-auto"></i>
        </a>
        <ul id="reportes-nav" class="nav-content collapse " data-bs-parent="#sidebar-nav">
          <li>
            <a href="#">
              <i class="bi bi-circle"></i><span>Ventas por Usuario</span>
            </a>
          </li>
          <li>
            <a href="#">
              <i class="bi bi-circle"></i><span>Productos por Categoría</span>
            </a>
          </li>
        </ul>
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
        <div class="col-lg-3 col-md-6">
          <div class="card info-card sales-card">
            <div class="card-body">
              <h5 class="card-title">Ventas Totales</h5>
              <div class="d-flex align-items-center">
                <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                  <i class="bi bi-cart"></i>
                </div>
                <div class="ps-3">
                  <h6>1,234</h6>
                  <span class="text-success small pt-1 fw-bold">+15%</span>
                  <span class="text-muted small pt-2 ps-1">incremento</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="col-lg-3 col-md-6">
          <div class="card info-card revenue-card">
            <div class="card-body">
              <h5 class="card-title">Ingresos</h5>
              <div class="d-flex align-items-center">
                <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                  <i class="bi bi-currency-dollar"></i>
                </div>
                <div class="ps-3">
                  <h6>$5,678</h6>
                  <span class="text-success small pt-1 fw-bold">+10%</span>
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
                  <h6>654</h6>
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
                  <h6>567</h6>
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
                          { value: 40, name: 'Productos' },
                          { value: 30, name: 'Clientes' },
                          { value: 20, name: 'Órdenes' },
                          { value: 10, name: 'Otros' }
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

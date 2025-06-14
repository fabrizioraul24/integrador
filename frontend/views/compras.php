<?php
session_start();
if ($_SESSION['rol'] != 3) {
    die("Acceso denegado. Este panel es exclusivo para compradores.");
}
?>
<!DOCTYPE html>
<html lang="es">
<<<<<<< HEAD
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Comprador</title>
</head>
<body>
    <h1>Bienvenido, Comprador <?php echo $_SESSION['nombre']; ?></h1>
    <p>Opciones de compras.</p>
</body>
</html>
=======
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Carrito de compras con JavaScript</title>
    <link rel="shortcut icon" href="assets/imgs/favicon.ico" type="image/x-icon" />
    <link
      href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
      rel="stylesheet"
      integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH"
      crossorigin="anonymous"
    />
    <link
      rel="stylesheet"
      href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
    />
    <link rel="stylesheet" href="assets/css/home.css" />
  </head>

  <body>
    <!-- Botón del carrito -->
    <div class="position-relative">
      <a
        href="#"
        class="btn btn_shopping cart-badge position-fixed top-0 end-0 me-4 mt-3 swing-on-hover border bg-white"
      >
        <i class="bi bi-bag-heart"></i>
        <span
          id="contador-carrito"
          class="position-absolute top-1 start-100 translate-middle badge rounded-pill bg-danger"
        >
          0
        </span>
      </a>
    </div>

    <!-- Contenedor principal de la tienda de donas -->
    <div class="container my-5">
      <h1 class="text-center fw-bold">Bienvenidos a Tu Tienda de Donas 🍩</h1>
      <div id="donas-container" class="row g-4 mt-4"></div>
    </div>

    <!-- Offcanvas del carrito -->
    <div
      class="offcanvas offcanvas-end"
      tabindex="-1"
      id="offcanvasRight"
      aria-labelledby="offcanvasRightLabel"
    >
      <div class="offcanvas-header border-bottom">
        <h4 class="offcanvas-title text-center">Carrito de compras</h4>
        <button
          type="button"
          class="btn-close"
          data-bs-dismiss="offcanvas"
          aria-label="Close"
        ></button>
      </div>

      <div class="offcanvas-body border-bottom"></div>

      <div class="offcanvas-footer mt-4">
        <h5 class="justify-content-between mb-2">
          <span class="fw-bold px-3">SUBTOTAL:</span>
          <span id="subtotal" class="fw-bold float-end px-2 fs-2"> $0.00 </span>
        </h5>
        <div class="text-center mb-5 px-3">
          <button class="btn btn-primary mt-5 w-100" onclick="generarPedidoWhatsApp()">
            Enviar pedido por WhatsApp
          </button>
        </div>
      </div>
    </div>

    <footer class="footer bg-dark text-white mt-5 py-4">
      <div class="container">
        <div class="row align-items-center">
          <div class="col-12 col-md-6 text-center text-md-start mb-3 mb-md-0">
            <p class="mb-0">
              © 2025
              <a
                target="_blank"
                rel="noreferrer"
                href="https://www.urianviera.com"
                class="text-white text-decoration-none"
                >Urian Viera</a
              >
              || Todos los derechos reservados.
            </p>
          </div>
          <div class="col-12 col-md-6 text-center text-md-end">
            <div class="social-icons d-inline-flex justify-content-center">
              <a
                target="_blank"
                rel="noreferrer"
                href="https://github.com/urian121"
                class="text-white me-3"
              >
                <i class="bi bi-github"></i>
              </a>
              <a
                target="_blank"
                rel="noreferrer"
                href="https://www.linkedin.com/in/urian-viera"
                class="text-white me-3"
              >
                <i class="bi bi-linkedin"></i>
              </a>
              <a
                target="_blank"
                rel="noreferrer"
                href="https://www.youtube.com/WebDeveloperUrianViera"
                class="text-white me-3"
              >
                <i class="bi bi-youtube"></i>
              </a>
              <a
                target="_blank"
                rel="noreferrer"
                href="https://www.npmjs.com/package/nextjs-toast-notify"
                class="text-white me-3"
              >
                <i class="bi bi-bell"></i>
              </a>
              <a
                target="_blank"
                rel="noreferrer"
                href="https://www.urianviera.com/"
                class="text-white me-3"
              >
                <i class="bi bi-pc-display-horizontal"></i>
              </a>
              <a
                target="_blank"
                rel="noreferrer"
                href="https://www.paypal.com/donate/?hosted_button_id=4SV78MQJJH3VE"
                class="text-white"
              >
                <i class="bi bi-paypal"></i>
              </a>
            </div>
          </div>
        </div>
      </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/obtener_productos.js"></script>
    <script src="assets/js/app.js"></script>
  </body>
</html>

>>>>>>> f40753a (comit con el nuevo dash y el cotizaciones)

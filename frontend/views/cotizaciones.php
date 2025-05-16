<?php
// Iniciar sesión para mantener la cotización
session_start();

// Conexión a la base de datos
$host = '127.0.0.1';
$user = 'root';
$pass = '';
$db = 'noah';
$port = 3308;

$conn = new mysqli($host, $user, $pass, $db, $port);

if ($conn->connect_error) {
    die("<div class='p-4 bg-red-100 text-red-800 rounded'>Error de conexión: " . $conn->connect_error . "</div>");
}

$conn->set_charset("utf8mb4");

// Función para obtener productos
function obtenerProductos($conexion) {
    $sql = "SELECT id_producto, nombre_producto, precio, cantidad FROM productos";
    $result = $conexion->query($sql);
    
    if (!$result) {
        die("<div class='p-4 bg-red-100 text-red-800 rounded'>Error en la consulta: " . $conexion->error . "</div>");
    }
    
    $productos = array();
    while($row = $result->fetch_assoc()) {
        $productos[] = $row;
    }
    
    return $productos;
}

// Obtener productos
$productos = obtenerProductos($conn);

// Limpiar cotización anterior si se solicita nueva
if (isset($_GET['nueva'])) {
    unset($_SESSION['cotizacion']);
    header("Location: cotizaciones.php");
    exit;
}

// Procesar cotización
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $items = $_POST['items'] ?? [];
    $detalle = array();
    $total = 0;
    
    foreach ($items as $id => $cantidad) {
        if ($cantidad > 0) {
            foreach ($productos as $producto) {
                if ($producto['id_producto'] == $id) {
                    $subtotal = $producto['precio'] * $cantidad;
                    $total += $subtotal;
                    
                    $detalle[] = array(
                        'id' => $id,
                        'nombre' => $producto['nombre_producto'],
                        'precio' => $producto['precio'],
                        'cantidad' => $cantidad,
                        'subtotal' => $subtotal
                    );
                    break;
                }
            }
        }
    }
    
    // Guardar cotización en sesión
    $_SESSION['cotizacion'] = array(
        'detalle' => $detalle,
        'total' => $total,
        'fecha' => date('d/m/Y H:i:s')
    );
    
    // Redirigir para evitar reenvío del formulario
    header("Location: cotizaciones.php");
    exit;
}

// Generar PDF si se solicita
if (isset($_GET['imprimir']) && isset($_SESSION['cotizacion'])) {
    require('fpdf/fpdf.php');
    
    $cotizacion = $_SESSION['cotizacion'];
    
    // Crear PDF
    $pdf = new FPDF();
    $pdf->AddPage();
    $pdf->SetFont('Arial', 'B', 16);
    
    // Encabezado
    $pdf->SetTextColor(78, 107, 175);
    $pdf->Cell(0, 10, 'COTIZACION', 0, 1, 'C');
    $pdf->SetFont('Arial', '', 10);
    $pdf->Cell(0, 10, 'Fecha: ' . $cotizacion['fecha'], 0, 1, 'R');
    $pdf->Ln(10);
    
    // Configurar anchos de columna
    $w = array(80, 30, 30, 40);
    
    // Encabezados de tabla
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->SetFillColor(78, 107, 175);
    $pdf->SetTextColor(255);
    $pdf->Cell($w[0], 10, 'Producto', 1, 0, 'C', true);
    $pdf->Cell($w[1], 10, 'Precio Unit.', 1, 0, 'C', true);
    $pdf->Cell($w[2], 10, 'Cantidad', 1, 0, 'C', true);
    $pdf->Cell($w[3], 10, 'Subtotal', 1, 1, 'C', true);
    
    // Contenido de la tabla
    $pdf->SetFont('Arial', '', 10);
    $pdf->SetTextColor(0);
    $pdf->SetFillColor(224, 235, 255);
    $fill = false;
    
    foreach($cotizacion['detalle'] as $item) {
        $pdf->Cell($w[0], 10, iconv('UTF-8', 'windows-1252', $item['nombre']), 1, 0, 'L', $fill);
        $pdf->Cell($w[1], 10, 'Bs ' . number_format($item['precio'], 2), 1, 0, 'R', $fill);
        $pdf->Cell($w[2], 10, $item['cantidad'], 1, 0, 'C', $fill);
        $pdf->Cell($w[3], 10, 'Bs ' . number_format($item['subtotal'], 2), 1, 1, 'R', $fill);
        $fill = !$fill;
    }
    
    // Total
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell($w[0]+$w[1]+$w[2], 10, 'TOTAL', 1, 0, 'R', true);
    $pdf->Cell($w[3], 10, 'Bs ' . number_format($cotizacion['total'], 2), 1, 1, 'R', true);
    
    // Pie de página
    $pdf->Ln(15);
    $pdf->SetFont('Arial', 'I', 10);
    $pdf->Cell(0, 10, iconv('UTF-8', 'windows-1252', 'Gracias por su preferencia'), 0, 1, 'C');
    
    // Salida del PDF
    $pdf->Output('I', 'cotizacion_'.date('YmdHis').'.pdf');
    exit;
}

// Obtener cotización de sesión para mostrar
$cotizacion = $_SESSION['cotizacion'] ?? null;
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Cotizaciones</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .bg-primary { background-color: #4E6BAF; }
        .hover\:bg-primary-dark:hover { background-color: #3a5688; }
        .text-primary { color: #4E6BAF; }
        .border-primary { border-color: #4E6BAF; }
        .focus\:ring-primary:focus { --tw-ring-color: #4E6BAF; }
        .bg-blue-50 { background-color: #f0f4f8; }
        .btn-primary { background-color: #4E6BAF; color: white; }
        .btn-primary:hover { background-color: #3a5688; }
        .btn-secondary { background-color: #6b7280; color: white; }
        .btn-secondary:hover { background-color: #4b5563; }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">
    <div class="container mx-auto px-4 py-8">
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-primary mb-2">
                <i class="fas fa-file-invoice-dollar mr-2"></i>Sistema de Cotizaciones
            </h1>
            <p class="text-gray-600">Gestión completa de cotizaciones de productos</p>
        </div>

        <?php if (isset($cotizacion) && !empty($cotizacion['detalle'])): ?>
            <div class="bg-white rounded-lg shadow-md overflow-hidden mb-6">
                <div class="bg-primary px-6 py-4 text-white">
                    <h2 class="text-xl font-semibold">
                        <i class="fas fa-receipt mr-2"></i>Cotización Generada
                    </h2>
                </div>
                <div class="p-6">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-primary text-white">
                                <tr>
                                    <th class="px-4 py-3 text-left">Producto</th>
                                    <th class="px-4 py-3 text-left">Precio Unit.</th>
                                    <th class="px-4 py-3 text-left">Cantidad</th>
                                    <th class="px-4 py-3 text-left">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach($cotizacion['detalle'] as $item): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-4"><?= htmlspecialchars($item['nombre']) ?></td>
                                    <td class="px-4 py-4">Bs <?= number_format($item['precio'], 2) ?></td>
                                    <td class="px-4 py-4"><?= $item['cantidad'] ?></td>
                                    <td class="px-4 py-4">Bs <?= number_format($item['subtotal'], 2) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot class="bg-gray-50">
                                <tr>
                                    <td colspan="3" class="px-4 py-3 text-right font-medium">TOTAL</td>
                                    <td class="px-4 py-3 font-bold">Bs <?= number_format($cotizacion['total'], 2) ?></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    <div class="mt-6 flex justify-between">
                        <a href="cotizaciones.php?nueva=1" class="btn-primary px-4 py-2 rounded-md">
                            <i class="fas fa-plus-circle mr-2"></i> Nueva Cotización
                        </a>
                        <a href="cotizaciones.php?imprimir=1" class="bg-green-600 hover:bg-green-700 px-4 py-2 text-white rounded-md">
                            <i class="fas fa-print mr-2"></i> Imprimir Cotización
                        </a>
                    </div>
                </div>
            </div>
        <?php elseif (empty($productos)): ?>
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded">
                <div class="flex items-center">
                    <i class="fas fa-exclamation-circle mr-2"></i>
                    <span>No hay productos disponibles para cotizar.</span>
                </div>
            </div>
        <?php else: ?>
            <div class="bg-white rounded-lg shadow-md overflow-hidden mb-6">
                <div class="bg-primary px-6 py-4 text-white">
                    <h2 class="text-xl font-semibold">
                        <i class="fas fa-calculator mr-2"></i>Generar Nueva Cotización
                    </h2>
                </div>
                <div class="p-6">
                    <form method="post" action="cotizaciones.php">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-primary text-white">
                                    <tr>
                                        <th class="px-4 py-3 text-left">Producto</th>
                                        <th class="px-4 py-3 text-left">Precio</th>
                                        <th class="px-4 py-3 text-left">Stock</th>
                                        <th class="px-4 py-3 text-left">Cantidad</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <?php foreach($productos as $producto): ?>
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-4 py-4"><?= htmlspecialchars($producto['nombre_producto']) ?></td>
                                        <td class="px-4 py-4">Bs <?= number_format($producto['precio'], 2) ?></td>
                                        <td class="px-4 py-4"><?= $producto['cantidad'] ?></td>
                                        <td class="px-4 py-4">
                                            <input type="number" 
                                                   name="items[<?= $producto['id_producto'] ?>]" 
                                                   min="0" 
                                                   max="<?= $producto['cantidad'] ?>" 
                                                   value="0"
                                                   class="w-20 px-2 py-1 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary">
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-6">
                            <button type="submit" class="btn-primary px-4 py-2 rounded-md">
                                <i class="fas fa-check-circle mr-2"></i> Generar Cotización
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        <?php endif; ?>

        <?php if (isset($cotizacion) && empty($cotizacion['detalle'])): ?>
            <div class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4 mb-6 rounded">
                <div class="flex items-center">
                    <i class="fas fa-exclamation-triangle mr-2"></i>
                    <span>No seleccionaste ningún producto para cotizar.</span>
                </div>
            </div>
            <div class="mt-4">
                <a href="cotizaciones.php" class="btn-primary px-4 py-2 rounded-md">
                    <i class="fas fa-arrow-left mr-2"></i> Volver
                </a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>

<?php
$conn->close();
?>
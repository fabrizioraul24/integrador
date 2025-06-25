<?php
session_start();
date_default_timezone_set('America/La_Paz');

$host = '127.0.0.1';
$user = 'root';
$pass = '';
$db = 'noah';
$port = 3308;

$conn = new mysqli($host, $user, $pass, $db, $port);
if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

// Obtener correo del usuario logueado
$email = 'admin@pilandina.com'; // Valor por defecto
if (isset($_SESSION['id_usuario'])) {
    $stmt = $conn->prepare("SELECT email FROM usuarios WHERE id_usuario = ?");
    $stmt->bind_param("i", $_SESSION['id_usuario']);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $email = htmlspecialchars($user['email'] ?? 'No disponible');
    }
    $stmt->close();
}

function obtenerConteos($conn) {
    $conteos = ['clientes' => 0, 'productos' => 0, 'ventas' => 0];
    try {
        $query = $conn->query("SELECT COUNT(*) as total FROM clientes");
        if ($query) $conteos['clientes'] = $query->fetch_assoc()['total'];

        $query = $conn->query("SELECT COUNT(*) as total FROM productos");
        if ($query) $conteos['productos'] = $query->fetch_assoc()['total'];

        $query = $conn->query("SELECT COUNT(*) as total FROM ventas");
        if ($query) $conteos['ventas'] = $query->fetch_assoc()['total'];
    } catch (Exception $e) {
        error_log("Error al obtener conteos: " . $e->getMessage());
    }
    return $conteos;
}

$conteos = obtenerConteos($conn);

// Datos para el gráfico de ventas mensuales
function obtenerVentasMensuales($conn) {
    $ventasMensuales = [];
    $meses = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
    $anioActual = date('Y');
    for ($i = 0; $i < 12; $i++) {
        $mes = str_pad($i + 1, 2, '0', STR_PAD_LEFT);
        $sql = "SELECT SUM(total) as total FROM ventas WHERE MONTH(fecha_venta) = ? AND YEAR(fecha_venta) = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $mes, $anioActual);
        $stmt->execute();
        $result = $stmt->get_result();
        $total = $result->fetch_assoc()['total'] ?? 0;
        $ventasMensuales[] = $total ? floatval($total) : 0;
        $stmt->close();
    }
    return ['labels' => $meses, 'data' => $ventasMensuales];
}
$ventasData = obtenerVentasMensuales($conn);

// Manejo de logout
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: dashboard.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mensaje'])) {
    header('Content-Type: application/json');
    $mensaje = strtolower(trim($_POST['mensaje'] ?? ''));

    function mesANumero($mes) {
        $meses = [
            'enero'=>'01','febrero'=>'02','marzo'=>'03','abril'=>'04',
            'mayo'=>'05','junio'=>'06','julio'=>'07','agosto'=>'08',
            'septiembre'=>'09','octubre'=>'10','noviembre'=>'11','diciembre'=>'12'
        ];
        return $meses[$mes] ?? date('m');
    }

    function stockProducto($conn, $mensaje) {
        $producto = null;
        $id_producto = null;
        if (preg_match('/stock\s+de\s+(\d+)/', $mensaje, $numMatches)) $id_producto = intval($numMatches[1]);
        if (preg_match('/stock\s+de\s+([a-z\s]+)/i', $mensaje, $matches)) $producto = trim($matches[1]);

        if ($id_producto) {
            $stmt = $conn->prepare("SELECT nombre_producto, cantidad, foto, id_categoria, tipo_de_presentacion FROM productos WHERE id_producto = ? LIMIT 1");
            $stmt->bind_param("i", $id_producto);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($res && $res->num_rows > 0) {
                $row = $res->fetch_assoc();
                $img = $row['foto'] ? $row['foto'] : '';
                $texto = "Producto con ID <strong>$id_producto</strong>: <strong>" . htmlspecialchars($row['nombre_producto']) . "</strong>, stock actual: <strong>" . $row['cantidad'] . "</strong> unidades.";
                $texto .= " <button onclick=\"generateReport('category', $id_producto, '{$row['id_categoria']}')\" class='btn-report'>📊 Reporte por Categoría</button>";
                $texto .= " <button onclick=\"generateReport('presentation', $id_producto, '{$row['tipo_de_presentacion']}')\" class='btn-report'>🎀 Reporte por Presentación</button>";
                return ['texto' => $texto, 'imagen' => $img];
            }
            return ['texto' => "No encontré producto con ID <strong>$id_producto</strong>.", 'imagen' => ''];
        } elseif ($producto) {
            $producto_esc = $conn->real_escape_string($producto);
            $sql = "SELECT nombre_producto, cantidad, foto, id_categoria, tipo_de_presentacion FROM productos WHERE nombre_producto LIKE '%$producto_esc%' LIMIT 1";
            $res = $conn->query($sql);
            if ($res && $res->num_rows > 0) {
                $row = $res->fetch_assoc();
                $img = $row['foto'] ? $row['foto'] : '';
                $texto = "Producto <strong>" . htmlspecialchars($row['nombre_producto']) . "</strong> tiene stock actual de <strong>" . $row['cantidad'] . "</strong> unidades.";
                $texto .= " <button onclick=\"generateReport('category', 0, '{$row['id_categoria']}')\" class='btn-report'>📊 Reporte por Categoría</button>";
                $texto .= " <button onclick=\"generateReport('presentation', 0, '{$row['tipo_de_presentacion']}')\" class='btn-report'>🎀 Reporte por Presentación</button>";
                return ['texto' => $texto, 'imagen' => $img];
            }
            return ['texto' => "No encontré producto con nombre similar a '<strong>" . htmlspecialchars($producto) . "</strong>'.", 'imagen' => ''];
        }
        return ['texto' => "Indica el nombre o ID del producto para consultar stock (ej: 'stock de 44' o 'stock de leche').", 'imagen' => ''];
    }

    function ultimaVenta($conn) {
        $sql = "SELECT v.id_venta, v.fecha_venta, c.nombres_y_apellidos, SUM(dv.cantidad) AS total_items, v.total
                FROM ventas v
                LEFT JOIN clientes c ON v.id_cliente = c.id_cliente
                LEFT JOIN detalle_ventas dv ON v.id_venta = dv.id_venta
                GROUP BY v.id_venta
                ORDER BY v.fecha_venta DESC
                LIMIT 1";
        $res = $conn->query($sql);
        if ($res && $res->num_rows > 0) {
            $row = $res->fetch_assoc();
            return ['texto' => "Tu última venta fue el día <strong>" . date('d/m/Y H:i', strtotime($row['fecha_venta'])) . "</strong>, al cliente <strong>" . htmlspecialchars($row['nombres_y_apellidos'] ?? 'Desconocido') . "</strong>, con un total de <strong>" . $row['total_items'] . "</strong> productos vendidos y un monto total de <strong>$" . number_format($row['total'], 2) . "</strong>.", 'imagen' => ''];
        }
        return ['texto' => "No se encontraron ventas recientes.", 'imagen' => ''];
    }

    function mejorProductoMes($conn, $mensaje) {
        $anioActual = date('Y');
        if (preg_match('/(enero|febrero|marzo|abril|mayo|junio|julio|agosto|septiembre|octubre|noviembre|diciembre)(?:\s*(\d{4}))?/i', $mensaje, $matches)) {
            $mes = strtolower($matches[1]);
            $anio = $matches[2] ?? $anioActual;
            $mes_num = mesANumero($mes);
            $fecha_inicio = "$anio-$mes_num-01";
            $fecha_fin = date('Y-m-t', strtotime($fecha_inicio));
        } else {
            $fecha_inicio = date('Y-m-01');
            $fecha_fin = date('Y-m-t');
        }

        $sql = "SELECT p.nombre_producto, SUM(dv.cantidad) AS total_vendido
                FROM detalle_ventas dv
                JOIN ventas v ON dv.id_venta = v.id_venta
                JOIN productos p ON dv.id_producto = p.id_producto
                WHERE v.fecha_venta BETWEEN '$fecha_inicio' AND '$fecha_fin'
                GROUP BY p.id_producto
                ORDER BY total_vendido DESC
                LIMIT 1";
        $res = $conn->query($sql);
        if ($res && $res->num_rows > 0) {
            $row = $res->fetch_assoc();
            return ['texto' => "El mejor producto vendido entre <strong>$fecha_inicio</strong> y <strong>$fecha_fin</strong> fue '<strong>" . htmlspecialchars($row['nombre_producto']) . "</strong>' con <strong>" . $row['total_vendido'] . "</strong> unidades vendidas.", 'imagen' => ''];
        }
        return ['texto' => "No hay ventas registradas en el período indicado.", 'imagen' => ''];
    }

    function mejorCompraCliente($conn, $mensaje) {
        if (preg_match('/cliente\s+(.+)/i', $mensaje, $matches)) {
            $cliente = $conn->real_escape_string(trim($matches[1]));
            $sql = "SELECT c.nombres_y_apellidos, p.nombre_producto, SUM(dv.cantidad) AS total_comprado
                    FROM ventas v
                    JOIN clientes c ON v.id_cliente = c.id_cliente
                    JOIN detalle_ventas dv ON v.id_venta = dv.id_venta
                    JOIN productos p ON dv.id_producto = p.id_producto
                    WHERE c.nombres_y_apellidos LIKE '%$cliente%'
                    GROUP BY p.id_producto
                    ORDER BY total_comprado DESC
                    LIMIT 1";
            $res = $conn->query($sql);
            if ($res && $res->num_rows > 0) {
                $row = $res->fetch_assoc();
                return ['texto' => "El mejor producto comprado por el cliente <strong>" . htmlspecialchars($row['nombres_y_apellidos']) . "</strong> fue '<strong>" . htmlspecialchars($row['nombre_producto']) . "</strong>' con <strong>" . $row['total_comprado'] . "</strong> unidades.", 'imagen' => ''];
            }
            return ['texto' => "No se encontraron compras para el cliente indicado.", 'imagen' => ''];
        }
        return ['texto' => "Indica el nombre del cliente (ej: 'mejor compra cliente Juan Perez').", 'imagen' => ''];
    }

    function responder($conn, $mensaje) {
        if (strpos($mensaje, 'última venta') !== false || strpos($mensaje, 'ultima venta') !== false) return ultimaVenta($conn);
        if (preg_match('/stock\s+de\s+(\d+)/', $mensaje) || preg_match('/stock\s+de\s+[a-z\s]+/i', $mensaje)) return stockProducto($conn, $mensaje);
        if (strpos($mensaje, 'mejor producto') !== false || strpos($mensaje, 'producto más vendido') !== false) return mejorProductoMes($conn, $mensaje);
        if (strpos($mensaje, 'mejor compra cliente') !== false) return mejorCompraCliente($conn, $mensaje);
        return ['texto' => "No entendí tu pregunta. Puedes consultar sobre: <strong>última venta</strong>, <strong>stock de <producto o id></strong>, <strong>mejor producto del mes</strong> o <strong>mejor compra cliente <nombre></strong>.", 'imagen' => ''];
    }

    try {
        $respuesta = responder($conn, $mensaje);
        echo json_encode(['texto' => $respuesta['texto'], 'imagen' => $respuesta['imagen']]);
    } catch (Exception $e) {
        echo json_encode(['error' => 'Error inesperado: ' . $e->getMessage()]);
    }
    exit;
}

if (isset($_GET['report'])) {
    require_once 'fpdf/fpdf.php';
    $reportType = $_GET['report'];
    $id = isset($_GET['product']) ? (int)$_GET['product'] : 0;
    $category = isset($_GET['category']) ? $_GET['category'] : '';
    $presentation = isset($_GET['presentation']) ? $_GET['presentation'] : '';

    $pdf = new FPDF('P', 'mm', 'A4');
    $pdf->AddPage();
    $pdf->SetAutoPageBreak(true, 15);
    $pdf->SetFont('Arial', 'B', 18);
    $pdf->SetTextColor(50, 100, 150);
    $pdf->Cell(0, 10, 'Reporte Bot - Pil Andina', 0, 1, 'C');
    $pdf->SetFont('Arial', 'I', 12);
    $pdf->Cell(0, 10, 'Generado por tu Asistente Virtual ✨', 0, 1, 'C');
    $pdf->SetFont('Arial', '', 10);
    $pdf->Cell(0, 10, 'Fecha: ' . date('d/m/Y H:i'), 0, 1);
    $pdf->Ln(10);

    if ($reportType === 'category') {
        $sql = "SELECT c.nombre_categoria, p.nombre_producto, SUM(dv.cantidad) as total_vendido, p.precio
                FROM productos p
                JOIN categorias c ON p.id_categoria = c.id_categoria
                LEFT JOIN detalle_ventas dv ON p.id_producto = dv.id_producto
                LEFT JOIN ventas v ON dv.id_venta = v.id_venta
                WHERE p.id_categoria = ? AND p.deleted = 0
                GROUP BY p.id_producto";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $category);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $pdf->SetFont('Arial', 'B', 14);
            $pdf->Cell(0, 10, "Reporte de Ventas por Categoría: $category", 0, 1, 'C');
            $pdf->SetFillColor(230, 240, 255);
            $pdf->SetFont('Arial', '', 10);
            $pdf->Cell(50, 10, 'Producto', 1, 0, 'C', true);
            $pdf->Cell(40, 10, 'Total Vendido', 1, 0, 'C', true);
            $pdf->Cell(40, 10, 'Precio', 1, 1, 'C', true);
            while ($row = $result->fetch_assoc()) {
                $pdf->Cell(50, 10, $row['nombre_producto'], 1, 0, 'C');
                $pdf->Cell(40, 10, $row['total_vendido'] ?: '0', 1, 0, 'C');
                $pdf->Cell(40, 10, number_format($row['precio'], 2), 1, 1, 'C');
            }
        } else {
            $pdf->Cell(0, 10, 'No hay datos disponibles para esta categoría.', 0, 1);
        }
    } elseif ($reportType === 'presentation') {
        $sql = "SELECT tipo_de_presentacion, nombre_producto, SUM(dv.cantidad) as total_vendido, precio
                FROM productos p
                LEFT JOIN detalle_ventas dv ON p.id_producto = dv.id_producto
                LEFT JOIN ventas v ON dv.id_venta = v.id_venta
                WHERE tipo_de_presentacion = ? AND p.deleted = 0
                GROUP BY p.id_producto";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $presentation);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $pdf->SetFont('Arial', 'B', 14);
            $pdf->Cell(0, 10, "Reporte de Ventas por Presentación: $presentation", 0, 1, 'C');
            $pdf->SetFillColor(255, 230, 240); // Rosa suave para un toque estético
            $pdf->SetTextColor(120, 60, 90); // Morado suave
            $pdf->SetFont('Arial', '', 10);
            $pdf->Cell(50, 10, 'Producto', 1, 0, 'C', true);
            $pdf->Cell(40, 10, 'Total Vendido', 1, 0, 'C', true);
            $pdf->Cell(40, 10, 'Precio', 1, 1, 'C', true);
            while ($row = $result->fetch_assoc()) {
                $pdf->Cell(50, 10, $row['nombre_producto'], 1, 0, 'C');
                $pdf->Cell(40, 10, $row['total_vendido'] ?: '0', 1, 0, 'C');
                $pdf->Cell(40, 10, number_format($row['precio'], 2), 1, 1, 'C');
            }
        } else {
            $pdf->Cell(0, 10, 'No hay datos disponibles para esta presentación.', 0, 1);
        }
    }

    $pdf->SetY(-15);
    $pdf->SetFont('Arial', 'I', 8);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->Cell(0, 5, 'Hecho por Falconbot - ' . date('Y'), 0, 0, 'C');
    $pdf->Output('I', 'Reporte_Bot_' . $reportType . '_' . ($id ?: $category) . '_' . date('Ymd_His') . '.pdf');
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Pil Andina</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#4e6baf',
                        'primary-dark': '#3a5186',
                        'primary-light': '#86acd4',
                        panel: '#ffffff',
                        'panel-dark': '#1f2937',
                        accent: '#42568b',
                        'bot-bg': '#f8fafc',
                        'bot-dark-bg': '#2d3748',
                    },
                    animation: {
                        'fade-in': 'fadeIn 0.5s ease-in',
                        'slide-up': 'slideUp 0.4s ease-out',
                        'bounce-light': 'bounceLight 2s infinite',
                        'pulse-glow': 'pulseGlow 2s infinite',
                    },
                    backgroundImage: {
                        'gradient-primary': 'linear-gradient(135deg, #42568b 0%, #86acd4 100%)',
                        'gradient-card': 'linear-gradient(145deg, #ffffff 0%, #f8fafc 100%)',
                        'gradient-dark': 'linear-gradient(145deg, #1f2937 0%, #374151 100%)',
                    }
                }
            }
        }
    </script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
        
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
            0%, 100% { transform: translateY(0px); }
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
            transform: translateY(-8px) scale(1.02);
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.15);
        }
        
        .dark .card-hover:hover {
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.4);
        }
        
        .sidebar-animation {
            transition: all 0.4s cubic-bezier(0.68, -0.55, 0.265, 1.55);
        }
        
        .floating-gradient {
            background: linear-gradient(-45deg, #4e6baf, #86acd4, #42568b, #5a7bc7);
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
        
        .chat-bubble-pulse {
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }
        
        .scrollbar-thin::-webkit-scrollbar {
            width: 6px;
        }
        
        .scrollbar-thin::-webkit-scrollbar-track {
            background: #f1f5f9;
            border-radius: 3px;
        }
        
        .scrollbar-thin::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 3px;
        }
        
        .scrollbar-thin::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }
        
        .dark .scrollbar-thin::-webkit-scrollbar-track {
            background: #374151;
        }
        
        .dark .scrollbar-thin::-webkit-scrollbar-thumb {
            background: #6b7280;
        }
        
        /* Chat Panel Styles */
        #chatPanel {
            background: var(--bot-bg, #f8fafc);
            border-radius: 12px 0 0 12px;
            overflow: hidden;
        }
        .dark #chatPanel {
            background: var(--bot-dark-bg, #2d3748);
        }
        #chatMessages {
            padding: 20px;
            overflow-y: auto;
            flex-grow: 1;
            background: var(--bot-bg, #f8fafc);
        }
        .dark #chatMessages {
            background: var(--bot-dark-bg, #2d3748);
        }
        .message {
            max-width: 75%;
            margin-bottom: 15px;
            padding: 12px 18px;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            font-size: 0.95rem;
            line-height: 1.5;
            word-wrap: break-word;
            transition: all 0.3s ease;
        }
        .message.user {
            background-color: #4e6baf; /* Azul para mensajes del usuario */
            color: white;
            margin-left: auto;
            border-bottom-right-radius: 4px;
        }
        .message.bot {
            background-color: #e2e8f0;
            color: #2d3748;
            margin-right: auto;
            border-bottom-left-radius: 4px;
        }
        .dark .message.bot {
            background-color: #4a5568;
            color: #edf2f7;
        }
        .message img {
            max-width: 160px;
            margin-top: 10px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }
        #chatPanel form {
            display: flex;
            padding: 12px 16px;
            border-top: 1px solid #e2e8f0;
            background: var(--bot-bg, #f8fafc);
        }
        .dark #chatPanel form {
            border-top: 1px solid #4a5568;
            background: var(--bot-dark-bg, #2d3748);
        }
        #chatPanel input#mensaje {
            flex-grow: 1;
            padding: 10px 14px;
            border-radius: 20px 0 0 20px;
            border: 2px solid var(--primary);
            font-size: 0.95rem;
            color: #2d3748;
            transition: all 0.3s ease;
            outline: none;
            font-weight: 500;
            box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.05);
        }
        #chatPanel input#mensaje::placeholder {
            color: #a0aec0;
            font-style: italic;
        }
        #chatPanel input#mensaje:focus {
            border-color: #86acd4;
            box-shadow: 0 0 10px rgba(78, 107, 175, 0.3);
            background-color: #fff;
            color: #2d3748;
        }
        #chatPanel button[type="submit"] {
            background-color: var(--primary);
            border: none;
            color: white;
            padding: 0 22px;
            border-radius: 0 20px 20px 0;
            font-size: 0.95rem;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        #chatPanel button[type="submit"]:hover {
            background-color: #3a5186;
        }
        .dark #chatPanel input#mensaje {
            background-color: #4a5568;
            color: #edf2f7;
            border: 2px solid #86acd4;
            box-shadow: inset 0 2px 4px rgba(255, 255, 255, 0.05);
        }
        .dark #chatPanel input#mensaje::placeholder {
            color: #cbd5e0;
        }
        .dark #chatPanel input#mensaje:focus {
            border-color: #86acd4;
            box-shadow: 0 0 10px rgba(134, 172, 212, 0.3);
            background-color: #4a5568;
            color: #edf2f7;
        }
        .dark #chatPanel button[type="submit"] {
            background-color: #86acd4;
        }
        .dark #chatPanel button[type="submit"]:hover {
            background-color: #6b8ac7;
        }
        .btn-report {
            padding: 8px 16px;
            margin: 5px 5px 0 0;
            background: #86acd4;
            color: white;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            transition: background 0.3s ease;
            font-size: 0.85rem;
        }
        .btn-report:hover {
            background: #6d94b9;
        }
        .dark .btn-report {
            background: #86acd4;
        }
        .dark .btn-report:hover {
            background: #6b8ac7;
        }
    </style>
</head>
<body class="bg-gray-50 dark:bg-gray-900 transition-colors duration-300">
    <!-- Sidebar -->
    <aside id="sidebar" class="fixed left-0 top-0 z-40 h-screen w-64 transition-transform duration-300 transform -translate-x-full lg:translate-x-0 sidebar-animation">
        <div class="h-full px-3 py-4 overflow-y-auto bg-white dark:bg-gray-800 shadow-2xl">
            <!-- Logo -->
            <div class="flex items-center justify-center mb-8 p-4">
                <div class="relative">
                    <div class="w-13 h-13 bg-gradient-primary rounded-full flex items-center justify-center shadow-lg animate-pulse-glow overflow-hidden">
                        <img src="../views/logo/image.jpg" alt="Pil Andina Logo" class="w-full h-full object-cover">
                    </div>
                    <div class="absolute -top-1 -right-1 w-4 h-4 bg-green-400 rounded-full animate-bounce-light"></div>
                </div>
                <div class="ml-3">
                    <h1 class="text-xl font-bold text-gray-800 dark:text-white text-shadow">Pil Andina</h1>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Sistema de Gestión</p>
                </div>
            </div>

            <!-- Navigation -->
            <nav class="space-y-2">
               <a href="../views/dashboard.php" class="flex items-center p-3 text-gray-700 dark:text-gray-200 rounded-lg hover:bg-primary hover:text-white transition-all duration-300 group card-hover">
                    <div class="w-8 h-8 bg-gradient-primary rounded-lg flex items-center justify-center shadow-sm group-hover:shadow-lg transition-all duration-300">
                        <i class="fas fa-tachometer-alt text-white text-sm"></i>
                    </div>
                    <span class="ml-3 font-medium">Dashboard</span>
                </a>
                
                <a href="../views/usuarios.php" class="flex items-center p-3 text-gray-700 dark:text-gray-200 rounded-lg hover:bg-primary hover:text-white transition-all duration-300 group card-hover">
                    <div class="w-8 h-8 bg-blue-500 rounded-lg flex items-center justify-center shadow-sm group-hover:shadow-lg transition-all duration-300">
                        <i class="fas fa-users text-white text-sm"></i>
                    </div>
                    <span class="ml-3 font-medium">Usuarios</span>
                </a>
                
                <a href="../views/clientes.php" class="flex items-center p-3 text-gray-700 dark:text-gray-200 rounded-lg hover:bg-primary hover:text-white transition-all duration-300 group card-hover">
                    <div class="w-8 h-8 bg-green-500 rounded-lg flex items-center justify-center shadow-sm group-hover:shadow-lg transition-all duration-300">
                        <i class="fas fa-user-friends text-white text-sm"></i>
                    </div>
                    <span class="ml-3 font-medium">Clientes</span>
                </a>
                
                <a href="../views/listado_productos.php" class="flex items-center p-3 text-gray-700 dark:text-gray-200 rounded-lg hover:bg-primary hover:text-white transition-all duration-300 group card-hover">
                    <div class="w-8 h-8 bg-purple-500 rounded-lg flex items-center justify-center shadow-sm group-hover:shadow-lg transition-all duration-300">
                        <i class="fas fa-box text-white text-sm"></i>
                    </div>
                    <span class="ml-3 font-medium">Productos</span>
                </a>

                <a href="../views/categorias.php" class="flex items-center p-3 text-gray-700 dark:text-gray-200 rounded-lg hover:bg-primary hover:text-white transition-all duration-300 group card-hover">
                    <div class="w-8 h-8 bg-pink-500 rounded-lg flex items-center justify-center shadow-sm group-hover:shadow-lg transition-all duration-300">
                        <i class="fas fa-tags text-white text-sm"></i>
                    </div>
                    <span class="ml-3 font-medium">Categorias</span>
                </a>

                <a href="../views/traspaso.php" class="flex items-center p-3 text-gray-700 dark:text-gray-200 rounded-lg hover:bg-primary hover:text-white transition-all duration-300 group card-hover">
                    <div class="w-8 h-8 bg-orange-500 rounded-lg flex items-center justify-center shadow-sm group-hover:shadow-lg transition-all duration-300">
                        <i class="fas fa-exchange-alt text-white text-sm"></i>
                    </div>
                    <span class="ml-3 font-medium">Traspaso</span>
                </a>
                
                <a href="../views/ventas_admin.php" class="flex items-center p-3 text-gray-700 dark:text-gray-200 rounded-lg hover:bg-primary hover:text-white transition-all duration-300 group card-hover">
                    <div class="w-8 h-8 bg-red-500 rounded-lg flex items-center justify-center shadow-sm group-hover:shadow-lg transition-all duration-300">
                        <i class="fas fa-shopping-bag text-white text-sm"></i>
                    </div>
                    <span class="ml-3 font-medium">Ventas</span>
                </a>
                
                <a href="../views/cotizaciones.php" class="flex items-center p-3 text-gray-700 dark:text-gray-200 rounded-lg hover:bg-primary hover:text-white transition-all duration-300 group card-hover">
                    <div class="w-8 h-8 bg-yellow-500 rounded-lg flex items-center justify-center shadow-sm group-hover:shadow-lg transition-all duration-300">
                        <i class="fas fa-file-invoice text-white text-sm"></i>
                    </div>
                    <span class="ml-3 font-medium">Cotizaciones</span>
                </a>
                
                <a href="../views/kardex.php" class="flex items-center p-3 text-gray-700 dark:text-gray-200 rounded-lg hover:bg-primary hover:text-white transition-all duration-300 group card-hover">
                    <div class="w-8 h-8 bg-teal-500 rounded-lg flex items-center justify-center shadow-sm group-hover:shadow-lg transition-all duration-300">
                        <i class="fas fa-clipboard-list text-white text-sm"></i>
                    </div>
                    <span class="ml-3 font-medium">Kardex</span>
                </a>
            </nav>

            <!-- Bottom Section -->
            <div class="absolute bottom-4 left-3 right-3">
                <div class="flex items-center justify-between p-3 bg-gray-100 dark:bg-gray-700 rounded-lg">
                    <button id="darkModeToggle" class="flex items-center space-x-2 text-gray-600 dark:text-gray-300 hover:text-primary transition-colors duration-300">
                        <i class="fas fa-moon dark:hidden"></i>
                        <i class="fas fa-sun hidden dark:block"></i>
                        <span class="text-sm font-medium">Tema</span>
                    </button>
                    <a href="?logout=1" class="text-gray-500 hover:text-red-500 transition-colors duration-300">
                        <i class="fas fa-sign-out-alt"></i>
                    </a>
                </div>
            </div>
        </div>
    </aside>

    <!-- Mobile overlay -->
    <div id="sidebarOverlay" class="fixed inset-0 z-30 bg-black bg-opacity-50 lg:hidden hidden"></div>

    <!-- Main Content -->
    <div class="lg:ml-64">
        <!-- Header -->
        <header class="bg-white dark:bg-gray-800 shadow-sm border-b border-gray-200 dark:border-gray-700 sticky top-0 z-20">
            <div class="flex items-center justify-between p-4">
                <div class="flex items-center space-x-4">
                    <button id="sidebarToggle" class="lg:hidden p-2 rounded-lg text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors duration-300">
                        <i class="fas fa-bars text-xl"></i>
                    </button>
                    <div>
                        <h1 class="text-2xl font-bold text-gray-800 dark:text-white">Dashboard Principal</h1>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Bienvenido de vuelta, <?php echo isset($_SESSION['nombre']) ? htmlspecialchars($_SESSION['nombre']) : 'Admin'; ?></p>
                    </div>
                </div>

                <div class="flex items-center space-x-4">
                    <!-- Search -->
                    <div class="relative hidden md:block">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-search text-gray-400"></i>
                        </div>
                        <input type="text" placeholder="Buscar..." class="pl-10 pr-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-primary focus:border-transparent transition-all duration-300">
                    </div>

                    <!-- Notifications -->
                    <button class="relative p-2 text-gray-600 dark:text-gray-300 hover:text-primary transition-colors duration-300">
                        <i class="fas fa-bell text-xl"></i>
                        <span class="absolute top-0 right-0 w-2 h-2 bg-red-500 rounded-full animate-pulse"></span>
                    </button>

                    <!-- Profile -->
                    <div class="flex items-center space-x-3">
                        <div class="w-10 h-10 bg-gradient-primary rounded-full flex items-center justify-center shadow-lg">
                            <i class="fas fa-user text-white text-sm"></i>
                        </div>
                        <div class="hidden md:block">
                            <p class="text-sm font-medium text-gray-700 dark:text-gray-200"><?php echo isset($_SESSION['nombre']) ? htmlspecialchars($_SESSION['nombre']) : 'Administrador'; ?></p>
                            <p class="text-xs text-gray-500 dark:text-gray-400"><?php echo $email; ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <!-- Main Dashboard Content -->
        <main class="p-6 space-y-8">
            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <!-- Card 1 -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 card-hover animate-fade-in border border-gray-100 dark:border-gray-700">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600 dark:text-gray-400 uppercase tracking-wide">Clientes Registrados</p>
                            <p class="text-3xl font-bold text-gray-900 dark:text-white mt-2"><?php echo $conteos['clientes']; ?></p>
                            <div class="flex items-center mt-2">
                                <span class="text-green-500 text-sm font-medium flex items-center">
                                    <i class="fas fa-arrow-up mr-1"></i>
                                    +12%
                                </span>
                                <span class="text-gray-500 text-sm ml-2">este mes</span>
                            </div>
                        </div>
                        <div class="w-16 h-16 bg-gradient-to-br from-blue-400 to-blue-600 rounded-2xl flex items-center justify-center shadow-lg">
                            <i class="fas fa-users text-white text-2xl"></i>
                        </div>
                    </div>
                </div>

                <!-- Card 2 -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 card-hover animate-fade-in border border-gray-100 dark:border-gray-700" style="animation-delay: 0.1s">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600 dark:text-gray-400 uppercase tracking-wide">Productos Registrados</p>
                            <p class="text-3xl font-bold text-gray-900 dark:text-white mt-2"><?php echo $conteos['productos']; ?></p>
                            <div class="flex items-center mt-2">
                                <span class="text-green-500 text-sm font-medium flex items-center">
                                    <i class="fas fa-arrow-up mr-1"></i>
                                    +8%
                                </span>
                                <span class="text-gray-500 text-sm ml-2">este mes</span>
                            </div>
                        </div>
                        <div class="w-16 h-16 bg-gradient-to-br from-purple-400 to-purple-600 rounded-2xl flex items-center justify-center shadow-lg">
                            <i class="fas fa-box text-white text-2xl"></i>
                        </div>
                    </div>
                </div>

                <!-- Card 3 -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 card-hover animate-fade-in border border-gray-100 dark:border-gray-700" style="animation-delay: 0.2s">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600 dark:text-gray-400 uppercase tracking-wide">Total Ventas</p>
                            <p class="text-3xl font-bold text-gray-900 dark:text-white mt-2"><?php echo $conteos['ventas']; ?></p>
                            <div class="flex items-center mt-2">
                                <span class="text-green-500 text-sm font-medium flex items-center">
                                    <i class="fas fa-arrow-up mr-1"></i>
                                    +24%
                                </span>
                                <span class="text-gray-500 text-sm ml-2">este mes</span>
                            </div>
                        </div>
                        <div class="w-16 h-16 bg-gradient-to-br from-green-400 to-green-600 rounded-2xl flex items-center justify-center shadow-lg">
                            <i class="fas fa-shopping-bag text-white text-2xl"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Charts and Recent Activity -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Chart Card -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 animate-slide-up border border-gray-100 dark:border-gray-700">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Ventas Mensuales</h3>
                        <button class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                            <i class="fas fa-ellipsis-h"></i>
                        </button>
                    </div>
                    <canvas id="ventasChart" height="200"></canvas>
                    <script>
                        const ctx = document.getElementById('ventasChart').getContext('2d');
                        new Chart(ctx, {
                            type: 'line',
                            data: {
                                labels: <?php echo json_encode($ventasData['labels']); ?>,
                                datasets: [{
                                    label: 'Ventas ($)',
                                    data: <?php echo json_encode($ventasData['data']); ?>,
                                    fill: true,
                                    backgroundColor: 'rgba(78, 107, 175, 0.2)',
                                    borderColor: 'rgba(78, 107, 175, 1)',
                                    borderWidth: 2,
                                    tension: 0.4,
                                    pointRadius: 5,
                                    pointHoverRadius: 7
                                }]
                            },
                            options: {
                                scales: {
                                    y: {
                                        beginAtZero: true,
                                        ticks: { callback: value => '$' + value }
                                    }
                                },
                                plugins: {
                                    legend: { position: 'top' },
                                    tooltip: { callbacks: { label: ctx => '$' + ctx.raw } }
                                }
                            }
                        });
                    </script>
                </div>

                <!-- Recent Activity -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 animate-slide-up border border-gray-100 dark:border-gray-700" style="animation-delay: 0.1s">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Actividad Reciente</h3>
                        <button class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                            <i class="fas fa-ellipsis-h"></i>
                        </button>
                    </div>
                    <div class="space-y-4 max-h-64 overflow-y-auto scrollbar-thin">
                        <div class="flex items-center space-x-3 p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                            <div class="w-10 h-10 bg-green-100 dark:bg-green-900 rounded-full flex items-center justify-center">
                                <i class="fas fa-plus text-green-600 dark:text-green-400"></i>
                            </div>
                            <div class="flex-1">
                                <p class="text-sm font-medium text-gray-900 dark:text-white">Nuevo cliente registrado</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400"><?php echo date('d/m/Y H:i'); ?></p>
                            </div>
                        </div>
                        
                        <div class="flex items-center space-x-3 p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                            <div class="w-10 h-10 bg-blue-100 dark:bg-blue-900 rounded-full flex items-center justify-center">
                                <i class="fas fa-shopping-cart text-blue-600 dark:text-blue-400"></i>
                            </div>
                            <div class="flex-1">
                                <p class="text-sm font-medium text-gray-900 dark:text-white">Venta procesada</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400"><?php echo date('d/m/Y H:i', strtotime('-15 minutes')); ?></p>
                            </div>
                        </div>
                        
                        <div class="flex items-center space-x-3 p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                            <div class="w-10 h-10 bg-yellow-100 dark:bg-yellow-900 rounded-full flex items-center justify-center">
                                <i class="fas fa-exclamation-triangle text-yellow-600 dark:text-yellow-400"></i>
                            </div>
                            <div class="flex-1">
                                <p class="text-sm font-medium text-gray-900 dark:text-white">Stock bajo detectado</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400"><?php echo date('d/m/Y H:i', strtotime('-1 hour')); ?></p>
                            </div>
                        </div>
                        
                        <div class="flex items-center space-x-3 p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                            <div class="w-10 h-10 bg-purple-100 dark:bg-purple-900 rounded-full flex items-center justify-center">
                                <i class="fas fa-user-edit text-purple-600 dark:text-purple-400"></i>
                            </div>
                            <div class="flex-1">
                                <p class="text-sm font-medium text-gray-900 dark:text-white">Producto actualizado</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400"><?php echo date('d/m/Y H:i', strtotime('-2 hours')); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 animate-fade-in border border-gray-100 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-6">Acciones Rápidas</h3>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <a href="../views/clientes.php" class="flex flex-col items-center p-4 bg-gradient-to-br from-blue-50 to-blue-100 dark:from-blue-900 dark:to-blue-800 rounded-xl hover:shadow-lg transition-all duration-300 card-hover">
                        <div class="w-12 h-12 bg-blue-500 rounded-full flex items-center justify-center mb-3">
                            <i class="fas fa-plus text-white"></i>
                        </div>
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-200">Nuevo Cliente</span>
                    </a>
                    
                    <a href="../views/listado_productos.php" class="flex flex-col items-center p-4 bg-gradient-to-br from-green-50 to-green-100 dark:from-green-900 dark:to-green-800 rounded-xl hover:shadow-lg transition-all duration-300 card-hover">
                        <div class="w-12 h-12 bg-green-500 rounded-full flex items-center justify-center mb-3">
                            <i class="fas fa-box text-white"></i>
                        </div>
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-200">Agregar Producto</span>
                    </a>
                    
                    <a href="../views/ventas_admin.php" class="flex flex-col items-center p-4 bg-gradient-to-br from-purple-50 to-purple-100 dark:from-purple-900 dark:to-purple-800 rounded-xl hover:shadow-lg transition-all duration-300 card-hover">
                        <div class="w-12 h-12 bg-purple-500 rounded-full flex items-center justify-center mb-3">
                            <i class="fas fa-shopping-cart text-white"></i>
                        </div>
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-200">Nueva Venta</span>
                    </a>
                    
                    <a href="../views/reportes.php" class="flex flex-col items-center p-4 bg-gradient-to-br from-orange-50 to-orange-100 dark:from-orange-900 dark:to-orange-800 rounded-xl hover:shadow-lg transition-all duration-300 card-hover">
                        <div class="w-12 h-12 bg-orange-500 rounded-full flex items-center justify-center mb-3">
                            <i class="fas fa-file-alt text-white"></i>
                        </div>
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-200">Generar Reporte</span>
                    </a>
                </div>
            </div>
        </main>
    </div>

    <!-- Chatbot -->
    <div id="chatBubble" class="fixed bottom-6 right-6 w-16 h-16 bg-gradient-primary rounded-full shadow-2xl cursor-pointer flex items-center justify-center z-50 chat-bubble-pulse hover:shadow-3xl transition-all duration-300">
        <i class="fas fa-comments text-white text-xl"></i>
    </div>

    <!-- Chat Panel -->
    <div id="chatPanel" class="fixed top-0 right-0 h-full w-96 max-w-full bg-white dark:bg-gray-600 shadow-2xl transform translate-x-full transition-transform duration-300 z-40 flex flex-col border-l border-gray-200 dark:border-gray-700">
        <div class="bg-gradient-primary p-4 text-white flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <div class="w-10 h-10 bg-white bg-opacity-20 rounded-full flex items-center justify-center">
                    <i class="fas fa-robot text-white"></i>
                </div>
                <div>
                    <h3 class="font-semibold">Asistente Virtual</h3>
                    <p class="text-xs opacity-80">Consulta rápida de datos</p>
                </div>
            </div>
            <button id="closeChatBtn" class="text-white hover:text-gray-200 transition-colors duration-300">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        <div id="chatMessages"></div>
        <form id="chatForm" autocomplete="off">
            <input type="text" id="mensaje" name="mensaje" placeholder="Escribe tu pregunta..." required />
            <button type="submit">Enviar</button>
        </form>
    </div>

    <script>
        // Sidebar and Overlay Toggle
        const sidebar = document.getElementById('sidebar');
        const sidebarToggle = document.getElementById('sidebarToggle');
        const sidebarOverlay = document.getElementById('sidebarOverlay');

        sidebarToggle.addEventListener('click', () => {
            sidebar.classList.toggle('-translate-x-full');
            sidebarOverlay.classList.toggle('hidden');
        });

        sidebarOverlay.addEventListener('click', () => {
            sidebar.classList.add('-translate-x-full');
            sidebarOverlay.classList.add('hidden');
        });

        // Dark Mode Toggle
        const darkModeToggle = document.getElementById('darkModeToggle');
        let isDarkMode = localStorage.getItem('darkMode') === 'true';
        
        if (isDarkMode) {
            document.body.classList.add('dark');
        }

        darkModeToggle.addEventListener('click', () => {
            document.body.classList.toggle('dark');
            isDarkMode = !isDarkMode;
            localStorage.setItem('darkMode', isDarkMode);
        });

        // Chatbot Functionality
        const chatBubble = document.getElementById('chatBubble');
        const chatPanel = document.getElementById('chatPanel');
        const closeChatBtn = document.getElementById('closeChatBtn');
        const chatMessages = document.getElementById('chatMessages');
        const chatForm = document.getElementById('chatForm');
        const mensajeInput = document.getElementById('mensaje');

        chatBubble.addEventListener('click', () => {
            chatPanel.classList.remove('translate-x-full');
        });

        closeChatBtn.addEventListener('click', () => {
            chatPanel.classList.add('translate-x-full');
        });

        function agregarMensaje(texto, tipo = 'user', imagen = '') {
            const contenedor = document.createElement('div');
            contenedor.classList.add('message', tipo);
            contenedor.innerHTML = texto;
            if (imagen) {
                const img = document.createElement('img');
                img.src = 'Uploads/' + imagen;
                img.alt = 'Imagen producto';
                contenedor.appendChild(img);
            }
            chatMessages.appendChild(contenedor);
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }

        chatForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const texto = mensajeInput.value.trim();
            if (!texto) return;
            agregarMensaje(texto, 'user');
            mensajeInput.value = '';

            try {
                const resp = await fetch('', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({ mensaje: texto }),
                });
                const data = await resp.json();
                if (data.error) {
                    agregarMensaje('Error: ' + data.error, 'bot');
                } else {
                    agregarMensaje(data.texto, 'bot', data.imagen);
                }
            } catch {
                agregarMensaje('Error de conexión al servidor.', 'bot');
            }
        });

        // Generate report
        function generateReport(type, id, value) {
            let url = `?report=${type}&product=${id}&${type === 'category' ? 'category' : 'presentation'}=${encodeURIComponent(value)}`;
            fetch(url, {
                method: 'GET'
            })
            .then(response => response.blob())
            .then(blob => {
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = `Reporte_Bot_${type}_${id}_${new Date().toISOString().replace(/[:.]/g, '-')}.pdf`;
                a.click();
                window.URL.revokeObjectURL(url);
            })
            .catch(error => console.error('Error:', error));
        }
    </script>
</body>
</html>
<?php $conn->close(); ?>
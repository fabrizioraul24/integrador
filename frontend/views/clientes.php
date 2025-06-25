<?php
session_start();
date_default_timezone_set('America/La_Paz');

$host = '127.0.0.1';
$user = 'root';
$pass = '';
$db = 'noah';
$port = 3308;

$conn = new mysqli($host, $user, $pass, $db, $port);

if ($conn->connect_error) { // Opening brace for connection error check
    die("Error de conexión: " . $conn->connect_error);
} // Closing brace for connection error check

$conn->query("ALTER TABLE clientes ADD COLUMN IF NOT EXISTS deleted TINYINT(1) DEFAULT 0");
$conn->query("ALTER TABLE clientes ADD COLUMN IF NOT EXISTS deleted_at DATETIME DEFAULT NULL");

$conn->query("CREATE TABLE IF NOT EXISTS clientes_eliminados (
    id_cliente INT PRIMARY KEY,
    nombres_y_apellidos VARCHAR(255),
    direccion VARCHAR(255),
    telefono VARCHAR(20),
    NIT VARCHAR(20),
    email VARCHAR(100),
    deleted_at DATETIME
)");

$archivo_numeracion = 'ultimo_numero_reporte.txt';
$search = isset($_GET['search']) ? $_GET['search'] : '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') { // Opening brace for POST handling
    if (isset($_POST['accion'])) { // Opening brace for accion check
        switch ($_POST['accion']) { // Opening brace for switch
            case 'agregar':
                $nombre = $conn->real_escape_string($_POST['nombre']);
                $direccion = $conn->real_escape_string($_POST['direccion']);
                $telefono = $conn->real_escape_string($_POST['telefono']);
                $nit = $conn->real_escape_string($_POST['nit']);
                $email = $conn->real_escape_string($_POST['email']);
                
                $sql = "INSERT INTO clientes (nombres_y_apellidos, direccion, telefono, NIT, email) 
                        VALUES ('$nombre', '$direccion', '$telefono', '$nit', '$email')";
                if ($conn->query($sql)) { // Opening brace for insert success
                    $mensaje = "Cliente agregado correctamente";
                    $tipo_mensaje = "success";
                } else { // Opening brace for insert failure
                    $mensaje = "Error al agregar cliente: " . $conn->error;
                    $tipo_mensaje = "error";
                } // Closing brace for insert failure
                break;
                
            case 'editar':
                $id = intval($_POST['id_cliente']);
                $nombre = $conn->real_escape_string($_POST['nombre']);
                $direccion = $conn->real_escape_string($_POST['direccion']);
                $telefono = $conn->real_escape_string($_POST['telefono']);
                $nit = $conn->real_escape_string($_POST['nit']);
                $email = $conn->real_escape_string($_POST['email']);
                
                $sql = "UPDATE clientes SET 
                        nombres_y_apellidos = '$nombre',
                        direccion = '$direccion',
                        telefono = '$telefono',
                        NIT = '$nit',
                        email = '$email'
                        WHERE id_cliente = $id";
                if ($conn->query($sql)) { // Opening brace for update success
                    $mensaje = "Cliente actualizado correctamente";
                    $tipo_mensaje = "success";
                } else { // Opening brace for update failure
                    $mensaje = "Error al actualizar cliente: " . $conn->error;
                    $tipo_mensaje = "error";
                } // Closing brace for update failure
                break;
                
            case 'restaurar':
                $id = intval($_POST['id_cliente']);
                $sql = "UPDATE clientes SET deleted = 0, deleted_at = NULL WHERE id_cliente = $id";
                if ($conn->query($sql)) { // Opening brace for restore success
                    $sql = "DELETE FROM clientes_eliminados WHERE id_cliente = $id";
                    $conn->query($sql);
                    $mensaje = "Cliente restaurado correctamente";
                    $tipo_mensaje = "success";
                } else { // Opening brace for restore failure
                    $mensaje = "Error al restaurar cliente: " . $conn->error;
                    $tipo_mensaje = "error";
                } // Closing brace for restore failure
                break;
        } // Closing brace for switch
    } // Closing brace for accion check
} // Closing brace for POST handling

if (isset($_GET['delete'])) { // Opening brace for delete action
    $id = intval($_GET['delete']);
    $sql = "INSERT INTO clientes_eliminados 
            SELECT id_cliente, nombres_y_apellidos, direccion, telefono, NIT, email, NOW() 
            FROM clientes WHERE id_cliente = $id";
    $conn->query($sql);
    $sql = "UPDATE clientes SET deleted = 1, deleted_at = NOW() WHERE id_cliente = $id";
    if ($conn->query($sql)) { // Opening brace for delete success
        $mensaje = "Cliente marcado como eliminado";
        $tipo_mensaje = "success";
    } else { // Opening brace for delete failure
        $mensaje = "Error al eliminar cliente: " . $conn->error;
        $tipo_mensaje = "error";
    } // Closing brace for delete failure
} // Closing brace for delete action

if (isset($_GET['delete_permanent'])) { // Opening brace for permanent delete
    $id = intval($_GET['delete_permanent']);
    $sql = "DELETE FROM clientes WHERE id_cliente = $id";
    if ($conn->query($sql)) { // Opening brace for permanent delete success
        $mensaje = "Cliente eliminado permanentemente";
        $tipo_mensaje = "success";
    } else { // Opening brace for permanent delete failure
        $mensaje = "Error al eliminar cliente permanentemente: " . $conn->error;
        $tipo_mensaje = "error";
    } // Closing brace for permanent delete failure
} // Closing brace for permanent delete

function obtener_siguiente_numero($archivo) { // Opening brace for function
    $numero = 100;
    if (file_exists($archivo)) { // Opening brace for file check
        $numero = (int)file_get_contents($archivo);
    } // Closing brace for file check
    $nuevo_numero = $numero + 1;
    file_put_contents($archivo, $nuevo_numero);
    return $nuevo_numero;
} // Closing brace for function

$sql = "SELECT * FROM clientes WHERE deleted = 0";
if (!empty($search)) { // Opening brace for search check
    $sql = "SELECT * FROM clientes WHERE nombres_y_apellidos LIKE ? AND deleted = 0";
    $stmt = $conn->prepare($sql);
    $search_param = "%$search%";
    $stmt->bind_param("s", $search_param);
    $stmt->execute();
    $result = $stmt->get_result();
} else { // Opening brace for no search
    $result = $conn->query($sql);
} // Closing brace for no search

$sql_eliminados = "SELECT * FROM clientes_eliminados";
$result_eliminados = $conn->query($sql_eliminados);

if (isset($_GET['reporte'])) { // Opening brace for report generation
    require('fpdf/fpdf.php');
    
    class PDF extends FPDF { // Opening brace for PDF class
        private $headerColor = array(41, 84, 144);
        private $accentColor = array(230, 126, 34);
        private $lightBlue = array(236, 245, 255);
        private $darkGray = array(52, 73, 94);
        
        function Header() { // Opening brace for Header
            $this->SetFillColor($this->headerColor[0], $this->headerColor[1], $this->headerColor[2]);
            $this->Rect(0, 0, 210, 45, 'F');
            
            $this->Image('../views/logo/sinf.png', 15, 8, 35);
            
            $this->SetFont('Arial', 'B', 22);
            $this->SetTextColor(255, 255, 255);
            $this->SetXY(60, 12);
            $this->Cell(0, 8, 'PIL ANDINA', 0, 1, 'L');
            
            $this->SetFont('Arial', '', 14);
            $this->SetXY(60, 22);
            $this->Cell(0, 6, 'REPORTE DE CLIENTES DEL SISTEMA', 0, 1, 'L');
            
            $this->SetDrawColor($this->accentColor[0], $this->accentColor[1], $this->accentColor[2]);
            $this->SetLineWidth(2);
            $this->Line(60, 32, 190, 32);
            
            $this->SetFont('Arial', '', 9);
            $this->SetXY(140, 35);
            $this->Cell(0, 4, 'Fecha: ' . date("d/m/Y H:i"), 0, 0, 'R');
            
            $this->Ln(15);
        } // Closing brace for Header
        
        function Footer() { // Opening brace for Footer
            $this->SetY(-25);
            
            $this->SetDrawColor($this->headerColor[0], $this->headerColor[1], $this->headerColor[2]);
            $this->SetLineWidth(0.5);
            $this->Line(15, $this->GetY(), 195, $this->GetY());
            
            $this->Ln(3);
            
            $this->SetFont('Arial', '', 8);
            $this->SetTextColor($this->darkGray[0], $this->darkGray[1], $this->darkGray[2]);
            
            $this->SetX(15);
            $this->Cell(90, 4, 'Código: ' . $GLOBALS['codigo_reporte'], 0, 0, 'L');
            
            $this->Cell(0, 4, 'Página ' . $this->PageNo() . ' de {nb}', 0, 0, 'R');
            
            $this->Ln(4);
            $this->SetX(15);
            $this->Cell(0, 4, '© PIL ANDINA - Sistema de Información Gerencial', 0, 0, 'C');
        } // Closing brace for Footer
        
        function CreateInfoBox($title, $content, $x, $y, $width, $height) { // Opening brace for CreateInfoBox
            $this->SetFillColor(200, 200, 200);
            $this->Rect($x + 1, $y + 1, $width, $height, 'F');
            
            $this->SetFillColor($this->lightBlue[0], $this->lightBlue[1], $this->lightBlue[2]);
            $this->SetDrawColor($this->headerColor[0], $this->headerColor[1], $this->headerColor[2]);
            $this->SetLineWidth(0.5);
            $this->Rect($x, $y, $width, $height, 'DF');
            
            $this->SetXY($x + 5, $y + 3);
            $this->SetFont('Arial', 'B', 10);
            $this->SetTextColor($this->headerColor[0], $this->headerColor[1], $this->headerColor[2]);
            $this->Cell(0, 5, $title, 0, 1, 'L');
            
            $this->SetX($x + 5);
            $this->SetFont('Arial', '', 9);
            $this->SetTextColor($this->darkGray[0], $this->darkGray[1], $this->darkGray[2]);
            $this->Cell(0, 5, $content, 0, 1, 'L');
        } // Closing brace for CreateInfoBox
        
        function CreateSectionTitle($title) { // Opening brace for CreateSectionTitle
            $this->Ln(5);
            
            $this->SetFillColor($this->accentColor[0], $this->accentColor[1], $this->accentColor[2]);
            $this->Rect(15, $this->GetY(), 180, 8, 'F');
            
            $this->SetFont('Arial', 'B', 12);
            $this->SetTextColor(255, 255, 255);
            $this->SetX(15);
            $this->Cell(180, 8, $title, 0, 1, 'C');
            
            $this->Ln(3);
        } // Closing brace for CreateSectionTitle
        
        function RoundRect($x, $y, $w, $h, $r, $style = '') { // Opening brace for RoundRect
            $k = $this->k;
            $hp = $this->h;
            if ($style == 'F')
                $op = 'f';
            elseif ($style == 'FD' || $style == 'DF')
                $op = 'B';
            else
                $op = 'S';
            $MyArc = 4/3 * (sqrt(2) - 1);
            $this->_out(sprintf('%.2F %.2F m', ($x+$r)*$k, ($hp-$y)*$k));
            $xc = $x+$w-$r;
            $yc = $y+$r;
            $this->_out(sprintf('%.2F %.2F l', $xc*$k, ($hp-$y)*$k));
            $this->_Arc($xc + $r*$MyArc, $yc - $r, $xc + $r, $yc - $r*$MyArc, $xc + $r, $yc);
            $xc = $x+$w-$r;
            $yc = $y+$h-$r;
            $this->_out(sprintf('%.2F %.2F l', ($x+$w)*$k, ($hp-$yc)*$k));
            $this->_Arc($xc + $r, $yc + $r*$MyArc, $xc + $r*$MyArc, $yc + $r, $xc, $yc + $r);
            $xc = $x+$r;
            $yc = $y+$h-$r;
            $this->_out(sprintf('%.2F %.2F l', $xc*$k, ($hp-($y+$h))*$k));
            $this->_Arc($xc - $r*$MyArc, $yc + $r, $xc - $r, $yc + $r*$MyArc, $xc - $r, $yc);
            $xc = $x+$r;
            $yc = $y+$r;
            $this->_out(sprintf('%.2F %.2F l', ($x)*$k, ($hp-$yc)*$k));
            $this->_Arc($xc - $r, $yc - $r*$MyArc, $xc - $r*$MyArc, $yc - $r, $xc, $yc - $r);
            $this->_out($op);
        } // Closing brace for RoundRect

        function _Arc($x1, $y1, $x2, $y2, $x3, $y3) { // Opening brace for _Arc
            $h = $this->h;
            $this->_out(sprintf('%.2F %.2F %.2F %.2F %.2F %.2F c ', $x1*$this->k, ($h-$y1)*$this->k,
                $x2*$this->k, ($h-$y2)*$this->k, $x3*$this->k, ($h-$y3)*$this->k));
        } // Closing brace for _Arc
    } // Closing brace for PDF class
    
    $sql = "SELECT * FROM clientes WHERE deleted = 0";
    if (!empty($search)) { // Opening brace for search check
        $sql = "SELECT * FROM clientes WHERE nombres_y_apellidos LIKE ? AND deleted = 0";
        $stmt = $conn->prepare($sql);
        $search_param = "%$search%";
        $stmt->bind_param("s", $search_param);
        $stmt->execute();
        $result = $stmt->get_result();
    } else { // Opening brace for no search
        $result = $conn->query($sql);
    } // Closing brace for no search
    
    $numero_reporte = obtener_siguiente_numero($archivo_numeracion);
    $fecha_actual = date("d-m-Y");
    $hora_actual = date("H:i");
    $codigo_reporte = "$numero_reporte-$fecha_actual-$hora_actual-$numero_reporte";
    
    $pdf = new PDF('P', 'mm', 'A4');
    $pdf->AddPage();
    $pdf->AliasNbPages();
    
    $pdf->SetFont('Arial', 'B', 14);
    $pdf->SetTextColor(230, 126, 34);
    $pdf->SetFillColor(255, 248, 220);
    $pdf->Rect(15, $pdf->GetY(), 180, 12, 'F');
    $pdf->Cell(180, 12, 'CÓDIGO DEL REPORTE: ' . $codigo_reporte, 0, 1, 'C');
    
    $pdf->Ln(8);
    
    $usuario_generado = isset($_SESSION['nombre_usu']) ? $_SESSION['nombre_usu'] : 'Desconocido';
    
    $pdf->CreateInfoBox('FECHA DE GENERACIÓN', date("d/m/Y H:i:s"), 15, $pdf->GetY(), 85, 15);
    $pdf->CreateInfoBox('GENERADO POR', $usuario_generado, 110, $pdf->GetY() - 15, 85, 15);
    
    $pdf->Ln(20);
    
    $pdf->CreateSectionTitle('LISTADO DE CLIENTES REGISTRADOS');
    
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->SetFillColor(41, 84, 144);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->SetDrawColor(255, 255, 255);
    $pdf->SetLineWidth(0.3);
    
    $pdf->Cell(20, 12, '#', 1, 0, 'C', true);
    $pdf->Cell(50, 12, 'NOMBRE COMPLETO', 1, 0, 'C', true);
    $pdf->Cell(55, 12, 'DIRECCIÓN', 1, 0, 'C', true);
    $pdf->Cell(30, 12, 'TELÉFONO', 1, 0, 'C', true);
    $pdf->Cell(25, 12, 'NIT', 1, 1, 'C', true);
    
    $pdf->SetFont('Arial', '', 9);
    $pdf->SetTextColor(52, 73, 94);
    $pdf->SetDrawColor(189, 195, 199);
    
    $row_num = 0;
    while ($row = $result->fetch_assoc()) { // Opening brace for while loop
        $row_num++;
        
        if ($row_num % 2) { // Opening brace for row color
            $pdf->SetFillColor(250, 252, 255);
        } else { // Opening brace for alternate row color
            $pdf->SetFillColor(255, 255, 255);
        } // Closing brace for alternate row color
        
        if ($row_num % 5 == 0) { // Opening brace for special row color
            $pdf->SetFillColor(255, 248, 220);
        } // Closing brace for special row color
        
        $pdf->Cell(20, 10, $row_num, 1, 0, 'C', true);
        $pdf->Cell(50, 10, substr($row['nombres_y_apellidos'], 0, 22), 1, 0, 'L', true);
        $pdf->Cell(55, 10, substr($row['direccion'], 0, 25), 1, 0, 'L', true);
        $pdf->Cell(30, 10, $row['telefono'], 1, 0, 'C', true);
        $pdf->Cell(25, 10, $row['NIT'], 1, 1, 'C', true);
        
        if ($pdf->GetY() > 250) { // Opening brace for page break
            $pdf->AddPage();
            
            $pdf->SetFont('Arial', 'B', 10);
            $pdf->SetFillColor(41, 84, 144);
            $pdf->SetTextColor(255, 255, 255);
            $pdf->SetDrawColor(255, 255, 255);
            $pdf->SetLineWidth(0.3);
            
            $pdf->Cell(20, 12, '#', 1, 0, 'C', true);
            $pdf->Cell(50, 12, 'NOMBRE COMPLETO', 1, 0, 'C', true);
            $pdf->Cell(55, 12, 'DIRECCIÓN', 1, 0, 'C', true);
            $pdf->Cell(30, 12, 'TELÉFONO', 1, 0, 'C', true);
            $pdf->Cell(25, 12, 'NIT', 1, 1, 'C', true);
            
            $pdf->SetFont('Arial', '', 9);
            $pdf->SetTextColor(52, 73, 94);
            $pdf->SetDrawColor(189, 195, 199);
        } // Closing brace for page break
    } // Closing brace for while loop
    
    $pdf->Ln(10);
    $total_clientes = $row_num;
    
    $pdf->SetFont('Arial', 'B', 11);
    $pdf->SetTextColor(41, 84, 144);
    $pdf->SetFillColor(236, 245, 255);
    $pdf->Rect(15, $pdf->GetY(), 180, 10, 'F');
    $pdf->Cell(180, 10, 'TOTAL DE CLIENTES REGISTRADOS: ' . $total_clientes, 0, 1, 'C');
    
    ob_end_clean();
    
    $pdf->Output('I', 'Reporte_Clientes_PIL_Andina_' . $codigo_reporte . '.pdf');
    
    if (isset($stmt)) { // Opening brace for stmt check
        $stmt->close();
    } // Closing brace for stmt check
    exit();
} // Closing brace for report generation
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Clientes - Pil Andina</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
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
                        'soft-red': '#fee2e2',
                        'text-soft-red': '#b91c1c',
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
        
        .scrollbar-thin::-webkit-scrollbar {
            width: 6px;
            height: 6px;
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
        
        .form-control {
            border-radius: 10px;
            padding: 10px;
            border: 1px solid #ccc;
            box-shadow: inset 0 0 8px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            border-color: var(--primary);
            box-shadow: inset 0 0 5px rgba(78, 107, 175, 0.4);
            outline: none;
        }
        
        .form-select {
            border-radius: 10px;
            padding: 10px;
            border: 1px solid #ccc;
            background-color: #fff;
        }
        
        .dark .form-control,
        .dark .form-select {
            background-color: #3a3b3c;
            color: #ccc;
            border-color: #4b5563;
        }
        
        .dark .form-control:focus,
        .dark .form-select:focus {
            border-color: var(--primary-light);
            box-shadow: inset 0 0 5px rgba(134, 172, 212, 0.4);
        }
        
        .mensaje {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1000;
            padding: 15px 25px;
            border-radius: 8px;
            color: white;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            animation: slideIn 0.5s, fadeOut 0.5s 2.5s forwards;
        }
        
        @keyframes slideIn {
            from { transform: translateX(100%); }
            to { transform: translateX(0); }
        }
        
        @keyframes fadeOut {
            from { opacity: 1; }
            to { opacity: 0; }
        }
        
        .mensaje-success {
            background-color: #48BB78;
        }
        
        .mensaje-error {
            background-color: #F56565;
        }
    </style>
</head>
<body class="bg-gray-50 transition-colors duration-300 dark:bg-gray-900">
    <aside id="sidebar" class="fixed left-0 top-0 z-40 h-screen w-64 transition-transform duration-300 transform -translate-x-full lg:translate-x-0 sidebar-animation">
        <div class="h-full px-3 py-4 overflow-y-auto bg-white dark:bg-gray-800 shadow-2xl">
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
                
                <a href="../views/clientes.php" class="flex items-center p-3 text-gray-700 dark:text-gray-200 rounded-lg bg-primary text-white group card-hover">
                    <div class="w-8 h-8 bg-green-500 rounded-lg flex items-center justify-center shadow-lg transition-all duration-300">
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

            <div class="absolute bottom-4 left-3 right-3">
                <div class="flex items-center justify-between p-3 bg-gray-100 dark:bg-gray-700 rounded-lg">
                    <button id="darkModeToggle" class="flex items-center space-x-2 text-gray-600 dark:text-gray-300 hover:text-primary transition-colors duration-300">
                        <i id="theme-icon" class="fas fa-moon"></i>
                        <span class="text-sm font-medium theme-text">Modo Oscuro</span>
                    </button>
                    <a href="../logout.php" class="text-gray-500 hover:text-red-500 transition-colors duration-300">
                        <i class="fas fa-sign-out-alt"></i>
                    </a>
                </div>
            </div>
        </div>
    </aside>

    <div id="sidebarOverlay" class="fixed inset-0 z-30 bg-black bg-opacity-50 lg:hidden hidden"></div>

    <div class="lg:ml-64">
        <header class="bg-white dark:bg-gray-800 shadow-sm border-b border-gray-200 dark:border-gray-700 sticky top-0 z-20">
            <div class="flex items-center justify-between p-4">
                <div class="flex items-center space-x-4">
                    <button id="sidebarToggle" class="lg:hidden p-2 rounded-lg text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors duration-300">
                        <i class="fas fa-bars text-xl"></i>
                    </button>
                    <div>
                        <h1 class="text-2xl font-bold text-gray-800 dark:text-white">Gestión de Clientes</h1>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Bienvenido, <?php echo isset($_SESSION['nombre_usu']) ? htmlspecialchars($_SESSION['nombre_usu']) : 'Admin'; ?></p>
                    </div>
                </div>

                <div class="flex items-center space-x-4">
                    <div class="relative hidden md:block">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-search text-gray-400"></i>
                        </div>
                        <input type="text" placeholder="Buscar..." class="pl-10 pr-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-primary focus:border-transparent transition-all duration-300">
                    </div>

                    <button class="relative p-2 text-gray-600 dark:text-gray-300 hover:text-primary transition-colors duration-300">
                        <i class="fas fa-bell text-xl"></i>
                        <span class="absolute top-0 right-0 w-2 h-2 bg-red-500 rounded-full animate-pulse"></span>
                    </button>

                    <div class="flex items-center space-x-3">
                        <div class="w-10 h-10 bg-gradient-primary rounded-full flex items-center justify-center shadow-lg">
                            <i class="fas fa-user text-white text-sm"></i>
                        </div>
                        <div class="hidden md:block">
                            <p class="text-sm font-medium text-gray-700 dark:text-gray-200"><?php echo isset($_SESSION['nombre_usu']) ? htmlspecialchars($_SESSION['nombre_usu']) : 'Administrador'; ?></p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">admin@pilandina.com</p>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <main class="p-6 space-y-8">
            <?php if (isset($mensaje)): ?>
                <div class="mensaje mensaje-<?php echo $tipo_mensaje; ?>">
                    <i class="fas <?php echo $tipo_mensaje === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?> mr-2"></i>
                    <?php echo htmlspecialchars($mensaje); ?>
                </div>
            <?php endif; ?>

            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 animate-fade-in border border-gray-100 dark:border-gray-700 card-hover">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                        <i class="fas fa-file-alt mr-2 text-primary"></i>Información de Reportes
                    </h3>
                    <a href="?reporte=true" class="bg-green-600 hover:bg-green-700 text-white font-medium py-2 px-4 rounded-lg shadow transition duration-200 flex items-center">
                        <i class="fas fa-file-pdf mr-2"></i> Generar Reporte PDF
                    </a>
                </div>
                <p class="text-gray-600 dark:text-gray-400 mb-2">El próximo reporte generado tendrá un número consecutivo mayor al último utilizado.</p>
                <?php
                $ultimo_numero = file_exists($archivo_numeracion) ? (int)file_get_contents($archivo_numeracion) : 100;
                echo "<p class='font-medium text-gray-900 dark:text-gray-200'><span class='text-primary'>Último número usado:</span> <span class='font-bold'>$ultimo_numero</span></p>";
                ?>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 animate-slide-up border border-gray-100 dark:border-gray-700 card-hover">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-6">
                    <i class="fas fa-user-plus mr-2 text-primary"></i>Agregar Nuevo Cliente
                </h3>
                <form action="" method="POST">
                    <input type="hidden" name="accion" value="agregar">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <div>
                            <label for="nombre" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Nombre y Apellidos</label>
                            <input type="text" name="nombre" id="nombre" class="form-control w-full text-gray-900 dark:text-gray-100 dark:bg-gray-700" required>
                        </div>
                        <div>
                            <label for="direccion" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Dirección</label>
                            <input type="text" name="direccion" id="direccion" class="form-control w-full text-gray-900 dark:text-gray-100 dark:bg-gray-700" required>
                        </div>
                        <div>
                            <label for="telefono" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Teléfono</label>
                            <input type="text" name="telefono" id="telefono" class="form-control w-full text-gray-900 dark:text-gray-100 dark:bg-gray-700" required>
                        </div>
                        <div>
                            <label for="nit" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">NIT</label>
                            <input type="text" name="nit" id="nit" class="form-control w-full text-gray-900 dark:text-gray-100 dark:bg-gray-700" required>
                        </div>
                        <div class="md:col-span-2">
                            <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Email</label>
                            <input type="email" name="email" id="email" class="form-control w-full text-gray-900 dark:text-gray-100 dark:bg-gray-700" required>
                        </div>
                    </div>
                    <button type="submit" class="bg-primary hover:bg-primary-dark text-white font-medium py-2 px-6 rounded-lg shadow transition duration-200 flex items-center">
                        <i class="fas fa-save mr-2"></i> Guardar Cliente
                    </button>
                </form>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 animate-slide-up border border-gray-100 dark:border-gray-700 card-hover" style="animation-delay: 0.1s;">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                    <i class="fas fa-search mr-2 text-primary"></i>Buscar Clientes
                </h3>
                <form action="" method="GET" class="flex flex-col md:flex-row gap-4">
                    <input type="text" name="search" class="form-control flex-grow text-gray-900 dark:text-gray-100 dark:bg-gray-700" placeholder="Buscar cliente por nombre..." value="<?php echo htmlspecialchars($search); ?>">
                    <div class="flex gap-2">
                        <button type="submit" class="bg-primary hover:bg-primary-dark text-white font-medium py-2 px-4 rounded-lg shadow transition duration-200 flex items-center">
                            <i class="fas fa-search mr-2"></i> Buscar
                        </button>
                        <a href="?" class="bg-gray-500 hover:bg-gray-600 text-white font-medium py-2 px-4 rounded-lg shadow transition duration-200 flex items-center">
                            <i class="fas fa-sync-alt mr-2"></i> Resetear
                        </a>
                    </div>
                </form>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 animate-slide-up border border-gray-100 dark:border-gray-700 card-hover" style="animation-delay: 0.2s;">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                    <i class="fas fa-users mr-2 text-primary"></i>Clientes Activos
                </h3>
                <div class="overflow-x-auto scrollbar-thin">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-primary text-white">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">ID</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Nombre</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Dirección</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Teléfono</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">NIT</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Email</th>
                                <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            <?php while ($row = $result->fetch_assoc()): ?>
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition duration-150">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300"><?php echo $row['id_cliente']; ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-100"><?php echo htmlspecialchars($row['nombres_y_apellidos']); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300"><?php echo htmlspecialchars($row['direccion']); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300"><?php echo htmlspecialchars($row['telefono']); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300"><?php echo htmlspecialchars($row['NIT']); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300"><?php echo htmlspecialchars($row['email']); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <button class="text-yellow-600 hover:text-yellow-900 mr-3 edit-btn" 
                                                data-id="<?php echo $row['id_cliente']; ?>"
                                                data-nombre="<?php echo htmlspecialchars($row['nombres_y_apellidos']); ?>"
                                                data-direccion="<?php echo htmlspecialchars($row['direccion']); ?>"
                                                data-telefono="<?php echo htmlspecialchars($row['telefono']); ?>"
                                                data-nit="<?php echo htmlspecialchars($row['NIT']); ?>"
                                                data-email="<?php echo htmlspecialchars($row['email']); ?>">
                                            <i class="fas fa-edit mr-1"></i> Editar
                                        </button>
                                        <a href="?delete=<?php echo $row['id_cliente']; ?>" class="text-red-600 hover:text-red-900" onclick="return confirm('¿Estás seguro de marcar este cliente como eliminado?')">
                                            <i class="fas fa-trash-alt mr-1"></i> Eliminar
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 animate-slide-up border border-gray-100 dark:border-gray-700 card-hover" style="animation-delay: 0.3s;">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                    <i class="fas fa-trash-alt mr-2 text-text-soft-red"></i>Clientes Eliminados
                </h3>
                <div class="overflow-x-auto scrollbar-thin">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-soft-red text-text-soft-red">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">ID</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Nombre</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Dirección</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Teléfono</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">NIT</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Email</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Eliminado el</th>
                                <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            <?php while ($row = $result_eliminados->fetch_assoc()): ?>
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition duration-150">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300"><?php echo $row['id_cliente']; ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-100"><?php echo htmlspecialchars($row['nombres_y_apellidos']); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300"><?php echo htmlspecialchars($row['direccion']); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300"><?php echo htmlspecialchars($row['telefono']); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300"><?php echo htmlspecialchars($row['NIT']); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300"><?php echo htmlspecialchars($row['email']); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300"><?php echo $row['deleted_at']; ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <form action="" method="POST" class="inline">
                                            <input type="hidden" name="accion" value="restaurar">
                                            <input type="hidden" name="id_cliente" value="<?php echo $row['id_cliente']; ?>">
                                            <button type="submit" class="text-green-600 hover:text-green-900 mr-3" onclick="return confirm('¿Restaurar este cliente?')">
                                                <i class="fas fa-undo mr-1"></i> Restaurar
                                            </button>
                                        </form>
                                        <a href="?delete_permanent=<?php echo $row['id_cliente']; ?>" class="text-red-600 hover:text-red-900" onclick="return confirm('¿Estás seguro de eliminar PERMANENTEMENTE este cliente? Esta acción no se puede deshacer.')">
                                            <i class="fas fa-trash-alt mr-1"></i> Eliminar Definitivamente
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <div class="fixed inset-0 z-50 hidden bg-black bg-opacity-50" id="editModal">
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20">
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl transform transition-all max-w-lg w-full">
                <div class="bg-gradient-primary p-4 text-white flex items-center justify-between">
                    <h3 class="text-lg font-semibold">
                        <i class="fas fa-edit mr-2"></i>Editar Cliente
                    </h3>
                    <button onclick="closeModal()" class="text-white hover:text-gray-200">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
                <div class="p-6">
                    <form action="" method="POST">
                        <input type="hidden" name="accion" value="editar">
                        <input type="hidden" name="id_cliente" id="edit-id_cliente">
                        <div class="mb-4">
                            <label for="edit-nombre" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Nombre y Apellidos</label>
                            <input type="text" name="nombre" id="edit-nombre" class="form-control w-full text-gray-900 dark:text-gray-100 dark:bg-gray-700" required>
                        </div>
                        <div class="mb-4">
                            <label for="edit-direccion" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Dirección</label>
                            <input type="text" name="direccion" id="edit-direccion" class="form-control w-full text-gray-900 dark:text-gray-100 dark:bg-gray-700" required>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                            <div>
                                <label for="edit-telefono" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Teléfono</label>
                                <input type="text" name="telefono" id="edit-telefono" class="form-control w-full text-gray-900 dark:text-gray-100 dark:bg-gray-700" required>
                            </div>
                            <div>
                                <label for="edit-nit" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">NIT</label>
                                <input type="text" name="nit" id="edit-nit" class="form-control w-full text-gray-900 dark:text-gray-100 dark:bg-gray-700" required>
                            </div>
                        </div>
                        <div class="mb-6">
                            <label for="edit-email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Email</label>
                            <input type="email" name="email" id="edit-email" class="form-control w-full text-gray-900 dark:text-gray-100 dark:bg-gray-700" required>
                        </div>
                        <div class="flex justify-end gap-4">
                            <button type="submit" class="bg-primary hover:bg-primary-dark text-white font-medium py-2 px-4 rounded-lg shadow transition duration-200 flex items-center">
                                <i class="fas fa-save mr-2"></i> Guardar Cambios
                            </button>
                            <button type="button" onclick="closeModal()" class="bg-gray-600 hover:bg-gray-700 text-white font-medium py-2 px-4 rounded-lg shadow transition duration-200 flex items-center">
                                <i class="fas fa-times mr-2"></i> Cancelar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

<script>
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

    function openModal() {
        document.getElementById('editModal').classList.remove('hidden');
    }

    function closeModal() {
        document.getElementById('editModal').classList.add('hidden');
    }

    document.querySelectorAll('.edit-btn').forEach(button => {
        button.addEventListener('click', function() {
            document.getElementById('edit-id_cliente').value = this.getAttribute('data-id');
            document.getElementById('edit-nombre').value = this.getAttribute('data-nombre');
            document.getElementById('edit-direccion').value = this.getAttribute('data-direccion');
            document.getElementById('edit-telefono').value = this.getAttribute('data-telefono');
            document.getElementById('edit-nit').value = this.getAttribute('data-nit');
            document.getElementById('edit-email').value = this.getAttribute('data-email');
            openModal();
        });
    });

    document.getElementById('editModal').addEventListener('click', function(e) {
        if (e.target === this) closeModal();
    });

    setTimeout(() => {
        document.querySelectorAll('.mensaje').forEach(mensaje => mensaje.style.display = 'none');
    }, 3000);
</script>
</body>
</html>
<?php
require_once "fpdf/fpdf.php";
require_once "../../includes/conexion.php";

if (!isset($_GET['id_pago'])) {
    die("ID de pago no proporcionado.");
}

$id_pago = $_GET['id_pago'];

// Configurar la codificación para evitar problemas con acentos
$pdo->exec("SET NAMES utf8");

// Obtener datos del pago
$stmt = $pdo->prepare("
    SELECT 
        p.id, 
        p.monto, 
        p.fecha_pago, 
        p.metodo_pago, 
        c.nombre AS cliente, 
        c.documento,
        v.placa, 
        r.hora_entrada, 
        r.hora_salida, 
        l.numero_lugar, 
        t.tipo AS tipo_tarifa, 
        t.precio AS tarifa_precio
    FROM pagos p
    JOIN clientes c ON p.id_cliente = c.id
    JOIN registros r ON p.id_registro = r.id
    JOIN vehiculos v ON r.id_vehiculo = v.id
    JOIN lugares l ON r.id_lugar = l.id
    JOIN tarifas t ON r.id_tarifa = t.id
    WHERE p.id = ?
");

$stmt->execute([$id_pago]);
$pago = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$pago) {
    die("Pago no encontrado.");
}

// Crear PDF
$pdf = new FPDF();
$pdf->AddPage();
$pdf->SetFont('Arial', 'B', 14);
$pdf->Cell(0, 10, utf8_decode('Factura de Parqueadero'), 0, 1, 'C');
$pdf->Ln(5);

// Información del cliente
$pdf->SetFont('Arial', '', 11);
$pdf->Cell(95, 8, utf8_decode("Cliente: {$pago['cliente']}"), 0, 0);
$pdf->Cell(95, 8, utf8_decode("Documento: {$pago['documento']}"), 0, 1);
$pdf->Cell(95, 8, utf8_decode("Fecha de Pago: {$pago['fecha_pago']}"), 0, 1);
$pdf->Ln(5);

// Detalle del vehículo y lugar
$pdf->SetFont('Arial', 'B', 11);
$pdf->Cell(45, 8, utf8_decode('Placa'), 1);
$pdf->Cell(35, 8, utf8_decode('Lugar'), 1);
$pdf->Cell(55, 8, utf8_decode('Hora Entrada'), 1);
$pdf->Cell(55, 8, utf8_decode('Hora Salida'), 1);
$pdf->Ln();

$pdf->SetFont('Arial', '', 11);
$pdf->Cell(45, 8, utf8_decode($pago['placa']), 1);
$pdf->Cell(35, 8, $pago['numero_lugar'], 1, 0, 'C');
$pdf->Cell(55, 8, $pago['hora_entrada'], 1, 0, 'C');
$pdf->Cell(55, 8, $pago['hora_salida'] ?? 'N/A', 1, 0, 'C');
$pdf->Ln(10);

// Información del pago
$pdf->SetFont('Arial', 'B', 11);
$pdf->Cell(95, 8, utf8_decode("Método de Pago: " . ucfirst($pago['metodo_pago'])), 0, 0);
$pdf->Cell(95, 8, utf8_decode("Tarifa: {$pago['tipo_tarifa']} - $" . number_format($pago['tarifa_precio'], 2)), 0, 1);
$pdf->Ln(5);

// Total a pagar
$pdf->SetFont('Arial', 'B', 13);
$pdf->Cell(140, 10, utf8_decode('Total a Pagar'), 1);
$pdf->Cell(50, 10, '$' . number_format($pago['monto'], 2), 1, 0, 'C');
$pdf->Ln(10);

// Generar PDF
$pdf->Output();
?>

<?php
require_once '../../conexion.php';
require_once 'fpdf/fpdf.php';

$pdf = new FPDF('P', 'mm', 'letter');
$pdf->AddPage();
$pdf->SetMargins(10, 10, 10); // Márgenes más pequeños para el nuevo tamaño
$pdf->SetTitle("Separados");
$pdf->SetFont('Arial', 'B', 12);

// Verificar si se ha proporcionado un ID de separado válido a través de la URL
if(isset($_GET['v']) && isset($_GET['cl'])) {
    $id = $_GET['v'];
    $idcliente = $_GET['cl'];

    $query_separado = mysqli_query($conexion, "SELECT nombre_cl, ced_cliente, dire_cliente, tele_cliente, nomb_separado, precio_separado, abono FROM separados WHERE id = $id");
    $separado = mysqli_fetch_assoc($query_separado);

    if ($separado) {
        // Restar el abono al precio_separado para obtener el valor restante
        $precio_restante = $separado['precio_separado'] - $separado['abono'];

        $pdf->Cell(195, 5, utf8_decode("DROGUERIA MI BUENA ESPERANZA"), 0, 1, 'C');
        $pdf->Image("../../assets/img/logo.jpeg", 170, 15, 30, 20, 'jpeg');

        // Resto del código para generar el PDF usando los datos de $separado

        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell(20, 5, utf8_decode("Teléfono: "), 0, 0, 'L');
        $pdf->SetFont('Arial', '', 10);
        $pdf->Cell(20, 5, utf8_decode("3113433423"), 0, 1, 'L');

        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell(20, 5, utf8_decode("Dirección: "), 0, 0, 'L');
        $pdf->SetFont('Arial', '', 10);
        $pdf->Cell(20, 5, utf8_decode("Ciudad Bolivar (Antioquia)"), 0, 1, 'L');

        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell(20, 5, "Correo: ", 0, 0, 'L');
        $pdf->SetFont('Arial', '', 10);
        $pdf->Cell(20, 5, utf8_decode("hilda-aguirre34j@hotmail.com"), 0, 1, 'L');

        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell(20, 5, "Nit: ", 0, 0, 'L');
        $pdf->SetFont('Arial', '', 10);
        $pdf->Cell(195, 5, "35603096-2", 0, 1, 'L');

        $pdf->Ln();

        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(0, 10, utf8_decode("**NO RESPONSABLE DE IVA**"), 0, 1, 'C');
        
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->SetFillColor(0, 0, 0);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->Cell(196, 5, "Datos del cliente", 1, 1, 'C', 1);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->Cell(85, 5, utf8_decode('Nombre'), 0, 0, 'L');
        $pdf->Cell(35, 5, utf8_decode('Teléfono'), 0, 0, 'L');
        $pdf->Cell(45, 5, utf8_decode('Dirección'), 0, 0, 'L');
        $pdf->Cell(45, 5, utf8_decode('Cédula'), 0, 1, 'L');
        
        $pdf->SetFont('Arial', '', 10);
        $pdf->Cell(85, 5, utf8_decode($separado['nombre_cl']), 0, 0, 'L');
        $pdf->Cell(35, 5, utf8_decode($separado['tele_cliente']), 0, 0, 'L');
        $pdf->Cell(45, 5, utf8_decode($separado['dire_cliente']), 0, 0, 'L');
        $pdf->Cell(45, 5, utf8_decode($separado['ced_cliente']), 0, 1, 'L');
        
        $pdf->Ln(3);

        $pdf->SetFont('Arial', 'B', 10);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->Cell(196, 5, "Detalle de Producto", 1, 1, 'C', 1);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->Cell(14, 5, utf8_decode('N°'), 0, 0, 'L');
        $pdf->Cell(90, 5, utf8_decode('Descripción'), 0, 0, 'L');
        $pdf->Cell(25, 5, 'Cantidad', 0, 0, 'L');
        $pdf->Cell(32, 5, 'Precio pendiente', 0, 0, 'L');
        $pdf->Cell(35, 5, 'Abono.', 0, 1, 'L');

        $pdf->Cell(14, 5, '1', 0, 0, 'L');
        $pdf->Cell(90, 5, utf8_decode($separado['nomb_separado']), 0, 0, 'L');
        $pdf->Cell(25, 5, '1', 0, 0, 'L');
        $pdf->Cell(32, 5, number_format($precio_restante, 2, ',', '.'), 0, 0, 'L'); // Precio pendiente con COP
        $pdf->Cell(35, 5, number_format($separado['abono'], 2, ',', '.'), 0, 1, 'L'); // Abono con COP
        
        $pdf->Ln(5);
        
        $pdf->SetLineWidth(0.5);
        $pdf->Line(10, $pdf->GetY(), 200, $pdf->GetY());


        $pdf->Ln(5);

        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(0, 10, utf8_decode("¡Gracias por su compra!"), 0, 1, 'C');

        $pdf->Ln();

        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell(0, 5, utf8_decode("Fecha y hora de generación de la factura: " . date('Y-m-d H:i:s')), 0, 1, 'C');

        $pdf->Output("abono_" . $separado['nombre_cl'] . ".pdf", "I");
    }
}

$pdf->Output("abonos.pdf", "I");
?>
<?php
header('Content-Type: application/json');
require_once '../includes/conexion.php';

if (isset($_GET['id_pago'])) {
    $id_pago = intval($_GET['id_pago']);
    $sql = "SELECT p.monto, p.metodo_pago, p.fecha_pago, c.nombre AS cliente, v.placa
            FROM pagos p
            JOIN clientes c ON p.id_cliente = c.id
            JOIN registros r ON p.id_registro = r.id
            JOIN vehiculos v ON r.id_vehiculo = v.id
            WHERE p.id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id_pago]);
    $pago = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($pago) {
        echo json_encode(['success' => true, 'cliente' => $pago['cliente'], 'placa' => $pago['placa'], 'monto' => $pago['monto'], 'metodo_pago' => $pago['metodo_pago'], 'fecha_pago' => $pago['fecha_pago']]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Pago no encontrado']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'ID de pago no proporcionado']);
}
?>

<?php
header('Content-Type: application/json');
require_once '../includes/conexion.php';

if (isset($_GET['cliente_id'])) {
    $cliente_id = intval($_GET['cliente_id']);

    $sql = "SELECT v.id, v.placa, v.marca, v.modelo, v.color, v.tipo, l.numero_lugar AS lugar
            FROM vehiculos v
            JOIN registros r ON v.id = r.id_vehiculo
            JOIN lugares l ON r.id_lugar = l.id
            WHERE v.id_cliente = ?";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$cliente_id]);
    $vehiculos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if ($vehiculos) {
        echo json_encode(['success' => true, 'vehiculos' => $vehiculos]);
    } else {
        echo json_encode(['success' => false, 'error' => 'No hay vehículos registrados para este cliente.']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'ID de cliente no proporcionado.']);
}
?>

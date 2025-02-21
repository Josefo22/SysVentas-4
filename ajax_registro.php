<?php
header('Content-Type: application/json');
require_once './includes/conexion.php';

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);

    $sql = "SELECT r.id, v.placa, l.numero_lugar AS lugar, r.hora_entrada, r.hora_salida, r.estado, r.total
            FROM registros r
            JOIN vehiculos v ON r.id_vehiculo = v.id
            JOIN lugares l ON r.id_lugar = l.id
            WHERE r.id = ?";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id]);
    $registro = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($registro) {
        echo json_encode(['success' => true, 'registro' => $registro]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Registro no encontrado.']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'ID no proporcionado.']);
}
?>

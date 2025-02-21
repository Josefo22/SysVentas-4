<?php
session_start();
require_once 'includes/conexion.php';

if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit();
}

// Estadísticas generales
$sql_lugares = "SELECT 
    COUNT(*) as total_lugares,
    SUM(CASE WHEN estado = 'disponible' THEN 1 ELSE 0 END) as disponibles,
    SUM(CASE WHEN estado = 'ocupado' THEN 1 ELSE 0 END) as ocupados
FROM lugares";
$stmt = $pdo->query($sql_lugares);
$lugares = $stmt->fetch();

// Vehículos actuales
$sql_vehiculos = "SELECT COUNT(*) as total FROM registros WHERE estado = 'activo'";
$stmt = $pdo->query($sql_vehiculos);
$vehiculos_actuales = $stmt->fetch()['total'];

// Ingresos del día
$sql_ingresos = "SELECT SUM(monto) as total FROM pagos WHERE DATE(fecha_pago) = CURRENT_DATE()";
$stmt = $pdo->query($sql_ingresos);
$ingresos_hoy = $stmt->fetch()['total'] ?? 0;

// Últimos registros de entrada
$sql_ultimos = "SELECT 
    r.id, r.hora_entrada, r.estado,
    v.placa, v.tipo,
    c.nombre as cliente,
    l.numero_lugar,
    t.tipo as tipo_tarifa
FROM registros r
JOIN vehiculos v ON r.id_vehiculo = v.id
JOIN clientes c ON v.id_cliente = c.id
JOIN lugares l ON r.id_lugar = l.id
JOIN tarifas t ON r.id_tarifa = t.id
ORDER BY r.hora_entrada DESC
LIMIT 5";
$stmt = $pdo->query($sql_ultimos);
$ultimos_registros = $stmt->fetchAll();

// Próximos vencimientos de mensualidades
$sql_vencimientos = "SELECT 
    c.nombre as cliente,
    v.placa,
    r.hora_entrada,
    DATE_ADD(r.hora_entrada, INTERVAL 1 MONTH) as fecha_vencimiento
FROM registros r
JOIN vehiculos v ON r.id_vehiculo = v.id
JOIN clientes c ON v.id_cliente = c.id
JOIN tarifas t ON r.id_tarifa = t.id
WHERE t.tipo = 'mensual' 
AND r.estado = 'activo'
AND DATE_ADD(r.hora_entrada, INTERVAL 1 MONTH) <= DATE_ADD(CURRENT_DATE(), INTERVAL 7 DAY)
ORDER BY fecha_vencimiento ASC
LIMIT 5";
$stmt = $pdo->query($sql_vencimientos);
$proximos_vencimientos = $stmt->fetchAll();
?>


<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Sistema de Parqueadero</title>
    <style>
           /* Hace que el contenido ocupe toda la pantalla */
   html, body {
    height: 100%;
    margin: 0;
    display: flex;
    flex-direction: column;
}
.content {
    flex: 1;
}
    </style>
    <link rel="stylesheet" href="..\SysParqueo\css\Style.css">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
   
</head>
<body>
    <div class="container-fluid">
        <div class="row">
        <?php include '../SysParqueo\modulos\Navbar.php'; ?>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 ms-sm-auto px-md-4 py-4">
                <!-- Header -->
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Dashboard</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="window.location.href='registro.php'">
                                <i class="fas fa-plus me-2"></i>Nuevo Registro
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Statistics Cards -->
                <div class="row g-4 mb-4">
                    <div class="col-12 col-sm-6 col-xl-3">
                        <div class="stat-card card bg-primary text-white">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="flex-grow-1">
                                        <h5 class="mb-0">Lugares Disponibles</h5>
                                        <h2 class="mb-0"><?php echo $lugares['disponibles']; ?>/<?php echo $lugares['total_lugares']; ?></h2>
                                    </div>
                                    <i class="fas fa-parking stat-icon"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-sm-6 col-xl-3">
                        <div class="stat-card card bg-success text-white">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="flex-grow-1">
                                        <h5 class="mb-0">Vehículos Actuales</h5>
                                        <h2 class="mb-0"><?php echo $vehiculos_actuales; ?></h2>
                                    </div>
                                    <i class="fas fa-car stat-icon"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-sm-6 col-xl-3">
                        <div class="stat-card card bg-info text-white">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="flex-grow-1">
                                        <h5 class="mb-0">Ingresos Hoy</h5>
                                        <h2 class="mb-0">$<?php echo number_format($ingresos_hoy, 3); ?></h2>
                                    </div>
                                    <i class="fas fa-money-bill-wave stat-icon"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-sm-6 col-xl-3">
                        <div class="stat-card card bg-warning text-white">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="flex-grow-1">
                                        <h5 class="mb-0">Ocupación</h5>
                                        <h2 class="mb-0">
    <?php 
    if ($lugares['total_lugares'] > 0) {
        echo round(($lugares['ocupados'] / $lugares['total_lugares']) * 100);
    } else {
        echo "0";
    }
    ?>%
</h2>
                                    </div>
                                    <i class="fas fa-chart-pie stat-icon"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Records & Expirations -->
                <div class="row">
                    <!-- Recent Records -->
                    <div class="col-12 col-xl-8 mb-4">
                        <div class="table-container">
                            <h5 class="mb-4">Últimos Registros</h5>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Placa</th>
                                            <th>Cliente</th>
                                            <th>Lugar</th>
                                            <th>Entrada</th>
                                            <th>Tipo</th>
                                            <th>Estado</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($ultimos_registros as $registro): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($registro['placa']); ?></td>
                                            <td><?php echo htmlspecialchars($registro['cliente']); ?></td>
                                            <td><?php echo htmlspecialchars($registro['numero_lugar']); ?></td>
                                            <td><?php echo date('d/m/Y H:i', strtotime($registro['hora_entrada'])); ?></td>
                                            <td>
                                                <span class="badge bg-<?php echo $registro['tipo_tarifa'] == 'mensual' ? 'success' : 'primary'; ?>">
                                                    <?php echo ucfirst($registro['tipo_tarifa']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?php echo $registro['estado'] == 'activo' ? 'success' : 'secondary'; ?>">
                                                    <?php echo ucfirst($registro['estado']); ?>
                                                </span>
                                            </td>
                                            <td>
                                            <button class="btn btn-sm btn-outline-primary btn-action" 
        onclick="verRegistro(<?php echo $registro['id']; ?>)">
    <i class="fas fa-eye"></i>
</button>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
<!-- Modal -->
<div class="modal fade" id="modalRegistro" tabindex="-1" aria-labelledby="modalRegistroLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalRegistroLabel">Detalles del Registro</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <p><strong>ID Registro:</strong> <span id="registroId"></span></p>
                <p><strong>Vehículo (Placa):</strong> <span id="registroVehiculo"></span></p>
                <p><strong>Lugar:</strong> <span id="registroLugar"></span></p>
                <p><strong>Hora Entrada:</strong> <span id="registroEntrada"></span></p>
                <p><strong>Hora Salida:</strong> <span id="registroSalida"></span></p>
                <p><strong>Estado:</strong> <span id="registroEstado"></span></p>
                <p><strong>Total a Pagar:</strong> <span id="registroTotal"></span></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>
                    <!-- Upcoming Expirations -->
                    <div class="col-12 col-xl-4 mb-4">
                        <div class="table-container">
                            <h5 class="mb-4">Próximos Vencimientos</h5>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Cliente</th>
                                            <th>Placa</th>
                                            <th>Vence</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($proximos_vencimientos as $vencimiento): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($vencimiento['cliente']); ?></td>
                                            <td><?php echo htmlspecialchars($vencimiento['placa']); ?></td>
                                            <td>
                                                <?php 
                                                $dias_restantes = floor((strtotime($vencimiento['fecha_vencimiento']) - time()) / (60 * 60 * 24));
                                                $badge_class = $dias_restantes <= 3 ? 'danger' : ($dias_restantes <= 5 ? 'warning' : 'info');
                                                ?>
                                                <span class="badge bg-<?php echo $badge_class; ?>">
                                                    <?php echo $dias_restantes; ?> días
                                                </span>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php include '..\SysParqueo\modulos\Footer.php'; ?>
    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function verRegistro(id) {
    fetch(`ajax_registro.php?id=${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById("registroId").textContent = data.registro.id;
                document.getElementById("registroVehiculo").textContent = data.registro.placa;
                document.getElementById("registroLugar").textContent = data.registro.lugar;
                document.getElementById("registroEntrada").textContent = data.registro.hora_entrada;
                document.getElementById("registroSalida").textContent = data.registro.hora_salida || "En curso";
                document.getElementById("registroEstado").textContent = data.registro.estado;
                document.getElementById("registroTotal").textContent = `$${data.registro.total || '0.00'}`;
                var modal = new bootstrap.Modal(document.getElementById("modalRegistro"));
                modal.show();
            } else {
                alert(data.error);
            }
        })
        .catch(error => console.error("Error al obtener el registro:", error));
}

    </script>
</body>                 
</html>
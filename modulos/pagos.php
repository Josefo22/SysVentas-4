<?php
session_start();
require_once '../includes/conexion.php';

if (!isset($_SESSION['usuario_id'])) {
    header('Location: ../login.php');
    exit();
}

// Procesar nuevo pago
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'crear_pago') {
    try {
        $pdo->beginTransaction();

        // Insertar el pago
        $sql_pago = "INSERT INTO pagos (id_cliente, id_registro, monto, metodo_pago) 
                     VALUES (?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql_pago);
        $stmt->execute([
            $_POST['id_cliente'],
            $_POST['id_registro'],
            $_POST['monto'],
            $_POST['metodo_pago']
        ]);

        // Si es pago de salida, actualizar el registro y lugar
        if (isset($_POST['es_salida']) && $_POST['es_salida'] == '1') {
            // Actualizar registro
            $sql_registro = "UPDATE registros SET 
                           hora_salida = CURRENT_TIMESTAMP,
                           total = ?,
                           estado = 'finalizado'
                           WHERE id = ?";
            $stmt = $pdo->prepare($sql_registro);
            $stmt->execute([$_POST['monto'], $_POST['id_registro']]);

            // Liberar el lugar
            $sql_lugar = "UPDATE lugares SET estado = 'disponible' 
                         WHERE id = (SELECT id_lugar FROM registros WHERE id = ?)";
            $stmt = $pdo->prepare($sql_lugar);
            $stmt->execute([$_POST['id_registro']]);
        }

        $pdo->commit();
        $_SESSION['mensaje'] = "Pago registrado exitosamente";
        header('Location: pagos.php');
        exit();

    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Error al procesar el pago: " . $e->getMessage();
    }
}

// Obtener pagos con información relacionada
$sql_pagos = "SELECT 
    p.id,
    p.monto,
    p.fecha_pago,
    p.metodo_pago,
    c.nombre as cliente,
    c.documento,
    v.placa,
    r.hora_entrada,
    r.hora_salida,
    r.estado as estado_registro,
    t.tipo as tipo_tarifa
FROM pagos p
JOIN clientes c ON p.id_cliente = c.id
JOIN registros r ON p.id_registro = r.id
JOIN vehiculos v ON r.id_vehiculo = v.id
JOIN tarifas t ON r.id_tarifa = t.id
ORDER BY p.fecha_pago DESC
LIMIT 100";

$stmt = $pdo->query($sql_pagos);
$pagos = $stmt->fetchAll();

// Obtener registros activos para nuevo pago
$sql_registros_activos = "SELECT 
    r.id,
    r.hora_entrada,
    v.placa,
    c.id as id_cliente,
    c.nombre as cliente,
    t.tipo as tipo_tarifa,
    t.precio
FROM registros r
JOIN vehiculos v ON r.id_vehiculo = v.id
JOIN clientes c ON v.id_cliente = c.id
JOIN tarifas t ON r.id_tarifa = t.id
WHERE r.estado = 'activo'";

$stmt = $pdo->query($sql_registros_activos);
$registros_activos = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Pagos - Sistema de Parqueadero</title>
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
     <link rel="stylesheet" href="..\..\SysParqueo\css\Style.css">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>

    <div class="container-fluid">
        <div class="row">
        <?php include '../../SysParqueo/modulos/Navbar.php'; ?>
        <div class="col-md-9 col-lg-10 ms-sm-auto px-md-4 py-4">
                <!-- Header -->
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Gestión de Pagos</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#nuevoPagoModal">
                            <i class="fas fa-plus me-2"></i>Nuevo Pago
                        </button>
                    </div>
                </div>

                <?php if (isset($error)): ?>
                    <div class="alert alert-danger" role="alert">
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <?php if (isset($_SESSION['mensaje'])): ?>
                    <div class="alert alert-success" role="alert">
                        <?php 
                        echo $_SESSION['mensaje'];
                        unset($_SESSION['mensaje']);
                        ?>
                    </div>
                <?php endif; ?>

                <!-- Tabla de Pagos -->
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Cliente</th>
                                        <th>Documento</th>
                                        <th>Placa</th>
                                        <th>Monto</th>
                                        <th>Método</th>
                                        <th>Fecha</th>
                                        <th>Tipo</th>
                                        <th>Estado</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($pagos as $pago): ?>
                                    <tr>
                                        <td><?php echo $pago['id']; ?></td>
                                        <td><?php echo htmlspecialchars($pago['cliente']); ?></td>
                                        <td><?php echo htmlspecialchars($pago['documento']); ?></td>
                                        <td><?php echo htmlspecialchars($pago['placa']); ?></td>
                                        <td>$<?php echo number_format($pago['monto'], 2); ?></td>
                                        <td><?php echo ucfirst($pago['metodo_pago']); ?></td>
                                        <td><?php echo date('d/m/Y H:i', strtotime($pago['fecha_pago'])); ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo $pago['tipo_tarifa'] == 'mensual' ? 'success' : 'primary'; ?>">
                                                <?php echo ucfirst($pago['tipo_tarifa']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?php echo $pago['estado_registro'] == 'activo' ? 'success' : 'secondary'; ?>">
                                                <?php echo ucfirst($pago['estado_registro']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-info" onclick="verDetallesPago(<?php echo $pago['id']; ?>)">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-primary" onclick="imprimirRecibo(<?php echo $pago['id']; ?>)">
                                                <i class="fas fa-print"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </>
        </div>
    </div>
    </div>
   <!-- Modal para Detalles del Pago -->
   <div class="modal fade" id="modalDetallesPago" tabindex="-1" aria-labelledby="modalDetallesPagoLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalDetallesPagoLabel">Detalles del Pago</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body" id="detallesPagoContent">
                Cargando detalles...
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>
    <!-- Modal Nuevo Pago -->
    <div class="modal fade" id="nuevoPagoModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Nuevo Pago</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form method="POST" id="formPago" class="needs-validation" novalidate>
                        <input type="hidden" name="action" value="crear_pago">
                        
                        <div class="mb-3">
                            <label for="id_registro" class="form-label">Registro</label>
                            <select class="form-select" id="id_registro" name="id_registro" required>
                                <option value="">Seleccione...</option>
                                <?php foreach ($registros_activos as $registro): ?>
                                    <option value="<?php echo $registro['id']; ?>" 
                                            data-cliente="<?php echo $registro['id_cliente']; ?>"
                                            data-tarifa="<?php echo $registro['precio']; ?>">
                                        <?php echo $registro['placa'] . ' - ' . $registro['cliente'] . 
                                                ' (' . ucfirst($registro['tipo_tarifa']) . ')'; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <input type="hidden" id="id_cliente" name="id_cliente">

                        <div class="mb-3">
                            <label for="monto" class="form-label">Monto</label>
                            <input type="number" step="0.01" class="form-control" id="monto" name="monto" required>
                        </div>

                        <div class="mb-3">
                            <label for="metodo_pago" class="form-label">Método de Pago</label>
                            <select class="form-select" id="metodo_pago" name="metodo_pago" required>
                                <option value="">Seleccione...</option>
                                <option value="efectivo">Efectivo</option>
                                <option value="tarjeta">Tarjeta</option>
                                <option value="transferencia">Transferencia</option>
                            </select>
                        </div>

                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="es_salida" name="es_salida" value="1">
                            <label class="form-check-label" for="es_salida">Registrar Salida</label>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" form="formPago" class="btn btn-primary">Guardar Pago</button>
                </div>
            </div>
        </div>
    </div>
 

    <?php include 'Footer.php'; ?>
 <!-- Bootstrap Bundle with Popper -->
 <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Actualizar cliente y monto al seleccionar registro
        document.getElementById('id_registro').addEventListener('change', function() {
            const option = this.options[this.selectedIndex];
            document.getElementById('id_cliente').value = option.dataset.cliente;
            document.getElementById('monto').value = option.dataset.tarifa;
        });

        // Validación del formulario
        (function () {
            'use strict'
            var forms = document.querySelectorAll('.needs-validation')
            Array.prototype.slice.call(forms)
                .forEach(function (form) {
                    form.addEventListener('submit', function (event) {
                        if (!form.checkValidity()) {
                            event.preventDefault()
                            event.stopPropagation()
                        }
                        form.classList.add('was-validated')
                    }, false)
                })
        })()

        function verDetallesPago(id) {
            fetch('../includes/getDetallesPago.php?id_pago=' + id)

        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('detallesPagoContent').innerHTML = `
                    <p><strong>Cliente:</strong> ${data.cliente}</p>
                    <p><strong>Vehículo (Placa):</strong> ${data.placa}</p>
                    <p><strong>Monto Pagado:</strong> $${parseFloat(data.monto).toFixed(2)}</p>
                    <p><strong>Método de Pago:</strong> ${data.metodo_pago}</p>
                    <p><strong>Fecha de Pago:</strong> ${data.fecha_pago}</p>
                `;
            } else {
                document.getElementById('detallesPagoContent').innerHTML = `<p>No se encontraron detalles para este pago.</p>`;
            }
            new bootstrap.Modal(document.getElementById('modalDetallesPago')).show();
        })
        .catch(error => {
            console.error('Error al obtener detalles del pago:', error);
            document.getElementById('detallesPagoContent').innerHTML = `<p>Error al cargar los detalles.</p>`;
        });
}

        // Update the imprimirRecibo function in pagos.php
function imprimirRecibo(id) {
    window.open('../assets/pdf/generar.php?id_pago=' + id, '_blank');
}
    </script>
</body>
</html>
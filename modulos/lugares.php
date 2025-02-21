<?php
session_start();
require_once '../includes/conexion.php';

if (!isset($_SESSION['usuario_id'])) {
    header('Location: ../login.php');
    exit();
}

// Mensaje de éxito/error
$mensaje = '';
$tipo_mensaje = '';

// Procesar eliminación
if (isset($_GET['eliminar'])) {
    $id = (int)$_GET['eliminar'];
    
    // Verificar si el lugar está ocupado
    $sql_verificar = "SELECT estado FROM lugares WHERE id = :id";
    $stmt = $pdo->prepare($sql_verificar);
    $stmt->execute(['id' => $id]);
    $lugar = $stmt->fetch();
    
    if ($lugar && $lugar['estado'] !== 'ocupado') {
        try {
            $sql_eliminar = "DELETE FROM lugares WHERE id = :id";
            $stmt = $pdo->prepare($sql_eliminar);
            $stmt->execute(['id' => $id]);
            
            $mensaje = "Lugar eliminado correctamente";
            $tipo_mensaje = "success";
        } catch (PDOException $e) {
            $mensaje = "Error al eliminar: " . $e->getMessage();
            $tipo_mensaje = "danger";
        }
    } else {
        $mensaje = "No se puede eliminar un lugar ocupado";
        $tipo_mensaje = "warning";
    }
}

// Procesar formulario de creación/edición
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : null;
    $numero_lugar = $_POST['numero_lugar'];
    $estado = $_POST['estado'];
    
    // Validar que el número de lugar no exista ya (excepto si es edición)
    $sql_verificar = "SELECT id FROM lugares WHERE numero_lugar = :numero_lugar AND id != :id";
    $stmt = $pdo->prepare($sql_verificar);
    $stmt->execute([
        'numero_lugar' => $numero_lugar,
        'id' => $id ?? 0
    ]);
    
    if ($stmt->rowCount() > 0) {
        $mensaje = "El número de lugar ya existe";
        $tipo_mensaje = "danger";
    } else {
        try {
            if ($id) {
                // Actualizar lugar existente
                $sql = "UPDATE lugares SET numero_lugar = :numero_lugar, estado = :estado WHERE id = :id";
                $params = [
                    'numero_lugar' => $numero_lugar,
                    'estado' => $estado,
                    'id' => $id
                ];
                $mensaje = "Lugar actualizado correctamente";
            } else {
                // Crear nuevo lugar
                $sql = "INSERT INTO lugares (numero_lugar, estado) VALUES (:numero_lugar, :estado)";
                $params = [
                    'numero_lugar' => $numero_lugar,
                    'estado' => $estado
                ];
                $mensaje = "Lugar creado correctamente";
            }
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $tipo_mensaje = "success";
            
        } catch (PDOException $e) {
            $mensaje = "Error al procesar: " . $e->getMessage();
            $tipo_mensaje = "danger";
        }
    }
}

// Obtener datos para editar
$lugar_editar = null;
if (isset($_GET['editar'])) {
    $id = (int)$_GET['editar'];
    $sql = "SELECT * FROM lugares WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['id' => $id]);
    $lugar_editar = $stmt->fetch();
}

// Obtener estadísticas
$sql_stats = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN estado = 'disponible' THEN 1 ELSE 0 END) as disponibles,
    SUM(CASE WHEN estado = 'ocupado' THEN 1 ELSE 0 END) as ocupados,
    SUM(CASE WHEN estado = 'reservado' THEN 1 ELSE 0 END) as reservados
FROM lugares";
$stmt = $pdo->query($sql_stats);
$stats = $stmt->fetch();

// Obtener todos los lugares para la tabla
$sql_lugares = "SELECT * FROM lugares ORDER BY numero_lugar";
$stmt = $pdo->query($sql_lugares);
$lugares = $stmt->fetchAll();

// Contar vehículos por tipo
$sql_vehiculos = "SELECT 
    COUNT(CASE WHEN v.tipo = 'carro' THEN 1 END) as total_carros,
    COUNT(CASE WHEN v.tipo = 'moto' THEN 1 END) as total_motos
FROM registros r
JOIN vehiculos v ON r.id_vehiculo = v.id
WHERE r.estado = 'activo'";
$stmt = $pdo->query($sql_vehiculos);
$vehiculos = $stmt->fetch();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Lugares - Sistema de Parqueadero</title>
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

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 ms-sm-auto px-md-4 py-4">
                <!-- Header -->
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Gestión de Lugares</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#modalLugar">
                            <i class="fas fa-plus me-2"></i>Nuevo Lugar
                        </button>
                    </div>
                </div>

                <?php if ($mensaje): ?>
                <div class="alert alert-<?php echo $tipo_mensaje; ?> alert-dismissible fade show" role="alert">
                    <?php echo $mensaje; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php endif; ?>

                <!-- Stats Cards -->
                <div class="row g-4 mb-4">
                    <div class="col-12 col-sm-6 col-xl-3">
                        <div class="card bg-primary text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="card-title mb-0">Total Lugares</h6>
                                        <h2 class="mt-2 mb-0"><?php echo $stats['total']; ?></h2>
                                    </div>
                                    <div class="icon-lg rounded-circle bg-primary-light">
                                        <i class="fas fa-parking fa-lg"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-sm-6 col-xl-3">
                        <div class="card bg-success text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="card-title mb-0">Disponibles</h6>
                                        <h2 class="mt-2 mb-0"><?php echo $stats['disponibles']; ?></h2>
                                    </div>
                                    <div class="icon-lg rounded-circle bg-success-light">
                                        <i class="fas fa-check-circle fa-lg"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-sm-6 col-xl-3">
                        <div class="card bg-danger text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="card-title mb-0">Ocupados</h6>
                                        <h2 class="mt-2 mb-0"><?php echo $stats['ocupados']; ?></h2>
                                    </div>
                                    <div class="icon-lg rounded-circle bg-danger-light">
                                        <i class="fas fa-ban fa-lg"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-sm-6 col-xl-3">
                        <div class="card bg-info text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="card-title mb-0">Tasa Ocupación</h6>
                                        <h2 class="mt-2 mb-0">
                                            <?php 
                                            if ($stats['total'] > 0) {
                                                echo round(($stats['ocupados'] / $stats['total']) * 100) . '%';
                                            } else {
                                                echo "0%";
                                            }
                                            ?>
                                        </h2>
                                    </div>
                                    <div class="icon-lg rounded-circle bg-info-light">
                                        <i class="fas fa-chart-pie fa-lg"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Mapa Visual de Lugares -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Mapa de Lugares</h5>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-12">
                                <div class="parking-map">
                                    <?php foreach ($lugares as $lugar): ?>
                                    <div class="parking-spot parking-spot-<?php echo $lugar['estado']; ?>" 
                                          data-bs-toggle="tooltip" data-bs-placement="top" 
                                          title="Lugar #<?php echo $lugar['numero_lugar']; ?> - <?php echo ucfirst($lugar['estado']); ?>">
                                        <span class="parking-number"><?php echo $lugar['numero_lugar']; ?></span>
                                        <i class="fas <?php 
                                            echo $lugar['estado'] === 'disponible' ? 'fa-check' : 
                                                 ($lugar['estado'] === 'ocupado' ? 'fa-car' : 'fa-calendar-check'); 
                                        ?>"></i>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-12">
                                <div class="d-flex justify-content-center">
                                    <div class="me-4">
                                        <span class="badge bg-success me-1"></span> Disponible
                                    </div>
                                    <div class="me-4">
                                        <span class="badge bg-danger me-1"></span> Ocupado
                                    </div>
                                    <div>
                                        <span class="badge bg-warning me-1"></span> Reservado
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Listado de Lugares -->
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Listado de Lugares</h5>
                        <div>
                            <span class="badge bg-primary me-2">
                                <i class="fas fa-car me-1"></i> Carros: <?php echo $vehiculos['total_carros']; ?>
                            </span>
                            <span class="badge bg-secondary">
                                <i class="fas fa-motorcycle me-1"></i> Motos: <?php echo $vehiculos['total_motos']; ?>
                            </span>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover table-striped">
                                <thead>
                                    <tr>
                                        <th>N° Lugar</th>
                                        <th>Estado</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($lugares as $lugar): ?>
                                    <tr>
                                        <td><?php echo $lugar['numero_lugar']; ?></td>
                                        <td>
                                            <span class="badge bg-<?php 
                                                echo $lugar['estado'] === 'disponible' ? 'success' : 
                                                     ($lugar['estado'] === 'ocupado' ? 'danger' : 'warning'); 
                                            ?>">
                                                <?php echo ucfirst($lugar['estado']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <a href="?editar=<?php echo $lugar['id']; ?>" class="btn btn-outline-primary">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <?php if ($lugar['estado'] !== 'ocupado'): ?>
                                                <a href="?eliminar=<?php echo $lugar['id']; ?>" class="btn btn-outline-danger" 
                                                   onclick="return confirm('¿Está seguro de eliminar este lugar?')">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                                <?php endif; ?>
                                            </div>
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

    <!-- Modal para Crear/Editar Lugar -->
    <div class="modal fade" id="modalLugar" tabindex="-1" aria-labelledby="modalLugarLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalLugarLabel">
                        <?php echo $lugar_editar ? 'Editar Lugar' : 'Nuevo Lugar'; ?>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <?php if ($lugar_editar): ?>
                        <input type="hidden" name="id" value="<?php echo $lugar_editar['id']; ?>">
                        <?php endif; ?>
                        
                        <div class="mb-3">
                            <label for="numero_lugar" class="form-label">Número de Lugar</label>
                            <input type="number" class="form-control" id="numero_lugar" name="numero_lugar" 
                                   value="<?php echo $lugar_editar ? $lugar_editar['numero_lugar'] : ''; ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="estado" class="form-label">Estado</label>
                            <select class="form-select" id="estado" name="estado" required>
                                <option value="disponible" <?php echo ($lugar_editar && $lugar_editar['estado'] === 'disponible') ? 'selected' : ''; ?>>
                                    Disponible
                                </option>
                                <option value="ocupado" <?php echo ($lugar_editar && $lugar_editar['estado'] === 'ocupado') ? 'selected' : ''; ?>>
                                    Ocupado
                                </option>
                                <option value="reservado" <?php echo ($lugar_editar && $lugar_editar['estado'] === 'reservado') ? 'selected' : ''; ?>>
                                    Reservado
                                </option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">
                            <?php echo $lugar_editar ? 'Actualizar' : 'Crear'; ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php include '../../SysParqueo/modulos/Footer.php'; ?>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
    // Inicializar tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl)
    })
    
    // Abrir modal de edición automáticamente
    <?php if ($lugar_editar): ?>
    document.addEventListener('DOMContentLoaded', function() {
        var modal = new bootstrap.Modal(document.getElementById('modalLugar'));
        modal.show();
    });
    <?php endif; ?>
    </script>
    
    <style>
    .parking-map {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        justify-content: center;
        padding: 20px;
    }
    
    .parking-spot {
        width: 60px;
        height: 80px;
        border-radius: 5px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        color: white;
        position: relative;
        transition: all 0.3s ease;
    }
    
    .parking-spot:hover {
        transform: scale(1.05);
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    }
    
    .parking-spot-disponible {
        background-color: var(--bs-success);
    }
    
    .parking-spot-ocupado {
        background-color: var(--bs-danger);
    }
    
    .parking-spot-reservado {
        background-color: var(--bs-warning);
    }
    
    .parking-number {
        font-size: 12px;
        position: absolute;
        top: 5px;
        right: 5px;
    }
    
    .parking-spot i {
        font-size: 24px;
        margin-top: 10px;
    }
    
    .badge {
        padding: 8px 12px;
    }
    </style>
</body>
</html>
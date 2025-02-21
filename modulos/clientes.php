<?php
session_start();
require_once '../includes/conexion.php';

if (!isset($_SESSION['usuario_id'])) {
    header('Location: ../login.php');
    exit();
}

// Manejo de acciones
$mensaje = '';
$error = false;

// Eliminar cliente
if (isset($_GET['eliminar']) && !empty($_GET['eliminar'])) {
    try {
        // Verificar si tiene vehículos asociados
        $check_vehiculos = $pdo->prepare("SELECT COUNT(*) FROM vehiculos WHERE id_cliente = ?");
        $check_vehiculos->execute([$_GET['eliminar']]);
        if ($check_vehiculos->fetchColumn() > 0) {
            throw new Exception("No se puede eliminar el cliente porque tiene vehículos asociados");
        }
        
        // Eliminar cliente
        $stmt = $pdo->prepare("DELETE FROM clientes WHERE id = ?");
        $stmt->execute([$_GET['eliminar']]);
        $mensaje = "Cliente eliminado correctamente";
    } catch (Exception $e) {
        $error = true;
        $mensaje = $e->getMessage();
    }
}

// Agregar o editar cliente
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validar datos
        if (empty($_POST['nombre']) || empty($_POST['documento'])) {
            throw new Exception("Nombre y documento son obligatorios");
        }
        
        if (isset($_POST['id']) && !empty($_POST['id'])) {
            // Actualizar cliente existente
            $sql = "UPDATE clientes SET 
                    nombre = :nombre, 
                    documento = :documento, 
                    telefono = :telefono, 
                    email = :email, 
                    direccion = :direccion 
                    WHERE id = :id";
            $stmt = $pdo->prepare($sql);
            $params = [
                ':id' => $_POST['id'],
                ':nombre' => $_POST['nombre'],
                ':documento' => $_POST['documento'],
                ':telefono' => $_POST['telefono'],
                ':email' => $_POST['email'],
                ':direccion' => $_POST['direccion']
            ];
            $stmt->execute($params);
            $mensaje = "Cliente actualizado correctamente";
        } else {
            // Verificar si ya existe el documento
            $check = $pdo->prepare("SELECT id FROM clientes WHERE documento = ?");
            $check->execute([$_POST['documento']]);
            if ($check->rowCount() > 0) {
                throw new Exception("Ya existe un cliente con ese documento");
            }
            
            // Insertar nuevo cliente
            $sql = "INSERT INTO clientes (nombre, documento, telefono, email, direccion) 
                    VALUES (:nombre, :documento, :telefono, :email, :direccion)";
            $stmt = $pdo->prepare($sql);
            $params = [
                ':nombre' => $_POST['nombre'],
                ':documento' => $_POST['documento'],
                ':telefono' => $_POST['telefono'],
                ':email' => $_POST['email'],
                ':direccion' => $_POST['direccion']
            ];
            $stmt->execute($params);
            $mensaje = "Cliente agregado correctamente";
        }
    } catch (Exception $e) {
        $error = true;
        $mensaje = $e->getMessage();
    }
}

// Obtener cliente para editar
$cliente_editar = null;
if (isset($_GET['editar']) && !empty($_GET['editar'])) {
    $stmt = $pdo->prepare("SELECT * FROM clientes WHERE id = ?");
    $stmt->execute([$_GET['editar']]);
    $cliente_editar = $stmt->fetch();
}

// Paginación
$pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$registros_por_pagina = 10;
$inicio = ($pagina - 1) * $registros_por_pagina;

// Búsqueda
$busqueda = isset($_GET['busqueda']) ? $_GET['busqueda'] : '';
$condicion_busqueda = '';
$params_busqueda = [];

if ($busqueda) {
    $condicion_busqueda = " WHERE nombre LIKE :busqueda OR documento LIKE :busqueda OR telefono LIKE :busqueda";
    $params_busqueda[':busqueda'] = "%$busqueda%";
}

// Contar total de registros para paginación
$sql_count = "SELECT COUNT(*) FROM clientes" . $condicion_busqueda;
$stmt_count = $pdo->prepare($sql_count);
if ($busqueda) {
    $stmt_count->execute($params_busqueda);
} else {
    $stmt_count->execute();
}
$total_registros = $stmt_count->fetchColumn();
$total_paginas = ceil($total_registros / $registros_por_pagina);

// Obtener clientes
$sql = "SELECT * FROM clientes" . $condicion_busqueda . " ORDER BY nombre ASC LIMIT :inicio, :registros_por_pagina";
$stmt = $pdo->prepare($sql);

if ($busqueda) {
    $stmt->bindValue(':busqueda', "%$busqueda%", PDO::PARAM_STR);
}

$stmt->bindValue(':inicio', $inicio, PDO::PARAM_INT);
$stmt->bindValue(':registros_por_pagina', $registros_por_pagina, PDO::PARAM_INT);
$stmt->execute();
$clientes = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Clientes - Sistema de Parqueadero</title>
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
                    <h1 class="h2">Gestión de Clientes</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#clienteModal">
                            <i class="fas fa-plus me-2"></i>Nuevo Cliente
                        </button>
                    </div>
                </div>

                <?php if ($mensaje): ?>
                <div class="alert alert-<?php echo $error ? 'danger' : 'success'; ?> alert-dismissible fade show" role="alert">
                    <?php echo $mensaje; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php endif; ?>

                <!-- Search and Filters -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-6">
                                <div class="input-group">
                                    <input type="text" class="form-control" placeholder="Buscar por nombre, documento o teléfono" name="busqueda" value="<?php echo htmlspecialchars($busqueda); ?>">
                                    <button class="btn btn-outline-secondary" type="submit">
                                        <i class="fas fa-search"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="col-md-6 text-md-end">
                                <?php if ($busqueda): ?>
                                <a href="clientes.php" class="btn btn-outline-secondary">
                                    <i class="fas fa-times me-2"></i>Limpiar filtros
                                </a>
                                <?php endif; ?>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Clients Table -->
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Nombre</th>
                                        <th>Documento</th>
                                        <th>Teléfono</th>
                                        <th>Email</th>
                                        <th>Dirección</th>
                                        <th>Fecha Registro</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (count($clientes) > 0): ?>
                                        <?php foreach ($clientes as $cliente): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($cliente['nombre']); ?></td>
                                            <td><?php echo htmlspecialchars($cliente['documento']); ?></td>
                                            <td><?php echo htmlspecialchars($cliente['telefono'] ?? 'N/A'); ?></td>
                                            <td><?php echo htmlspecialchars($cliente['email'] ?? 'N/A'); ?></td>
                                            <td><?php echo htmlspecialchars($cliente['direccion'] ?? 'N/A'); ?></td>
                                            <td><?php echo date('d/m/Y', strtotime($cliente['fecha_registro'])); ?></td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <a href="?editar=<?php echo $cliente['id']; ?>" class="btn btn-outline-primary" title="Editar">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <a href="#" class="btn btn-outline-info ver-vehiculos" data-cliente-id="<?php echo $cliente['id']; ?>" title="Ver Vehículos">
                                                          <i class="fas fa-car"></i>
                                                    </a>
                                                    </a>
                                                    <a href="#" class="btn btn-outline-danger" title="Eliminar" 
                                                       onclick="confirmarEliminacion(<?php echo $cliente['id']; ?>, '<?php echo htmlspecialchars($cliente['nombre']); ?>')">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="7" class="text-center py-4">
                                                <div class="alert alert-info mb-0">
                                                    <i class="fas fa-info-circle me-2"></i>
                                                    <?php echo $busqueda ? 'No se encontraron clientes con el criterio de búsqueda.' : 'No hay clientes registrados.'; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <?php if ($total_paginas > 1): ?>
                        <nav aria-label="Page navigation">
                            <ul class="pagination justify-content-center mt-4">
                                <?php if ($pagina > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?pagina=1<?php echo $busqueda ? '&busqueda='.urlencode($busqueda) : ''; ?>">
                                        <i class="fas fa-angle-double-left"></i>
                                    </a>
                                </li>
                                <li class="page-item">
                                    <a class="page-link" href="?pagina=<?php echo $pagina-1; ?><?php echo $busqueda ? '&busqueda='.urlencode($busqueda) : ''; ?>">
                                        <i class="fas fa-angle-left"></i>
                                    </a>
                                </li>
                                <?php endif; ?>

                                <?php 
                                $rango = 2;
                                $desde = max(1, $pagina - $rango);
                                $hasta = min($total_paginas, $pagina + $rango);
                                
                                for ($i = $desde; $i <= $hasta; $i++): 
                                ?>
                                <li class="page-item <?php echo $i == $pagina ? 'active' : ''; ?>">
                                    <a class="page-link" href="?pagina=<?php echo $i; ?><?php echo $busqueda ? '&busqueda='.urlencode($busqueda) : ''; ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                                <?php endfor; ?>

                                <?php if ($pagina < $total_paginas): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?pagina=<?php echo $pagina+1; ?><?php echo $busqueda ? '&busqueda='.urlencode($busqueda) : ''; ?>">
                                        <i class="fas fa-angle-right"></i>
                                    </a>
                                </li>
                                <li class="page-item">
                                    <a class="page-link" href="?pagina=<?php echo $total_paginas; ?><?php echo $busqueda ? '&busqueda='.urlencode($busqueda) : ''; ?>">
                                        <i class="fas fa-angle-double-right"></i>
                                    </a>
                                </li>
                                <?php endif; ?>
                            </ul>
                        </nav>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
<!-- Modal para mostrar los vehículos -->
<div class="modal fade" id="vehiculosModal" tabindex="-1" aria-labelledby="vehiculosModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="vehiculosModalLabel">Vehículos del Cliente</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <div id="vehiculosContent">
                    Cargando vehículos...
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>
    <!-- Client Modal -->
    <div class="modal fade" id="clienteModal" tabindex="-1" aria-labelledby="clienteModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="clienteModalLabel">
                        <?php echo $cliente_editar ? 'Editar Cliente' : 'Nuevo Cliente'; ?>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="clienteForm" method="POST" action="">
                        <?php if ($cliente_editar): ?>
                        <input type="hidden" name="id" value="<?php echo $cliente_editar['id']; ?>">
                        <?php endif; ?>
                        
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="nombre" class="form-label">Nombre Completo <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="nombre" name="nombre" required
                                       value="<?php echo $cliente_editar ? htmlspecialchars($cliente_editar['nombre']) : ''; ?>">
                            </div>
                            
                            <div class="col-md-6">
                                <label for="documento" class="form-label">Documento <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="documento" name="documento" required
                                       value="<?php echo $cliente_editar ? htmlspecialchars($cliente_editar['documento']) : ''; ?>">
                            </div>
                            
                            <div class="col-md-6">
                                <label for="telefono" class="form-label">Teléfono</label>
                                <input type="text" class="form-control" id="telefono" name="telefono"
                                       value="<?php echo $cliente_editar ? htmlspecialchars($cliente_editar['telefono']) : ''; ?>">
                            </div>
                            
                            <div class="col-md-6">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email"
                                       value="<?php echo $cliente_editar ? htmlspecialchars($cliente_editar['email']) : ''; ?>">
                            </div>
                            
                            <div class="col-12">
                                <label for="direccion" class="form-label">Dirección</label>
                                <input type="text" class="form-control" id="direccion" name="direccion"
                                       value="<?php echo $cliente_editar ? htmlspecialchars($cliente_editar['direccion']) : ''; ?>">
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" form="clienteForm" class="btn btn-primary">
                        <?php echo $cliente_editar ? 'Actualizar' : 'Guardar'; ?>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <?php include '../../SysParqueo/modulos/Footer.php'; ?>
    
    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    
    <script>
        // Mostrar modal de edición si hay parámetro en la URL
        <?php if ($cliente_editar): ?>
        document.addEventListener('DOMContentLoaded', function() {
            const clienteModal = new bootstrap.Modal(document.getElementById('clienteModal'));
            clienteModal.show();
        });
        <?php endif; ?>
        
        // Función para confirmar eliminación
        function confirmarEliminacion(id, nombre) {
            if (confirm(`¿Está seguro que desea eliminar al cliente "${nombre}"?`)) {
                window.location.href = `?eliminar=${id}`;
            }
        }
    </script>
    <script>
document.addEventListener("DOMContentLoaded", function () {
    const botones = document.querySelectorAll('.ver-vehiculos');

    botones.forEach(boton => {
        boton.addEventListener('click', function () {
            const clienteId = this.getAttribute('data-cliente-id');

            // Carga los vehículos mediante AJAX
            fetch(`ajax_vehiculos_cliente.php?cliente_id=${clienteId}`)
                .then(response => response.json())
                .then(data => {
                    const content = document.getElementById('vehiculosContent');
                    if (data.success) {
                        let html = `<table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Placa</th>
                                    <th>Marca</th>
                                    <th>Modelo</th>
                                    <th>Color</th>
                                </tr>
                            </thead>
                            <tbody>`;

                        data.vehiculos.forEach(v => {
                            html += `
                                <tr>
                                    <td>${v.id}</td>
                                    <td>${v.placa}</td>
                                    <td>${v.marca}</td>
                                    <td>${v.modelo}</td>
                                    <td>${v.color}</td>
                                </tr>`;
                        });

                        html += `</tbody></table>`;
                        content.innerHTML = html;
                    } else {
                        content.innerHTML = `<p>${data.error}</p>`;
                    }

                    // Abre el modal
                    const modal = new bootstrap.Modal(document.getElementById('vehiculosModal'));
                    modal.show();
                })
                .catch(error => {
                    document.getElementById('vehiculosContent').innerHTML = `<p>Error al cargar los vehículos.</p>`;
                    console.error("Error:", error);
                });
        });
    });
});
</script>

</body>
</html>

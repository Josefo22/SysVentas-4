<?php
session_start();
require_once '../includes/conexion.php';

if (!isset($_SESSION['usuario_id'])) {
    header('Location: ../login.php');
    exit();
}

// Procesar formulario de creación/edición
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'create') {
            $tipo = $_POST['tipo'];
            $precio = $_POST['precio'];
            $descripcion = $_POST['descripcion'];
            
            $sql = "INSERT INTO tarifas (tipo, precio, descripcion) VALUES (?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$tipo, $precio, $descripcion]);
            
            $_SESSION['mensaje'] = "Tarifa creada exitosamente";
            
        } elseif ($_POST['action'] === 'update') {
            $id = $_POST['id'];
            $tipo = $_POST['tipo'];
            $precio = $_POST['precio'];
            $descripcion = $_POST['descripcion'];
            
            $sql = "UPDATE tarifas SET tipo = ?, precio = ?, descripcion = ? WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$tipo, $precio, $descripcion, $id]);
            
            $_SESSION['mensaje'] = "Tarifa actualizada exitosamente";
        }
        
        header('Location: tarifas.php');
        exit();
    }
}

// Eliminar tarifa
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    
    $sql = "DELETE FROM tarifas WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id]);
    
    $_SESSION['mensaje'] = "Tarifa eliminada exitosamente";
    header('Location: tarifas.php');
    exit();
}

// Obtener todas las tarifas
$sql = "SELECT * FROM tarifas ORDER BY tipo, precio";
$stmt = $pdo->query($sql);
$tarifas = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Tarifas - Sistema de Parqueadero</title>
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
    <link rel="stylesheet" href="../css/Style.css">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">

</head>
<body>
<?php include 'Navbar.php'; ?>
    <div class="container-fluid">
        <div class="row">
            

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 ms-sm-auto px-md-4 py-4">
                <!-- Header -->
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Gestión de Tarifas</h1>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createTarifaModal">
                        <i class="fas fa-plus me-2"></i>Nueva Tarifa
                    </button>
                </div>

                <!-- Mensajes de alerta -->
                <?php if (isset($_SESSION['mensaje'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php 
                        echo $_SESSION['mensaje'];
                        unset($_SESSION['mensaje']);
                        ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <!-- Tabla de Tarifas -->
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Tipo</th>
                                <th>Precio</th>
                                <th>Descripción</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($tarifas as $tarifa): ?>
                            <tr>
                                <td>
                                    <span class="badge bg-<?php echo $tarifa['tipo'] === 'mensual' ? 'success' : 'primary'; ?>">
                                        <?php echo ucfirst($tarifa['tipo']); ?>
                                    </span>
                                </td>
                                <td>$<?php echo number_format($tarifa['precio'], 3); ?></td>
                                <td><?php echo htmlspecialchars($tarifa['descripcion']); ?></td>
                                <td>
                                    <button class="btn btn-sm btn-outline-primary me-2" 
                                            onclick="editarTarifa(<?php echo htmlspecialchars(json_encode($tarifa)); ?>)">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger"
                                            onclick="confirmarEliminacion(<?php echo $tarifa['id']; ?>)">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Crear Tarifa -->
    <div class="modal fade" id="createTarifaModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Nueva Tarifa</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="tarifas.php" method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="create">
                        <div class="mb-3">
                            <label class="form-label">Tipo de Tarifa</label>
                            <select name="tipo" class="form-select" required>
                                <option value="diaria">Diaria</option>
                                <option value="mensual">Mensual</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Precio</label>
                            <input type="number" name="precio" class="form-control" step="0.01" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Descripción</label>
                            <textarea name="descripcion" class="form-control" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Guardar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Editar Tarifa -->
    <div class="modal fade" id="editTarifaModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Editar Tarifa</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="tarifas.php" method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="update">
                        <input type="hidden" name="id" id="edit_id">
                        <div class="mb-3">
                            <label class="form-label">Tipo de Tarifa</label>
                            <select name="tipo" id="edit_tipo" class="form-select" required>
                                <option value="diaria">Diaria</option>
                                <option value="mensual">Mensual</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Precio</label>
                            <input type="number" name="precio" id="edit_precio" class="form-control" step="0.01" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Descripción</label>
                            <textarea name="descripcion" id="edit_descripcion" class="form-control" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Actualizar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php include 'Footer.php'; ?>
    
    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
    function editarTarifa(tarifa) {
        document.getElementById('edit_id').value = tarifa.id;
        document.getElementById('edit_tipo').value = tarifa.tipo;
        document.getElementById('edit_precio').value = tarifa.precio;
        document.getElementById('edit_descripcion').value = tarifa.descripcion;
        
        new bootstrap.Modal(document.getElementById('editTarifaModal')).show();
    }

    function confirmarEliminacion(id) {
        if (confirm('¿Está seguro de que desea eliminar esta tarifa?')) {
            window.location.href = `tarifas.php?delete=${id}`;
        }
    }
    </script>
</body>
</html>
<?php
session_start();
require_once 'includes/conexion.php';

if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit();
}

// Obtener lugares disponibles
$sql_lugares = "SELECT id, numero_lugar FROM lugares WHERE estado = 'disponible' ORDER BY numero_lugar";
$stmt_lugares = $pdo->query($sql_lugares);
$lugares_disponibles = $stmt_lugares->fetchAll();

// Obtener tarifas
$sql_tarifas = "SELECT id, tipo, precio, descripcion FROM tarifas ORDER BY tipo";
$stmt_tarifas = $pdo->query($sql_tarifas);
$tarifas = $stmt_tarifas->fetchAll();

// Procesar el formulario cuando se envía
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();
        
        // 1. Verificar si el cliente ya existe
        $documento = $_POST['documento'];
        $sql_verificar_cliente = "SELECT id FROM clientes WHERE documento = :documento";
        $stmt = $pdo->prepare($sql_verificar_cliente);
        $stmt->execute(['documento' => $documento]);
        $cliente_existente = $stmt->fetch();
        
        if ($cliente_existente) {
            $id_cliente = $cliente_existente['id'];
            
            // Actualizar datos del cliente
            $sql_actualizar_cliente = "UPDATE clientes SET 
                nombre = :nombre,
                telefono = :telefono,
                email = :email,
                direccion = :direccion
                WHERE id = :id";
            
            $stmt = $pdo->prepare($sql_actualizar_cliente);
            $stmt->execute([
                'nombre' => $_POST['nombre'],
                'telefono' => $_POST['telefono'],
                'email' => $_POST['email'],
                'direccion' => $_POST['direccion'],
                'id' => $id_cliente
            ]);
        } else {
            // Insertar nuevo cliente
            $sql_insertar_cliente = "INSERT INTO clientes (nombre, documento, telefono, email, direccion) 
                VALUES (:nombre, :documento, :telefono, :email, :direccion)";
            
            $stmt = $pdo->prepare($sql_insertar_cliente);
            $stmt->execute([
                'nombre' => $_POST['nombre'],
                'documento' => $documento,
                'telefono' => $_POST['telefono'],
                'email' => $_POST['email'],
                'direccion' => $_POST['direccion']
            ]);
            
            $id_cliente = $pdo->lastInsertId();
        }
        
        // 2. Verificar si el vehículo ya existe
        $placa = strtoupper($_POST['placa']);
        $sql_verificar_vehiculo = "SELECT id FROM vehiculos WHERE placa = :placa";
        $stmt = $pdo->prepare($sql_verificar_vehiculo);
        $stmt->execute(['placa' => $placa]);
        $vehiculo_existente = $stmt->fetch();
        
        if ($vehiculo_existente) {
            $id_vehiculo = $vehiculo_existente['id'];
            
            // Actualizar datos del vehículo
            $sql_actualizar_vehiculo = "UPDATE vehiculos SET 
                marca = :marca,
                modelo = :modelo,
                color = :color,
                tipo = :tipo,
                id_cliente = :id_cliente
                WHERE id = :id";
            
            $stmt = $pdo->prepare($sql_actualizar_vehiculo);
            $stmt->execute([
                'marca' => $_POST['marca'],
                'modelo' => $_POST['modelo'],
                'color' => $_POST['color'],
                'tipo' => $_POST['tipo_vehiculo'],
                'id_cliente' => $id_cliente,
                'id' => $id_vehiculo
            ]);
        } else {
            // Insertar nuevo vehículo
            $sql_insertar_vehiculo = "INSERT INTO vehiculos (id_cliente, placa, marca, modelo, color, tipo) 
                VALUES (:id_cliente, :placa, :marca, :modelo, :color, :tipo)";
            
            $stmt = $pdo->prepare($sql_insertar_vehiculo);
            $stmt->execute([
                'id_cliente' => $id_cliente,
                'placa' => $placa,
                'marca' => $_POST['marca'],
                'modelo' => $_POST['modelo'],
                'color' => $_POST['color'],
                'tipo' => $_POST['tipo_vehiculo']
            ]);
            
            $id_vehiculo = $pdo->lastInsertId();
        }
        
        // 3. Crear registro de entrada
        $sql_insertar_registro = "INSERT INTO registros (id_vehiculo, id_lugar, id_tarifa) 
            VALUES (:id_vehiculo, :id_lugar, :id_tarifa)";
        
        $stmt = $pdo->prepare($sql_insertar_registro);
        $stmt->execute([
            'id_vehiculo' => $id_vehiculo,
            'id_lugar' => $_POST['lugar'],
            'id_tarifa' => $_POST['tarifa']
        ]);
        
        $id_registro = $pdo->lastInsertId();
        
        // 4. Actualizar estado del lugar a ocupado
        $sql_actualizar_lugar = "UPDATE lugares SET estado = 'ocupado' WHERE id = :id_lugar";
        $stmt = $pdo->prepare($sql_actualizar_lugar);
        $stmt->execute(['id_lugar' => $_POST['lugar']]);
        
        // 5. Si es tarifa mensual, registrar el pago
        if ($_POST['tipo_tarifa'] === 'mensual') {
            $sql_obtener_precio = "SELECT precio FROM tarifas WHERE id = :id";
            $stmt = $pdo->prepare($sql_obtener_precio);
            $stmt->execute(['id' => $_POST['tarifa']]);
            $precio = $stmt->fetch()['precio'];
            
            $sql_insertar_pago = "INSERT INTO pagos (id_cliente, id_registro, monto, metodo_pago) 
                VALUES (:id_cliente, :id_registro, :monto, :metodo_pago)";
            
            $stmt = $pdo->prepare($sql_insertar_pago);
            $stmt->execute([
                'id_cliente' => $id_cliente,
                'id_registro' => $id_registro,
                'monto' => $precio,
                'metodo_pago' => $_POST['metodo_pago'] ?? 'efectivo'
            ]);
        }
        
        $pdo->commit();
        $mensaje_exito = "Registro creado exitosamente";
        
    } catch (PDOException $e) {
        $pdo->rollBack();
        $mensaje_error = "Error al procesar el registro: " . $e->getMessage();
    }
}

// Función para verificar si un cliente existe por documento
if (isset($_GET['documento'])) {
    $documento = $_GET['documento'];
    $sql = "SELECT c.*, v.placa, v.marca, v.modelo, v.color, v.tipo 
            FROM clientes c 
            LEFT JOIN vehiculos v ON c.id = v.id_cliente 
            WHERE c.documento = :documento 
            ORDER BY v.id DESC LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['documento' => $documento]);
    $cliente = $stmt->fetch(PDO::FETCH_ASSOC);
    
    header('Content-Type: application/json');
    echo json_encode($cliente);
    exit;
}

// Función para verificar si un vehículo existe por placa
if (isset($_GET['placa'])) {
    $placa = strtoupper($_GET['placa']);
    $sql = "SELECT v.*, c.nombre, c.documento, c.telefono, c.email, c.direccion
            FROM vehiculos v
            JOIN clientes c ON v.id_cliente = c.id
            WHERE v.placa = :placa";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['placa' => $placa]);
    $vehiculo = $stmt->fetch(PDO::FETCH_ASSOC);
    
    header('Content-Type: application/json');
    echo json_encode($vehiculo);
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrar Entrada - Sistema de Parqueadero</title>
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
                    <h1 class="h2">Registrar Entrada</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="window.location.href='index.php'">
                                <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                            </button>
                        </div>
                    </div>
                </div>

                <?php if (isset($mensaje_exito)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i> <?php echo $mensaje_exito; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php endif; ?>

                <?php if (isset($mensaje_error)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i> <?php echo $mensaje_error; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php endif; ?>

                <!-- Formulario de Registro -->
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
                                <form id="formRegistro" method="POST" class="needs-validation" novalidate>
                                    <div class="row g-3">
                                        <!-- Búsqueda Rápida -->
                                        <div class="col-12 mb-4">
                                            <div class="card bg-light">
                                                <div class="card-body">
                                                    <h5 class="card-title mb-3">Búsqueda Rápida</h5>
                                                    <div class="row g-3">
                                                        <div class="col-md-6">
                                                            <div class="input-group">
                                                                <span class="input-group-text"><i class="fas fa-id-card"></i></span>
                                                                <input type="text" id="buscarDocumento" class="form-control" placeholder="Buscar por documento">
                                                                <button type="button" class="btn btn-outline-primary" onclick="buscarCliente()">
                                                                    <i class="fas fa-search"></i>
                                                                </button>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <div class="input-group">
                                                                <span class="input-group-text"><i class="fas fa-car"></i></span>
                                                                <input type="text" id="buscarPlaca" class="form-control" placeholder="Buscar por placa">
                                                                <button type="button" class="btn btn-outline-primary" onclick="buscarVehiculo()">
                                                                    <i class="fas fa-search"></i>
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Datos del Cliente -->
                                        <div class="col-12 mb-4">
                                            <h5 class="mb-3">Datos del Cliente</h5>
                                            <div class="row g-3">
                                                <div class="col-md-6">
                                                    <label for="nombre" class="form-label">Nombre Completo</label>
                                                    <input type="text" class="form-control" id="nombre" name="nombre" required>
                                                    <div class="invalid-feedback">
                                                        Ingrese el nombre del cliente
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <label for="documento" class="form-label">Documento de Identidad</label>
                                                    <input type="text" class="form-control" id="documento" name="documento" required>
                                                    <div class="invalid-feedback">
                                                        Ingrese el documento del cliente
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <label for="telefono" class="form-label">Teléfono</label>
                                                    <input type="tel" class="form-control" id="telefono" name="telefono">
                                                </div>
                                                <div class="col-md-4">
                                                    <label for="email" class="form-label">Email</label>
                                                    <input type="email" class="form-control" id="email" name="email">
                                                </div>
                                                <div class="col-md-4">
                                                    <label for="direccion" class="form-label">Dirección</label>
                                                    <input type="text" class="form-control" id="direccion" name="direccion">
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Datos del Vehículo -->
                                        <div class="col-12 mb-4">
                                            <h5 class="mb-3">Datos del Vehículo</h5>
                                            <div class="row g-3">
                                                <div class="col-md-4">
                                                    <label for="placa" class="form-label">Placa</label>
                                                    <input type="text" class="form-control" id="placa" name="placa" required>
                                                    <div class="invalid-feedback">
                                                        Ingrese la placa del vehículo
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <label for="marca" class="form-label">Marca</label>
                                                    <input type="text" class="form-control" id="marca" name="marca">
                                                </div>
                                                <div class="col-md-4">
                                                    <label for="modelo" class="form-label">Modelo</label>
                                                    <input type="text" class="form-control" id="modelo" name="modelo">
                                                </div>
                                                <div class="col-md-4">
                                                    <label for="color" class="form-label">Color</label>
                                                    <input type="text" class="form-control" id="color" name="color">
                                                </div>
                                                <div class="col-md-4">
                                                    <label for="tipo_vehiculo" class="form-label">Tipo de Vehículo</label>
                                                    <select class="form-select" id="tipo_vehiculo" name="tipo_vehiculo" required>
                                                        <option value="">Seleccionar...</option>
                                                        <option value="carro">Automóvil</option>
                                                        <option value="moto">Motocicleta</option>
                                                    </select>
                                                    <div class="invalid-feedback">
                                                        Seleccione el tipo de vehículo
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Datos del Registro -->
                                        <div class="col-12 mb-4">
                                            <h5 class="mb-3">Datos del Registro</h5>
                                            <div class="row g-3">
                                                <div class="col-md-4">
                                                    <label for="lugar" class="form-label">Lugar de Parqueo</label>
                                                    <select class="form-select" id="lugar" name="lugar" required>
                                                        <option value="">Seleccionar...</option>
                                                        <?php foreach ($lugares_disponibles as $lugar): ?>
                                                        <option value="<?php echo $lugar['id']; ?>">
                                                            Lugar #<?php echo $lugar['numero_lugar']; ?>
                                                        </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                    <div class="invalid-feedback">
                                                        Seleccione un lugar disponible
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <label for="tipo_tarifa" class="form-label">Tipo de Tarifa</label>
                                                    <select class="form-select" id="tipo_tarifa" name="tipo_tarifa" onchange="mostrarTarifas()" required>
                                                        <option value="">Seleccionar...</option>
                                                        <option value="diaria">Diaria</option>
                                                        <option value="mensual">Mensual</option>
                                                    </select>
                                                    <div class="invalid-feedback">
                                                        Seleccione el tipo de tarifa
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <label for="tarifa" class="form-label">Tarifa</label>
                                                    <select class="form-select" id="tarifa" name="tarifa" required disabled>
                                                        <option value="">Seleccione primero el tipo</option>
                                                    </select>
                                                    <div class="invalid-feedback">
                                                        Seleccione la tarifa
                                                    </div>
                                                </div>
                                                
                                                <!-- Mostrar solo si es mensual -->
                                                <div class="col-md-4" id="divMetodoPago" style="display: none;">
                                                    <label for="metodo_pago" class="form-label">Método de Pago</label>
                                                    <select class="form-select" id="metodo_pago" name="metodo_pago">
                                                        <option value="efectivo">Efectivo</option>
                                                        <option value="tarjeta">Tarjeta</option>
                                                        <option value="transferencia">Transferencia</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-12">
                                            <hr class="my-4">
                                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                                <button type="button" class="btn btn-outline-secondary me-md-2" onclick="window.location.href='index.php'">
                                                    <i class="fas fa-times me-2"></i>Cancelar
                                                </button>
                                                <button type="submit" class="btn btn-primary">
                                                    <i class="fas fa-save me-2"></i>Registrar Entrada
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include '../SysParqueo\modulos\Footer.php'; ?>
    
    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
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
    
    // Función para buscar cliente por documento
    function buscarCliente() {
        const documento = document.getElementById('buscarDocumento').value;
        if (!documento) return;
        
        fetch(`registro.php?documento=${documento}`)
            .then(response => response.json())
            .then(data => {
                if (data) {
                    // Llenar datos del cliente
                    document.getElementById('nombre').value = data.nombre || '';
                    document.getElementById('documento').value = data.documento || '';
                    document.getElementById('telefono').value = data.telefono || '';
                    document.getElementById('email').value = data.email || '';
                    document.getElementById('direccion').value = data.direccion || '';
                    
                    // Llenar datos del vehículo si existe
                    if (data.placa) {
                        document.getElementById('placa').value = data.placa || '';
                        document.getElementById('marca').value = data.marca || '';
                        document.getElementById('modelo').value = data.modelo || '';
                        document.getElementById('color').value = data.color || '';
                        if (data.tipo) {
                            document.getElementById('tipo_vehiculo').value = data.tipo;
                        }
                    }
                } else {
                    alert('Cliente no encontrado');
                }
            })
            .catch(error => console.error('Error:', error));
    }
    
    // Función para buscar vehículo por placa
    function buscarVehiculo() {
        const placa = document.getElementById('buscarPlaca').value;
        if (!placa) return;
        
        fetch(`registro.php?placa=${placa}`)
            .then(response => response.json())
            .then(data => {
                if (data) {
                    // Llenar datos del vehículo
                    document.getElementById('placa').value = data.placa || '';
                    document.getElementById('marca').value = data.marca || '';
                    document.getElementById('modelo').value = data.modelo || '';
                    document.getElementById('color').value = data.color || '';
                    if (data.tipo) {
                        document.getElementById('tipo_vehiculo').value = data.tipo;
                    }
                    
                    // Llenar datos del cliente
                    document.getElementById('nombre').value = data.nombre || '';
                    document.getElementById('documento').value = data.documento || '';
                    document.getElementById('telefono').value = data.telefono || '';
                    document.getElementById('email').value = data.email || '';
                    document.getElementById('direccion').value = data.direccion || '';
                } else {
                    alert('Vehículo no encontrado');
                }
            })
            .catch(error => console.error('Error:', error));
    }
    
    // Función para mostrar tarifas según el tipo seleccionado
    function mostrarTarifas() {
        const tipoTarifa = document.getElementById('tipo_tarifa').value;
        const selectTarifa = document.getElementById('tarifa');
        const divMetodoPago = document.getElementById('divMetodoPago');
        
        // Limpiar opciones actuales
        selectTarifa.innerHTML = '<option value="">Seleccionar...</option>';
        
        if (!tipoTarifa) {
            selectTarifa.disabled = true;
            divMetodoPago.style.display = 'none';
            return;
        }
        
        // Mostrar/ocultar método de pago para tarifa mensual
        if (tipoTarifa === 'mensual') {
            divMetodoPago.style.display = 'block';
        } else {
            divMetodoPago.style.display = 'none';
        }
        
        // Obtener tarifas del tipo seleccionado
        selectTarifa.disabled = false;
        
        // Aquí agregamos las tarifas disponibles desde PHP
        <?php foreach ($tarifas as $tarifa): ?>
        if ('<?php echo $tarifa['tipo']; ?>' === tipoTarifa) {
            const option = document.createElement('option');
            option.value = '<?php echo $tarifa['id']; ?>';
            option.textContent = '<?php echo "$" . number_format($tarifa['precio'], 2) . " - " . $tarifa['descripcion']; ?>';
            selectTarifa.appendChild(option);
        }
        <?php endforeach; ?>
    }
    </script>
</body>
</html>
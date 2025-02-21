<?php
session_start();
require_once 'includes/conexion.php';

// Verificar si el usuario ha iniciado sesión
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit();
}

// Obtener datos del usuario
$usuario_id = $_SESSION['usuario_id'];
$nombre_usuario = $_SESSION['nombre'];
$rol_usuario = $_SESSION['rol'];

// Consultar estadísticas generales
try {
    // Total de lugares de parqueo
    $sql_lugares = "SELECT 
        COUNT(*) as total_lugares,
        SUM(CASE WHEN estado = 'disponible' THEN 1 ELSE 0 END) as disponibles,
        SUM(CASE WHEN estado = 'ocupado' THEN 1 ELSE 0 END) as ocupados
    FROM lugares";
    $stmt = $pdo->query($sql_lugares);
    $lugares = $stmt->fetch();

    // Vehículos actuales en el parqueadero
    $sql_vehiculos = "SELECT COUNT(*) as total FROM registros WHERE estado = 'activo'";
    $stmt = $pdo->query($sql_vehiculos);
    $vehiculos_actuales = $stmt->fetch()['total'];

    // Ingresos del día
    $sql_ingresos = "SELECT SUM(monto) as total FROM pagos WHERE DATE(fecha_pago) = CURRENT_DATE()";
    $stmt = $pdo->query($sql_ingresos);
    $ingresos_hoy = $stmt->fetch()['total'] ?? 0;

} catch (PDOException $e) {
    die("Error al obtener datos: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Dashboard - Parqueadero</title>
    <link rel="stylesheet" href="css/estilo.css">
</head>
<body>
    <h1>Bienvenido, <?php echo htmlspecialchars($nombre_usuario); ?> 👋</h1>
    <h2>Rol: <?php echo htmlspecialchars($rol_usuario); ?></h2>

    <h3>📊 Estadísticas Generales</h3>
    <ul>
        <li><strong>Total de lugares:</strong> <?php echo $lugares['total_lugares']; ?></li>
        <li><strong>Lugares disponibles:</strong> <?php echo $lugares['disponibles']; ?></li>
        <li><strong>Lugares ocupados:</strong> <?php echo $lugares['ocupados']; ?></li>
        <li><strong>Vehículos actuales:</strong> <?php echo $vehiculos_actuales; ?></li>
        <li><strong>Ingresos hoy:</strong> $<?php echo number_format($ingresos_hoy, 2); ?></li>
    </ul>

    <a href="logout.php">Cerrar sesión</a>
</body>
</html>

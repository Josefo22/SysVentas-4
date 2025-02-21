<?php
include("includes/conexion.php");

$nombre = "Admin";
$email = "admin@parqueadero.com";
$contrasena = password_hash("123456", PASSWORD_DEFAULT); // Encriptación segura

try {
    $stmt = $pdo->prepare("INSERT INTO usuarios (nombre, email, contrasena, rol) VALUES (:nombre, :email, :contrasena, 'admin')");
    $stmt->bindParam(':nombre', $nombre);
    $stmt->bindParam(':email', $email);
    $stmt->bindParam(':contrasena', $contrasena);
    $stmt->execute();

    echo "✅ Usuario administrador creado con éxito.";
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage();
}
?>

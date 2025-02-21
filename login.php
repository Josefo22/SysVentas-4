<?php
session_start();
require_once 'includes/conexion.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];
    
    if ($email && $password) {
        try {
            // Preparar la consulta
            $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = ? LIMIT 1");
            $stmt->execute([$email]);
            
            // Verificar si se encontró el usuario
            if ($stmt && $usuario = $stmt->fetch(PDO::FETCH_ASSOC)) {
                // Verificar la contraseña
                if (password_verify($password, $usuario['contrasena'])) {
                    // Login exitoso
                    $_SESSION['usuario_id'] = $usuario['id'];
                    $_SESSION['nombre'] = $usuario['nombre'];
                    $_SESSION['rol'] = $usuario['rol'];
                    $_SESSION['email'] = $usuario['email'];
                    
                    // Actualizar último acceso
                    $update = $pdo->prepare("UPDATE usuarios SET ultimo_acceso = CURRENT_TIMESTAMP WHERE id = ?");
                    $update->execute([$usuario['id']]);
                    
                    header('Location: dashboard.php');
                    exit();
                } else {
                    $error = "Contraseña incorrecta";
                }
            } else {
                $error = "No se encontró el usuario";
            }
        } catch (PDOException $e) {
            $error = "Error en el sistema. Por favor, intente más tarde.";
            // Para desarrollo, puedes descomentar la siguiente línea:
         $error = "Error: " . $e->getMessage();
        }
    } else {
        $error = "Por favor, complete todos los campos";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistema de Parqueadero</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(120deg, #2980b9, #8e44ad);
            height: 100vh;
        }
        .login-container {
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 0 20px rgba(0,0,0,0.2);
        }
        .card-header {
            background: none;
            border: none;
            padding-bottom: 0;
        }
        .login-icon {
            font-size: 3rem;
            color: #2980b9;
        }
        .form-control {
            border-radius: 10px;
            padding: 12px;
        }
        .btn-login {
            border-radius: 10px;
            padding: 12px;
            font-weight: bold;
            background: linear-gradient(120deg, #2980b9, #8e44ad);
            border: none;
        }
        .btn-login:hover {
            background: linear-gradient(120deg, #3498db, #9b59b6);
            transform: translateY(-2px);
            transition: all 0.3s;
        }
        .alert {
            border-radius: 10px;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-6 col-lg-4">
                    <div class="card">
                        <div class="card-header text-center pt-4">
                            <i class="fas fa-parking login-icon mb-3"></i>
                            <h4 class="mb-4">Sistema de Parqueadero</h4>
                        </div>
                        <div class="card-body p-4">
                            <?php if (isset($error)): ?>
                                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                    <i class="fas fa-exclamation-circle me-2"></i>
                                    <?php echo htmlspecialchars($error); ?>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                            <?php endif; ?>

                            <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                                <div class="mb-3">
                                    <label class="form-label">
                                        <i class="fas fa-envelope me-2"></i>Email
                                    </label>
                                    <input type="email" class="form-control" name="email" 
                                           value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                                           required autocomplete="email">
                                </div>
                                <div class="mb-4">
                                    <label class="form-label">
                                        <i class="fas fa-lock me-2"></i>Contraseña
                                    </label>
                                    <input type="password" class="form-control" name="password" 
                                           required autocomplete="current-password">
                                </div>
                                <div class="d-grid">
                                    <button type="submit" class="btn btn-primary btn-login">
                                        <i class="fas fa-sign-in-alt me-2"></i>Iniciar Sesión
                                    </button>
                                </div>
                            </form>
                        </div>
                        <div class="card-footer text-center bg-white py-3">
                            <small class="text-muted">
                                Sistema de Gestión de Parqueadero &copy; <?php echo date('Y'); ?>
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
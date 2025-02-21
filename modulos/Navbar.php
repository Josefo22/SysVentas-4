<!-- Sidebar -->
<div class="col-md-3 col-lg-2 d-md-block sidebar collapse">
    <div class="position-sticky pt-0" style="margin-top: -10px;">
        <!-- Logo y Título -->
        <div class="text-center mb-3">
            <i class="fas fa-parking fa-3x text-white mb-2"></i>
            <h5 class="text-white">Sistema de Parqueadero</h5>
        </div>

        <!-- Menú de Navegación -->
        <ul class="nav flex-column">
            <li class="nav-item mb-2">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>" 
                   href="/SysParqueo/dashboard.php">
                    <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                </a>
            </li>
            <li class="nav-item mb-2">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'registro.php' ? 'active' : ''; ?>" 
                   href="/SysParqueo/registro.php">
                    <i class="fas fa-car me-2"></i>Registro
                </a>
            </li>
            <li class="nav-item mb-2">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'lugares.php' ? 'active' : ''; ?>" 
                   href="/SysParqueo/modulos/lugares.php">
                    <i class="fas fa-parking me-2"></i>Lugares
                </a>
            </li>
            <li class="nav-item mb-2">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'clientes.php' ? 'active' : ''; ?>" 
                   href="/SysParqueo/modulos/clientes.php">
                    <i class="fas fa-users me-2"></i>Clientes
                </a>
            </li>
            <li class="nav-item mb-2">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'pagos.php' ? 'active' : ''; ?>" 
                   href="/SysParqueo/modulos/pagos.php">
                    <i class="fas fa-money-bill me-2"></i>Pagos
                </a>
            </li>
            <li class="nav-item mb-2">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'tarifas.php' ? 'active' : ''; ?>" 
                   href="/SysParqueo/modulos/tarifas.php">
                    <i class="fas fa-tags me-2"></i>Tarifas
                </a>
            </li>
            <!-- Cerrar Sesión -->
            <li class="nav-item mt-3">
                <a class="nav-link text-danger" href="/SysParqueo/logout.php">
                    <i class="fas fa-sign-out-alt me-2"></i>Salir
                </a>
            </li>
        </ul>
    </div>
</div>

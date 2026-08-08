<?php 
require 'inc/config.php';
redirigirLogin();

// ==================== FUNCIONES PARA FECHA EN ESPAÑOL ====================
function obtenerMes($mes) {
    $meses = [
        1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
        5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
        9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
    ];
    return $meses[$mes] ?? 'Mes';
}

function obtenerDia($dia) {
    $dias = [
        0 => 'Domingo', 1 => 'Lunes', 2 => 'Martes', 3 => 'Miércoles',
        4 => 'Jueves', 5 => 'Viernes', 6 => 'Sábado'
    ];
    return $dias[$dia] ?? 'Día';
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard • Escuela Andrés Castro 2025</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="css/style.css?v=<?= time() ?>">
</head>
<body class="bg-light">

<!-- Botón menú móvil -->
<button class="btn-menu-toggle" id="btnMenu">
    <i class="bi bi-list"></i>
</button>

<!-- Fondo oscuro al abrir el menú -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<div class="container-fluid p-0">
    <div class="row g-0">
        <!-- SIDEBAR -->
        <div class="col-lg-3 col-xl-2 sidebar text-white" id="sidebar">
            <div class="text-center py-4 border-bottom border-light border-opacity-25">
                <img src="img/logo_andrescastro.jpg" alt="Logo Escuela" class="img-fluid" style="max-height: 90px;">
                <h5 class="mt-3 mb-0 fw-bold">Andrés Castro</h5>
                <small class="opacity-80">Tola • Rivas • 2025</small>
            </div>

            <div class="p-3">
                <!-- Usuario actual -->
                <div class="user-card text-white text-center mb-4 shadow-lg">
                    <i class="bi bi-person-circle fs-1"></i>
                    <h6 class="mt-3 mb-1 fw-bold"><?= h($_SESSION['nombre']) ?></h6>
                    <span class="badge <?= $_SESSION['rol']=='admin' ? 'bg-danger' : ($_SESSION['rol']=='maestro' ? 'bg-primary' : 'bg-success') ?> fs-6">
                        <?= $_SESSION['rol']=='admin' ? 'Administrador' : ($_SESSION['rol']=='maestro' ? 'Profesor' : 'Estudiante') ?>
                    </span>
                    <?php if(esEstudiante()): ?>
                        <p class="mb-0 mt-2"><strong><?= $_SESSION['grado'] ?>° Año</strong></p>
                    <?php endif; ?>
                </div>

                <!-- Menú -->
                <nav class="nav flex-column">
                    <?php if(esAdmin() || esMaestro()): ?>
                        <a href="admin/asignar_materias.php" class="nav-link">
                            <i class="bi bi-journal-check"></i> Asignar Materias
                        </a>
                        <a href="teacher/ingresar_notas.php" class="nav-link">
                            <i class="bi bi-clipboard-check"></i> Ingresar Notas
                        </a>
                        <a href="admin/matricular_estudiantes.php" class="nav-link">
                            <i class="bi bi-person-plus"></i> Matricular Estudiantes
                        </a>
                    <?php endif; ?>

                    <?php if(esAdmin()): ?>
                        <hr class="border-light opacity-50 my-3">
                        <a href="admin/usuarios.php" class="nav-link">
                            <i class="bi bi-people"></i> Gestión de Usuarios
                        </a>
                        <a href="admin/auditoria.php" class="nav-link">
                            <i class="bi bi-shield-lock"></i> Auditoría del Sistema
                        </a>
                    <?php endif; ?>

                    <?php if(esEstudiante()): ?>
                        <a href="student/mis_notas.php" class="nav-link">
                            <i class="bi bi-file-earmark-text"></i> Mis Notas
                        </a>
                        <a href="student/horario.php" class="nav-link">
                            <i class="bi bi-calendar3"></i> Mi Horario
                        </a>
                    <?php endif; ?>

                    <hr class="border-light opacity-50 my-3">

                    <a href="cambiar_password.php" class="nav-link">
                        <i class="bi bi-key"></i> Cambiar Contraseña
                    </a>
                    
                    <a href="logout.php" class="nav-link text-danger fw-bold">
                        <i class="bi bi-box-arrow-right"></i> Cerrar Sesión
                    </a>
                </nav>
            </div>
        </div>

        <!-- CONTENIDO PRINCIPAL -->
        <div class="col-lg-9 col-xl-10" style="margin-left: auto;">
            <div class="p-4 p-md-5">
                <!-- Bienvenida -->
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-center mb-5">
                    <div>
                        <h1 class="display-5 fw-bold text-primary mb-2">
                            Bienvenid@, <?= explode(" ", h($_SESSION['nombre']))[0] ?> 
                        </h1>
                        <p class="lead text-muted mb-0">Sistema de Gestión Académica • 2025</p>
                    </div>
                    <img src="img/logo_andrescastro.jpg" alt="Logo" class="img-fluid d-none d-md-block" style="max-height: 90px;">
                </div>

                <!-- Tarjetas -->
                <div class="row g-4 mb-5">
                    <?php if(esAdmin()): ?>
                        <div class="col-md-6 col-lg-4">
                            <div class="card card-hover border-0 h-100 text-white bg-admin">
                                <div class="card-body text-center position-relative overflow-hidden">
                                    <i class="bi bi-person-gear position-absolute opacity-10" style="font-size:8rem; right:-20px; bottom:-30px;"></i>
                                    <h3 class="fw-bold">Administrador</h3>
                                    <p>Control total del sistema</p>
                                </div>
                            </div>
                        </div>
                    <?php elseif(esMaestro()): ?>
                        <div class="col-md-6 col-lg-4">
                            <div class="card card-hover border-0 h-100 text-white bg-maestro">
                                <div class="card-body text-center">
                                    <i class="bi bi-easel2 position-absolute opacity-10" style="font-size:8rem; right:-20px; bottom:-30px;"></i>
                                    <h3 class="fw-bold">Profesor</h3>
                                    <p>Gestión de notas y horario</p>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="col-md-6 col-lg-4">
                            <div class="card card-hover border-0 h-100 text-white bg-estudiante">
                                <div class="card-body text-center">
                                    <i class="bi bi-mortarboard position-absolute opacity-10" style="font-size:8rem; right:-20px; bottom:-30px;"></i>
                                    <h3 class="fw-bold">Estudiante <?= $_SESSION['grado'] ?>°</h3>
                                    <p>Consulta tus calificaciones</p>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Fecha -->
                    <div class="col-md-6 col-lg-4">
                        <div class="card card-hover border-0 h-100">
                            <div class="card-body text-center">
                                <h2 class="text-primary fw-bold"><?= date('d') ?></h2>
                                <p class="fs-3 fw-bold"><?= obtenerMes(date('n')) ?> <?= date('Y') ?></p>
                                <p class="text-muted fw-bold"><?= obtenerDia(date('w')) ?></p>
                            </div>
                        </div>
                    </div>

                    <!-- Reloj -->
                    <div class="col-md-6 col-lg-4">
                        <div class="card card-hover border-0 h-100">
                            <div class="card-body text-center">
                                <i class="bi bi-clock fs-1 text-primary mb-3"></i>
                                <h4 id="reloj" class="mb-0"></h4>
                                <small class="text-muted">Hora actual - Nicaragua</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Crédito -->
                <div class="text-center mt-5 pt-4 border-top border-light">
                    <p class="text-muted mb-2">
                        <strong>Sistema desarrollado por:</strong><br>
                        EDU+
                    </p>
                    <small class="text-muted">Ingeniería en Sistemas • Tola, Rivas • 2025</small>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Reloj
    function actualizarReloj() {
        const ahora = new Date();
        document.getElementById('reloj').innerHTML = ahora.toLocaleTimeString('es-NI', {hour12: false});
    }
    setInterval(actualizarReloj, 1000);
    actualizarReloj();

    // Menú responsive
    const btnMenu = document.getElementById('btnMenu');
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');

    if (btnMenu && sidebar && overlay) {
        btnMenu.addEventListener('click', function () {
            sidebar.classList.toggle('active');
            overlay.classList.toggle('active');
        });

        overlay.addEventListener('click', function () {
            sidebar.classList.remove('active');
            overlay.classList.remove('active');
        });
    }
</script>
</body>
</html>
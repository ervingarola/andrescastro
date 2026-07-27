<?php 
require '../inc/config.php'; 
redirigirLogin(); 

if (!esAdmin() && !esMaestro()) {
    die("<h1 class='text-danger text-center mt-5'>Acceso denegado</h1>");
}

$maestro_id = $_SESSION['user_id'];
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Ingresar Notas Académicas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body class="bg-light">

<div class="container mt-5 pt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="text-primary fw-bold">Ingresar Notas Académicas</h2>
        <a href="../dashboard.php" class="btn btn-outline-secondary">Volver al Dashboard</a>
    </div>

    <?php
    try {
        echo "<!-- Debug: Maestro ID = " . $maestro_id . " -->";

        $stmt = $conn->prepare("
            SELECT a.id AS asignacion_id, m.nombre AS materia, a.grado 
            FROM asignaciones a 
            JOIN materias m ON a.materia_id = m.id 
            WHERE a.maestro_id = ? 
            ORDER BY a.grado, m.nombre
        ");
        $stmt->execute([$maestro_id]);
        $asignaciones = $stmt->fetchAll();

        echo "<p class='text-muted'>Asignaciones encontradas: " . count($asignaciones) . "</p>";

        if (empty($asignaciones)) {
            echo "<div class='alert alert-warning text-center p-5'>
                    <h4>Aún no tienes materias asignadas</h4>
                    <p>El Administrador debe asignarte materias primero.</p>
                    </div>";
        } else {
            foreach ($asignaciones as $asig) {
                $asignacion_id = $asig['asignacion_id'];
                
                $countStmt = $conn->prepare("SELECT COUNT(*) FROM matricula_estudiantes WHERE asignacion_id = ?");
                $countStmt->execute([$asignacion_id]);
                $totalEstudiantes = $countStmt->fetchColumn();
                ?>
                <div class="card shadow mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5><?= h($asig['materia']) ?> — <?= $asig['grado'] ?>° Grado</h5>
                    </div>
                    <div class="card-body text-center py-5">
                        <?php if ($totalEstudiantes == 0): ?>
                            <i class="bi bi-people text-muted" style="font-size: 4rem;"></i>
                            <h5 class="mt-3 text-muted">Aún no hay estudiantes matriculados</h5>
                            <p class="text-muted">El Administrador debe matricular estudiantes primero.</p>
                        <?php else: ?>
                            <p class="text-success fw-bold">Hay <?= $totalEstudiantes ?> estudiantes matriculados.</p>
                            <a href="ingresar_notas_detalle.php?asignacion=<?= $asignacion_id ?>" 
                                class="btn btn-success btn-lg">
                                <i class="bi bi-pencil-square"></i> Ingresar Notas
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php
            }
        }
    } catch (Exception $e) {
        echo "<div class='alert alert-danger'>
                <strong>Error en ingresar_notas.php:</strong><br>
                " . h($e->getMessage()) . "
                </div>";
    }
    ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
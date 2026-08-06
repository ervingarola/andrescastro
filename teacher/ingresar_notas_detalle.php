<?php 
require '../inc/config.php'; 
redirigirLogin(); 

if (!esAdmin() && !esMaestro()) {
    die("<h1 class='text-danger text-center mt-5'>Acceso denegado</h1>");
}

$asignacion_id = (int)($_GET['asignacion'] ?? 0);
$mensaje = "";

if ($asignacion_id === 0) {
    die("<h1 class='text-danger text-center mt-5'>Asignación no válida</h1>");
}

// Información de la asignación
$stmt = $conn->prepare("
    SELECT m.nombre AS materia, a.grado, a.aula, u.nombre_completo AS profesor, a.maestro_id
    FROM asignaciones a 
    JOIN materias m ON a.materia_id = m.id 
    JOIN usuarios u ON a.maestro_id = u.id 
    WHERE a.id = ?
");
$stmt->execute([$asignacion_id]);
$asignacion = $stmt->fetch();

if (!$asignacion) {
    die("<h1 class='text-danger text-center mt-5'>Asignación no encontrada</h1>");
}

// Solo el admin o el profesor dueño pueden entrar
if (!esAdmin() && $asignacion['maestro_id'] != $_SESSION['user_id']) {
    die("<h1 class='text-danger text-center mt-5'>No tienes permiso para esta asignación</h1>");
}

// ==================== PROCESAR GUARDADO ====================
if ($_POST && isset($_POST['guardar_notas'])) {
    try {
        $conn->beginTransaction();
        
        foreach ($_POST['notas'] as $matricula_id => $valores) {
            $p1 = (isset($valores['p1']) && $valores['p1'] !== '') ? (float)$valores['p1'] : null;
            $p2 = (isset($valores['p2']) && $valores['p2'] !== '') ? (float)$valores['p2'] : null;
            $p3 = (isset($valores['p3']) && $valores['p3'] !== '') ? (float)$valores['p3'] : null;
            $p4 = (isset($valores['p4']) && $valores['p4'] !== '') ? (float)$valores['p4'] : null;

            // Calcular promedio solo con los períodos que tengan nota
            $notasValidas = array_filter([$p1, $p2, $p3, $p4], function($n) {
                return $n !== null;
            });

            $promedio = null;
            if (count($notasValidas) > 0) {
                $promedio = round(array_sum($notasValidas) / count($notasValidas), 2);
            }

            $stmt = $conn->prepare("
                MERGE INTO notas AS target
                USING (VALUES (?, ?, ?, ?, ?, ?)) AS source (matricula_id, p1, p2, p3, p4, promedio)
                ON target.matricula_id = source.matricula_id
                WHEN MATCHED THEN 
                    UPDATE SET 
                        periodo1 = source.p1, 
                        periodo2 = source.p2, 
                        periodo3 = source.p3,
                        periodo4 = source.p4,
                        promedio_final = source.promedio
                WHEN NOT MATCHED THEN 
                    INSERT (matricula_id, periodo1, periodo2, periodo3, periodo4, promedio_final)
                    VALUES (source.matricula_id, source.p1, source.p2, source.p3, source.p4, source.promedio);
            ");
            $stmt->execute([$matricula_id, $p1, $p2, $p3, $p4, $promedio]);
        }
        
        $conn->commit();
        $mensaje = "<div class='alert alert-success alert-dismissible fade show'>
                        <i class='bi bi-check-circle-fill'></i> ¡Notas guardadas correctamente!
                        <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
                    </div>";
    } catch (Exception $e) {
        $conn->rollBack();
        $mensaje = "<div class='alert alert-danger'>Error al guardar notas: " . h($e->getMessage()) . "</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Notas - <?= h($asignacion['materia']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body class="bg-notas">

<div class="container mt-5 pt-4">
    <!-- Encabezado -->
    <div class="notas-header">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h2 class="mb-1"><?= h($asignacion['materia']) ?></h2>
                <p class="mb-0 opacity-90">
                    <?= $asignacion['grado'] ?>° Grado
                    <?php if ($asignacion['aula']): ?>
                        • Aula <?= h($asignacion['aula']) ?>
                    <?php endif; ?>
                </p>
            </div>
            <a href="ingresar_notas.php" class="btn btn-light btn-sm">
                <i class="bi bi-arrow-left"></i> Volver
            </a>
        </div>
    </div>

    <div class="info-profesor">
        <i class="bi bi-person-badge"></i>
        <strong>Profesor:</strong> <?= h($asignacion['profesor']) ?>
    </div>

    <?= $mensaje ?>

    <form method="post">
        <input type="hidden" name="guardar_notas" value="1">

        <div class="table-responsive tabla-notas-detalle">
            <table class="table table-bordered table-hover mb-0">
                <thead>
                    <tr>
                        <th class="text-start ps-3">Estudiante</th>
                        <th>1° Corte</th>
                        <th>2° Corte</th>
                        <th>3° Corte</th>
                        <th>4° Corte</th>
                        <th>Promedio</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $stmt = $conn->prepare("
                        SELECT me.id AS matricula_id, u.nombre_completo 
                        FROM matricula_estudiantes me
                        JOIN usuarios u ON me.estudiante_id = u.id
                        WHERE me.asignacion_id = ?
                        ORDER BY u.nombre_completo
                    ");
                    $stmt->execute([$asignacion_id]);
                    $hayEstudiantes = false;

                    while ($est = $stmt->fetch()):
                        $hayEstudiantes = true;
                        $nStmt = $conn->prepare("
                            SELECT periodo1, periodo2, periodo3, periodo4, promedio_final 
                            FROM notas WHERE matricula_id = ?
                        ");
                        $nStmt->execute([$est['matricula_id']]);
                        $nota = $nStmt->fetch();

                        $promedio = $nota['promedio_final'] ?? null;
                        $estado = '';
                        $badge = '';

                        if ($promedio !== null) {
                            if ($promedio >= 60) {
                                $estado = 'Aprobado';
                                $badge = 'bg-success';
                            } else {
                                $estado = 'Reprobado';
                                $badge = 'bg-danger';
                            }
                        }
                    ?>
                    <tr>
                        <td class="fw-semibold ps-3"><?= h($est['nombre_completo']) ?></td>
                        <td class="text-center">
                            <input type="number" step="0.01" min="0" max="100"
                                   name="notas[<?= $est['matricula_id'] ?>][p1]"
                                   value="<?= $nota['periodo1'] ?? '' ?>"
                                   class="form-control input-nota">
                        </td>
                        <td class="text-center">
                            <input type="number" step="0.01" min="0" max="100"
                                   name="notas[<?= $est['matricula_id'] ?>][p2]"
                                   value="<?= $nota['periodo2'] ?? '' ?>"
                                   class="form-control input-nota">
                        </td>
                        <td class="text-center">
                            <input type="number" step="0.01" min="0" max="100"
                                   name="notas[<?= $est['matricula_id'] ?>][p3]"
                                   value="<?= $nota['periodo3'] ?? '' ?>"
                                   class="form-control input-nota">
                        </td>
                        <td class="text-center">
                            <input type="number" step="0.01" min="0" max="100"
                                   name="notas[<?= $est['matricula_id'] ?>][p4]"
                                   value="<?= $nota['periodo4'] ?? '' ?>"
                                   class="form-control input-nota">
                        </td>
                        <td class="text-center fw-bold">
                            <?= $promedio !== null ? number_format($promedio, 2) : '—' ?>
                        </td>
                        <td class="text-center">
                            <?php if ($estado): ?>
                                <span class="badge <?= $badge ?>"><?= $estado ?></span>
                            <?php else: ?>
                                <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>

                    <?php if (!$hayEstudiantes): ?>
                        <tr>
                            <td colspan="7" class="text-center text-muted py-5">
                                No hay estudiantes matriculados en esta asignación.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if ($hayEstudiantes): ?>
            <div class="text-center mt-4 mb-5">
                <button type="submit" class="btn btn-success btn-guardar-notas">
                    <i class="bi bi-save"></i> Guardar Todas las Notas
                </button>
            </div>
        <?php endif; ?>
    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php
require '../inc/config.php';
redirigirLogin();
if (!esAdmin() && !esMaestro()) {
    die("<h1 class='text-danger text-center mt-5'>Acceso denegado</h1>");
}
$isAdmin = esAdmin();
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
<body class="bg-notas">
<div class="container mt-5 pt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="text-primary fw-bold">
            <?= $isAdmin ? 'Ingresar Notas (Administrador)' : 'Ingresar Notas Académicas' ?>
        </h2>
        <a href="../dashboard.php" class="btn btn-outline-secondary">Volver al Dashboard</a>
    </div>
    <?php
    try {
        if ($isAdmin) {
            $stmt = $conn->query("
                SELECT a.id AS asignacion_id, m.nombre AS materia, a.grado, a.aula,
                        u.nombre_completo AS profesor
                FROM asignaciones a
                JOIN materias m ON a.materia_id = m.id
                JOIN usuarios u ON a.maestro_id = u.id
                ORDER BY CAST(a.grado AS INT), a.aula, m.nombre
            ");
        } else {
            $stmt = $conn->prepare("
                SELECT a.id AS asignacion_id, m.nombre AS materia, a.grado, a.aula,
                        u.nombre_completo AS profesor
                FROM asignaciones a
                JOIN materias m ON a.materia_id = m.id
                JOIN usuarios u ON a.maestro_id = u.id
                WHERE a.maestro_id = ?
                ORDER BY CAST(a.grado AS INT), a.aula, m.nombre
            ");
            $stmt->execute([$_SESSION['user_id']]);
        }
        $asignaciones = $stmt->fetchAll();
        if (empty($asignaciones)) {
            echo "<div class='alert alert-warning text-center p-5'>
                    <h4>Aún no hay materias asignadas</h4>
                    <p>Primero debes crear asignaciones en <strong>Asignar Materias</strong>.</p>
                    </div>";
        } else {
            // Agrupar por grado
            $porGrado = [];
            foreach ($asignaciones as $asig) {
                $porGrado[$asig['grado']][] = $asig;
            }

            // Ordenar los grados de menor a mayor (7 → 11)
            ksort($porGrado, SORT_NUMERIC);

            foreach ($porGrado as $grado => $lista): ?>
                <div class="card shadow mb-4">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0"><?= $grado ?>° Grado</h4>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0 align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Materia</th>
                                        <th>Aula</th>
                                        <th>Profesor</th>
                                        <th>Estudiantes</th>
                                        <th class="text-center">Acción</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($lista as $asig):
                                        $countStmt = $conn->prepare("SELECT COUNT(*) FROM matricula_estudiantes WHERE asignacion_id = ?");
                                        $countStmt->execute([$asig['asignacion_id']]);
                                        $total = $countStmt->fetchColumn();
                                    ?>
                                    <tr>
                                        <td class="fw-bold"><?= h($asig['materia']) ?></td>
                                        <td>
                                            <span class="badge bg-info text-dark fs-6">
                                                <?= h($asig['aula'] ?: 'Sin aula') ?>
                                            </span>
                                        </td>
                                        <td><?= h($asig['profesor']) ?></td>
                                        <td>
                                            <?php if ($total > 0): ?>
                                                <span class="text-success fw-bold"><?= $total ?> matriculados</span>
                                            <?php else: ?>
                                                <span class="text-muted">0 matriculados</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <?php if ($total > 0): ?>
                                                <a href="ingresar_notas_detalle.php?asignacion=<?= $asig['asignacion_id'] ?>"
                                                    class="btn btn-success btn-sm">
                                                    <i class="bi bi-pencil-square"></i> Ingresar Notas
                                                </a>
                                            <?php else: ?>
                                                <button class="btn btn-secondary btn-sm" disabled>
                                                    Sin estudiantes
                                                </button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php endforeach;
        }
    } catch (Exception $e) {
        echo "<div class='alert alert-danger'>
                <strong>Error:</strong><br>" . h($e->getMessage()) . "
                </div>";
    }
    ?>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
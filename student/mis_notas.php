<?php 
require '../inc/config.php'; 
redirigirLogin(); 
if(!esEstudiante()) die("<h1 class='text-danger text-center mt-5'>Acceso denegado</h1>");

$estudiante_id = $_SESSION['user_id'];
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Mis Notas Académicas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body class="bg-light">

<div class="container mt-5 pt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="text-primary fw-bold">Mis Notas Académicas</h2>
        <a href="../dashboard.php" class="btn btn-outline-secondary">Volver al Dashboard</a>
    </div>

    <div class="card shadow-lg border-0">
        <div class="card-header bg-primary text-white text-center">
            <h4 class="mb-0">Reporte de Calificaciones - Año 2025</h4>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th>Materia</th>
                            <th class="text-center">Período 1</th>
                            <th class="text-center">Período 2</th>
                            <th class="text-center">Período 3</th>
                            <th class="text-center">Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        try {
                            $stmt = $conn->prepare("
                                SELECT 
                                    m.nombre AS materia,
                                    COALESCE(n.periodo1, 0) AS periodo1,
                                    COALESCE(n.periodo2, 0) AS periodo2,
                                    COALESCE(n.periodo3, 0) AS periodo3
                                FROM matricula_estudiantes me
                                JOIN asignaciones a ON me.asignacion_id = a.id
                                JOIN materias m ON a.materia_id = m.id
                                LEFT JOIN notas n ON n.matricula_id = me.id
                                WHERE me.estudiante_id = ?
                                ORDER BY m.nombre
                            ");
                            $stmt->execute([$estudiante_id]);
                            $notas = $stmt->fetchAll();

                            if (empty($notas)) {
                                echo '<tr><td colspan="5" class="text-center py-5 text-muted">Aún no tienes notas registradas.</td></tr>';
                            } else {
                                foreach ($notas as $row) {
                                    $p1 = (float)$row['periodo1'];
                                    $p2 = (float)$row['periodo2'];
                                    $p3 = (float)$row['periodo3'];

                                    // Determinar estado
                                    if ($p1 < 60 || $p2 < 60 || $p3 < 60) {
                                        $estado = "Reparación";
                                        $badge = "bg-warning text-dark";
                                    } elseif ($p1 == 0 && $p2 == 0 && $p3 == 0) {
                                        $estado = "Sin notas";
                                        $badge = "bg-secondary";
                                    } else {
                                        $estado = "Aprobado";
                                        $badge = "bg-success";
                                    }
                                    ?>
                                    <tr>
                                        <td class="fw-bold"><?= h($row['materia']) ?></td>
                                        <td class="text-center"><?= $p1 > 0 ? $p1 : '-' ?></td>
                                        <td class="text-center"><?= $p2 > 0 ? $p2 : '-' ?></td>
                                        <td class="text-center"><?= $p3 > 0 ? $p3 : '-' ?></td>
                                        <td class="text-center"><span class="badge <?= $badge ?> fs-6"><?= $estado ?></span></td>
                                    </tr>
                                    <?php
                                }
                            }
                        } catch (Exception $e) {
                            echo '<tr><td colspan="5" class="text-danger text-center py-4">Error: ' . h($e->getMessage()) . '</td></tr>';
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
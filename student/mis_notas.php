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
<body class="bg-notas">

<div class="container mt-5 pt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="text-primary fw-bold mb-1">Mis Notas Académicas</h2>
            <p class="text-muted mb-0">Reporte de calificaciones • Año 2025</p>
        </div>
        <a href="../dashboard.php" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Dashboard
        </a>
    </div>

    <div class="card shadow-lg border-0">
        <div class="card-header bg-primary text-white text-center">
            <h4 class="mb-0">Boletín de Calificaciones</h4>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th>Materia</th>
                            <th class="text-center">1° Corte</th>
                            <th class="text-center">2° Corte</th>
                            <th class="text-center">3° Corte</th>
                            <th class="text-center">4° Corte</th>
                            <th class="text-center">Promedio</th>
                            <th class="text-center">Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        try {
                            $stmt = $conn->prepare("
                                SELECT 
                                    m.nombre AS materia,
                                    n.periodo1,
                                    n.periodo2,
                                    n.periodo3,
                                    n.periodo4,
                                    n.promedio_final
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
                                echo '<tr><td colspan="7" class="text-center py-5 text-muted">
                                        Aún no tienes notas registradas.
                                      </td></tr>';
                            } else {
                                foreach ($notas as $row) {
                                    $p1 = $row['periodo1'];
                                    $p2 = $row['periodo2'];
                                    $p3 = $row['periodo3'];
                                    $p4 = $row['periodo4'];
                                    $promedio = $row['promedio_final'];

                                    // Si no hay promedio guardado, calcularlo
                                    if ($promedio === null) {
                                        $validas = array_filter([$p1, $p2, $p3, $p4], function($n) {
                                            return $n !== null;
                                        });
                                        if (count($validas) > 0) {
                                            $promedio = round(array_sum($validas) / count($validas), 2);
                                        }
                                    }

                                    // Estado
                                    if ($promedio === null) {
                                        $estado = "Sin notas";
                                        $badge = "bg-secondary";
                                    } elseif ($promedio >= 60) {
                                        $estado = "Aprobado";
                                        $badge = "bg-success";
                                    } else {
                                        $estado = "Reprobado";
                                        $badge = "bg-danger";
                                    }
                                    ?>
                                    <tr>
                                        <td class="fw-bold"><?= h($row['materia']) ?></td>
                                        <td class="text-center"><?= $p1 !== null ? number_format($p1, 2) : '—' ?></td>
                                        <td class="text-center"><?= $p2 !== null ? number_format($p2, 2) : '—' ?></td>
                                        <td class="text-center"><?= $p3 !== null ? number_format($p3, 2) : '—' ?></td>
                                        <td class="text-center"><?= $p4 !== null ? number_format($p4, 2) : '—' ?></td>
                                        <td class="text-center fw-bold">
                                            <?= $promedio !== null ? number_format($promedio, 2) : '—' ?>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge <?= $badge ?> fs-6"><?= $estado ?></span>
                                        </td>
                                    </tr>
                                    <?php
                                }
                            }
                        } catch (Exception $e) {
                            echo '<tr><td colspan="7" class="text-danger text-center py-4">Error: ' . h($e->getMessage()) . '</td></tr>';
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer text-center bg-white">
            <small class="text-muted">
                Escala de evaluación: <strong>60 o más = Aprobado</strong> • Menos de 60 = Reprobado
            </small>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
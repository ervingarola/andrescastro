<?php 
require '../inc/config.php'; 
redirigirLogin(); 
if(!esEstudiante()) die("<h1 class='text-danger text-center mt-5'>Acceso denegado</h1>");

$estudiante_id = $_SESSION['user_id'];
$grado = $_SESSION['grado'] ?? '';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Mi Horario - <?= $grado ?>° Grado</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body class="bg-light">

<div class="container mt-5 pt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="text-primary fw-bold">Mi Horario Académico - <?= h($grado) ?>° Grado</h2>
        <a href="../dashboard.php" class="btn btn-outline-secondary">Volver al Dashboard</a>
    </div>

    <div class="card shadow-lg border-0">
        <div class="card-header bg-primary text-white text-center">
            <h4 class="mb-0">Horario Semanal • Año 2025</h4>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered horario-table text-center mb-0">
                    <thead>
                        <tr>
                            <th width="15%">Hora</th>
                            <th>Lunes</th>
                            <th>Martes</th>
                            <th>Miércoles</th>
                            <th>Jueves</th>
                            <th>Viernes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        try {
                            // Obtener horario del estudiante a través de sus matrículas
                            $stmt = $conn->prepare("
                                SELECT DISTINCT h.hora_inicio, h.hora_fin 
                                FROM horario h
                                JOIN asignaciones a ON h.asignacion_id = a.id
                                JOIN matricula_estudiantes me ON me.asignacion_id = a.id
                                WHERE me.estudiante_id = ?
                                ORDER BY h.hora_inicio
                            ");
                            $stmt->execute([$estudiante_id]);
                            $horas = $stmt->fetchAll();

                            if (empty($horas)) {
                                echo '<tr><td colspan="6" class="text-center py-5 text-muted fs-4">
                                        Aún no tienes horario asignado.<br>
                                        <small>El administrador debe crear el horario y matricularte.</small>
                                      </td></tr>';
                            } else {
                                $dias = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes'];
                                foreach ($horas as $h) {
                                    $inicio = substr($h['hora_inicio'], 0, 5);
                                    $fin = substr($h['hora_fin'], 0, 5);
                                    echo "<tr><td class='hora fw-bold'>$inicio - $fin</td>";

                                    foreach ($dias as $dia) {
                                        $stmt2 = $conn->prepare("
                                            SELECT m.nombre AS materia, u.nombre_completo AS profesor
                                            FROM horario h
                                            JOIN asignaciones a ON h.asignacion_id = a.id
                                            JOIN materias m ON a.materia_id = m.id
                                            JOIN usuarios u ON a.maestro_id = u.id
                                            JOIN matricula_estudiantes me ON me.asignacion_id = a.id
                                            WHERE me.estudiante_id = ? 
                                            AND h.dia = ? 
                                            AND h.hora_inicio = ?
                                        ");
                                        $stmt2->execute([$estudiante_id, $dia, $h['hora_inicio']]);
                                        $clase = $stmt2->fetch();

                                        if ($clase) {
                                            echo "<td>
                                                <div class='materia'>" . h($clase['materia']) . "</div>
                                                <div class='profesor'>Prof. " . h($clase['profesor']) . "</div>
                                            </td>";
                                        } else {
                                            echo "<td class='text-muted'>—</td>";
                                        }
                                    }
                                    echo "</tr>";
                                }
                            }
                        } catch (Exception $e) {
                            echo '<tr><td colspan="6" class="text-danger text-center py-4">Error al cargar el horario: ' . h($e->getMessage()) . '</td></tr>';
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer text-center bg-white">
            <small class="text-muted">Escuela Secundaria Andrés Castro • Sistema de Gestión Académica</small>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
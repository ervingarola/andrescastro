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
                            // Obtener todas las horas distintas de las asignaciones del estudiante
                            $stmt = $conn->prepare("
                                SELECT DISTINCT a.hora
                                FROM asignaciones a
                                JOIN matricula_estudiantes me ON me.asignacion_id = a.id
                                WHERE me.estudiante_id = ?
                                ORDER BY a.hora
                            ");
                            $stmt->execute([$estudiante_id]);
                            $horas = $stmt->fetchAll(PDO::FETCH_COLUMN);

                            if (empty($horas)) {
                                echo '<tr><td colspan="6" class="text-center py-5 text-muted fs-4">
                                        Aún no tienes horario asignado.<br>
                                        <small>El administrador o profesor debe matricularte en las asignaciones.</small>
                                      </td></tr>';
                            } else {
                                $dias = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes'];

                                foreach ($horas as $hora) {
                                    echo "<tr><td class='hora fw-bold'>" . h($hora) . "</td>";

                                    foreach ($dias as $dia) {
                                        $stmt2 = $conn->prepare("
                                            SELECT m.nombre AS materia, u.nombre_completo AS profesor, a.aula
                                            FROM asignaciones a
                                            JOIN materias m ON a.materia_id = m.id
                                            JOIN usuarios u ON a.maestro_id = u.id
                                            JOIN matricula_estudiantes me ON me.asignacion_id = a.id
                                            WHERE me.estudiante_id = ? 
                                                AND a.dia = ? 
                                                AND a.hora = ?
                                        ");
                                        $stmt2->execute([$estudiante_id, $dia, $hora]);
                                        $clase = $stmt2->fetch();

                                        if ($clase) {
                                            echo "<td>
                                                <div class='materia fw-bold'>" . h($clase['materia']) . "</div>
                                                <div class='profesor small text-muted'>Prof. " . h($clase['profesor']) . "</div>
                                                <div class='aula small text-primary'>" . h($clase['aula'] ?: '') . "</div>
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
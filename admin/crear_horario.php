<?php 
require '../inc/config.php'; 
redirigirLogin(); 

if (!esAdmin() && !esMaestro()) {
    die("<h1 class='text-danger text-center mt-5'>Acceso denegado</h1>");
}

$mensaje = "";

// ==================== GUARDAR HORARIO ====================
if ($_POST && isset($_POST['guardar_horario'])) {
    $asignacion_id = (int)$_POST['asignacion_id'];
    $dia           = $_POST['dia'];
    $hora_inicio   = $_POST['hora_inicio'];
    $hora_fin      = $_POST['hora_fin'];
    $aula          = trim($_POST['aula'] ?? '');

    if ($asignacion_id && $dia && $hora_inicio && $hora_fin) {
        try {
            // === VALIDACIÓN DE CONFLICTO DE HORARIO ===
            $stmt = $conn->prepare("
                SELECT h.id, m.nombre AS materia_conflicto
                FROM horario h
                JOIN asignaciones a ON h.asignacion_id = a.id
                JOIN materias m ON a.materia_id = m.id
                WHERE a.maestro_id = (SELECT maestro_id FROM asignaciones WHERE id = ?)
                    AND h.dia = ?
                    AND (
                        (h.hora_inicio <= ? AND h.hora_fin > ?)   -- Solapamiento por inicio
                    OR (h.hora_inicio < ? AND h.hora_fin >= ?)    -- Solapamiento por fin
                    OR (h.hora_inicio >= ? AND h.hora_inicio < ?) -- Dentro del nuevo horario
                    )
            ");
            $stmt->execute([$asignacion_id, $dia, $hora_inicio, $hora_inicio, $hora_fin, $hora_fin, $hora_inicio, $hora_fin]);

            if ($stmt->rowCount() > 0) {
                $conflicto = $stmt->fetch();
                $mensaje = "<div class='alert alert-warning'>
                    <strong>¡Conflicto de Horario!</strong><br>
                    El profesor ya tiene clase de <strong>" . h($conflicto['materia_conflicto']) . "</strong> 
                    el día <strong>{$dia}</strong> en ese horario.
                </div>";
            } else {
                // Insertar si no hay conflicto
                $stmt = $conn->prepare("
                    INSERT INTO horario (asignacion_id, dia, hora_inicio, hora_fin, aula)
                    VALUES (?, ?, ?, ?, ?)
                ");
                $stmt->execute([$asignacion_id, $dia, $hora_inicio, $hora_fin, $aula]);
                
                $mensaje = "<div class='alert alert-success'>¡Horario creado correctamente!</div>";
            }
        } catch (PDOException $e) {
            $mensaje = "<div class='alert alert-danger'>Error: " . h($e->getMessage()) . "</div>";
        }
    } else {
        $mensaje = "<div class='alert alert-danger'>Faltan datos obligatorios.</div>";
    }
}

// ==================== ELIMINAR HORARIO ====================
if (isset($_GET['eliminar'])) {
    $id = (int)$_GET['eliminar'];
    try {
        $stmt = $conn->prepare("DELETE FROM horario WHERE id = ?");
        $stmt->execute([$id]);
        $mensaje = "<div class='alert alert-success'>Horario eliminado correctamente.</div>";
    } catch (Exception $e) {
        $mensaje = "<div class='alert alert-danger'>No se pudo eliminar el horario.</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Crear Horario Académico</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body class="bg-light">

<div class="container mt-5 pt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="text-primary fw-bold">Crear Horario Académico</h2>
        <a href="../dashboard.php" class="btn btn-outline-secondary">Volver al Dashboard</a>
    </div>

    <?= $mensaje ?>

    <!-- Formulario -->
    <div class="card shadow mb-5">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0"><i class="bi bi-plus-circle"></i> Agregar Nuevo Horario</h5>
        </div>
        <div class="card-body">
            <form method="post" class="row g-3">
                <input type="hidden" name="guardar_horario" value="1">

                <div class="col-md-5">
                    <label class="form-label">Asignación (Materia - Profesor - Grado)</label>
                    <select name="asignacion_id" class="form-select" required>
                        <option value="">Seleccione una asignación...</option>
                        <?php
                        $stmt = $conn->query("
                            SELECT a.id, m.nombre AS materia, u.nombre_completo AS profesor, a.grado 
                            FROM asignaciones a 
                            JOIN materias m ON a.materia_id = m.id 
                            JOIN usuarios u ON a.maestro_id = u.id 
                            ORDER BY a.grado, m.nombre
                        ");
                        while ($row = $stmt->fetch()) {
                            echo "<option value='{$row['id']}'>
                                {$row['grado']}° - {$row['materia']} → Prof. {$row['profesor']}
                            </option>";
                        }
                        ?>
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label">Día</label>
                    <select name="dia" class="form-select" required>
                        <option value="">Día</option>
                        <option value="Lunes">Lunes</option>
                        <option value="Martes">Martes</option>
                        <option value="Miércoles">Miércoles</option>
                        <option value="Jueves">Jueves</option>
                        <option value="Viernes">Viernes</option>
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label">Hora Inicio</label>
                    <input type="time" name="hora_inicio" class="form-control" required>
                </div>

                <div class="col-md-2">
                    <label class="form-label">Hora Fin</label>
                    <input type="time" name="hora_fin" class="form-control" required>
                </div>

                <div class="col-md-1">
                    <label class="form-label">Aula</label>
                    <input type="text" name="aula" class="form-control" placeholder="A-101">
                </div>

                <div class="col-12 mt-3">
                    <button type="submit" class="btn btn-success btn-lg px-5">
                        <i class="bi bi-save"></i> Guardar Horario
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Lista de Horarios -->
    <h4 class="mb-3">Horarios Registrados</h4>
    <div class="card shadow">
        <div class="card-body p-0">
            <table class="table table-hover mb-0">
                <thead class="table-dark">
                    <tr>
                        <th>Grado</th>
                        <th>Materia</th>
                        <th>Profesor</th>
                        <th>Día</th>
                        <th>Hora</th>
                        <th>Aula</th>
                        <th>Acción</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $stmt = $conn->query("
                        SELECT h.id, a.grado, m.nombre AS materia, u.nombre_completo AS profesor,
                                h.dia, h.hora_inicio, h.hora_fin, h.aula
                        FROM horario h
                        JOIN asignaciones a ON h.asignacion_id = a.id
                        JOIN materias m ON a.materia_id = m.id
                        JOIN usuarios u ON a.maestro_id = u.id
                        ORDER BY a.grado, h.dia, h.hora_inicio
                    ");

                    if ($stmt->rowCount() == 0) {
                        echo "<tr><td colspan='7' class='text-center text-muted py-4'>Aún no hay horarios registrados.</td></tr>";
                    } else {
                        while ($row = $stmt->fetch()) {
                            $hora = substr($row['hora_inicio'], 0, 5) . " - " . substr($row['hora_fin'], 0, 5);
                            echo "<tr>
                                <td><strong>{$row['grado']}°</strong></td>
                                <td>" . h($row['materia']) . "</td>
                                <td>" . h($row['profesor']) . "</td>
                                <td><span class='badge bg-primary'>{$row['dia']}</span></td>
                                <td><strong>{$hora}</strong></td>
                                <td>" . h($row['aula'] ?: '—') . "</td>
                                <td>
                                    <a href='?eliminar={$row['id']}' 
                                        class='btn btn-sm btn-danger'
                                        onclick=\"return confirm('¿Eliminar este horario?')\">Eliminar</a>
                                </td>
                            </tr>";
                        }
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
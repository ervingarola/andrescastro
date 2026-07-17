<?php 
require '../inc/config.php'; 
redirigirLogin(); 
if(!esAdmin()) die("<h1 class='text-danger text-center mt-5'>Acceso denegado</h1>"); 

$mensaje = "";

// ==================== MATRICULAR ESTUDIANTE ====================
if ($_POST && isset($_POST['matricular'])) {
    $estudiante_id = (int)$_POST['estudiante_id'];
    $asignacion_id = (int)$_POST['asignacion_id'];

    if ($estudiante_id && $asignacion_id) {
        try {
            // VALIDACIÓN DE GRADO (Corrección principal)
            $checkStmt = $conn->prepare("
                SELECT u.grado AS grado_estudiante, a.grado AS grado_asignacion, 
                       m.nombre AS materia
                FROM usuarios u 
                JOIN asignaciones a ON a.id = ?
                JOIN materias m ON a.materia_id = m.id
                WHERE u.id = ?
            ");
            $checkStmt->execute([$asignacion_id, $estudiante_id]);
            $data = $checkStmt->fetch();

            if (!$data) {
                $mensaje = "<div class='alert alert-danger'>Datos no encontrados.</div>";
            } elseif ($data['grado_estudiante'] != $data['grado_asignacion']) {
                $mensaje = "<div class='alert alert-danger'>
                    <strong>Error de grado:</strong><br>
                    No se puede matricular un estudiante de <strong>{$data['grado_estudiante']}°</strong> 
                    en la asignatura <strong>{$data['materia']}</strong> de <strong>{$data['grado_asignacion']}°</strong>.
                </div>";
            } else {
                // Insertar matrícula
                $stmt = $conn->prepare("INSERT INTO matricula_estudiantes (estudiante_id, asignacion_id) VALUES (?, ?)");
                $stmt->execute([$estudiante_id, $asignacion_id]);
                $mensaje = "<div class='alert alert-success'>¡Estudiante matriculado correctamente!</div>";
            }
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                $mensaje = "<div class='alert alert-warning'>Este estudiante ya está matriculado en esta asignación.</div>";
            } else {
                $mensaje = "<div class='alert alert-danger'>Error: " . h($e->getMessage()) . "</div>";
            }
        }
    } else {
        $mensaje = "<div class='alert alert-danger'>Debes seleccionar estudiante y asignación.</div>";
    }
}

// ==================== ELIMINAR MATRÍCULA ====================
if (isset($_GET['eliminar'])) {
    $id = (int)$_GET['eliminar'];
    try {
        $stmt = $conn->prepare("DELETE FROM matricula_estudiantes WHERE id = ?");
        $stmt->execute([$id]);
        $mensaje = "<div class='alert alert-success'>Matrícula eliminada correctamente.</div>";
    } catch (Exception $e) {
        $mensaje = "<div class='alert alert-danger'>No se pudo eliminar la matrícula.</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Matricular Estudiantes</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body class="bg-light">

<div class="container mt-5 pt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="text-primary fw-bold">Matricular Estudiantes</h2>
        <a href="../dashboard.php" class="btn btn-outline-secondary">Volver al Dashboard</a>
    </div>

    <?= $mensaje ?>

    <div class="row g-4">
        <!-- Formulario de Matrícula -->
        <div class="col-lg-5">
            <div class="card shadow">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="bi bi-person-plus"></i> Nueva Matrícula</h5>
                </div>
                <div class="card-body">
                    <form method="post">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Estudiante</label>
                            <select name="estudiante_id" class="form-select" required>
                                <option value="">Seleccionar Estudiante</option>
                                <?php
                                $stmt = $conn->query("SELECT id, nombre_completo, grado FROM usuarios WHERE rol = 'estudiante' ORDER BY nombre_completo");
                                while ($row = $stmt->fetch()) {
                                    echo "<option value='{$row['id']}'>{$row['nombre_completo']} — {$row['grado']}°</option>";
                                }
                                ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Asignación (Materia + Profesor + Grado)</label>
                            <select name="asignacion_id" class="form-select" required>
                                <option value="">Seleccionar Asignación</option>
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

                        <button type="submit" name="matricular" class="btn btn-success w-100 btn-lg">
                            Matricular Estudiante
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Lista de Matrículas Actuales -->
        <div class="col-lg-7">
            <h4 class="mb-3">Matrículas Registradas</h4>
            <div class="card shadow">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-dark">
                                <tr>
                                    <th>Estudiante</th>
                                    <th>Grado</th>
                                    <th>Materia</th>
                                    <th>Profesor</th>
                                    <th>Acción</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $stmt = $conn->query("
                                    SELECT me.id, u.nombre_completo AS estudiante, u.grado, 
                                           m.nombre AS materia, p.nombre_completo AS profesor
                                    FROM matricula_estudiantes me
                                    JOIN usuarios u ON me.estudiante_id = u.id
                                    JOIN asignaciones a ON me.asignacion_id = a.id
                                    JOIN materias m ON a.materia_id = m.id
                                    JOIN usuarios p ON a.maestro_id = p.id
                                    ORDER BY u.nombre_completo
                                ");
                                $hayRegistros = false;
                                while ($row = $stmt->fetch()) {
                                    $hayRegistros = true;
                                    echo "<tr>
                                        <td>" . h($row['estudiante']) . "</td>
                                        <td>{$row['grado']}°</td>
                                        <td>" . h($row['materia']) . "</td>
                                        <td>" . h($row['profesor']) . "</td>
                                        <td>
                                            <a href='?eliminar={$row['id']}' 
                                            class='btn btn-sm btn-danger'
                                            onclick=\"return confirm('¿Eliminar esta matrícula?')\">
                                                Eliminar
                                            </a>
                                        </td>
                                    </tr>";
                                }
                                if (!$hayRegistros) {
                                    echo "<tr><td colspan='5' class='text-center text-muted py-4'>Aún no hay estudiantes matriculados.</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
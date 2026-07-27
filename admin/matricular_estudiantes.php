<?php 
require '../inc/config.php'; 
redirigirLogin(); 

if (!esAdmin() && !esMaestro()) {
    die("<h1 class='text-danger text-center mt-5'>Acceso denegado</h1>");
}

$isAdmin = esAdmin();
$mensaje = "";

// ==================== MATRICULAR POR GRADO + AULA ====================
if ($_POST && isset($_POST['matricular'])) {
    $estudiante_id = (int)$_POST['estudiante_id'];
    $grado = $_POST['grado'];
    $aula = trim($_POST['aula'] ?? '');

    if ($estudiante_id && $grado && $aula) {
        try {
            // Verificar estudiante
            $check = $conn->prepare("SELECT grado FROM usuarios WHERE id = ? AND rol = 'estudiante'");
            $check->execute([$estudiante_id]);
            $est = $check->fetch();

            if (!$est) {
                $_SESSION['mensaje'] = "<div class='alert alert-danger'>Estudiante no encontrado.</div>";
            } elseif ($est['grado'] != $grado) {
                $_SESSION['mensaje'] = "<div class='alert alert-danger'>
                    El estudiante es de <strong>{$est['grado']}°</strong>, no se puede matricular en <strong>{$grado}°</strong>.
                </div>";
            } else {
                // Obtener asignaciones del mismo grado + misma aula/sección
                if ($isAdmin) {
                    $stmt = $conn->prepare("SELECT id FROM asignaciones WHERE grado = ? AND aula = ?");
                    $stmt->execute([$grado, $aula]);
                } else {
                    $stmt = $conn->prepare("SELECT id FROM asignaciones WHERE grado = ? AND aula = ? AND maestro_id = ?");
                    $stmt->execute([$grado, $aula, $_SESSION['user_id']]);
                }

                $asignaciones = $stmt->fetchAll(PDO::FETCH_COLUMN);
                $matriculados = 0;

                foreach ($asignaciones as $asignacion_id) {
                    $existe = $conn->prepare("SELECT 1 FROM matricula_estudiantes WHERE estudiante_id = ? AND asignacion_id = ?");
                    $existe->execute([$estudiante_id, $asignacion_id]);

                    if ($existe->rowCount() == 0) {
                        $insert = $conn->prepare("INSERT INTO matricula_estudiantes (estudiante_id, asignacion_id) VALUES (?, ?)");
                        $insert->execute([$estudiante_id, $asignacion_id]);
                        $matriculados++;
                    }
                }

                if ($matriculados > 0) {
                    $_SESSION['mensaje'] = "<div class='alert alert-success'>
                        ¡Estudiante matriculado correctamente en <strong>$matriculados</strong> asignaciones 
                        del grado <strong>{$grado}°</strong> - Aula/Sección <strong>{$aula}</strong>!
                    </div>";
                } else {
                    $_SESSION['mensaje'] = "<div class='alert alert-warning'>
                        El estudiante ya estaba matriculado en todas las asignaciones de este grado y aula.
                    </div>";
                }
            }
        } catch (PDOException $e) {
            $_SESSION['mensaje'] = "<div class='alert alert-danger'>Error: " . h($e->getMessage()) . "</div>";
        }
    } else {
        $_SESSION['mensaje'] = "<div class='alert alert-danger'>Debes seleccionar estudiante, grado y aula/sección.</div>";
    }

    header("Location: matricular_estudiantes.php");
    exit;
}

// ==================== ELIMINAR MATRÍCULA ====================
if (isset($_GET['eliminar'])) {
    $id = (int)$_GET['eliminar'];
    try {
        $check = $conn->prepare("
            SELECT a.maestro_id 
            FROM matricula_estudiantes me
            JOIN asignaciones a ON me.asignacion_id = a.id
            WHERE me.id = ?
        ");
        $check->execute([$id]);
        $row = $check->fetch();

        if ($isAdmin || ($row && $row['maestro_id'] == $_SESSION['user_id'])) {
            $stmt = $conn->prepare("DELETE FROM matricula_estudiantes WHERE id = ?");
            $stmt->execute([$id]);
            $_SESSION['mensaje'] = "<div class='alert alert-success'>Matrícula eliminada correctamente.</div>";
        } else {
            $_SESSION['mensaje'] = "<div class='alert alert-danger'>No tienes permiso para eliminar esta matrícula.</div>";
        }
    } catch (Exception $e) {
        $_SESSION['mensaje'] = "<div class='alert alert-danger'>No se pudo eliminar.</div>";
    }
    header("Location: matricular_estudiantes.php");
    exit;
}

// Mostrar mensaje
if (isset($_SESSION['mensaje'])) {
    $mensaje = $_SESSION['mensaje'];
    unset($_SESSION['mensaje']);
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
        <h2 class="text-primary fw-bold">
            <?= $isAdmin ? 'Matricular Estudiantes' : 'Matricular Mis Estudiantes' ?>
        </h2>
        <a href="../dashboard.php" class="btn btn-outline-secondary">Volver al Dashboard</a>
    </div>

    <?= $mensaje ?>

    <div class="row g-4">
        <!-- Formulario -->
        <div class="col-lg-5">
            <div class="card shadow">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="bi bi-person-plus"></i> Matricular por Grado + Aula</h5>
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
                            <label class="form-label fw-bold">Grado</label>
                            <select name="grado" class="form-select" required>
                                <option value="">Seleccionar Grado</option>
                                <?php for($i=7; $i<=11; $i++): ?>
                                    <option value="<?= $i ?>"><?= $i ?>°</option>
                                <?php endfor; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Aula / Sección</label>
                            <input type="text" name="aula" class="form-control" placeholder="Ej: A-1, A-2, A-3" required>
                        </div>

                        <button type="submit" name="matricular" class="btn btn-success w-100 btn-lg">
                            Matricular en todas las asignaciones de este Grado + Aula
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Lista agrupada por grado -->
        <div class="col-lg-7">
            <h4 class="mb-3">Matrículas Registradas</h4>

            <?php
            if ($isAdmin) {
                $stmt = $conn->query("
                    SELECT me.id, u.nombre_completo AS estudiante, u.grado, 
                           m.nombre AS materia, p.nombre_completo AS profesor,
                           a.aula, me.fecha_matricula
                    FROM matricula_estudiantes me
                    JOIN usuarios u ON me.estudiante_id = u.id
                    JOIN asignaciones a ON me.asignacion_id = a.id
                    JOIN materias m ON a.materia_id = m.id
                    JOIN usuarios p ON a.maestro_id = p.id
                    ORDER BY u.grado, a.aula, u.nombre_completo
                ");
            } else {
                $stmt = $conn->prepare("
                    SELECT me.id, u.nombre_completo AS estudiante, u.grado, 
                           m.nombre AS materia, p.nombre_completo AS profesor,
                           a.aula, me.fecha_matricula
                    FROM matricula_estudiantes me
                    JOIN usuarios u ON me.estudiante_id = u.id
                    JOIN asignaciones a ON me.asignacion_id = a.id
                    JOIN materias m ON a.materia_id = m.id
                    JOIN usuarios p ON a.maestro_id = p.id
                    WHERE a.maestro_id = ?
                    ORDER BY u.grado, a.aula, u.nombre_completo
                ");
                $stmt->execute([$_SESSION['user_id']]);
            }

            $matriculas = $stmt->fetchAll();

            if (empty($matriculas)) {
                echo "<div class='alert alert-info text-center py-4'>Aún no hay estudiantes matriculados.</div>";
            } else {
                $porGrado = [];
                foreach ($matriculas as $row) {
                    $porGrado[$row['grado']][] = $row;
                }

                foreach ($porGrado as $grado => $lista): ?>
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-primary text-white fw-bold">
                            <?= $grado ?>° Grado
                        </div>
                        <div class="card-body p-0">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Estudiante</th>
                                        <th>Materia</th>
                                        <th>Aula</th>
                                        <th>Profesor</th>
                                        <th>Fecha</th>
                                        <th>Acción</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($lista as $row): ?>
                                    <tr>
                                        <td><?= h($row['estudiante']) ?></td>
                                        <td><?= h($row['materia']) ?></td>
                                        <td><strong><?= h($row['aula'] ?: '—') ?></strong></td>
                                        <td><?= h($row['profesor']) ?></td>
                                        <td><?= $row['fecha_matricula'] ? date('d/m/Y', strtotime($row['fecha_matricula'])) : '—' ?></td>
                                        <td>
                                            <a href="?eliminar=<?= $row['id'] ?>" 
                                                class="btn btn-sm btn-danger"
                                                onclick="return confirm('¿Eliminar esta matrícula?')">Eliminar</a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endforeach;
            }
            ?>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
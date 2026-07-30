<?php 
require '../inc/config.php'; 
redirigirLogin(); 

if (!esAdmin() && !esMaestro()) {
    die("<h1 class='text-danger text-center mt-5'>Acceso denegado</h1>");
}

$isAdmin = esAdmin();
$mensaje = "";

// ==================== ELIMINAR ASIGNACIÓN ====================
if ($_POST && isset($_POST['accion']) && $_POST['accion'] === 'eliminar') {
    $asignacion_id = (int)($_POST['asignacion_id'] ?? 0);

    if ($asignacion_id > 0) {
        try {
            // Verificar que exista y que el usuario tenga permiso
            $stmt = $conn->prepare("SELECT maestro_id FROM asignaciones WHERE id = ?");
            $stmt->execute([$asignacion_id]);
            $asig = $stmt->fetch();

            if (!$asig) {
                $mensaje = "<div class='alert alert-danger'>La asignación no existe.</div>";
            } elseif (!$isAdmin && $asig['maestro_id'] != $_SESSION['user_id']) {
                $mensaje = "<div class='alert alert-danger'>No tienes permiso para eliminar esta asignación.</div>";
            } else {
                // 1. Borrar las notas relacionadas a las matrículas de esta asignación
                $conn->prepare("
                    DELETE FROM notas 
                    WHERE matricula_id IN (
                        SELECT id FROM matricula_estudiantes WHERE asignacion_id = ?
                    )
                ")->execute([$asignacion_id]);

                // 2. Borrar registros de horario (si existen)
                $conn->prepare("DELETE FROM horario WHERE asignacion_id = ?")->execute([$asignacion_id]);

                // 3. Borrar matrículas relacionadas
                $conn->prepare("DELETE FROM matricula_estudiantes WHERE asignacion_id = ?")->execute([$asignacion_id]);
                
                // 4. Borrar la asignación
                $conn->prepare("DELETE FROM asignaciones WHERE id = ?")->execute([$asignacion_id]);
                
                $mensaje = "<div class='alert alert-success'>¡Asignación eliminada correctamente!</div>";
            }
        } catch (Exception $e) {
            $mensaje = "<div class='alert alert-danger'>Error al eliminar: " . h($e->getMessage()) . "</div>";
        }
    }
}

// ==================== GUARDAR ASIGNACIÓN ====================
if ($_POST && isset($_POST['guardar'])) {
    $maestro_id    = $isAdmin ? (int)$_POST['maestro_id'] : $_SESSION['user_id'];
    $materia_id    = (int)$_POST['materia_id'];
    $grado         = $_POST['grado'];
    $dia           = $_POST['dia'];
    $hora_inicio   = $_POST['hora_inicio'];
    $hora_fin      = $_POST['hora_fin'];
    $aula          = trim($_POST['aula'] ?? '');
    $nueva_materia = trim($_POST['nueva_materia'] ?? '');

    $hora_completa = $hora_inicio . " - " . $hora_fin;

    if (empty($grado) || empty($dia) || empty($hora_inicio) || empty($hora_fin) || empty($aula)) {
        $mensaje = "<div class='alert alert-danger'>Todos los campos obligatorios deben completarse.</div>";
    } else {
        try {
            if (!empty($nueva_materia)) {
                $stmt = $conn->prepare("INSERT INTO materias (nombre) VALUES (?)");
                $stmt->execute([$nueva_materia]);
                $materia_id = $conn->lastInsertId();
            }

            if ($materia_id == 0) {
                $mensaje = "<div class='alert alert-danger'>Debes seleccionar o crear una materia.</div>";
            } else {
                // Validación de conflicto
                $stmt = $conn->prepare("
                    SELECT 1 FROM asignaciones 
                    WHERE maestro_id = ? AND dia = ? AND hora = ?
                ");
                $stmt->execute([$maestro_id, $dia, $hora_completa]);

                if ($stmt->rowCount() > 0) {
                    $mensaje = "<div class='alert alert-warning'>
                        <strong>No es posible asignar esta materia</strong><br>
                        Debido a que ya tienes asignada otra materia en este día y hora.
                    </div>";
                } else {
                    $stmt = $conn->prepare("
                        INSERT INTO asignaciones (maestro_id, materia_id, grado, dia, hora, aula) 
                        VALUES (?, ?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([$maestro_id, $materia_id, $grado, $dia, $hora_completa, $aula]);

                    $mensaje = "<div class='alert alert-success'>¡Asignación creada correctamente!</div>";
                }
            }
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                $mensaje = "<div class='alert alert-warning'>
                    <strong>No es posible asignar esta materia</strong><br>
                    Debido a que ya tienes asignada otra materia en este día y hora.
                </div>";
            } else {
                $mensaje = "<div class='alert alert-danger'>Error: " . h($e->getMessage()) . "</div>";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Asignar Materias</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body class="bg-light">

<div class="container mt-5 pt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="text-primary fw-bold"><?= $isAdmin ? 'Gestión de Asignaciones' : 'Mis Asignaciones' ?></h2>
        <a href="../dashboard.php" class="btn btn-outline-secondary">← Dashboard</a>
    </div>

    <?= $mensaje ?>

    <div class="card shadow mb-5">
        <div class="card-header bg-primary text-white">
            <h5>Nueva Asignación</h5>
        </div>
        <div class="card-body">
            <form method="post" class="row g-3">
                <input type="hidden" name="guardar" value="1">

                <?php if ($isAdmin): ?>
                <div class="col-md-3">
                    <label class="form-label">Profesor</label>
                    <select name="maestro_id" class="form-select" required>
                        <option value="">Seleccionar profesor...</option>
                        <?php
                        $stmt = $conn->query("SELECT id, nombre_completo FROM usuarios WHERE rol='maestro' ORDER BY nombre_completo");
                        while ($row = $stmt->fetch()) echo "<option value='{$row['id']}'>".h($row['nombre_completo'])."</option>";
                        ?>
                    </select>
                </div>
                <?php endif; ?>

                <div class="col-md-3">
                    <label class="form-label">Materia</label>
                    <select id="materia_select" name="materia_id" class="form-select">
                        <option value="">Seleccionar existente</option>
                        <?php
                        $stmt = $conn->query("SELECT id, nombre FROM materias ORDER BY nombre");
                        while ($row = $stmt->fetch()) echo "<option value='{$row['id']}'>".h($row['nombre'])."</option>";
                        ?>
                    </select>
                    <input type="text" id="nueva_materia" name="nueva_materia" class="form-control mt-2" placeholder="Nueva materia...">
                </div>

                <div class="col-md-2">
                    <label class="form-label">Grado</label>
                    <select name="grado" id="gradoSelect" class="form-select" onchange="actualizarAulas()" required>
                        <option value="">Seleccionar</option>
                        <?php for($i=7; $i<=11; $i++): ?>
                            <option value="<?= $i ?>"><?= $i ?>°</option>
                        <?php endfor; ?>
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label">Aula / Sección</label>
                    <select name="aula" id="aulaSelect" class="form-select" required>
                        <option value="">Seleccionar Aula</option>
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label">Día</label>
                    <select name="dia" class="form-select" required>
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

                <div class="col-12">
                    <button type="submit" class="btn btn-success btn-lg">Guardar Asignación</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Listado por Grado -->
    <h4 class="mb-3">Mis Asignaciones por Grado</h4>
    <?php
    $where = $isAdmin ? "" : "WHERE a.maestro_id = " . (int)$_SESSION['user_id'];
    $stmt = $conn->query("
        SELECT a.id, a.grado, m.nombre AS materia, u.nombre_completo AS profesor, a.dia, a.hora, a.aula 
        FROM asignaciones a
        JOIN materias m ON a.materia_id = m.id
        JOIN usuarios u ON a.maestro_id = u.id
        $where
        ORDER BY CAST(a.grado AS INT) ASC, a.aula, a.dia, a.hora
    ");
    $asignaciones = $stmt->fetchAll();

    if (empty($asignaciones)) {
        echo "<div class='alert alert-info text-center py-4'>Aún no tienes asignaciones registradas.</div>";
    } else {
        $porGrado = [];
        foreach ($asignaciones as $row) {
            $porGrado[$row['grado']][] = $row;
        }
        ksort($porGrado, SORT_NUMERIC);

        foreach ($porGrado as $grado => $clases): ?>
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary text-white fw-bold"><?= $grado ?>° Grado</div>
                <div class="card-body p-0">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Materia</th>
                                <th>Profesor</th>
                                <th>Aula</th>
                                <th>Día</th>
                                <th>Hora</th>
                                <th>Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($clases as $row): ?>
                            <tr>
                                <td><?= h($row['materia']) ?></td>
                                <td><?= h($row['profesor']) ?></td>
                                <td><strong><?= h($row['aula'] ?: '—') ?></strong></td>
                                <td><?= h($row['dia']) ?></td>
                                <td><?= h($row['hora']) ?></td>
                                <td>
                                    <form method="post" onsubmit="return confirm('¿Estás seguro de eliminar esta asignación?')">
                                        <input type="hidden" name="accion" value="eliminar">
                                        <input type="hidden" name="asignacion_id" value="<?= $row['id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-danger">
                                            <i class="bi bi-trash"></i> Eliminar
                                        </button>
                                    </form>
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

<script>
function actualizarAulas() {
    const grado = document.getElementById('gradoSelect').value;
    const aulaSelect = document.getElementById('aulaSelect');

    const aulas = {
        '7': ['A-1', 'A-2', 'A-3'],
        '8': ['B-1', 'B-2', 'B-3'],
        '9': ['C-1', 'C-2', 'C-3'],
        '10': ['D-1', 'D-2', 'D-3'],
        '11': ['E-1', 'E-2', 'E-3']
    };

    aulaSelect.innerHTML = '<option value="">Seleccionar Aula</option>';

    if (aulas[grado]) {
        aulas[grado].forEach(aula => {
            const option = document.createElement('option');
            option.value = aula;
            option.textContent = aula;
            aulaSelect.appendChild(option);
        });
    }
}
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
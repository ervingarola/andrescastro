<?php 
require '../inc/config.php'; 
redirigirLogin(); 

$isAdmin = esAdmin();
$isMaestro = esMaestro();

if(!$isAdmin && !$isMaestro) {
    die("<h1 class='text-danger text-center mt-5'>Acceso denegado</h1>");
}

$mensaje = "";

// ==================== PROCESAR ASIGNACIÓN ====================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'asignar') {
    
    $maestro_id    = (int)($_POST['maestro_id'] ?? 0);
    $grado         = $_POST['grado'] ?? '';
    $materia_id    = (int)($_POST['materia_id'] ?? 0);
    $nueva_materia = trim($_POST['nueva_materia'] ?? '');
    $dia           = $_POST['dia'] ?? '';
    $hora          = trim($_POST['hora'] ?? '');

    if ($maestro_id === 0 || empty($grado) || empty($dia) || empty($hora) || $materia_id === 0) {
        $mensaje = "<div class='alert alert-danger'>Todos los campos son obligatorios.</div>";
    } else {
        try {
            if (!empty($nueva_materia)) {
                $stmt = $conn->prepare("SELECT id FROM materias WHERE nombre = ?");
                $stmt->execute([$nueva_materia]);
                $materia_id = $stmt->fetchColumn();

                if (!$materia_id) {
                    $stmt = $conn->prepare("INSERT INTO materias (nombre) VALUES (?)");
                    $stmt->execute([$nueva_materia]);
                    $materia_id = $conn->lastInsertId();
                }
            }

            $stmt = $conn->prepare("INSERT INTO asignaciones (maestro_id, materia_id, grado, dia, hora) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$maestro_id, $materia_id, $grado, $dia, $hora]);
            $mensaje = "<div class='alert alert-success'>¡Horario asignado correctamente!</div>";

        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                $mensaje = "<div class='alert alert-warning'>El profesor ya tiene una clase en ese día y hora.</div>";
            } else {
                $mensaje = "<div class='alert alert-danger'>Error: " . h($e->getMessage()) . "</div>";
            }
        }
    }
}

// Eliminar asignación
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'eliminar' && $isAdmin) {
    $id = (int)$_POST['asignacion_id'];
    $stmt = $conn->prepare("DELETE FROM asignaciones WHERE id = ?");
    $stmt->execute([$id]);
    $mensaje = "<div class='alert alert-info'>Asignación eliminada.</div>";
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Gestión de Horarios</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body class="container mt-4">

<h2 class="mb-4">Asignar Horarios a Maestros</h2>
<?= $mensaje ?>

<?php if ($isAdmin): ?>
<form method="post" class="row g-3 mb-5 bg-light p-4 rounded border">
    <input type="hidden" name="accion" value="asignar">

    <div class="col-md-3">
        <label class="form-label fw-bold">Maestro</label>
        <select name="maestro_id" class="form-select" required>
            <option value="">Seleccionar Maestro</option>
            <?php
            $stmt = $conn->query("SELECT id, nombre_completo FROM usuarios WHERE rol = 'maestro' ORDER BY nombre_completo");
            while ($row = $stmt->fetch()) {
                echo "<option value='{$row['id']}'>" . h($row['nombre_completo']) . "</option>";
            }
            ?>
        </select>
    </div>

    <div class="col-md-3">
        <label class="form-label fw-bold">Materia</label>
        <select id="materia_select" name="materia_id" class="form-select mb-2">
            <option value="">Seleccionar materia existente</option>
            <?php
            $stmt = $conn->query("SELECT id, nombre FROM materias ORDER BY nombre");
            while ($row = $stmt->fetch()) {
                echo "<option value='{$row['id']}'>" . h($row['nombre']) . "</option>";
            }
            ?>
        </select>
        <input type="text" id="nueva_materia" name="nueva_materia" class="form-control" placeholder="Nueva materia...">
    </div>

    <div class="col-md-2">
        <label class="form-label fw-bold">Grado</label>
        <select name="grado" class="form-select" required>
            <option value="">Seleccione</option>
            <?php for($i=7; $i<=11; $i++): ?>
                <option value="<?= $i ?>"><?= $i ?>°</option>
            <?php endfor; ?>
        </select>
    </div>

    <div class="col-md-2">
        <label class="form-label fw-bold">Día</label>
        <select name="dia" class="form-select" required>
            <option value="">Seleccionar día</option>
            <option value="Lunes">Lunes</option>
            <option value="Martes">Martes</option>
            <option value="Miércoles">Miércoles</option>
            <option value="Jueves">Jueves</option>
            <option value="Viernes">Viernes</option>
        </select>
    </div>

    <div class="col-md-2">
        <label class="form-label fw-bold">Hora</label>
        <input type="text" name="hora" class="form-control" placeholder="Ej: 07:00 - 08:00" required>
        <small class="text-muted">Escribe la hora libremente</small>
    </div>

    <div class="col-12 text-end">
        <button type="submit" class="btn btn-success">Asignar Horario</button>
    </div>
</form>
<?php endif; ?>

<h4 class="mt-5 mb-3">Horarios Asignados</h4>

<?php
$where = $isMaestro ? "AND a.maestro_id = " . (int)$_SESSION['user_id'] : "";
$stmt = $conn->query("
    SELECT u.id AS maestro_id, u.nombre_completo, COUNT(a.id) as total
    FROM usuarios u
    LEFT JOIN asignaciones a ON u.id = a.maestro_id
    WHERE u.rol = 'maestro' $where
    GROUP BY u.id, u.nombre_completo
    ORDER BY u.nombre_completo
");

while ($maestro = $stmt->fetch(PDO::FETCH_ASSOC)):
?>
<div class="card mb-4 shadow-sm">
    <div class="card-header bg-primary text-white fw-bold">
        <?= h($maestro['nombre_completo']) ?> 
        <span class="badge bg-light text-dark float-end"><?= $maestro['total'] ?> clases</span>
    </div>
    <div class="card-body p-0">
        <table class="table table-striped mb-0">
            <thead class="table-light">
                <tr>
                    <th>Materia</th>
                    <th>Grado</th>
                    <th>Día</th>
                    <th>Hora</th>
                    <?php if ($isAdmin): ?><th>Acción</th><?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php
                $stmt2 = $conn->prepare("
                    SELECT a.id, m.nombre AS materia, a.grado, a.dia, a.hora
                    FROM asignaciones a
                    JOIN materias m ON a.materia_id = m.id
                    WHERE a.maestro_id = ?
                    ORDER BY a.dia, a.hora
                ");
                $stmt2->execute([$maestro['maestro_id']]);
                while ($row = $stmt2->fetch(PDO::FETCH_ASSOC)):
                ?>
                <tr>
                    <td><?= h($row['materia']) ?></td>
                    <td><?= $row['grado'] ?>°</td>
                    <td><strong><?= h($row['dia']) ?></strong></td>
                    <td><?= h($row['hora']) ?></td>
                    <?php if ($isAdmin): ?>
                    <td>
                        <form method="post" class="d-inline" onsubmit="return confirm('¿Eliminar?')">
                            <input type="hidden" name="accion" value="eliminar">
                            <input type="hidden" name="asignacion_id" value="<?= $row['id'] ?>">
                            <button type="submit" class="btn btn-sm btn-danger">Eliminar</button>
                        </form>
                    </td>
                    <?php endif; ?>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endwhile; ?>

<a href="../dashboard.php" class="btn btn-primary mt-3">← Volver al Dashboard</a>

<script>
    const select = document.getElementById('materia_select');
    const input  = document.getElementById('nueva_materia');
    if(select && input) {
        select.addEventListener('change', () => { if(select.value) input.value = ''; });
        input.addEventListener('input', () => { if(input.value.trim()) select.value = ''; });
    }
</script>

</body>
</html>
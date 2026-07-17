<?php 
require '../inc/config.php'; 
redirigirLogin(); 

if (!esAdmin() && !esMaestro()) {
    die("<h1 class='text-danger text-center mt-5'>Acceso denegado</h1>");
}

$asignacion_id = (int)($_GET['asignacion'] ?? 0);
$mensaje = "";

if ($asignacion_id === 0) {
    die("<h1 class='text-danger text-center mt-5'>Asignación no válida</h1>");
}

// Información de la asignación
$stmt = $conn->prepare("
    SELECT m.nombre AS materia, a.grado, u.nombre_completo AS profesor
    FROM asignaciones a 
    JOIN materias m ON a.materia_id = m.id 
    JOIN usuarios u ON a.maestro_id = u.id 
    WHERE a.id = ?
");
$stmt->execute([$asignacion_id]);
$asignacion = $stmt->fetch();

// ==================== PROCESAR GUARDADO ====================
if ($_POST && isset($_POST['guardar_notas'])) {
    try {
        $conn->beginTransaction();
        
        foreach ($_POST['notas'] as $matricula_id => $valores) {
            $p1 = !empty($valores['p1']) ? (float)$valores['p1'] : null;
            $p2 = !empty($valores['p2']) ? (float)$valores['p2'] : null;
            $p3 = !empty($valores['p3']) ? (float)$valores['p3'] : null;

            $stmt = $conn->prepare("
                MERGE INTO notas AS target
                USING (VALUES (?, ?, ?, ?)) AS source (matricula_id, p1, p2, p3)
                ON target.matricula_id = source.matricula_id
                WHEN MATCHED THEN 
                    UPDATE SET periodo1 = source.p1, periodo2 = source.p2, periodo3 = source.p3
                WHEN NOT MATCHED THEN 
                    INSERT (matricula_id, periodo1, periodo2, periodo3)
                    VALUES (source.matricula_id, source.p1, source.p2, source.p3);
            ");
            $stmt->execute([$matricula_id, $p1, $p2, $p3]);
        }
        
        $conn->commit();
        $mensaje = "<div class='alert alert-success'>¡Notas guardadas correctamente!</div>";
    } catch (Exception $e) {
        $conn->rollBack();
        $mensaje = "<div class='alert alert-danger'>Error al guardar notas: " . h($e->getMessage()) . "</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Notas - <?= h($asignacion['materia']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body class="bg-light">

<div class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><?= h($asignacion['materia']) ?> — <?= $asignacion['grado'] ?>° Grado</h2>
        <a href="../dashboard.php" class="btn btn-outline-secondary">Volver al Dashboard</a>
    </div>

    <p><strong>Profesor:</strong> <?= h($asignacion['profesor']) ?></p>
    <?= $mensaje ?>

    <form method="post">
        <input type="hidden" name="guardar_notas" value="1">

        <div class="table-responsive">
            <table class="table table-bordered table-hover">
                <thead class="table-dark">
                    <tr>
                        <th>Estudiante</th>
                        <th class="text-center">Período 1</th>
                        <th class="text-center">Período 2</th>
                        <th class="text-center">Período 3</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $stmt = $conn->prepare("
                        SELECT me.id AS matricula_id, u.nombre_completo 
                        FROM matricula_estudiantes me
                        JOIN usuarios u ON me.estudiante_id = u.id
                        WHERE me.asignacion_id = ?
                        ORDER BY u.nombre_completo
                    ");
                    $stmt->execute([$asignacion_id]);
                    while ($est = $stmt->fetch()):
                        $nStmt = $conn->prepare("SELECT periodo1, periodo2, periodo3 FROM notas WHERE matricula_id = ?");
                        $nStmt->execute([$est['matricula_id']]);
                        $nota = $nStmt->fetch();
                    ?>
                    <tr>
                        <td class="fw-bold"><?= h($est['nombre_completo']) ?></td>
                        <td><input type="number" step="0.01" min="0" max="100" name="notas[<?= $est['matricula_id'] ?>][p1]" value="<?= $nota['periodo1'] ?? '' ?>" class="form-control text-center"></td>
                        <td><input type="number" step="0.01" min="0" max="100" name="notas[<?= $est['matricula_id'] ?>][p2]" value="<?= $nota['periodo2'] ?? '' ?>" class="form-control text-center"></td>
                        <td><input type="number" step="0.01" min="0" max="100" name="notas[<?= $est['matricula_id'] ?>][p3]" value="<?= $nota['periodo3'] ?? '' ?>" class="form-control text-center"></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <button type="submit" class="btn btn-success btn-lg mt-3">
            <i class="bi bi-save"></i> Guardar Todas las Notas
        </button>
    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
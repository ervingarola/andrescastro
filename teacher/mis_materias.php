<?php 
require '../inc/config.php'; 
redirigirLogin(); 
if(!esMaestro()) die("<h1 class='text-danger text-center mt-5'>Acceso denegado</h1>");

$maestro_id = $_SESSION['user_id'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Mis Materias Asignadas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body class="bg-light">

<div class="container mt-5 pt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="text-primary fw-bold">Mis Materias Asignadas</h2>
        <a href="../dashboard.php" class="btn btn-outline-secondary">Volver al Dashboard</a>
    </div>

    <?php
    try {
        $stmt = $conn->prepare("SELECT m.nombre AS materia, a.grado FROM asignaciones a
            JOIN materias m ON a.materia_id = m.id WHERE a.maestro_id = ? ORDER BY a.grado, m.nombre");
        $stmt->execute([$maestro_id]);
        $materias = $stmt->fetchAll();

        if (empty($materias)) {
            echo '<div class="alert alert-warning text-center fs-5 p-4">Aún no tienes materias asignadas.<br><small>Contacta al administrador.</small></div>';
        } else {
            $colores = ['7'=>'primary','8'=>'success','9'=>'warning','10'=>'danger','11'=>'info'];
            echo '<div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">';
            foreach ($materias as $row) {
                $color = $colores[$row['grado']] ?? 'secondary';
                echo '<div class="col">
                    <div class="card h-100 text-white bg-'.$color.' border-0 shadow card-hover">
                        <div class="card-body text-center d-flex flex-column justify-content-center">
                            <h5 class="card-title display-5 fw-bold">'.$row['grado'].'°</h5>
                            <hr class="border-light opacity-75 mx-auto" style="width:60%;">
                            <p class="card-text fs-4 fw-bold mb-0">'.h($row['materia']).'</p>
                        </div>
                        <div class="card-footer bg-transparent border-0 text-center pt-3 pb-4">
                            <a href="ingresar_notas.php" class="btn btn-light btn-lg fw-bold text-'.$color.' shadow-sm">
                                Poner Notas
                            </a>
                        </div>
                    </div>
                </div>';
            }
            echo '</div>';
        }
    } catch (Exception $e) {
        echo '<div class="alert alert-danger">Error del sistema.</div>';
    }
    ?>

    <div class="text-center mt-5">
        <small class="text-muted">Total de asignaciones: <strong><?= count($materias ?? []) ?></strong></small>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
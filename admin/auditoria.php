<?php 
require '../inc/config.php';
redirigirLogin();
if(!esAdmin()) die("<h1 class='text-danger text-center mt-5'>Acceso denegado</h1>");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Auditoría del Sistema</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body class="bg-light">

<div class="container mt-5 pt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="text-primary fw-bold">Auditoría del Sistema</h2>
        <a href="../dashboard.php" class="btn btn-outline-secondary">Volver al Dashboard</a>
    </div>

    <div class="card shadow-lg border-0">
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
            <h4 class="mb-0">Registro Completo de Actividades</h4>
            <small>Escuela Secundaria Andrés Castro • 2025</small>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th>Fecha y Hora</th>
                            <th>Acción</th>
                            <th>Tabla</th>
                            <th>ID Registro</th>
                            <th>Usuario</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        try {
                            // Usar la vista con las columnas correctas
                            $stmt = $conn->prepare("SELECT 
                                fecha_hora,
                                accion,
                                tabla_afectada,
                                registro_id,
                                usuario,
                                descripcion_registro
                                FROM vw_auditoria_completa 
                                ORDER BY fecha_hora DESC");
                            $stmt->execute();
                            $registros = $stmt->fetchAll();

                            if (empty($registros)) {
                                echo "<tr><td colspan='5' class='text-center text-muted py-4'>Aún no hay registros de auditoría.</td></tr>";
                            } else {
                                foreach ($registros as $row) {
                                    $accion = $row['accion'];
                                    $color = $accion === 'INSERT' ? 'success' : ($accion === 'UPDATE' ? 'warning' : 'danger');
                                    $icono = $accion === 'INSERT' ? 'plus-circle' : ($accion === 'UPDATE' ? 'pencil' : 'trash');

                                    echo "<tr>
                                        <td class='fw-bold'>" . date('d/m/Y H:i', strtotime($row['fecha_hora'])) . "</td>
                                        <td><span class='badge bg-$color fs-6'><i class='bi bi-$icono'></i> $accion</span></td>
                                        <td>" . h($row['tabla_afectada']) . "</td>
                                        <td><span class='badge bg-secondary'>#" . h($row['registro_id']) . "</span></td>
                                        <td class='text-muted small'>" . h($row['usuario'] ?? 'Sistema') . "</td>
                                    </tr>";
                                }
                            }
                        } catch (Exception $e) {
                            echo "<tr><td colspan='5' class='text-center text-danger py-4'>Error al cargar auditoría: " . h($e->getMessage()) . "</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer bg-white text-center">
            <small class="text-muted">Total de registros: 
                <strong><?= $conn->query("SELECT COUNT(*) FROM auditoria_registro")->fetchColumn() ?></strong>
            </small>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
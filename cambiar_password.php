<?php 
require 'inc/config.php'; 
redirigirLogin(); 

$mensaje = "";

// ==================== PROCESAR CAMBIO DE CONTRASEÑA ====================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cambiar'])) {
    $actual    = $_POST['password_actual'] ?? '';
    $nueva     = $_POST['password_nueva'] ?? '';
    $confirmar = $_POST['password_confirmar'] ?? '';

    if (empty($actual) || empty($nueva) || empty($confirmar)) {
        $mensaje = "<div class='alert alert-danger'>Todos los campos son obligatorios.</div>";
    } elseif (strlen($nueva) < 4) {
        $mensaje = "<div class='alert alert-danger'>La nueva contraseña debe tener al menos 4 caracteres.</div>";
    } elseif ($nueva !== $confirmar) {
        $mensaje = "<div class='alert alert-danger'>La nueva contraseña y la confirmación no coinciden.</div>";
    } else {
        try {
            // Verificar contraseña actual
            $stmt = $conn->prepare("SELECT password FROM usuarios WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $usuario = $stmt->fetch();

            if (!$usuario || !password_verify($actual, $usuario['password'])) {
                $mensaje = "<div class='alert alert-danger'>La contraseña actual es incorrecta.</div>";
            } else {
                // Actualizar contraseña
                $hash = password_hash($nueva, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE usuarios SET password = ? WHERE id = ?");
                $stmt->execute([$hash, $_SESSION['user_id']]);

                $mensaje = "<div class='alert alert-success'>
                    <i class='bi bi-check-circle-fill'></i> ¡Contraseña actualizada correctamente!
                </div>";
            }
        } catch (Exception $e) {
            $mensaje = "<div class='alert alert-danger'>Error al cambiar la contraseña: " . h($e->getMessage()) . "</div>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Cambiar Contraseña</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="bg-notas">

<div class="container mt-5 pt-4">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="text-primary fw-bold mb-0">Cambiar Contraseña</h2>
                <a href="dashboard.php" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-arrow-left"></i> Dashboard
                </a>
            </div>

            <?= $mensaje ?>

            <div class="card card-password shadow">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-key"></i> Actualizar mi contraseña</h5>
                </div>
                <div class="card-body">
                    <form method="post" class="form-password">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Contraseña actual</label>
                            <input type="password" name="password_actual" class="form-control" required 
                                    placeholder="Escribe tu contraseña actual">
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Nueva contraseña</label>
                            <input type="password" name="password_nueva" class="form-control" required 
                                    placeholder="Mínimo 4 caracteres" minlength="4">
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-bold">Confirmar nueva contraseña</label>
                            <input type="password" name="password_confirmar" class="form-control" required 
                                    placeholder="Repite la nueva contraseña">
                        </div>

                        <button type="submit" name="cambiar" class="btn btn-success w-100 btn-lg btn-guardar-password">
                            <i class="bi bi-save"></i> Guardar nueva contraseña
                        </button>
                    </form>
                </div>
            </div>

            <div class="alert alert-info mt-4">
                <i class="bi bi-info-circle"></i>
                <strong>Consejo de seguridad:</strong> Usa una contraseña que solo tú conozcas y no la compartas.
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php 
require __DIR__ . '/inc/config.php';

if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit;
}

$mensaje = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario  = trim($_POST['usuario'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($usuario === '' || $password === '') {
        $mensaje = "Complete todos los campos";
    } else {
        try {
            $stmt = $conn->prepare("SELECT id, nombre_completo, password, rol, grado FROM usuarios WHERE usuario = ?");
            $stmt->execute([$usuario]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['nombre']  = $user['nombre_completo'];
                $_SESSION['rol']     = $user['rol'];
                $_SESSION['grado']   = $user['grado'] ?? null;
                header("Location: dashboard.php");
                exit;
            } else {
                $mensaje = "Usuario o contraseña incorrectos";
            }
        } catch (Exception $e) {
            $mensaje = "Error del sistema";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Colegio Andrés Castro | Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css?v=<?= time() ?>">
</head>
<body class="login-body">
<div class="login-card">
    <div class="login-header text-center">
        <img src="img/logo_andrescastro.jpg" alt="Colegio Andrés Castro" class="img-fluid mb-3" style="max-width: 180px;">
        <h3 class="text-white">Colegio Andrés Castro</h3>
        <p class="typing text-white opacity-90">Sistema de Gestión Académica</p>
    </div>

    <div class="login-body">
        <?php if ($mensaje !== ''): ?>
            <div class="alert alert-danger text-center rounded-pill mb-4"><?= h($mensaje) ?></div>
        <?php endif; ?>

        <form method="post" id="formLogin">
            <div class="mb-4">
                <label class="form-label fw-600">Usuario</label>
                <input type="text" name="usuario" class="form-control" required autofocus>
            </div>
            <div class="mb-4">
                <label class="form-label fw-600">Contraseña</label>
                <input type="password" name="password" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary btn-login w-100">INGRESAR AL SISTEMA</button>
        </form>

        <div class="login-footer text-center mt-4 text-muted small">
            Sistema seguro • Colegio Andrés Castro 2025
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="js/script.js"></script>
</body>
</html>
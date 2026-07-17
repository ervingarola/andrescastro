<?php 
require '../inc/config.php'; 
redirigirLogin(); 
if(!esAdmin()) die("<h1 class='text-danger text-center mt-5'>Acceso denegado</h1>");

$mensaje = "";

// ========== CAMBIAR CONTRASEÑA ==========
if (isset($_POST['cambiar_pass'])) {
    $user_id = (int)$_POST['user_id'];
    $nueva_pass = trim($_POST['nueva_pass']);
    
    if (strlen($nueva_pass) < 4) {
        $mensaje = "<div class='alert alert-danger'>La contraseña debe tener al menos 4 caracteres.</div>";
    } else {
        $hash = password_hash($nueva_pass, PASSWORD_DEFAULT);
        try {
            $stmt = $conn->prepare("UPDATE usuarios SET password = ? WHERE id = ?");
            $stmt->execute([$hash, $user_id]);
            $mensaje = "<div class='alert alert-success'>¡Contraseña cambiada exitosamente!</div>";
        } catch (Exception $e) {
            $mensaje = "<div class='alert alert-danger'>Error al cambiar contraseña.</div>";
        }
    }
}

// ========== CREAR USUARIO ==========
if (isset($_POST['crear'])) {
    $nombre = trim($_POST['nombre']);
    $usuario = trim($_POST['usuario']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $rol = $_POST['rol'];
    $grado = ($rol === 'estudiante' && !empty($_POST['grado'])) ? $_POST['grado'] : null;

    if (empty($nombre) || empty($usuario) || empty($_POST['password'])) {
        $mensaje = "<div class='alert alert-danger'>Faltan datos obligatorios.</div>";
    } else {
        try {
            $stmt = $conn->prepare("INSERT INTO usuarios (nombre_completo, usuario, password, rol, grado) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$nombre, $usuario, $password, $rol, $grado]);
            $mensaje = "<div class='alert alert-success'>Usuario creado correctamente.</div>";
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                $mensaje = "<div class='alert alert-danger'>El usuario <strong>$usuario</strong> ya existe.</div>";
            } else {
                $mensaje = "<div class='alert alert-danger'>Error al crear usuario.</div>";
            }
        }
    }
}

// ========== ELIMINAR USUARIO ==========
if (isset($_GET['eliminar'])) {
    $id = (int)$_GET['eliminar'];
    if ($id != 1) { // no borrar el admin principal si su id es 1
        $conn->prepare("DELETE FROM usuarios WHERE id = ? AND id != 1")->execute([$id]);
        $mensaje = "<div class='alert alert-success'>Usuario eliminado.</div>";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin - Gestión de Usuarios</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body class="bg-light">

<div class="container mt-5 pt-3">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold text-primary">Gestión de Usuarios</h2>
        <a href="../dashboard.php" class="btn btn-outline-secondary">Volver al Dashboard</a>
    </div>

    <?= $mensaje ?>

    <!-- FORMULARIO CREAR USUARIO -->
    <div class="card shadow mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">Crear Nuevo Usuario</h5>
        </div>
        <div class="card-body">
            <form method="post" class="row g-3">
                <div class="col-md-4">
                    <input type="text" name="nombre" class="form-control" placeholder="Nombre completo" required>
                </div>
                <div class="col-md-3">
                    <input type="text" name="usuario" class="form-control" placeholder="Nombre de usuario" required>
                </div>
                <div class="col-md-3">
                    <input type="password" name="password" class="form-control" placeholder="Contraseña" required>
                </div>
                <div class="col-md-3">
                    <select name="rol" class="form-select" onchange="toggleGrado(this)" required>
                        <option value="">-- Seleccionar rol --</option>
                        <option value="admin">Administrador</option>
                        <option value="maestro">Maestro</option>
                        <option value="estudiante">Estudiante</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="grado" id="gradoSelect" class="form-select" disabled>
                        <option value="">Grado</option>
                        <option>7</option><option>8</option><option>9</option><option>10</option><option>11</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" name="crear" class="btn btn-success w-100">Crear</button>
                </div>
            </form>
        </div>
    </div>

    <!-- LISTA DE USUARIOS -->
    <h4 class="mb-3">Lista de Usuarios</h4>
    <div class="table-responsive">
        <table class="table table-hover align-middle table-bordered">
            <thead class="table-dark">
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Usuario</th>
                    <th>Rol</th>
                    <th>Grado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $stmt = $conn->query("SELECT id, nombre_completo, usuario, rol, grado FROM usuarios ORDER BY rol DESC, nombre_completo");
                while ($u = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $badge = $u['rol'] === 'admin' ? 'danger' : ($u['rol'] === 'maestro' ? 'primary' : 'success');
                    echo "<tr>
                        <td><strong>{$u['id']}</strong></td>
                        <td>" . h($u['nombre_completo']) . "</td>
                        <td>{$u['usuario']}</td>
                        <td><span class='badge bg-$badge'>" . ucfirst($u['rol']) . "</span></td>
                        <td>" . ($u['grado'] ? $u['grado'] . "°" : "—") . "</td>
                        <td class='text-center'>
                            <button class='btn btn-sm btn-warning' data-bs-toggle='modal' data-bs-target='#modal{$u['id']}'>
                                Cambiar Contraseña
                            </button>
                            " . ($u['id'] != 1 ? "<a href='?eliminar={$u['id']}' class='btn btn-sm btn-danger' onclick=\"return confirm('¿Seguro?')\">Eliminar</a>" : "") . "
                        </td>
                    </tr>";

                    // Modal para cambiar contraseña
                    echo "
                    <div class='modal fade' id='modal{$u['id']}' tabindex='-1'>
                        <div class='modal-dialog'>
                            <div class='modal-content'>
                                <div class='modal-header bg-warning text-dark'>
                                    <h5 class='modal-title'>Cambiar Contraseña</h5>
                                    <button type='button' class='btn-close' data-bs-dismiss='modal'></button>
                                </div>
                                <form method='post'>
                                    <div class='modal-body'>
                                        <p><strong>Usuario:</strong> {$u['usuario']} ({$u['nombre_completo']})</p>
                                        <input type='hidden' name='user_id' value='{$u['id']}'>
                                        <label class='form-label'>Nueva contraseña</label>
                                        <input type='text' name='nueva_pass' class='form-control' placeholder='Escribe nueva contraseña' required>
                                    </div>
                                    <div class='modal-footer'>
                                        <button type='button' class='btn btn-secondary' data-bs-dismiss='modal'>Cancelar</button>
                                        <button type='submit' name='cambiar_pass' class='btn btn-warning'>Cambiar Contraseña</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>";
                }
                ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function toggleGrado(select) {
    document.getElementById('gradoSelect').disabled = select.value !== 'estudiante';
    if (select.value !== 'estudiante') document.getElementById('gradoSelect').value = '';
}
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
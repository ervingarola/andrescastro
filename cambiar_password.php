<?php
// cambiar_password.php → SOLO PARA USAR UNA VEZ
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nueva = $_POST['nueva'] ?? '';
    if ($nueva !== '') {
        $hash = password_hash($nueva, PASSWORD_DEFAULT);
        echo "<div style='background:#d4edda;padding:20px;border-radius:10px;font-size:18px'>";
        echo "<h3>¡HASH GENERADO CORRECTAMENTE EN TU APPSERV!</h3>";
        echo "<strong>Contraseña que escribiste:</strong> $nueva<br><br>";
        echo "<strong>Copia este hash y pégalo en SQL:</strong><br>";
        echo "<textarea style='width:100%;height:120px;font-size:16px'>$hash</textarea>";
        echo "</div>";
    }
}
?>
<!DOCTYPE html>
<html><head><title>Cambiar Contraseña Admin</title>
<style>body{font-family:Arial;background:#f4f4f4;padding:50px}</style></head>
<body>
<h2>Cambiar contraseña del admin (solo una vez)</h2>
<form method="post">
    <input type="text" name="nueva" placeholder="Escribe tu nueva contraseña personal" 
        style="padding:10px;font-size:18px;width:300px" required autofocus>
    <button type="submit" style="padding:10px 20px;font-size:18px">Generar Hash</button>
</form>
</body></html>
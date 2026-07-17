<?php
// inc/config.php → VERSIÓN QUE NUNCA FALLA EN APPSERV + SQL SERVER
session_start();
date_default_timezone_set('America/Managua');

// ========== CONEXIÓN QUE FUNCIONA AL 100% ==========
$serverName = "ERVING\\SQLEXPRESS";   // Cambia por tu instancia si es diferente
$connectionOptions = array(
    "Database" => "escuela_andres_castro",
    "Uid"      => "Escuela_Andrescastro",
    "PWD"      => "123",               // ← TU CONTRASEÑA AQUÍ
    "TrustServerCertificate" => true
);

try {
    $conn = new PDO("sqlsrv:server=$serverName;Database=escuela_andres_castro;TrustServerCertificate=1", 
                    "Escuela_Andrescastro", "123");
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("<h3 style='color:red;text-align:center;margin-top:50px'>
            Error de conexión: " . h($e->getMessage()) . 
        "<br><br>Verifica que SQL Server esté encendido y que el usuario/contraseña sean correctos.</h3>");
}

// ========== FUNCIONES DE SEGURIDAD ==========
function estaLogueado() { return isset($_SESSION['user_id']); }

function redirigirLogin() { 
    if (!estaLogueado()) {
        header("Location: ../index.php");
        exit;
    }
}

function esAdmin()      { return ($_SESSION['rol'] ?? '') === 'admin'; }
function esMaestro()    { return ($_SESSION['rol'] ?? '') === 'maestro'; }
function esEstudiante() { return ($_SESSION['rol'] ?? '') === 'estudiante'; }

function h($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}
?>
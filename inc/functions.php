<?php
// inc/functions.php
require_once 'config.php';

function estaLogueado() {
    return isset($_SESSION['user_id']);
}

function redirigirLogin() {
    if (!estaLogueado()) {
        header("Location: index.php");
        exit();
    }
}

function obtenerRol() {
    return $_SESSION['rol'] ?? null;
}

function esAdmin() { return obtenerRol() === 'admin'; }
function esMaestro() { return obtenerRol() === 'maestro'; }
function esEstudiante() { return obtenerRol() === 'estudiante'; }
?>
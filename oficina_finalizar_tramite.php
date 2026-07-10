<?php
session_start();

// Validamos que sea un usuario de Oficina
if (!isset($_SESSION["auth_mesa"]) || $_SESSION["rol"] != "oficina") {
    header("Location: login_mesa.php");
    exit();
}

require 'conexion.php';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['id_tramite'])) {
    
    $id_tramite = intval($_POST['id_tramite']);
    
    // id_estado = 5 (Generalmente significa Atendido / Finalizado)
    $query = "UPDATE tramites SET id_estado = 5 WHERE id_tramite = $id_tramite";
    
    if (mysqli_query($cn, $query)) {
        header("Location: principal_oficina.php?ok=finalizado");
    } else {
        header("Location: principal_oficina.php?error=bd");
    }
    exit();
    
} else {
    header("Location: principal_oficina.php");
    exit();
}
?>
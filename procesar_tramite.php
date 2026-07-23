<?php
session_start();
require 'conexion.php';

// 1. MANEJAR CAMBIOS DE ESTADO (Desactivar o Activar vía enlace GET)
if (isset($_GET['accion']) && $_GET['accion'] == 'estado' && isset($_GET['id']) && isset($_GET['est'])) {
    $id = intval($_GET['id']);
    $est = intval($_GET['est']);
    
    $sql_estado = "UPDATE tipos_tramite SET estado = $est WHERE id_tipo_tramite = $id";
    mysqli_query($cn, $sql_estado);
    
    header("Location: gestion_tramites.php");
    exit();
}

// 2. MANEJAR CREACIÓN Y EDICIÓN (Vía formulario POST)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = isset($_POST['id_tipo_tramite']) ? intval($_POST['id_tipo_tramite']) : 0;
    $nombre = mysqli_real_escape_string($cn, trim($_POST['nombre_tramite']));

    if ($id > 0) {
        // Si hay ID, es una EDICIÓN
        $sql_update = "UPDATE tipos_tramite SET nombre_tramite = '$nombre' WHERE id_tipo_tramite = $id";
        mysqli_query($cn, $sql_update);
    } else {
        // Si no hay ID, es una CREACIÓN NUEVA (por defecto estado 1 = Activo)
        $sql_insert = "INSERT INTO tipos_tramite (nombre_tramite, estado) VALUES ('$nombre', 1)";
        mysqli_query($cn, $sql_insert);
    }
    
    header("Location: gestion_tramites.php");
    exit();
}

// Redirección de seguridad si alguien entra a este archivo directamente
header("Location: gestion_tramites.php");
exit();
?>
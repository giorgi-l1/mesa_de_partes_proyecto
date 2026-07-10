<?php
session_start();

// Validamos permisos y método de envío
if (!isset($_SESSION["auth"]) || $_SESSION["auth"] != "1" || $_SERVER["REQUEST_METHOD"] != "POST") {
    header("Location: login_mesa.php");
    exit();
}

require 'conexion.php';

// Validar que recibamos los datos
if (isset($_POST['id_ticket']) && isset($_POST['nuevo_estado'])) {
    
    $id_ticket = intval($_POST['id_ticket']);
    $nuevo_estado = mysqli_real_escape_string($cn, $_POST['nuevo_estado']);
    
    // Consulta para actualizar el estado del ticket (ej. de 'Abierto' a 'Atendido')
    $query = "UPDATE tickets_ayuda SET estado_ticket = '$nuevo_estado' WHERE id_ticket = $id_ticket";
    
    if (mysqli_query($cn, $query)) {
        // Redirigir con éxito
        header("Location: mesa_bandeja_ayuda.php?status=success");
        exit();
    } else {
        // Manejo básico de errores
        echo "Error al actualizar el estado: " . mysqli_error($cn);
    }
} else {
    header("Location: mesa_bandeja_ayuda.php?error=datos");
    exit();
}
?>
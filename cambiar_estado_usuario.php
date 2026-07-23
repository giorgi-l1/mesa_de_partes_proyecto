<?php
session_start();
require 'conexion.php'; // Asegúrate de que la ruta sea correcta

if (isset($_GET['id']) && isset($_GET['est'])) {
    $id_usuario = intval($_GET['id']);
    $nuevo_estado = intval($_GET['est']); 

    // Medida de seguridad: Validar que el estado sea estrictamente 0 o 1
    if ($nuevo_estado === 0 || $nuevo_estado === 1) {
        // Ejecuta la baja lógica
        $sql = "UPDATE usuarios SET estado = $nuevo_estado WHERE id_usuario = $id_usuario";
        mysqli_query($cn, $sql);
    }
}

// Retorna silenciosamente a la tabla
header("Location: gestion_usuarios.php");
exit();
?>
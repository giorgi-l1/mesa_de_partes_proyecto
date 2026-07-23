<?php
session_start();
require 'conexion.php'; 

if (isset($_GET['id']) && isset($_GET['est'])) {
    $id_usuario = intval($_GET['id']);
    $nuevo_estado = intval($_GET['est']); 

    if ($nuevo_estado === 0 || $nuevo_estado === 1) {
        
        // 1. Cambiamos el estado del usuario seleccionado
        mysqli_query($cn, "UPDATE usuarios SET estado = $nuevo_estado WHERE id_usuario = $id_usuario");
        
        // 2. Si le damos de baja (0), purgamos sus asignaciones operativas
        if ($nuevo_estado === 0) {
            
            // Obtenemos su DNI para buscar en las tablas operativas
            $q_doc = mysqli_query($cn, "SELECT numero_documento FROM datos_personales WHERE id_usuario = $id_usuario");
            if ($row_doc = mysqli_fetch_assoc($q_doc)) {
                $dni = $row_doc['numero_documento'];
                
                // A) Liberar al trabajador base para que no quede atascado como "ocupado"
                mysqli_query($cn, "UPDATE datos_personal SET estado_asignado = NULL WHERE id_usuario = $id_usuario");
                
                // B) Si estaba en una Mesa de Partes (Tipo 6), desactivar esa Mesa y liberarla
                mysqli_query($cn, "UPDATE usuarios u 
                                   INNER JOIN datos_personales dpers ON u.id_usuario = dpers.id_usuario 
                                   INNER JOIN datos_personal dp ON u.id_usuario = dp.id_usuario 
                                   SET u.estado = 0, dp.estado_asignado = NULL 
                                   WHERE dpers.numero_documento = '$dni' AND u.id_tipo = 6");
                                   
                // C) Si era Jefe de una Oficina (Tipo 7), quitamos su nombre de la cuenta de la oficina
                // Ponemos la cuenta como "Vacante" para no eliminar el correo de la oficina
                mysqli_query($cn, "UPDATE datos_personales dpers 
                                   INNER JOIN usuarios u ON dpers.id_usuario = u.id_usuario 
                                   SET dpers.numero_documento = 'SIN_ASIGNAR', dpers.nombres = 'Vacante (Requiere Asignación)', dpers.apellido_paterno = '' 
                                   WHERE dpers.numero_documento = '$dni' AND u.id_tipo = 7");
            }
        }
    }
}

// Retorna silenciosamente a la tabla
header("Location: gestion_usuarios.php");
exit();
?>
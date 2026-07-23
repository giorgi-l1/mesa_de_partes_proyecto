<?php
session_start();
require 'conexion.php';

if (isset($_GET['id']) && isset($_GET['est'])) {
    $id_usuario = intval($_GET['id']);
    $nuevo_estado = intval($_GET['est']);

    if ($nuevo_estado === 0 || $nuevo_estado === 1) {
        // 1. Cambiamos el estado del usuario (Ventanilla)
        mysqli_query($cn, "UPDATE usuarios SET estado = $nuevo_estado WHERE id_usuario = $id_usuario");

        // 2. Si le damos de baja (0), liberamos la ventanilla y al trabajador
        if ($nuevo_estado === 0) {

            // A) Liberar el espacio físico (Mesa X) para que la ventanilla vuelva a estar disponible
            mysqli_query($cn, "UPDATE datos_personal SET estado_asignado = NULL WHERE id_usuario = $id_usuario");

            // B) Liberar a la cuenta "Personal" (Tipo 3) del trabajador usando el DNI como puente
            $q_doc = mysqli_query($cn, "SELECT numero_documento FROM datos_personales WHERE id_usuario = $id_usuario");
            if ($row_doc = mysqli_fetch_assoc($q_doc)) {
                $dni = $row_doc['numero_documento'];

                // Buscar la cuenta base (Tipo 3) que tenga ese mismo DNI y poner su estado_asignado en NULL
                $q_liberar = "UPDATE datos_personal dp 
                              INNER JOIN datos_personales dpers ON dp.id_usuario = dpers.id_usuario 
                              INNER JOIN usuarios u ON u.id_usuario = dp.id_usuario
                              SET dp.estado_asignado = NULL 
                              WHERE dpers.numero_documento = '$dni' AND u.id_tipo = 3";
                mysqli_query($cn, $q_liberar);
            }
        }
        // 3. Si se REACTIVA (1), le devolvemos su estado de ocupado a ambas cuentas
        else if ($nuevo_estado === 1) {
            // Recuperamos el cargo_real (Ventanilla X) de la cuenta de mesa
            $q_cargo = mysqli_query($cn, "SELECT cargo_real FROM datos_oficina_usuario WHERE id_usuario = $id_usuario");
            if($row_cargo = mysqli_fetch_assoc($q_cargo)) {
                $nombre_mesa = "Mesa " . str_replace('Ventanilla ', '', $row_cargo['cargo_real']);
                
                // Ocupar nuevamente la ventanilla física
                mysqli_query($cn, "UPDATE datos_personal SET estado_asignado = '$nombre_mesa' WHERE id_usuario = $id_usuario");
                
                // Ocupar nuevamente al trabajador base usando el DNI
                $q_doc = mysqli_query($cn, "SELECT numero_documento FROM datos_personales WHERE id_usuario = $id_usuario");
                if($row_doc = mysqli_fetch_assoc($q_doc)) {
                    $dni = $row_doc['numero_documento'];
                    mysqli_query($cn, "UPDATE datos_personal dp 
                                       INNER JOIN datos_personales dpers ON dp.id_usuario = dpers.id_usuario 
                                       INNER JOIN usuarios u ON u.id_usuario = dp.id_usuario 
                                       SET dp.estado_asignado = '$nombre_mesa' 
                                       WHERE dpers.numero_documento = '$dni' AND u.id_tipo = 3");
                }
            }
        }
    }
}
header("Location: gestion_mesas.php");
exit();
?>
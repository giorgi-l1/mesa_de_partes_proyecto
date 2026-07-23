<?php
session_start();
require 'conexion.php';

if (isset($_GET['id']) && isset($_GET['estado'])) {
    $id_oficina = intval($_GET['id']);
    $estado = intval($_GET['estado']);
    
    // 1. Cambiar estado lógico de la oficina
    mysqli_query($cn, "UPDATE oficinas SET estado = $estado WHERE id_oficina = $id_oficina");
    
    // 2. Obtener la cuenta operativa y actualizar su estado
    $q_cuenta = mysqli_query($cn, "SELECT id_usuario FROM datos_oficina_usuario WHERE id_oficina = $id_oficina AND id_rol_oficina = 1 LIMIT 1");
    if ($row_cuenta = mysqli_fetch_assoc($q_cuenta)) {
        $id_cuenta_ofi = $row_cuenta['id_usuario'];
        mysqli_query($cn, "UPDATE usuarios SET estado = $estado WHERE id_usuario = $id_cuenta_ofi");

        // 3. Liberar (0) u Ocupar (1) al trabajador base
        $q_doc = mysqli_query($cn, "SELECT numero_documento FROM datos_personales WHERE id_usuario = $id_cuenta_ofi");
        if ($row_doc = mysqli_fetch_assoc($q_doc)) {
            $dni = $row_doc['numero_documento'];
            
            if ($estado == 0) {
                // Liberar
                mysqli_query($cn, "UPDATE datos_personal dp INNER JOIN datos_personales dpers ON dp.id_usuario = dpers.id_usuario INNER JOIN usuarios u ON u.id_usuario = dp.id_usuario SET dp.estado_asignado = NULL WHERE dpers.numero_documento = '$dni' AND u.id_tipo = 3");
            } else {
                // Reactivar
                $q_siglas = mysqli_query($cn, "SELECT siglas FROM oficinas WHERE id_oficina = $id_oficina");
                $siglas = mysqli_fetch_assoc($q_siglas)['siglas'];
                $asignacion = "Jefatura " . $siglas;
                mysqli_query($cn, "UPDATE datos_personal dp INNER JOIN datos_personales dpers ON dp.id_usuario = dpers.id_usuario INNER JOIN usuarios u ON u.id_usuario = dp.id_usuario SET dp.estado_asignado = '$asignacion' WHERE dpers.numero_documento = '$dni' AND u.id_tipo = 3");
            }
        }
    }
    header("Location: Listado_Oficina.php?msg=estado");
    exit();
} else {
    header("Location: Listado_Oficina.php");
    exit();
}
?>
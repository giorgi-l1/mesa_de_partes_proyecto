<?php
session_start();
require 'conexion.php';

$id = intval($_POST["id_oficina"]);
$nombre = trim($_POST["nombre_oficina"]);
$siglas = strtoupper(trim($_POST["siglas"]));
$id_encargado_nuevo = $_POST["id_encargado"] ?? '';

// 1. Actualizar datos básicos de la oficina
$sql = "UPDATE oficinas SET nombre_oficina = '$nombre', siglas = '$siglas' WHERE id_oficina = $id";
$exito = mysqli_query($cn, $sql);

if ($exito && !empty($id_encargado_nuevo)) {
    // 2. Obtener la cuenta operativa actual (Tipo 7) asignada a esta oficina
    $q_cuenta_ofi = mysqli_query($cn, "SELECT id_usuario FROM datos_oficina_usuario WHERE id_oficina = $id AND id_rol_oficina = 1 LIMIT 1");

    if ($row_cuenta = mysqli_fetch_assoc($q_cuenta_ofi)) {
        $id_cuenta_ofi = $row_cuenta['id_usuario'];

        // 3. Identificar al trabajador base antiguo (Tipo 3) comparando el DNI con la cuenta operativa
        $q_dni_ofi = mysqli_query($cn, "SELECT numero_documento FROM datos_personales WHERE id_usuario = $id_cuenta_ofi");
        if ($row_dni = mysqli_fetch_assoc($q_dni_ofi)) {
            $dni_antiguo = $row_dni['numero_documento'];

            // Liberar al trabajador base antiguo directamente por su DNI
            mysqli_query($cn, "UPDATE datos_personal dp 
                               INNER JOIN datos_personales dpers ON dp.id_usuario = dpers.id_usuario 
                               INNER JOIN usuarios u ON u.id_usuario = dp.id_usuario 
                               SET dp.estado_asignado = NULL 
                               WHERE dpers.numero_documento = '$dni_antiguo' AND u.id_tipo = 3");
        }

        // 4. Obtener datos del NUEVO trabajador seleccionado y transferir el mando
        $q_base = mysqli_query($cn, "SELECT * FROM datos_personales WHERE id_usuario = $id_encargado_nuevo");
        if ($base = mysqli_fetch_assoc($q_base)) {
            // Reemplazar los datos de la cuenta operativa por los de la nueva persona
            mysqli_query($cn, "UPDATE datos_personales 
                               SET nombres = '{$base['nombres']}', apellido_paterno = '{$base['apellido_paterno']}', apellido_materno = '{$base['apellido_materno']}', numero_documento = '{$base['numero_documento']}' 
                               WHERE id_usuario = $id_cuenta_ofi");

            // Ocupar al nuevo trabajador base
            $nombre_asignacion = "Jefatura " . $siglas;
            mysqli_query($cn, "UPDATE datos_personal SET estado_asignado = '$nombre_asignacion' WHERE id_usuario = $id_encargado_nuevo");
        }
    }
}

echo "<!DOCTYPE html><html><head><script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script></head><body>";
if ($exito) {
    echo "<script>document.addEventListener('DOMContentLoaded', function() { Swal.fire({icon: 'success', title: '¡Actualizado!', text: 'La oficina fue actualizada.', confirmButtonColor: '#198754'}).then(() => { window.location = 'Listado_Oficina.php'; }); });</script>";
} else {
    echo "<script>document.addEventListener('DOMContentLoaded', function() { Swal.fire({icon: 'error', title: 'Error', text: 'No se pudo actualizar.', confirmButtonColor: '#dc3545'}).then(() => { history.back(); }); });</script>";
}
echo "</body></html>";
?>
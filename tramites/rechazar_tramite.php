<?php
session_start();

// Validar que sea un administrador o personal de mesa de partes
if (!isset($_SESSION["auth_mesa"]) || $_SESSION["auth_mesa"] != "1") {
    // Ruta correcta hacia atrás para salir de la carpeta actual
    header("Location: ../login_mesa.php");
    exit();
}

include("../conexion.php");

// Recibimos el ID (puede venir por POST o GET dependiendo de tu formulario)
$id = isset($_POST["id"]) ? intval($_POST["id"]) : (isset($_GET["id"]) ? intval($_GET["id"]) : 0);

if ($id === 0) {
    header("Location: ver_tramites.php");
    exit();
}

// 1. Recibir la observación escrita por el administrador (asumiendo que viene por POST)
$observacion_input = isset($_POST["observaciones"]) ? trim($_POST["observaciones"]) : '';

// 2. Escapar el texto para evitar inyecciones SQL
$observacion_limpia = mysqli_real_escape_string($cn, $observacion_input);

// Si por algún motivo llega vacío, asignamos un texto genérico
if (empty($observacion_limpia)) {
    $observacion_limpia = 'Observado/Rechazado por Mesa de Partes';
}

mysqli_begin_transaction($cn);

try {
    // 3. ACTUALIZACIÓN MEJORADA: Guardamos la observación directamente en la tabla 'tramites'
    mysqli_query($cn, "UPDATE tramites SET id_estado=4, observacion_admin='$observacion_limpia' WHERE id_tramite='$id'");

    $oficina = mysqli_fetch_assoc(mysqli_query($cn, "SELECT id_oficina_actual FROM tramites WHERE id_tramite='$id'"));

    $numero = mysqli_fetch_assoc(mysqli_query($cn, "SELECT IFNULL(MAX(numero_movimiento),0)+1 siguiente FROM movimientos_tramite WHERE id_tramite='$id'"));

    // 4. Inserción en 'movimientos_tramite' con la observación dinámica en lugar del texto en duro
    mysqli_query($cn, "INSERT INTO movimientos_tramite (id_tramite, numero_movimiento, id_oficina_origen, id_oficina_destino, id_estado_mov, observaciones) 
                       VALUES ('$id', '" . $numero["siguiente"] . "', '" . $oficina["id_oficina_actual"] . "', '" . $oficina["id_oficina_actual"] . "', 4, '$observacion_limpia')");

    // Confirmar cambios
    mysqli_commit($cn);

    // --- DISPARAR LA NOTIFICACIÓN ---
    require_once '../notificaciones.php';

    $query_datos = mysqli_query($cn, "SELECT id_usuario, numero_expediente FROM tramites WHERE id_tramite = '$id'");
    if ($datos_tramite = mysqli_fetch_assoc($query_datos)) {
        
        // 5. Incluimos el motivo de la observación en el mensaje de notificación para el usuario
        $mensaje = "Su trámite ha sido <b>Observado o Rechazado</b> por Mesa de Partes.<br><br><b>Motivo:</b> " . htmlspecialchars($observacion_input) . "<br><br>Por favor, revise las indicaciones o acérquese a la oficina para subsanar las observaciones.";

        notificar_usuario($cn, $datos_tramite['id_usuario'], 'rechazado', $datos_tramite['numero_expediente'], $mensaje);
    }
    // -----------------------------------

    header("Location: ver_tramites.php?rechazado=1");
} catch (Exception $e) {
    mysqli_rollback($cn);
    header("Location: ver_tramites.php?error=1");
}
?>
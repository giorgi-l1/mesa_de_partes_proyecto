<?php

session_start();

// Validar que sea un administrador o personal de mesa de partes
if (!isset($_SESSION["auth_mesa"]) || $_SESSION["auth_mesa"] != "1") {
    // Aquí usamos la ruta correcta hacia atrás para salir de la carpeta actual
    header("Location: ../login_mesa.php");
    exit();
}

include("../conexion.php");

if (!isset($_GET["id"])) {
    header("Location: ver_tramites.php");
    exit();
}

$id = intval($_GET["id"]);

mysqli_begin_transaction($cn);

try {
    mysqli_query($cn, "UPDATE tramites SET id_estado=4 WHERE id_tramite='$id'");

    $oficina = mysqli_fetch_assoc(mysqli_query($cn, "SELECT id_oficina_actual FROM tramites WHERE id_tramite='$id'"));

    $numero = mysqli_fetch_assoc(mysqli_query($cn, "SELECT IFNULL(MAX(numero_movimiento),0)+1 siguiente FROM movimientos_tramite WHERE id_tramite='$id'"));

    mysqli_query($cn, "INSERT INTO movimientos_tramite (id_tramite, numero_movimiento, id_oficina_origen, id_oficina_destino, id_estado_mov, observaciones) VALUES ('$id', '" . $numero["siguiente"] . "', '" . $oficina["id_oficina_actual"] . "', '" . $oficina["id_oficina_actual"] . "', 4, 'Observado/Rechazado por Mesa de Partes')");

    // Confirmar cambios
    mysqli_commit($cn);

    // --- DISPARAR LA NOTIFICACIÓN ---
    require_once '../notificaciones.php';

    $query_datos = mysqli_query($cn, "SELECT id_usuario, numero_expediente FROM tramites WHERE id_tramite = '$id'");
    if ($datos_tramite = mysqli_fetch_assoc($query_datos)) {

        $mensaje = "Su trámite ha sido <b>Observado o Rechazado</b> por Mesa de Partes. Por favor, revise las indicaciones o acérquese a la oficina para subsanar las observaciones.";

        notificar_usuario($cn, $datos_tramite['id_usuario'], 'rechazado', $datos_tramite['numero_expediente'], $mensaje);
    }
    // -----------------------------------

    header("Location: ver_tramites.php?rechazado=1");
} catch (Exception $e) {
    mysqli_rollback($cn);
    header("Location: ver_tramites.php?error=1");
}
?>
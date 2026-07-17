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

    // Iniciar transacción para asegurar que ambos procesos se guarden juntos
// Iniciar transacción para asegurar que ambos procesos se guarden juntos
    mysqli_begin_transaction($cn);
    $proceso_exitoso = false;

    try {
        // 1. Obtener la oficina actual
        $query_oficina = mysqli_query($cn, "SELECT id_oficina_actual FROM tramites WHERE id_tramite = $id_tramite");
        $data_oficina = mysqli_fetch_assoc($query_oficina);
        $id_oficina_actual = $data_oficina['id_oficina_actual'];

        // 2. Actualizar estado a 5 (Atendido/Finalizado)
        mysqli_query($cn, "UPDATE tramites SET id_estado = 5 WHERE id_tramite = $id_tramite");

        // 3. Siguiente número de movimiento
        $sqlNumero = mysqli_query($cn, "SELECT IFNULL(MAX(numero_movimiento),0)+1 AS siguiente FROM movimientos_tramite WHERE id_tramite='$id_tramite'");
        $numero = mysqli_fetch_assoc($sqlNumero);
        $numero_movimiento = $numero["siguiente"];

        // 4. Registrar en el historial (Solo UNA vez)
        mysqli_query($cn, "INSERT INTO movimientos_tramite 
        (id_tramite, numero_movimiento, id_oficina_origen, id_oficina_destino, id_estado_mov, observaciones) 
        VALUES 
        ('$id_tramite', '$numero_movimiento', '$id_oficina_actual', '$id_oficina_actual', 5, 'Trámite Atendido y Finalizado por la oficina.')");

        // 5. ¡IMPORTANTE! Confirmamos los cambios en la BD
        mysqli_commit($cn);
        $proceso_exitoso = true;

    } catch (Exception $e) {
        mysqli_rollback($cn);
        header("Location: principal_oficina.php?error=bd");
        exit();
    }

    // 6. ---- MAGIA DE LA NOTIFICACIÓN (Aislado) ----
    if ($proceso_exitoso) {
        try {
            require_once 'notificaciones.php';
            $query_datos = mysqli_query($cn, "SELECT id_usuario, numero_expediente FROM tramites WHERE id_tramite = $id_tramite");

            if ($datos_tramite = mysqli_fetch_assoc($query_datos)) {
                $mensaje_oficina = "La oficina ha marcado tu trámite como Atendido y Finalizado. Puedes acercarte a recoger tus documentos si corresponde.";
                notificar_usuario($cn, $datos_tramite['id_usuario'], 'finalizado', $datos_tramite['numero_expediente'], $mensaje_oficina);
            }
        } catch (Throwable $t) {
            // Silenciamos cualquier error de la API para que no rompa el flujo visual
        }

        header("Location: principal_oficina.php?ok=finalizado");
        exit();
    }
} else {
    header("Location: principal_oficina.php");
    exit();
}
?>
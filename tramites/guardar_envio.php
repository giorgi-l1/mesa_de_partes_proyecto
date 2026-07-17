<?php

session_start();

// 1. Verificamos si es Mesa de Partes
$es_mesa = (isset($_SESSION["auth_mesa"]) && $_SESSION["auth_mesa"] == "1");

// 2. Verificamos si es una Oficina (Ajusta "id_oficina" por la variable de sesión exacta que uses en tu login de oficinas)
$es_oficina = isset($_SESSION["id_oficina"]);

// 3. Si no es NINGUNO de los dos, lo expulsamos
if (!$es_mesa && !$es_oficina) {
    // Lo mandamos al index principal o login general
    header("Location: ../index.php");
    exit();
}

include("../conexion.php");

if ($_SERVER["REQUEST_METHOD"] != "POST") {
    header("Location: ver_tramites.php");
    exit();
}

$id_tramite = intval($_POST["id_tramite"]);
$oficina_origen = intval($_POST["oficina_origen"]);
$oficina_destino = intval($_POST["oficina_destino"]);

// Iniciar transacción correctamente
mysqli_begin_transaction($cn);

try {
    // 1. Obtener número de movimiento
    $sqlNumero = mysqli_query($cn, "SELECT IFNULL(MAX(numero_movimiento),0)+1 AS siguiente FROM movimientos_tramite WHERE id_tramite='$id_tramite'");
    $numero = mysqli_fetch_assoc($sqlNumero);
    $numero_movimiento = $numero["siguiente"];

    // 2. Actualizar trámite a estado 3 (Derivado)
    mysqli_query($cn, "UPDATE tramites SET id_oficina_actual='$oficina_destino', id_estado='3' WHERE id_tramite='$id_tramite'");

    // 3. Registrar movimiento
    mysqli_query($cn, "INSERT INTO movimientos_tramite (id_tramite, numero_movimiento, id_oficina_origen, id_oficina_destino, id_estado_mov, observaciones) VALUES ('$id_tramite', '$numero_movimiento', '$oficina_origen', '$oficina_destino', 3, 'Derivado desde Mesa de Partes')");

    // 4. Confirmar cambios en la Base de Datos
    mysqli_commit($cn);
    $exito = true;

    // --- 5. DISPARAR LA NOTIFICACIÓN ---
    require_once '../notificaciones.php';

    // Buscar los datos del trámite
    $query_datos = mysqli_query($cn, "SELECT id_usuario, numero_expediente FROM tramites WHERE id_tramite = '$id_tramite'");
    if ($datos_tramite = mysqli_fetch_assoc($query_datos)) {
        // Obtenemos el nombre de la oficina destino para un mensaje más personalizado
        $q_of = mysqli_query($cn, "SELECT nombre_oficina FROM oficinas WHERE id_oficina = '$oficina_destino'");
        $d_of = mysqli_fetch_assoc($q_of);
        $nombre_oficina = $d_of['nombre_oficina'];

        $mensaje = "Su trámite ha sido validado por Mesa de Partes y derivado al área de <b>$nombre_oficina</b> para continuar con su atención.";

        // Llamar a nuestra súper función
        notificar_usuario($cn, $datos_tramite['id_usuario'], 'derivado', $datos_tramite['numero_expediente'], $mensaje);
    }
    // -----------------------------------

} catch (Exception $e) {
    // Revertir si hay error
    mysqli_rollback($cn);
    $exito = false;
}
?>
<!DOCTYPE html>
<html>

<head>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body>
    <script>
        <?php if ($exito): ?>
            Swal.fire({
                icon: 'success',
                title: 'Trámite derivado',
                text: 'El trámite fue enviado correctamente al área correspondiente.',
                confirmButtonColor: '#198754'
            }).then(() => {
                window.location = 'ver_tramites.php';
            });
        <?php else: ?>
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'No se pudo enviar el trámite.',
                confirmButtonColor: '#dc3545'
            }).then(() => {
                history.back();
            });
        <?php endif; ?>
    </script>
</body>

</html>
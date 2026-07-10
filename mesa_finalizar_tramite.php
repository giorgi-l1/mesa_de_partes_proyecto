<?php
session_start();

if (!isset($_SESSION["auth_mesa"]) || $_SESSION["auth_mesa"] != "1") {
    header("Location: login_mesa.php");
    exit();
}

require 'conexion.php';

if ($_SERVER["REQUEST_METHOD"] != "POST" || !isset($_POST['id_tramite'])) {
    header("Location: principal_mesa.php");
    exit();
}

$id_usuario = $_SESSION["id_usuario"];
$id_tramite = intval($_POST['id_tramite']);

const ID_ESTADO_FINALIZADO = 5;

// ----------------------------------------------------
// 1. VERIFICAR QUE EL TRABAJADOR PERTENECE A UNA OFICINA
//    Y QUE SU ROL TIENE PERMISO PARA FINALIZAR TRÁMITES
// ----------------------------------------------------
$query_staff = "SELECT du.id_oficina, r.puede_finalizar
                FROM datos_oficina_usuario du
                INNER JOIN roles_oficina r ON du.id_rol_oficina = r.id_rol_oficina
                WHERE du.id_usuario = '$id_usuario'
                LIMIT 1";
$res_staff = mysqli_query($cn, $query_staff);

if (!$res_staff || mysqli_num_rows($res_staff) == 0) {
    header("Location: principal_mesa.php?error=1");
    exit();
}

$staff = mysqli_fetch_assoc($res_staff);
$id_oficina = $staff['id_oficina'];

if ($staff['puede_finalizar'] != 1) {
    // El rol del trabajador no tiene permiso para finalizar trámites
    header("Location: principal_mesa.php?error=1");
    exit();
}

// ----------------------------------------------------
// 2. VERIFICAR QUE EL TRÁMITE REALMENTE ESTÁ EN LA BANDEJA
//    DE LA OFICINA DEL TRABAJADOR (evita manipular otros trámites)
// ----------------------------------------------------
$query_check = "SELECT id_tramite FROM tramites
                WHERE id_tramite = '$id_tramite' AND id_oficina_actual = '$id_oficina'
                LIMIT 1";
$res_check = mysqli_query($cn, $query_check);

if (!$res_check || mysqli_num_rows($res_check) == 0) {
    header("Location: principal_mesa.php?error=1");
    exit();
}

// ----------------------------------------------------
// 3. ACTUALIZAR EL ESTADO DEL TRÁMITE
// ----------------------------------------------------
$query_update = "UPDATE tramites SET id_estado = " . ID_ESTADO_FINALIZADO . "
                 WHERE id_tramite = '$id_tramite'";
mysqli_query($cn, $query_update);

// ----------------------------------------------------
// 4. REGISTRAR EL MOVIMIENTO EN EL HISTORIAL DEL TRÁMITE
// ----------------------------------------------------
$query_num_mov = "SELECT COALESCE(MAX(numero_movimiento), 0) + 1 AS siguiente
                  FROM movimientos_tramite WHERE id_tramite = '$id_tramite'";
$res_num_mov = mysqli_query($cn, $query_num_mov);
$siguiente_movimiento = 1;
if ($res_num_mov && $fila = mysqli_fetch_assoc($res_num_mov)) {
    $siguiente_movimiento = intval($fila['siguiente']);
}

$observacion = "El trámite fue finalizado por el personal de la oficina.";
$query_movimiento = "INSERT INTO movimientos_tramite
                     (id_tramite, numero_movimiento, id_oficina_origen, id_oficina_destino, id_estado_mov, fecha_recepcion, observaciones)
                     VALUES ('$id_tramite', '$siguiente_movimiento', '$id_oficina', '$id_oficina', " . ID_ESTADO_FINALIZADO . ", NOW(), '$observacion')";
mysqli_query($cn, $query_movimiento);

header("Location: principal_mesa.php?ok=finalizado");
exit();

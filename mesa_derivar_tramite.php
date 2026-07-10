<?php
session_start();

if (!isset($_SESSION["auth_mesa"]) || $_SESSION["auth_mesa"] != "1") {
    header("Location: login_mesa.php");
    exit();
}

require 'conexion.php';

if ($_SERVER["REQUEST_METHOD"] != "POST" || !isset($_POST['id_tramite']) || !isset($_POST['id_oficina_destino'])) {
    header("Location: principal_mesa.php");
    exit();
}

$id_usuario = $_SESSION["id_usuario"];
$id_tramite = intval($_POST['id_tramite']);
$id_oficina_destino = intval($_POST['id_oficina_destino']);
$observaciones = trim($_POST['observaciones']);
if ($observaciones === "") {
    $observaciones = "El trámite fue derivado a otra área para continuar su gestión.";
}

const ID_ESTADO_DERIVADO = 3;

// ----------------------------------------------------
// 1. VERIFICAR QUE EL TRABAJADOR PERTENECE A UNA OFICINA
//    Y QUE SU ROL TIENE PERMISO PARA DERIVAR TRÁMITES
// ----------------------------------------------------
$query_staff = "SELECT du.id_oficina, r.puede_derivar
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
$id_oficina_origen = $staff['id_oficina'];

if ($staff['puede_derivar'] != 1) {
    header("Location: principal_mesa.php?error=1");
    exit();
}

// No tiene sentido derivar el trámite a la misma oficina en la que ya está
if ($id_oficina_destino == $id_oficina_origen) {
    header("Location: principal_mesa.php?error=1");
    exit();
}

// ----------------------------------------------------
// 2. VERIFICAR QUE EL TRÁMITE ESTÁ EN LA BANDEJA DE LA OFICINA
//    DEL TRABAJADOR Y QUE LA OFICINA DESTINO EXISTE
// ----------------------------------------------------
$query_check = "SELECT id_tramite FROM tramites
                WHERE id_tramite = '$id_tramite' AND id_oficina_actual = '$id_oficina_origen'
                LIMIT 1";
$res_check = mysqli_query($cn, $query_check);

if (!$res_check || mysqli_num_rows($res_check) == 0) {
    header("Location: principal_mesa.php?error=1");
    exit();
}

$query_check_destino = "SELECT id_oficina FROM oficinas WHERE id_oficina = '$id_oficina_destino' LIMIT 1";
$res_check_destino = mysqli_query($cn, $query_check_destino);

if (!$res_check_destino || mysqli_num_rows($res_check_destino) == 0) {
    header("Location: principal_mesa.php?error=1");
    exit();
}

// ----------------------------------------------------
// 3. ACTUALIZAR LA UBICACIÓN ACTUAL DEL TRÁMITE
// ----------------------------------------------------
$query_update = "UPDATE tramites
                 SET id_oficina_actual = '$id_oficina_destino', id_estado = " . ID_ESTADO_DERIVADO . "
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

$observaciones_escapadas = mysqli_real_escape_string($cn, $observaciones);
$query_movimiento = "INSERT INTO movimientos_tramite
                     (id_tramite, numero_movimiento, id_oficina_origen, id_oficina_destino, id_estado_mov, observaciones)
                     VALUES ('$id_tramite', '$siguiente_movimiento', '$id_oficina_origen', '$id_oficina_destino', " . ID_ESTADO_DERIVADO . ", '$observaciones_escapadas')";
mysqli_query($cn, $query_movimiento);

header("Location: principal_mesa.php?ok=derivado");
exit();

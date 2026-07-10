<?php
session_start();

if (!isset($_SESSION["auth"]) || $_SESSION["auth"] != "1" || $_SERVER["REQUEST_METHOD"] != "POST") {
    header("Location: index.php");
    exit();
}

require 'conexion.php';

$id_usuario = $_SESSION["id_usuario"];
$id_tipo_ticket = mysqli_real_escape_string($cn, $_POST['id_tipo_ticket']);
$asunto = mysqli_real_escape_string($cn, $_POST['asunto']);
$descripcion_problema = mysqli_real_escape_string($cn, $_POST['descripcion_problema']);

// Manejo seguro de campos opcionales para evitar errores de Foreign Key. 
// Si viene vacío, se guarda como NULL en la base de datos, no como string vacío.
$id_tramite = !empty($_POST['id_tramite']) ? intval($_POST['id_tramite']) : 'NULL';
$id_oficina = !empty($_POST['id_oficina']) ? intval($_POST['id_oficina']) : 'NULL';

// -------------------------------------------------------------
// 1. GENERACIÓN DEL CÓDIGO DEL TICKET (Formato: TCK-YYYY-XXXXXX)
// -------------------------------------------------------------
$anio_actual = date('Y');
$prefijo = "TCK-" . $anio_actual . "-";

$query_tck = "SELECT codigo_ticket FROM tickets_ayuda WHERE codigo_ticket LIKE '$prefijo%' ORDER BY id_ticket DESC LIMIT 1";
$res_tck = mysqli_query($cn, $query_tck);

if (mysqli_num_rows($res_tck) > 0) {
    $fila_tck = mysqli_fetch_assoc($res_tck);
    $ultimo_tck = $fila_tck['codigo_ticket'];
    // Extraer los últimos 6 dígitos y sumar 1
    $correlativo = intval(substr($ultimo_tck, 9)) + 1;
} else {
    // Si es el primer ticket del año
    $correlativo = 1;
}

$codigo_ticket = $prefijo . str_pad($correlativo, 6, "0", STR_PAD_LEFT);

// -------------------------------------------------------------
// 2. INSERCIÓN EN BASE DE DATOS
// -------------------------------------------------------------
$sql_insert = "INSERT INTO tickets_ayuda (codigo_ticket, id_usuario, id_tipo_ticket, id_tramite, id_oficina, asunto, descripcion_problema) 
               VALUES ('$codigo_ticket', '$id_usuario', '$id_tipo_ticket', $id_tramite, $id_oficina, '$asunto', '$descripcion_problema')";

if (mysqli_query($cn, $sql_insert)) {
    header("Location: mesa_ayuda.php?status=success&tck=" . $codigo_ticket);
    exit();
} else {
    header("Location: mesa_ayuda.php?error=db");
    exit();
}
?>
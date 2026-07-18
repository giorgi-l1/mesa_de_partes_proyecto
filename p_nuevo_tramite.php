<?php
session_start();

if (!isset($_SESSION["auth"]) || $_SESSION["auth"] != "1" || $_SERVER["REQUEST_METHOD"] != "POST") {
    header("Location: index.php");
    exit();
}

require 'conexion.php';

$id_usuario = $_SESSION["id_usuario"];
$id_tipo_tramite = mysqli_real_escape_string($cn, $_POST['id_tipo_tramite']);
$asunto = mysqli_real_escape_string($cn, $_POST['asunto']);
$descripcion_motivo = mysqli_real_escape_string($cn, $_POST['descripcion_motivo']);
$enlace_externo = !empty($_POST['enlace_externo']) ? mysqli_real_escape_string($cn, $_POST['enlace_externo']) : 'NULL';

// -------------------------------------------------------------
// 1. GENERACIÓN DEL NÚMERO DE EXPEDIENTE (Formato: EXP-YYYY-XXXXXX)
// ------sq-------------------------------------------------------
$anio_actual = date('Y');
$prefijo = "EXP-" . $anio_actual . "-";

$query_exp = "SELECT numero_expediente FROM tramites WHERE numero_expediente LIKE '$prefijo%' ORDER BY id_tramite DESC LIMIT 1";
$res_exp = mysqli_query($cn, $query_exp);

if (mysqli_num_rows($res_exp) > 0) {
    $fila_exp = mysqli_fetch_assoc($res_exp);
    $ultimo_exp = $fila_exp['numero_expediente'];
    // Extraer los últimos 6 dígitos y sumar 1
    $correlativo = intval(substr($ultimo_exp, 9)) + 1;
} else {
    // Si no hay trámites este año, empieza en 1
    $correlativo = 1;
}
// Formateamos rellenando con ceros a la izquierda (Ej: 000001)
$numero_expediente = $prefijo . str_pad($correlativo, 6, "0", STR_PAD_LEFT);

// -------------------------------------------------------------
// 2. VALIDACIÓN Y SUBIDA DE DOCUMENTO (Si existe)
// -------------------------------------------------------------
$ruta_bd = 'NULL';
$nombre_archivo_bd = 'NULL';
$tiene_archivo = false;

if (isset($_FILES['documento']) && $_FILES['documento']['error'] == 0) {
    $archivo = $_FILES['documento'];
    $nombre_original = $archivo['name'];
    $tamano = $archivo['size'];
    $temporal = $archivo['tmp_name'];

    // Validación de formato PDF
    $ext = strtolower(pathinfo($nombre_original, PATHINFO_EXTENSION));
    if ($ext !== 'pdf' || mime_content_type($temporal) !== 'application/pdf') {
        header("Location: nuevo_tramite.php?error=formato");
        exit();
    }

    // Validación de peso (5MB = 5 * 1024 * 1024 bytes = 5242880 bytes)
    if ($tamano > 5242880) {
        header("Location: nuevo_tramite.php?error=peso");
        exit();
    }

    // Crear carpeta dinámicamente si no existe
    $carpeta_destino = "documentos_adjuntos/" . $anio_actual . "/";
    if (!file_exists($carpeta_destino)) {
        mkdir($carpeta_destino, 0777, true);
    }

    // Nombre único para no sobreescribir (Ej: EXP-2026-000001_171542.pdf)
    $nuevo_nombre = str_replace('-', '_', $numero_expediente) . "_" . time() . ".pdf";
    $ruta_final = $carpeta_destino . $nuevo_nombre;

    if (move_uploaded_file($temporal, $ruta_final)) {
        $ruta_bd = "'" . mysqli_real_escape_string($cn, $ruta_final) . "'";
        $nombre_archivo_bd = "'" . mysqli_real_escape_string($cn, $nuevo_nombre) . "'";
        $tiene_archivo = true;
    }
}
// -------------------------------------------------------------
// 3. INSERCIÓN EN BASE DE DATOS (Transacción)
// -------------------------------------------------------------
mysqli_begin_transaction($cn);

try {
    // A. Forzamos el área de destino a UTD Central / Mesa de Partes (id = 1)
    $id_oficina_actual = 1;
    $id_estado = 1; // 1 = Pendiente

    // B. Insertar la Cabecera del Trámite
    $sql_tramite = "INSERT INTO tramites (numero_expediente, id_usuario, id_tipo_tramite, id_oficina_actual, id_estado, asunto, descripcion_motivo) 
                    VALUES ('$numero_expediente', '$id_usuario', '$id_tipo_tramite', '$id_oficina_actual', '$id_estado', '$asunto', '$descripcion_motivo')";
    mysqli_query($cn, $sql_tramite);
    $id_tramite_generado = mysqli_insert_id($cn);

    // C. Insertar el Adjunto (Si subió archivo o mandó enlace)
    if ($tiene_archivo || $enlace_externo !== 'NULL') {
        $enlace_bd = $enlace_externo !== 'NULL' ? "'$enlace_externo'" : "NULL";
        $sql_adjunto = "INSERT INTO documentos_adjuntos (id_tramite, nombre_adjunto, nombre_archivo, ruta_archivo, enlace_externo) 
                        VALUES ('$id_tramite_generado', 'Sustento Principal', $nombre_archivo_bd, $ruta_bd, $enlace_bd)";
        mysqli_query($cn, $sql_adjunto);
    }

    // D. Registrar el Primer Movimiento (Historial)
    // Ingresa con origen NULL (Usuario Web) hacia el destino 1 (Mesa de Partes / UTD)
    $observacion_mov = "Documento registrado vía Mesa de Partes Virtual. Pendiente de revisión por UTD.";
    // Ingresa con origen 5 (Plataforma Web) hacia el destino 1 (Mesa de Partes / UTD)
    $sql_movimiento = "INSERT INTO movimientos_tramite (id_tramite, numero_movimiento, id_oficina_origen, id_oficina_destino, id_estado_mov, observaciones) 
                       VALUES ('$id_tramite_generado', 1, 5, 1, '$id_estado', '$observacion_mov')";
    mysqli_query($cn, $sql_movimiento);

    // Confirmar todo
    mysqli_commit($cn);

    header("Location: nuevo_tramite.php?status=success&exp=" . $numero_expediente);
    exit();

} catch (Exception $e) {
    mysqli_rollback($cn);
    header("Location: nuevo_tramite.php?error=db");
    exit();
}
?>
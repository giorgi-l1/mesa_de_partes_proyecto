<?php
//include("../auth.php");
include("../conexion.php");

if(!isset($_GET["id"]))
{
    header("Location: ../principal_mesa.php");
    exit();
}

$id = intval($_GET["id"]);

/* Cambiar estado a 4 (Rechazado) */
mysqli_query($cn,"
UPDATE tramites
SET id_estado=4
WHERE id_tramite='$id'
");

/* Obtener oficina actual */
$oficina = mysqli_fetch_assoc(mysqli_query($cn,"
SELECT id_oficina_actual
FROM tramites
WHERE id_tramite='$id'
"));

/* Número de movimiento */
$numero = mysqli_fetch_assoc(mysqli_query($cn,"
SELECT IFNULL(MAX(numero_movimiento),0)+1 siguiente
FROM movimientos_tramite
WHERE id_tramite='$id'
"));

mysqli_query($cn,"
INSERT INTO movimientos_tramite
(
id_tramite,
numero_movimiento,
id_oficina_origen,
id_oficina_destino,
id_estado_mov,
observaciones
)
VALUES
(
'$id',
'".$numero["siguiente"]."',
'".$oficina["id_oficina_actual"]."',
'".$oficina["id_oficina_actual"]."',
4,
'Rechazado por Mesa de Partes'
)
");

// Redirección corregida al panel principal de mesa
header("Location: ../principal_mesa.php?ok=rechazado");
exit();
?>
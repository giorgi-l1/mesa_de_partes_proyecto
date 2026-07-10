<?php

//include("../auth.php");
include("../conexion.php");

if ($_SERVER["REQUEST_METHOD"] != "POST") {
    header("Location: ver_tramites.php");
    exit();
}

$id_tramite = intval($_POST["id_tramite"]);
$oficina_origen = intval($_POST["oficina_origen"]);
$oficina_destino = intval($_POST["oficina_destino"]);
if(mysqli_commit($cn))
{
?>

<!DOCTYPE html>

<html>

<head>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

</head>

<body>

<script>

Swal.fire({

icon:'success',

title:'Trámite derivado',

text:'El trámite fue enviado correctamente al área correspondiente.',

confirmButtonColor:'#198754'

}).then(()=>{

window.location='ver_tramites.php';

});

</script>

</body>

</html>

<?php

}
else
{

mysqli_rollback($cn);

?>

<!DOCTYPE html>

<html>

<head>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

</head>

<body>

<script>

Swal.fire({

icon:'error',

title:'Error',

text:'No se pudo enviar el trámite.',

confirmButtonColor:'#dc3545'

}).then(()=>{

history.back();

});

</script>

</body>

</html>

<?php

}

/* Obtener el siguiente número de movimiento */

$sqlNumero = mysqli_query($cn,"
SELECT IFNULL(MAX(numero_movimiento),0)+1 AS siguiente
FROM movimientos_tramite
WHERE id_tramite='$id_tramite'
");

$numero = mysqli_fetch_assoc($sqlNumero);
$numero_movimiento = $numero["siguiente"];

/* Iniciar transacción */

mysqli_begin_transaction($cn);

try{

    /* Actualizar trámite */

    mysqli_query($cn,"
    UPDATE tramites
    SET
        id_oficina_actual='$oficina_destino',
        id_estado='3'
    WHERE id_tramite='$id_tramite'
    ");

    /* Registrar movimiento */

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
        '$id_tramite',
        '$numero_movimiento',
        '$oficina_origen',
        '$oficina_destino',
        3,
        'Derivado desde Mesa de Partes'
    )
    ");

    mysqli_commit($cn);

    header("Location: ver_tramites.php?ok=1");

}catch(Exception $e){

    mysqli_rollback($cn);

    echo "Ocurrió un error al enviar el trámite.";

}

?>
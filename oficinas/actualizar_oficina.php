<?php

// include("../auth.php");
include("../conexion.php");

$id = intval($_POST["id_oficina"]);

$nombre = trim($_POST["nombre_oficina"]);

$siglas = strtoupper(trim($_POST["siglas"]));

$sql="UPDATE oficinas
SET
nombre_oficina='$nombre',
siglas='$siglas'
WHERE id_oficina='$id'";

if(mysqli_query($cn,$sql))
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

title:'Actualizado',

text:'La oficina fue actualizada correctamente.',

confirmButtonColor:'#198754'

}).then(()=>{

window.location='oficinas.php';

});

</script>

</body>

</html>

<?php
}
else
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

icon:'error',

title:'Error',

text:'No se pudo actualizar la oficina.',

confirmButtonColor:'#dc3545'

}).then(()=>{

history.back();

});

</script>

</body>

</html>

<?php
}
?>
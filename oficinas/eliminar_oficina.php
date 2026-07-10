<?php

// include("../auth.php");
include("../conexion.php");

$id = intval($_GET["id"]);

$sql="DELETE FROM oficinas
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

title:'Eliminado',

text:'La oficina fue eliminada correctamente.',

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

text:'No se pudo eliminar la oficina.',

confirmButtonColor:'#dc3545'

}).then(()=>{

window.location='oficinas.php';

});

</script>

</body>

</html>

<?php
}
?>
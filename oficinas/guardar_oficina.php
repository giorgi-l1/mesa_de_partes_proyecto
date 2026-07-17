<?php

// include("../auth.php");
include("../conexion.php");

$nombre = trim($_POST["nombre_oficina"]);
$siglas = strtoupper(trim($_POST["siglas"]));

$consulta = mysqli_query($cn,"
SELECT *
FROM oficinas
WHERE nombre_oficina='$nombre'
");

if(mysqli_num_rows($consulta)>0)
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

icon:'warning',

title:'Atención',

text:'La oficina ya existe.',

confirmButtonColor:'#7b1e3d'

}).then(()=>{

window.location='registrar_oficina.php';

});

</script>

</body>
</html>

<?php
exit();
}

$sql="INSERT INTO oficinas
(
nombre_oficina,
siglas
)

VALUES
(
'$nombre',
'$siglas'
)";

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

title:'¡Registro exitoso!',

text:'La oficina fue registrada correctamente.',

confirmButtonColor:'#198754'

}).then(()=>{

window.location='oficinas_listar.php';

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

text:'No se pudo registrar la oficina.',

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
<?php
// include("../auth.php");
include("../conexion.php");

$sql = "SELECT * FROM oficinas ORDER BY id_oficina ASC";
$resultado = mysqli_query($cn, $sql);
?>

<!DOCTYPE html>
<html lang="es">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Panel de Gestión de Oficinas</title>

<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600&family=Montserrat:wght@300;400;500;600&display=swap" rel="stylesheet">

<link rel="stylesheet" href="../css/dashboard.css">

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>

.table-container{
    overflow-x:auto;
}

table{
    width:100%;
    border-collapse:collapse;
    margin-top:20px;
}

table th{
    background:#7b1e3d;
    color:white;
    padding:12px;
}

table td{
    padding:12px;
    border-bottom:1px solid #ddd;
}

td:last-child{
    text-align:center;
}

.btn{
    display:inline-block;
    padding:8px 14px;
    border-radius:6px;
    color:white;
    text-decoration:none;
    font-size:13px;
    transition:.3s;
    margin:0 3px;
}

.btn:hover{
    opacity:.90;
}

.btn-nuevo{
    background:#198754;
    display:inline-block;
    margin-top:15px;
}

.btn-editar{
    background:#0d6efd;
}

.btn-eliminar{
    background:#dc3545;
}

</style>

</head>

<body>

<div class="container">

<div class="panel">

<h2 class="panel-title">

Panel de Gestión de Oficinas

</h2>

<p>

Desde este módulo podrá registrar, editar y eliminar las oficinas que participan en el flujo de Mesa de Partes.

</p>

<p>

<a href="registrar_oficina.php" class="btn btn-nuevo">

+ Nueva Oficina

</a>

</p>

<div class="table-container">

<table>

<thead>

<tr>

<th>ID</th>

<th>Nombre de la Oficina</th>

<th>Siglas</th>

<th>Acciones</th>

</tr>

</thead>

<tbody>

<?php

if(mysqli_num_rows($resultado)>0)
{

while($fila=mysqli_fetch_assoc($resultado))
{

?>

<tr>

<td>

<?php echo $fila["id_oficina"];?>

</td>

<td>

<?php echo htmlspecialchars($fila["nombre_oficina"]);?>

</td>

<td>

<?php echo htmlspecialchars($fila["siglas"]);?>

</td>

<td>

<a
class="btn btn-editar"
href="editar_oficina.php?id=<?php echo $fila["id_oficina"];?>">

Editar

</a>

<a
class="btn btn-eliminar"
href="#"
onclick="confirmarEliminar(<?php echo $fila['id_oficina']; ?>); return false;">

Eliminar

</a>

</td>

</tr>

<?php

}

}
else
{

?>

<tr>

<td colspan="4" style="text-align:center;padding:30px;color:#777;">

No existen oficinas registradas.

</td>

</tr>

<?php

}

?>

</tbody>

</table>

</div>

</div>

</div>

<script>

function confirmarEliminar(id){

Swal.fire({

title:'¿Está seguro?',

text:'La oficina será eliminada permanentemente.',

icon:'warning',

showCancelButton:true,

confirmButtonColor:'#dc3545',

cancelButtonColor:'#6c757d',

confirmButtonText:'Sí, eliminar',

cancelButtonText:'Cancelar',

reverseButtons:true

}).then((result)=>{

if(result.isConfirmed){

window.location='eliminar_oficina.php?id='+id;

}

});

}

</script>

</body>

</html>
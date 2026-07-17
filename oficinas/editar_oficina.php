<?php
//include("../auth.php");
include("../conexion.php");

if(!isset($_GET["id"])){
    header("Location: oficinas_listar.php");
    exit();
}

$id = intval($_GET["id"]);

$sql = "SELECT * FROM oficinas WHERE id_oficina='$id'";
$resultado = mysqli_query($cn,$sql);

if(mysqli_num_rows($resultado)==0){
    header("Location: oficinas.php");
    exit();
}

$fila = mysqli_fetch_assoc($resultado);
?>

<!DOCTYPE html>
<html lang="es">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Editar Oficina</title>

<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600&family=Montserrat:wght@300;400;500;600&display=swap" rel="stylesheet">

<link rel="stylesheet" href="../css/dashboard.css">

<style>

.formulario{
max-width:700px;
margin:auto;
}

.form-group{
margin-bottom:20px;
}

.form-group label{
display:block;
margin-bottom:8px;
font-weight:bold;
}

.form-control{
width:100%;
padding:12px;
border:1px solid #ccc;
border-radius:8px;
}

.botones{
margin-top:25px;
display:flex;
gap:10px;
}

.btn{
padding:10px 18px;
border-radius:8px;
text-decoration:none;
color:white;
font-weight:bold;
border:none;
cursor:pointer;
}

.btn-actualizar{
background:#0d6efd;
}

.btn-cancelar{
background:#6c757d;
}

</style>

</head>

<body>

<div class="container">

<div class="panel formulario">

<h2 class="panel-title">

Editar Oficina

</h2>

<form action="actualizar_oficina.php" method="POST">

<input
type="hidden"
name="id_oficina"
value="<?php echo $fila['id_oficina'];?>">

<div class="form-group">

<label>Nombre Oficina</label>

<input
type="text"
name="nombre_oficina"
class="form-control"
value="<?php echo htmlspecialchars($fila['nombre_oficina']);?>"
required>

</div>

<div class="form-group">

<label>Siglas</label>

<input
type="text"
name="siglas"
class="form-control"
maxlength="20"
value="<?php echo htmlspecialchars($fila['siglas']);?>"
required>

</div>

<div class="botones">

<button
class="btn btn-actualizar"
type="submit">

Actualizar

</button>

<a
href="oficinas_listar.php"
class="btn btn-cancelar">

Cancelar

</a>

</div>

</form>

</div>

</div>

</body>

</html>
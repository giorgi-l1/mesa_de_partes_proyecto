<?php
//include("../auth.php");
include("../conexion.php");
?>

<!DOCTYPE html>
<html lang="es">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Registrar Oficina</title>

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

font-weight:600;

}

.form-control{

width:100%;

padding:12px;

border:1px solid #ccc;

border-radius:8px;

font-size:15px;

}

.botones{

margin-top:25px;

display:flex;

gap:10px;

}

.btn{

padding:10px 18px;

border:none;

border-radius:8px;

cursor:pointer;

text-decoration:none;

color:white;

font-weight:bold;

}

.btn-guardar{

background:#198754;

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

Registrar Oficina

</h2>

<form action="guardar_oficina.php" method="POST">

<div class="form-group">

<label>

Nombre de la Oficina

</label>

<input
type="text"
name="nombre_oficina"
class="form-control"
required>

</div>

<div class="form-group">

<label>

Siglas

</label>

<input
type="text"
name="siglas"
class="form-control"
maxlength="20"
style="text-transform:uppercase;"
required>

</div>

<div class="botones">

<button
type="submit"
class="btn btn-guardar">

Guardar

</button>

<a
href="oficinas.php"
class="btn btn-cancelar">

Cancelar

</a>

</div>

</form>

</div>

</div>

</body>

</html>
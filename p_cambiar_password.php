<?php
session_start();

if (!isset($_SESSION["auth"]) || $_SESSION["auth"] != "1" || $_SERVER["REQUEST_METHOD"] != "POST") {
    header("Location: index.php");
    exit();
}

require 'conexion.php'; 

$id_usuario = $_SESSION["id_usuario"];

// Capturamos y sanitizamos las claves
$pass_actual    = mysqli_real_escape_string($cn, $_POST['pass_actual']);
$pass_nueva     = mysqli_real_escape_string($cn, $_POST['pass_nueva']);
$pass_confirmar = mysqli_real_escape_string($cn, $_POST['pass_confirmar']);

// Validamos que los campos no vengan vacíos
if(empty($pass_actual) || empty($pass_nueva) || empty($pass_confirmar)) {
    header("Location: cambiar_password.php?error=vacia");
    exit();
}

// Validamos coincidencia
if($pass_nueva !== $pass_confirmar) {
    header("Location: cambiar_password.php?error=coincidencia");
    exit();
}


// 1. Verificamos que la contraseña actual sea la correcta en la BD
$query_verificar = "SELECT password FROM usuarios WHERE id_usuario = '$id_usuario'";
$resultado_verificar = mysqli_query($cn, $query_verificar);
$fila = mysqli_fetch_assoc($resultado_verificar);

if($fila['password'] !== $pass_actual) {
    header("Location: cambiar_password.php?error=incorrecta");
    exit();
}

// 2. Realizamos la actualización
$query_update = "UPDATE usuarios SET password = '$pass_nueva' WHERE id_usuario = '$id_usuario'";

if(mysqli_query($cn, $query_update)) {
    header("Location: cambiar_password.php?status=success");
    exit();
} else {
    header("Location: cambiar_password.php?error=db");
    exit();
}
?>
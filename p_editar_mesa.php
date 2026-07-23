<?php
session_start();
require 'conexion.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id_usuario = (int) $_POST['id_usuario'];
    $cargo = mysqli_real_escape_string($cn, trim($_POST['cargo']));
    $estado_asignado = mysqli_real_escape_string($cn, trim($_POST['estado_asignado']));
    $password = mysqli_real_escape_string($cn, trim($_POST['password']));

    // Actualizar cargo y asignación en datos_personal
    mysqli_query($cn, "UPDATE datos_personal SET cargo = '$cargo', estado_asignado = '$estado_asignado' WHERE id_usuario = $id_usuario");

    // Si escribió una nueva contraseña, la actualizamos
    if (!empty($password)) {
        mysqli_query($cn, "UPDATE usuarios SET password = '$password' WHERE id_usuario = $id_usuario");
    }

    header("Location: gestion_mesas.php?msg=actualizado");
    exit();
}
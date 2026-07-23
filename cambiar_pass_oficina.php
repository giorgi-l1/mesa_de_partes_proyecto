<?php
session_start();
require 'conexion.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id_cuenta_ofi = intval($_POST['id_cuenta_ofi']);
    $id_oficina = intval($_POST['id_oficina']);
    $nueva_password = mysqli_real_escape_string($cn, $_POST['nueva_password']);

    // Actualizar la contraseña en texto plano (como la vienes manejando)
    $query = "UPDATE usuarios SET password = '$nueva_password' WHERE id_usuario = $id_cuenta_ofi";
    
    if (mysqli_query($cn, $query)) {
        header("Location: detalle_oficina.php?id=$id_oficina&msg=pass_ok");
    } else {
        header("Location: detalle_oficina.php?id=$id_oficina&error=pass_fail");
    }
    exit();
}
header("Location: Listado_Oficina.php");
exit();
?>
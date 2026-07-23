<?php
session_start();
require 'conexion.php';

$nombre = trim($_POST["nombre_oficina"]);
$siglas = strtoupper(trim($_POST["siglas"]));
$id_encargado = $_POST["id_encargado"] ?? '';

// 1. Insertar la oficina
$sql_oficina = "INSERT INTO oficinas (nombre_oficina, siglas, estado) VALUES ('$nombre', '$siglas', 1)";

if(mysqli_query($cn, $sql_oficina)) {
    $id_oficina_nueva = mysqli_insert_id($cn);

    // 2. Si se seleccionó un trabajador, crearle su cuenta Tipo 7 y ocuparlo
    if (!empty($id_encargado)) {
        $q_base = mysqli_query($cn, "SELECT * FROM datos_personales WHERE id_usuario = $id_encargado");
        if ($base = mysqli_fetch_assoc($q_base)) {
            
            // A. Crear Cuenta Operativa de Oficina (Tipo 7)
            $correo_ofi = strtolower($siglas) . ".ofi@unjfsc.edu.pe";
            $pass_ofi = "12345"; // Contraseña fija solicitada
            
            mysqli_query($cn, "INSERT INTO usuarios (correo, password, id_tipo, estado) VALUES ('$correo_ofi', '$pass_ofi', 7, 1)");
            $id_cuenta_ofi = mysqli_insert_id($cn);

            // B. Clonar datos personales
            mysqli_query($cn, "INSERT INTO datos_personales (id_usuario, nombres, apellido_paterno, apellido_materno, tipo_documento, numero_documento) 
                               VALUES ($id_cuenta_ofi, '{$base['nombres']}', '{$base['apellido_paterno']}', '{$base['apellido_materno']}', '{$base['tipo_documento']}', '{$base['numero_documento']}')");

            // C. Asignar en datos_oficina_usuario (Rol de Jefe)
            mysqli_query($cn, "INSERT INTO datos_oficina_usuario (id_usuario, id_oficina, id_rol_oficina, cargo_real) 
                               VALUES ($id_cuenta_ofi, $id_oficina_nueva, 1, 'Jefe de Oficina')");
                               
            // D. Asignar en la tabla usuario_oficina (La de tu imagen)
            mysqli_query($cn, "INSERT INTO usuario_oficina (id_usuario, id_oficina) VALUES ($id_cuenta_ofi, $id_oficina_nueva)");
            
            // E. Bloquear al trabajador base marcándolo como ocupado
            $nombre_asignacion = "Jefatura " . $siglas;
            mysqli_query($cn, "UPDATE datos_personal SET estado_asignado = '$nombre_asignacion' WHERE id_usuario = $id_encargado");
        }
    }
    header("Location: Listado_Oficina.php?msg=success");
} else {
    header("Location: Listado_Oficina.php?msg=error");
}
?>
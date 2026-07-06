<?php
session_start();

if (!isset($_SESSION["auth"]) || $_SESSION["auth"] != "1" || $_SERVER["REQUEST_METHOD"] != "POST") {
    header("Location: index.php");
    exit();
}

require 'conexion.php'; 

$id_usuario = $_SESSION["id_usuario"];
$id_tipo = $_SESSION["id_tipo"];

if ($id_tipo == 4) {
    // -----------------------------------------------------
    // ACTUALIZAR DATOS JURÍDICOS (Institución)
    // -----------------------------------------------------
    
    // Recibimos y limpiamos los datos editables
    $correo_empresarial = mysqli_real_escape_string($cn, $_POST['correo_empresarial']);
    $dir_empresa = mysqli_real_escape_string($cn, $_POST['dir_empresa']);

    $query = "UPDATE datos_juridicos 
              SET correo_empresarial = '$correo_empresarial', 
                  dir_empresa = '$dir_empresa' 
              WHERE id_usuario = '$id_usuario'";
              
    if(mysqli_query($cn, $query)){
        // Éxito, redirigimos al perfil con mensaje
        header("Location: perfil.php?status=success");
        exit();
    } else {
        echo "Error al actualizar la base de datos: " . mysqli_error($cn);
    }

} else {
    // -----------------------------------------------------
    // ACTUALIZAR DATOS PERSONALES (Alumno, Docente, Egresado)
    // -----------------------------------------------------
    
    // Recibimos y limpiamos los datos editables
    $celular = mysqli_real_escape_string($cn, $_POST['celular']);
    $telefono_fijo = mysqli_real_escape_string($cn, $_POST['telefono_fijo']);
    $correo_personal = mysqli_real_escape_string($cn, $_POST['correo_personal']);
    $direccion_texto = mysqli_real_escape_string($cn, $_POST['direccion_texto']);
    $referencia_direccion = mysqli_real_escape_string($cn, $_POST['referencia_direccion']);

    $query = "UPDATE datos_personales 
              SET celular = '$celular', 
                  telefono_fijo = '$telefono_fijo', 
                  correo_personal = '$correo_personal', 
                  direccion_texto = '$direccion_texto',
                  referencia_direccion = '$referencia_direccion'
              WHERE id_usuario = '$id_usuario'";
              
    if(mysqli_query($cn, $query)){
        // Éxito, redirigimos al perfil con mensaje
        header("Location: perfil.php?status=success");
        exit();
    } else {
        echo "Error al actualizar la base de datos: " . mysqli_error($cn);
    }
}
?>
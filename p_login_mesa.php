<?php
session_start();
require 'conexion.php'; 

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    $identificador = $_POST['identificador'];
    $password = $_POST['password'];

    // Lógica del correo (Si inician sesión sin el @)
    $dominio = "@unjfsc.edu.pe"; 
    if (strpos($identificador, '@') === false) {
        $correo_generado = $identificador . $dominio;
    } else {
        $correo_generado = $identificador;
    }

    $query = "SELECT * FROM usuarios WHERE correo = '$correo_generado'";
    $resultado = mysqli_query($cn, $query);

    if (mysqli_num_rows($resultado) == 1) {
        $usuario = mysqli_fetch_assoc($resultado);
        
        // -------------------------------------------------------------
        // VALIDACIÓN ESTRICTA: Solo entran Administradores/Mesa de partes
        // OJO: Cambia el "5" por el id_tipo real de tu base de datos 
        // que corresponda al personal de Mesa de Partes.
        // -------------------------------------------------------------
        if ($usuario['id_tipo'] != 5) {
            header("Location: login_mesa.php?error=user");
            exit();
        }
        
        // Verificamos contraseña
        if ($password === $usuario['password']) {
            
            // Credenciales correctas
            $_SESSION["auth_mesa"] = "1"; // Variable de sesión exclusiva
            $_SESSION["id_usuario"] = $usuario['id_usuario'];
            $_SESSION["id_tipo"] = $usuario['id_tipo']; 
            
            // Lo enviamos a la bandeja de administración
            header("Location: principal_mesa.php"); 
            exit();
        } else {
            // Contraseña incorrecta
            header("Location: login_mesa.php?error=pass");
            exit();
        }
    } else {
        // Usuario no encontrado
        header("Location: login_mesa.php?error=user");
        exit();
    }
} else {
    header("Location: login_mesa.php");
    exit();
}
?>
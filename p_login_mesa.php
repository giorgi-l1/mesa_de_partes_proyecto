<?php
session_start();
require 'conexion.php'; 

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Limpiamos los inputs desde el principio
    $identificador = trim($_POST['identificador']);
    $password = trim($_POST['password']);

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
        // VALIDACIÓN DE ESTADO: Verificar que no esté dado de baja
        // -------------------------------------------------------------
        if ($usuario['estado'] != 1) {
            header("Location: login_mesa.php?error=cuenta_inactiva");
            exit();
        }
        // -------------------------------------------------------------
        // VALIDACIÓN ESTRICTA: Solo entran Mesa (6) y Oficinas (7)
        // -------------------------------------------------------------
        if ($usuario['id_tipo'] != 6 && $usuario['id_tipo'] != 7) {
            header("Location: login_mesa.php?error=user_tipo_invalido");
            exit();
        }
        
        // Verificamos contraseña usando trim() por si hay espacios en la BD
        if ($password === trim($usuario['password'])) {
            
            // Credenciales correctas
            $_SESSION["auth_mesa"] = "1"; 
            $_SESSION["id_usuario"] = $usuario['id_usuario'];
            $_SESSION["id_tipo"] = $usuario['id_tipo']; 
            $_SESSION["rol"] = ($usuario['id_tipo'] == 6) ? "mesa" : "oficina";
            
            // Enrutamiento según el tipo de usuario
            if ($usuario['id_tipo'] == 6) {
                header("Location: principal_mesa.php"); 
            } else {
                header("Location: principal_oficina.php"); 
            }
            exit();

        } else {
            // Contraseña incorrecta
            header("Location: login_mesa.php?error=pass");
            exit();
        }
    } else {
        // Usuario no encontrado
        header("Location: login_mesa.php?error=user_no_existe");
        exit();
    }
} else {
    header("Location: login_mesa.php");
    exit();
}
?>
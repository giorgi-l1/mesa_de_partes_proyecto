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
        // VALIDACIÓN ESTRICTA DE TIPOS PERMITIDOS (Mesa, Oficina o Admin)
        // -------------------------------------------------------------
        if ($usuario['id_tipo'] != 6 && $usuario['id_tipo'] != 7 && $usuario['id_tipo'] != 5) {
            // Asumiendo que 5 o el ID que use tu admin es válido, o si es tipo 6/7 con correo especial.
            header("Location: login_mesa.php?error=user_tipo_invalido");
            exit();
        }
        
        // Verificamos contraseña
        if ($password === trim($usuario['password'])) {
            
            // Credenciales correctas
            $_SESSION["auth_mesa"] = "1"; 
            $_SESSION["id_usuario"] = $usuario['id_usuario'];
            $_SESSION["id_tipo"] = $usuario['id_tipo']; 

            // ---------------------------------------------------------
            // REDIRECCIÓN DISCRETA AL SUPERADMIN (Por Correo o ID)
            // ---------------------------------------------------------
            // Opción A: Por correo institucional exacto del Superadmin
            /*
            if ($usuario['correo'] === 'admin@unjfsc.edu.pe' || $usuario['correo'] === 'admin_general@unjfsc.edu.pe') {
                $_SESSION["rol"] = "superadmin";
                header("Location: principal_admin.php");
                exit();
            }
            */
            // Opción B: Si prefieres por ID único de usuario (descomentar si usas esta):
            if ($usuario['id_usuario'] == 26) {
                $_SESSION["rol"] = "superadmin";
                header("Location: principal_admin.php");
                exit();
            }
            

            // ---------------------------------------------------------
            // ENRUTAMIENTO REGULAR DE MESA Y OFICINAS
            // ---------------------------------------------------------
            $_SESSION["rol"] = ($usuario['id_tipo'] == 6) ? "mesa" : "oficina";

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
<?php
session_start();
require 'conexion.php'; // Llamamos a tu conexión

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // 1. Recibimos los datos del formulario (AHORA INCLUIMOS EL TIPO)
    $identificador = $_POST['identificador'];
    $password = $_POST['password'];
    $tipo_login = $_POST['tipo_login']; // <-- El name del <select> de tu index

    // 2. Lógica "Por debajo de la mesa" para el correo
    $dominio = "@unjfsc.edu.pe"; 
    
    if (strpos($identificador, '@') === false) {
        $correo_generado = $identificador . $dominio;
    } else {
        $correo_generado = $identificador;
    }

    // 3. Buscamos al usuario en la BD
    $query = "SELECT * FROM usuarios WHERE correo = '$correo_generado'";
    $resultado = mysqli_query($cn, $query);

    if (mysqli_num_rows($resultado) == 1) {
        $usuario = mysqli_fetch_assoc($resultado);
        // VALIDACIÓN DE ESTADO: Si el usuario fue dado de baja (estado 0), lo rebotamos
        if ($usuario['estado'] == 0) {
            header("Location: index.php?error=inactivo");
            exit();
        }
        // 4. NUEVA VALIDACIÓN: ¿El tipo que eligió coincide con su tipo real en la BD?
        if ($tipo_login != $usuario['id_tipo']) {
            // Si eligió "Alumno" pero en la BD es "Docente", lo rebotamos
            header("Location: index.php?error=tipo");
            exit();
        }
        
        // 5. Verificamos si la contraseña coincide (SIN encriptar, según tu código actual)
        if ($password === $usuario['password']) {
            
            // Credenciales y tipo correctos
            $_SESSION["auth"] = "1";
            $_SESSION["id_usuario"] = $usuario['id_usuario'];
            $_SESSION["id_tipo"] = $usuario['id_tipo']; 
            
            // Lo enviamos a la plataforma principal
            header("Location: principal.php");
            exit();
        } else {
            // Contraseña incorrecta
            header("Location: index.php?error=pass");
            exit();
        }
    } else {
        // Usuario no encontrado
        header("Location: index.php?error=user");
        exit();
    }
} else {
    // Si alguien intenta entrar por URL directa
    header("Location: index.php");
    exit();
}
?>
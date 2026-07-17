<?php
session_start();
// Validar sesión de usuario (Ajusta la variable de sesión según tu login de alumnos)
if (!isset($_SESSION["id_usuario"])) {
    header("Location: index.php");
    exit();
}

require 'conexion.php';
$id_usuario = $_SESSION["id_usuario"];

// Si el usuario envió el formulario para guardar cambios
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = isset($_POST['recibir_emails']) ? 1 : 0;
    $wa = isset($_POST['recibir_whatsapp']) ? 1 : 0;
    $tel = mysqli_real_escape_string($cn, $_POST['telefono_whatsapp']);
    
    $a_derivado = isset($_POST['alerta_derivado']) ? 1 : 0;
    $a_rechazado = isset($_POST['alerta_rechazado']) ? 1 : 0;
    $a_finalizado = isset($_POST['alerta_finalizado']) ? 1 : 0;

    // Verificar si ya tiene configuración
    $check = mysqli_query($cn, "SELECT id_config FROM configuracion_alertas WHERE id_usuario = '$id_usuario'");
    
    if (mysqli_num_rows($check) > 0) {
        mysqli_query($cn, "UPDATE configuracion_alertas SET 
            recibir_emails = $email, recibir_whatsapp = $wa, telefono_whatsapp = '$tel',
            alerta_derivado = $a_derivado, alerta_rechazado = $a_rechazado, alerta_finalizado = $a_finalizado 
            WHERE id_usuario = '$id_usuario'");
    } else {
        mysqli_query($cn, "INSERT INTO configuracion_alertas 
            (id_usuario, recibir_emails, recibir_whatsapp, telefono_whatsapp, alerta_derivado, alerta_rechazado, alerta_finalizado) 
            VALUES ('$id_usuario', $email, $wa, '$tel', $a_derivado, $a_rechazado, $a_finalizado)");
    }
    $mensaje_ok = "¡Configuración guardada exitosamente!";
}

// Obtener datos actuales para mostrarlos en los checkboxes
$query_config = mysqli_query($cn, "SELECT * FROM configuracion_alertas WHERE id_usuario = '$id_usuario'");
$config = mysqli_fetch_assoc($query_config);

// Valores por defecto si es su primera vez
$c_email = isset($config['recibir_emails']) ? $config['recibir_emails'] : 1;
$c_wa = isset($config['recibir_whatsapp']) ? $config['recibir_whatsapp'] : 0;
$c_tel = isset($config['telefono_whatsapp']) ? $config['telefono_whatsapp'] : '';
$c_derivado = isset($config['alerta_derivado']) ? $config['alerta_derivado'] : 1;
$c_rechazado = isset($config['alerta_rechazado']) ? $config['alerta_rechazado'] : 1;
$c_finalizado = isset($config['alerta_finalizado']) ? $config['alerta_finalizado'] : 1;
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Configuración de Notificaciones</title>
    <link rel="stylesheet" href="css/dashboard.css">
    <style>
        .config-panel { max-width: 600px; margin: 40px auto; background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
        .form-switch { display: flex; align-items: center; margin-bottom: 15px; justify-content: space-between; border-bottom: 1px solid #eee; padding-bottom: 10px; }
        .form-switch input[type="checkbox"] { width: 20px; height: 20px; cursor: pointer; }
        .alerta-box { background: #f8f9fa; padding: 15px; border-radius: 8px; margin-top: 20px; }
        .btn-guardar { background: #007bff; color: white; border: none; padding: 12px 20px; border-radius: 5px; cursor: pointer; width: 100%; font-weight: bold; margin-top: 20px;}
    </style>
</head>
<body>
  <nav class="navbar">
        <div class="navbar-brand">UNJFSC <span>| Mesa de Partes</span></div>
        <div class="nav-links">
            <a href="principal.php" class="active">Inicio</a>

            <div class="dropdown">
                <a class="dropbtn">Trámites ▼</a>
                <div class="dropdown-content">
                    <a href="nuevo_tramite.php">Iniciar Nuevo Trámite</a>
                    <a href="mis_tramites.php">Revisar Mis Trámites</a>
                    <a href="historial_tramites.php">Historial Completo</a>
                </div>
            </div>

            <div class="dropdown">
                <a class="dropbtn">Mi Cuenta ▼</a>
                <div class="dropdown-content">
                    <a href="perfil.php">Actualizar Datos Personales</a>
                    <a href="perfil_foto.php">Cambiar Foto de Perfil</a>
                    <a href="cambiar_password.php">Cambiar Contraseña</a>
                    <a href="configuracion_alertas.php">Notificaciones</a>
                </div>
            </div>
            <div class="dropdown">
                <a class="dropbtn">Mesa de Ayuda ▼</a>
                <div class="dropdown-content">
                    <a href="mesa_ayuda.php">Registrar Reclamo / Ticket</a>
                    <a href="detalle_queja_usuario.php">Visualizar mis Reclamos</a>
                </div>
            </div>
            <a href="cerrar_session.php" class="btn-logout">Cerrar Sesión</a>
        </div>
    </nav>
    
    <div class="container">
        <div class="config-panel">
            <h2>Preferencias de Notificación</h2>
            <p>Elige cómo y cuándo deseas que te avisemos sobre el avance de tus trámites.</p>
            
            <?php if(isset($mensaje_ok)) echo "<p style='color:green; font-weight:bold;'>$mensaje_ok</p>"; ?>

            <form method="POST" action="">
                <h3>Canales de Comunicación</h3>
                <div class="form-switch">
                    <label><strong>✉️ Recibir correos electrónicos</strong></label>
                    <input type="checkbox" name="recibir_emails" <?php if($c_email) echo "checked"; ?>>
                </div>
                
                <div class="form-switch" style="border:none;">
                    <label><strong>📱 Recibir mensajes de WhatsApp</strong></label>
                    <input type="checkbox" name="recibir_whatsapp" <?php if($c_wa) echo "checked"; ?>>
                </div>
                <div style="margin-bottom: 20px;">
                    <label style="font-size:12px; color:#666;">Número de WhatsApp (Ej: 51999888777):</label>
                    <input type="text" name="telefono_whatsapp" value="<?php echo htmlspecialchars($c_tel); ?>" style="width:100%; padding:8px; border:1px solid #ccc; border-radius:4px;">
                </div>

                <div class="alerta-box">
                    <h3>¿Qué deseas que te notifiquemos?</h3>
                    <div class="form-switch">
                        <label>Cuando mi trámite cambie de oficina (Derivado)</label>
                        <input type="checkbox" name="alerta_derivado" <?php if($c_derivado) echo "checked"; ?>>
                    </div>
                    <div class="form-switch">
                        <label>Cuando mi trámite tenga un error (Observado/Rechazado)</label>
                        <input type="checkbox" name="alerta_rechazado" <?php if($c_rechazado) echo "checked"; ?>>
                    </div>
                    <div class="form-switch" style="border:none;">
                        <label>Cuando mi trámite sea Atendido y Finalizado</label>
                        <input type="checkbox" name="alerta_finalizado" <?php if($c_finalizado) echo "checked"; ?>>
                    </div>
                </div>

                <button type="submit" class="btn-guardar">Guardar Configuración</button>
            </form>
        </div>
    </div>
</body>
</html>
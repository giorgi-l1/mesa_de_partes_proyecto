<?php
session_start();

if (!isset($_SESSION["auth"]) || $_SESSION["auth"] != "1") {
    header("Location: index.php");
    exit();
}

require 'conexion.php'; 

$id_usuario = $_SESSION["id_usuario"];
$id_tipo = $_SESSION["id_tipo"];

// Variables para almacenar los datos (se llenarán según el tipo)
$datos = [];

if ($id_tipo == 4) { // INSTITUCIÓN
    $query = "SELECT u.correo, j.ruc, j.razon_social, j.correo_empresarial, j.dir_empresa 
              FROM usuarios u 
              INNER JOIN datos_juridicos j ON u.id_usuario = j.id_usuario 
              WHERE u.id_usuario = '$id_usuario'";
    $resultado = mysqli_query($cn, $query);
    $datos = mysqli_fetch_assoc($resultado);
} else { // ALUMNO, DOCENTE O EGRESADO
    $query = "SELECT u.correo, p.numero_documento, p.nombres, p.apellido_paterno, p.apellido_materno, 
                     p.correo_personal, p.direccion_texto, p.referencia_direccion, p.telefono_fijo, p.celular 
              FROM usuarios u 
              INNER JOIN datos_personales p ON u.id_usuario = p.id_usuario 
              WHERE u.id_usuario = '$id_usuario'";
    $resultado = mysqli_query($cn, $query);
    $datos = mysqli_fetch_assoc($resultado);
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Perfil | UNJFSC</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600&family=Montserrat:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/dashboard.css">
</head>
<body>
    <nav class="navbar">
        <div class="navbar-brand">UNJFSC <span>| Mesa de Partes</span></div>
        <div class="nav-links">
            <a href="principal.php">Volver al Inicio</a>
        </div>
    </nav>

    <div class="container">
        <div class="panel">
            <h2 class="panel-title">Actualizar Mis Datos</h2>

            <?php
            if (isset($_GET['status']) && $_GET['status'] == 'success') {
                echo '<div class="alert alert-success">¡Tus datos han sido actualizados correctamente!</div>';
            }
            ?>

            <form action="p_perfil.php" method="POST">
                <div class="form-grid">
                    
                    <div class="form-group full-width">
                        <label>Correo Institucional / Login</label>
                        <input type="email" value="<?php echo $datos['correo']; ?>" readonly>
                        <span class="helper-text">* El correo de inicio de sesión no puede ser modificado.</span>
                    </div>

                    <?php if ($id_tipo == 4): ?>
                        <div class="form-group">
                            <label>RUC</label>
                            <input type="text" value="<?php echo $datos['ruc']; ?>" readonly>
                        </div>
                        <div class="form-group">
                            <label>Razón Social</label>
                            <input type="text" value="<?php echo $datos['razon_social']; ?>" readonly>
                        </div>

                        <div class="form-group full-width" style="margin-top: 15px; border-top: 1px solid #eee; padding-top: 15px;">
                            <h3 style="color: var(--dorado-arena); margin-bottom: 10px;">Datos de Contacto (Editables)</h3>
                        </div>

                        <div class="form-group">
                            <label>Correo de Contacto Empresarial</label>
                            <input type="email" name="correo_empresarial" value="<?php echo $datos['correo_empresarial']; ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Dirección Fiscal</label>
                            <input type="text" name="dir_empresa" value="<?php echo $datos['dir_empresa']; ?>" required>
                        </div>

                    <?php else: ?>
                        <div class="form-group">
                            <label>DNI / Documento</label>
                            <input type="text" value="<?php echo $datos['numero_documento']; ?>" readonly>
                        </div>
                        <div class="form-group">
                            <label>Nombres</label>
                            <input type="text" value="<?php echo $datos['nombres']; ?>" readonly>
                        </div>
                        <div class="form-group">
                            <label>Apellido Paterno</label>
                            <input type="text" value="<?php echo $datos['apellido_paterno']; ?>" readonly>
                        </div>
                        <div class="form-group">
                            <label>Apellido Materno</label>
                            <input type="text" value="<?php echo $datos['apellido_materno']; ?>" readonly>
                        </div>

                        <div class="form-group full-width" style="margin-top: 15px; border-top: 1px solid #eee; padding-top: 15px;">
                            <h3 style="color: var(--dorado-arena); margin-bottom: 10px;">Datos de Contacto (Editables)</h3>
                        </div>

                        <div class="form-group">
                            <label>Celular</label>
                            <input type="text" name="celular" value="<?php echo $datos['celular']; ?>" required maxlength="15">
                        </div>
                        <div class="form-group">
                            <label>Teléfono Fijo (Opcional)</label>
                            <input type="text" name="telefono_fijo" value="<?php echo $datos['telefono_fijo']; ?>" maxlength="15">
                        </div>
                        <div class="form-group">
                            <label>Correo Personal (Opcional)</label>
                            <input type="email" name="correo_personal" value="<?php echo $datos['correo_personal']; ?>">
                        </div>
                        <div class="form-group full-width">
                            <label>Dirección de Residencia</label>
                            <input type="text" name="direccion_texto" value="<?php echo $datos['direccion_texto']; ?>" required>
                        </div>
                        <div class="form-group full-width">
                            <label>Referencia de Dirección</label>
                            <input type="text" name="referencia_direccion" value="<?php echo $datos['referencia_direccion']; ?>">
                        </div>
                    <?php endif; ?>

                </div>
                
                <button type="submit" class="btn-submit">GUARDAR CAMBIOS</button>
            </form>
        </div>
    </div>
</body>
</html>
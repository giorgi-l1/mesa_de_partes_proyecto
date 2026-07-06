<?php
session_start();

if (!isset($_SESSION["auth"]) || $_SESSION["auth"] != "1") {
    header("Location: index.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cambiar Contraseña | UNJFSC</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600&family=Montserrat:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/dashboard.css">
</head>
<body>
    <nav class="navbar">
        <div class="navbar-brand">UNJFSC <span>| Seguridad</span></div>
        <div class="nav-links">
            <a href="principal.php">Volver al Inicio</a>
        </div>
    </nav>

    <div class="container-sm">
        <div class="panel">
            <h2 class="panel-title">Modificar Contraseña de Acceso</h2>

            <?php
            if (isset($_GET['error'])) {
                $error = $_GET['error'];
                if ($error == 'vacia') echo '<div class="alert" style="background:#f8d7da; color:#721c24;">Todos los campos son obligatorios.</div>';
                if ($error == 'coincidencia') echo '<div class="alert" style="background:#f8d7da; color:#721c24;">La nueva contraseña y la confirmación no coinciden.</div>';
                if ($error == 'incorrecta') echo '<div class="alert" style="background:#f8d7da; color:#721c24;">La contraseña actual ingresada es incorrecta.</div>';
                if ($error == 'db') echo '<div class="alert" style="background:#f8d7da; color:#721c24;">Ocurrió un problema técnico al actualizar los datos.</div>';
            }
            if (isset($_GET['status']) && $_GET['status'] == 'success') {
                echo '<div class="alert alert-success">¡Contraseña cambiada exitosamente! Usa tu nueva clave el próximo inicio de sesión.</div>';
            }
            ?>

            <form action="p_cambiar_password.php" method="POST">
                <div class="form-grid">
                    
                    <div class="form-group full-width">
                        <label>Contraseña Actual</label>
                        <input type="password" name="pass_actual" required>
                    </div>

                    <div class="form-group">
                        <label>Nueva Contraseña</label>
                        <input type="password" name="pass_nueva" required minlength="4">
                    </div>

                    <div class="form-group">
                        <label>Confirmar Nueva Contraseña</label>
                        <input type="password" name="pass_confirmar" required minlength="4">
                    </div>

                </div>
                
                <button type="submit" class="btn-submit">ACTUALIZAR CONTRASEÑA</button>
            </form>
        </div>
    </div>
</body>
</html>
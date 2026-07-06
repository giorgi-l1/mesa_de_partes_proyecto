<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso Administrativo | Mesa de Partes UNJFSC</title>
    <link rel="stylesheet" href="css/estilos.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,600;1,400&family=Montserrat:wght@300;400;500&display=swap" rel="stylesheet">
</head>

<body>
    <div class="overlay"></div>

    <div class="login-card">
        <div class="login-header">
            <h1>MESA DE PARTES</h1>
            <p>Acceso exclusivo para personal administrativo</p>
        </div>

        <?php
        if (isset($_GET['error'])) {
            if ($_GET['error'] == 'pass') {
                echo '<div class="alerta-error" style="color:red; margin-bottom:10px; text-align:center;">Contraseña incorrecta.</div>';
            } else if ($_GET['error'] == 'user') {
                echo '<div class="alerta-error" style="color:red; margin-bottom:10px; text-align:center;">Usuario no encontrado o no tiene permisos.</div>';
            }
        }
        ?>

        <form action="p_login_mesa.php" method="POST">
            
            <div class="form-group">
                <label for="identificador">Usuario / Correo Institucional</label>
                <input type="text" id="identificador" name="identificador" required autocomplete="username" placeholder="Ej: admin / nombre@unjfsc.edu.pe">
            </div>

            <div class="form-group">
                <label for="password">Contraseña</label>
                <input type="password" id="password" name="password" required>
            </div>

            <button type="submit" class="btn-submit">INGRESAR AL SISTEMA</button>

            <div style="text-align: right; margin-top: 25px;">
                <a href="index.php" onmouseover="this.style.color='#c5a059'" onmouseout="this.style.color='#666'"
                    style="color: #666; font-size: 0.85rem; text-decoration: none; border-bottom: 1px dashed #ccc; transition: color 0.3s ease;">
                    ¿Eres usuario regular? Volver al inicio
                </a>
            </div>

        </form>
    </div>
</body>

</html>
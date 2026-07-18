<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso | Mesa de Partes UNJFSC</title>
    <link rel="stylesheet" href="css/estilos.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,600;1,400&family=Montserrat:wght@300;400;500&display=swap"
        rel="stylesheet">


</head>

<body>
    <div class="overlay"></div>

    <div class="login-card">
        <div class="login-header">
            <h1>LOGIN</h1>
            <p>Universidad Nacional José Faustino Sánchez Carrión</p>
        </div>

        <?php
        // Mostrar errores si p_login.php nos devuelve aquí con algún problema
        if (isset($_GET['error'])) {
            if ($_GET['error'] == 'pass') {
                echo '<div class="alerta-error">Contraseña incorrecta. Intente nuevamente.</div>';
            } else if ($_GET['error'] == 'user') {
                echo '<div class="alerta-error">El usuario no está registrado.</div>';
            }
        }
        ?>

        <form action="p_login.php" method="POST">
            <div class="form-group">
                <label for="tipo_login">Tipo de Perfil</label>
                <select name="tipo_login" id="tipo_login" required>
                    <option value="" disabled selected>-- Seleccione --</option>
                    <option value="1">Persona Natural</option>
                    <option value="2">Alumno</option>
                    <option value="3">Personal</option>
                    <option value="4">Institución Externa</option>
                    <option value="5">Egresado</option>
                </select>
            </div>
            <div class="form-group">
                <label for="identificador">RUC / Código</label>
                <input type="text" id="identificador" name="identificador" required autocomplete="username"
                    placeholder="Ej: 12345678">
            </div>

            <div class="form-group">
                <label for="password">Contraseña</label>
                <input type="password" id="password" name="password" required>
            </div>

            <button type="submit" class="btn-submit">INGRESAR</button>

            <div style="display: flex; justify-content: space-between; margin-top: 25px;">
                <a href="consulta.php" onmouseover="this.style.color='#c5a059'" onmouseout="this.style.color='#666'"
                    style="color: #666; font-size: 0.85rem; text-decoration: none; border-bottom: 1px dashed #ccc; transition: color 0.3s ease;">
                    ¿Consultar un expediente?
                </a>
                
                <a href="login_mesa.php" onmouseover="this.style.color='#c5a059'" onmouseout="this.style.color='#666'"
                    style="color: #666; font-size: 0.85rem; text-decoration: none; border-bottom: 1px dashed #ccc; transition: color 0.3s ease;">
                    ¿Eres usuario de Mesa/Oficina?
                </a>
            </div>

        </form>

        <div class="register-link">
            ¿No tienes una cuenta? <br><br> <a href="registro.php">Solicitar Registro</a>
        </div>
    </div>
</body>

</html>
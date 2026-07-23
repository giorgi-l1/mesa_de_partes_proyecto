<?php
session_start();
require 'conexion.php';

// Consultar SOLO a los trabajadores (Ej: id_tipo = 3 "Personal") que están LIBRES
$q_libres = "SELECT u.id_usuario, dp.nombres, dp.apellido_paterno, dp.apellido_materno, dp.numero_documento
             FROM usuarios u
             INNER JOIN datos_personales dp ON u.id_usuario = dp.id_usuario
             LEFT JOIN datos_personal dper ON u.id_usuario = dper.id_usuario
             WHERE u.id_tipo = 3 
               AND (dper.estado_asignado IS NULL OR dper.estado_asignado = '')
               AND u.estado = 1";
$res_libres = mysqli_query($cn, $q_libres);
// Consultar qué ventanillas ya están en uso
$q_ventanillas = "SELECT estado_asignado FROM datos_personal WHERE estado_asignado LIKE 'Mesa %'";
$res_vent = mysqli_query($cn, $q_ventanillas);
$ventanillas_ocupadas = [];
while($v = mysqli_fetch_assoc($res_vent)){
    // Extraemos solo el número (ej: de "Mesa 1" extrae "1")
    $ventanillas_ocupadas[] = str_replace('Mesa ', '', $v['estado_asignado']);
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Asignar Trabajador a Mesa | UNJFSC</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,600;1,400&family=Montserrat:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">

    <link rel="stylesheet" href="css/dashboard.css">
</head>

<body>

    <?php include 'cabecera_admin.php'; ?>

    <div class="container-sm">
        <a href="gestion_mesas.php" class="btn-volver">← Volver al listado</a>

        <div class="panel">
            <h2 class="panel-title">Habilitar Ventanilla de Mesa de Partes</h2>
            <p class="panel-subtitulo">Seleccione un trabajador disponible para generar su cuenta operativa de
                Ventanilla.</p>

            <form action="p_crear_mesa.php" method="POST">
                <div class="form-grid">

                    <div class="form-group full-width">
                        <label>Trabajador Disponible (Personal)</label>
                        <select name="id_trabajador_base" required>
                            <option value="" disabled selected>-- Seleccione un trabajador libre --</option>
                            <?php while ($t = mysqli_fetch_assoc($res_libres)): ?>
                                <option value="<?= $t['id_usuario'] ?>">
                                    <?= htmlspecialchars($t['numero_documento'] . ' - ' . $t['nombres'] . ' ' . $t['apellido_paterno']) ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Ventanilla a Asignar</label>
                        <select name="numero_ventanilla" required>
                            <option value="" disabled selected>-- Seleccione Ventanilla Libre --</option>
                            <?php for($i = 1; $i <= 5; $i++): ?>
                                <?php if(!in_array($i, $ventanillas_ocupadas)): ?>
                                    <option value="<?= $i ?>">Ventanilla <?= $i ?></option>
                                <?php endif; ?>
                            <?php endfor; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Contraseña de Acceso (Para la Mesa)</label>
                        <input type="password" name="password_mesa" required placeholder="Contraseña para operar">
                    </div>

                </div>

                <div class="detalle-observacion" style="margin-top: 20px;">
                    <strong>Nota:</strong> Se autogenerará el correo: <em>mesa[N] + [3 letras nombre] + [3 letras
                        apellido] + .ID</em>
                </div>

                <button type="submit" class="btn-submit" style="margin-top: 20px;">Generar Cuenta y Asignar</button>
            </form>
        </div>
    </div>
</body>

</html>
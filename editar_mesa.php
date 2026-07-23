<?php
session_start();
require 'conexion.php';

if (!isset($_GET['id'])) {
    header("Location: gestion_mesas.php");
    exit();
}

$id_usuario = intval($_GET['id']);
$q_info = "SELECT u.id_usuario, u.correo, dp.nombres, dp.apellido_paterno, dper.cargo, dper.estado_asignado 
           FROM usuarios u
           LEFT JOIN datos_personales dp ON u.id_usuario = dp.id_usuario
           LEFT JOIN datos_personal dper ON u.id_usuario = dper.id_usuario
           WHERE u.id_usuario = $id_usuario";
$res_info = mysqli_query($cn, $q_info);
$usuario = mysqli_fetch_assoc($res_info);
// Consultar qué ventanillas ya están en uso
$q_ventanillas = "SELECT estado_asignado FROM datos_personal WHERE estado_asignado LIKE 'Mesa %'";
$res_vent = mysqli_query($cn, $q_ventanillas);
$ventanillas_ocupadas = [];
while ($v = mysqli_fetch_assoc($res_vent)) {
    // Extraemos solo el número (ej: de "Mesa 1" extrae "1")
    $ventanillas_ocupadas[] = str_replace('Mesa ', '', $v['estado_asignado']);
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Editar Mesa | UNJFSC</title>
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
            <h2 class="panel-title">Editar Ventanilla:
                <?= htmlspecialchars($usuario['nombres'] . ' ' . $usuario['apellido_paterno']) ?></h2>
            <form action="p_editar_mesa.php" method="POST">
                <input type="hidden" name="id_usuario" value="<?= $usuario['id_usuario'] ?>">

                <div class="form-grid">
                    <div class="form-group full-width">
                        <label>Cuenta Operativa (No modificable)</label>
                        <input type="text" value="<?= htmlspecialchars($usuario['correo']) ?>" readonly>
                    </div>

                    <div class="form-group">
                        <label>Cargo Operativo</label>
                        <input type="text" name="cargo" value="Asistente UTD" readonly
                            style="background-color: #e9ecef; cursor: not-allowed;" title="El cargo base es fijo">
                    </div>

                    <div class="form-group">
                        <label>Estado Asignado (Ubicación)</label>
                        <select name="estado_asignado" required>
                            <?php for ($i = 1; $i <= 5; $i++):
                                $nombre_mesa = "Mesa " . $i;
                                $selected = ($usuario['estado_asignado'] == $nombre_mesa) ? 'selected' : '';
                                ?>
                                <option value="" disabled selected>-- Seleccione Ventanilla Libre --</option>
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <?php if (!in_array($i, $ventanillas_ocupadas)): ?>
                                        <option value="<?= $i ?>">Ventanilla <?= $i ?></option>
                                    <?php endif; ?>
                                <?php endfor; ?>
                            <?php endfor; ?>
                        </select>
                    </div>

                    <div class="form-group full-width">
                        <label>Nueva Contraseña (Dejar en blanco para no cambiar)</label>
                        <input type="password" name="password"
                            placeholder="Solo llenar si desea resetear la clave de esta ventanilla">
                    </div>
                </div>
                <button type="submit" class="btn-submit" style="margin-top: 20px;">Actualizar Datos</button>
            </form>
        </div>
    </div>
</body>

</html>
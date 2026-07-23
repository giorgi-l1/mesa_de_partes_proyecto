<?php
session_start();
require 'conexion.php';

if (!isset($_GET['id'])) {
    header("Location: gestion_mesas.php");
    exit();
}

$id_usuario = intval($_GET['id']);
$q_info = "SELECT u.*, dp.*, dper.cargo, dper.estado_asignado, ofi.nombre_oficina 
           FROM usuarios u
           LEFT JOIN datos_personales dp ON u.id_usuario = dp.id_usuario
           LEFT JOIN datos_personal dper ON u.id_usuario = dper.id_usuario
           LEFT JOIN usuario_oficina uo ON u.id_usuario = uo.id_usuario
           LEFT JOIN oficinas ofi ON uo.id_oficina = ofi.id_oficina
           WHERE u.id_usuario = $id_usuario";
$res_info = mysqli_query($cn, $q_info);
$usuario = mysqli_fetch_assoc($res_info);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Detalles Ventanilla | UNJFSC</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,600;1,400&family=Montserrat:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">

    <link rel="stylesheet" href="css/dashboard.css">
</head>
<body>
    <?php include 'cabecera_admin.php'; ?>   
    <div class="container">
        <a href="gestion_mesas.php" class="btn-volver">← Volver al listado</a>
        <div class="panel">
            <div class="panel-header-flex" style="margin-bottom: 20px; align-items: center;">
                <div>
                    <h2 class="panel-title panel-title-clean">Detalles Operativos</h2>
                    <p class="panel-subtitulo" style="margin-top: 5px;">Cargo: <span class="pill-cargo"><?= htmlspecialchars($usuario['cargo'] ?? 'No asignado') ?></span></p>
                </div>
                <div class="detalle-header-acciones">
                    <?php if ($usuario['estado'] == 1): ?>
                        <span class="status-badge status-atendido" style="font-size: 0.9rem; padding: 8px 15px;">Estado: Activo</span>
                    <?php else: ?>
                        <span class="status-badge status-rechazado" style="font-size: 0.9rem; padding: 8px 15px;">Estado: Inactivo (Baja)</span>
                    <?php endif; ?>
                </div>
            </div>
            <hr style="border: 0; border-top: 1px solid #eee; margin-bottom: 25px;">
            <div class="detalle-grid">
                <div class="detalle-item">
                    <span class="detalle-label">Cuenta Institucional</span>
                    <span class="detalle-valor"><strong><?= htmlspecialchars($usuario['correo']) ?></strong></span>
                </div>
                <div class="detalle-item">
                    <span class="detalle-label">Trabajador Vinculado</span>
                    <span class="detalle-valor"><?= htmlspecialchars($usuario['nombres'] . ' ' . $usuario['apellido_paterno'] . ' ' . $usuario['apellido_materno']) ?></span>
                </div>
                <div class="detalle-item">
                    <span class="detalle-label">Documento (DNI)</span>
                    <span class="detalle-valor"><?= htmlspecialchars($usuario['numero_documento']) ?></span>
                </div>
                <div class="detalle-item">
                    <span class="detalle-label">Oficina Asignada</span>
                    <span class="detalle-valor"><?= htmlspecialchars($usuario['nombre_oficina'] ?? 'Sin asignar') ?></span>
                </div>
                <div class="detalle-item">
                    <span class="detalle-label">Puesto Físico</span>
                    <span class="detalle-valor"><?= htmlspecialchars($usuario['estado_asignado'] ?? 'Sin ubicación') ?></span>
                </div>
            </div>
        </div>
    </div>
</body>
</html>

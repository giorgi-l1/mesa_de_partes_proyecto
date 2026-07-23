<?php
session_start();
require 'conexion.php';

if (!isset($_GET['id'])) {
    header("Location: gestion_usuarios.php");
    exit();
}

$id_usuario = intval($_GET['id']);

// Traer toda la información general
$q_info = "SELECT u.*, t.nombre_tipo, dp.* 
           FROM usuarios u
           INNER JOIN tipos_usuario t ON u.id_tipo = t.id_tipo
           LEFT JOIN datos_personales dp ON u.id_usuario = dp.id_usuario
           WHERE u.id_usuario = $id_usuario";

$res_info = mysqli_query($cn, $q_info);
$usuario = mysqli_fetch_assoc($res_info);

if (!$usuario) {
    die("Usuario no encontrado.");
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalles del Usuario | UNJFSC</title>
    <!-- Fuentes Google -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,600;1,400&family=Montserrat:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Tu CSS Principal -->
    <link rel="stylesheet" href="css/dashboard.css">
</head>
<body>

    <!-- Cabecera dinámica -->
    <?php include 'cabecera_admin.php'; ?>   

    <div class="container">
        <a href="gestion_usuarios.php" class="btn-volver">← Volver al listado</a>
        
        <div class="panel">
            <!-- CORRECCIÓN AQUÍ: Usamos panel-title y panel-subtitulo -->
            <div class="panel-header-flex" style="margin-bottom: 20px; align-items: center;">
                <div>
                    <h2 class="panel-title panel-title-clean">Detalles del Usuario</h2>
                    <p class="panel-subtitulo" style="margin-top: 5px;">Perfil asignado: <span class="pill-cargo"><?= htmlspecialchars($usuario['nombre_tipo']) ?></span></p>
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
                    <span class="detalle-label">ID del Sistema</span>
                    <span class="detalle-valor"><?= $usuario['id_usuario'] ?></span>
                </div>
                
                <div class="detalle-item">
                    <span class="detalle-label">Correo de Acceso</span>
                    <span class="detalle-valor"><strong><?= htmlspecialchars($usuario['correo']) ?></strong></span>
                </div>

                <div class="detalle-item">
                    <span class="detalle-label">Nombres Completos</span>
                    <span class="detalle-valor">
                        <?= htmlspecialchars($usuario['nombres'] ?? '') ?> 
                        <?= htmlspecialchars(($usuario['apellido_paterno'] ?? '') . ' ' . ($usuario['apellido_materno'] ?? '')) ?>
                    </span>
                </div>

                <div class="detalle-item">
                    <span class="detalle-label">Documento de Identidad</span>
                    <span class="detalle-valor">
                        <?= htmlspecialchars(($usuario['tipo_documento'] ?? 'DNI') . ': ' . ($usuario['numero_documento'] ?? 'No registrado')) ?>
                    </span>
                </div>

                <div class="detalle-item">
                    <span class="detalle-label">Celular / Teléfono</span>
                    <span class="detalle-valor">
                        <?= !empty($usuario['celular']) ? htmlspecialchars($usuario['celular']) : '<em class="sin-datos">No registrado</em>' ?>
                    </span>
                </div>
            </div>
        </div>
    </div>
         
</body>
</html>